<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'used_appliance_shop');
define('SITE_NAME', 'Used Appliance Shop');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$projectRoot = str_replace('\\', '/', dirname(__DIR__));

if ($docRoot && strpos($projectRoot, $docRoot) === 0) {
    $subDir = substr($projectRoot, strlen($docRoot));
} else {
    $subDir = '/smart-recycle-shop';
}
$subDir = rtrim(str_replace('\\', '/', $subDir), '/');

define('BASE_URL', $protocol . '://' . $host . $subDir);
define('FREE_SHIPPING_KM', 15);
define('FIRST_ORDER_DISCOUNT', 0.10);

function isLoggedIn() {
    return !empty($_SESSION['user']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function flash($key, $message = '') {
    if ($message !== '') {
        $_SESSION['flash'][$key] = $message;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return '';
}

function pageHeader($title = '') {
    $title = sanitize($title ?: SITE_NAME);
    echo "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title>{$title}</title>\n<link rel=\"stylesheet\" href=\"" . BASE_URL . "/assets/css/style.css\">\n<script defer src=\"" . BASE_URL . "/assets/js/main.js\"></script>\n</head>\n<body>\n<header class=\"site-header\">\n<div class=\"brand\"><a href=\"" . BASE_URL . "/\">สมาร์ท รีไซเคิล ช็อป</a></div>\n<nav class=\"site-nav\">\n";
    if (isLoggedIn()) {
        echo "<a href=\"" . BASE_URL . "/\">หน้าแรก</a>\n<a href=\"" . BASE_URL . "/products.php\">สินค้าทั้งหมด</a>\n<a href=\"" . BASE_URL . "/customizer.php\">ออกแบบตู้เย็น 3D</a>\n<a href=\"" . BASE_URL . "/preorder.php\">สั่งจองล่วงหน้า</a>\n<a href=\"" . BASE_URL . "/tracking.php\">ติดตามพัสดุ</a>\n<a href=\"" . BASE_URL . "/warranty.php\">เช็คประกันสินค้า</a>\n<a href=\"" . BASE_URL . "/location.php\">ที่ตั้งร้าน</a>\n<a href=\"" . BASE_URL . "/profile.php\">โปรไฟล์ของฉัน</a>\n";
        echo "<a href=\"" . BASE_URL . "/logout.php\">ออกจากระบบ</a>";
    } else {
        echo "<a href=\"" . BASE_URL . "/\">หน้าแรก</a>\n<a href=\"" . BASE_URL . "/customizer.php\">ออกแบบตู้เย็น 3D</a>\n<a href=\"" . BASE_URL . "/login.php\">เข้าสู่ระบบ</a>\n<a href=\"" . BASE_URL . "/register.php\">สมัครสมาชิก</a>";
    }
    echo "</nav>\n</header>\n<main class=\"page-content\">\n";
}

function pageFooter() {
    $isGuest = !isLoggedIn();
    $registerUrl = BASE_URL . "/register.php";
    echo "</main>\n<footer class=\"site-footer\">\n";
    echo "    <div class=\"footer-container\">\n";
    echo "        <div class=\"footer-col\">\n";
    echo "            <h3>สมาร์ท รีไซเคิล ช็อป</h3>\n";
    echo "            <p>แหล่งรวมเครื่องใช้ไฟฟ้ามือสองคุณภาพสูง ผ่านการทำความสะอาดและตรวจสอบสภาพ 100% โดยช่างผู้เชี่ยวชาญ ปลอดภัย คุ้มค่า มีรับประกันสินค้า</p>\n";
    echo "            <p class=\"footer-address\"><strong>คลังสินค้าหลัก:</strong> 123/45 ถนนพัฒนาการ แขวงสวนหลวง เขตสวนหลวง กรุงเทพมหานคร 10250</p>\n";
    echo "        </div>\n";
    echo "        <div class=\"footer-col\">\n";
    echo "            <h3>ช่องทางการติดต่อ</h3>\n";
    echo "            <ul class=\"footer-contact\">\n";
    echo "                <li><strong>เบอร์โทรศัพท์:</strong> 090-000-0000</li>\n";
    echo "                <li><strong>อีเมล:</strong> support@smartrecycleshop.com</li>\n";
    echo "                <li><strong>LINE ID:</strong> @smartrecycle</li>\n";
    echo "            </ul>\n";
    echo "        </div>\n";
    echo "        <div class=\"footer-col\">\n";
    echo "            <h3>สมัครสมาชิกรับส่วนลด</h3>\n";
    echo "            <p>สมัครสมาชิกกับเราวันนี้! เพื่อรับส่วนลดพิเศษทันที <strong>10%</strong> สำหรับการสั่งซื้อเครื่องใช้ไฟฟ้าชิ้นแรกของคุณ พร้อมสิทธิประโยชน์ในการเคลมประกันสินค้าและติดตามการจัดส่ง</p>\n";
    if ($isGuest) {
        echo "            <div style=\"margin-top: 16px;\">\n";
        echo "                <a href=\"{$registerUrl}\" class=\"btn btn-secondary\" style=\"padding: 10px 24px; font-size: 0.9rem;\">สมัครสมาชิกรับส่วนลดที่นี่</a>\n";
        echo "            </div>\n";
    }
    echo "        </div>\n";
    echo "    </div>\n";
    echo "    <div class=\"footer-bottom\">\n";
    echo "        <p>© " . date('Y') . " สมาร์ท รีไซเคิล ช็อป. สงวนลิขสิทธิ์ทั้งหมด.</p>\n";
    echo "        <p>Built for XAMPP</p>\n";
    echo "    </div>\n";
    echo "</footer>\n</body>\n</html>\n";
}

function authGuard() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
}

function adminGuard() {
    if (!isAdmin()) {
        redirect(BASE_URL . '/admin/login.php');
    }
}
