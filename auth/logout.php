
<?php

session_start();

// Xoa toan bo du lieu phien
$_SESSION = [];

// Huy session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Huy session
session_destroy();

// Quay ve trang dang nhap
header('Location: login.php');
exit;

