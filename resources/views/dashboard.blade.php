<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $level === 'admin' ? 'Admin Dashboard' : 'Dashboard Siswa' }} - Absensi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $namaLengkap = $namaLengkap ?? (auth()->user()->nama_lengkap ?? auth()->user()->name ?? 'Dionisius Denanta');
    $level = $level ?? (auth()->user()->level ?? 'siswa');
    $totalAbsensi = $totalAbsensi ?? 0;

    $sudahAbsen = $sudahAbsen ?? false;
    $statusAbsen = $statusAbsen ?? null;
    $waktuAbsen = $waktuAbsen ?? null;

    $totalPending = $totalPending ?? 0;
    $overdueCount = $overdueCount ?? 0;
    $todayCount = $todayCount ?? 0;
@endphp
<body class="min-h-screen bg-slate-50 pb-20 text-slate-900" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <x-header>
        <x-slot:namaLengkap>{{ $namaLengkap }}</x-slot>
        <x-slot:level>{{ $level }}</x-slot:level>
        {{-- </x-slot:user>{{ $user }}</x-slot:user> --}}
    </x-header>

    @if ($level === 'admin')
    <x-dashboard-admin
        :total-absensi="$totalAbsensi"
        :total-pending="$totalPending"
        :overdue-count="$overdueCount"
        :today-count="$todayCount"
    />
    @endif

    @if ($level === 'siswa')
        <x-dashboard-siswa>
            <x-slot:sudahAbsen>{{ $sudahAbsen }}</x-slot>
            <x-slot:statusAbsen>{{ $statusAbsen }}</x-slot>
            <x-slot:waktuAbsen>{{ $waktuAbsen }}</x-slot>
        </x-dashboard-siswa>
    @endif

    <x-navbar />

    <script>
        lucide.createIcons();

        @if ($level === 'siswa')
            function updateClock() {
                const now = new Date();
                const jakartaTime = new Intl.DateTimeFormat('id-ID', {
                    timeZone: 'Asia/Jakarta',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                }).format(now);

                const clockElement = document.getElementById('liveClock');
                if (clockElement) {
                    clockElement.textContent = jakartaTime;
                }
            }

            updateClock();
            setInterval(updateClock, 1000);
        @endif
    </script>
</body>
</html>