<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
adminGuard();

$product = [
    'name' => '',
    'category' => '',
    'grade' => 'A',
    'color' => '',
    'price' => 0,
    'original_price' => null,
    'stock' => 1,
    'featured' => 0,
    'status' => 1,
    'short_description' => '',
    'description' => '',
    'warranty_info' => '30 days warranty',
    'line_contact' => '',
];
$productImages = [];
$productSpecs = [];
$errors = [];
$success = '';
$productId = intval($_GET['id'] ?? 0);

if ($productId) {
    $stmt = $mysqli->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc() ?: $product;
    $stmt->close();
    $stmt = $mysqli->prepare('SELECT url FROM product_images WHERE product_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $productImages[] = $row['url']; }
    $stmt->close();
    $stmt = $mysqli->prepare('SELECT spec_key, spec_value FROM product_specs WHERE product_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $productSpecs[] = $row; }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product['name'] = sanitize($_POST['name'] ?? '');
    $product['category'] = sanitize($_POST['category'] ?? '');
    $product['grade'] = sanitize($_POST['grade'] ?? 'A');
    $product['color'] = sanitize($_POST['color'] ?? '');
    $product['price'] = floatval($_POST['price'] ?? 0);
    $origPrice = trim($_POST['original_price'] ?? '');
    $product['original_price'] = $origPrice !== '' ? floatval($origPrice) : null;
    $product['stock'] = intval($_POST['stock'] ?? 1);
    $product['featured'] = isset($_POST['featured']) ? 1 : 0;
    $product['status'] = isset($_POST['status']) ? 1 : 0;
    $product['short_description'] = sanitize($_POST['short_description'] ?? '');
    $product['description'] = sanitize($_POST['description'] ?? '');
    $product['warranty_info'] = sanitize($_POST['warranty_info'] ?? '30 days warranty');
    $product['line_contact'] = sanitize($_POST['line_contact'] ?? '');
    $productImages = [];
    for ($i = 1; $i <= 3; $i++) {
        $fileField = 'image_file' . $i;
        $textField = 'image' . $i;
        if (!empty($_FILES[$fileField]['name']) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES[$fileField]['tmp_name'];
            $fileName = time() . '_' . uniqid() . '_' . basename($_FILES[$fileField]['name']);
            $targetDir = __DIR__ . '/../assets/images/products/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $targetPath = $targetDir . $fileName;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $productImages[] = BASE_URL . '/assets/images/products/' . $fileName;
            }
        } else {
            $url = sanitize($_POST[$textField] ?? '');
            if ($url) {
                $productImages[] = $url;
            }
        }
    }
    $specLines = array_filter(array_map('trim', explode("\n", $_POST['specs'] ?? '')));

    if (!$product['name'] || !$product['category'] || !$product['price']) {
        $errors[] = 'กรุณากรอกข้อมูล ชื่อสินค้า, หมวดหมู่สินค้า และราคาขาย';
    }

    if (empty($errors)) {
        if ($productId) {
            $stmt = $mysqli->prepare('UPDATE products SET name = ?, category = ?, grade = ?, color = ?, price = ?, original_price = ?, stock = ?, featured = ?, status = ?, short_description = ?, description = ?, warranty_info = ?, line_contact = ? WHERE id = ?');
            $stmt->bind_param('ssssddiiissssi', $product['name'], $product['category'], $product['grade'], $product['color'], $product['price'], $product['original_price'], $product['stock'], $product['featured'], $product['status'], $product['short_description'], $product['description'], $product['warranty_info'], $product['line_contact'], $productId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $mysqli->prepare('INSERT INTO products (name, category, grade, color, price, original_price, stock, featured, status, short_description, description, warranty_info, line_contact, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->bind_param('ssssddiiissss', $product['name'], $product['category'], $product['grade'], $product['color'], $product['price'], $product['original_price'], $product['stock'], $product['featured'], $product['status'], $product['short_description'], $product['description'], $product['warranty_info'], $product['line_contact']);
            $stmt->execute();
            $productId = $stmt->insert_id;
            $stmt->close();
        }

        if ($productId) {
            $stmt = $mysqli->prepare('DELETE FROM product_images WHERE product_id = ?');
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $stmt->close();
            foreach ($productImages as $url) {
                $stmt = $mysqli->prepare('INSERT INTO product_images (product_id, url, created_at) VALUES (?, ?, NOW())');
                $stmt->bind_param('is', $productId, $url);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $mysqli->prepare('DELETE FROM product_specs WHERE product_id = ?');
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $stmt->close();
            foreach ($specLines as $line) {
                [$key, $value] = array_pad(array_map('trim', explode(':', $line, 2)), 2, '');
                if ($key && $value) {
                    $stmt = $mysqli->prepare('INSERT INTO product_specs (product_id, spec_key, spec_value) VALUES (?, ?, ?)');
                    $stmt->bind_param('iss', $productId, $key, $value);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $success = 'บันทึกข้อมูลสินค้าเรียบร้อยแล้ว';
            redirect(BASE_URL . '/admin/products.php');
        }
    }
}

adminHeader($productId ? 'แก้ไขข้อมูลสินค้า' : 'เพิ่มสินค้าใหม่');
if ($errors) { echo '<div class="alert">' . implode('<br>', $errors) . '</div>'; }
?>
<div class="card">
    <h2><?= $productId ? 'แก้ไขข้อมูลสินค้า (Edit Product)' : 'เพิ่มสินค้าใหม่ (New Product)' ?></h2>
    <form method="post" enctype="multipart/form-data" class="grid-2" style="margin-top: 20px;">
        <div>
            <label>ชื่อเครื่องใช้ไฟฟ้า</label>
            <input type="text" name="name" value="<?= sanitize($product['name']) ?>" placeholder="เช่น ตู้เย็น LG Smart Inverter 11 คิว" required>
            
            <label>หมวดหมู่สินค้า</label>
            <select name="category" required>
                <option value="">-- เลือกหมวดหมู่ --</option>
                <option value="fridge" <?= $product['category'] === 'fridge' ? 'selected' : '' ?>>ตู้เย็น (Fridge)</option>
                <option value="washer" <?= $product['category'] === 'washer' ? 'selected' : '' ?>>เครื่องซักผ้า (Washer)</option>
                <option value="ac" <?= $product['category'] === 'ac' ? 'selected' : '' ?>>แอร์ (AC)</option>
                <option value="tv" <?= $product['category'] === 'tv' ? 'selected' : '' ?>>ทีวี (TV)</option>
            </select>
            
            <label>ระดับเกรดสินค้า</label>
            <select name="grade">
                <option value="A" <?= $product['grade'] === 'A' ? 'selected' : '' ?>>เกรด A (สภาพเหมือนใหม่)</option>
                <option value="B" <?= $product['grade'] === 'B' ? 'selected' : '' ?>>เกรด B (มีรอยเล็กน้อย)</option>
                <option value="C" <?= $product['grade'] === 'C' ? 'selected' : '' ?>>เกรด C (ใช้งานได้ปกติ มีรอยชัดเจน)</option>
            </select>
            
            <label>สีของสินค้า</label>
            <input type="text" name="color" value="<?= sanitize($product['color']) ?>" placeholder="เช่น silver, white, black">
            
            <label>ราคาขายปัจจุบัน (บาท)</label>
            <input type="number" name="price" min="0" step="0.01" value="<?= sanitize($product['price']) ?>" required>
            
            <label>ราคาปกติก่อนลดราคา (บาท - เว้นว่างได้หากไม่มีส่วนลด)</label>
            <input type="number" name="original_price" min="0" step="0.01" value="<?= sanitize($product['original_price'] !== null ? $product['original_price'] : '') ?>">
            
            <label>จำนวนสินค้าในคลัง</label>
            <input type="number" name="stock" min="0" value="<?= sanitize($product['stock']) ?>">
            
            <div style="display: flex; gap: 24px; margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                    <input type="checkbox" name="featured" <?= $product['featured'] ? 'checked' : '' ?> style="width: auto; margin-bottom: 0;">
                    สินค้าแนะนำ (Featured)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                    <input type="checkbox" name="status" <?= $product['status'] ? 'checked' : '' ?> style="width: auto; margin-bottom: 0;">
                    เปิดแสดงหน้าร้าน (Active)
                </label>
            </div>
        </div>
        <div>
            <label>รายละเอียดสินค้าแบบย่อ</label>
            <textarea name="short_description" rows="3" placeholder="ระบุรายละเอียดสำคัญเพื่อแสดงในหน้ารายการสินค้า"><?= sanitize($product['short_description']) ?></textarea>
            
            <label>รายละเอียดสินค้าแบบเต็ม</label>
            <textarea name="description" rows="5" placeholder="ระบุการทำงาน จุดเด่น และข้อแนะนำของสินค้า"><?= sanitize($product['description']) ?></textarea>
            
            <label>ข้อมูลการรับประกันสินค้า</label>
            <input type="text" name="warranty_info" value="<?= sanitize($product['warranty_info']) ?>" placeholder="เช่น รับประกันคอมเพรสเซอร์ 1 ปี">
            
            <label>ช่องทางการติดต่อ LINE</label>
            <input type="text" name="line_contact" value="<?= sanitize($product['line_contact']) ?>" placeholder="เช่น line_user_id">
            
            <label>รูปภาพประกอบที่ 1</label>
            <input type="file" name="image_file1" accept="image/*" style="margin-bottom: 8px;">
            <input type="hidden" name="image1" value="<?= sanitize($productImages[0] ?? '') ?>">
            <?php if (!empty($productImages[0])): ?>
                <img src="<?= sanitize($productImages[0]) ?>" style="max-height: 80px; border-radius: 8px; display: block; margin-bottom: 16px; object-fit: cover;">
            <?php endif; ?>

            <label>รูปภาพประกอบที่ 2</label>
            <input type="file" name="image_file2" accept="image/*" style="margin-bottom: 8px;">
            <input type="hidden" name="image2" value="<?= sanitize($productImages[1] ?? '') ?>">
            <?php if (!empty($productImages[1])): ?>
                <img src="<?= sanitize($productImages[1]) ?>" style="max-height: 80px; border-radius: 8px; display: block; margin-bottom: 16px; object-fit: cover;">
            <?php endif; ?>

            <label>รูปภาพประกอบที่ 3</label>
            <input type="file" name="image_file3" accept="image/*" style="margin-bottom: 8px;">
            <input type="hidden" name="image3" value="<?= sanitize($productImages[2] ?? '') ?>">
            <?php if (!empty($productImages[2])): ?>
                <img src="<?= sanitize($productImages[2]) ?>" style="max-height: 80px; border-radius: 8px; display: block; margin-bottom: 16px; object-fit: cover;">
            <?php endif; ?>
            
            <label>คุณสมบัติทางเทคนิค (พิมพ์แยกบรรทัดละ 1 ข้อ เช่น ความจุ: 320 ลิตร)</label>
            <textarea name="specs" rows="4" placeholder="หัวข้อ:รายละเอียด&#10;Capacity: 320L&#10;Type: Frost Free"><?= sanitize(implode("\n", array_map(function($item){ return $item['spec_key'] . ': ' . $item['spec_value']; }, $productSpecs))) ?></textarea>
            
            <button type="submit" class="btn" style="width: 100%;">💾 บันทึกข้อมูลสินค้า</button>
        </div>
    </form>
</div>
<?php adminFooter();
