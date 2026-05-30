<?php

use App\Http\Controllers\Api\SiswaController;
use App\Http\Controllers\Api\PesertaController;
use App\Http\Controllers\Api\RegistrasiController;
use App\Http\Controllers\Api\TahunController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('apiauth')->group(function () {
    Route::prefix('siswa')->group(function () {
        Route::post('/all', [SiswaController::class, 'all'])->name('api.siswa.all');
        Route::post('/count', [SiswaController::class, 'count'])->name('api.siswa.count');
        Route::post('/find', [SiswaController::class, 'find'])->name('api.siswa.fiind');
        Route::post('/registrasi', [RegistrasiController::class, 'registrasi'])->name('api.siswa.registrasi');
    });
});

Route::prefix('peserta')->group(function () {
    Route::get('/cekWisuda', [PesertaController::class, 'cekWisuda'])->name('api.peserta.cekWisuda');
});

Route::prefix('tahun')->group(function () {
    Route::get('/', [TahunController::class, 'index'])->name('api.tahun.index');
});

