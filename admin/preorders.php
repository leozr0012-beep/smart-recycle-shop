<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
adminGuard();

$preorders = [];
$stmt = $mysqli->prepare('SELECT p.id, p.product_type, p.budget_range, p.brand_preference, p.created_at, c.phone, c.email, c.line_id FROM preorders p JOIN preorder_contacts c ON c.preorder_id = p.id ORDER BY p.created_at DESC');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $preorders[] = $row;
}
$stmt->close();

adminHeader('รายการจองสินค้าล่วงหน้า');
?>
<div class="card">
    <h2>คำขอสั่งจองสินค้าล่วงหน้าทั้งหมด (Preorders)</h2>
    <div class="table-card" style="margin-top: 16px;">
        <table>
            <thead>
                <tr><th>#</th><th>ประเภทเครื่องใช้ไฟฟ้า</th><th>งบประมาณที่ต้องการ</th><th>แบรนด์ที่ต้องการ</th><th>เบอร์โทรศัพท์ติดต่อ</th><th>ติดต่อกลับช่องทางอื่น</th><th>ทำรายการเมื่อ</th></tr>
            </thead>
            <tbody>
                <?php if (!$preorders): ?>
                    <tr><td colspan="7">ไม่พบคำขอสั่งจองสินค้าล่วงหน้าในระบบขณะนี้</td></tr>
                <?php else: foreach ($preorders as $item): 
                    $typeTranslations = [
                        'fridge' => 'ตู้เย็น',
                        'washer' => 'เครื่องซักผ้า',
                        'ac' => 'แอร์',
                        'tv' => 'ทีวี'
                    ];
                    $translatedType = $typeTranslations[$item['product_type']] ?? ucfirst($item['product_type']);
                ?>
                    <tr>
                        <td>#<?= sanitize($item['id']) ?></td>
                        <td><strong><?= sanitize($translatedType) ?></strong></td>
                        <td>฿<?= sanitize($item['budget_range']) ?></td>
                        <td><?= sanitize($item['brand_preference'] ?: 'ไม่ระบุ') ?></td>
                        <td><a href="tel:<?= sanitize($item['phone']) ?>" style="font-weight:bold;"><?= sanitize($item['phone']) ?></a></td>
                        <td>
                            <?php if ($item['email']): ?>📧 <?= sanitize($item['email']) ?><br><?php endif; ?>
                            <?php if ($item['line_id']): ?>💬 Line: <?= sanitize($item['line_id']) ?><?php endif; ?>
                            <?php if (!$item['email'] && !$item['line_id']): ?><span style="color:var(--text-muted);">ไม่มีข้อมูลเพิ่มเติม</span><?php endif; ?>
                        </td>
                        <td><?= sanitize($item['created_at']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminFooter();
