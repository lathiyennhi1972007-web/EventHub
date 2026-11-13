
<?php

require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Lay danh sach danh muc
$categoryStmt = $pdo->query("
    SELECT id, name
    FROM event_categories
    ORDER BY name ASC
");

$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Lay cac dia diem dang kha dung
$venueStmt = $pdo->query("
    SELECT id, venue_name, address, capacity
    FROM venues
    WHERE status = 'available'
    ORDER BY venue_name ASC
");

$venues = $venueStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Thêm sự kiện | EventHub</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/events.css">
</head>

<body>
<div class="admin-layout">

    <?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-content">

<section class="page-heading">
    <h1>Thêm sự kiện mới</h1>
    <p>Nhập thông tin để tạo sự kiện trên hệ thống EventHub.</p>

    <?php if (!empty($_SESSION['event_error'])): ?>
        <div class="event-message error-message">
            <?= htmlspecialchars($_SESSION['event_error']) ?>
        </div>
        <?php unset($_SESSION['event_error']); ?>
    <?php endif; ?>
</section>

    <section class="event-form-card">

<form action="store.php" method="POST">
            <div class="form-section">
                <h2>Thông tin cơ bản</h2>

                <div class="form-group">
                    <label for="title">Tên sự kiện *</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="200"
                        placeholder="Ví dụ: AI Workshop 2026"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category_id">Danh mục sự kiện</label>

                    <select id="category_id" name="category_id">
                        <option value="">-- Chọn danh mục --</option>

                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
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
                    ></textarea>
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
                            >
                                <?= htmlspecialchars($venue['venue_name']) ?>
                                - <?= htmlspecialchars($venue['address']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <?php if (empty($venues)): ?>
                        <small>Hiện chưa có địa điểm khả dụng.</small>
                    <?php endif; ?>
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
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="end_at">Thời gian kết thúc *</label>
                        <input
                            type="datetime-local"
                            id="end_at"
                            name="end_at"
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
                            placeholder="Ví dụ: 200"
                            required
                        >
                        <small id="capacityHint"></small>
                    </div>

                </div>
            </div>

            <div class="form-section">
                <h2>Trạng thái sự kiện</h2>

                <div class="form-group">
                    <label for="status">Trạng thái</label>

                    <select id="status" name="status">
                        <option value="draft">Bản nháp</option>
                        <option value="published">Đã công bố</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">

                <a href="index.php" class="reset-button">
                    Quay lại
                </a>

                <button type="submit" class="search-button">
                    Lưu sự kiện
                </button>

            </div>

        </form>

    </section>
        </div>
    </main>

</div>
<script>
const venueSelect = document.getElementById('venue_id');
const capacityInput = document.getElementById('capacity');
const capacityHint = document.getElementById('capacityHint');

venueSelect.addEventListener('change', function () {
    const option = this.options[this.selectedIndex];
    const venueCapacity = option.dataset.capacity;

    if (venueCapacity !== undefined && venueCapacity !== '') {
        capacityHint.textContent =
            'Sức chứa tối đa của địa điểm: ' + venueCapacity + ' người.';
    } else {
        capacityHint.textContent =
            'Địa điểm này chưa thiết lập giới hạn sức chứa.';
    }
});
</script>

</body>
</html>
