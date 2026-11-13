
<?php

// Bao ve khu vuc Admin
require_once __DIR__ . '/../includes/admin_guard.php';

// Ket noi database
require_once __DIR__ . '/../config/database.php';

$fullName = $_SESSION['full_name'] ?? 'Admin';

// Ham dem so ban ghi
function countRecords(PDO $pdo, string $table): int
{
    // Ten bang chi duoc truyen tu danh sach co dinh ben duoi
    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");

    return (int) $stmt->fetchColumn();
}

// Lay du lieu thong ke
$stats = [
    'events' => countRecords($pdo, 'events'),
    'users' => countRecords($pdo, 'users'),
    'registrations' => countRecords($pdo, 'registrations'),
    'checkins' => countRecords($pdo, 'checkins'),
    'tasks' => countRecords($pdo, 'tasks')
];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin | EventHub</title>

    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<div class="admin-layout">

<!-- SIDEBAR DÙNG CHUNG -->
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <!-- MAIN -->
    <main class="admin-main">

        <header class="admin-topbar">

            <div>
                <div class="topbar-title">Tổng quan hệ thống</div>
                <div class="topbar-subtitle">
                    Quản lý và theo dõi hoạt động EventHub
                </div>
            </div>

            <div class="admin-profile">

                <div class="profile-avatar">A</div>

                <div>
                    <div class="profile-name">
                        <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <span class="profile-role">Administrator</span>
                </div>

                <a href="../auth/logout.php" class="logout-link">
                    Đăng xuất
                </a>

            </div>

        </header>

        <div class="admin-content">

            <section class="page-heading">
                <h1>Dashboard</h1>
                <p>Chào mừng bạn đến với trung tâm quản trị EventHub.</p>
            </section>

            <section class="welcome-panel">

                <h2>Xin chào, <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>! 🌸</h2>

                <p>
                    Đây là không gian quản trị tổng quan.
                    Bạn có thể theo dõi dữ liệu sự kiện, người dùng,
                    đăng ký và hoạt động tổ chức tại đây.
                </p>

            </section>

            <h2 class="section-title">Thống kê tổng quan</h2>

            <section class="stats-grid">

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Sự kiện</span>
                        <span class="stat-icon">📅</span>
                    </div>
                    <h3><?= $stats['events'] ?></h3>
                    <p>Tổng số sự kiện trong hệ thống</p>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Tài khoản</span>
                        <span class="stat-icon">👥</span>
                    </div>
                    <h3><?= $stats['users'] ?></h3>
                    <p>Tổng số tài khoản người dùng</p>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Đăng ký</span>
                        <span class="stat-icon">🎟️</span>
                    </div>
                    <h3><?= $stats['registrations'] ?></h3>
                    <p>Tổng số lượt đăng ký</p>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <span>Check-in</span>
                        <span class="stat-icon">📷</span>
                    </div>
                    <h3><?= $stats['checkins'] ?></h3>
                    <p>Tổng số lượt check-in</p>
                </div>

            </section>

            <h2 class="section-title">Các chức năng quản lý</h2>

            <section class="module-grid">

                <div class="module-card">
                    <span class="module-icon">📅</span>
                    <h3>Quản lý sự kiện</h3>
                    <p>Tạo, cập nhật và theo dõi sự kiện.</p>
                    <span class="module-status">Đang chuẩn bị</span>
                </div>

                <div class="module-card">
                    <span class="module-icon">🎟️</span>
                    <h3>Đăng ký và vé điện tử</h3>
                    <p>Quản lý đăng ký, loại vé và mã QR.</p>
                    <span class="module-status">Đang chuẩn bị</span>
                </div>

                <div class="module-card">
                    <span class="module-icon">📋</span>
                    <h3>Timeline & Kanban</h3>
                    <p>Phân công nhiệm vụ và theo dõi tiến độ.</p>
                    <span class="module-status">Đang chuẩn bị</span>
                </div>

                <div class="module-card">
                    <span class="module-icon">💰</span>
                    <h3>Ngân sách</h3>
                    <p>Quản lý dự toán và chi phí thực tế.</p>
                    <span class="module-status">Đang chuẩn bị</span>
                </div>

                <div class="module-card">
                    <span class="module-icon">🧰</span>
                    <h3>Tài nguyên</h3>
                    <p>Quản lý địa điểm, thiết bị và nhà cung cấp.</p>
                    <span class="module-status">Đang chuẩn bị</span>
                </div>

                <div class="module-card">
                    <span class="module-icon">📊</span>
                    <h3>Báo cáo và phân tích</h3>
                    <p>Thống kê hiệu quả và kết quả sự kiện.</p>
                    <span class="module-status">Đang chuẩn bị</span>
                </div>

            </section>

        </div>

    </main>

</div>

</body>
</html>
