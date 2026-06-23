<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
$phone = trim($_GET['phone'] ?? '');
if (!$phone) {
    echo json_encode(['success' => false, 'message' => 'Phone parameter is required.']);
    exit;
}
$stmt = $mysqli->prepare('SELECT o.id, o.status, o.total_amount, o.created_at, u.name FROM orders o JOIN users u ON u.id = o.user_id WHERE o.phone_contact = ? ORDER BY o.created_at DESC LIMIT 1');
$stmt->bind_param('s', $phone);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}
echo json_encode(['success' => true, 'order' => $order]);
