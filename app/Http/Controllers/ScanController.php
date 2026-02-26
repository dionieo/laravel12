<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ScanController extends Controller
{
    public function process(Request $request): JsonResponse
    {
        $token = (string) $request->input('token', '');
        $username = (string) $request->input('username', '');
        $userLatitude = $request->input('latitude');
        $userLongitude = $request->input('longitude');

        if ($token === '' || $username === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Token atau username tidak valid.',
            ], 422);
        }

        $activeLocations = DB::table('geo_location')->where('is_active', 1)->get();

        if ($activeLocations->isNotEmpty()) {
            if ($userLatitude === null || $userLongitude === null || $userLatitude === '' || $userLongitude === '') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lokasi GPS tidak terdeteksi. Pastikan GPS aktif dan izinkan akses lokasi.',
                ], 400);
            }

            $isInValidLocation = false;
            $nearestLocationName = '';
            $nearestDistance = PHP_INT_MAX;

            foreach ($activeLocations as $location) {
                $distance = $this->calculateDistance(
                    (float) $userLatitude,
                    (float) $userLongitude,
                    (float) $location->latitude,
                    (float) $location->longitude
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestLocationName = (string) $location->nama_lokasi;
                }

                if ($distance <= (float) $location->radius) {
                    $isInValidLocation = true;
                    break;
                }
            }

            if (! $isInValidLocation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda berada di luar area yang diizinkan. Jarak Anda dari '.$nearestLocationName.': '.round($nearestDistance).' meter. Harap datang ke lokasi absensi.',
                ], 403);
            }
        }

        $now = now('Asia/Jakarta');
        $currentDate = $now->toDateString();

        $timeDatangStart = Carbon::createFromFormat('Y-m-d H:i', $currentDate.' 06:00', 'Asia/Jakarta');
        $timeDatangEnd = Carbon::createFromFormat('Y-m-d H:i', $currentDate.' 08:45', 'Asia/Jakarta');
        $timeDatangTelat = Carbon::createFromFormat('Y-m-d H:i', $currentDate.' 08:15', 'Asia/Jakarta');
        $timePulangStart = Carbon::createFromFormat('Y-m-d H:i', $currentDate.' 14:00', 'Asia/Jakarta');
        $timePulangEnd = Carbon::createFromFormat('Y-m-d H:i', $currentDate.' 23:59', 'Asia/Jakarta');

        $statusAbsensi = '';
        $tipeAbsensi = '';

        if ($now->betweenIncluded($timeDatangStart, $timeDatangEnd)) {
            $tipeAbsensi = 'datang';
            $statusAbsensi = $now->lte($timeDatangTelat) ? 'hadir' : 'terlambat';
        } elseif ($now->betweenIncluded($timePulangStart, $timePulangEnd)) {
            $tipeAbsensi = 'pulang';
            $statusAbsensi = 'pulang';
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Absensi hanya bisa dilakukan pada jam 06:00-08:45 (datang) atau 14:00-23:59 (pulang).',
            ], 403);
        }

        // ── Validasi token (cek saja, BELUM di-used) ──
        $tokenRow = DB::table('tokens')
            ->where('token', $token)
            ->where('status', 'active')
            ->where('expired_at', '>=', now())
            ->first(['id']);

        if (! $tokenRow) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid, sudah digunakan, atau kadaluarsa.',
            ], 400);
        }

        // ── Validasi user ──
        $user = DB::table('users')
            ->select('id')
            ->where('username', $username)
            ->first();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun user tidak ditemukan.',
            ], 404);
        }

        $userId = (int) $user->id;

        // ── Cek apakah sudah absensi tipe ini hari ini ──
        $checkQuery = DB::table('absensi')
            ->where('user_id', $userId)
            ->where('tanggal', $currentDate);

        if ($tipeAbsensi === 'datang') {
            $sudahDatang = (clone $checkQuery)->whereIn('status', ['hadir', 'terlambat'])->exists();
            if ($sudahDatang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda sudah melakukan absensi datang hari ini.',
                ], 409);
            }
        } else {
            // Pulang: wajib sudah absensi datang dulu
            $sudahDatang = (clone $checkQuery)->whereIn('status', ['hadir', 'terlambat'])->exists();
            if (! $sudahDatang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda belum melakukan absensi datang hari ini. Silakan absensi datang terlebih dahulu.',
                ], 403);
            }

            $sudahPulang = (clone $checkQuery)->where('status', 'pulang')->exists();
            if ($sudahPulang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda sudah melakukan absensi pulang hari ini.',
                ], 409);
            }
        }

        // ── Semua validasi lolos → consume token & insert absensi ──
        $waktu = $now->format('H:i:s');

        DB::table('absensi')->insert([
            'user_id' => $userId,
            'token_id' => (int) $tokenRow->id,
            'tanggal' => $currentDate,
            'waktu' => $waktu,
            'status' => $statusAbsensi,
        ]);

        // Token di-used hanya setelah absensi berhasil disimpan
        DB::table('tokens')->where('id', (int) $tokenRow->id)->update(['status' => 'used']);

        $hasNamaLengkapColumn = Schema::hasColumn('users', 'nama_lengkap');

        $notifColumns = ['telegram_chat_id', 'username'];
        if ($hasNamaLengkapColumn) {
            $notifColumns[] = 'nama_lengkap';
        }

        $notifUser = DB::table('users')
            ->select($notifColumns)
            ->where('id', $userId)
            ->first();

        $botToken = (string) env('TELEGRAM_BOT_TOKEN', '');

        if ($notifUser && ! empty($notifUser->telegram_chat_id) && $botToken !== '') {
            $namaPanggilan = $hasNamaLengkapColumn && ! empty($notifUser->nama_lengkap)
                ? $notifUser->nama_lengkap
                : ($notifUser->username ?? 'Siswa');

            $pesanNotifikasi = "🔔 Notifikasi Absensi\n\n";
            $pesanNotifikasi .= 'Ananda: *'.$namaPanggilan."*\n";
            $pesanNotifikasi .= 'Status: *'.$statusAbsensi."*\n";
            $pesanNotifikasi .= 'Waktu: *'.$waktu."*\n";
            $pesanNotifikasi .= 'Tanggal: *'.Carbon::parse($currentDate)->locale('id')->translatedFormat('d M Y').'*';

            try {
                Http::asForm()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $notifUser->telegram_chat_id,
                    'text' => $pesanNotifikasi,
                    'parse_mode' => 'Markdown',
                ]);
            } catch (\Throwable $exception) {
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi berhasil!',
        ]);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
