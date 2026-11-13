
<?php
// Khoi dong session neu chua duoc khoi dong
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiem tra tai khoan da dang nhap
if (!isset($_SESSION['user_id'])) {
    header('Location: /EventHub/auth/login.php');
    exit;
}

// Kiem tra quyen Admin
if (!in_array('ADMIN', $_SESSION['roles'] ?? [], true)) {
    http_response_code(403);
    exit('Ban khong co quyen truy cap khu vuc quan tri.');
}

// Thoi gian toi da khong hoat dong: 15 phut
$idleTimeout = 15 * 60;

// Kiem tra phien da het han chua
if (
    !isset($_SESSION['last_activity']) ||
    (time() - $_SESSION['last_activity']) >= $idleTimeout
) {
    // Xoa du lieu phien
    $_SESSION = [];

    // Xoa cookie session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax'
            ]
        );
    }

    // Huy session
    session_destroy();

    // Chuyen ve trang dang nhap
    header('Location: /EventHub/auth/login.php?expired=1');
    exit;
}

// Khởi tạo mã CSRF dùng chung cho khu vực Admin
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cập nhật thời điểm hoạt động gần nhất
$_SESSION['last_activity'] = time();
