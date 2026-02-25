<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TokenController extends Controller
{
    /**
     * Ambil token yang sedang aktif.
     * Menggantikan php/get_token.php
     */
    public function get(): Response
    {
        $token = DB::table('tokens')
            ->where('status', 'active')
            ->orderByDesc('generated_at')
            ->value('token');

        return response($token ?? '', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Generate token baru, expired-kan token lama yang masih aktif.
     * Menggantikan php/generate_token.php
     */
    public function generate(): Response
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

        return response($newToken, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Cek status token aktif terbaru.
     * Menggantikan php/token_status.php
     */
    public function status(): Response
    {
        $row = DB::table('tokens')
            ->orderByDesc('generated_at')
            ->first(['status']);

        $status = $row?->status ?? 'none';

        return response($status, 200)->header('Content-Type', 'text/plain');
    }
}
