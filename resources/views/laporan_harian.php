<?php
require 'php/session_check.php';
require 'php/koneksi.php';

// Hanya admin yang bisa mengakses
if ($_SESSION['level'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
$todayDisplay = date('l, d M Y');

// Ambil semua siswa aktif
$stmt = $conn->query("SELECT id, nama_lengkap FROM users WHERE level = 'siswa' AND status = 'aktif' ORDER BY nama_lengkap ASC");
$allStudents = [];
while ($row = $stmt->fetch_assoc()) {
    $allStudents[$row['id']] = $row['nama_lengkap'];
}

// Ambil data absensi hari ini
$stmt = $conn->prepare("SELECT user_id, waktu, status FROM absensi WHERE tanggal = ? ORDER BY waktu ASC");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

$attendance = [
    'hadir' => [],
    'terlambat' => [],
    'izin' => [],
    'pulang' => []
];

$hasAttended = [];
$hasGoneHome = [];

while ($row = $result->fetch_assoc()) {
    $userId = $row['user_id'];
    $status = $row['status'];
    
    if ($status === 'hadir' || $status === 'terlambat') {
        $attendance[$status][] = [
            'id' => $userId,
            'nama' => $allStudents[$userId] ?? 'Unknown',
            'waktu' => $row['waktu']
        ];
        $hasAttended[] = $userId;
    } elseif ($status === 'pulang') {
        $attendance['pulang'][] = [
            'id' => $userId,
            'nama' => $allStudents[$userId] ?? 'Unknown',
            'waktu' => $row['waktu']
        ];
        $hasGoneHome[] = $userId;
    } elseif ($status === 'izin') {
        $attendance['izin'][] = [
            'id' => $userId,
            'nama' => $allStudents[$userId] ?? 'Unknown',
            'waktu' => $row['waktu']
        ];
        $hasAttended[] = $userId;
    }
}

// Siswa yang belum absen datang
$notAttended = [];
foreach ($allStudents as $id => $nama) {
    if (!in_array($id, $hasAttended)) {
        $notAttended[] = ['id' => $id, 'nama' => $nama];
    }
}

// Gabungkan hadir dan terlambat untuk tampilan "Sudah Absen Datang"
$sudahDatang = array_merge($attendance['hadir'], $attendance['terlambat']);

// Function untuk nama hari dalam bahasa Indonesia
function getNamaHariIndo($date) {
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $hari[date('w', strtotime($date))];
}

$namaHari = getNamaHariIndo($today);
$tanggalFormat = date('d M Y', strtotime($today));
?>
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
        
        .section-header.green {
            border-color: #10B981;
        }
        
        .section-header.purple {
            border-color: #8B5CF6;
        }
        
        .section-header.blue {
            border-color: #3B82F6;
        }
        
        .section-header.red {
            border-color: #EF4444;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .section-icon.green {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }
        
        .section-icon.purple {
            background: rgba(139, 92, 246, 0.1);
            color: #8B5CF6;
        }
        
        .section-icon.blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
        }
        
        .section-icon.red {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }
        
        .section-title-wrapper {
            flex: 1;
        }
        
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
        
        .student-row:last-child {
            border-bottom: none;
        }
        
        .student-row:hover {
            background: #F8FAFC;
        }
        
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
        
        .student-info {
            flex: 1;
        }
        
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
        
        .badge-hadir {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }
        
        .badge-terlambat {
            background: rgba(245, 158, 11, 0.1);
            color: #F59E0B;
        }
        
        .badge-izin {
            background: rgba(139, 92, 246, 0.1);
            color: #8B5CF6;
        }
        
        .badge-pulang {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
        }
        
        .badge-belum {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }
        
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
        
        .empty-state p {
            font-size: 14px;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #FFFFFF;
            box-shadow: 0 -1px 3px 0 rgb(0 0 0 / 0.1);
            padding: 12px 0;
            z-index: 99;
        }
        
        .bottom-nav-content {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            color: #94A3B8;
            transition: color 0.2s;
            flex: 1;
            padding: 8px 0;
        }
        
        .nav-item.active {
            color: #2463EB;
        }
        
        .nav-item:hover {
            color: #2463EB;
        }
        
        .nav-item span {
            font-size: 11px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 16px;
            }
            
            .section-card {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <button class="back-btn" onclick="window.location.href='dashboard.php'">
                <i data-lucide="arrow-left" width="20" height="20"></i>
            </button>
            <div class="header-title">
                <h1>Laporan Hari Ini</h1>
            </div>
        </div>
        <div class="header-date">
            <div class="header-date-day"><?php echo $namaHari; ?></div>
            <div class="header-date-full"><?php echo $tanggalFormat; ?></div>
        </div>
    </div>

    <div class="container">
        <!-- Sudah Absen Datang -->
        <div class="section-card">
            <div class="section-header green">
                <div class="section-icon green">
                    <i data-lucide="user-check" width="20" height="20"></i>
                </div>
                <div class="section-title-wrapper">
                    <div class="section-title green">Sudah Absen Datang</div>
                    <div class="section-count"><?php echo count($sudahDatang); ?> siswa</div>
                </div>
            </div>
            
            <?php if (empty($sudahDatang)): ?>
                <div class="empty-state">
                    <i data-lucide="user-x" width="40" height="40"></i>
                    <p>Belum ada siswa yang absen datang</p>
                </div>
            <?php else: ?>
                <div class="student-list">
                    <?php foreach ($sudahDatang as $student): ?>
                        <div class="student-row">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($student['nama'], 0, 1)); ?>
                            </div>
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['nama']); ?></div>
                                <div class="student-time"><?php echo date('H:i', strtotime($student['waktu'])); ?> WIB</div>
                            </div>
                            <?php 
                            // Tentukan badge berdasarkan waktu
                            $jamMasuk = strtotime($student['waktu']);
                            $batasOnTime = strtotime('08:15:00');
                            $statusBadge = ($jamMasuk <= $batasOnTime) ? 'hadir' : 'terlambat';
                            $statusText = ($jamMasuk <= $batasOnTime) ? 'HADIR' : 'TERLAMBAT';
                            ?>
                            <div class="status-badge badge-<?php echo $statusBadge; ?>">
                                <?php echo $statusText; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Izin Hari Ini -->
        <div class="section-card">
            <div class="section-header purple">
                <div class="section-icon purple">
                    <i data-lucide="file-text" width="20" height="20"></i>
                </div>
                <div class="section-title-wrapper">
                    <div class="section-title purple">Izin Hari Ini</div>
                    <div class="section-count"><?php echo count($attendance['izin']); ?> siswa</div>
                </div>
            </div>
            
            <?php if (empty($attendance['izin'])): ?>
                <div class="empty-state">
                    <i data-lucide="calendar-off" width="40" height="40"></i>
                    <p>Tidak ada siswa yang izin hari ini</p>
                </div>
            <?php else: ?>
                <div class="student-list">
                    <?php foreach ($attendance['izin'] as $student): ?>
                        <div class="student-row">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($student['nama'], 0, 1)); ?>
                            </div>
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['nama']); ?></div>
                                <div class="student-time">Izin tidak masuk</div>
                            </div>
                            <div class="status-badge badge-izin">
                                IZIN
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sudah Absen Pulang -->
        <div class="section-card">
            <div class="section-header blue">
                <div class="section-icon blue">
                    <i data-lucide="log-out" width="20" height="20"></i>
                </div>
                <div class="section-title-wrapper">
                    <div class="section-title blue">Sudah Absen Pulang</div>
                    <div class="section-count"><?php echo count($attendance['pulang']); ?> siswa</div>
                </div>
            </div>
            
            <?php if (empty($attendance['pulang'])): ?>
                <div class="empty-state">
                    <i data-lucide="home" width="40" height="40"></i>
                    <p>Belum ada siswa yang absen pulang</p>
                </div>
            <?php else: ?>
                <div class="student-list">
                    <?php foreach ($attendance['pulang'] as $student): ?>
                        <div class="student-row">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($student['nama'], 0, 1)); ?>
                            </div>
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['nama']); ?></div>
                                <div class="student-time"><?php echo date('H:i', strtotime($student['waktu'])); ?> WIB</div>
                            </div>
                            <div class="status-badge badge-pulang">
                                PULANG
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Belum Absen -->
        <div class="section-card">
            <div class="section-header red">
                <div class="section-icon red">
                    <i data-lucide="alert-circle" width="20" height="20"></i>
                </div>
                <div class="section-title-wrapper">
                    <div class="section-title red">Belum Absen</div>
                    <div class="section-count"><?php echo count($notAttended); ?> siswa</div>
                </div>
            </div>
            
            <?php if (empty($notAttended)): ?>
                <div class="empty-state">
                    <i data-lucide="check-circle-2" width="40" height="40"></i>
                    <p>Semua siswa sudah absen</p>
                </div>
            <?php else: ?>
                <div class="student-list">
                    <?php foreach ($notAttended as $student): ?>
                        <div class="student-row">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($student['nama'], 0, 1)); ?>
                            </div>
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['nama']); ?></div>
                                <div class="student-time">Belum melakukan absensi</div>
                            </div>
                            <div class="status-badge badge-belum">
                                BELUM ABSEN
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bottom-nav">
        <div class="bottom-nav-content">
            <a href="dashboard.php" class="nav-item">
                <i data-lucide="home" width="24" height="24"></i>
                <span>HOME</span>
            </a>
            <a href="history.php" class="nav-item">
                <i data-lucide="users" width="24" height="24"></i>
                <span>SISWA</span>
            </a>
            <a href="laporan_bulanan.php" class="nav-item active">
                <i data-lucide="bar-chart-3" width="24" height="24"></i>
                <span>LAPORAN</span>
            </a>
            <a href="#" class="nav-item">
                <i data-lucide="user-circle" width="24" height="24"></i>
                <span>PROFIL</span>
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
