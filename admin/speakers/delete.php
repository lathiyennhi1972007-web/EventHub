
<?php
require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Chỉ chấp nhận yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Lấy mã diễn giả
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// Kiểm tra mã diễn giả
if (!$id || $id <= 0) {
    $_SESSION['speaker_error'] = 'Mã diễn giả không hợp lệ.';
    header('Location: index.php');
    exit;
}

// Kiểm tra CSRF
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['speaker_error'] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    header('Location: index.php');
    exit;
}

try {
    // Kiểm tra diễn giả có tồn tại hay không
    $stmt = $pdo->prepare(
        'SELECT id, avatar FROM speakers WHERE id = ?'
    );
    $stmt->execute([$id]);
    $speaker = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$speaker) {
        $_SESSION['speaker_error'] = 'Không tìm thấy diễn giả cần xóa.';
        header('Location: index.php');
        exit;
    }

    // Kiểm tra diễn giả đã được gắn với sự kiện chưa
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM event_speakers WHERE speaker_id = ?'
    );
    $stmt->execute([$id]);
    $eventCount = (int) $stmt->fetchColumn();

    if ($eventCount > 0) {
        $_SESSION['speaker_error'] =
            'Không thể xóa diễn giả vì diễn giả đang được gắn với sự kiện.';

        header('Location: index.php');
        exit;
    }

    // Xóa diễn giả trong cơ sở dữ liệu
    $stmt = $pdo->prepare(
        'DELETE FROM speakers WHERE id = ?'
    );
    $stmt->execute([$id]);

    // Chỉ xóa ảnh thuộc thư mục ảnh diễn giả
    $avatar = $speaker['avatar'] ?? '';

    if (
        is_string($avatar) &&
        strpos($avatar, 'uploads/speakers/') === 0
    ) {
        $avatarPath = __DIR__ . '/../../uploads/speakers/' . basename($avatar);

        if (is_file($avatarPath)) {
            unlink($avatarPath);
        }
    }

    $_SESSION['speaker_success'] = 'Xóa diễn giả thành công.';

} catch (PDOException $e) {
    error_log('Lỗi xóa diễn giả: ' . $e->getMessage());

    $_SESSION['speaker_error'] =
        'Không thể xóa diễn giả. Vui lòng thử lại sau.';
}

header('Location: index.php');
exit;
