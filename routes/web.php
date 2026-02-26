<?php

use App\Http\Controllers\KelolaIzinController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        $today = now()->timezone('Asia/Jakarta')->toDateString();

        $data = [
            'namaLengkap'  => $user->nama_lengkap ?? $user->name ?? 'Pengguna',
            'level'        => $user->level ?? 'siswa',
            'totalAbsensi' => 0,
            'sudahAbsen'   => false,
        ];

        if ($user->level === 'admin') {
            // Total absensi datang hari ini (hadir + terlambat)
            $data['totalAbsensi'] = DB::table('absensi')
                ->where('tanggal', $today)
                ->whereIn('status', ['hadir', 'terlambat'])
                ->count();

            // Izin pending counts
            $pending = DB::table('izin_siswa')
                ->join('users', 'izin_siswa.user_id', '=', 'users.id')
                ->where('izin_siswa.status', 'pending')
                ->where('users.status', 'aktif')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN izin_siswa.tanggal < CURDATE() THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN izin_siswa.tanggal = CURDATE() THEN 1 ELSE 0 END) as today
                ")
                ->first();

            $data['totalPending'] = $pending->total ?? 0;
            $data['overdueCount'] = $pending->overdue ?? 0;
            $data['todayCount']   = $pending->today ?? 0;
        } else {
            // Siswa: cek sudah absen hari ini
            $absensi = DB::table('absensi')
                ->where('user_id', $user->id)
                ->where('tanggal', $today)
                ->whereIn('status', ['hadir', 'terlambat'])
                ->first();

            if ($absensi) {
                $data['sudahAbsen']  = true;
                $data['statusAbsen'] = $absensi->status;
                $data['waktuAbsen']  = $absensi->waktu;
            }
        }

        return view('dashboard', $data);
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

    // Laporan harian (admin)
    Route::get('/laporan-harian', [LaporanController::class, 'harian'])->name('laporan.harian');

    // Kelola izin (admin)
    Route::get('/kelola-izin', [KelolaIzinController::class, 'index'])->name('kelola-izin');
    Route::post('/kelola-izin', [KelolaIzinController::class, 'action'])->name('kelola-izin.action');
});

// Debug DB (opsional, bisa dihapus di production)
Route::get('/test-db', function () {
    return DB::selectOne('SELECT DATABASE() as database')->database;
});