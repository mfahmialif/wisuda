<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\Helper;
use App\Http\Services\Message;
use App\Http\Services\SimkeuApp;
use App\Models\Api;
use App\Models\Pembayaran;
use App\Models\Prodi;
use App\Models\Peserta;
use App\Models\Setting;
use App\Models\Tahun;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Milon\Barcode\Facades\DNS2DFacade;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::get()->pluck('value', 'slug');
        $fonnte = Api::where('type', 'notif_wa_fonnte')->first();
        $zenziva = Api::where('type', 'notif_wa_zenziva')->first();
        $satuconnect = Api::where('type', 'notif_wa_satuconnect')->first();

        $tahun = Tahun::all();
        $tipe = Helper::getEnumValues('peserta', 'tipe');
        $prodi = Prodi::all();

        return view('admin.setting.index', compact('setting', 'fonnte', 'zenziva', 'satuconnect', 'tahun', 'tipe', 'prodi'));
    }

    public function save(Request $request)
    {
        try {
            DB::beginTransaction();
            $dataValidated = $request->validate([
                'otp' => 'nullable',
                'isi_pesan_wa' => 'nullable',
                'vendor_notifikasi' => 'nullable',
                'userkey_zenziva' => 'nullable',
                'passkey_zenziva' => 'nullable',
                'passkey_fonnte' => 'nullable',
            ]);

            $setting = $request->only('vendor_notifikasi', 'isi_pesan_wa');
            foreach ($setting as $slug => $value) {
                Setting::where('slug', $slug)
                    ->update([
                        'value' => $value
                    ]);
            }

            Api::where('type', 'notif_wa_fonnte')
                ->update([
                    'token' => $request->passkey_fonnte
                ]);

            Api::where('type', 'notif_wa_zenziva')
                ->update([
                    'userkey' => $request->userkey_zenziva,
                    'token' => $request->passkey_zenziva
                ]);


            DB::commit();
            return [
                "status" => true,
                "message" => 200,
                "data" => 'Berhasil update setting whatsapp',
                "req" => $request->all()
            ];
        } catch (\Throwable $th) {
            //throw $th;
            return [
                "status" => false,
                "message" => 500,
                "data" => $th->getMessage(),
            ];
        }
    }

    public function tes(Request $request)
    {
        try {
            //code...
            $request->validate([
                'nomor_hp' => 'required'
            ]);

            $send = Message::send([
                'nomor_hp' => $request->nomor_hp,
                'peserta_id' => null,
                'password' => 'prodidalwa',
            ]);

            if (!$send) {
                abort(500, 'Gagal kirim pesan');
            }

            return [
                "status" => true,
                "message" => 200,
                "data" => 'Berhasil',
                "req" => $request->all()
            ];
        } catch (\Throwable $th) {
            //throw $th;
            return [
                "status" => true,
                "message" => 500,
                "data" => $th->getMessage(),
                "req" => $request->all()
            ];
        }
    }

    public function generateQr(Request $request)
    {
        $log = [];
        try {
            $request->validate([
                'tahun_id' => 'required',
                'dari' => 'required',
                'kelipatan' => 'required',
                'sampai' => 'required',
                'jenis_kelamin' => 'required',
                'tipe' => 'required',
            ]);

            $dari = $request->dari - 1;

            $tanggal = Carbon::now()->format('d-m-Y H:i:s');

            $lokasiSave = public_path('/img/qr/');
            if (!file_exists($lokasiSave)) {
                mkdir($lokasiSave, 0755, true);
            }

            $peserta = Peserta::join('users', 'users.id', '=', 'peserta.user_id')
                ->where('peserta.tahun_id', $request->tahun_id)
                ->where('peserta.tipe', $request->tipe)
                ->when($request->jenis_kelamin != '*', function ($query) use ($request) {
                    $query->where('users.jenis_kelamin', $request->jenis_kelamin);
                })
                ->limit($request->kelipatan)
                ->offset($dari)
                ->select('peserta.*', 'users.jenis_kelamin')
                ->get();

            $generatedFiles = [];
            $qrPolos = "/img/qr_polos.png";
            foreach ($peserta as $key => $value) {
                $img = Image::make(asset($qrPolos));
                $img->text($value->nim, 120, 55, function ($font) {
                    $font->file(public_path("/font/FranklinGothic.ttf"));
                    $font->size(12);
                    $font->color('#000000');
                });
                $nama = Str::limit($value->nama, 30);
                $img->text(strtoupper($nama), 120, 75, function ($font) {
                    $font->file(public_path("/font/FranklinGothic.ttf"));
                    $font->size(12);
                    $font->color('#000000');
                });
                $img->text(strtoupper(@$value->prodi->alias), 120, 95, function ($font) {
                    $font->file(public_path("/font/FranklinGothic.ttf"));
                    $font->size(12);
                    $font->color('#000000');
                });
                $img->insert(DNS2DFacade::getBarcodePNG((string) $value->id, 'QRCODE', 4.6, 4.6), 'bottom-left', 14, 21);

                $jenisKelamin = $value->jenis_kelamin === '*' ? 'semua' : $value->jenis_kelamin;
                $name = $jenisKelamin . '-' . $value->id . '-' . $value->nim . '-' . strtoupper($value->nama) . '.jpg';
                $path = $lokasiSave . $name;
                $img->save($path);

                $generatedFiles[] = [
                    'path' => $path,
                    'name' => $name
                ];
                $log[] = 'Success generated QR ' . $value->nama . ' ' . $value->nim . ' ' . @$value->prodi->nama;
            }

            // $directoryZip = public_path('img/qr-compress/');
            // $dari = $request->dari + 1;
            // $sampai = intval($request->dari + 1) + intval($request->kelipatan) - 1;
            // $zipPath = $directoryZip . "QR $dari - $sampai.zip";
            // if (!file_exists($directoryZip)) {
            //     mkdir($directoryZip, 0755, true);
            // }
            // $zip = Helper::compressToZip($generatedFiles, $zipPath);

            // if ($zip) {
            //     File::deleteDirectory($lokasiSave);
            //     return response()->download($zipPath)->deleteFileAfterSend(true);
            // }
            return [
                'status' => true,
                'message' => 200,
                'req' => $request->all(),
                'log' => $log,
                'tanggal' => $tanggal,
                'data' => 'Berhasil Generate QR'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => 500,
                'log' => $log,
                'tanggal' => $tanggal,
                'data' => $th->getMessage(),
                'req' => $request->all(),
            ];
        }
    }

    public function getPeserta(Request $request)
    {
        try {
            $request->validate([
                'tahun_id' => 'required',
                'jenis_kelamin' => 'required',
                'tipe' => 'required',
            ]);

            $peserta = Peserta::join('users', 'users.id', '=', 'peserta.user_id')
                ->where('peserta.tahun_id', $request->tahun_id)
                ->where('peserta.tipe', $request->tipe)
                ->when($request->jenis_kelamin != '*', function ($query) use ($request) {
                    $query->where('users.jenis_kelamin', $request->jenis_kelamin);
                })->count();

            return [
                'status' => true,
                'message' => 200,
                'req' => $request->all(),
                'data' => $peserta
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => 500,
                'data' => $th->getMessage(),
                'req' => $request->all(),
            ];
        }
    }

    /**
     * Hitung jumlah pembayaran berdasarkan tahun akademik.
     */
    public function sinkronSimkeuCount(Request $request)
    {
        try {
            $count = Pembayaran::join('peserta', 'peserta.id', '=', 'pembayaran.peserta_id')
                ->where('peserta.tahun_id', $request->tahun_id)
                ->when($request->jenjang, function ($q) use ($request) {
                    $q->join('prodi as prodi_filter', 'prodi_filter.id', '=', 'peserta.prodi_id')
                       ->where('prodi_filter.jenjang', $request->jenjang);
                })
                ->count();

            return [
                'status' => true,
                'data'   => $count,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'data'   => 0,
                'error'  => $th->getMessage(),
            ];
        }
    }

    /**
     * Sinkronkan semua data pembayaran ke SIMKEU V2 secara batch.
     */
    public function sinkronSimkeu(Request $request)
    {
        // Disable time limit agar tidak timeout
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        try {
            $request->validate([
                'tahun_id'   => 'required|integer',
                'batch_size' => 'nullable|integer|min:1|max:50',
            ]);

            $batchSize = $request->batch_size ?? 10;

            // Ambil semua pembayaran beserta relasi peserta dan tahun
            $pembayaranList = Pembayaran::with(['peserta.tahun'])
                ->whereHas('peserta', function ($q) use ($request) {
                    $q->where('tahun_id', $request->tahun_id);
                    if ($request->jenjang) {
                        $q->whereHas('prodi', function ($pq) use ($request) {
                            $pq->where('jenjang', $request->jenjang);
                        });
                    }
                })
                ->get();

            if ($pembayaranList->isEmpty()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tidak ada data pembayaran untuk tahun ini',
                ]);
            }

            $simkeu = new SimkeuApp();
            $result = $simkeu->kirimPembayaranBatch($pembayaranList, $batchSize);

            return response()->json([
                'status'  => true,
                'message' => "Sinkronisasi selesai: {$result['success']} berhasil, {$result['failed']} gagal",
                'data'    => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal sinkronisasi: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * List pembayaran beserta data peserta untuk tes sinkron (berdasarkan tahun, dengan pagination).
     */
    public function tesSinkronSimkeuList(Request $request)
    {
        try {
            $perPage = $request->per_page ?? 10;

            $query = Pembayaran::join('peserta', 'peserta.id', '=', 'pembayaran.peserta_id')
                ->join('prodi', 'prodi.id', '=', 'peserta.prodi_id')
                ->join('tahun', 'tahun.id', '=', 'peserta.tahun_id')
                ->join('users', 'users.id', '=', 'peserta.user_id')
                ->where('peserta.tahun_id', $request->tahun_id)
                ->when($request->jenjang, function ($q) use ($request) {
                    $q->where('prodi.jenjang', $request->jenjang);
                })
                ->when($request->search, function ($q) use ($request) {
                    $q->where(function ($q2) use ($request) {
                        $q2->where('peserta.nim', 'LIKE', "%{$request->search}%")
                           ->orWhere('peserta.nama', 'LIKE', "%{$request->search}%");
                    });
                })
                ->select(
                    'pembayaran.id',
                    'pembayaran.jumlah',
                    'pembayaran.jenis_pembayaran',
                    'pembayaran.keterangan',
                    'pembayaran.created_at as tanggal',
                    'peserta.nim',
                    'peserta.nama',
                    'prodi.nama as prodi_nama',
                    'prodi.alias as prodi_alias',
                    'tahun.kode as th_akademik_kode',
                    'tahun.nama as tahun_nama',
                    'users.jenis_kelamin',
                )
                ->orderBy('pembayaran.created_at', 'desc');

            $paginated = $query->paginate($perPage);

            $paginated->getCollection()->transform(function ($item) {
                $item->tanggal = \Carbon::parse($item->tanggal)->format('Y-m-d H:i:s');
                $item->tanggal_display = \Carbon::parse($item->tanggal)->format('d M Y, H:i');
                $item->jumlah_display = 'Rp ' . number_format($item->jumlah, 0, ',', '.');
                return $item;
            });

            return [
                'status' => true,
                'data'   => $paginated->items(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'from'         => $paginated->firstItem(),
                    'to'           => $paginated->lastItem(),
                ],
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'data'   => [],
                'error'  => $th->getMessage(),
            ];
        }
    }

    /**
     * Tes kirim 1 data pembayaran dari DB ke SIMKEU V2 (AJAX).
     */
    public function tesSinkronSimkeu(Request $request)
    {
        try {
            $request->validate([
                'pembayaran_id' => 'required|integer|exists:pembayaran,id',
            ]);

            $pembayaran = Pembayaran::with(['peserta.tahun'])->findOrFail($request->pembayaran_id);

            $payload = [
                'nim'              => $pembayaran->peserta->nim,
                'jenis_pembayaran' => SimkeuApp::mapJenisPembayaran($pembayaran->jenis_pembayaran),
                'th_akademik_kode' => $pembayaran->peserta->tahun->kode ?? '-',
                'tanggal'          => $pembayaran->created_at->format('Y-m-d H:i:s'),
                'jumlah'           => (int) $pembayaran->jumlah,
            ];

            $simkeu = new SimkeuApp();
            $result = $simkeu->kirimPembayaranWisuda($payload);

            return response()->json([
                'status'  => $result['success'],
                'message' => $result['message'],
                'payload' => $payload,
                'data'    => $result['response'],
                'debug'   => $result['debug'] ?? null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $th->getMessage(),
            ], 500);
        }
    }
}
