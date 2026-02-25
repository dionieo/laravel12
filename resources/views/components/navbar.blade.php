<nav class="fixed bottom-0 left-0 right-0 z-99 bg-white py-3 shadow-[0_-1px_3px_0_rgb(0_0_0/0.1)]">
    <div class="mx-auto flex max-w-150 items-center justify-around">
        <a href="dashboard.php" class="flex flex-1 flex-col items-center gap-1 px-0 py-2 text-[#2463EB]">
            <i data-lucide="home" width="24" height="24"></i>
            <span class="text-[11px] font-semibold">HOME</span>
        </a>
        <a href="history.php" class="flex flex-1 flex-col items-center gap-1 px-0 py-2 text-slate-400 transition hover:text-[#2463EB]">
            <i data-lucide="{{ $level === 'admin' ? 'users' : 'calendar-clock' }}" width="24" height="24"></i>
            <span class="text-[11px] font-semibold">{{ $level === 'admin' ? 'SISWA' : 'RIWAYAT' }}</span>
        </a>
        <a href="{{ $level === 'admin' ? 'laporan_bulanan.php' : 'ajukan_izin.php' }}" class="flex flex-1 flex-col items-center gap-1 px-0 py-2 text-slate-400 transition hover:text-[#2463EB]">
            <i data-lucide="{{ $level === 'admin' ? 'bar-chart-3' : 'file-text' }}" width="24" height="24"></i>
            <span class="text-[11px] font-semibold">{{ $level === 'admin' ? 'LAPORAN' : 'IZIN' }}</span>
        </a>
        <a href="#" class="flex flex-1 flex-col items-center gap-1 px-0 py-2 text-slate-400 transition hover:text-[#2463EB]">
            <i data-lucide="user-circle" width="24" height="24"></i>
            <span class="text-[11px] font-semibold">PROFIL</span>
        </a>
    </div>
</nav>