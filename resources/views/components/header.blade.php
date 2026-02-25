<header class="sticky top-0 z-100 bg-white px-5 py-6 shadow-sm">
    <div class="mx-auto flex max-w-300 flex-col items-start justify-between gap-4 md:flex-row md:items-center">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-linear-to-br from-[#2463EB] to-[#1E40AF] text-lg font-semibold text-white">
                {{ strtoupper(substr($namaLengkap, 0, 1)) }}
            </div>
            <div>
                <h2 class="mb-1 text-sm font-normal text-slate-500">Selamat Datang</h2>
                <h1 class="text-lg font-semibold text-slate-900">
                    <span class="text-[#2463EB]">{{ $namaLengkap }}</span> ({{ $level == 'admin' ? 'Admin' : 'Siswa' }})
                </h1>
            </div>
        </div>
        <button onclick="window.location.href='logout.php'" class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600 transition hover:scale-105 hover:bg-red-600 hover:text-white">
            <i data-lucide="log-out" width="20" height="20"></i>
        </button>
    </div>
</header>