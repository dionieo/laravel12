<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

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