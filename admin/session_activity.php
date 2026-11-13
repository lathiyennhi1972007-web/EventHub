
<?php

// Khởi động phiên đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Trả kết quả dưới dạng JSON
header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức yêu cầu không hợp lệ.'
    ]);
    exit;
}

// Kiểm tra tài khoản và quyền Admin
if (
    !isset($_SESSION['user_id']) ||
    !in_array('ADMIN', $_SESSION['roles'] ?? [], true)
) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Phiên đăng nhập không hợp lệ.'
    ]);
    exit;
}

// Kiểm tra thời gian không hoạt động
$idleTimeout = 15 * 60;
$lastActivity = $_SESSION['last_activity'] ?? 0;

if (!$lastActivity || (time() - $lastActivity) >= $idleTimeout) {
    // Xóa dữ liệu phiên
    $_SESSION = [];

    // Xóa cookie phiên
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

    session_destroy();

    http_response_code(401);
    echo json_encode([
        'success' => false,
        'expired' => true,
        'message' => 'Phiên đăng nhập đã hết hạn.'
    ]);
    exit;
}

// Kiểm tra mã CSRF
$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Yêu cầu không hợp lệ.'
    ]);
    exit;
}

// Gia hạn thời gian hoạt động
$_SESSION['last_activity'] = time();

echo json_encode([
    'success' => true,
    'last_activity' => $_SESSION['last_activity']
]);
