<?php

use App\Http\Controllers\ScanController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard', [
        'title' => 'Dashboard',
        'user' => 'Dionisius Denanta',
        'level' => 'admin'
        ]);
});

Route::get('/absensi', function () {
    return view('absensi');
});

Route::get('/scan-camera', function () {
    return view('scan-camera');
});

Route::post('/scan', [ScanController::class, 'process'])->name('scan.process');

// Token endpoints (pengganti php/get_token.php, generate_token.php, token_status.php)
Route::get('/token/get', [TokenController::class, 'get'])->name('token.get');
Route::get('/token/generate', [TokenController::class, 'generate'])->name('token.generate');
Route::get('/token/status', [TokenController::class, 'status'])->name('token.status');

Route::get('/test-db', function () {
    return DB::selectOne('SELECT DATABASE() as database')->database;
});