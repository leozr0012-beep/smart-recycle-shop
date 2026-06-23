<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
adminGuard();

$errors = [];
$success = '';

// Handle Delete Admin
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $currentAdminId = intval($_SESSION['user']['id']);
    
    if ($deleteId === $currentAdminId) {
        $errors[] = 'ไม่สามารถลบโปรไฟล์ของตัวเองได้';
    } else {
        // Fetch the avatar path to delete it
        $stmt = $mysqli->prepare('SELECT avatar FROM users WHERE id = ? AND role = \'admin\' LIMIT 1');
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $res = $stmt->get_result();
        $targetUser = $res->fetch_assoc();
        $stmt->close();
        
        if ($targetUser) {
            if (!empty($targetUser['avatar'])) {
                $oldAvatarPath = __DIR__ . '/../' . $targetUser['avatar'];
                if (file_exists($oldAvatarPath) && is_file($oldAvatarPath)) {
                    unlink($oldAvatarPath);
                }
            }
            
            $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ? AND role = \'admin\'');
            $stmt->bind_param('i', $deleteId);
            if ($stmt->execute()) {
                $success = 'ลบโปรไฟล์ผู้ดูแลระบบเรียบร้อยแล้ว';
            } else {
                $errors[] = 'เกิดข้อผิดพลาดในการลบข้อมูล';
            }
            $stmt->close();
        } else {
            $errors[] = 'ไม่พบผู้ดูแลระบบที่ต้องการลบ';
        }
    }
}

// Handle Add/Edit Admin Profile
$editId = intval($_GET['edit'] ?? 0);
$editAdmin = null;

if ($editId) {
    $stmt = $mysqli->prepare('SELECT * FROM users WHERE id = ? AND role = \'admin\' LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editAdmin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$editAdmin) {
        $errors[] = 'ไม่พบข้อมูลผู้ดูแลระบบที่ต้องการแก้ไข';
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $lineId = sanitize($_POST['line_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$name || !$phone) {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล และเบอร์โทรศัพท์';
    }
    
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
    
    // Check if phone number is already registered by another user
    if ($action === 'add') {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $phoneCheck = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($phoneCheck) {
            $errors[] = 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้วในระบบ';
        }
        
        if (!$password || strlen($password) < 6) {
            $errors[] = 'กรุณากรอกรหัสผ่านอย่างน้อย 6 ตัวอักษร';
        }
    } elseif ($action === 'edit') {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1');
        $stmt->bind_param('si', $phone, $editId);
        $stmt->execute();
        $phoneCheck = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($phoneCheck) {
            $errors[] = 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้วโดยผู้ใช้อื่น';
        }
        
        if ($password !== '' && strlen($password) < 6) {
            $errors[] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
        }
    }
    
    // Handle Avatar File Upload if any
    $avatarPath = null;
    if (empty($errors) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'เกิดข้อผิดพลาดในการอัปโหลดรูปโปรไฟล์';
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = 'รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF, WEBP) เท่านั้น';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'ขนาดไฟล์ต้องไม่เกิน 2MB';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $ext = ($fileType === 'image/png') ? 'png' : (($fileType === 'image/gif') ? 'gif' : (($fileType === 'image/webp') ? 'webp' : 'jpg'));
                }
                
                // Save to project root's profile directory
                $uploadDir = __DIR__ . '/../profile/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $filename = 'admin_' . time() . '_' . uniqid() . '.' . $ext;
                $destination = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $avatarPath = 'profile/' . $filename;
                } else {
                    $errors[] = 'ไม่สามารถบันทึกไฟล์รูปภาพได้';
                }
            }
        }
    }
    
    if (empty($errors)) {
        if ($action === 'add') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO users (name, phone, email, line_id, password, role, avatar, created_at) VALUES (?, ?, ?, ?, ?, \'admin\', ?, NOW())');
            $stmt->bind_param('ssssss', $name, $phone, $email, $lineId, $hashedPassword, $avatarPath);
            
            if ($stmt->execute()) {
                $success = 'เพิ่มโปรไฟล์ผู้ดูแลระบบสำเร็จแล้ว';
                // Reset form fields
                $_POST = [];
            } else {
                $errors[] = 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง';
            }
            $stmt->close();
        } elseif ($action === 'edit' && $editAdmin) {
            // Keep old avatar if no new avatar uploaded
            if ($avatarPath === null) {
                $avatarPath = $editAdmin['avatar'];
            } else {
                // Delete old avatar
                if (!empty($editAdmin['avatar'])) {
                    $oldAvatarPath = __DIR__ . '/../' . $editAdmin['avatar'];
                    if (file_exists($oldAvatarPath) && is_file($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
            }
            
            if ($password !== '') {
                // Changing password too
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare('UPDATE users SET name = ?, phone = ?, email = ?, line_id = ?, password = ?, avatar = ? WHERE id = ? AND role = \'admin\'');
                $stmt->bind_param('ssssssi', $name, $phone, $email, $lineId, $hashedPassword, $avatarPath, $editId);
            } else {
                // Keep existing password
                $stmt = $mysqli->prepare('UPDATE users SET name = ?, phone = ?, email = ?, line_id = ?, avatar = ? WHERE id = ? AND role = \'admin\'');
                $stmt->bind_param('sssssi', $name, $phone, $email, $lineId, $avatarPath, $editId);
            }
            
            if ($stmt->execute()) {
                $success = 'อัปเดตโปรไฟล์ผู้ดูแลระบบเรียบร้อยแล้ว';
                // If editing self, refresh session
                if ($editId === intval($_SESSION['user']['id'])) {
                    refreshUserSession($mysqli, $editId);
                }
                // Redirect back to list
                header('Location: ' . BASE_URL . '/admin/admins.php?success=' . urlencode($success));
                exit;
            } else {
                $errors[] = 'ไม่สามารถอัปเดตข้อมูลได้ กรุณาลองใหม่อีกครั้ง';
            }
            $stmt->close();
        }
    }
}

// Fetch success query param
if (isset($_GET['success'])) {
    $success = sanitize($_GET['success']);
}

// Fetch all admin profiles
$admins = [];
$stmt = $mysqli->prepare('SELECT * FROM users WHERE role = \'admin\' ORDER BY id DESC');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}
$stmt->close();

adminHeader('จัดการข้อมูลผู้ดูแลระบบ');
?>

<div class="admin-hero-cover" style="margin-bottom: 24px;">
    <div class="admin-hero-cover-content">
        <h1>จัดการโปรไฟล์ผู้ดูแลระบบ (Admin Management)</h1>
        <p>สามารถเพิ่ม แก้ไข หรือลบโปรไฟล์แอดมินรายอื่น รวมถึงจัดการข้อมูลความปลอดภัยของคุณเอง</p>
    </div>
    <div class="admin-hero-status">
        <div class="admin-status-dot"></div>
        <div class="admin-hero-status-text">
            <h4>จำนวนแอดมิน</h4>
            <p><?= count($admins) ?> บัญชี</p>
        </div>
    </div>
</div>

<!-- Alerts Block -->
<?php if ($success): ?>
    <div class="alert" style="background: #d1fae5; color: #065f46; border: 1px solid #10b981; margin-bottom: 24px; padding: 16px 20px; border-radius: var(--radius-md); font-weight: 500;">
        🎉 <?= sanitize($success) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert" style="background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; margin-bottom: 24px; padding: 16px 20px; border-radius: var(--radius-md); font-weight: 500;">
        ⚠️ <?= implode('<br>⚠️ ', $errors) ?>
    </div>
<?php endif; ?>

<div class="customizer-container" style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 32px; align-items: start;">
    
    <!-- Left Column: Admin Listing Table -->
    <div class="card" style="padding: 24px; margin-bottom: 0;">
        <h2>ผู้ดูแลระบบทั้งหมดในระบบ</h2>
        <div class="table-card" style="margin-top: 16px;">
            <table>
                <thead>
                    <tr>
                        <th>โปรไฟล์</th>
                        <th>ข้อมูลติดต่อ</th>
                        <th>LINE ID</th>
                        <th>วันที่สร้าง</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $item): 
                        $isAdminSelf = (intval($item['id']) === intval($_SESSION['user']['id']));
                        $avatarUrl = !empty($item['avatar']) ? BASE_URL . '/' . $item['avatar'] : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80';
                    ?>
                        <tr style="<?= $isAdminSelf ? 'background-color: rgba(79, 70, 229, 0.03);' : '' ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?= $avatarUrl ?>" alt="Avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color);">
                                    <div>
                                        <strong style="display: block; color: var(--text-primary);"><?= sanitize($item['name']) ?></strong>
                                        <?php if ($isAdminSelf): ?>
                                            <span style="font-size: 0.75rem; background: var(--primary); color: #fff; padding: 1px 6px; border-radius: 4px; font-weight: bold;">คุณเอง</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; display: block; color: var(--text-secondary);">📞 <?= sanitize($item['phone']) ?></span>
                                <span style="font-size: 0.85rem; display: block; color: var(--text-muted);"><?= sanitize($item['email']) ?></span>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: var(--text-secondary);"><?= sanitize($item['line_id'] ?: '-') ?></span>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= date('Y-m-d H:i', strtotime($item['created_at'])) ?></span>
                            </td>
                            <td>
                                <a href="?edit=<?= $item['id'] ?>" style="font-weight: 600; color: var(--primary);">แก้ไข</a>
                                <?php if (!$isAdminSelf): ?>
                                    | <a href="?delete=<?= $item['id'] ?>" onclick="return confirm('ยืนยันที่จะลบผู้ดูแลระบบรายนี้ใช่หรือไม่?');" style="font-weight: 600; color: #ef4444;">ลบออก</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Right Column: Add / Edit Form -->
    <div class="card" style="padding: 32px;">
        <h2><?= $editAdmin ? '📝 แก้ไขโปรไฟล์แอดมิน' : '➕ เพิ่มผู้ดูแลระบบใหม่' ?></h2>
        <p style="color: var(--text-secondary); margin-bottom: 24px; font-size: 0.9rem;">
            <?= $editAdmin ? 'กรุณากรอกข้อมูลเพื่อปรับปรุงบัญชีผู้ดูแลระบบ' : 'กรุณากรอกข้อมูลด้านล่าง บัญชีใหม่จะมีสิทธิ์ระดับแอดมินโดยอัตโนมัติ' ?>
        </p>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $editAdmin ? 'edit' : 'add' ?>">
            
            <div style="margin-bottom: 16px;">
                <label>ชื่อ-นามสกุล <span style="color: #ef4444;">*</span></label>
                <input type="text" name="name" value="<?= sanitize($_POST['name'] ?? ($editAdmin['name'] ?? '')) ?>" required placeholder="เช่น สมชาย ดีใจ">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label>เบอร์โทรศัพท์ <span style="color: #ef4444;">*</span></label>
                <input type="text" name="phone" value="<?= sanitize($_POST['phone'] ?? ($editAdmin['phone'] ?? '')) ?>" required placeholder="เช่น 0900000000">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label>อีเมล</label>
                <input type="email" name="email" value="<?= sanitize($_POST['email'] ?? ($editAdmin['email'] ?? '')) ?>" placeholder="เช่น admin@example.com">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label>LINE ID</label>
                <input type="text" name="line_id" value="<?= sanitize($_POST['line_id'] ?? ($editAdmin['line_id'] ?? '')) ?>" placeholder="เช่น line_admin">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label>รหัสผ่าน <?= $editAdmin ? '(ปล่อยว่างหากไม่ต้องการเปลี่ยน)' : '<span style="color: #ef4444;">*</span>' ?></label>
                <input type="password" name="password" <?= $editAdmin ? '' : 'required' ?> placeholder="อย่างน้อย 6 ตัวอักษร">
            </div>
            
            <div style="margin-bottom: 24px;">
                <label>รูปภาพโปรไฟล์</label>
                <input type="file" name="avatar" accept="image/*" style="padding: 8px 0; border: none; background: none; margin-bottom: 0;">
                <?php if ($editAdmin && !empty($editAdmin['avatar'])): ?>
                    <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 0.85rem; color: var(--text-secondary);">รูปปัจจุบัน:</span>
                        <img src="<?= BASE_URL . '/' . $editAdmin['avatar'] ?>" alt="Current Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn" style="flex: 1;"><?= $editAdmin ? '💾 บันทึกการแก้ไข' : '➕ เพิ่มแอดมิน' ?></button>
                <?php if ($editAdmin): ?>
                    <a href="admins.php" class="btn btn-secondary" style="flex: 1; text-decoration: none; padding: 12px 0;">ยกเลิก</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

</div>

<?php
adminFooter();
?>
