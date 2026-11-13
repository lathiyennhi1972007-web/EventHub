
<?php

require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

function redirectWithError($message)
{
    $_SESSION['event_error'] = $message;
    header('Location: create.php');
    exit;
}

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Lấy dữ liệu từ biểu mẫu
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

// Kiểm tra dữ liệu bắt buộc
if ($title === '' || $startAt === '' || $endAt === '') {
    redirectWithError('Vui lòng nhập đầy đủ thông tin bắt buộc!');
}

if ($capacity === false || $capacity < 1) {
    redirectWithError('Sức chứa phải là số nguyên lớn hơn 0!');
}

// Kiểm tra thời gian
$start = DateTime::createFromFormat('Y-m-d\TH:i', $startAt);
$end = DateTime::createFromFormat('Y-m-d\TH:i', $endAt);

if (!$start || !$end) {
    redirectWithError('Thời gian không hợp lệ!');
}

if ($end <= $start) {
    redirectWithError('Thời gian kết thúc phải sau thời gian bắt đầu!');
}

$startAt = $start->format('Y-m-d H:i:s');
$endAt = $end->format('Y-m-d H:i:s');

// Kiểm tra trạng thái
if (!in_array($status, ['draft', 'published'], true)) {
    redirectWithError('Trạng thái sự kiện không hợp lệ!');
}

// Kiểm tra danh mục
if ($categoryId !== '') {
    $categoryStmt = $pdo->prepare(
        "SELECT id FROM event_categories WHERE id = ?"
    );
    $categoryStmt->execute([$categoryId]);

    if (!$categoryStmt->fetch()) {
        redirectWithError('Danh mục sự kiện không tồn tại!');
    }
} else {
    $categoryId = null;
}

// Kiểm tra địa điểm và sức chứa
if ($venueId !== '') {
    $venueStmt = $pdo->prepare("
        SELECT id, capacity, status
        FROM venues
        WHERE id = ?
    ");
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch(PDO::FETCH_ASSOC);

    if (!$venue || $venue['status'] !== 'available') {
        redirectWithError('Địa điểm không tồn tại hoặc không khả dụng!');
    }

    if (
        $venue['capacity'] !== null &&
        $capacity > (int) $venue['capacity']
    ) {
        redirectWithError(
            'Sức chứa sự kiện vượt quá sức chứa của địa điểm!'
        );
    }
} else {
    $venueId = null;
}

// Lấy ID tài khoản đang đăng nhập
$organizerId = $_SESSION['user_id'] ?? null;

if (!$organizerId) {
    redirectWithError('Không xác định được tài khoản đăng nhập!');
}

// Xử lý ảnh bìa sự kiện
$coverImage = null;
$uploadedFilePath = null;

if (
    isset($_FILES['cover_image']) &&
    $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE
) {
    $file = $_FILES['cover_image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        redirectWithError('Tải ảnh lên không thành công!');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        redirectWithError('Ảnh không được vượt quá 5 MB!');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        redirectWithError('Tệp tải lên không hợp lệ!');
    }

    // Kiểm tra MIME thực tế của tệp
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mimeType])) {
        redirectWithError('Chỉ chấp nhận ảnh JPG, PNG hoặc WebP!');
    }

    // Thư mục lưu ảnh
    $uploadDir = dirname(__DIR__, 2) . '/uploads/events/';

    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        redirectWithError('Thư mục lưu ảnh không khả dụng!');
    }

    // Tạo tên file ngẫu nhiên để tránh trùng lặp
    $fileName = bin2hex(random_bytes(16))
        . '.' . $allowedTypes[$mimeType];

    $destination = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        redirectWithError('Không thể lưu ảnh bìa sự kiện!');
    }

    $coverImage = 'uploads/events/' . $fileName;
    $uploadedFilePath = $destination;
}

// Lưu sự kiện vào database
try {
    $sql = "
        INSERT INTO events (
            organizer_id,
            category_id,
            venue_id,
            title,
            description,
            cover_image,
            start_at,
            end_at,
            capacity,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $organizerId,
        $categoryId,
        $venueId,
        $title,
        $description !== '' ? $description : null,
        $coverImage,
        $startAt,
        $endAt,
        $capacity,
        $status
    ]);

    $_SESSION['event_success'] = 'Tạo sự kiện thành công!';

    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    // Xóa ảnh nếu lưu database thất bại
    if ($uploadedFilePath && file_exists($uploadedFilePath)) {
        unlink($uploadedFilePath);
    }

    error_log('EventHub store event error: ' . $e->getMessage());

    redirectWithError(
        'Không thể lưu sự kiện. Vui lòng kiểm tra dữ liệu và thử lại!'
    );
}
