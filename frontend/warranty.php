<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

$phone = sanitize($_GET['phone'] ?? '');
$warranties = [];

if ($phone) {
    $stmt = $mysqli->prepare('SELECT w.*, u.name, p.name AS product_name FROM warranties w JOIN users u ON u.id = w.user_id JOIN products p ON p.id = w.product_id WHERE u.phone = ? ORDER BY w.created_at DESC');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $warranties[] = $row;
    }
    $stmt->close();
}

pageHeader('เช็คประกันสินค้า');
?>
<div class="card" style="max-width: 720px; margin: auto;">
    <h2>ตรวจสอบการรับประกันเครื่องใช้ไฟฟ้า</h2>
    <p>ระบุเบอร์โทรศัพท์ของคุณเพื่อดึงข้อมูลสถานะการรับประกัน วันเริ่มต้นและวันสิ้นสุดการรับคุ้มครองสินค้าทั้งหมด</p>
    <form method="get">
        <label>เบอร์โทรศัพท์สมาชิก</label>
        <input type="text" name="phone" value="<?= $phone ?>" placeholder="เช่น 0912345678" required>
        <button type="submit" class="btn" style="width: 100%;">ตรวจสอบข้อมูล</button>
    </form>
    <?php if ($phone): ?>
        <?php if (empty($warranties)): ?>
            <div class="alert" style="margin-top: 24px;">ไม่พบข้อมูลประวัติการรับประกันเครื่องใช้ไฟฟ้าสำหรับเบอร์โทรศัพท์นี้</div>
        <?php else: ?>
            <h3 style="margin-top: 24px; margin-bottom: 12px; color: var(--text-primary);">พบประวัติการรับประกันทั้งหมด <?= count($warranties) ?> รายการ</h3>
            <?php foreach ($warranties as $warranty): 
                $isExpired = strtotime($warranty['end_date']) < time() || $warranty['status'] === 'expired';
                $statusClass = $isExpired ? 'status-pending' : 'status-paid'; // pending style (amber/red) vs paid style (green)
                $statusText = $isExpired ? 'หมดอายุการรับประกัน' : 'อยู่ในการรับประกัน';
            ?>
                <div class="status-card" style="margin-top:16px; border-left: 4px solid <?= $isExpired ? '#ef4444' : '#10b981' ?>;">
                    <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:8px;">
                        <h4 style="font-size:1.1rem; color:var(--text-primary);"><?= sanitize($warranty['product_name']) ?></h4>
                        <span class="badge <?= $statusClass ?>" style="padding: 4px 10px; font-size: 0.8rem;"><?= $statusText ?></span>
                    </div>
                    <div style="margin-top: 10px; font-size:0.95rem; color:var(--text-secondary);">
                        <p><strong>ผู้สั่งซื้อ:</strong> <?= sanitize($warranty['name']) ?></p>
                        <p><strong>หมายเลขคำสั่งซื้อ:</strong> #<?= sanitize($warranty['order_id']) ?></p>
                        <p><strong>วันเริ่มต้นการรับประกัน:</strong> <?= sanitize($warranty['start_date']) ?></p>
                        <p><strong>วันสิ้นสุดการรับประกัน:</strong> <?= sanitize($warranty['end_date']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php pageFooter();
