<?php
require_once __DIR__ . '/config.php';

function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user']['id'] : null;
}

function getCurrentUserRole() {
    return isLoggedIn() ? $_SESSION['user']['role'] : null;
}

function refreshUserSession($mysqli, $userId) {
    $stmt = $mysqli->prepare('SELECT id, name, phone, email, line_id, role, avatar FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $_SESSION['user'] = $user;
    }
    $stmt->close();
}

function adminHeader($title = '') {
    $title = sanitize($title ?: 'Admin Dashboard');
    echo "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"UTF-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n<title>{$title}</title>\n<link rel=\"stylesheet\" href=\"" . BASE_URL . "/assets/css/style.css\">\n<script defer src=\"" . BASE_URL . "/assets/js/main.js\"></script>\n</head>\n<body>\n<header class=\"admin-header\">\n<div class=\"brand\"><a href=\"" . BASE_URL . "/admin/dashboard.php\">ระบบดูแลร้านค้า</a></div>\n<nav class=\"admin-nav\">\n<a href=\"" . BASE_URL . "/admin/dashboard.php\">แผงควบคุม</a>
<a href=\"" . BASE_URL . "/admin/products.php\">จัดการสินค้า</a>
<a href=\"" . BASE_URL . "/admin/orders.php\">จัดการคำสั่งซื้อ</a>
<a href=\"" . BASE_URL . "/admin/preorders.php\">รายการจองล่วงหน้า</a>
<a href=\"" . BASE_URL . "/admin/shipments.php\">การจัดส่ง</a>
<a href=\"" . BASE_URL . "/admin/warranty.php\">การรับประกัน</a>
<a href=\"" . BASE_URL . "/admin/admins.php\">จัดการแอดมิน</a>
<a href=\"" . BASE_URL . "/logout.php\">ออกจากระบบ</a>
</nav>\n</header>\n<main class=\"page-content\">\n";
}

function adminFooter() {
    echo "</main>\n<footer class=\"site-footer\">\n<p>Admin panel • " . SITE_NAME . "</p>\n</footer>\n</body>\n</html>\n";
}

function isFirstOrder($mysqli, $userId) {
    $stmt = $mysqli->prepare('SELECT COUNT(*) as count FROM orders WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] ?? 0;
    $stmt->close();
    return $count === 0;
}

function calculateShipping($distanceKm) {
    if ($distanceKm <= FREE_SHIPPING_KM) {
        return 0;
    }
    return 100 + max(0, $distanceKm - FREE_SHIPPING_KM) * 10;
}

function buildProductFilters($params) {
    $conditions = ['p.status = 1'];
    $types = [];
    if (!empty($params['category'])) {
        $conditions[] = 'p.category = ?';
        $types[] = $params['category'];
    }
    if (!empty($params['grade'])) {
        $conditions[] = 'p.grade = ?';
        $types[] = $params['grade'];
    }
    if (!empty($params['color'])) {
        $conditions[] = 'p.color = ?';
        $types[] = $params['color'];
    }
    if (!empty($params['price_min'])) {
        $conditions[] = 'p.price >= ?';
        $types[] = $params['price_min'];
    }
    if (!empty($params['price_max'])) {
        $conditions[] = 'p.price <= ?';
        $types[] = $params['price_max'];
    }
    return [implode(' AND ', $conditions), $types];
}
