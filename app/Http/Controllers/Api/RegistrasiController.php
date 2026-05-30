<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Peserta;
use App\Models\Pembayaran;
use App\Models\Tahun;
use App\Models\Prodi;
use Illuminate\Http\Request;

class RegistrasiController extends Controller
{
    /**
     * Registrasi peserta baru dengan pembayaran wajib.
     *
     * Endpoint ini menerima data peserta dan data pembayaran sekaligus.
     * Siswa wajib membayar dulu, sehingga data pembayaran harus diinputkan
     * bersamaan dengan data registrasi.
     *
     * Referensi logika: PembayaranController@registrasi
     */
    public function registrasi(Request $request)
    {
        \DB::beginTransaction();
        try {
            $rules = [
                // Data Siswa
                'nim'             => 'required|string',
                'nama'            => 'required|string',
                'nama_ayah'       => 'nullable|string',
                'judul'           => 'nullable|string',
                'tanggal_sidang'  => 'nullable|date',
                'tahun_id'        => 'required|integer|exists:tahun,id',
                'jenis_kelamin'   => 'required|in:Laki-Laki,Perempuan',
                'prodi'           => 'required|string|exists:prodi,alias',
                'ukuran_baju'     => 'nullable|string',
                'is_bayar'        => 'required|boolean',
            ];

            // Jika is_bayar true, validasi data pembayaran
            if ($request->is_bayar) {
                $rules['jenis_pembayaran'] = 'required|string';
                $rules['jumlah']           = 'required|numeric|min:1';
                $rules['keterangan']       = 'nullable|string';
            }

            $request->validate($rules);

            $prodi = Prodi::where('alias', $request->prodi)->firstOrFail();

            // Cek peserta yang sudah ada berdasarkan NIM
            $peserta = Peserta::where('nim', $request->nim)->first();
            $user = @$peserta->user;

            if (!$user) {
                $noUnik = substr($request->nim, -6);
                $password = \Hash::make($noUnik);

                $user = new User();
                $user->password = $password;
                $user->no_unik = $noUnik;
                $user->role_id = 2;
            }

            $user->username       = $request->nim;
            $user->nama           = $request->nama;
            $user->jenis_kelamin  = $request->jenis_kelamin;
            $user->save();

            // Buat atau update data peserta
            if (!$peserta) {
                $peserta = new Peserta();
            }

            $peserta->nim            = $request->nim;
            $peserta->nama           = $request->nama;
            $peserta->nama_ayah      = $request->nama_ayah;
            $peserta->judul          = $request->judul;
            $peserta->tanggal_sidang = $request->tanggal_sidang;
            $peserta->prodi_id       = $prodi->id;
            $peserta->status_id      = 1;
            $peserta->user_id        = $user->id;
            $peserta->tahun_id       = $request->tahun_id;
            $peserta->tanggal_daftar = now();
            $peserta->ukuran_baju    = $request->ukuran_baju;
            $peserta->save();

            // Simpan data pembayaran jika is_bayar true
            $pembayaran = null;
            if ($request->is_bayar) {
                $pembayaran = new Pembayaran();
                $pembayaran->peserta_id       = $peserta->id;
                $pembayaran->jumlah           = $request->jumlah;
                $pembayaran->jenis_pembayaran = $request->jenis_pembayaran;
                $pembayaran->keterangan       = $request->keterangan;
                $pembayaran->save();
            }

            \DB::commit();

            $responseData = [
                'peserta' => $peserta->load('prodi', 'tahun'),
                'no_unik' => $user->no_unik,
            ];

            if ($pembayaran) {
                $responseData['pembayaran'] = $pembayaran;
            }

            return response()->json([
                'status'  => true,
                'message' => $pembayaran
                    ? 'Berhasil registrasi peserta baru dan menyimpan data pembayaran'
                    : 'Berhasil registrasi peserta baru tanpa pembayaran',
                'code'    => 200,
                'data'    => $responseData,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \DB::rollback();
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
                'code'    => 422,
            ], 422);
        } catch (\Throwable $th) {
            \DB::rollback();
            return response()->json([
                'status'  => false,
                'message' => 'Gagal registrasi peserta baru',
                'error'   => $th->getMessage(),
                'code'    => 500,
            ], 500);
        }
    }

    /**
     * Edit nominal pembayaran berdasarkan NIM dan created_at.
     */
    public function editPembayaran(Request $request)
    {
        try {
            $request->validate([
                'nim'        => 'required|string',
                'created_at' => 'required|date',
                'jumlah'     => 'required|numeric|min:1',
            ]);

            // Cari peserta berdasarkan NIM
            $peserta = Peserta::where('nim', $request->nim)->first();

            if (!$peserta) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Peserta dengan NIM tersebut tidak ditemukan',
                    'code'    => 404,
                ], 404);
            }

            // Cari pembayaran berdasarkan peserta_id dan created_at
            $pembayaran = Pembayaran::where('peserta_id', $peserta->id)
                ->where('created_at', $request->created_at)
                ->first();

            if (!$pembayaran) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data pembayaran tidak ditemukan',
                    'code'    => 404,
                ], 404);
            }

            // Update nominal pembayaran
            $pembayaran->jumlah = $request->jumlah;
            $pembayaran->save();

            return response()->json([
                'status'  => true,
                'message' => 'Nominal pembayaran berhasil diperbarui',
                'code'    => 200,
                'data'    => $pembayaran,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
                'code'    => 422,
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengubah nominal pembayaran',
                'error'   => $th->getMessage(),
                'code'    => 500,
            ], 500);
        }
    }

    /**
     * Hapus pembayaran berdasarkan NIM dan created_at.
     */
    public function hapusPembayaran(Request $request)
    {
        try {
            $request->validate([
                'nim'        => 'required|string',
                'created_at' => 'required|date',
            ]);

            // Cari peserta berdasarkan NIM
            $peserta = Peserta::where('nim', $request->nim)->first();

            if (!$peserta) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Peserta dengan NIM tersebut tidak ditemukan',
                    'code'    => 404,
                ], 404);
            }

            // Cari pembayaran berdasarkan peserta_id dan created_at
            $pembayaran = Pembayaran::where('peserta_id', $peserta->id)
                ->where('created_at', $request->created_at)
                ->first();

            if (!$pembayaran) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data pembayaran tidak ditemukan',
                    'code'    => 404,
                ], 404);
            }

            $pembayaran->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Data pembayaran berhasil dihapus',
                'code'    => 200,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
                'code'    => 422,
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal menghapus data pembayaran',
                'error'   => $th->getMessage(),
                'code'    => 500,
            ], 500);
        }
    }
}
