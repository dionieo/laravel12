<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LaporanController extends Controller
{
    /**
     * Laporan harian — hanya admin.
     */
    public function harian()
    {
        $user = Auth::user();

        if ($user->level !== 'admin') {
            return redirect()->route('dashboard');
        }

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Semua siswa aktif
        $allStudents = DB::table('users')
            ->where('level', 'siswa')
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->pluck('nama_lengkap', 'id')
            ->toArray();

        // Absensi hari ini
        $rows = DB::table('absensi')
            ->where('tanggal', $today)
            ->orderBy('waktu')
            ->get(['user_id', 'waktu', 'status']);

        $attendance = [
            'hadir'     => [],
            'terlambat' => [],
            'izin'      => [],
            'pulang'    => [],
        ];

        $hasAttended = [];

        foreach ($rows as $row) {
            $nama = $allStudents[$row->user_id] ?? 'Unknown';
            $entry = [
                'id'    => $row->user_id,
                'nama'  => $nama,
                'waktu' => $row->waktu,
            ];

            if (in_array($row->status, ['hadir', 'terlambat'])) {
                $attendance[$row->status][] = $entry;
                $hasAttended[] = $row->user_id;
            } elseif ($row->status === 'pulang') {
                $attendance['pulang'][] = $entry;
            } elseif ($row->status === 'izin') {
                $attendance['izin'][] = $entry;
                $hasAttended[] = $row->user_id;
            }
        }

        // Siswa yang belum absen datang
        $notAttended = [];
        foreach ($allStudents as $id => $nama) {
            if (! in_array($id, $hasAttended)) {
                $notAttended[] = ['id' => $id, 'nama' => $nama];
            }
        }

        // Gabungkan hadir + terlambat
        $sudahDatang = array_merge($attendance['hadir'], $attendance['terlambat']);

        // Format tanggal Indonesia
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $namaHari = $hari[Carbon::parse($today)->dayOfWeek];
        $tanggalFormat = Carbon::parse($today)->format('d M Y');

        return view('laporan-harian', compact(
            'sudahDatang',
            'attendance',
            'notAttended',
            'namaHari',
            'tanggalFormat',
        ));
    }
}
