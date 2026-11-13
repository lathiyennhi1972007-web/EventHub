
<?php
// EVENTHUB - LOGIN AND AUTHENTICATION

// Cau hinh session cookie
session_set_cookie_params([
    'httponly' => true,
    'secure' => false, // localhost dang dung HTTP
    'samesite' => 'Lax'
]);

session_start();

require_once __DIR__ . '/../config/database.php';

// Neu da dang nhap va co quyen ADMIN
if (
    isset($_SESSION['user_id']) &&
    in_array('ADMIN', $_SESSION['roles'] ?? [], true)
) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Tao CSRF token
if (empty($_SESSION['login_token'])) {
    $_SESSION['login_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['login_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['login_token'], $token)
    ) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng tải lại trang.';

    } else {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (
            $email === '' ||
            $password === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $error = 'Vui lòng kiểm tra email và mật khẩu.';

        } else {

            try {
                // Tim tai khoan theo email
                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        full_name,
                        email,
                        password_hash,
                        status
                    FROM users
                    WHERE email = ?
                    LIMIT 1
                ");

                $stmt->execute([$email]);
                $user = $stmt->fetch();

                // Kiem tra tai khoan va mat khau
                if (
                    !$user ||
                    !password_verify($password, $user['password_hash'])
                ) {
                    $error = 'Email hoặc mật khẩu không chính xác.';

                } elseif ($user['status'] !== 'active') {
                    $error = 'Tài khoản hiện không hoạt động.';

                } else {

                    // Lay danh sach vai tro
                    $roleStmt = $pdo->prepare("
                        SELECT r.role_code
                        FROM user_roles ur
                        JOIN roles r ON r.id = ur.role_id
                        WHERE ur.user_id = ?
                    ");

                    $roleStmt->execute([$user['id']]);
                    $roles = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!in_array('ADMIN', $roles, true)) {
                        $error = 'Tài khoản chưa được cấp quyền Admin.';

                    } else {

                        // Tao session moi sau khi dang nhap
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int) $user['id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['roles'] = $roles;
$_SESSION['last_activity'] = time();
                        unset($_SESSION['login_token']);

                        header('Location: ../admin/dashboard.php');
                        exit;
                    }
                }

            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Hệ thống đang gặp sự cố. Vui lòng thử lại.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | EventHub</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>

<div class="login-container">

    <div class="login-banner">

        <div class="brand">
            <div class="brand-icon">E</div>
            <span>EventHub</span>
        </div>

        <div class="banner-content">
            <span class="welcome-label">EVENT MANAGEMENT PLATFORM</span>

            <h1>Kết nối sự kiện.<br>
                Kiến tạo trải nghiệm.</h1>

            <p>
                Nền tảng quản lý sự kiện trực tuyến,
                kết nối con người và tối ưu mọi hoạt động tổ chức.
            </p>

            <div class="banner-decoration">
                <div class="decoration-card card-one">
                    <span>✦</span>
                    <strong>Plan</strong>
                    <small>Lên kế hoạch</small>
                </div>

                <div class="decoration-card card-two">
                    <span>♡</span>
                    <strong>Connect</strong>
                    <small>Kết nối mọi người</small>
                </div>

                <div class="decoration-card card-three">
                    <span>✓</span>
                    <strong>Manage</strong>
                    <small>Quản lý hiệu quả</small>
                </div>
            </div>
        </div>

        <div class="banner-footer">
            EventHub Management System © 2026
        </div>
    </div>

    <div class="login-form-section">

        <div class="form-wrapper">

            <div class="mobile-brand">
                <div class="brand-icon">E</div>
                <span>EventHub</span>
            </div>

            <div class="form-heading">
                <span class="form-label">WELCOME BACK</span>
                <h2>Chào mừng trở lại!</h2>
                <p>Đăng nhập để tiếp tục sử dụng EventHub.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="login-error" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">

                <input
                    type="hidden"
                    name="login_token"
                    value="<?= htmlspecialchars($_SESSION['login_token'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="form-group">
                    <label for="email">Địa chỉ email</label>

                    <div class="input-wrapper">
                        <span class="input-icon">✉</span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Nhập email của bạn"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>

                    <div class="input-wrapper">
                        <span class="input-icon">♙</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Nhập mật khẩu"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="login-button">
                    Đăng nhập
                    <span>→</span>
                </button>

            </form>

            <div class="login-divider">
                <span>EVENTHUB</span>
            </div>

            <p class="security-note">
                ♡ Hệ thống quản lý sự kiện an toàn và chuyên nghiệp
            </p>

        </div>
    </div>
</div>

</body>
</html>
