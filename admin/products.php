<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
adminGuard();

if (!empty($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    if ($deleteId) {
        $stmt = $mysqli->prepare('DELETE FROM product_specs WHERE product_id = ?');
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $stmt->close();
        $stmt = $mysqli->prepare('DELETE FROM product_images WHERE product_id = ?');
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $stmt->close();
        $stmt = $mysqli->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $stmt->close();
    }
    redirect(BASE_URL . '/admin/products.php');
}

$products = [];
$stmt = $mysqli->prepare('SELECT p.*, COUNT(pi.id) as image_count FROM products p LEFT JOIN product_images pi ON pi.product_id = p.id GROUP BY p.id ORDER BY p.created_at DESC');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

adminHeader('จัดการข้อมูลสินค้า');
?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2>รายการสินค้าทั้งหมด</h2>
        <a class="btn" href="product_form.php">➕ เพิ่มสินค้าใหม่</a>
    </div>
    <div class="table-card" style="margin-top:16px;">
        <table>
            <thead>
                <tr><th>ชื่อสินค้า</th><th>หมวดหมู่</th><th>ราคาขาย</th><th>สถานะแสดงผล</th><th>จำนวนรูป</th><th>การจัดการ</th></tr>
            </thead>
            <tbody>
                <?php if (!$products): ?>
                    <tr><td colspan="6">ไม่พบข้อมูลสินค้าในระบบในขณะนี้</td></tr>
                <?php else: foreach ($products as $item): 
                    $catTranslations = [
                        'fridge' => 'ตู้เย็น',
                        'washer' => 'เครื่องซักผ้า',
                        'ac' => 'แอร์',
                        'tv' => 'ทีวี'
                    ];
                    $translatedCat = $catTranslations[$item['category']] ?? ucfirst($item['category']);
                    $translatedStatus = $item['status'] ? 'แสดงผล' : 'ซ่อนไว้';
                ?>
                    <tr>
                        <td><strong><?= sanitize($item['name']) ?></strong></td>
                        <td><?= sanitize($translatedCat) ?></td>
                        <td>
                            ฿<?= number_format($item['price'], 2) ?>
                            <?php if ($item['original_price'] !== null && $item['original_price'] > $item['price']): ?>
                                <br><span style="text-decoration: line-through; color: var(--text-muted); font-size: 0.85rem;">฿<?= number_format($item['original_price'], 2) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $item['status'] ? 'status-paid' : 'status-pending' ?>" style="padding: 4px 10px; font-size: 0.8rem;">
                                <?= $translatedStatus ?>
                            </span>
                        </td>
                        <td><?= sanitize($item['image_count']) ?> ภาพ</td>
                        <td>
                            <a href="product_form.php?id=<?= $item['id'] ?>" style="font-weight: 600; color: var(--primary);">แก้ไข</a> |
                            <a href="products.php?delete=<?= $item['id'] ?>" onclick="return confirm('ยืนยันที่จะลบสินค้านี้ใช่หรือไม่? ข้อมูลรูปภาพและสเปคจะถูกลบทั้งหมด');" style="font-weight: 600; color: #ef4444;">ลบออก</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php adminFooter();
