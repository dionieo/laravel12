<main class="mx-auto max-w-150 px-5 py-5">
    <div id="liveClock" class="mb-6 text-center text-4xl font-bold tracking-[-0.02em] text-[#2463EB] sm:text-5xl">00:00:00</div>

    @if ($sudahAbsen)
        <div class="mb-5 block cursor-default rounded-3xl bg-linear-to-br from-[#2463EB] to-[#1E40AF] px-6 py-10 text-center shadow-[0_10px_25px_-5px_rgba(36,99,235,0.3)]">
            <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-[20px] bg-white/20 backdrop-blur">
                <i data-lucide="check-circle" width="40" height="40" class="text-white"></i>
            </div>
            <h2 class="mb-2 text-[22px] font-bold text-white">Absensi Hari Ini</h2>
            <p class="text-sm text-white/90">Anda sudah melakukan absensi</p>
            <div class="mt-3 inline-block rounded-xl px-4 py-2 text-[13px] font-semibold text-white {{ $statusAbsen === 'hadir' ? 'bg-emerald-600/90' : 'bg-orange-600/90' }}">
                {{ strtoupper($statusAbsen) }} - {{ $waktuAbsen ? \Carbon\Carbon::parse($waktuAbsen)->format('H:i') : '--:--' }}
            </div>
        </div>
    @else
        <a href="scan-camera" class="mb-5 block rounded-3xl bg-linear-to-br from-white to-[#eef5ff] px-6 py-10 text-center text-inherit shadow-[0_10px_25px_-5px_rgba(36,99,235,0.3)] transition hover:-translate-y-1 hover:shadow-[0_20px_35px_-5px_rgba(36,99,235,0.4)]">
            <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-[20px] bg-[#2463EB]/10 backdrop-blur">
                <i data-lucide="qr-code" width="40" height="40" class="text-slate-900"></i>
            </div>
            <h2 class="mb-2 text-[22px] font-bold text-slate-900">Absensi Sekarang</h2>
            <p class="text-sm text-slate-500">Tap untuk scan QR Code absensi</p>
        </a>
    @endif

    <section class="mb-5">
        <h3 class="mb-3 px-1 text-base font-semibold text-slate-900">Menu Cepat</h3>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <a href="scan-camera" class="rounded-[20px] bg-white px-4 py-6 text-center text-inherit shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-linear-to-br from-blue-100 to-blue-200 text-[#2463EB]">
                    <i data-lucide="scan" width="28" height="28"></i>
                </div>
                <h3 class="text-[13px] font-semibold leading-[1.3] text-slate-900">Scan Absensi</h3>
            </a>

            <a href="ajukan_pulang_awal.php" class="rounded-[20px] bg-white px-4 py-6 text-center text-inherit shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-linear-to-br from-rose-200 to-rose-300 text-rose-600">
                    <i data-lucide="clock" width="28" height="28"></i>
                </div>
                <h3 class="text-[13px] font-semibold leading-[1.3] text-slate-900">Izin Pulang Cepat</h3>
            </a>

            <a href="laporan_per_siswa.php" class="rounded-[20px] bg-white px-4 py-6 text-center text-inherit shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-linear-to-br from-orange-200 to-orange-300 text-orange-600">
                    <i data-lucide="file-bar-chart" width="28" height="28"></i>
                </div>
                <h3 class="text-[13px] font-semibold leading-[1.3] text-slate-900">Laporan Saya</h3>
            </a>
        </div>
    </section>
</main>