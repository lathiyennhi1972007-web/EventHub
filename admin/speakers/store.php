
<?php

require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Chi chap nhan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Kiem tra CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['speaker_error'] = 'Yeu cau khong hop le. Vui long thu lai.';
    header('Location: create.php');
    exit;
}

// Lay va lam sach du lieu
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$organization = trim($_POST['organization'] ?? '');
$position = trim($_POST['position'] ?? '');
$biography = trim($_POST['biography'] ?? '');

// Kiem tra ho ten
if ($fullName === '') {
    $_SESSION['speaker_error'] = 'Vui long nhap ho va ten dien gia.';
    header('Location: create.php');
    exit;
}

// Kiem tra email neu co nhap
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['speaker_error'] = 'Email khong dung dinh dang.';
    header('Location: create.php');
    exit;
}

// Kiem tra thu muc upload
$uploadDir = __DIR__ . '/../../uploads/speakers/';

if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    $_SESSION['speaker_error'] = 'Thu muc luu anh khong ton tai hoac khong co quyen ghi.';
    header('Location: create.php');
    exit;
}

$avatarPath = null;
$uploadedFilePath = null;

try {

    // Xu ly anh dai dien neu nguoi dung chon anh
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Khong the tai anh len. Vui long thu lai.');
        }

        // Gioi han dung luong 2 MB
        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            throw new RuntimeException('Anh dai dien khong duoc vuot qua 2 MB.');
        }

        // Kiem tra MIME thuc te cua tep
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['avatar']['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedTypes[$mimeType])) {
            throw new RuntimeException('Chi chap nhan anh JPG, PNG hoac WEBP.');
        }

        // Tao ten tep ngau nhien, khong dung ten goc
        $fileName = 'speaker_' . bin2hex(random_bytes(16))
                  . '.' . $allowedTypes[$mimeType];

        $destination = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
            throw new RuntimeException('Khong the luu anh dai dien.');
        }

        $uploadedFilePath = $destination;
        $avatarPath = 'uploads/speakers/' . $fileName;
    }

    // Them dien gia vao database
    $sql = "
        INSERT INTO speakers (
            full_name,
            email,
            phone,
            organization,
            position,
            biography,
            avatar
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $fullName,
        $email !== '' ? $email : null,
        $phone !== '' ? $phone : null,
        $organization !== '' ? $organization : null,
        $position !== '' ? $position : null,
        $biography !== '' ? $biography : null,
        $avatarPath
    ]);

    // Tao thong bao thanh cong
    $_SESSION['speaker_success'] = 'Them dien gia thanh cong!';

    // Doi CSRF Token sau khi xu ly thanh cong
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: index.php');
    exit;

} catch (Throwable $e) {

    // Neu da tai anh len nhung luu database that bai,
    // xoa tep anh de tranh anh rac
    if ($uploadedFilePath && file_exists($uploadedFilePath)) {
        unlink($uploadedFilePath);
    }

    error_log('Speaker store error: ' . $e->getMessage());

    $_SESSION['speaker_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Khong the them dien gia. Vui long kiem tra du lieu va thu lai.';

    header('Location: create.php');
    exit;
}
