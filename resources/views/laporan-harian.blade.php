<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hari Ini</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F8FAFC;
            color: #0F172A;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        .header {
            background: #FFFFFF;
            padding: 16px 20px;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-top {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }

        .back-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #F8FAFC;
            border: none;
            color: #0F172A;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #E2E8F0;
        }

        .header-title {
            flex: 1;
        }

        .header-title h1 {
            font-size: 18px;
            font-weight: 700;
            color: #0F172A;
        }

        .header-date {
            background: #F8FAFC;
            padding: 12px 16px;
            border-radius: 12px;
            text-align: center;
        }

        .header-date-day {
            font-size: 14px;
            font-weight: 600;
            color: #0F172A;
        }

        .header-date-full {
            font-size: 12px;
            color: #64748B;
            margin-top: 2px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .section-card {
            background: #FFFFFF;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid;
        }

        .section-header.green { border-color: #10B981; }
        .section-header.purple { border-color: #8B5CF6; }
        .section-header.blue { border-color: #3B82F6; }
        .section-header.red { border-color: #EF4444; }

        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-icon.green { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .section-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        .section-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .section-icon.red { background: rgba(239, 68, 68, 0.1); color: #EF4444; }

        .section-title-wrapper { flex: 1; }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .section-title.green { color: #10B981; }
        .section-title.purple { color: #8B5CF6; }
        .section-title.blue { color: #3B82F6; }
        .section-title.red { color: #EF4444; }

        .section-count {
            font-size: 12px;
            color: #64748B;
        }

        .student-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .student-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 12px;
            border-bottom: 1px solid #F1F5F9;
            transition: background 0.2s;
        }

        .student-row:last-child { border-bottom: none; }
        .student-row:hover { background: #F8FAFC; }

        .student-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2463EB 0%, #1E40AF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .student-info { flex: 1; }

        .student-name {
            font-size: 15px;
            font-weight: 600;
            color: #0F172A;
            margin-bottom: 4px;
        }

        .student-time {
            font-size: 13px;
            color: #64748B;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .badge-hadir { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .badge-terlambat { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .badge-izin { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        .badge-pulang { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .badge-belum { background: rgba(239, 68, 68, 0.1); color: #EF4444; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94A3B8;
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state p { font-size: 14px; }

        @media (max-width: 768px) {
            .container { padding: 16px; }
            .section-card { padding: 20px 16px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <button class="back-btn" onclick="window.location.href='{{ route('dashboard') }}'">
                <i data-lucide="arrow-left" width="20" height="20"></i>
            </button>
            <div class="header-title">
                <h1>Laporan Hari Ini</h1>
            </div>
        </div>
        <div class="header-date">
            <div class="header-date-day">{{ $namaHari }}</div>
            <div class="header-date-full">{{ $tanggalFormat }}</div>
        </div>
    </div>

    <div class="container">
        {{-- Sudah Absen Datang --}}
        <div class="section-card">
            <div class="section-header green">
                <div class="section-icon green">
                    <i data-lucide="user-check" width="20" height="20"></i>
                </div>
                <div class="section-title-wrapper">
                    <div class="section-title green">Sudah Absen Datang</div>
                    <div class="section-count">{{ count($sudahDatang) }} siswa</div>
                </div>
            </div>

            @if (empty($sudahDatang))
                <div class="empty-state">
                    <i data-lucide="user-x" width="40" height="40"></i>
                    <p>Belum ada siswa yang absen datang</p>
                </div>
            @else
                <div class="student-list">
                    @foreach ($sudahDatang as $student)
                        @php
                            $jamMasuk = strtotime($student['waktu']);
                            $batasOnTime = strtotime('08:15:00');
                            $statusBadge = $jamMasuk <= $batasOnTime ? 'hadir' : 'terlambat';
                            $statusText  = $jamMasuk <= $batasOnTime ? 'HADIR' : 'TERLAMBAT';
                        @endphp
                        <div class="student-row">
                            <div class="student-avatar">
                                {{ strtoupper(substr($student['nama'], 0, 1)) }}
                            </div>
                            <div class="student-info">
                                <div class="student-name">{{ $student['nama'] }}</div>
                                <div class="student-time">{{ date('H:i', strtotime($student['waktu'])) }} WIB</div>
                            </div>
                            <div class="status-badge badge-{{ $statusBadge }}">
                                {{ $statusText }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Izin Hari Ini --}}
        <div class="section-card">
            <div class="section-header purple">
                <div class="section-icon purple">
                    <i data-lucide="file-text" width="20" height="20"></i>
                </div>
                <div class="section-title-wrapper">
                    <div class="section-title purple">Izin Hari Ini</div>
                    <div class="section-count">{{ count($attendance['izin']) }} siswa</div>
                </div>
            </div>

            @if (empty($attendance['izin']))
                <div class="empty-state">
                    <i data-lucide="calendar-off" width="40" height="40"></i>
                    <p>Tidak ada siswa yang izin hari ini</p>
                </div>
            @else
                <div class="student-list">
                    @foreach ($attendance['izin'] as $student)
                        <div class="student-row">
                            <div class="student-avatar">
                                {{ strtoupper(substr($student['nama'], 0, 1)) }}
                            </div>
                            <div class="student-info">
                                <div class="student-name">{{ $student['nama'] }}</div>
                                <div class="student-time">Izin tidak masuk</div>
                            </div>
                            <div class="status-badge badge-izin">IZIN</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Sudah Absen Pulang --}}
        <div class="section-card">
            <div class="section-header blue">
                <div class="section-icon blue">
                    <i data-lucide="log-out" width="20" height="20"></i>
                </div>
                <div class="section-title-wrapper">
                    <div class="section-title blue">Sudah Absen Pulang</div>
                    <div class="section-count">{{ count($attendance['pulang']) }} siswa</div>
                </div>
            </div>

            @if (empty($attendance['pulang']))
                <div class="empty-state">
                    <i data-lucide="home" width="40" height="40"></i>
                    <p>Belum ada siswa yang absen pulang</p>
                </div>
            @else
                <div class="student-list">
                    @foreach ($attendance['pulang'] as $student)
                        <div class="student-row">
                            <div class="student-avatar">
                                {{ strtoupper(substr($student['nama'], 0, 1)) }}
                            </div>
                            <div class="student-info">
                                <div class="student-name">{{ $student['nama'] }}</div>
                                <div class="student-time">{{ date('H:i', strtotime($student['waktu'])) }} WIB</div>
                            </div>
                            <div class="status-badge badge-pulang">PULANG</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Belum Absen --}}
        <div class="section-card">
            <div class="section-header red">
                <div class="section-icon red">
                    <i data-lucide="alert-circle" width="20" height="20"></i>
                </div>
                <div class="section-title-wrapper">
                    <div class="section-title red">Belum Absen</div>
                    <div class="section-count">{{ count($notAttended) }} siswa</div>
                </div>
            </div>

            @if (empty($notAttended))
                <div class="empty-state">
                    <i data-lucide="check-circle-2" width="40" height="40"></i>
                    <p>Semua siswa sudah absen</p>
                </div>
            @else
                <div class="student-list">
                    @foreach ($notAttended as $student)
                        <div class="student-row">
                            <div class="student-avatar">
                                {{ strtoupper(substr($student['nama'], 0, 1)) }}
                            </div>
                            <div class="student-info">
                                <div class="student-name">{{ $student['nama'] }}</div>
                                <div class="student-time">Belum melakukan absensi</div>
                            </div>
                            <div class="status-badge badge-belum">BELUM ABSEN</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <x-navbar />

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
