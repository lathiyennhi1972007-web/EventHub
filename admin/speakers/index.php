
<?php
require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Ham hien thi du lieu an toan
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Lay tu khoa tim kiem
$keyword = trim($_GET['keyword'] ?? '');

// Truy van danh sach dien gia
$sql = "
    SELECT
        id,
        full_name,
        email,
        phone,
        organization,
        position,
        created_at
    FROM speakers
    WHERE 1 = 1
";

$params = [];

if ($keyword !== '') {
    $sql .= "
        AND (
            full_name LIKE ?
            OR email LIKE ?
            OR organization LIKE ?
        )
    ";

    $searchValue = '%' . $keyword . '%';
    $params = [$searchValue, $searchValue, $searchValue];
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$speakers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý diễn giả | EventHub</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/events.css">

    <style>
        .speaker-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .speaker-name {
            color: #654653;
            font-weight: 600;
        }

        .speaker-subtitle {
            color: #9b8791;
            font-size: 12px;
        }

        .speaker-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #fff0f6;
            color: #d96b9a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .speaker-table {
            width: 100%;
        }

        .speaker-table td {
            vertical-align: middle;
        }

        .speaker-empty {
            text-align: center;
            padding: 35px !important;
            color: #9b8791;
        }

        .speaker-count {
            color: #d96b9a;
            font-weight: 700;
        }
		.speaker-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.speaker-view-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: #f1eaff;
    border: 1px solid #e5d9ff;
    color: #9575d5;
    text-decoration: none;
    font-size: 21px;
    transition: all 0.2s ease;
}

.speaker-view-icon:hover {
    background: #e6d9ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(149, 117, 213, 0.18);
}
.speaker-edit-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: #fff0f6;
    border: 1px solid #f8d5e5;
    color: #df78a5;
    text-decoration: none;
    font-size: 21px;
    transition: all 0.2s ease;
}

.speaker-edit-icon:hover {
    background: #fbdce9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(217, 107, 154, 0.18);
}
.speaker-delete-button {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: #fff0f0;
    border: 1px solid #f8d1d1;
    color: #d96b75;
    font-size: 19px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.speaker-delete-button:hover {
    background: #ffdddd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(217, 107, 117, 0.18);
}
    </style>
</head>

<body>

<div class="admin-layout">

    <?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">

        <header class="admin-topbar">
            <div>
                <div class="topbar-title">Quản lý diễn giả</div>
                <div class="topbar-subtitle">
                    Quản lý thông tin diễn giả tham gia các sự kiện
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
 <?php if (!empty($_SESSION['speaker_success'])): ?>
    <div class="event-message success-message">
        <?= e($_SESSION['speaker_success']) ?>
    </div>
    <?php unset($_SESSION['speaker_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['speaker_error'])): ?>
    <div class="event-message error-message">
        <?= e($_SESSION['speaker_error']) ?>
    </div>
    <?php unset($_SESSION['speaker_error']); ?>
<?php endif; ?>
    <h1>Danh sách diễn giả</h1>
    <p>Quản lý hồ sơ và thông tin liên hệ của diễn giả.</p>

    <a href="create.php" class="search-button">
        + Thêm diễn giả mới
    </a>
</section>

            <section class="event-summary">
                <div class="summary-card">
                    <span>Tổng số diễn giả</span>
                    <strong><?= count($speakers) ?></strong>
                </div>

                <div class="summary-card">
                    <span>Kết quả tìm kiếm</span>
                    <strong>
                        <?= $keyword !== '' ? 'Đang lọc' : 'Tất cả' ?>
                    </strong>
                </div>
            </section>

            <section class="event-toolbar">

                <form method="GET" action="index.php"
                      class="event-search-form">

                    <input
                        type="text"
                        name="keyword"
                        placeholder="Tìm theo tên, email hoặc tổ chức..."
                        value="<?= e($keyword) ?>"
                    >

                    <button type="submit" class="search-button">
                        Tìm kiếm
                    </button>

                    <a href="index.php" class="reset-button">
                        Đặt lại
                    </a>

                </form>

            </section>

            <section class="event-table-card">

                <div class="table-heading">
                    <div>
                        <h2>Các diễn giả</h2>
                        <p>
                            Tìm thấy
                            <span class="speaker-count">
                                <?= count($speakers) ?>
                            </span>
                            diễn giả phù hợp.
                        </p>
                    </div>
                </div>

                <div class="table-responsive">

                    <table class="event-table speaker-table">

<thead>
    <tr>
        <th>Mã</th>
        <th>Diễn giả</th>
        <th>Tổ chức / Chức vụ</th>
        <th>Email</th>
        <th>Số điện thoại</th>
        <th>Ngày tạo</th>
        <th>Thao tác</th>
    </tr>
</thead>
                        <tbody>

                            <?php if (count($speakers) > 0): ?>

                                <?php foreach ($speakers as $speaker): ?>

                                    <tr>

                                        <td>
                                            #<?= (int) $speaker['id'] ?>
                                        </td>

                                        <td>
                                            <div class="speaker-info">
                                                <span class="speaker-name">
                                                    <?= e($speaker['full_name']) ?>
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="speaker-info">
                                                <span>
                                                    <?= e($speaker['organization'] ?: 'Chưa cập nhật') ?>
                                                </span>

                                                <small class="speaker-subtitle">
                                                    <?= e($speaker['position'] ?: 'Chưa cập nhật chức vụ') ?>
                                                </small>
                                            </div>
                                        </td>

                                        <td>
                                            <?= e($speaker['email'] ?: 'Chưa cập nhật') ?>
                                        </td>

                                        <td>
                                            <?= e($speaker['phone'] ?: 'Chưa cập nhật') ?>
                                        </td>

                                        <td>
                                            <?= date(
                                                'd/m/Y',
                                                strtotime($speaker['created_at'])
                                            ) ?>
                                        </td>
<td>
    <div class="speaker-actions">

        <!-- Xem chi tiet -->
        <a
            href="detail.php?id=<?= (int) $speaker['id'] ?>"
            class="speaker-view-icon"
            title="Xem chi tiết diễn giả"
            aria-label="Xem chi tiết diễn giả"
        >
            <span aria-hidden="true">&#128065;</span>
        </a>

        <!-- Chinh sua -->
        <a
            href="edit.php?id=<?= (int) $speaker['id'] ?>"
            class="speaker-edit-icon"
            title="Chỉnh sửa diễn giả"
            aria-label="Chỉnh sửa diễn giả"
        >
            <span aria-hidden="true">&#9998;</span>
        </a>
<form
    action="delete.php"
    method="POST"
    onsubmit="return confirm('Bạn có chắc chắn muốn xóa diễn giả này không?');"
>
    <input
        type="hidden"
        name="id"
        value="<?= (int) $speaker['id'] ?>"
    >

    <input
        type="hidden"
        name="csrf_token"
        value="<?= e($_SESSION['csrf_token']) ?>"
    >

    <button
        type="submit"
        class="speaker-delete-button"
        title="Xóa diễn giả"
        aria-label="Xóa diễn giả"
    >
        <span aria-hidden="true">&#128465;</span>
    </button>
</form>
    </div>
</td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
<td colspan="7" class="speaker-empty">
                                        Chưa có diễn giả phù hợp.
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
