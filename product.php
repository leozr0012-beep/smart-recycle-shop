<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

$productId = intval($_GET['id'] ?? 0);
if (!$productId) {
    redirect(BASE_URL . '/products.php');
}

$stmt = $mysqli->prepare('SELECT * FROM products WHERE id = ? AND status = 1 LIMIT 1');
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) {
    redirect(BASE_URL . '/products.php');
}

$images = [];
$stmt = $mysqli->prepare('SELECT url FROM product_images WHERE product_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $images[] = $row['url'];
}
$stmt->close();

$specs = [];
$stmt = $mysqli->prepare('SELECT spec_key, spec_value FROM product_specs WHERE product_id = ?');
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $specs[] = $row;
}
$stmt->close();

$success = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
    $distance = floatval($_POST['distance_km'] ?? 0);
    $deliveryAddress = sanitize($_POST['delivery_address'] ?? '');
    $phoneContact = sanitize($_POST['phone_contact'] ?? '');

    if ($distance < 0 || !$deliveryAddress) {
        $errors[] = 'กรุณาระบุระยะทางและที่อยู่จัดส่งที่ถูกต้อง';
    }
    if (!$phoneContact) {
        $errors[] = 'กรุณาระบุเบอร์โทรศัพท์สำหรับติดต่อ';
    }
    
    if (empty($errors)) {
        $userId = getCurrentUserId();
        $shippingCost = calculateShipping($distance);
        $discount = isFirstOrder($mysqli, $userId) ? round($product['price'] * FIRST_ORDER_DISCOUNT, 2) : 0;
        $totalAmount = round($product['price'] + $shippingCost - $discount, 2);

        $mysqli->begin_transaction();
        try {
            $orderStatus = 'pending';
            $stmt = $mysqli->prepare('INSERT INTO orders (user_id, status, total_amount, shipping_cost, discount_amount, distance_km, created_at, phone_contact, delivery_address) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)');
            $stmt->bind_param('isddddss', $userId, $orderStatus, $totalAmount, $shippingCost, $discount, $distance, $phoneContact, $deliveryAddress);
            $stmt->execute();
            $orderId = $stmt->insert_id;
            $stmt->close();

            $quantity = 1;
            $itemTotal = round($product['price'] * $quantity, 2);
            $stmt = $mysqli->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iiidd', $orderId, $productId, $quantity, $product['price'], $itemTotal);
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare('INSERT INTO payments (order_id, amount, payment_method, paid_at, created_at) VALUES (?, ?, ?, NOW(), NOW())');
            $method = 'online';
            $stmt->bind_param('ids', $orderId, $totalAmount, $method);
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare('INSERT INTO warranties (order_id, user_id, product_id, start_date, end_date, created_at) VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())');
            $stmt->bind_param('iii', $orderId, $userId, $productId);
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare('INSERT INTO admin_logs (admin_id, action, record_type, record_id, created_at) VALUES (?, ?, ?, ?, NOW())');
            $adminId = 0;
            $action = 'Order created from product page';
            $recordType = 'order';
            $stmt->bind_param('issi', $adminId, $action, $recordType, $orderId);
            $stmt->execute();
            $stmt->close();

            $mysqli->commit();
            $success = 'บันทึกคำสั่งซื้อเรียบร้อยแล้ว สถานะคำสั่งซื้อของคุณคือรอดำเนินการ';
        } catch (Exception $e) {
            $mysqli->rollback();
            $errors[] = 'ไม่สามารถส่งคำสั่งซื้อได้ กรุณาลองใหม่อีกครั้ง';
        }
    }
}

pageHeader(sanitize($product['name']));
?>
<!-- Leaflet Map CSS/JS Library -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php
if ($success) {
    echo '<div class="alert" style="background:#d1fae5;color:#065f46;">' . sanitize($success) . '</div>';
}
if ($errors) {
    echo '<div class="alert">' . implode('<br>', $errors) . '</div>';
}
?>
<div class="grid-2">
    <div class="card">
        <h2><?= sanitize($product['name']) ?></h2>
        <p><?= sanitize($product['short_description']) ?></p>
        <p><strong>หมวดหมู่:</strong> <?= sanitize($product['category'] === 'fridge' ? 'ตู้เย็น' : ($product['category'] === 'washer' ? 'เครื่องซักผ้า' : ($product['category'] === 'ac' ? 'แอร์' : 'ทีวี'))) ?> · <strong>เกรด:</strong> <?= sanitize($product['grade']) ?> · <strong>สี:</strong> <?= sanitize($product['color'] === 'white' ? 'สีขาว' : ($product['color'] === 'black' ? 'สีดำ' : ($product['color'] === 'silver' ? 'สีเงิน' : 'สีผสม'))) ?></p>
        <p><strong>ราคา:</strong> 
            <?php if ($product['original_price'] !== null && $product['original_price'] > $product['price']): ?>
                <?php 
                    $discountPercent = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                ?>
                <span class="price-original" style="font-size: 1.1rem; text-decoration: line-through; color: var(--text-muted); margin-right: 8px;">฿<?= number_format($product['original_price'], 2) ?></span>
                <span class="price-current" style="font-size: 1.5rem; color: #ef4444; font-weight: 800;">฿<?= number_format($product['price'], 2) ?></span>
                <span class="badge" style="background: #ef4444; color: #fff; margin-left: 8px; font-weight: bold; vertical-align: middle;">ลด <?= $discountPercent ?>%</span>
            <?php else: ?>
                <span style="font-size: 1.3rem; font-weight: bold; color: var(--primary);">฿<?= number_format($product['price'], 2) ?></span>
            <?php endif; ?>
        </p>
        <p><strong>การรับประกัน:</strong> รับประกันสินค้า 30 วันหลังการซื้อ</p>
        <p><strong>ติดต่อ LINE:</strong> <a href="https://line.me/ti/p/<?= sanitize($product['line_contact'] ?? '') ?>" target="_blank" style="color: #22c55e; font-weight: bold;">แชทคุยผ่าน LINE</a></p>
        <?php if (!empty($product['phone_contact'])): ?>
        <p><strong>เบอร์โทรติดต่อ:</strong> <a href="tel:<?= sanitize($product['phone_contact']) ?>" style="color: var(--primary); font-weight: bold;"> <?= sanitize($product['phone_contact']) ?> </a></p>
        <?php endif; ?>
        <?php if (!empty($product['address'])): ?>
        <div style="margin-top:16px;">
            <iframe src="https://www.google.com/maps/embed/v1/place?key=YOUR_GOOGLE_MAPS_API_KEY&q=<?= urlencode($product['address']) ?>" width="100%" height="240" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
        <?php endif; ?>
        <div class="grid-2" style="gap:16px; margin-top:20px;">
            <?php foreach ($images ?: ['https://via.placeholder.com/420x280?text=Photo'] as $image): ?>
                <img src="<?= sanitize($image) ?>" alt="Gallery" style="width:100%;border-radius:18px; max-height:220px; object-fit:cover;">
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <h2>ข้อมูลทางเทคนิค</h2>
        <?php if ($specs): ?>
            <ul style="padding-left:18px; color:#475569; margin-bottom:20px;">
                <?php foreach ($specs as $spec): ?>
                    <li><strong><?= sanitize($spec['spec_key']) ?>:</strong> <?= sanitize($spec['spec_value']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>ไม่มีข้อมูลทางเทคนิคเพิ่มเติม</p>
        <?php endif; ?>
        <p><strong>ข้อมูลการรับประกันเพิ่มเติม:</strong> รับประกันฟรี 30 วันสำหรับเครื่องใช้ไฟฟ้าทุกชนิด สามารถติดต่อขยายเวลารับประกันได้ผ่านทีมสนับสนุน</p>
        <h3 style="margin-top: 24px; margin-bottom: 12px;">สั่งซื้อสินค้า</h3>
        <form method="post">
            <label for="phone_contact">เบอร์โทรศัพท์สำหรับติดต่อจัดส่ง</label>
            <input type="text" id="phone_contact" name="phone_contact" placeholder="เช่น 0912345678" value="<?= sanitize($_POST['phone_contact'] ?? $_SESSION['user']['phone'] ?? '') ?>" required style="margin-bottom: 16px;">

            <label>ปักหมุดตำแหน่งจัดส่ง (คลิกบนแผนที่หรือลากหมุดเพื่อปรับตำแหน่ง)</label>
            <div id="map" style="height: 300px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 12px; z-index: 1;"></div>
            <button type="button" id="btn-get-location" class="btn btn-secondary" style="width: auto; margin-bottom: 20px; padding: 8px 16px; font-size: 0.85rem; border-radius: 999px; display: inline-flex; align-items: center; gap: 6px;">
                <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                ใช้ตำแหน่งปัจจุบันของฉัน
            </button>

            <label for="delivery_address">ที่อยู่สำหรับจัดส่ง</label>
            <textarea id="delivery_address" name="delivery_address" rows="3" placeholder="ระบุเลขที่บ้าน ถนน อำเภอ จังหวัด (หรือคลิกปักหมุดบนแผนที่เพื่อค้นหาอัตโนมัติ)" required style="margin-bottom: 16px;"><?= sanitize($_POST['delivery_address'] ?? '') ?></textarea>
            
            <label for="distance_km">ระยะห่างจากร้าน/คลังสินค้า (กิโลเมตร)</label>
            <input type="number" id="distance_km" name="distance_km" min="0" step="0.1" placeholder="เช่น 5.5" value="<?= sanitize($_POST['distance_km'] ?? '') ?>" required style="margin-bottom: 20px;">
            
            <button type="submit" class="btn" style="width: 100%;">สั่งซื้อสินค้าทันที</button>
        </form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Shop coordinates (Phatthanakan, Bangkok)
    const shopLat = 13.738318;
    const shopLng = 100.618641;

    // Handle standard icon path missing issue
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    // Initialize map centered at the shop
    const map = L.map('map').setView([shopLat, shopLng], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Shop static marker
    const shopMarker = L.marker([shopLat, shopLng]).addTo(map)
        .bindPopup("<b>คลังสินค้า สมาร์ท รีไซเคิล ช็อป</b><br>123/45 ถนนพัฒนาการ")
        .openPopup();

    // Draggable delivery marker
    // Let's place it slightly offset so user sees two distinct markers
    let deliveryMarker = L.marker([shopLat + 0.005, shopLng + 0.005], {
        draggable: true
    }).addTo(map).bindPopup("<b>ตำแหน่งจัดส่งของคุณ</b><br>ลากหมุดเพื่อระบุตำแหน่งที่แน่นอน");

    // Haversine formula
    function getDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radius of the earth in km
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2); 
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        const d = R * c; // Distance in km
        return d;
    }

    function deg2rad(deg) {
        return deg * (Math.PI/180);
    }

    // Geocoding and updating inputs
    function updateLocation(lat, lng) {
        const dist = getDistance(shopLat, shopLng, lat, lng);
        document.getElementById('distance_km').value = dist.toFixed(1);

        const addressArea = document.getElementById('delivery_address');
        addressArea.placeholder = "กำลังค้นหาที่อยู่จัดส่ง...";

        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=th`)
            .then(response => response.json())
            .then(data => {
                if (data && data.display_name) {
                    addressArea.value = data.display_name;
                } else {
                    addressArea.placeholder = "ระบุเลขที่บ้าน ถนน อำเภอ จังหวัด";
                }
            })
            .catch(err => {
                console.error("Geocoding fetch failed:", err);
                addressArea.placeholder = "ระบุเลขที่บ้าน ถนน อำเภอ จังหวัด";
            });
    }

    // Drag events
    deliveryMarker.on('dragend', function(e) {
        const pos = deliveryMarker.getLatLng();
        updateLocation(pos.lat, pos.lng);
    });

    // Map click events
    map.on('click', function(e) {
        deliveryMarker.setLatLng(e.latlng);
        deliveryMarker.openPopup();
        updateLocation(e.latlng.lat, e.latlng.lng);
    });

    // Handle "Use Current Location"
    const btnGetLoc = document.getElementById('btn-get-location');
    if (btnGetLoc) {
        btnGetLoc.addEventListener('click', function() {
            if (navigator.geolocation) {
                btnGetLoc.disabled = true;
                btnGetLoc.innerText = "กำลังรับตำแหน่ง...";

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        map.setView([lat, lng], 15);
                        deliveryMarker.setLatLng([lat, lng]);
                        deliveryMarker.openPopup();
                        updateLocation(lat, lng);

                        btnGetLoc.disabled = false;
                        btnGetLoc.innerHTML = `<svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg> ใช้ตำแหน่งปัจจุบันของฉัน`;
                    },
                    function(err) {
                        alert("ดึงตำแหน่งไม่สำเร็จ: " + err.message);
                        btnGetLoc.disabled = false;
                        btnGetLoc.innerHTML = `<svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg> ใช้ตำแหน่งปัจจุบันของฉัน`;
                    },
                    { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
                );
            } else {
                alert("เบราว์เซอร์นี้ไม่รองรับการดึงตำแหน่ง");
            }
        });
    }
});
</script>
    </div>
</div>
<?php pageFooter();
