
<?php

require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

function redirectWithError($message)
{
    $_SESSION['event_error'] = $message;
    header('Location: create.php');
    exit;
}

// Chi chap nhan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Lay du lieu tu bieu mau
$title = trim($_POST['title'] ?? '');
$categoryId = $_POST['category_id'] ?? '';
$venueId = $_POST['venue_id'] ?? '';
$description = trim($_POST['description'] ?? '');
$startAt = $_POST['start_at'] ?? '';
$endAt = $_POST['end_at'] ?? '';
$capacity = filter_var(
    $_POST['capacity'] ?? null,
    FILTER_VALIDATE_INT
);
$status = $_POST['status'] ?? 'draft';

// Kiem tra du lieu bat buoc
if ($title === '' || $startAt === '' || $endAt === '') {
    redirectWithError('Vui lòng nhập đầy đủ thông tin bắt buộc.');
}

if ($capacity === false || $capacity < 1) {
    redirectWithError('Sức chứa phải là số nguyên lớn hơn 0.');
}

// Kiem tra dinh dang thoi gian
$start = DateTime::createFromFormat('Y-m-d\TH:i', $startAt);
$end = DateTime::createFromFormat('Y-m-d\TH:i', $endAt);

if (!$start || !$end) {
    redirectWithError('Thời gian không hợp lệ.');
}

if ($end <= $start) {
    redirectWithError('Thời gian kết thúc phải sau thời gian bắt đầu.');
}

// Chuyen thoi gian sang dinh dang MySQL
$startAt = $start->format('Y-m-d H:i:s');
$endAt = $end->format('Y-m-d H:i:s');

// Kiem tra trang thai
if (!in_array($status, ['draft', 'published'], true)) {
    redirectWithError('Trạng thái sự kiện không hợp lệ.');
}

// Kiem tra danh muc
if ($categoryId !== '') {
    $categoryStmt = $pdo->prepare(
        "SELECT id FROM event_categories WHERE id = ?"
    );
    $categoryStmt->execute([$categoryId]);

    if (!$categoryStmt->fetch()) {
        redirectWithError('Danh mục sự kiện không tồn tại.');
    }
} else {
    $categoryId = null;
}

// Kiem tra dia diem va suc chua
if ($venueId !== '') {
    $venueStmt = $pdo->prepare("
        SELECT id, capacity, status
        FROM venues
        WHERE id = ?
    ");
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch(PDO::FETCH_ASSOC);

    if (!$venue || $venue['status'] !== 'available') {
        redirectWithError('Địa điểm không tồn tại hoặc không khả dụng.');
    }

    if (
        $venue['capacity'] !== null &&
        $capacity > (int) $venue['capacity']
    ) {
        redirectWithError(
            'Sức chứa sự kiện vượt quá sức chứa của địa điểm.'
        );
    }
} else {
    $venueId = null;
}

// Lay ID tai khoan dang dang nhap
$organizerId = $_SESSION['user_id'] ?? null;

if (!$organizerId) {
    redirectWithError('Không xác định được tài khoản đăng nhập.');
}

// Luu su kien vao database
try {
    $sql = "
        INSERT INTO events (
            organizer_id,
            category_id,
            venue_id,
            title,
            description,
            start_at,
            end_at,
            capacity,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $organizerId,
        $categoryId,
        $venueId,
        $title,
        $description !== '' ? $description : null,
        $startAt,
        $endAt,
        $capacity,
        $status
    ]);

    $_SESSION['event_success'] = 'Tạo sự kiện thành công!';

    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    error_log('EventHub store event error: ' . $e->getMessage());
    redirectWithError('Không thể lưu sự kiện. Vui lòng kiểm tra dữ liệu và thử lại.');
}