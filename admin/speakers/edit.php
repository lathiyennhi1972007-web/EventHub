
<?php
require_once __DIR__ . '/../../includes/admin_guard.php';
require_once __DIR__ . '/../../config/database.php';

// Ham ma hoa du lieu
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Tao CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Kiem tra ID dien gia
$speakerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$speakerId || $speakerId < 1) {
    header('Location: index.php');
    exit;
}

// Lay thong tin dien gia
$stmt = $pdo->prepare("
    SELECT
        id,
        full_name,
        email,
        phone,
        organization,
        position,
        biography,
        avatar,
        created_at
    FROM speakers
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$speakerId]);
$speaker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$speaker) {
    http_response_code(404);
    exit('Khong tim thay dien gia can chinh sua.');
}

// Duong dan anh hien tai
$currentAvatarUrl = !empty($speaker['avatar'])
    ? '../../' . ltrim($speaker['avatar'], '/')
    : null;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Chỉnh sửa diễn giả | EventHub</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/events.css">
	
<style>
    /* ===== SPEAKER FORM - MODERN PASTEL ===== */

    .speaker-form-card {
        border: 1px solid #f4dce9;
        border-radius: 22px;
        padding: 28px;
        background: linear-gradient(145deg, #ffffff, #fff9fc);
        box-shadow: 0 12px 35px rgba(205, 113, 157, 0.09);
    }

    .speaker-form-card .form-section {
        position: relative;
        padding: 23px;
        margin-bottom: 22px;
        border: 1px solid #f4e5ee;
        border-radius: 17px;
        background: #ffffff;
        box-shadow: 0 5px 16px rgba(170, 105, 140, 0.045);
    }

    .speaker-form-card .form-section:nth-child(1) {
        background: linear-gradient(135deg, #fff5fa, #ffffff 75%);
        border-left: 4px solid #e77eaa;
    }

    .speaker-form-card .form-section:nth-child(2) {
        background: linear-gradient(135deg, #f7f3ff, #ffffff 75%);
        border-left: 4px solid #a997e9;
    }

    .speaker-form-card .form-section:nth-child(3) {
        background: linear-gradient(135deg, #fff7f1, #ffffff 75%);
        border-left: 4px solid #efa77e;
    }

    .speaker-form-card .form-section h2 {
        display: flex;
        align-items: center;
        gap: 11px;
        margin-top: 0;
        margin-bottom: 23px;
        color: #65445b;
        font-size: 18px;
        font-weight: 700;
    }

    .speaker-form-card .form-section:nth-child(1) h2::before {
        content: "✦";
        color: #dc6f9e;
        background: #ffe4f0;
    }

    .speaker-form-card .form-section:nth-child(2) h2::before {
        content: "✧";
        color: #927bd3;
        background: #eee8ff;
    }

    .speaker-form-card .form-section:nth-child(3) h2::before {
        content: "♡";
        color: #df936a;
        background: #fff0e5;
    }

    .speaker-form-card .form-section h2::before {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        font-size: 20px;
    }

    .speaker-form-card .form-group label {
        color: #63495a;
        font-weight: 650;
        margin-bottom: 9px;
    }

    .speaker-form-card input:not([type="file"]),
    .speaker-form-card textarea {
        border: 1px solid #f0dce7;
        border-radius: 12px;
        background: #fffafd;
        transition: all 0.22s ease;
    }

    .speaker-form-card input:focus,
    .speaker-form-card textarea:focus {
        outline: none;
        border-color: #dc7ba6;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(220, 123, 166, 0.12);
    }

    .speaker-form-card input[type="file"] {
        width: 100%;
        padding: 15px;
        border: 1px dashed #dda4c0;
        border-radius: 13px;
        background: #fff7fb;
        cursor: pointer;
    }

    .speaker-form-card small {
        display: block;
        margin-top: 8px;
        color: #9b8492;
        line-height: 1.5;
    }

    .speaker-form-card .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding-top: 8px;
    }

    .speaker-form-card .search-button {
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #e77eaa, #c96fba);
        box-shadow: 0 6px 15px rgba(207, 111, 167, 0.23);
        transition: all 0.2s ease;
    }

    .speaker-form-card .search-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 9px 20px rgba(207, 111, 167, 0.3);
    }

    .speaker-form-card .reset-button {
        border-radius: 12px;
        background: #fff0f6;
        border: 1px solid #f4d7e5;
    }

    @media (max-width: 700px) {
        .speaker-form-card {
            padding: 13px;
        }

        .speaker-form-card .form-section {
            padding: 16px;
		}
	}
/* ===== AVATAR UPLOAD ===== */

.avatar-upload-box {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 20px;
    border: 1px dashed #e9b5ce;
    border-radius: 16px;
    background: linear-gradient(135deg, #fff5fa, #faf6ff);
}

.avatar-preview {
    width: 125px;
    height: 125px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    overflow: hidden;
    border: 3px solid #f4d6e5;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffe3f0, #eee7ff);
    color: #d16f9e;
}

.avatar-preview span {
    font-size: 35px;
}

.avatar-preview small {
    font-size: 11px;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-upload-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 9px;
}

.speaker-form-card .avatar-upload-content input[type="file"] {
    display: none;
}

.avatar-upload-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 17px;
    border-radius: 11px;
    background: linear-gradient(135deg, #e77eaa, #bd83d4);
    color: white !important;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s ease;
}

.avatar-upload-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 14px rgba(207, 111, 167, 0.2);
}

.avatar-remove-button {
    border: none;
    background: #fff0f3;
    color: #ce6484;
    padding: 7px 11px;
    border-radius: 8px;
    cursor: pointer;
}

@media (max-width: 550px) {
    .avatar-upload-box {
        flex-direction: column;
        align-items: flex-start;
    }
}

</style>

</head>

<body>

<div class="admin-layout">

    <?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-content">

            <section class="page-heading">
<h1>Chỉnh sửa diễn giả</h1>
<p>Cập nhật thông tin hồ sơ diễn giả #<?= (int) $speaker['id'] ?>.</p>

                <?php if (!empty($_SESSION['speaker_error'])): ?>
                    <div class="event-message error-message">
                        <?= e($_SESSION['speaker_error']) ?>
                    </div>
                    <?php unset($_SESSION['speaker_error']); ?>
                <?php endif; ?>
            </section>

<section class="event-form-card speaker-form-card">


<form action="update.php" method="POST"
      enctype="multipart/form-data">

    <!-- Gửi mã diễn giả cần cập nhật -->
    <input
        type="hidden"
        name="id"
        value="<?= (int) $speaker['id'] ?>"
    >

    <!-- Mã bảo vệ yêu cầu CSRF -->
    <input
        type="hidden"
        name="csrf_token"
        value="<?= e($_SESSION['csrf_token']) ?>"
    >


                    <div class="form-section">
                        <h2>Thông tin cơ bản</h2>

                        <div class="form-group">
                            <label for="full_name">Họ và tên diễn giả *</label>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                maxlength="150"
    value="<?= e($speaker['full_name']) ?>"
                                required
                            >
                        </div>

                        <div class="form-grid">

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    maxlength="150"
                      value="<?= e($speaker['email'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="phone">Số điện thoại</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    maxlength="20"
                     value="<?= e($speaker['phone'] ?? '') ?>"
                                >
                            </div>

                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Thông tin công tác</h2>

                        <div class="form-grid">

                            <div class="form-group">
                                <label for="organization">Tổ chức / Đơn vị</label>
                                <input
                                    type="text"
                                    id="organization"
                                    name="organization"
                                    maxlength="150"
	value="<?= e($speaker['organization'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label for="position">Chức vụ</label>
                                <input
                                    type="text"
                                    id="position"
                                    name="position"
                                    maxlength="150"
value="<?= e($speaker['position'] ?? '') ?>"
                                >
                            </div>

                        </div>
                    </div>

                    <div class="form-section">
                        <h2>Giới thiệu diễn giả</h2>

                        <div class="form-group">
                            <label for="biography">Tiểu sử / Giới thiệu</label>
         <textarea
    id="biography"
    name="biography"
    rows="5"
    placeholder="Nhập thông tin giới thiệu về diễn giả..."
><?= e($speaker['biography'] ?? '') ?></textarea>
                        </div>

<div class="form-group">
    <label for="avatar">Ảnh đại diện diễn giả</label>

    <div class="avatar-upload-box">

        <div class="avatar-preview" id="avatarPreview">
            <?php if ($currentAvatarUrl): ?>
    <img src="<?= e($currentAvatarUrl) ?>" alt="Ảnh đại diện hiện tại">
<?php else: ?>
    <span>👤</span>
    <small>Chưa có ảnh</small>
<?php endif; ?>
        </div>

        <div class="avatar-upload-content">
            <label for="avatar" class="avatar-upload-button">
                <span>📷</span> Chọn ảnh đại diện
            </label>

            <input
                type="file"
                id="avatar"
                name="avatar"
                accept="image/jpeg,image/png,image/webp"
            >

            <small>
               <?= $currentAvatarUrl
        ? 'Đang sử dụng ảnh hiện tại. Chọn ảnh mới nếu muốn thay đổi.'
        : 'Chưa có ảnh đại diện. Bạn có thể chọn ảnh mới.' ?>
            </small>

            <button
                type="button"
                id="removeAvatar"
                class="avatar-remove-button"
                hidden
            >
                ✕ Bỏ ảnh đã chọn
            </button>
        </div>

    </div>
</div>
                    </div>

                    <div class="form-actions">

                        <a href="index.php" class="reset-button">
                            Quay lại
                        </a>

                        <button type="submit" class="search-button">
                           Lưu thay đổi
                        </button>

                    </div>

                </form>

            </section>

        </div>
    </main>

</div>


<script>
const avatarInput = document.getElementById('avatar');
const avatarPreview = document.getElementById('avatarPreview');
const removeAvatar = document.getElementById('removeAvatar');

avatarInput.addEventListener('change', function () {
    const file = this.files[0];

    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Vui lòng chọn ảnh JPG, PNG hoặc WEBP.');
        this.value = '';
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        avatarPreview.innerHTML = '';

        const img = document.createElement('img');
        img.src = event.target.result;
        img.alt = 'Ảnh đại diện xem trước';

        avatarPreview.appendChild(img);
        removeAvatar.hidden = false;
    };

    reader.readAsDataURL(file);
});

removeAvatar.addEventListener('click', function () {
    avatarInput.value = '';

    avatarPreview.innerHTML = `
        <span>👤</span>
        <small>Chưa có ảnh</small>
    `;

    removeAvatar.hidden = true;
});
</script>
</body>
</html>
