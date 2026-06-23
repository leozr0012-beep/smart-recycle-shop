<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$phone || !$password) {
        $errors[] = 'กรุณากรอกเบอร์โทรศัพท์และรหัสผ่าน';
    }
    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT id, password FROM users WHERE phone = ? LIMIT 1');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                refreshUserSession($mysqli, $user['id']);
                if (isAdmin()) {
                    redirect(BASE_URL . '/admin/dashboard.php');
                } else {
                    redirect(BASE_URL . '/');
                }
            }
        }
        $errors[] = 'เบอร์โทรศัพท์หรือรหัสผ่านไม่ถูกต้อง';
        $stmt->close();
    }
}

pageHeader('Login');
if ($errors) {
    echo '<div class="alert">' . implode('<br>', $errors) . '</div>';
}
?>
<div class="card" style="max-width: 640px; margin: auto;">
    <h2>เข้าสู่ระบบ</h2>
    <p>ระบุเบอร์โทรศัพท์ของคุณเพื่อเข้าสู่ระบบสมาชิก</p>
    <form method="post">
        <label>เบอร์โทรศัพท์</label>
        <input type="text" name="phone" placeholder="เช่น 0912345678" value="<?= sanitize($_POST['phone'] ?? '') ?>" required>
        <label>รหัสผ่าน</label>
        <input type="password" name="password" placeholder="ระบุรหัสผ่านของคุณ" required>
        <button type="submit" class="btn" style="width: 100%;">เข้าสู่ระบบ</button>
    </form>
    <p style="margin-top:16px; text-align: center;">ยังไม่มีบัญชีสมาชิก? <a href="register.php" style="font-weight: bold;">สมัครสมาชิกใหม่ที่นี่</a></p>
</div>
<?php pageFooter();
