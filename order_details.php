<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require 'config.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Fetch sales records for the given sale_order_id
$stmt = $db->prepare("SELECT sales.id, sales.product_id, products.name, sales.quantity, products.price, sales.total_price FROM sales JOIN products ON sales.product_id = products.id WHERE sales.sale_order_id = :order_id ORDER BY sales.id ASC");
$stmt->bindValue(':order_id', $order_id);
if (!$stmt->execute()) {
    $errorInfo = $stmt->errorInfo();
    error_log("order_details.php: SQL execute error: " . print_r($errorInfo, true));
    echo json_encode(['success' => false, 'message' => 'Database query failed']);
    exit;
}
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    error_log("order_details.php: No items found for order_id: $order_id");
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

echo json_encode(['success' => true, 'items' => $items]);
exit;
?>
