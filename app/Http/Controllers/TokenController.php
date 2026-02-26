<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TokenController extends Controller
{
    /**
     * Ambil token yang sedang aktif.
     * Jika sudah expired (melewati expired_at), otomatis update status.
     */
    public function get(): JsonResponse
    {
        // Auto-expire token yang sudah lewat waktu
        DB::table('tokens')
            ->where('status', 'active')
            ->where('expired_at', '<', Carbon::now('Asia/Jakarta'))
            ->update(['status' => 'expired']);

        $token = DB::table('tokens')
            ->where('status', 'active')
            ->orderByDesc('generated_at')
            ->value('token');

        return response()->json([
            'success' => $token !== null,
            'token'   => $token ?? '',
        ]);
    }

    /**
     * Generate token baru, expired-kan token lama yang masih aktif.
     */
    public function generate(): JsonResponse
    {
        // Expire semua token aktif sebelumnya
        DB::table('tokens')
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        $newToken = strtoupper(Str::random(32));
        $now = Carbon::now('Asia/Jakarta');

        DB::table('tokens')->insert([
            'token'        => $newToken,
            'status'       => 'active',
            'generated_at' => $now,
            'expired_at'   => $now->copy()->addMinutes(10),
        ]);

        return response()->json([
            'success' => true,
            'token'   => $newToken,
        ]);
    }

    /**
     * Cek status token aktif terbaru.
     */
    public function status(): JsonResponse
    {
        // Auto-expire token yang sudah lewat waktu
        DB::table('tokens')
            ->where('status', 'active')
            ->where('expired_at', '<', Carbon::now('Asia/Jakarta'))
            ->update(['status' => 'expired']);

        $row = DB::table('tokens')
            ->orderByDesc('generated_at')
            ->first(['status', 'token']);

        return response()->json([
            'success' => true,
            'status'  => $row?->status ?? 'none',
            'token'   => $row?->token ?? '',
        ]);
    }
}
