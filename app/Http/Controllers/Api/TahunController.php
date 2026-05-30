<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tahun;

class TahunController extends Controller
{
    /**
     * Menampilkan semua data tahun.
     */
    public function index()
    {
        try {
            $tahun = Tahun::orderBy('kode', 'desc')->get();

            return response()->json([
                'status'  => true,
                'message' => 'Data tahun berhasil diambil',
                'code'    => 200,
                'data'    => $tahun,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengambil data tahun',
                'error'   => $th->getMessage(),
                'code'    => 500,
            ], 500);
        }
    }
}
