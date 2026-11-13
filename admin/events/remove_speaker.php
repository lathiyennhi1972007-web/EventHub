
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

// Chuyển hướng kèm thông báo lỗi
function removeSpeakerError(string $message, ?int $eventId): void
{
    $_SESSION['speaker_error'] = $message;

    if ($eventId && $eventId > 0) {
        header('Location: detail.php?id=' . $eventId);
    } else {
        header('Location: index.php');
    }

    exit;
}

// Kiểm tra mã sự kiện và mã diễn giả
if (!$eventId || $eventId < 1 || !$speakerId || $speakerId < 1) {
    removeSpeakerError('Thông tin diễn giả hoặc sự kiện không hợp lệ.', $eventId);
}

// Kiểm tra CSRF
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    removeSpeakerError('Yêu cầu không hợp lệ. Vui lòng thử lại.', $eventId);
}

try {
    // Kiểm tra liên kết diễn giả với sự kiện
    $stmt = $pdo->prepare("
        SELECT id
        FROM event_speakers
        WHERE event_id = ? AND speaker_id = ?
        LIMIT 1
    ");
    $stmt->execute([$eventId, $speakerId]);

    if (!$stmt->fetchColumn()) {
        removeSpeakerError('Diễn giả không thuộc sự kiện này.', $eventId);
    }

    // Chỉ xóa liên kết, không xóa hồ sơ diễn giả
    $stmt = $pdo->prepare("
        DELETE FROM event_speakers
        WHERE event_id = ? AND speaker_id = ?
    ");
    $stmt->execute([$eventId, $speakerId]);

    $_SESSION['speaker_success'] = 'Đã gỡ diễn giả khỏi sự kiện thành công. Hồ sơ diễn giả vẫn được giữ nguyên.';

    header('Location: detail.php?id=' . $eventId);
    exit;

} catch (PDOException $e) {
    error_log('Lỗi gỡ diễn giả khỏi sự kiện: ' . $e->getMessage());

    removeSpeakerError(
        'Không thể gỡ diễn giả khỏi sự kiện. Vui lòng thử lại.',
        $eventId
    );
}
