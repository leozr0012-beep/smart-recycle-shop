<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

$latestProducts = [];
$featuredProducts = [];
$discountedProducts = [];

// Fetch products (applicable to both guest and logged-in users)
$query = 'SELECT p.id,p.name,p.category,p.grade,p.color,p.price,p.original_price,p.short_description, (SELECT url FROM product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS image_url FROM products p WHERE p.status = 1 ORDER BY p.created_at DESC LIMIT 6';
$stmt = $mysqli->prepare($query);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $latestProducts[] = $row;
    }
    $stmt->close();
}

$query = 'SELECT p.id,p.name,p.category,p.grade,p.color,p.price,p.original_price,p.short_description, (SELECT url FROM product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS image_url FROM products p WHERE p.featured = 1 AND p.status = 1 ORDER BY p.created_at DESC LIMIT 4';
$stmt = $mysqli->prepare($query);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $featuredProducts[] = $row;
    }
    $stmt->close();
}

$query = 'SELECT p.id,p.name,p.category,p.grade,p.color,p.price,p.original_price,p.short_description, (SELECT url FROM product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS image_url FROM products p WHERE p.original_price IS NOT NULL AND p.price < p.original_price AND p.status = 1 ORDER BY p.created_at DESC LIMIT 4';
$stmt = $mysqli->prepare($query);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $discountedProducts[] = $row;
    }
    $stmt->close();
}

pageHeader('หน้าแรก - สมาร์ท รีไซเคิล ช็อป');
?>

<!-- Banner Carousel Slider -->
<div class="slider-container reveal">
    <div class="slider-wrapper">
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1621905252507-b354bc25edac?auto=format&fit=crop&w=1200&q=80');">
            <div class="slide-content">
                <h2>แอร์มือสอง สภาพดี 99% ประหยัดไฟเบอร์ 5</h2>
                <p>ผ่านการทำความสะอาดและตรวจสอบคุณภาพโดยช่างผู้เชี่ยวชาญ พร้อมประกัน 30 วัน จัดส่งฟรีในกรุงเทพฯ ระยะ 15 กม.</p>
                <a href="products.php?category=ac" class="btn">เลือกดูแอร์ทั้งหมด</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1200&q=80');">
            <div class="slide-content">
                <h2>ตู้เย็นแบรนด์ดัง ราคาประหยัด สภาพนางฟ้า</h2>
                <p>ตู้เย็นสองประตู ตู้เย็นมินิ ตู้เย็นแช่แข็ง ทำความสะอาดฆ่าเชื้อโรคเรียบร้อยแล้ว ราคาเริ่มต้นเพียง 3,900 บาทเท่านั้น</p>
                <a href="products.php?category=fridge" class="btn btn-secondary">เลือกดูตู้เย็นทั้งหมด</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1545173168-9f1947eebd01?auto=format&fit=crop&w=1200&q=80');">
            <div class="slide-content">
                <h2>เครื่องซักผ้าฝาบน-ฝาหน้า คุณภาพพรีเมียม</h2>
                <p>หลากหลายรุ่นความจุ ถังซักสะอาดหมดจดพร้อมใช้งานทันที รับส่วนลดเพิ่ม 10% สำหรับการสั่งซื้อครั้งแรกเมื่อสมัครสมาชิก</p>
                <a href="products.php?category=washer" class="btn">เลือกดูเครื่องซักผ้าทั้งหมด</a>
            </div>
        </div>
    </div>
    
    <!-- Navigation Buttons -->
    <div class="slider-btn slider-btn-prev">&#10094;</div>
    <div class="slider-btn slider-btn-next">&#10095;</div>
    
    <!-- Dots -->
    <div class="slider-dots">
        <span class="slider-dot active" data-slide="0"></span>
        <span class="slider-dot" data-slide="1"></span>
        <span class="slider-dot" data-slide="2"></span>
    </div>
</div>

<!-- Promo Banner Cards (3 boxes under slider) -->
<div class="promo-banners-container reveal delay-1">
    <div class="promo-grid">
        <!-- Banner 1 -->
        <a href="products.php" class="promo-card promo-card-1">
            <div>
                <span class="promo-badge">HOT DEAL</span>
                <h3>สินค้าแยกชิ้น ส่งด่วน!</h3>
                <p>รับของรวดเร็วทันใจ ส่งฟรีระยะทาง 15 กม. แรกจากคลังสินค้า</p>
            </div>
            <div style="font-size: 0.85rem; font-weight: bold; text-decoration: underline; margin-top: 10px;">ช้อปด่วนเลย &rarr;</div>
        </a>

        <!-- Banner 2 -->
        <a href="preorder.php" class="promo-card promo-card-2">
            <div>
                <span class="promo-badge">EASY PAY</span>
                <h3>ผ่อนสบาย 0% นาน 6 เดือน</h3>
                <p>มีบริการสั่งจองสินค้าล่วงหน้า ผ่อนชำระแบบสบายกระเป๋า</p>
            </div>
            <div style="font-size: 0.85rem; font-weight: bold; text-decoration: underline; margin-top: 10px;">ดูช่องทางการจอง &rarr;</div>
        </a>

        <!-- Banner 3 -->
        <a href="<?= isLoggedIn() ? 'products.php' : 'register.php' ?>" class="promo-card promo-card-3">
            <div>
                <span class="promo-badge">WELCOME GIFT</span>
                <h3>ส่วนลดลูกค้าใหม่ 10%</h3>
                <p>สมัครสมาชิกและรับส่วนลดพิเศษทันทีในบิลคำสั่งซื้อแรก</p>
            </div>
            <div style="font-size: 0.85rem; font-weight: bold; text-decoration: underline; margin-top: 10px;"><?= isLoggedIn() ? 'เริ่มช้อปปิ้ง' : 'สมัครสมาชิกตอนนี้' ?> &rarr;</div>
        </a>
    </div>
</div>

<!-- 3D Refrigerator Customizer Call-to-Action Banner -->
<div class="card reveal delay-2" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 40px; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 32px; align-items: center; margin-top: 10px; margin-bottom: 32px; border-radius: var(--radius-lg); position: relative; overflow: hidden; border: none;">
    <div style="position: relative; z-index: 2;">
        <span class="badge" style="background: var(--secondary); color: #fff; margin-bottom: 12px; font-weight: bold;">ฟีเจอร์ใหม่สุดเจ๋ง!</span>
        <h2 style="font-size: 2.1rem; color: #ffffff; margin-bottom: 12px; font-weight: 800; line-height: 1.3;">จำลองตู้เย็นในฝันด้วยระบบ 3D คัสตอมสีได้ตามใจชอบ</h2>
        <p style="color: #cbd5e1; font-size: 1.05rem; margin-bottom: 24px; line-height: 1.6;">ทดลองสลับสีฝาประตู สีตัวเครื่อง และวัสดุมือจับตู้เย็นในระบบ 3 มิติเชิงโต้ตอบแบบสมจริง 360° พร้อมสลักข้อความชื่อของคุณและสั่งทำพิเศษได้ทันที!</p>
        <a href="customizer.php" class="btn btn-secondary" style="padding: 12px 32px; font-size: 1rem; border-radius: 99px;">🎮 เริ่มลองออกแบบเลย</a>
    </div>
    <div style="display: flex; justify-content: center; align-items: center; position: relative; z-index: 2;">
        <!-- Beautiful 3D Fridge Graphic SVG -->
        <svg viewBox="0 0 200 200" style="width: 150px; height: 150px; filter: drop-shadow(0 12px 24px rgba(0,0,0,0.5));" xmlns="http://www.w3.org/2000/svg">
            <rect x="50" y="20" width="100" height="160" rx="10" fill="#475569" />
            <path d="M 50,20 L 150,20 L 150,180 L 50,180 Z" fill="#64748b" opacity="0.3" />
            <rect x="52" y="22" width="96" height="50" rx="6" fill="#e2e8f0" />
            <rect x="52" y="76" width="96" height="100" rx="6" fill="#e2e8f0" />
            <rect x="134" y="32" width="6" height="30" rx="3" fill="#94a3b8" />
            <rect x="134" y="86" width="6" height="60" rx="3" fill="#94a3b8" />
            <rect x="85" y="27" width="30" height="4" rx="2" fill="#cbd5e1" />
            <circle cx="100" cy="100" r="80" fill="#818cf8" opacity="0.15" />
        </svg>
    </div>
    <!-- Backdrop radial glow -->
    <div style="position: absolute; top: -50%; right: -30%; width: 350px; height: 350px; background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%); pointer-events: none;"></div>
</div>

<!-- Categories Selector Grid -->
<div class="kaidee-categories-card reveal delay-2">
    <h2>ค้นหาตามประเภทเครื่องใช้ไฟฟ้า</h2>
    <div class="kaidee-grid">
        <!-- Fridge -->
        <a href="products.php?category=fridge" class="kaidee-item">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v7h10V4H7zm0 9v7h10v-7H7zm2-6h2v3H9V5zm0 9h2v4H9v-4z"/>
            </svg>
            <span>ตู้เย็น</span>
        </a>
        <!-- Washer -->
        <a href="products.php?category=washer" class="kaidee-item">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm-7 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-6a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm4-6H8V5h8v1z"/>
            </svg>
            <span>เครื่องซักผ้า</span>
        </a>
        <!-- TV -->
        <a href="products.php?category=tv" class="kaidee-item">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 1.9-.9 1.9-2l.01-12c0-1.1-.9-2-1.9-2zm0 14H3V5h18v12z"/>
            </svg>
            <span>ทีวี</span>
        </a>
        <!-- AC -->
        <a href="products.php?category=ac" class="kaidee-item">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 10H5V5h14v8zm-2 4h-2v2h2v-2zm-4 0h-2v2h2v-2zm-4 0H7v2h2v-2z"/>
            </svg>
            <span>แอร์</span>
        </a>
    </div>
</div>

<!-- Recommended Products (สินค้าแนะนำ) -->
<div class="card reveal delay-2">
    <h2 style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 1.5rem;">⭐️</span> สินค้าแนะนำพิเศษ
    </h2>
    <div class="grid-4">
        <?php if (empty($featuredProducts)): ?>
            <p style="grid-column: 1/-1; color: var(--text-secondary);">ไม่มีสินค้าแนะนำในขณะนี้</p>
        <?php else: foreach ($featuredProducts as $product): ?>
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
                    <span class="badge status-shipping" style="padding: 2px 8px; font-size: 0.75rem;"><?= sanitize(ucfirst($product['color'])) ?></span>
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
</div>

<!-- Discounted Products (สินค้าลดราคา) -->
<div class="card reveal delay-3">
    <h2 style="margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 1.5rem;">🔥</span> สินค้าลดราคาสุดคุ้ม
    </h2>
    <div class="grid-4">
        <?php if (empty($discountedProducts)): ?>
            <p style="grid-column: 1/-1; color: var(--text-secondary);">ไม่มีสินค้าลดราคาพิเศษในขณะนี้</p>
        <?php else: foreach ($discountedProducts as $product): ?>
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
                    <span class="badge status-shipping" style="padding: 2px 8px; font-size: 0.75rem;"><?= sanitize(ucfirst($product['color'])) ?></span>
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
</div>

<!-- Latest Arrivals (สินค้าเข้าใหม่) -->
<div class="card reveal delay-3">
    <h2 style="margin-bottom: 24px;">📦 สินค้าเข้าใหม่ล่าสุด</h2>
    <div class="grid-3">
        <?php if (empty($latestProducts)): ?>
            <p style="grid-column: 1/-1; color: var(--text-secondary);">ไม่มีสินค้าวางจำหน่ายในขณะนี้</p>
        <?php else: foreach ($latestProducts as $product): ?>
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
                <div class="price-tag">
                    <?php if ($product['original_price'] !== null && $product['original_price'] > $product['price']): ?>
                        <span class="price-original">฿<?= number_format($product['original_price'], 2) ?></span>
                        <span class="price-current">฿<?= number_format($product['price'], 2) ?></span>
                    <?php else: ?>
                        ฿<?= number_format($product['price'], 2) ?>
                    <?php endif; ?>
                </div>
                <a class="btn btn-secondary" href="product.php?id=<?= $product['id'] ?>" style="width: 100%;">ดูรายละเอียดด่วน</a>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- Highlighted Warehouse Location -->
<div class="card reveal delay-4" style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; align-items: center; text-align: left; margin-bottom: 32px;">
    <div>
        <h3 style="font-size: 1.3rem; margin-bottom: 12px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px; fill: var(--primary);">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
            ที่ตั้งคลังสินค้าหลัก (ดูสินค้าจริงได้ที่นี่)
        </h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 12px;">คุณสามารถเดินทางมาชมสภาพสินค้าจริง ทดสอบการทำงานของเครื่อง หรือติดต่อรับสินค้ากลับด้วยตนเองได้ทุกวัน</p>
        <p style="font-size: 1.05rem; font-weight: 600; color: var(--primary); line-height: 1.5; margin-bottom: 16px;">📍 123/45 ถนนพัฒนาการ แขวงสวนหลวง เขตสวนหลวง กรุงเทพมหานคร 10250</p>
        <a href="location.php" class="btn btn-secondary" style="padding: 10px 24px; font-size: 0.9rem; border-radius: 99px; display: inline-flex; align-items: center; gap: 8px;">
            <span>🗺️ ดูแผนที่และข้อมูลการเดินทางทั้งหมด</span>
        </a>
    </div>
    <div style="background: rgba(79, 70, 229, 0.04); border-radius: 16px; padding: 24px 20px; text-align: center; border: 1px dashed var(--primary);">
        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 8px; font-weight: 500;">📞 สายด่วนติดต่อสอบถาม</p>
        <h4 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 8px; font-weight: 800;">090-000-0000</h4>
        <p style="font-size: 0.85rem; color: var(--text-muted);">เปิดให้บริการทุกวัน 09:00 - 18:00 น.</p>
    </div>
</div>

<?php 
pageFooter();
?>
