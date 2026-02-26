<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KelolaIzinController extends Controller
{
    /**
     * Tampilkan daftar izin (pending + history). Admin only.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->level !== 'admin') {
            return redirect()->route('dashboard');
        }

        // Pending izin with urgency sorting
        $pendingList = DB::table('izin_siswa as i')
            ->join('users as u', 'i.user_id', '=', 'u.id')
            ->where('i.status', 'pending')
            ->where('u.status', 'aktif')
            ->selectRaw("
                i.*,
                u.nama_lengkap,
                u.username,
                TIMESTAMPDIFF(HOUR, i.created_at, NOW()) as hours_pending,
                DATEDIFF(i.tanggal, CURDATE()) as days_until,
                CASE
                    WHEN i.tanggal < CURDATE() THEN 1
                    WHEN i.tanggal = CURDATE() THEN 2
                    WHEN TIMESTAMPDIFF(HOUR, i.created_at, NOW()) > 24 THEN 3
                    ELSE 4
                END as urgency_level
            ")
            ->orderBy('urgency_level')
            ->orderBy('i.tanggal')
            ->orderBy('i.created_at')
            ->get();

        // Recent approved/rejected (last 30)
        $historyList = DB::table('izin_siswa as i')
            ->join('users as u', 'i.user_id', '=', 'u.id')
            ->leftJoin('users as a', 'i.approved_by', '=', 'a.id')
            ->whereIn('i.status', ['approved', 'rejected'])
            ->select(
                'i.*',
                'u.nama_lengkap',
                'u.username',
                'a.nama_lengkap as admin_name'
            )
            ->orderByDesc('i.approved_at')
            ->limit(30)
            ->get();

        return view('kelola-izin', compact('pendingList', 'historyList'));
    }

    /**
     * Approve / reject izin.
     */
    public function action(Request $request)
    {
        $user = Auth::user();

        if ($user->level !== 'admin') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'izin_id'     => 'required|integer',
            'action'      => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $izinId     = $request->input('izin_id');
        $action     = $request->input('action');
        $adminNotes = trim($request->input('admin_notes', ''));
        $adminId    = $user->id;

        if ($action === 'approve') {
            return $this->handleApprove($izinId, $adminId, $adminNotes);
        }

        return $this->handleReject($izinId, $adminId, $adminNotes);
    }

    private function handleApprove(int $izinId, int $adminId, string $adminNotes)
    {
        $izin = DB::table('izin_siswa')
            ->where('id', $izinId)
            ->where('status', 'pending')
            ->first();

        if (! $izin) {
            return back()->with('error', 'Izin tidak ditemukan atau sudah diproses.');
        }

        // Cek apakah sudah ada absensi di tanggal itu
        $existing = DB::table('absensi')
            ->where('user_id', $izin->user_id)
            ->where('tanggal', $izin->tanggal)
            ->exists();

        if ($existing) {
            return back()->with('error', 'Tidak bisa approve: Siswa sudah absensi di tanggal ini.');
        }

        DB::beginTransaction();
        try {
            // Update status izin
            DB::table('izin_siswa')
                ->where('id', $izinId)
                ->update([
                    'status'      => 'approved',
                    'admin_notes' => $adminNotes,
                    'approved_by' => $adminId,
                    'approved_at' => Carbon::now('Asia/Jakarta'),
                ]);

            // Insert ke absensi dengan status 'izin'
            DB::table('absensi')->insert([
                'user_id'  => $izin->user_id,
                'tanggal'  => $izin->tanggal,
                'waktu'    => Carbon::now('Asia/Jakarta')->format('H:i:s'),
                'status'   => 'izin',
                'token_id' => null,
            ]);

            DB::commit();
            return back()->with('success', 'Izin disetujui! Status izin telah ditambahkan ke absensi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal approve izin: ' . $e->getMessage());
        }
    }

    private function handleReject(int $izinId, int $adminId, string $adminNotes)
    {
        $affected = DB::table('izin_siswa')
            ->where('id', $izinId)
            ->where('status', 'pending')
            ->update([
                'status'      => 'rejected',
                'admin_notes' => $adminNotes,
                'approved_by' => $adminId,
                'approved_at' => Carbon::now('Asia/Jakarta'),
            ]);

        if ($affected) {
            return back()->with('success', 'Pengajuan izin ditolak.');
        }

        return back()->with('error', 'Gagal menolak izin atau izin tidak ditemukan.');
    }
}
