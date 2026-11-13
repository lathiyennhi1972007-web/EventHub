
<?php

require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Chi chap nhan yeu cau POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ham chuyen huong kem thong bao loi
function speakerUpdateError(string $message, int $id = 0): void
{
    $_SESSION['speaker_error'] = $message;

    if ($id > 0) {
        header('Location: edit.php?id=' . $id);
    } else {
        header('Location: index.php');
    }
    exit;
}

// Kiem tra ID dien gia
$speakerId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$speakerId || $speakerId < 1) {
    speakerUpdateError('Mã diễn giả không hợp lệ.');
}

// Kiem tra CSRF token
$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    speakerUpdateError(
        'Yêu cầu không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang.',
        $speakerId
    );
}

// Lay va kiem tra du lieu form
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$organization = trim($_POST['organization'] ?? '');
$position = trim($_POST['position'] ?? '');
$biography = trim($_POST['biography'] ?? '');

if ($fullName === '') {
    speakerUpdateError('Vui lòng nhập họ và tên diễn giả.', $speakerId);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    speakerUpdateError('Địa chỉ email không hợp lệ.', $speakerId);
}

// Lay thong tin dien gia hien tai
$stmt = $pdo->prepare("
    SELECT id, avatar
    FROM speakers
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$speakerId]);

$currentSpeaker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentSpeaker) {
    speakerUpdateError('Diễn giả không tồn tại hoặc đã bị xóa.');
}

$oldAvatar = $currentSpeaker['avatar'] ?? null;
$newAvatar = $oldAvatar;
$newAvatarPath = null;

// Xu ly anh dai dien neu nguoi dung chon anh moi
if (
    isset($_FILES['avatar']) &&
    $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE
) {
    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        speakerUpdateError('Tải ảnh lên không thành công.', $speakerId);
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        speakerUpdateError('Ảnh đại diện không được vượt quá 2MB.', $speakerId);
    }

    // Kiem tra MIME thuc te cua file
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mimeType])) {
        speakerUpdateError(
            'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.',
            $speakerId
        );
    }

    // Kiem tra file co phai anh hop le
    if (getimagesize($file['tmp_name']) === false) {
        speakerUpdateError('File tải lên không phải ảnh hợp lệ.', $speakerId);
    }

    $uploadDirectory = __DIR__ . '/../../uploads/speakers/';

    if (
        !is_dir($uploadDirectory) ||
        !is_writable($uploadDirectory)
    ) {
        error_log('Speaker upload directory is missing or not writable.');
        speakerUpdateError(
            'Thư mục lưu ảnh chưa sẵn sàng. Vui lòng thử lại.',
            $speakerId
        );
    }

    $fileName = bin2hex(random_bytes(16))
        . '.' . $allowedTypes[$mimeType];

    $newAvatarPath = $uploadDirectory . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $newAvatarPath)) {
        speakerUpdateError('Không thể lưu ảnh đại diện.', $speakerId);
    }

    // Duong dan luu trong database
    $newAvatar = 'uploads/speakers/' . $fileName;
}

// Cap nhat thong tin dien gia
try {
    $updateStmt = $pdo->prepare("
        UPDATE speakers
        SET
            full_name = ?,
            email = ?,
            phone = ?,
            organization = ?,
            position = ?,
            biography = ?,
            avatar = ?
        WHERE id = ?
    ");

    $updateStmt->execute([
        $fullName,
        $email !== '' ? $email : null,
        $phone !== '' ? $phone : null,
        $organization !== '' ? $organization : null,
        $position !== '' ? $position : null,
        $biography !== '' ? $biography : null,
        $newAvatar,
        $speakerId
    ]);

    // Chi xoa anh cu sau khi cap nhat database thanh cong
    if (
        $newAvatarPath !== null &&
        !empty($oldAvatar) &&
        str_starts_with($oldAvatar, 'uploads/speakers/')
    ) {
        $oldAvatarPath = __DIR__ . '/../../' . $oldAvatar;

        if (is_file($oldAvatarPath)) {
            @unlink($oldAvatarPath);
        }
    }

    $_SESSION['speaker_success'] = 'Cập nhật thông tin diễn giả thành công.';

    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    // Neu cap nhat that bai, xoa file anh moi vua tai len
    if ($newAvatarPath !== null && is_file($newAvatarPath)) {
        @unlink($newAvatarPath);
    }

    error_log($e->getMessage());

    speakerUpdateError(
        'Không thể cập nhật thông tin. Vui lòng kiểm tra lại dữ liệu.',
        $speakerId
    );
}
