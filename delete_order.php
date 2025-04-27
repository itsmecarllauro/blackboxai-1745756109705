<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$orderId = isset($data['orderId']) ? intval($data['orderId']) : 0;

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    $db->beginTransaction();

    // Delete sales items linked to the order
    $stmt = $db->prepare("DELETE FROM sales WHERE sale_order_id = :orderId");
    $stmt->bindValue(':orderId', $orderId);
    $stmt->execute();

    // Delete the sales order record
    $stmt = $db->prepare("DELETE FROM sales_orders WHERE id = :orderId");
    $stmt->bindValue(':orderId', $orderId);
    $stmt->execute();

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to delete order: ' . $e->getMessage()]);
}
exit;
?>
