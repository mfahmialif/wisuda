<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use Illuminate\Http\Request;

class PesertaController extends Controller
{
    public function cekWisuda(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'nim' => 'nullable',
            ]);
            
            if ($request->nim == null) {
                return response()->json([
                    "status" => false,
                    "data" => null,
                    "message" => "Data tidak ditemukan",
                    "code" => 404,
                ], 404);
            }
            
    
            // Ambil data peserta
            $siswa = Peserta::where('nim', $validated['nim'])->exists();
    
            // Jika tidak ditemukan
            if (!$siswa) {
                return response()->json([
                    "status" => false,
                    "data" => null,
                    "message" => "Data tidak ditemukan",
                    "code" => 404,
                ], 404);
            }
    
            // Jika ditemukan
            return response()->json([
                "status" => true,
                "data" => $siswa,
                "message" => "success",
                "code" => 200,
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "status" => false,
                "data" => null,
                "message" => "Validasi gagal",
                "errors" => $e->errors(),
                "code" => 422,
            ], 422);
    
        } catch (\Throwable $th) {
            return response()->json([
                "status" => false,
                "data" => null,
                "message" => "Terjadi kesalahan pada server",
                "error" => $th->getMessage(), // hapus di production kalau sensitif
                "code" => 500,
            ], 500);
        }
    }
}
