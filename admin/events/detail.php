<?php
require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Ham hien thi du lieu an toan
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Chuyen trang thai sang tieng Viet
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

// Kiem tra ID su kien
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$eventId || $eventId < 1) {
    http_response_code(400);
    exit('Mã sự kiện không hợp lệ.');
}

// Lay thong tin su kien va thong tin lien ket
$stmt = $pdo->prepare("
    SELECT
        e.id,
        e.title,
        e.description,
        e.start_at,
        e.end_at,
        e.capacity,
        e.status,
        e.organizer_id,
        e.category_id,
        e.venue_id,
        e.created_at,

        c.name AS category_name,

        v.venue_name,
        v.address AS venue_address,
        v.capacity AS venue_capacity,
        v.status AS venue_status,

        u.full_name AS organizer_name,
        u.email AS organizer_email,
        u.phone AS organizer_phone

    FROM events e
    LEFT JOIN event_categories c ON c.id = e.category_id
    LEFT JOIN venues v ON v.id = e.venue_id
    LEFT JOIN users u ON u.id = e.organizer_id

    WHERE e.id = ?
    LIMIT 1
");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    http_response_code(404);
    exit('Không tìm thấy sự kiện.');
}
// Lấy danh sách diễn giả tham gia sự kiện
$speakerStmt = $pdo->prepare("
    SELECT
        s.id,
        s.full_name,
        s.organization,
        s.position,
        s.email,
        es.speaker_role,
        es.display_order
    FROM event_speakers es
    INNER JOIN speakers s ON s.id = es.speaker_id
    WHERE es.event_id = ?
    ORDER BY es.display_order ASC, s.full_name ASC
");

$speakerStmt->execute([$eventId]);
$speakers = $speakerStmt->fetchAll(PDO::FETCH_ASSOC);
// Tự động xác định thứ tự tiếp theo của diễn giả
$nextDisplayOrder = 1;

foreach ($speakers as $speaker) {
    $currentOrder = (int) ($speaker['display_order'] ?? 0);

    if ($currentOrder >= $nextDisplayOrder) {
        $nextDisplayOrder = $currentOrder + 1;
    }
}
// Lấy danh sách diễn giả chưa được gắn vào sự kiện
$availableSpeakerStmt = $pdo->prepare("
    SELECT
        s.id,
        s.full_name,
        s.organization
    FROM speakers s
    WHERE NOT EXISTS (
        SELECT 1
        FROM event_speakers es
        WHERE es.speaker_id = s.id
          AND es.event_id = ?
    )
    ORDER BY s.full_name ASC
");

$availableSpeakerStmt->execute([$eventId]);
$availableSpeakers = $availableSpeakerStmt->fetchAll(PDO::FETCH_ASSOC);
function formatEventDate($value): string
{
    if (empty($value)) {
        return 'Chưa cập nhật';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : 'Chưa cập nhật';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết sự kiện | EventHub</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/events.css">

    <style>
        .detail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
        }

        .detail-card {
            background: #fff;
            border: 1px solid #f0e3ea;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 5px 18px rgba(120, 75, 95, 0.05);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 20px;
        }

        .detail-item {
            padding: 15px 16px;
            border: 1px solid #f4e8ee;
            border-radius: 12px;
            background: #fffafd;
            min-width: 0;
        }

        .detail-label {
            display: block;
            color: #8b7b83;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .detail-value {
            color: #44343c;
            font-size: 15px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .detail-description {
            margin-top: 18px;
            padding: 18px;
            border-radius: 12px;
            background: #fffafd;
            border: 1px solid #f4e8ee;
            line-height: 1.7;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .detail-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 10px;
            background: #fff1f6;
            border: 1px solid #f3d8e4;
            color: #c45f8c;
            text-decoration: none;
            font-weight: 600;
        }

        .detail-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 10px;
            background: #d96b9a;
            border: 1px solid #d96b9a;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 700px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<div class="admin-layout">

    <?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <div class="topbar-title">Chi tiết sự kiện</div>
                <div class="topbar-subtitle">Thông tin đầy đủ của sự kiện trong EventHub</div>
            </div>

            <div class="admin-profile">
                <div class="profile-avatar">A</div>
                <div>
                    <div class="profile-name">
                        <?= e($_SESSION['full_name'] ?? 'Admin') ?>
                    </div>
                    <span class="profile-role">Administrator</span>
                </div>
                <a href="../../auth/logout.php" class="logout-link">Đăng xuất</a>
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

    <h1><?= e($event['title']) ?></h1>
    <p>Thông tin chi tiết của sự kiện #<?= (int) $event['id'] ?>.</p>

</section>

            <section class="detail-card">
                <div>
                    <span class="detail-label">Trạng thái sự kiện</span>
                    <span class="status-badge status-<?= e($event['status']) ?>">
                        <?= e(statusLabel($event['status'])) ?>
                    </span>
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Danh mục sự kiện</span>
                        <div class="detail-value">
                            <?= !empty($event['category_name'])
                                ? e($event['category_name'])
                                : 'Chưa chọn danh mục' ?>
                        </div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Địa điểm tổ chức</span>
                        <div class="detail-value">
                            <?= !empty($event['venue_name'])
                                ? e($event['venue_name'])
                                : 'Chưa chọn địa điểm' ?>
                        </div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Địa chỉ</span>
                        <div class="detail-value">
                            <?= !empty($event['venue_address'])
                                ? e($event['venue_address'])
                                : 'Chưa cập nhật địa chỉ' ?>
                        </div>
                    </div>

<div class="detail-item">
    <span class="detail-label">Sức chứa địa điểm</span>
    <div class="detail-value">
        <?= $event['venue_capacity'] !== null
            ? number_format((int) $event['venue_capacity']) . ' người'
            : 'Chưa cập nhật' ?>
    </div>
</div>

<div class="detail-item">
    <span class="detail-label">Tình trạng địa điểm</span>
    <div class="detail-value">
        <?= $event['venue_status'] === 'available'
            ? 'Có thể sử dụng'
            : ($event['venue_status'] === 'unavailable'
                ? 'Không khả dụng'
                : 'Chưa cập nhật') ?>
    </div>
</div>

                    <div class="detail-item">
                        <span class="detail-label">Người phụ trách</span>
                        <div class="detail-value">
                            <?= !empty($event['organizer_name'])
                                ? e($event['organizer_name'])
                                : 'Organizer #' . (int) $event['organizer_id'] ?>
                        </div>
                    </div>

<div class="detail-item">
    <span class="detail-label">Email người phụ trách</span>
    <div class="detail-value">
        <?= !empty($event['organizer_email'])
            ? e($event['organizer_email'])
            : 'Chưa cập nhật' ?>
    </div>
</div>

<div class="detail-item">
    <span class="detail-label">Số điện thoại</span>
    <div class="detail-value">
        <?= !empty($event['organizer_phone'])
            ? e($event['organizer_phone'])
            : 'Chưa cập nhật' ?>
    </div>
</div>

                    <div class="detail-item">
                        <span class="detail-label">Sức chứa tối đa</span>
                        <div class="detail-value">
                            <?= number_format((int) $event['capacity']) ?> người
                        </div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Thời gian bắt đầu</span>
                        <div class="detail-value"><?= e(formatEventDate($event['start_at'])) ?></div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Thời gian kết thúc</span>
                        <div class="detail-value"><?= e(formatEventDate($event['end_at'])) ?></div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Organizer ID</span>
                        <div class="detail-value">#<?= (int) $event['organizer_id'] ?></div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Ngày tạo</span>
                        <div class="detail-value"><?= e(formatEventDate($event['created_at'])) ?></div>
                    </div>
                </div>

                <div class="detail-description">
                    <span class="detail-label">Mô tả sự kiện</span>
                    <div class="detail-value" style="font-weight: 400;">
                        <?= $event['description'] !== null && trim($event['description']) !== ''
                            ? nl2br(e($event['description']))
                            : 'Sự kiện chưa có mô tả.' ?>
                    </div>
                </div>

                <div class="detail-actions">
                    <a href="index.php" class="detail-back">← Quay lại danh sách</a>
                    <a href="edit.php?id=<?= (int) $event['id'] ?>" class="detail-edit">
                        Chỉnh sửa sự kiện
                    </a>
                </div>
                        </section>

<!-- Biểu mẫu thêm diễn giả -->
<section class="detail-card" style="margin-top: 22px;">

    <h2 style="color: #65445b; margin-top: 0;">
        Thêm diễn giả vào sự kiện
    </h2>

    <?php if (!empty($availableSpeakers)): ?>

        <form method="POST" action="add_speaker.php">

            <input
                type="hidden"
                name="event_id"
                value="<?= (int) $event['id'] ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <div class="detail-grid">

                <div class="detail-item">
                    <label class="detail-label" for="speaker_id">
                        Chọn diễn giả
                    </label>

                    <select
                        name="speaker_id"
                        id="speaker_id"
                        required
                        style="width: 100%; padding: 12px; border: 1px solid #f0dce7; border-radius: 10px;"
                    >
                        <option value="">-- Chọn diễn giả --</option>

                        <?php foreach ($availableSpeakers as $availableSpeaker): ?>
                            <option value="<?= (int) $availableSpeaker['id'] ?>">
                                <?= e($availableSpeaker['full_name']) ?>
                                <?= $availableSpeaker['organization']
                                    ? ' - ' . e($availableSpeaker['organization'])
                                    : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="detail-item">
                    <label class="detail-label" for="speaker_role">
                        Vai trò trong sự kiện
                    </label>

                    <input
                        type="text"
                        name="speaker_role"
                        id="speaker_role"
                        maxlength="100"
                        placeholder="Ví dụ: Diễn giả chính, Khách mời..."
                        style="width: 100%; padding: 12px; border: 1px solid #f0dce7; border-radius: 10px;"
                    >
                </div>

                <div class="detail-item">
                    <label class="detail-label" for="display_order">
                        Thứ tự hiển thị
                    </label>

                    <input
                        type="number"
                        name="display_order"
                        id="display_order"
                        min="1"
                        value="<?= $nextDisplayOrder ?>"
                        required
                        style="width: 100%; padding: 12px; border: 1px solid #f0dce7; border-radius: 10px;"
                    >
                </div>

            </div>

            <button
                type="submit"
                class="detail-edit"
                style="margin-top: 18px; cursor: pointer;"
            >
                + Thêm diễn giả
            </button>

        </form>

    <?php else: ?>

        <p style="color: #927c8b;">
            Không còn diễn giả nào để thêm hoặc bạn chưa tạo hồ sơ diễn giả.
        </p>

        <a href="../speakers/create.php" class="detail-back">
            + Tạo hồ sơ diễn giả
        </a>

    <?php endif; ?>

</section>
            <!-- Danh sách diễn giả tham gia sự kiện -->
            <section class="detail-card" style="margin-top: 22px;">

                <h2 style="color: #65445b; margin-top: 0;">
                    Diễn giả tham gia (<?= count($speakers) ?>)
                </h2>

                <p style="color: #927c8b; margin-bottom: 20px;">
                    Danh sách diễn giả và vai trò của họ trong sự kiện.
                </p>

                <div class="table-responsive">
                    <table class="event-table">
                        <thead>
                            <tr>
                                <th>Thứ tự</th>
                                <th>Họ và tên</th>
                                <th>Tổ chức / Chức vụ</th>
                                <th>Email</th>
                                <th>Vai trò</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($speakers)): ?>

                                <?php foreach ($speakers as $speaker): ?>
                                    <tr>
                                        <td>
                                            <?= (int) $speaker['display_order'] ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= e($speaker['full_name']) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= e($speaker['organization'] ?: 'Chưa cập nhật') ?>
                                            <br>
                                            <small style="color: #927c8b;">
                                                <?= e($speaker['position'] ?: 'Chưa cập nhật chức vụ') ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?= e($speaker['email'] ?: 'Chưa cập nhật') ?>
                                        </td>

                                        <td>
                                            <?= e($speaker['speaker_role'] ?: 'Chưa cập nhật') ?>
                                        </td>

                                        
<td>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">

        <a
            href="../speakers/detail.php?id=<?= (int) $speaker['id'] ?>"
            class="detail-back"
        >
            Xem hồ sơ
        </a>

        <form
            method="POST"
            action="remove_speaker.php"
            onsubmit="return confirm('Bạn có chắc chắn muốn gỡ diễn giả này khỏi sự kiện không? Hồ sơ diễn giả sẽ được giữ nguyên.');"
        >
            <input
                type="hidden"
                name="event_id"
                value="<?= (int) $event['id'] ?>"
            >

            <input
                type="hidden"
                name="speaker_id"
                value="<?= (int) $speaker['id'] ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <button
                type="submit"
                style="
                    padding: 10px 16px;
                    border-radius: 10px;
                    background: #fff0f0;
                    border: 1px solid #f5cccc;
                    color: #d96b75;
                    font-weight: 600;
                    cursor: pointer;
                "
            >
                Gỡ khỏi sự kiện
            </button>
        </form>

    </div>
</td>

                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 25px; color: #927c8b;">
                                        Sự kiện chưa có diễn giả tham gia.
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
