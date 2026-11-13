
<?php
require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Lay ID dien gia
$speakerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$speakerId || $speakerId <= 0) {
    header('Location: index.php');
    exit;
}

// Lay thong tin dien gia
$stmt = $pdo->prepare("
    SELECT *
    FROM speakers
    WHERE id = ?
");
$stmt->execute([$speakerId]);
$speaker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$speaker) {
    http_response_code(404);
    exit('Khong tim thay dien gia.');
}

// Lay danh sach su kien dien gia tham gia
$eventStmt = $pdo->prepare("
    SELECT
        e.id,
        e.title,
        e.start_at,
        e.end_at,
        e.status,
        es.speaker_role,
        es.display_order
    FROM event_speakers es
    INNER JOIN events e ON e.id = es.event_id
    WHERE es.speaker_id = ?
    ORDER BY e.start_at DESC, es.display_order ASC
");
$eventStmt->execute([$speakerId]);
$events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

// Duong dan anh dai dien
$avatarUrl = !empty($speaker['avatar'])
    ? '../../' . ltrim($speaker['avatar'], '/')
    : null;

function speakerStatusLabel($status): string
{
    $labels = [
        'draft' => 'Bản nháp',
        'published' => 'Đã công bố',
        'ongoing' => 'Đang diễn ra',
        'completed' => 'Đã hoàn thành',
        'cancelled' => 'Đã hủy'
    ];

    return $labels[$status] ?? $status;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết diễn giả | EventHub</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/events.css">

    <style>
        .speaker-detail-card {
            background: #fff;
            border: 1px solid #f4dce9;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 22px;
            box-shadow: 0 8px 25px rgba(205, 113, 157, 0.07);
        }

        .speaker-profile {
            display: flex;
            align-items: center;
            gap: 25px;
            padding: 25px;
            border-radius: 17px;
            background: linear-gradient(120deg, #fff0f7, #f5f0ff);
            margin-bottom: 25px;
        }

        .speaker-profile-avatar {
            width: 125px;
            height: 125px;
            flex-shrink: 0;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            background: #fce5f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c96f9e;
            font-size: 42px;
            font-weight: 700;
            box-shadow: 0 5px 18px rgba(205, 113, 157, .15);
        }

        .speaker-profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .speaker-profile h1 {
            margin: 0 0 8px;
            color: #65445b;
            font-size: 25px;
        }

        .speaker-profile p {
            margin: 5px 0;
            color: #927c8b;
        }

        .speaker-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .speaker-detail-item {
            padding: 18px;
            border: 1px solid #f3e4ed;
            border-radius: 14px;
            background: #fffafd;
        }

        .speaker-detail-item.full-width {
            grid-column: 1 / -1;
        }

        .speaker-detail-label {
            color: #a28b9a;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .speaker-detail-value {
            color: #65445b;
            font-weight: 600;
            overflow-wrap: anywhere;
            white-space: pre-line;
        }

        .speaker-detail-heading {
            color: #65445b;
            font-size: 19px;
            margin: 0 0 20px;
        }

        .speaker-event-table {
            width: 100%;
            border-collapse: collapse;
        }

        .speaker-event-table th {
            background: #fff1f7;
            color: #a75c80;
            padding: 14px;
            text-align: left;
        }

        .speaker-event-table td {
            padding: 15px 14px;
            border-bottom: 1px solid #f5e9ef;
            color: #65445b;
        }

        .speaker-event-title {
            font-weight: 600;
            color: #bd6491;
        }

        .speaker-event-empty {
            padding: 25px;
            text-align: center;
            color: #a28b9a;
        }

        .speaker-detail-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .speaker-back-button {
            display: inline-flex;
            align-items: center;
            padding: 11px 17px;
            border-radius: 11px;
            background: #fff0f6;
            border: 1px solid #f4d7e5;
            color: #bd6491;
            text-decoration: none;
        }

        @media (max-width: 650px) {
            .speaker-profile {
                flex-direction: column;
                align-items: flex-start;
            }

            .speaker-detail-grid {
                grid-template-columns: 1fr;
            }

            .speaker-detail-item.full-width {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>
<div class="admin-layout">

    <?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-content">

            <section class="page-heading">
                <h1>Chi tiết diễn giả</h1>
                <p>Thông tin hồ sơ và các sự kiện có sự tham gia của diễn giả.</p>
            </section>

            <div class="speaker-detail-actions">
                <a href="index.php" class="speaker-back-button">
                    ← Quay lại danh sách
                </a>
            </div>

            <section class="speaker-detail-card">

                <div class="speaker-profile">

                    <div class="speaker-profile-avatar">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= e($avatarUrl) ?>" alt="Ảnh diễn giả">
                        <?php else: ?>
                            <?= e(mb_substr($speaker['full_name'], 0, 1, 'UTF-8')) ?>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h1><?= e($speaker['full_name']) ?></h1>

                        <p>
                            <?= e($speaker['position'] ?: 'Chưa cập nhật chức vụ') ?>
                        </p>

                        <p>
                            <?= e($speaker['organization'] ?: 'Chưa cập nhật tổ chức') ?>
                        </p>
                    </div>

                </div>

                <h2 class="speaker-detail-heading">Thông tin hồ sơ</h2>

                <div class="speaker-detail-grid">

                    <div class="speaker-detail-item">
                        <div class="speaker-detail-label">Mã diễn giả</div>
                        <div class="speaker-detail-value">
                            #<?= (int) $speaker['id'] ?>
                        </div>
                    </div>

                    <div class="speaker-detail-item">
                        <div class="speaker-detail-label">Họ và tên</div>
                        <div class="speaker-detail-value">
                            <?= e($speaker['full_name']) ?>
                        </div>
                    </div>

                    <div class="speaker-detail-item">
                        <div class="speaker-detail-label">Email</div>
                        <div class="speaker-detail-value">
                            <?= e($speaker['email'] ?: 'Chưa cập nhật') ?>
                        </div>
                    </div>

                    <div class="speaker-detail-item">
                        <div class="speaker-detail-label">Số điện thoại</div>
                        <div class="speaker-detail-value">
                            <?= e($speaker['phone'] ?: 'Chưa cập nhật') ?>
                        </div>
                    </div>

                    <div class="speaker-detail-item">
                        <div class="speaker-detail-label">Tổ chức / Đơn vị</div>
                        <div class="speaker-detail-value">
                            <?= e($speaker['organization'] ?: 'Chưa cập nhật') ?>
                        </div>
                    </div>

                    <div class="speaker-detail-item">
                        <div class="speaker-detail-label">Chức vụ</div>
                        <div class="speaker-detail-value">
                            <?= e($speaker['position'] ?: 'Chưa cập nhật') ?>
                        </div>
                    </div>

                    <div class="speaker-detail-item full-width">
                        <div class="speaker-detail-label">Tiểu sử / Giới thiệu</div>
                        <div class="speaker-detail-value">
                            <?= e($speaker['biography'] ?: 'Chưa có thông tin giới thiệu.') ?>
                        </div>
                    </div>

                    <div class="speaker-detail-item">
                        <div class="speaker-detail-label">Ngày tạo hồ sơ</div>
                        <div class="speaker-detail-value">
                            <?= date('d/m/Y H:i', strtotime($speaker['created_at'])) ?>
                        </div>
                    </div>

                </div>

            </section>

            <section class="speaker-detail-card">

                <h2 class="speaker-detail-heading">
                    Sự kiện tham gia (<?= count($events) ?>)
                </h2>

                <div class="table-responsive">
                    <table class="speaker-event-table">
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Tên sự kiện</th>
                                <th>Thời gian</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if ($events): ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>#<?= (int) $event['id'] ?></td>

                                    <td class="speaker-event-title">
                                        <?= e($event['title']) ?>
                                    </td>

                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($event['start_at'])) ?>
                                    </td>

                                    <td>
                                        <?= e($event['speaker_role'] ?: 'Chưa cập nhật') ?>
                                    </td>

                                    <td>
                                        <?= e(speakerStatusLabel($event['status'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="speaker-event-empty">
                                    Diễn giả chưa được gắn với sự kiện nào.
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
