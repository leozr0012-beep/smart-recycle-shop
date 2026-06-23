<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
adminGuard();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['shipment_id']) && !empty($_POST['status'])) {
    $shipmentId = intval($_POST['shipment_id']);
    $status = sanitize($_POST['status']);
    $stmt = $mysqli->prepare('UPDATE shipments SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $status, $shipmentId);
    $stmt->execute();
    $stmt->close();
    
    $statusLogs = [
        'pending' => 'รอจัดเตรียมพัสดุภัณฑ์',
        'shipping' => 'พัสดุถูกส่งออกระหว่างทาง',
        'delivered' => 'จัดส่งพัสดุปลายทางเรียบร้อยแล้ว'
    ];
    $note = $statusLogs[$status] ?? ('สถานะการจัดส่งเปลี่ยนเป็น ' . $status);
    $stmt = $mysqli->prepare('INSERT INTO shipment_logs (shipment_id, status, note, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->bind_param('iss', $shipmentId, $status, $note);
    $stmt->execute();
    $stmt->close();
    redirect(BASE_URL . '/admin/shipments.php');
}

$shipments = [];
$stmt = $mysqli->prepare('SELECT s.id, s.order_id, s.status, s.tracking_code, s.updated_at, o.phone_contact FROM shipments s JOIN orders o ON o.id = s.order_id ORDER BY s.updated_at DESC');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $shipments[] = $row;
}
$stmt->close();

adminHeader('จัดการการจัดส่งสินค้า');
?>
<div class="card">
    <h2>รายการจัดส่งพัสดุทั้งหมด (Shipments)</h2>
    <div class="table-card" style="margin-top: 16px;">
        <table>
            <thead>
                <tr><th>#</th><th>หมายเลขสั่งซื้อ</th><th>เลขพัสดุ (Tracking Code)</th><th>สถานะนำส่ง</th><th>อัปเดตล่าสุด</th><th>การจัดการ</th></tr>
            </thead>
            <tbody>
                <?php if (!$shipments): ?>
                    <tr><td colspan="6">ไม่พบประวัติการจัดส่งพัสดุในระบบขณะนี้</td></tr>
                <?php else: foreach ($shipments as $shipment): 
                    $shipmentStatusTranslations = [
                        'pending' => 'เตรียมจัดส่ง',
                        'shipping' => 'ระหว่างนำส่ง',
                        'delivered' => 'จัดส่งสำเร็จ'
                    ];
                    $translatedShipmentStatus = $shipmentStatusTranslations[$shipment['status']] ?? ucfirst($shipment['status']);
                ?>
                    <tr>
                        <td><?= sanitize($shipment['id']) ?></td>
                        <td>#<?= sanitize($shipment['order_id']) ?></td>
                        <td><span style="font-family: monospace; font-size:1rem; font-weight:bold; color:var(--primary);"><?= sanitize($shipment['tracking_code']) ?></span></td>
                        <td><span class="badge status-<?= sanitize($shipment['status']) ?>"><?= sanitize($translatedShipmentStatus) ?></span></td>
                        <td><?= sanitize($shipment['updated_at']) ?></td>
                        <td>
                            <form method="post" style="display:inline-flex; gap:8px; align-items:center; margin-bottom:0;">
                                <input type="hidden" name="shipment_id" value="<?= $shipment['id'] ?>">
                                <select name="status" style="margin-bottom:0; padding:6px 12px; font-size:0.85rem; width:auto;">
                                    <?php foreach (['pending' => 'เตรียมจัดส่ง', 'shipping' => 'ระหว่างนำส่ง', 'delivered' => 'จัดส่งสำเร็จ'] as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= $shipment['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn" style="padding:6px 16px; font-size:0.85rem; box-shadow:none;">อัปเดต</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminFooter();
