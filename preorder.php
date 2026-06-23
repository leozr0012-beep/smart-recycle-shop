<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

// Check GET parameters from 3D customizer
$customType = sanitize($_GET['type'] ?? '');
$doorColor = sanitize($_GET['door_color'] ?? '');
$bodyColor = sanitize($_GET['body_color'] ?? '');
$handleFinish = sanitize($_GET['handle_finish'] ?? '');
$engraving = sanitize($_GET['engraving'] ?? '');

$isCustomFridge = ($customType === 'fridge_custom');

$defaultBudget = '';
$defaultBrand = '';
if ($isCustomFridge) {
    $defaultBudget = '16,990 บาท (สั่งผลิตสีพิเศษ)';
    $defaultBrand = "Custom Fridge (สีประตู: $doorColor, สีตัวเครื่อง: $bodyColor, มือจับ: $handleFinish";
    if ($engraving) {
        $defaultBrand .= ", สลักชื่อ: $engraving";
    }
    $defaultBrand .= ")";
}

$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productType = sanitize($_POST['product_type'] ?? '');
    $budgetRange = sanitize($_POST['budget_range'] ?? '');
    $brandPreference = sanitize($_POST['brand_preference'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $lineId = sanitize($_POST['line_id'] ?? '');

    if (!$productType || !$budgetRange || !$phone) {
        $errors[] = 'กรุณากรอกประเภทสินค้า งบประมาณ และเบอร์โทรศัพท์สำหรับติดต่อกลับ';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email) {
        $errors[] = 'กรุณากรอกรูปแบบอีเมลให้ถูกต้อง';
    }
    if (empty($errors)) {
        $stmt = $mysqli->prepare('INSERT INTO preorders (product_type, budget_range, brand_preference, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('sss', $productType, $budgetRange, $brandPreference);
        if ($stmt->execute()) {
            $preorderId = $stmt->insert_id;
            $stmt->close();
            $stmt = $mysqli->prepare('INSERT INTO preorder_contacts (preorder_id, phone, email, line_id, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->bind_param('isss', $preorderId, $phone, $email, $lineId);
            $stmt->execute();
            $stmt->close();
            $success = 'บันทึกข้อมูลการสั่งจองสินค้าล่วงหน้าสำเร็จแล้ว ทีมงานของเราจะทำการติดต่อกลับหาคุณโดยเร็วที่สุด';
        } else {
            $errors[] = 'ไม่สามารถส่งคำขอจองล่วงหน้าได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง';
        }
    }
}

pageHeader('Preorder');
if ($success) { echo '<div class="alert" style="background:#d1fae5;color:#065f46;">' . sanitize($success) . '</div>'; }
if ($errors) { echo '<div class="alert">' . implode('<br>', $errors) . '</div>'; }
?>
<div class="card" style="max-width: 720px; margin: auto;">
    <h2>สั่งจองเครื่องใช้ไฟฟ้าล่วงหน้า (Preorder)</h2>
    <p>แจ้งประเภทสินค้าและงบประมาณที่คุณต้องการ ทีมงานของเราจะจัดหาเครื่องใช้ไฟฟ้าคุณภาพเยี่ยมตามงบของคุณและติดต่อกลับหาคุณโดยเร็วที่สุด</p>
    <form method="post" style="margin-top: 20px;">
        <label>ประเภทเครื่องใช้ไฟฟ้า</label>
        <select name="product_type" required>
            <option value="">เลือกประเภทเครื่องใช้ไฟฟ้า</option>
            <option value="fridge" <?= ($isCustomFridge || ($_POST['product_type'] ?? '') === 'fridge') ? 'selected' : '' ?>>ตู้เย็น (Fridge)</option>
            <option value="washer" <?= (($_POST['product_type'] ?? '') === 'washer') ? 'selected' : '' ?>>เครื่องซักผ้า (Washer)</option>
            <option value="ac" <?= (($_POST['product_type'] ?? '') === 'ac') ? 'selected' : '' ?>>แอร์ (AC)</option>
            <option value="tv" <?= (($_POST['product_type'] ?? '') === 'tv') ? 'selected' : '' ?>>ทีวี (TV)</option>
        </select>
        <label>งบประมาณที่ต้องการ (บาท)</label>
        <input type="text" name="budget_range" value="<?= sanitize($_POST['budget_range'] ?? $defaultBudget) ?>" placeholder="เช่น 5,000 - 8,000 บาท" required>
        <label>แบรนด์สินค้าที่ต้องการ/รายละเอียดสั่งทำพิเศษ</label>
        <input type="text" name="brand_preference" value="<?= sanitize($_POST['brand_preference'] ?? $defaultBrand) ?>" placeholder="เช่น Samsung, LG หรือระบุสีตู้เย็นสั่งทำพิเศษ">
        <label>เบอร์โทรศัพท์สำหรับติดต่อกลับ</label>
        <input type="text" name="phone" placeholder="เช่น 0912345678" value="<?= sanitize($_POST['phone'] ?? $_SESSION['user']['phone'] ?? '') ?>" required>
        <label>อีเมล</label>
        <input type="email" name="email" placeholder="เช่น customer@example.com (ไม่บังคับ)" value="<?= sanitize($_POST['email'] ?? $_SESSION['user']['email'] ?? '') ?>">
        <label>LINE ID</label>
        <input type="text" name="line_id" placeholder="ระบุไอดีไลน์สำหรับติดต่อกลับ (ไม่บังคับ)" value="<?= sanitize($_POST['line_id'] ?? $_SESSION['user']['line_id'] ?? '') ?>">
        <button type="submit" class="btn" style="width: 100%;">ส่งคำขอสั่งจองสินค้า</button>
    </form>
</div>
<?php pageFooter();
?>
