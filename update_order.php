<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$orderId = isset($data['orderId']) ? intval($data['orderId']) : 0;
$items = $data['items'] ?? [];

if ($orderId <= 0 || !is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $db->beginTransaction();

    foreach ($items as $item) {
        $saleId = intval($item['saleId']);
        $quantity = intval($item['quantity']);
        if ($quantity <= 0) {
            $stmt = $db->prepare("DELETE FROM sales WHERE id = :saleId AND sale_order_id = :orderId");
            $stmt->bindValue(':saleId', $saleId);
            $stmt->bindValue(':orderId', $orderId);
            $stmt->execute();
        } else {
            $stmt = $db->prepare("SELECT price FROM products JOIN sales ON products.id = sales.product_id WHERE sales.id = :saleId");
            $stmt->bindValue(':saleId', $saleId);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                throw new Exception("Product not found for sale ID $saleId");
            }
            $newTotalPrice = $product['price'] * $quantity;

            $stmt = $db->prepare("UPDATE sales SET quantity = :quantity, total_price = :total_price WHERE id = :saleId AND sale_order_id = :orderId");
            $stmt->bindValue(':quantity', $quantity);
            $stmt->bindValue(':total_price', $newTotalPrice);
            $stmt->bindValue(':saleId', $saleId);
            $stmt->bindValue(':orderId', $orderId);
            $stmt->execute();
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to update order: ' . $e->getMessage()]);
}
exit;
?>
