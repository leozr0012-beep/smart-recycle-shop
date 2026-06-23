<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
authGuard();

$user = $_SESSION['user'];
$userId = $user['id'];

$errors = [];
$success = '';

// Handle Profile Details Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $lineId = sanitize($_POST['line_id'] ?? '');

    if (!$name) {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('UPDATE users SET name = ?, email = ?, line_id = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $email, $lineId, $userId);
        if ($stmt->execute()) {
            $success = 'อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว';
            refreshUserSession($mysqli, $userId);
            $user = $_SESSION['user']; // Refresh local variable
        } else {
            $errors[] = 'ไม่สามารถอัปเดตข้อมูลได้ กรุณาลองใหม่อีกครั้ง';
        }
        $stmt->close();
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$oldPassword || !$newPassword || !$confirmPassword) {
        $errors[] = 'กรุณากรอกข้อมูลรหัสผ่านให้ครบถ้วน';
    }

    if (empty($errors)) {
        // Fetch current password hash
        $stmt = $mysqli->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $dbUser = $res->fetch_assoc();
        $stmt->close();

        if ($dbUser && password_verify($oldPassword, $dbUser['password'])) {
            if ($newPassword === $confirmPassword) {
                if (strlen($newPassword) >= 6) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $stmt->bind_param('si', $hashedPassword, $userId);
                    if ($stmt->execute()) {
                        $success = 'เปลี่ยนรหัสผ่านใหม่เรียบร้อยแล้ว';
                    } else {
                        $errors[] = 'ไม่สามารถเปลี่ยนรหัสผ่านได้ กรุณาลองใหม่อีกครั้ง';
                    }
                    $stmt->close();
                } else {
                    $errors[] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
                }
            } else {
                $errors[] = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
            }
        } else {
            $errors[] = 'รหัสผ่านเดิมไม่ถูกต้อง';
        }
    }
}

// Handle Avatar Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['avatar'];
    
    // Validate file errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'มีข้อผิดพลาดในการอัปโหลดรูปภาพ';
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'อนุญาตเฉพาะไฟล์รูปภาพ (JPEG, PNG, WEBP, GIF) เท่านั้น';
        } else {
            // Check size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'รูปภาพต้องมีขนาดไม่เกิน 2MB';
            } else {
                // Get extension
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $ext = ($fileType === 'image/png') ? 'png' : (($fileType === 'image/gif') ? 'gif' : (($fileType === 'image/webp') ? 'webp' : 'jpg'));
                }
                
                // Unique filename using user ID and timestamp to prevent browser cache issues
                $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                
                // Set folder destination as "profile" folder
                $uploadDir = __DIR__ . '/../profile/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $destination = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Delete old profile picture if exists
                    if (!empty($user['avatar'])) {
                        $oldAvatarPath = __DIR__ . '/../' . $user['avatar'];
                        if (file_exists($oldAvatarPath) && is_file($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }
                    
                    // Save relative path to database
                    $dbAvatarPath = 'profile/' . $filename;
                    $stmt = $mysqli->prepare('UPDATE users SET avatar = ? WHERE id = ?');
                    $stmt->bind_param('si', $dbAvatarPath, $userId);
                    if ($stmt->execute()) {
                        $success = 'เปลี่ยนรูปโปรไฟล์เรียบร้อยแล้ว';
                        refreshUserSession($mysqli, $userId);
                        $user = $_SESSION['user']; // Refresh local variable
                    } else {
                        $errors[] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลรูปโปรไฟล์';
                    }
                    $stmt->close();
                } else {
                    $errors[] = 'ไม่สามารถย้ายไฟล์รูปภาพไปยังโฟลเดอร์ปลายทางได้';
                }
            }
        }
    }
}

pageHeader('โปรไฟล์ของฉัน - สมาร์ท รีไซเคิล ช็อป');
?>

<div class="profile-layout" style="display: grid; grid-template-columns: 320px 1fr; gap: 32px; max-width: 1100px; margin: 0 auto 48px auto;">
    
    <!-- Left Column: User Profile Picture Card -->
    <div class="profile-sidebar">
        <div class="card" style="padding: 32px 24px; text-align: center; display: flex; flex-direction: column; align-items: center; position: sticky; top: 100px;">
            
            <!-- Sleek Avatar Form with Hover Trigger -->
            <form id="avatar-form" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <div class="avatar-container" style="width: 140px; height: 140px; border-radius: 50%; overflow: hidden; position: relative; border: 4px solid var(--border-color); box-shadow: var(--shadow-md); cursor: pointer; transition: var(--transition);">
                    <?php 
                    $avatarUrl = !empty($user['avatar']) ? BASE_URL . '/' . $user['avatar'] : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80';
                    ?>
                    <img src="<?= $avatarUrl ?>" id="avatar-preview" alt="User Avatar" style="width: 100%; height: 100%; object-fit: cover; transition: var(--transition);">
                    
                    <!-- Hover overlay container -->
                    <div class="avatar-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: var(--transition); color: #ffffff; font-size: 0.8rem; font-weight: bold; gap: 6px;">
                        <svg style="width: 28px; height: 28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        เปลี่ยนรูปภาพ
                    </div>
                </div>
                <!-- Hidden file input trigger -->
                <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display: none;">
            </form>

            <h3 style="font-size: 1.3rem; margin-bottom: 6px; font-weight: 700; color: var(--text-primary);"><?= sanitize($user['name']) ?></h3>
            
            <!-- Role Badge -->
            <span class="badge" style="margin-bottom: 24px; font-weight: bold; background: <?= $user['role'] === 'admin' ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : 'linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%)' ?>; color: #fff; padding: 6px 16px; border-radius: 99px; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase;">
                <?= $user['role'] === 'admin' ? '🛡️ ผู้ดูแลระบบ (Admin)' : '👤 สมาชิก (Customer)' ?>
            </span>

            <!-- Details Sidebar info -->
            <div style="width: 100%; border-top: 1px solid var(--border-color); padding-top: 20px; text-align: left; font-size: 0.9rem;">
                <div style="margin-bottom: 12px;">
                    <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">เบอร์โทรศัพท์</span>
                    <strong style="color: var(--text-primary);"><?= sanitize($user['phone']) ?></strong>
                </div>
                <div style="margin-bottom: 12px;">
                    <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">อีเมลติดต่อ</span>
                    <strong style="color: var(--text-primary);"><?= sanitize($user['email'] ?: 'ยังไม่ได้ระบุ') ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">LINE ID</span>
                    <strong style="color: var(--text-primary);"><?= sanitize($user['line_id'] ?: 'ยังไม่ได้ระบุ') ?></strong>
                </div>
            </div>

        </div>
    </div>

    <!-- Right Column: Settings Form Panels -->
    <div class="profile-content" style="display: flex; flex-direction: column; gap: 32px;">
        
        <!-- Alerts Block -->
        <?php if ($success): ?>
            <div class="alert" style="background: #d1fae5; color: #065f46; border: 1px solid #10b981; margin: 0; padding: 16px 20px; border-radius: var(--radius-md); font-weight: 500;">
                🎉 <?= sanitize($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert" style="background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; margin: 0; padding: 16px 20px; border-radius: var(--radius-md); font-weight: 500;">
                ⚠️ <?= implode('<br>⚠️ ', $errors) ?>
            </div>
        <?php endif; ?>

        <!-- Panel 1: Profile Information Edit -->
        <div class="card" style="padding: 40px;">
            <h2 style="font-size: 1.5rem; margin-bottom: 8px; font-weight: 700; color: var(--text-primary);">แก้ไขข้อมูลส่วนตัว</h2>
            <p style="color: var(--text-secondary); margin-bottom: 32px; font-size: 0.95rem;">ปรับปรุงข้อมูลการติดต่อของคุณสำหรับการตรวจสอบรับประกันและจัดสั่งสินค้า</p>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary); font-size: 0.9rem;">ชื่อ-นามสกุล</label>
                        <input type="text" name="name" value="<?= sanitize($user['name']) ?>" required style="width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary); font-size: 0.9rem;">เบอร์โทรศัพท์ (ไม่สามารถเปลี่ยนได้)</label>
                        <input type="text" value="<?= sanitize($user['phone']) ?>" disabled style="width: 100%; box-sizing: border-box; background: #e2e8f0; color: #64748b; font-size: 0.95rem; cursor: not-allowed;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary); font-size: 0.9rem;">อีเมลติดต่อ</label>
                        <input type="email" name="email" value="<?= sanitize($user['email']) ?>" placeholder="เช่น customer@example.com" style="width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary); font-size: 0.9rem;">LINE ID</label>
                        <input type="text" name="line_id" value="<?= sanitize($user['line_id']) ?>" placeholder="เพื่อความสะดวกรวดเร็วในการส่งข้อมูลเคลมประกัน" style="width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                    </div>
                </div>

                <button type="submit" class="btn" style="padding: 12px 32px; font-size: 1rem;">💾 บันทึกข้อมูลส่วนตัว</button>
            </form>
        </div>

        <!-- Panel 2: Change Password -->
        <div class="card" style="padding: 40px;">
            <h2 style="font-size: 1.5rem; margin-bottom: 8px; font-weight: 700; color: var(--text-primary);">เปลี่ยนรหัสผ่านใหม่</h2>
            <p style="color: var(--text-secondary); margin-bottom: 32px; font-size: 0.95rem;">กรุณากรอกรหัสผ่านเก่าเพื่อยืนยันความเป็นเจ้าของ ก่อนทำการตั้งรหัสผ่านชุดใหม่</p>

            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary); font-size: 0.9rem;">รหัสผ่านเดิม</label>
                    <input type="password" name="old_password" required placeholder="กรอกรหัสผ่านปัจจุบันของคุณ" style="width: 100%; max-width: 480px; box-sizing: border-box; font-size: 0.95rem;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary); font-size: 0.9rem;">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" required placeholder="อย่างน้อย 6 ตัวอักษร" style="width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-secondary); font-size: 0.9rem;">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" required placeholder="กรอกรหัสผ่านใหม่อีกครั้งเพื่อยืนยัน" style="width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                    </div>
                </div>

                <button type="submit" class="btn btn-secondary" style="padding: 12px 32px; font-size: 1rem;">🔑 อัปเดตรหัสผ่านใหม่</button>
            </form>
        </div>

    </div>

</div>

<!-- Styles for Avatar container hover and layouts -->
<style>
.avatar-container:hover .avatar-overlay {
    opacity: 1 !important;
}
.avatar-container:hover img {
    transform: scale(1.05);
}
</style>

<!-- JS for Upload Submit on File Change -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarContainer = document.querySelector('.avatar-container');
    const avatarInput = document.getElementById('avatar-input');
    const avatarForm = document.getElementById('avatar-form');
    const avatarPreview = document.getElementById('avatar-preview');

    avatarContainer.addEventListener('click', function() {
        avatarInput.click();
    });

    avatarInput.addEventListener('change', function() {
        if (avatarInput.files && avatarInput.files[0]) {
            // Instantly submit form to trigger file upload
            avatarForm.submit();
        }
    });
});
</script>

<?php
pageFooter();
?>
