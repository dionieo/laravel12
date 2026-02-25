<main class="mx-auto max-w-300 px-5 py-6">
    <section class="mb-8 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <a href="absensi" class="block rounded-[20px] bg-white px-6 py-8 text-center text-inherit shadow-md transition hover:-translate-y-1 hover:shadow-xl">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-blue-100 to-blue-200 text-[#2463EB]">
                <i data-lucide="qr-code" width="32" height="32"></i>
            </div>
            <h3 class="mb-2 text-base font-semibold text-slate-900">ABSENSI</h3>
            <p class="text-sm font-normal text-slate-500">Generate QR Code</p>
        </a>

        <a href="laporan_harian.php" class="block rounded-[20px] bg-white px-6 py-8 text-center text-inherit shadow-md transition hover:-translate-y-1 hover:shadow-xl">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-emerald-100 to-emerald-200 text-emerald-600">
                <i data-lucide="calendar-check" width="32" height="32"></i>
            </div>
            <h3 class="mb-2 text-base font-semibold text-slate-900">DATA HARI INI</h3>
            <p class="text-[28px] font-bold text-[#2463EB]">{{ $totalAbsensi }}</p>
            <p class="text-sm font-normal text-slate-500">Total Absensi</p>
        </a>

        <a href="kelola_izin.php" class="block rounded-[20px] bg-white px-6 py-8 text-center text-inherit shadow-md transition hover:-translate-y-1 hover:shadow-xl">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-amber-100 to-amber-200 text-amber-600">
                <i data-lucide="clipboard-list" width="32" height="32"></i>
            </div>
            <h3 class="mb-2 text-base font-semibold text-slate-900">KELOLA IZIN</h3>
            @if ($totalPending > 0)
                <p class="text-2xl font-bold text-red-600">{{ $totalPending }}</p>
                <p class="text-sm font-normal text-slate-500">
                    @if ($overdueCount > 0)
                        <span class="rounded-md bg-red-100 px-2 py-0.5 font-semibold text-red-800">⚠️ {{ $overdueCount }} Terlambat</span>
                    @elseif ($todayCount > 0)
                        <span class="rounded-md bg-amber-100 px-2 py-0.5 font-semibold text-amber-800">🔥 {{ $todayCount }} Hari Ini</span>
                    @else
                        Menunggu Review
                    @endif
                </p>
            @else
                <p class="text-sm font-normal text-slate-500">Tidak ada pending</p>
            @endif
        </a>
    </section>

    <section class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-[#2463EB]">
                    <i data-lucide="file-text" width="18" height="18"></i>
                </div>
                <h2 class="text-lg font-semibold text-slate-900">Laporan & Riwayat</h2>
            </div>
            <span class="rounded-xl bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">3 Menu</span>
        </div>

        <div class="flex flex-col gap-3">
            <a href="history.php" class="flex items-center justify-between rounded-2xl bg-white p-5 text-inherit shadow-sm transition hover:translate-x-1 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-[#2463EB]">
                        <i data-lucide="history" width="24" height="24"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 text-[15px] font-semibold text-slate-900">Riwayat Absensi</h3>
                        <p class="text-[13px] text-slate-500">Lihat semua riwayat absensi</p>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="text-slate-300" width="20" height="20"></i>
            </a>

            <a href="laporan_bulanan.php" class="flex items-center justify-between rounded-2xl bg-white p-5 text-inherit shadow-sm transition hover:translate-x-1 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-[#2463EB]">
                        <i data-lucide="calendar-range" width="24" height="24"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 text-[15px] font-semibold text-slate-900">Laporan Bulanan</h3>
                        <p class="text-[13px] text-slate-500">Rekap absensi per bulan</p>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="text-slate-300" width="20" height="20"></i>
            </a>

            <a href="export_laporan.php" class="flex items-center justify-between rounded-2xl bg-white p-5 text-inherit shadow-sm transition hover:translate-x-1 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-[#2463EB]">
                        <i data-lucide="download" width="24" height="24"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 text-[15px] font-semibold text-slate-900">Export Laporan</h3>
                        <p class="text-[13px] text-slate-500">Download laporan Excel</p>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="text-slate-300" width="20" height="20"></i>
            </a>
        </div>
    </section>

    <section class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-[#2463EB]">
                    <i data-lucide="database" width="18" height="18"></i>
                </div>
                <h2 class="text-lg font-semibold text-slate-900">Kelola Data</h2>
            </div>
            <span class="rounded-xl bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">4 Menu</span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="admin_absensi_manual.php" class="rounded-2xl bg-white p-6 text-center text-inherit shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-[14px] bg-[#2463EB] text-white">
                    <i data-lucide="user-check" width="28" height="28"></i>
                </div>
                <h3 class="text-[15px] font-semibold text-slate-900">Absensi Manual</h3>
            </a>

            <a href="kelola_status_siswa.php" class="rounded-2xl bg-white p-6 text-center text-inherit shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-[14px] bg-emerald-600 text-white">
                    <i data-lucide="users" width="28" height="28"></i>
                </div>
                <h3 class="text-[15px] font-semibold text-slate-900">Status Siswa</h3>
            </a>

            <a href="kelola_hari_libur.php" class="rounded-2xl bg-white p-6 text-center text-inherit shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-[14px] bg-violet-600 text-white">
                    <i data-lucide="calendar-off" width="28" height="28"></i>
                </div>
                <h3 class="text-[15px] font-semibold text-slate-900">Hari Libur</h3>
            </a>

            <a href="manage_wali_siswa.php" class="rounded-2xl bg-white p-6 text-center text-inherit shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-[14px] bg-orange-600 text-white">
                    <i data-lucide="user-plus" width="28" height="28"></i>
                </div>
                <h3 class="text-[15px] font-semibold text-slate-900">Data Wali Siswa</h3>
            </a>
        </div>
    </section>

    <section class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-[#2463EB]">
                    <i data-lucide="settings" width="18" height="18"></i>
                </div>
                <h2 class="text-lg font-semibold text-slate-900">Pengaturan Sistem</h2>
            </div>
            <span class="rounded-xl bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">3 Menu</span>
        </div>

        <div class="flex flex-col gap-3">
            <a href="kelola_token_pendaftaran.php" class="flex items-center justify-between rounded-2xl bg-white p-5 text-inherit shadow-sm transition hover:translate-x-1 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-[#2463EB]">
                        <i data-lucide="key" width="24" height="24"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 text-[15px] font-semibold text-slate-900">Token Pendaftaran</h3>
                        <p class="text-[13px] text-slate-500">Kelola link pendaftaran siswa baru</p>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="text-slate-300" width="20" height="20"></i>
            </a>

            <a href="kelola_geo_fencing.php" class="flex items-center justify-between rounded-2xl bg-white p-5 text-inherit shadow-sm transition hover:translate-x-1 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-[#2463EB]">
                        <i data-lucide="map-pin" width="24" height="24"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 text-[15px] font-semibold text-slate-900">Geo-Fencing</h3>
                        <p class="text-[13px] text-slate-500">Kelola validasi lokasi GPS</p>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="text-slate-300" width="20" height="20"></i>
            </a>

            <a href="kelola_notifikasi.php" class="flex items-center justify-between rounded-2xl bg-white p-5 text-inherit shadow-sm transition hover:translate-x-1 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-50 text-[#2463EB]">
                        <i data-lucide="bell" width="24" height="24"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 text-[15px] font-semibold text-slate-900">Notifikasi Telegram</h3>
                        <p class="text-[13px] text-slate-500">Pengaturan notifikasi otomatis</p>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="text-slate-300" width="20" height="20"></i>
            </a>
        </div>
    </section>
</main>
