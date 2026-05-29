<?php

namespace App\Http\Controllers;

use App\Http\Services\WhatsApp;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Services\Mahasiswa;


class TestingController extends Controller
{
    public function index()
    {
        return redirect()->route('root');
        // $m = Mahasiswa::nim("202185110053");
        // dd($m);
    }
}
