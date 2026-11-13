
<?php

require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Tao token CSRF cho thao tac xoa su kien
if (empty($_SESSION['delete_event_token'])) {
    $_SESSION['delete_event_token'] = bin2hex(random_bytes(32));
}

// Ham chuyen doi ky tu an toan khi hien thi
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Lay tu khoa tim kiem
$keyword = trim($_GET['keyword'] ?? '');

// Lay trang thai loc
$selectedStatus = trim($_GET['status'] ?? '');

// Lay danh sach trang thai dang co trong database
$statusStmt = $pdo->query("
    SELECT DISTINCT status
    FROM events
    ORDER BY status
");

$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

// Tao cau truy van danh sach su kien
$sql = "
    SELECT
        id,
        title,
        description,
        start_at,
        end_at,
        capacity,
        status,
        organizer_id,
        created_at
    FROM events
    WHERE 1 = 1
";

$params = [];

// Tim kiem theo ten su kien
if ($keyword !== '') {
    $sql .= " AND title LIKE ?";
    $params[] = '%' . $keyword . '%';
}

// Loc theo trang thai
if ($selectedStatus !== '' && in_array($selectedStatus, $statuses, true)) {
    $sql .= " AND status = ?";
    $params[] = $selectedStatus;
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chuyen trang thai thanh ten tieng Viet
function statusLabel($status): string
{
    $labels = [
        'draft' => 'Bản nháp',
        'published' => 'Đã công bố',
        'ongoing' => 'Đang diễn ra',
        'completed' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy',
    ];

    return $labels[$status] ?? $status;
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý sự kiện | EventHub</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/events.css">

    <style>
        /* Nhom nut thao tac */
        .event-actions {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .event-actions form {
            margin: 0;
        }

        .icon-action {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
            text-decoration: none;
            padding: 0;
        }

        .icon-action svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .edit-icon {
            color: #d96b9a;
            background: #fff1f6;
            border-color: #f6d9e5;
        }

        .edit-icon:hover {
            background: #f9dce9;
            transform: translateY(-2px);
        }

        .delete-icon {
            color: #df7777;
            background: #fff0f0;
            border-color: #f6dada;
        }

        .delete-icon:hover {
            background: #ffe0e0;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

<div class="admin-layout">

<!-- SIDEBAR DÙNG CHUNG -->
<?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <!-- NOI DUNG CHINH -->
    <main class="admin-main">

        <header class="admin-topbar">

            <div>
                <div class="topbar-title">Quản lý sự kiện</div>
                <div class="topbar-subtitle">
                    Theo dõi và quản lý các sự kiện trên EventHub
                </div>
            </div>

            <div class="admin-profile">

                <div class="profile-avatar">A</div>

                <div>
                    <div class="profile-name">
                        <?= e($_SESSION['full_name'] ?? 'Admin') ?>
                    </div>
                    <span class="profile-role">Administrator</span>
                </div>

                <a href="../../auth/logout.php" class="logout-link">
                    Đăng xuất
                </a>

            </div>

        </header>

        <div class="admin-content">

<section class="page-heading">
    <h1>Danh sách sự kiện</h1>
    <p>Quản lý thông tin và trạng thái các sự kiện của hệ thống.</p>

    <a href="create.php" class="search-button">
        + Thêm sự kiện mới
    </a>

    <?php if (!empty($_SESSION['event_success'])): ?>
        <div class="event-message success-message">
            <?= htmlspecialchars($_SESSION['event_success']) ?>
        </div>
        <?php unset($_SESSION['event_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['event_error'])): ?>
        <div class="event-message error-message">
            <?= e($_SESSION['event_error']) ?>
        </div>
        <?php unset($_SESSION['event_error']); ?>
    <?php endif; ?>
</section>
            <!-- THONG KE -->
            <section class="event-summary">

                <div class="summary-card">
                    <span>Tổng số sự kiện</span>
                    <strong><?= count($events) ?></strong>
                </div>

                <div class="summary-card">
                    <span>Trạng thái đang lọc</span>
                    <strong>
                        <?= $selectedStatus !== ''
                            ? e(statusLabel($selectedStatus))
                            : 'Tất cả' ?>
                    </strong>
                </div>

            </section>

            <!-- TIM KIEM VA LOC -->
            <section class="event-toolbar">

                <form method="GET" action="index.php" class="event-search-form">

                    <input
                        type="text"
                        name="keyword"
                        placeholder="Tìm kiếm theo tên sự kiện..."
                        value="<?= e($keyword) ?>"
                    >

                    <select name="status">
                        <option value="">Tất cả trạng thái</option>

                        <?php foreach ($statuses as $status): ?>
                            <option
                                value="<?= e($status) ?>"
                                <?= $selectedStatus === $status ? 'selected' : '' ?>
                            >
                                <?= e(statusLabel($status)) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <button type="submit" class="search-button">
                        Tìm kiếm
                    </button>

                    <a href="index.php" class="reset-button">
                        Đặt lại
                    </a>

                </form>

            </section>

            <!-- BANG SU KIEN -->
            <section class="event-table-card">

                <div class="table-heading">
                    <div>
                        <h2>Các sự kiện</h2>
                        <p>
                            Tìm thấy <?= count($events) ?> sự kiện phù hợp.
                        </p>
                    </div>
                </div>

                <div class="table-responsive">

                    <table class="event-table">

                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Tên sự kiện</th>
                                <th>Thời gian</th>
                                <th>Sức chứa</th>
                                <th>Organizer ID</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($events) > 0): ?>

                                <?php foreach ($events as $event): ?>

                                    <tr>

                                        <td>#<?= (int) $event['id'] ?></td>

                                        <td>
                                            <strong class="event-name">
                                                <?= e($event['title']) ?>
                                            </strong>

                                            <small class="event-description">
                                                <?= e($event['description'] ?? '') ?>
                                            </small>
                                        </td>

                                        <td>
                                            <div>
                                                <?= date(
                                                    'd/m/Y H:i',
                                                    strtotime($event['start_at'])
                                                ) ?>
                                            </div>

                                            <small>
                                                Đến:
                                                <?= date(
                                                    'd/m/Y H:i',
                                                    strtotime($event['end_at'])
                                                ) ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= number_format((int) $event['capacity']) ?>
                                            người
                                        </td>

                                        <td>
                                            #<?= (int) $event['organizer_id'] ?>
                                        </td>

                                        <td>
                                            <span class="status-badge status-<?= e($event['status']) ?>">
                                                <?= e(statusLabel($event['status'])) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="event-actions">

                                                <!-- Bieu tuong but chi: Chinh sua -->
                                                <a
                                                    href="edit.php?id=<?= (int) $event['id'] ?>"
                                                    class="icon-action edit-icon"
                                                    title="Chỉnh sửa sự kiện"
                                                    aria-label="Chỉnh sửa sự kiện"
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M12 20h9"/>
                                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                                                    </svg>
                                                </a>

                                                <!-- Bieu tuong thung rac: Xoa -->
                                                <form
                                                    action="delete.php"
                                                    method="POST"
                                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa sự kiện #<?= (int) $event['id'] ?>?');"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int) $event['id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($_SESSION['delete_event_token']) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="icon-action delete-icon"
                                                        title="Xóa sự kiện"
                                                        aria-label="Xóa sự kiện"
                                                    >
                                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M3 6h18"/>
                                                            <path d="M8 6V4h8v2"/>
                                                            <path d="m19 6-1 14H6L5 6"/>
                                                            <path d="M10 11v5"/>
                                                            <path d="M14 11v5"/>
                                                        </svg>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="empty-message">
                                        Chưa có sự kiện phù hợp.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </div>

    </main>

</div>

</body>
</html>
