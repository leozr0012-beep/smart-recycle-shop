<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

$category = sanitize($_GET['category'] ?? '');
$grade = sanitize($_GET['grade'] ?? '');
$color = sanitize($_GET['color'] ?? '');
$price_min = sanitize($_GET['price_min'] ?? '');
$price_max = sanitize($_GET['price_max'] ?? '');

$whereClause = 'p.status = 1';
$params = [];
$types = '';
if ($category) { $whereClause .= ' AND p.category = ?'; $params[] = $category; $types .= 's'; }
if ($grade) { $whereClause .= ' AND p.grade = ?'; $params[] = $grade; $types .= 's'; }
if ($color) { $whereClause .= ' AND p.color = ?'; $params[] = $color; $types .= 's'; }
if ($price_min !== '') { $whereClause .= ' AND p.price >= ?'; $params[] = $price_min; $types .= 'd'; }
if ($price_max !== '') { $whereClause .= ' AND p.price <= ?'; $params[] = $price_max; $types .= 'd'; }

$sql = "SELECT p.id, p.name, p.category, p.grade, p.color, p.price, p.original_price, p.short_description, (SELECT url FROM product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS image_url FROM products p WHERE {$whereClause} ORDER BY p.created_at DESC";
$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

pageHeader('Products');
?>
<div class="card">
    <h2>ค้นหาเครื่องใช้ไฟฟ้าที่ต้องการ</h2>
    <form method="get" class="grid-2" style="align-items:start;">
        <div>
            <label>หมวดหมู่</label>
            <select name="category">
                <option value="">ทุกหมวดหมู่</option>
                <option value="fridge" <?= $category === 'fridge' ? 'selected' : '' ?>>ตู้เย็น</option>
                <option value="washer" <?= $category === 'washer' ? 'selected' : '' ?>>เครื่องซักผ้า</option>
                <option value="ac" <?= $category === 'ac' ? 'selected' : '' ?>>แอร์</option>
                <option value="tv" <?= $category === 'tv' ? 'selected' : '' ?>>ทีวี</option>
            </select>
        </div>
        <div>
            <label>ระดับคุณภาพ (เกรด)</label>
            <select name="grade">
                <option value="">ทุกระดับเกรด</option>
                <option value="A" <?= $grade === 'A' ? 'selected' : '' ?>>A</option>
                <option value="B" <?= $grade === 'B' ? 'selected' : '' ?>>B</option>
                <option value="C" <?= $grade === 'C' ? 'selected' : '' ?>>C</option>
            </select>
        </div>
        <div>
            <label>สีสินค้า</label>
            <select name="color">
                <option value="">ทุกสี</option>
                <option value="white" <?= $color === 'white' ? 'selected' : '' ?>>สีขาว (White)</option>
                <option value="black" <?= $color === 'black' ? 'selected' : '' ?>>สีดำ (Black)</option>
                <option value="silver" <?= $color === 'silver' ? 'selected' : '' ?>>สีเงิน (Silver)</option>
                <option value="blue" <?= $color === 'blue' ? 'selected' : '' ?>>สีน้ำเงิน (Blue)</option>
            </select>
        </div>
        <div>
            <label>ราคาเริ่มต้น (บาท)</label>
            <input type="number" name="price_min" min="0" step="0.01" value="<?= $price_min ?>">
            <label>ราคาสูงสุด (บาท)</label>
            <input type="number" name="price_max" min="0" step="0.01" value="<?= $price_max ?>">
        </div>
        <div>
            <button type="submit" class="btn" style="margin-top: 8px;">กรองข้อมูลสินค้า</button>
        </div>
    </form>
</div>
<div class="grid-3">
    <?php if (empty($products)): ?>
        <div class="card" style="grid-column: 1/-1;"><p>ไม่พบเครื่องใช้ไฟฟ้าที่ตรงตามเงื่อนไขที่ค้นหา กรุณาลองเลือกใหม่อีกครั้ง</p></div>
    <?php else: foreach ($products as $product): ?>
        <div class="product-card">
            <?php if ($product['original_price'] !== null && $product['original_price'] > $product['price']): ?>
                <?php 
                    $discountPercent = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                ?>
                <div class="badge-sale">ลด <?= $discountPercent ?>%</div>
            <?php endif; ?>
            <div class="product-card-img-container">
                <img src="<?= sanitize($product['image_url'] ?: 'https://via.placeholder.com/420x280?text=Product') ?>" alt="<?= sanitize($product['name']) ?>">
            </div>
            <h3><?= sanitize($product['name']) ?></h3>
            <p><?= sanitize($product['short_description']) ?></p>
            <p style="font-size: 0.85rem; margin-bottom: 12px;">
                <span class="badge status-done" style="padding: 2px 8px; font-size: 0.75rem;">เกรด <?= sanitize($product['grade'] ?? 'A') ?></span>
                <span class="badge status-shipping" style="padding: 2px 8px; font-size: 0.75rem;"><?= sanitize(ucfirst($product['category'] === 'fridge' ? 'ตู้เย็น' : ($product['category'] === 'washer' ? 'เครื่องซักผ้า' : ($product['category'] === 'ac' ? 'แอร์' : 'ทีวี')))) ?></span>
            </p>
            <div class="price-tag">
                <?php if ($product['original_price'] !== null && $product['original_price'] > $product['price']): ?>
                    <span class="price-original">฿<?= number_format($product['original_price'], 2) ?></span>
                    <span class="price-current">฿<?= number_format($product['price'], 2) ?></span>
                <?php else: ?>
                    ฿<?= number_format($product['price'], 2) ?>
                <?php endif; ?>
            </div>
            <a class="btn" href="product.php?id=<?= $product['id'] ?>" style="width: 100%;">ดูรายละเอียด</a>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php pageFooter();
