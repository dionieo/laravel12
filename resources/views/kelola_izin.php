<?php
require 'php/session_check.php';
require 'php/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Hanya admin yang bisa approve izin
if ($_SESSION['level'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$admin_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $izin_id = intval($_POST['izin_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    $admin_notes = trim($_POST['admin_notes']);
    
    if ($action === 'approve') {
        // Get izin data
        $stmt = $conn->prepare("SELECT user_id, tanggal FROM izin_siswa WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $izin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $izin = $result->fetch_assoc();
            $user_id = $izin['user_id'];
            $tanggal = $izin['tanggal'];
            
            // Check apakah sudah ada absensi di tanggal ini
            $stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
            $stmt->bind_param("is", $user_id, $tanggal);
            $stmt->execute();
            $check = $stmt->get_result();
            
            if ($check->num_rows > 0) {
                $message = "Tidak bisa approve: Siswa sudah absensi di tanggal ini.";
                $message_type = "error";
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Update status izin
                    $stmt = $conn->prepare("UPDATE izin_siswa SET status = 'approved', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $stmt->bind_param("sii", $admin_notes, $admin_id, $izin_id);
                    $stmt->execute();
                    
                    // Insert ke tabel absensi dengan status 'izin'
                    $waktu = date("H:i:s");
                    $stmt = $conn->prepare("INSERT INTO absensi (user_id, tanggal, waktu, status, token_id) VALUES (?, ?, ?, 'izin', NULL)");
                    $stmt->bind_param("iss", $user_id, $tanggal, $waktu);
                    $stmt->execute();
                    
                    $conn->commit();
                    $message = "Izin disetujui! Status izin telah ditambahkan ke absensi.";
                    $message_type = "success";
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Gagal approve izin: " . $e->getMessage();
                    $message_type = "error";
                }
            }
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE izin_siswa SET status = 'rejected', admin_notes = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("sii", $admin_notes, $admin_id, $izin_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Pengajuan izin ditolak.";
            $message_type = "success";
        } else {
            $message = "Gagal menolak izin atau izin tidak ditemukan.";
            $message_type = "error";
        }
    }
}

// Get all pending izin with urgency sorting
// Priority: 1) Tanggal izin sudah lewat, 2) Tanggal izin hari ini, 3) Pending > 24 jam, 4) Tanggal terdekat
$sql_pending = "
    SELECT i.*, 
            u.nama_lengkap, 
            u.username,
            TIMESTAMPDIFF(HOUR, i.created_at, NOW()) as hours_pending,
            DATEDIFF(i.tanggal, CURDATE()) as days_until,
            CASE 
                WHEN i.tanggal < CURDATE() THEN 1
                WHEN i.tanggal = CURDATE() THEN 2
                WHEN TIMESTAMPDIFF(HOUR, i.created_at, NOW()) > 24 THEN 3
                ELSE 4
            END as urgency_level
    FROM izin_siswa i
    JOIN users u ON i.user_id = u.id
    WHERE i.status = 'pending' AND u.status = 'aktif'
    ORDER BY urgency_level ASC, i.tanggal ASC, i.created_at ASC
";
$result_pending = $conn->query($sql_pending);
$pending_list = [];
while ($row = $result_pending->fetch_assoc()) {
    $pending_list[] = $row;
}

// Get recent approved/rejected
$sql_history = "
    SELECT i.*, u.nama_lengkap, u.username, a.nama_lengkap as admin_name
    FROM izin_siswa i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN users a ON i.approved_by = a.id
    WHERE i.status IN ('approved', 'rejected')
    ORDER BY i.approved_at DESC
    LIMIT 30
";
$result_history = $conn->query($sql_history);
$history_list = [];
while ($row = $result_history->fetch_assoc()) {
    $history_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Izin Siswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #F8FAFC;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        /* Header */
        .header {
            background: white;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            background: #f1f5f9;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: #334155;
        }

        .back-btn:hover {
            background: #e2e8f0;
            transform: translateX(-2px);
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .header-title p {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Summary Chips */
        .summary-chips {
            max-width: 1200px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .chip {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            min-width: 160px;
            transition: all 0.2s;
        }

        .chip:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .chip-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .chip.pending .chip-icon {
            background: #FEF3C7;
            color: #F59E0B;
        }

        .chip.approved .chip-icon {
            background: #D1FAE5;
            color: #10B981;
        }

        .chip.rejected .chip-icon {
            background: #FEE2E2;
            color: #EF4444;
        }

        .chip-content h3 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
        }

        .chip-content p {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Card Layout */
        .izin-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.2s;
            border: 1px solid #f1f5f9;
            position: relative;
        }

        .izin-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        
        /* Urgent states */
        .izin-card.urgent-overdue {
            border: 2px solid #EF4444;
            background: #FEF2F2;
        }
        
        .izin-card.urgent-today {
            border: 2px solid #F59E0B;
            background: #FFFBEB;
        }
        
        .izin-card.urgent-pending {
            border: 2px solid #F59E0B;
            border-left-width: 4px;
        }
        
        .urgent-badge {
            position: absolute;
            top: -8px;
            right: 1rem;
            background: #EF4444;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            animation: pulse 2s infinite;
        }
        
        .urgent-badge.warning {
            background: #F59E0B;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .pending-time-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            background: #FEF3C7;
            color: #92400E;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .card-header {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .profile-pic {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .student-meta {
            font-size: 0.875rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .meta-divider {
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .status-badge.pending {
            background: #FEF3C7;
            color: #F59E0B;
        }

        .status-badge.approved {
            background: #D1FAE5;
            color: #10B981;
        }

        .status-badge.rejected {
            background: #FEE2E2;
            color: #EF4444;
        }

        .card-body {
            margin-bottom: 1rem;
        }

        .info-row {
            margin-bottom: 1rem;
        }

        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.375rem;
        }

        .info-value {
            color: #334155;
            line-height: 1.6;
        }

        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.5rem 0.75rem;
            background: #eff6ff;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .attachment-link:hover {
            background: #dbeafe;
            transform: translateX(2px);
        }

        /* Admin Actions */
        .admin-actions {
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .admin-notes {
            width: 100%;
            padding: 0.75rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.875rem;
            resize: vertical;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }

        .admin-notes:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-reject {
            background: white;
            color: #EF4444;
            border: 2px solid #FEE2E2;
        }

        .btn-reject:hover {
            background: #FEF2F2;
            border-color: #FECACA;
        }

        .btn-approve {
            background: #10B981;
            color: white;
        }

        .btn-approve:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        /* Alert */
        .alert {
            max-width: 1200px;
            margin: 1rem auto;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.success {
            background: #D1FAE5;
            color: #065f46;
            border: 1px solid #10B981;
        }

        .alert.error {
            background: #FEE2E2;
            color: #991b1b;
            border: 1px solid #EF4444;
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.06);
            padding: 0.75rem;
            z-index: 100;
        }

        .nav-container {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-around;
        }

        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            padding: 0.5rem;
            text-decoration: none;
            color: #64748b;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .nav-item:hover,
        .nav-item.active {
            color: #3b82f6;
            background: #eff6ff;
        }

        .nav-item i {
            font-size: 1.25rem;
        }

        .nav-item span {
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .summary-chips {
                flex-direction: column;
            }

            .chip {
                min-width: 100%;
            }

            .action-buttons {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
            }

            .header-title h1 {
                font-size: 1.25rem;
            }

            .container {
                padding: 0 1rem;
            }

            .summary-chips {
                padding: 0 1rem;
            }
        }

        .section-divider {
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="header-title">
                <h1>Kelola Izin Siswa</h1>
                <p>Review dan proses pengajuan izin</p>
            </div>
        </div>
    </header>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="alert <?= $message_type ?>" style="margin: 1rem 1.5rem;">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Summary Chips -->
    <div class="summary-chips">
        <div class="chip pending">
            <div class="chip-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="chip-content">
                <h3><?= count($pending_list) ?></h3>
                <p>Menunggu</p>
            </div>
        </div>

        <div class="chip approved">
            <div class="chip-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="chip-content">
                <h3><?= count(array_filter($history_list, fn($h) => $h['status'] === 'approved')) ?></h3>
                <p>Disetujui</p>
            </div>
        </div>

        <div class="chip rejected">
            <div class="chip-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="chip-content">
                <h3><?= count(array_filter($history_list, fn($h) => $h['status'] === 'rejected')) ?></h3>
                <p>Ditolak</p>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="container">
        <h2 class="section-title">
            <i class="fas fa-hourglass-half" style="color: #F59E0B;"></i>
            Pengajuan Menunggu Review
        </h2>

        <?php if (count($pending_list) > 0): ?>
            <?php foreach ($pending_list as $izin): 
                // Get first letter for profile pic
                $initial = strtoupper(substr($izin['nama_lengkap'], 0, 1));
                
                // Determine leave type
                $leaveType = 'Izin';
                if (stripos($izin['alasan_siswa'], 'sakit') !== false) {
                    $leaveType = 'Sakit';
                } elseif (stripos($izin['alasan_siswa'], 'keluarga') !== false) {
                    $leaveType = 'Keluarga';
                }
                
                // Determine urgency
                $urgencyClass = '';
                $urgencyBadge = '';
                $urgencyLevel = $izin['urgency_level'];
                
                if ($urgencyLevel == 1) {
                    // Tanggal sudah lewat
                    $urgencyClass = 'urgent-overdue';
                    $urgencyBadge = '<div class="urgent-badge">⚠️ TERLAMBAT</div>';
                } elseif ($urgencyLevel == 2) {
                    // Hari ini
                    $urgencyClass = 'urgent-today';
                    $urgencyBadge = '<div class="urgent-badge warning">🔥 HARI INI</div>';
                } elseif ($urgencyLevel == 3) {
                    // Pending > 24 jam
                    $urgencyClass = 'urgent-pending';
                    $urgencyBadge = '<div class="urgent-badge warning">⏰ PENDING LAMA</div>';
                }
                
                // Format pending time
                $hoursPending = $izin['hours_pending'];
                $pendingTimeText = '';
                if ($hoursPending < 1) {
                    $pendingTimeText = 'Baru saja';
                } elseif ($hoursPending < 24) {
                    $pendingTimeText = floor($hoursPending) . ' jam yang lalu';
                } else {
                    $daysPending = floor($hoursPending / 24);
                    $pendingTimeText = $daysPending . ' hari yang lalu';
                }
            ?>
                <div class="izin-card <?= $urgencyClass ?>">
                    <?= $urgencyBadge ?>
                    <div class="card-header">
                        <div class="profile-pic"><?= $initial ?></div>
                        <div class="student-info">
                            <div class="student-name"><?= htmlspecialchars($izin['nama_lengkap']) ?></div>
                            <div class="student-meta">
                                <span>@<?= htmlspecialchars($izin['username']) ?></span>
                                <span class="meta-divider"></span>
                                <span><?= $leaveType ?></span>
                                <span class="meta-divider"></span>
                                <span class="pending-time-badge">
                                    <i class="fas fa-hourglass-half"></i>
                                    <?= $pendingTimeText ?>
                                </span>
                            </div>
                        </div>
                        <span class="status-badge pending">
                            <i class="fas fa-clock"></i>
                            Menunggu
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-calendar-alt"></i> Tanggal Izin
                            </div>
                            <div class="info-value">
                                <?= date('l, d F Y', strtotime($izin['tanggal'])) ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">
                                <i class="fas fa-file-alt"></i> Alasan
                            </div>
                            <div class="info-value">
                                <?= nl2br(htmlspecialchars($izin['alasan_siswa'])) ?>
                            </div>
                        </div>

                        <?php if ($izin['bukti_file']): ?>
                            <div class="info-row">
                                <a href="<?= htmlspecialchars($izin['bukti_file']) ?>" target="_blank" class="attachment-link">
                                    <i class="fas fa-paperclip"></i>
                                    Lihat Lampiran
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="info-row" style="margin-bottom: 0;">
                            <div class="info-label">
                                <i class="fas fa-clock"></i> Diajukan pada
                            </div>
                            <div class="info-value" style="font-size: 0.875rem; color: #64748b;">
                                <?= date('d M Y, H:i', strtotime($izin['created_at'])) ?> WIB
                            </div>
                        </div>
                    </div>

                    <form method="POST" class="admin-actions">
                        <input type="hidden" name="izin_id" value="<?= $izin['id'] ?>">
                        <textarea 
                            name="admin_notes" 
                            class="admin-notes" 
                            placeholder="Catatan admin (opsional)..." 
                            rows="2"
                        ></textarea>
                        <div class="action-buttons">
                            <button 
                                type="submit" 
                                name="action" 
                                value="reject" 
                                class="btn btn-reject"
                                onclick="return confirm('Tolak pengajuan izin ini?')"
                            >
                                <i class="fas fa-times"></i>
                                Tolak
                            </button>
                            <button 
                                type="submit" 
                                name="action" 
                                value="approve" 
                                class="btn btn-approve"
                                onclick="return confirm('Setujui izin ini? Status izin akan ditambahkan ke absensi.')"
                            >
                                <i class="fas fa-check"></i>
                                Setujui
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Tidak ada pengajuan izin yang menunggu review</p>
            </div>
        <?php endif; ?>

        <!-- History Section -->
        <?php if (count($history_list) > 0): ?>
            <div class="section-divider"></div>
            
            <h2 class="section-title">
                <i class="fas fa-history" style="color: #64748b;"></i>
                Riwayat Terbaru
            </h2>

            <?php foreach (array_slice($history_list, 0, 10) as $izin): 
                $initial = strtoupper(substr($izin['nama_lengkap'], 0, 1));
            ?>
                <div class="izin-card">
                    <div class="card-header">
                        <div class="profile-pic"><?= $initial ?></div>
                        <div class="student-info">
                            <div class="student-name"><?= htmlspecialchars($izin['nama_lengkap']) ?></div>
                            <div class="student-meta">
                                <span><?= date('d M Y', strtotime($izin['tanggal'])) ?></span>
                                <span class="meta-divider"></span>
                                <span>oleh <?= htmlspecialchars($izin['admin_name'] ?? 'Admin') ?></span>
                            </div>
                        </div>
                        <span class="status-badge <?= $izin['status'] ?>">
                            <i class="fas fa-<?= $izin['status'] === 'approved' ? 'check-circle' : 'times-circle' ?>"></i>
                            <?= $izin['status'] === 'approved' ? 'Disetujui' : 'Ditolak' ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="info-row">
                            <div class="info-label">Alasan</div>
                            <div class="info-value"><?= htmlspecialchars($izin['alasan_siswa']) ?></div>
                        </div>

                        <?php if ($izin['admin_notes']): ?>
                            <div class="info-row" style="margin-bottom: 0;">
                                <div class="info-label">Catatan Admin</div>
                                <div class="info-value"><?= nl2br(htmlspecialchars($izin['admin_notes'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </a>
            <a href="laporan_harian.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Laporan</span>
            </a>
            <a href="kelola_izin.php" class="nav-item active">
                <i class="fas fa-clipboard-check"></i>
                <span>Izin</span>
            </a>
            <a href="history.php" class="nav-item">
                <i class="fas fa-history"></i>
                <span>Data</span>
            </a>
        </div>
    </nav>
</body>
</html>
