<?php

require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$eventId || $eventId < 1) {
    header('Location: index.php');
    exit;
}

// Lay thong tin su kien can chinh sua
$eventStmt = $pdo->prepare("\n    SELECT id, organizer_id, category_id, venue_id, title, description,\n           start_at, end_at, capacity, status\n    FROM events\n    WHERE id = ?\n    LIMIT 1\n");
$eventStmt->execute([$eventId]);
$event = $eventStmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    http_response_code(404);
    exit('Không tìm thấy sự kiện cần chỉnh sửa.');
}

// Lay danh sach danh muc
$categoryStmt = $pdo->query("\n    SELECT id, name\n    FROM event_categories\n    ORDER BY name ASC\n");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Lay dia diem kha dung va dia diem dang duoc gan cho su kien nay
$venueStmt = $pdo->prepare("\n    SELECT id, venue_name, address, capacity, status\n    FROM venues\n    WHERE status = 'available' OR id = ?\n    ORDER BY venue_name ASC\n");
$venueStmt->execute([$event['venue_id'] ?? 0]);
$venues = $venueStmt->fetchAll(PDO::FETCH_ASSOC);

$startValue = date('Y-m-d\\TH:i', strtotime($event['start_at']));
$endValue = date('Y-m-d\\TH:i', strtotime($event['end_at']));

$allowedStatuses = [
    'draft' => 'Bản nháp',
    'published' => 'Đã công bố',
    'ongoing' => 'Đang diễn ra',
    'completed' => 'Đã hoàn thành',
    'cancelled' => 'Đã hủy',
];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa sự kiện | EventHub</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/events.css">
</head>
<body>
<div class="admin-layout">

    <?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-content">

            <section class="page-heading">
                <h1>Chỉnh sửa sự kiện</h1>
                <p>Cập nhật thông tin cho sự kiện #<?= (int) $event['id'] ?>.</p>

                <?php if (!empty($_SESSION['event_error'])): ?>
                    <div class="event-message error-message">
                        <?= e($_SESSION['event_error']) ?>
                    </div>
                    <?php unset($_SESSION['event_error']); ?>
                <?php endif; ?>
            </section>

            <section class="event-form-card">
                <form action="update.php" method="POST">
                    <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">

                    <div class="form-section">
                        <h2>Thông tin cơ bản</h2>

                        <div class="form-group">
                            <label for="title">Tên sự kiện *</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                maxlength="200"
                                value="<?= e($event['title']) ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="category_id">Danh mục sự kiện</label>
                            <select id="category_id" name="category_id">
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option
                                        value="<?= (int) $category['id'] ?>"
                                        <?= (string) ($event['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>
                                    >
                                        <?= e($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Mô tả sự kiện</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Giới thiệu nội dung và mục tiêu sự kiện..."
                            ><?= e($event['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Địa điểm tổ chức</h2>
                        <div class="form-group">
                            <label for="venue_id">Chọn địa điểm</label>
                            <select id="venue_id" name="venue_id">
                                <option value="">-- Chọn địa điểm --</option>
                                <?php foreach ($venues as $venue): ?>
                                    <option
                                        value="<?= (int) $venue['id'] ?>"
                                        data-capacity="<?= $venue['capacity'] === null ? '' : (int) $venue['capacity'] ?>"
                                        <?= (string) ($event['venue_id'] ?? '') === (string) $venue['id'] ? 'selected' : '' ?>
                                    >
                                        <?= e($venue['venue_name']) ?> - <?= e($venue['address']) ?>
                                        <?= $venue['status'] !== 'available' ? ' (Hiện không khả dụng)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($venues)): ?>
                                <small>Hiện chưa có địa điểm khả dụng.</small>
                            <?php endif; ?>
                            <small id="capacityHint"></small>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Thời gian và sức chứa</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="start_at">Thời gian bắt đầu *</label>
                                <input
                                    type="datetime-local"
                                    id="start_at"
                                    name="start_at"
                                    value="<?= e($startValue) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="end_at">Thời gian kết thúc *</label>
                                <input
                                    type="datetime-local"
                                    id="end_at"
                                    name="end_at"
                                    value="<?= e($endValue) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="capacity">Sức chứa tối đa *</label>
                                <input
                                    type="number"
                                    id="capacity"
                                    name="capacity"
                                    min="1"
                                    value="<?= (int) $event['capacity'] ?>"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Trạng thái sự kiện</h2>
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select id="status" name="status">
                                <?php foreach ($allowedStatuses as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $event['status'] === $value ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="reset-button">Hủy</a>
                        <button type="submit" class="search-button">Lưu thay đổi</button>
                    </div>
                </form>
            </section>

        </div>
    </main>
</div>

<script>
const venueSelect = document.getElementById('venue_id');
const capacityHint = document.getElementById('capacityHint');

function updateVenueHint() {
    const option = venueSelect.options[venueSelect.selectedIndex];
    const venueCapacity = option ? option.dataset.capacity : '';

    if (venueCapacity) {
        capacityHint.textContent = 'Sức chứa tối đa của địa điểm: ' + venueCapacity + ' người.';
    } else if (venueSelect.value !== '') {
        capacityHint.textContent = 'Địa điểm này chưa thiết lập giới hạn sức chứa.';
    } else {
        capacityHint.textContent = '';
    }
}

venueSelect.addEventListener('change', updateVenueHint);
updateVenueHint();
</script>
</body>
</html>
