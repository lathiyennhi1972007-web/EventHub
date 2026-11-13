
<?php
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';

$isEventPage = strpos($currentScript, '/admin/events/') !== false;
$isSpeakerPage = strpos($currentScript, '/admin/speakers/') !== false;
$isDashboardPage = strpos($currentScript, '/admin/dashboard.php') !== false;
?>

<aside class="admin-sidebar">

    <div class="sidebar-brand">
        <span class="sidebar-logo">E</span>
        <span>EventHub</span>
    </div>

    <nav class="sidebar-menu">

        <!-- TONG QUAN -->
        <div class="menu-heading">TỔNG QUAN</div>

        <a href="/EventHub/admin/dashboard.php"
           class="menu-link <?= $isDashboardPage ? 'active' : '' ?>">
            <span class="menu-icon">▦</span>
            <span>Dashboard</span>
        </a>

        <!-- QUAN LY SU KIEN -->
        <div class="menu-heading">QUẢN LÝ SỰ KIỆN</div>

        <details class="event-menu" <?= $isEventPage ? 'open' : '' ?>>

            <summary class="menu-link menu-toggle">
                <span class="menu-icon">📅</span>
                <span>Sự kiện</span>
                <span class="submenu-arrow">⌄</span>
            </summary>

            <div class="submenu">

                <a href="/EventHub/admin/events/index.php"
                   class="submenu-link">
                    Danh sách sự kiện
                </a>

                <a href="/EventHub/admin/events/create.php"
                   class="submenu-link">
                    Tạo sự kiện mới
                </a>

                <div class="menu-link coming">
                    Danh mục sự kiện
                    <span class="coming-label">Sắp có</span>
                </div>

                <div class="menu-link coming">
                    Địa điểm tổ chức
                    <span class="coming-label">Sắp có</span>
                </div>

            </div>

        </details>

        <a href="/EventHub/admin/speakers/index.php"
   class="menu-link <?= $isSpeakerPage ? 'active' : '' ?>">
    <span class="menu-icon">🎤</span>
    <span>Diễn giả</span>
</a>

        <div class="menu-link coming">
            <span class="menu-icon">👥</span>
            Khách mời
            <span class="coming-label">Sắp có</span>
        </div>

        <div class="menu-link coming">
            <span class="menu-icon">🎟️</span>
            Đăng ký và vé
            <span class="coming-label">Sắp có</span>
        </div>

        <div class="menu-link coming">
            <span class="menu-icon">📷</span>
            Check-in
            <span class="coming-label">Sắp có</span>
        </div>

        <!-- DIEU HANH -->
        <div class="menu-heading">ĐIỀU HÀNH</div>

        <div class="menu-link coming">
            <span class="menu-icon">📋</span>
            Timeline & Kanban
            <span class="coming-label">Sắp có</span>
        </div>

        <div class="menu-link coming">
            <span class="menu-icon">💰</span>
            Ngân sách và chi phí
            <span class="coming-label">Sắp có</span>
        </div>

        <div class="menu-link coming">
            <span class="menu-icon">🧰</span>
            Tài nguyên
            <span class="coming-label">Sắp có</span>
        </div>

        <!-- PHAN TICH VA HE THONG -->
        <div class="menu-heading">PHÂN TÍCH & HỆ THỐNG</div>

        <div class="menu-link coming">
            <span class="menu-icon">📊</span>
            Báo cáo và thống kê
            <span class="coming-label">Sắp có</span>
        </div>

        <div class="menu-link coming">
            <span class="menu-icon">📝</span>
            Khảo sát
            <span class="coming-label">Sắp có</span>
        </div>

        <div class="menu-link coming">
            <span class="menu-icon">⚙️</span>
            Tài khoản và phân quyền
            <span class="coming-label">Sắp có</span>
        </div>

    </nav>

</aside>
<?php
// Lấy thời điểm hoạt động và mã CSRF của phiên Admin hiện tại
$sessionLastActivity = (int) ($_SESSION['last_activity'] ?? time());
$sessionCsrfToken = $_SESSION['csrf_token'] ?? '';
?>

<style>
    /* ===== CẢNH BÁO PHIÊN ĐĂNG NHẬP ===== */

    .session-warning-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(54, 35, 49, 0.42);
        backdrop-filter: blur(5px);
    }

    .session-warning-overlay[hidden] {
        display: none !important;
    }

    .session-warning-card {
        width: 100%;
        max-width: 440px;
        padding: 32px;
        border: 1px solid #f5d5e5;
        border-radius: 24px;
        background: linear-gradient(145deg, #ffffff, #fff5fa);
        box-shadow: 0 20px 60px rgba(91, 48, 76, 0.22);
        text-align: center;
        animation: sessionPopup 0.3s ease;
    }

    @keyframes sessionPopup {
        from {
            opacity: 0;
            transform: translateY(15px) scale(0.97);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .session-warning-icon {
        width: 65px;
        height: 65px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
        border-radius: 20px;
        background: linear-gradient(135deg, #ffe2ef, #eee5ff);
        font-size: 30px;
    }

    .session-warning-card h2 {
        margin: 0 0 12px;
        color: #65445b;
        font-size: 23px;
    }

    .session-warning-card p {
        margin: 0;
        color: #8f7587;
        font-size: 14px;
        line-height: 1.7;
    }

    .session-countdown {
        margin: 20px 0;
        color: #d96b9a;
        font-size: 36px;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
        letter-spacing: 2px;
    }

    .session-warning-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 22px;
    }

    .session-warning-button {
        min-height: 44px;
        padding: 12px 19px;
        border: none;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 650;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .session-extend-button {
        background: linear-gradient(135deg, #e77eaa, #bd83d4);
        color: #ffffff;
        box-shadow: 0 5px 14px rgba(207, 111, 167, 0.2);
    }

    .session-extend-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(207, 111, 167, 0.28);
    }

    .session-extend-button:disabled {
        cursor: wait;
        opacity: 0.7;
        transform: none;
    }

    .session-logout-button {
        background: #fff0f4;
        border: 1px solid #f5d5e1;
        color: #c65e83;
    }

    .session-logout-button:hover {
        background: #ffe2ec;
    }

    .session-warning-error {
        margin-top: 14px !important;
        color: #d14f65 !important;
        font-size: 13px !important;
    }

    @media (max-width: 480px) {
        .session-warning-card {
            padding: 24px 18px;
        }

        .session-warning-actions {
            flex-direction: column;
        }

        .session-warning-button {
            width: 100%;
        }
    }
</style>

<!-- Hộp cảnh báo phiên đăng nhập -->
<div
    class="session-warning-overlay"
    id="sessionWarning"
    role="dialog"
    aria-modal="true"
    aria-labelledby="sessionWarningTitle"
    hidden
>
    <div class="session-warning-card">

        <div class="session-warning-icon">⏳</div>

        <h2 id="sessionWarningTitle">Phiên đăng nhập sắp hết hạn</h2>

        <p>
            Bạn đã không hoạt động trong một thời gian.
            Phiên đăng nhập sẽ kết thúc sau:
        </p>

        <div class="session-countdown" id="sessionCountdown">
            02:00
        </div>

        <p>
            Hãy gia hạn phiên để tiếp tục sử dụng hệ thống EventHub.
        </p>

        <div class="session-warning-actions">

            <button
                type="button"
                class="session-warning-button session-extend-button"
                id="extendSessionButton"
            >
                Gia hạn phiên
            </button>

            <a
                href="/EventHub/auth/logout.php"
                class="session-warning-button session-logout-button"
                style="text-decoration: none;"
            >
                Đăng xuất
            </a>

        </div>

        <p
            class="session-warning-error"
            id="sessionWarningError"
            role="alert"
            hidden
        ></p>

    </div>
</div>

<script>
(function () {
    // Thiết lập thời gian phiên đăng nhập
const IDLE_LIMIT = 15 * 60 * 1000;
const WARNING_TIME = 13 * 60 * 1000;
    const ACTIVITY_INTERVAL = 5000;

    // Địa chỉ xử lý gia hạn phiên
    const ACTIVITY_URL = '/EventHub/admin/session_activity.php';
    const DASHBOARD_URL = '/EventHub/admin/dashboard.php';
    const LOGIN_URL = '/EventHub/auth/login.php?expired=1';

    // Nhận dữ liệu phiên từ máy chủ
    const CSRF_TOKEN = <?= json_encode(
        $sessionCsrfToken,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) ?>;

    let lastActivityAt = <?= $sessionLastActivity ?> * 1000;
    let lastRequestAt = 0;
    let warningVisible = false;
    let expiryHandled = false;
    let activityRequestPending = false;

    const warningBox = document.getElementById('sessionWarning');
    const countdown = document.getElementById('sessionCountdown');
    const extendButton = document.getElementById('extendSessionButton');
    const errorMessage = document.getElementById('sessionWarningError');

    // Chuyển phiên đã hết hạn về máy chủ để kiểm tra và hủy phiên
    function handleExpiredSession() {
        if (expiryHandled) return;

        expiryHandled = true;
        window.location.replace(DASHBOARD_URL);
    }

    // Gửi yêu cầu ghi nhận hoạt động hoặc gia hạn phiên
    async function sendActivity(isExtension = false) {
        if (expiryHandled || activityRequestPending) {
            return false;
        }

        activityRequestPending = true;

        try {
            const response = await fetch(ACTIVITY_URL, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: new URLSearchParams({
                    csrf_token: CSRF_TOKEN
                }).toString()
            });

            const result = await response.json();

            if (response.status === 401 || result.expired) {
                window.location.replace(LOGIN_URL);
                return false;
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không thể gia hạn phiên.');
            }

            // Cập nhật thời gian hoạt động theo dữ liệu máy chủ
            lastActivityAt = Number(result.last_activity) * 1000;
            lastRequestAt = Date.now();

            if (isExtension) {
                warningVisible = false;
                warningBox.hidden = true;
                errorMessage.hidden = true;
            }

            return true;

        } catch (error) {
            if (isExtension) {
                errorMessage.textContent =
                    'Không thể gia hạn phiên. Vui lòng kiểm tra kết nối và thử lại.';
                errorMessage.hidden = false;
            }

            return false;

        } finally {
            activityRequestPending = false;
        }
    }

    // Ghi nhận thao tác thực tế của người dùng
    function registerActivity() {
        if (warningVisible || expiryHandled) {
            return;
        }

        const now = Date.now();

        // Chỉ gửi yêu cầu tối đa một lần trong mỗi khoảng 5 giây
        if (now - lastRequestAt >= ACTIVITY_INTERVAL) {
            lastRequestAt = now;
            sendActivity(false);
        }
    }

    // Định dạng thời gian đếm ngược
    function formatTime(totalSeconds) {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;

        return String(minutes).padStart(2, '0')
            + ':'
            + String(seconds).padStart(2, '0');
    }

    // Hiển thị cảnh báo khi phiên còn 2 phút
    function updateSessionWarning() {
        if (expiryHandled) return;

        const elapsed = Date.now() - lastActivityAt;

        if (elapsed >= IDLE_LIMIT) {
            handleExpiredSession();
            return;
        }

        if (elapsed >= WARNING_TIME) {
            warningVisible = true;
            warningBox.hidden = false;

            const remainingSeconds = Math.max(
                0,
                Math.ceil((IDLE_LIMIT - elapsed) / 1000)
            );

            countdown.textContent = formatTime(remainingSeconds);

        } else {
            warningVisible = false;
            warningBox.hidden = true;
        }
    }

    // Xử lý thao tác gia hạn bằng nút trên hộp cảnh báo
    extendButton.addEventListener('click', async function () {
        extendButton.disabled = true;
        extendButton.textContent = 'Đang gia hạn...';
        errorMessage.hidden = true;

        const success = await sendActivity(true);

        if (!success && !expiryHandled) {
            extendButton.disabled = false;
            extendButton.textContent = 'Thử gia hạn lại';
        } else if (success) {
            extendButton.disabled = false;
            extendButton.textContent = 'Gia hạn phiên';
        }
    });

    // Theo dõi thao tác chuột, bàn phím, cuộn trang và màn hình cảm ứng
    ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(function (eventName) {
        document.addEventListener(eventName, registerActivity, {
            passive: true
        });
    });

    // Cập nhật đồng hồ đếm ngược mỗi giây
    updateSessionWarning();
    setInterval(updateSessionWarning, 1000);

})();
</script>