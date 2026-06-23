<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $line_id = sanitize($_POST['line_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'customer';

    if (!$name || !$phone || !$email || !$password) {
        $errors[] = 'กรุณากรอกข้อมูลในช่องที่จำเป็นให้ครบถ้วน';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'กรุณากรอกรูปแบบอีเมลให้ถูกต้อง';
    }
    if (strlen($password) < 6) {
        $errors[] = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    }
    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'เบอร์โทรศัพท์นี้ถูกใช้ลงทะเบียนแล้ว';
        }
        $stmt->close();
    }
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare('INSERT INTO users (name, phone, email, line_id, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssssss', $name, $phone, $email, $line_id, $passwordHash, $role);
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            refreshUserSession($mysqli, $userId);
            redirect(BASE_URL . '/');
        } else {
            $errors[] = 'เกิดข้อผิดพลาดในการลงทะเบียน กรุณาลองใหม่อีกครั้ง';
        }
        $stmt->close();
    }
}

pageHeader('Register');
if ($errors) {
    echo '<div class="alert">' . implode('<br>', $errors) . '</div>';
}
?>
<div class="card" style="max-width: 640px; margin: auto;">
    <h2>สร้างบัญชีสมาชิกใหม่</h2>
    <p>สมัครสมาชิกเพื่อสั่งซื้อเครื่องใช้ไฟฟ้า คุยติดต่อสอบถาม และติดตามการส่งมอบสินค้า</p>
    <form method="post">
        <label>ชื่อ-นามสกุล</label>
        <input type="text" name="name" placeholder="ระบุชื่อและนามสกุลของคุณ" value="<?= sanitize($_POST['name'] ?? '') ?>" required>
        <label>เบอร์โทรศัพท์</label>
        <input type="text" name="phone" placeholder="เช่น 0912345678" value="<?= sanitize($_POST['phone'] ?? '') ?>" required>
        <label>อีเมล</label>
        <input type="email" name="email" placeholder="ระบุที่อยู่อีเมล เช่น customer@example.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
        <label>LINE ID</label>
        <input type="text" name="line_id" placeholder="ระบุไอดีไลน์สำหรับติดต่อกลับ (ไม่บังคับ)" value="<?= sanitize($_POST['line_id'] ?? '') ?>">
        <label>รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)</label>
        <input type="password" name="password" placeholder="ตั้งรหัสผ่านของคุณ" required>
        <button type="submit" class="btn" style="width: 100%;">สมัครสมาชิก</button>
    </form>
    <p style="margin-top:16px; text-align: center;">มีบัญชีสมาชิกอยู่แล้ว? <a href="login.php" style="font-weight: bold;">เข้าสู่ระบบที่นี่</a></p>
</div>
<?php pageFooter();
