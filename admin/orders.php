<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
adminGuard();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['order_id']) && !empty($_POST['status'])) {
    $orderId = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    $stmt = $mysqli->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $orderId);
    $stmt->execute();
    $stmt->close();
    if ($status === 'shipping') {
        $trackingCode = 'TRK' . time();
        $stmt = $mysqli->prepare('INSERT INTO shipments (order_id, status, tracking_code, updated_at) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('iss', $orderId, $status, $trackingCode);
        $stmt->execute();
        $stmt->close();
    }
    $stmt = $mysqli->prepare('INSERT INTO shipment_logs (shipment_id, status, note, created_at) VALUES ((SELECT id FROM shipments WHERE order_id = ? ORDER BY id DESC LIMIT 1), ?, ?, NOW())');
    
    $statusLogs = [
        'pending' => 'เปลี่ยนสถานะเป็น รอดำเนินการ',
        'paid' => 'เปลี่ยนสถานะเป็น ชำระเงินแล้ว',
        'shipping' => 'เปลี่ยนสถานะเป็น กำลังจัดส่ง',
        'done' => 'เปลี่ยนสถานะเป็น ส่งมอบเรียบร้อย'
    ];
    $note = $statusLogs[$status] ?? ('สถานะอัปเดตเป็น ' . $status);
    $stmt->bind_param('iss', $orderId, $status, $note);
    $stmt->execute();
    $stmt->close();
    redirect(BASE_URL . '/admin/orders.php');
}

$orders = [];
$stmt = $mysqli->prepare('SELECT o.id, o.status, o.total_amount, o.shipping_cost, o.discount_amount, o.created_at, o.phone_contact, o.delivery_address, u.name, u.phone FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

adminHeader('จัดการรายการสั่งซื้อ');
?>
<div class="card">
    <h2>คำสั่งซื้อเครื่องใช้ไฟฟ้าทั้งหมด</h2>
    <div class="table-card" style="margin-top: 16px;">
        <table>
            <thead>
                <tr><th>#</th><th>ข้อมูลลูกค้า & ที่อยู่จัดส่ง</th><th>ยอดสุทธิ</th><th>สถานะสั่งซื้อ</th><th>สั่งซื้อเมื่อ</th><th>การจัดการ</th></tr>
            </thead>
            <tbody>
                <?php if (!$orders): ?>
                    <tr><td colspan="6">ไม่พบข้อมูลคำสั่งซื้อในระบบขณะนี้</td></tr>
                <?php else: foreach ($orders as $order): 
                    $statusTranslations = [
                        'pending' => 'รอดำเนินการ',
                        'paid' => 'ชำระเงินแล้ว',
                        'shipping' => 'กำลังจัดส่ง',
                        'done' => 'ส่งมอบเรียบร้อย'
                    ];
                    $translatedStatus = $statusTranslations[$order['status']] ?? ucfirst($order['status']);
                ?>
                    <tr>
                        <td>#<?= sanitize($order['id']) ?></td>
                        <td>
                            <strong><?= sanitize($order['name']) ?></strong><br>
                            <span style="font-size:0.85rem; color:var(--text-muted); display:block; margin-bottom:4px;">📞 <?= sanitize($order['phone_contact'] ?: $order['phone']) ?></span>
                            <?php if (!empty($order['delivery_address'])): ?>
                                <span style="font-size:0.8rem; color:var(--text-secondary); display:block; max-width:240px; white-space:normal; line-height:1.3;">📍 <?= sanitize($order['delivery_address']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><strong>฿<?= number_format($order['total_amount'], 2) ?></strong></td>
                        <td><span class="badge status-<?= sanitize($order['status']) ?>"><?= sanitize($translatedStatus) ?></span></td>
                        <td><?= sanitize($order['created_at']) ?></td>
                        <td>
                            <form method="post" style="display:inline-flex; gap: 8px; align-items: center; margin-bottom: 0;">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" style="margin-bottom:0; padding: 6px 12px; font-size: 0.85rem; width: auto;">
                                    <?php foreach (['pending' => 'รอดำเนินการ', 'paid' => 'ชำระเงินแล้ว', 'shipping' => 'กำลังจัดส่ง', 'done' => 'ส่งมอบเรียบร้อย'] as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= $order['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn" style="padding: 6px 16px; font-size: 0.85rem; box-shadow: none;">อัปเดต</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminFooter();
