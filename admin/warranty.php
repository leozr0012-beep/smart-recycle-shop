<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
adminGuard();

$warranties = [];
$stmt = $mysqli->prepare('SELECT w.id, w.order_id, w.start_date, w.end_date, w.status, u.name, p.name AS product_name, o.delivery_address, o.phone_contact FROM warranties w JOIN users u ON u.id = w.user_id JOIN products p ON p.id = w.product_id JOIN orders o ON o.id = w.order_id ORDER BY w.created_at DESC');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $warranties[] = $row;
}
$stmt->close();

adminHeader('การรับประกันสินค้า');
?>
<div class="card">
    <h2>รายการรับประกันสินค้าทั้งหมด</h2>
    <div class="table-card" style="margin-top: 16px;">
        <table>
            <thead>
                <tr><th>#</th><th>สินค้าที่คุ้มครอง</th><th>ข้อมูลลูกค้า</th><th>วันเริ่มต้นคุ้มครอง</th><th>วันสิ้นสุดการรับประกัน</th><th>สถานะประกัน</th></tr>
            </thead>
            <tbody>
                <?php if (!$warranties): ?>
                    <tr><td colspan="6">ไม่พบข้อมูลประวัติการรับประกันในขณะนี้</td></tr>
                <?php else: foreach ($warranties as $item): 
                    $isExpired = strtotime($item['end_date']) < time() || $item['status'] === 'expired';
                    $statusClass = $isExpired ? 'status-pending' : 'status-paid';
                    $statusText = $isExpired ? 'หมดอายุประกัน' : 'คุ้มครองปกติ';
                ?>
                    <tr>
                        <td>#<?= sanitize($item['id']) ?></td>
                        <td><strong><?= sanitize($item['product_name']) ?></strong><br><span style="font-size:0.8rem; color:var(--text-muted);">คำสั่งซื้อ #<?= sanitize($item['order_id']) ?></span></td>
                        <td><?= sanitize($item['name']) ?></td>
                        <td><?= sanitize($item['start_date']) ?></td>
                        <td><?= sanitize($item['end_date']) ?></td>
                        <td><span class="badge <?= $statusClass ?>" style="padding: 4px 10px; font-size: 0.8rem;"><?= $statusText ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminFooter();
