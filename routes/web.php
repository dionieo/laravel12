<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Guest routes (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/',      [LoginController::class, 'showForm']);
    Route::post('/',     [LoginController::class, 'login']);
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated routes (harus sudah login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', function () {
        $user = auth()->user();
        return view('dashboard', [
            'namaLengkap'  => $user->nama_lengkap ?? $user->name ?? 'Pengguna',
            'level'        => $user->level ?? 'siswa',
            'totalAbsensi' => 0,
            'sudahAbsen'   => false,
        ]);
    })->name('dashboard');

    // QR Absensi (admin)
    Route::get('/absensi', function () {
        return view('absensi');
    })->name('absensi');

    // Scan camera (siswa)
    Route::get('/scan-camera', function () {
        return view('scan-camera');
    })->name('scan-camera');

    // Proses scan
    Route::post('/scan', [ScanController::class, 'process'])->name('scan.process');

    // Token endpoints
    Route::get('/token/get',      [TokenController::class, 'get'])->name('token.get');
    Route::get('/token/generate', [TokenController::class, 'generate'])->name('token.generate');
    Route::get('/token/status',   [TokenController::class, 'status'])->name('token.status');
});

// Debug DB (opsional, bisa dihapus di production)
Route::get('/test-db', function () {
    return DB::selectOne('SELECT DATABASE() as database')->database;
});