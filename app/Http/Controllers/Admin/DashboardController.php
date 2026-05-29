<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Operasi\DaftarTugasController;
use App\Http\Services\Helper;
use App\Models\DaftarTugas;
use App\Models\Prodi;
use App\Models\Kalender;
use App\Models\KuotaProdi;
use App\Models\Setting;
use App\Models\Peserta;
use App\Models\Tahun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $daftarTugasController = new DaftarTugasController();
        $daftarTugas = $daftarTugasController->hitungDeadline(
            DaftarTugas::where('user_id', auth()->user()->id)->orderBy('created_at', 'desc')->offset(0)->limit(5)->get()
        );
        $daftarTugas = $daftarTugasController->EditFormatDeadline($daftarTugas);
        $nDaftarTugas = count(DaftarTugas::all());
        $jumlahHalaman = ceil($nDaftarTugas / 5);
        $kalender = Kalender::where('user_id', auth()->user()->id)->get();

        $tahun = Tahun::orderBy('nama', 'desc')->get();
        return view(
            'admin/dashboard/index',
            compact(
                'daftarTugas',
                'jumlahHalaman',
                'kalender',
                'tahun'
            )
        );
    }

    public function getDaurah(Request $request)
    {
        $prodi = Prodi::all();
        $dataDaurah['label'] = [];
        $dataDaurah['warna'] = [];
        $dataDaurah['jumlah'] = [];

        foreach ($prodi as $key => $value) {
            $jumlah = Peserta::join('users', 'users.id', 'peserta.user_id')
                ->when($request->tahun_id != '*', function ($q) use ($request) {
                    $q->where('peserta.tahun_id', $request->tahun_id);
                })
                ->when($request->jenis_kelamin != '*', function ($q) use ($request) {
                    $q->where('users.jenis_kelamin', $request->jenis_kelamin);
                })
                ->where('peserta.prodi_id', $value->id)->count();
            $dataDaurah['label'][] = strtoupper($value->nama);
            $dataDaurah['warna'][] = \Helper::getColorCode($value->warna);
            $dataDaurah['jumlah'][] = $jumlah;
            $dataDaurah['data'][] = (object) [
                "label" => strtoupper($value->nama),
                "jumlah" => $jumlah,
                'warna' => $value->warna,
            ];
        }

        return $dataDaurah;
    }

    public function getRekapan(Request $request)
    {
        // Get active year if not specified
        $tahunId = $request->tahun_id;

        // Base query for counting
        $baseQuery = function () use ($tahunId) {
            return Peserta::join('users', 'users.id', 'peserta.user_id')
                ->join('prodi', 'prodi.id', 'peserta.prodi_id')
                ->when($tahunId != '*', function ($q) use ($tahunId) {
                    $q->where('peserta.tahun_id', $tahunId);
                });
        };

        // Get active year name for display
        $tahunNama = 'Semua Tahun';
        if ($tahunId != '*') {
            $tahun = Tahun::find($tahunId);
            $tahunNama = $tahun ? $tahun->nama : 'Semua Tahun';
        }

        // Count by gender and education level
        $rekapan = [
            'tahun' => $tahunNama,
            'total' => $baseQuery()->count(),
            'banin' => [
                's1' => $baseQuery()->where('users.jenis_kelamin', 'Laki-Laki')->where('prodi.jenjang', 'S1')->count(),
                'pasca' => $baseQuery()->where('users.jenis_kelamin', 'Laki-Laki')->whereIn('prodi.jenjang', ['S2', 'S3'])->count(),
                'total' => $baseQuery()->where('users.jenis_kelamin', 'Laki-Laki')->count(),
            ],
            'banat' => [
                's1' => $baseQuery()->where('users.jenis_kelamin', 'Perempuan')->where('prodi.jenjang', 'S1')->count(),
                'pasca' => $baseQuery()->where('users.jenis_kelamin', 'Perempuan')->whereIn('prodi.jenjang', ['S2', 'S3'])->count(),
                'total' => $baseQuery()->where('users.jenis_kelamin', 'Perempuan')->count(),
            ],
        ];

        return response()->json($rekapan);
    }
}