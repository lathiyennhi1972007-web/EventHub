
<?php
require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Chỉ chấp nhận yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Lấy dữ liệu từ biểu mẫu
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$speakerId = filter_input(INPUT_POST, 'speaker_id', FILTER_VALIDATE_INT);
$speakerRole = trim($_POST['speaker_role'] ?? '');
$displayOrder = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT);

// Hàm chuyển hướng kèm thông báo lỗi
function addSpeakerError(string $message, ?int $eventId): void
{
    $_SESSION['speaker_error'] = $message;

    if ($eventId && $eventId > 0) {
        header('Location: detail.php?id=' . $eventId);
    } else {
        header('Location: index.php');
    }

    exit;
}

// Kiểm tra mã sự kiện
if (!$eventId || $eventId < 1) {
    addSpeakerError('Mã sự kiện không hợp lệ.', null);
}

// Kiểm tra CSRF
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    addSpeakerError('Yêu cầu không hợp lệ. Vui lòng thử lại.', $eventId);
}

// Kiểm tra mã diễn giả
if (!$speakerId || $speakerId < 1) {
    addSpeakerError('Vui lòng chọn diễn giả.', $eventId);
}

// Kiểm tra vai trò
if (mb_strlen($speakerRole, 'UTF-8') > 100) {
    addSpeakerError('Vai trò không được vượt quá 100 ký tự.', $eventId);
}

// Kiểm tra thứ tự hiển thị
if (
    $displayOrder === false ||
    $displayOrder < 1 ||
    $displayOrder > 65535
) {
    addSpeakerError('Thứ tự hiển thị phải từ 1 đến 65.535.', $eventId);
}

try {
    // Kiểm tra sự kiện có tồn tại không
    $stmt = $pdo->prepare(
        'SELECT id FROM events WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$eventId]);

    if (!$stmt->fetchColumn()) {
        addSpeakerError('Không tìm thấy sự kiện.', $eventId);
    }

    // Kiểm tra diễn giả có tồn tại không
    $stmt = $pdo->prepare(
        'SELECT id FROM speakers WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$speakerId]);

    if (!$stmt->fetchColumn()) {
        addSpeakerError('Không tìm thấy diễn giả.', $eventId);
    }

    // Kiểm tra diễn giả đã được gắn vào sự kiện chưa
    $stmt = $pdo->prepare("
        SELECT id
        FROM event_speakers
        WHERE event_id = ? AND speaker_id = ?
        LIMIT 1
    ");
    $stmt->execute([$eventId, $speakerId]);

    if ($stmt->fetchColumn()) {
        addSpeakerError(
            'Diễn giả này đã được gắn vào sự kiện.',
            $eventId
        );
    }

    // Lưu liên kết diễn giả và sự kiện
    $stmt = $pdo->prepare("
        INSERT INTO event_speakers (
            event_id,
            speaker_id,
            speaker_role,
            display_order
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $eventId,
        $speakerId,
        $speakerRole !== '' ? $speakerRole : null,
        $displayOrder
    ]);

    $_SESSION['speaker_success'] = 'Thêm diễn giả vào sự kiện thành công.';

    header('Location: detail.php?id=' . $eventId);
    exit;

} catch (PDOException $e) {
    error_log('Lỗi thêm diễn giả vào sự kiện: ' . $e->getMessage());

    addSpeakerError(
        'Không thể thêm diễn giả. Vui lòng kiểm tra dữ liệu và thử lại.',
        $eventId
    );
}
