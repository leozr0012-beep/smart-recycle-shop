<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

$phone = sanitize($_GET['phone'] ?? '');
$order = null;
$shipment = null;
$shipmentLogs = [];

if ($phone) {
    $stmt = $mysqli->prepare('SELECT o.*, u.name FROM orders o JOIN users u ON u.id = o.user_id WHERE o.phone_contact = ? ORDER BY o.created_at DESC LIMIT 1');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        $stmt = $mysqli->prepare('SELECT * FROM shipments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->bind_param('i', $order['id']);
        $stmt->execute();
        $shipment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($shipment) {
            $stmt = $mysqli->prepare('SELECT * FROM shipment_logs WHERE shipment_id = ? ORDER BY created_at DESC');
            $stmt->bind_param('i', $shipment['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $shipmentLogs[] = $row;
            }
            $stmt->close();
        }
    }
}

pageHeader('ติดตามพัสดุ');
?>
<div class="card" style="max-width: 720px; margin: auto;">
    <h2>ติดตามสถานะการจัดส่ง</h2>
    <p>กรอกเบอร์โทรศัพท์ของคุณเพื่อตรวจสอบสถานะคำสั่งซื้อ รายละเอียดพัสดุ และประวัติการเดินทางของสินค้า</p>
    <form method="get">
        <label>เบอร์โทรศัพท์ที่ลงทะเบียน</label>
        <input type="text" name="phone" value="<?= $phone ?>" placeholder="เช่น 0912345678" required>
        <button type="submit" class="btn" style="width: 100%;">ค้นหาข้อมูล</button>
    </form>
    <?php if ($phone): ?>
        <?php if (!$order): ?>
            <div class="alert" style="margin-top: 24px;">ไม่พบรายการสั่งซื้อสำหรับเบอร์โทรศัพท์นี้</div>
        <?php else: 
            $statusTranslations = [
                'pending' => 'รอดำเนินการ',
                'paid' => 'ชำระเงินแล้ว',
                'shipping' => 'กำลังจัดส่ง',
                'done' => 'เสร็จสิ้น/ส่งมอบแล้ว'
            ];
            $translatedStatus = $statusTranslations[$order['status']] ?? ucfirst($order['status']);
        ?>
            <div class="status-card" style="margin-top:24px;">
                <h3>หมายเลขคำสั่งซื้อ #<?= sanitize($order['id']) ?></h3>
                <p><strong>ชื่อผู้สั่งซื้อ:</strong> <?= sanitize($order['name']) ?></p>
                <p><strong>สถานะคำสั่งซื้อ:</strong> <span class="badge status-<?= sanitize($order['status']) ?>"><?= sanitize($translatedStatus) ?></span></p>
                <p><strong>ยอดชำระสุทธิ:</strong> ฿<?= number_format($order['total_amount'], 2) ?></p>
                <p><strong>ค่าจัดส่ง:</strong> ฿<?= number_format($order['shipping_cost'], 2) ?></p>
                <p><strong>สั่งซื้อเมื่อ:</strong> <?= sanitize($order['created_at']) ?></p>
            </div>

            <?php if ($shipment): 
                $shipmentStatusTranslations = [
                    'pending' => 'รอจัดเตรียมพัสดุ',
                    'shipping' => 'ระหว่างนำส่งพัสดุ',
                    'delivered' => 'จัดส่งพัสดุเรียบร้อยแล้ว'
                ];
                $translatedShipmentStatus = $shipmentStatusTranslations[$shipment['status']] ?? ucfirst($shipment['status']);
            ?>
                <div class="status-card" style="margin-top:20px; border-left: 4px solid var(--color-primary, #10b981);">
                    <h3>ข้อมูลการจัดส่งพัสดุ</h3>
                    <p><strong>หมายเลขพัสดุ (Tracking):</strong> <span style="font-family: monospace; font-size:1.1rem; color:var(--color-secondary, #6366f1); font-weight:bold;"><?= sanitize($shipment['tracking_code']) ?></span></p>
                    <p><strong>สถานะการนำส่ง:</strong> <span class="badge status-<?= sanitize($shipment['status']) ?>"><?= sanitize($translatedShipmentStatus) ?></span></p>
                    <p><strong>อัปเดตล่าสุดเมื่อ:</strong> <?= sanitize($shipment['updated_at']) ?></p>
                    
                    <?php if ($shipmentLogs): ?>
                        <div style="margin-top: 20px;">
                            <h4>บันทึกสถานะพัสดุ (Timeline)</h4>
                            <div class="timeline" style="margin-top: 10px; border-left: 2px solid #e2e8f0; padding-left: 15px; margin-left: 5px;">
                                <?php foreach ($shipmentLogs as $log): ?>
                                    <div class="timeline-item" style="margin-bottom: 15px; position: relative;">
                                        <span class="timeline-dot" style="position: absolute; left: -21px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--color-primary, #10b981);"></span>
                                        <div style="font-size: 0.85rem; color: #64748b;"><?= sanitize($log['created_at']) ?></div>
                                        <div style="font-weight: 500; color: #1e293b;"><?= sanitize($log['note']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php pageFooter();
