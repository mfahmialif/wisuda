<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimkeuApp
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;

    /**
     * Mapping jenis pembayaran dari Wisuda ke SIMKEU.
     *
     * Wisuda DB:  'Transfer Bank', 'Tunai', 'Lain-lain'
     * SIMKEU:     'deposit', 'cash', 'transfer', 'yayasan'
     */
    public const JENIS_PEMBAYARAN_MAP = [
        'transfer bank' => 'transfer',
        'tunai'         => 'cash',
        'lain-lain'     => 'deposit',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('simkeu.v2_base_url', ''), '/');
        $this->apiKey  = config('simkeu.v2_api_key', '');
        $this->timeout = config('simkeu.v2_timeout', 30);
    }

    /**
     * Konversi jenis pembayaran dari format Wisuda ke format SIMKEU.
     *
     * @param string|null $jenisPembayaran  Nilai dari DB wisuda (Transfer Bank, Tunai, Lain-lain)
     * @return string  Nilai untuk SIMKEU (transfer, cash, deposit, yayasan)
     */
    public static function mapJenisPembayaran(?string $jenisPembayaran): string
    {
        $key = strtolower(trim($jenisPembayaran ?? ''));
        return self::JENIS_PEMBAYARAN_MAP[$key] ?? 'cash';
    }

    /**
     * Kirim satu data pembayaran wisuda ke SIMKEU.
     *
     * Contoh curl:
     * curl -X POST "{BASE_URL}/api/helper/pembayaran-wisuda" \
     *   -H "Accept: application/json" \
     *   -H "Content-Type: application/json" \
     *   -H "apikey: ISI_API_KEY_KAMU" \
     *   -d '{
     *     "nim": "2021001001",
     *     "jenis_pembayaran": "transfer",
     *     "th_akademik_kode": "20252",
     *     "tanggal": "2026-05-30 12:00:00",
     *     "jumlah": 500000
     *   }'
     *
     * @param array $data [nim, jenis_pembayaran, th_akademik_kode, tanggal, jumlah]
     * @return array ['success' => bool, 'message' => string, 'response' => mixed]
     */
    public function kirimPembayaranWisuda(array $data): array
    {
        $url = "{$this->baseUrl}/api/helper/pembayaran-wisuda";

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'apikey'       => $this->apiKey,
            ])
            ->withoutVerifying()
            ->withoutRedirecting()
            ->timeout($this->timeout)
            ->post($url, $data);

            $httpStatus  = $response->status();
            $body        = $response->json() ?? [];
            $rawBody     = $response->body();

            // Jika redirect (301/302/307/308), ambil Location header
            $redirectUrl = null;
            if (in_array($httpStatus, [301, 302, 307, 308])) {
                $redirectUrl = $response->header('Location');

                // Kirim ulang POST ke URL redirect
                if ($redirectUrl) {
                    $response = Http::withHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                        'apikey'       => $this->apiKey,
                    ])
                    ->withoutVerifying()
                    ->withoutRedirecting()
                    ->timeout($this->timeout)
                    ->post($redirectUrl, $data);

                    $httpStatus = $response->status();
                    $body       = $response->json() ?? [];
                    $rawBody    = $response->body();
                }
            }

            // Debug info untuk troubleshooting
            $debug = [
                'url'          => $url,
                'redirect_url' => $redirectUrl,
                'http_status'  => $httpStatus,
                'raw_body'     => $rawBody,
                'sent_payload' => $data,
                'apikey_used'  => substr($this->apiKey, 0, 5) . '***',
            ];

            // Cek HTTP status gagal
            if (!$response->successful()) {
                return [
                    'success'  => false,
                    'message'  => "SIMKEU HTTP error: {$httpStatus}",
                    'response' => $body,
                    'debug'    => $debug,
                ];
            }

            // Cek response body: SIMKEU bisa return HTTP 200 tapi {"status": false}
            $bodyStatus = $body['status'] ?? null;
            if ($bodyStatus === false) {
                $bodyMessage = $body['message'] ?? $body['error'] ?? 'Status false tanpa pesan';
                return [
                    'success'  => false,
                    'message'  => "SIMKEU menolak: {$bodyMessage}",
                    'response' => $body,
                    'debug'    => $debug,
                ];
            }

            return [
                'success'  => true,
                'message'  => $body['message'] ?? 'Berhasil kirim ke SIMKEU',
                'response' => $body,
                'debug'    => $debug,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success'  => false,
                'message'  => 'Timeout / Connection error: ' . $e->getMessage(),
                'response' => null,
                'debug'    => ['url' => $url, 'error' => $e->getMessage()],
            ];
        } catch (\Throwable $th) {
            return [
                'success'  => false,
                'message'  => 'Error: ' . $th->getMessage(),
                'response' => null,
                'debug'    => ['url' => $url, 'error' => $th->getMessage()],
            ];
        }
    }

    /**
     * Kirim banyak pembayaran dalam batch untuk menghindari timeout.
     *
     * @param \Illuminate\Support\Collection $pembayaranList  Collection of pembayaran with peserta & tahun relations
     * @param int $batchSize  Jumlah data per batch (default 10)
     * @param callable|null $onProgress  Callback setiap selesai 1 item: fn(int $current, int $total, array $result)
     * @return array ['total' => int, 'success' => int, 'failed' => int, 'logs' => array]
     */
    public function kirimPembayaranBatch($pembayaranList, int $batchSize = 10, ?callable $onProgress = null): array
    {
        $logs    = [];
        $success = 0;
        $failed  = 0;
        $total   = $pembayaranList->count();
        $current = 0;

        // Proses per batch
        foreach ($pembayaranList->chunk($batchSize) as $batch) {
            foreach ($batch as $pembayaran) {
                $current++;

                $nim             = @$pembayaran->peserta->nim ?? '-';
                $thAkademikKode  = @$pembayaran->peserta->tahun->kode ?? '-';

                $payload = [
                    'nim'              => $nim,
                    'jenis_pembayaran' => self::mapJenisPembayaran($pembayaran->jenis_pembayaran),
                    'th_akademik_kode' => $thAkademikKode,
                    'tanggal'          => $pembayaran->created_at->format('Y-m-d H:i:s'),
                    'jumlah'           => (int) $pembayaran->jumlah,
                ];

                $result = $this->kirimPembayaranWisuda($payload);

                $logEntry = [
                    'no'      => $current,
                    'nim'     => $nim,
                    'jumlah'  => $pembayaran->jumlah,
                    'success' => $result['success'],
                    'message' => $result['message'],
                ];

                if ($result['success']) {
                    $success++;
                } else {
                    $failed++;
                    Log::warning("SimkeuApp: Gagal kirim pembayaran #{$pembayaran->id} NIM {$nim}", $result);
                }

                $logs[] = $logEntry;

                if ($onProgress) {
                    $onProgress($current, $total, $logEntry);
                }
            }

            // Jeda antar batch untuk menghindari rate limiting
            if ($current < $total) {
                usleep(500000); // 0.5 detik
            }
        }

        return [
            'total'   => $total,
            'success' => $success,
            'failed'  => $failed,
            'logs'    => $logs,
        ];
    }
}
