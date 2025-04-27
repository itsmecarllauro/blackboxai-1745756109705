<?php
require 'config.php';

// Check if sales_orders table has data
$salesOrdersCount = $db->query("SELECT COUNT(*) FROM sales_orders")->fetchColumn();
echo "Total sales_orders records: " . $salesOrdersCount . "\n";

// Check if sales table has sale_order_id column and data
$hasSaleOrderIdColumn = false;
$columns = $db->query("SHOW COLUMNS FROM sales")->fetchAll(PDO::FETCH_COLUMN);
if (in_array('sale_order_id', $columns)) {
    $hasSaleOrderIdColumn = true;
}
echo "sales table has sale_order_id column: " . ($hasSaleOrderIdColumn ? "Yes" : "No") . "\n";

if ($hasSaleOrderIdColumn) {
    $linkedSalesCount = $db->query("SELECT COUNT(*) FROM sales WHERE sale_order_id IS NOT NULL")->fetchColumn();
    echo "Total sales records linked to sale_order_id: " . $linkedSalesCount . "\n";
} else {
    echo "No sale_order_id column in sales table, cannot check linked sales.\n";
}

// Show some sample sales_orders records
echo "\nSample sales_orders records:\n";
$salesOrders = $db->query("SELECT * FROM sales_orders ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($salesOrders as $order) {
    echo "ID: {$order['id']}, Cashier ID: {$order['cashier_id']}, Sale Time: {$order['sale_time']}\n";
}

// Show some sample sales records with sale_order_id
if ($hasSaleOrderIdColumn) {
    echo "\nSample sales records with sale_order_id:\n";
    $sales = $db->query("SELECT id, product_id, quantity, total_price, sale_order_id FROM sales WHERE sale_order_id IS NOT NULL ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sales as $sale) {
        echo "Sale ID: {$sale['id']}, Product ID: {$sale['product_id']}, Quantity: {$sale['quantity']}, Total Price: {$sale['total_price']}, Sale Order ID: {$sale['sale_order_id']}\n";
    }
}
?>
