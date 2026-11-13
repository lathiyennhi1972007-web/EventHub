<?php

require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

function redirectWithError($eventId, $message)
{
    $_SESSION['event_error'] = $message;
    header('Location: edit.php?id=' . urlencode((string) $eventId));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$eventId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$eventId || $eventId < 1) {
    $_SESSION['event_error'] = 'Mã sự kiện không hợp lệ.';
    header('Location: index.php');
    exit;
}

// Kiem tra su kien co ton tai khong va lay dia diem hien tai
$eventStmt = $pdo->prepare("SELECT id, venue_id FROM events WHERE id = ? LIMIT 1");
$eventStmt->execute([$eventId]);
$currentEvent = $eventStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentEvent) {
    http_response_code(404);
    exit('Không tìm thấy sự kiện cần cập nhật.');
}

$title = trim($_POST['title'] ?? '');
$categoryId = $_POST['category_id'] ?? '';
$venueId = $_POST['venue_id'] ?? '';
$description = trim($_POST['description'] ?? '');
$startAt = $_POST['start_at'] ?? '';
$endAt = $_POST['end_at'] ?? '';
$capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? 'draft';

if ($title === '' || $startAt === '' || $endAt === '') {
    redirectWithError($eventId, 'Vui lòng nhập đầy đủ thông tin bắt buộc.');
}

if ($capacity === false || $capacity < 1) {
    redirectWithError($eventId, 'Sức chứa phải là số nguyên lớn hơn 0.');
}

$start = DateTime::createFromFormat('Y-m-d\\TH:i', $startAt);
$end = DateTime::createFromFormat('Y-m-d\\TH:i', $endAt);

if (!$start || !$end) {
    redirectWithError($eventId, 'Thời gian không hợp lệ.');
}

if ($end <= $start) {
    redirectWithError($eventId, 'Thời gian kết thúc phải sau thời gian bắt đầu.');
}

$startAt = $start->format('Y-m-d H:i:s');
$endAt = $end->format('Y-m-d H:i:s');

$allowedStatuses = ['draft', 'published', 'ongoing', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses, true)) {
    redirectWithError($eventId, 'Trạng thái sự kiện không hợp lệ.');
}

// Kiem tra danh muc neu co chon
if ($categoryId !== '') {
    $categoryId = filter_var($categoryId, FILTER_VALIDATE_INT);
    if (!$categoryId || $categoryId < 1) {
        redirectWithError($eventId, 'Danh mục sự kiện không hợp lệ.');
    }

    $categoryStmt = $pdo->prepare('SELECT id FROM event_categories WHERE id = ?');
    $categoryStmt->execute([$categoryId]);
    if (!$categoryStmt->fetch(PDO::FETCH_ASSOC)) {
        redirectWithError($eventId, 'Danh mục sự kiện không tồn tại.');
    }
} else {
    $categoryId = null;
}

// Kiem tra dia diem neu co chon
if ($venueId !== '') {
    $venueId = filter_var($venueId, FILTER_VALIDATE_INT);
    if (!$venueId || $venueId < 1) {
        redirectWithError($eventId, 'Địa điểm tổ chức không hợp lệ.');
    }

    $venueStmt = $pdo->prepare('SELECT id, capacity, status FROM venues WHERE id = ?');
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch(PDO::FETCH_ASSOC);

    $isCurrentVenue = (int) $venueId === (int) ($currentEvent['venue_id'] ?? 0);
    if (!$venue || ($venue['status'] !== 'available' && !$isCurrentVenue)) {
        redirectWithError($eventId, 'Địa điểm không tồn tại hoặc không khả dụng.');
    }

    if ($venue['capacity'] !== null && $capacity > (int) $venue['capacity']) {
        redirectWithError($eventId, 'Sức chứa sự kiện vượt quá sức chứa của địa điểm.');
    }
} else {
    $venueId = null;
}

try {
    // Khong thay doi organizer_id, giu nguyen nguoi tao su kien ban dau
    $sql = "
        UPDATE events
        SET category_id = ?,
            venue_id = ?,
            title = ?,
            description = ?,
            start_at = ?,
            end_at = ?,
            capacity = ?,
            status = ?
        WHERE id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $categoryId,
        $venueId,
        $title,
        $description !== '' ? $description : null,
        $startAt,
        $endAt,
        $capacity,
        $status,
        $eventId
    ]);

    $_SESSION['event_success'] = 'Cập nhật sự kiện thành công!';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    error_log('EventHub update event error: ' . $e->getMessage());
    redirectWithError($eventId, 'Không thể cập nhật sự kiện. Vui lòng kiểm tra dữ liệu và thử lại.');
}
