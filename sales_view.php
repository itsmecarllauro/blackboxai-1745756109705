<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    header('Location: index.php');
    exit;
}
require 'config.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if ($role === 'cashier') {
    $stmt = $db->prepare("
        SELECT 
            sales_orders.id AS order_id,
            sales_orders.sale_time,
            SUM(sales.quantity) AS total_quantity,
            SUM(sales.total_price) AS total_price
        FROM sales_orders
        JOIN sales ON sales.sale_order_id = sales_orders.id
        WHERE sales_orders.cashier_id = :cashier_id
        GROUP BY sales_orders.id, sales_orders.sale_time
        ORDER BY sales_orders.sale_time DESC
    ");
    $stmt->bindValue(':cashier_id', $user_id);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("
        SELECT 
            sales.sale_order_id AS order_id,
            sales_orders.sale_time,
            SUM(sales.quantity) AS total_quantity,
            SUM(sales.total_price) AS total_price,
            users.username AS cashier_name
        FROM sales
        JOIN sales_orders ON sales.sale_order_id = sales_orders.id
        LEFT JOIN users ON sales.cashier_id = users.id
        GROUP BY sales.sale_order_id, sales_orders.sale_time, users.username
        ORDER BY sales_orders.sale_time DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Orders View</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        button {
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-[#F5F5DC] min-h-screen"> <!-- light beige -->
<header class="bg-[#A97142] text-white p-4 flex justify-between items-center"> <!-- nude brown -->
        <h1 class="text-xl font-bold">Orders View</h1>
        <div>
            <?php if ($role === 'admin'): ?>
                <a href="admin_dashboard.php" class=" mr-4 text-white">Dashboard</a>
            <?php else: ?>
                <a href="cashier_dashboard.php" class=" mr-4 text-white">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php" class=" text-white">Logout</a>
        </div>
    </header>
    <div class="mb-4">
    <a href="admin_dashboard.php" class="inline-block text-[#A97142]">&larr; Back to Dashboard</a>
        </div>
    <main class="p-6 max-w-6xl mx-auto">
        <table class="w-full bg-[#FDF6EC] border border-[#A97142] rounded shadow overflow-hidden">
        <thead class="text-white" style="background-color: #A97142;">

                <tr>
                    <th class="p-3 text-left">Order ID</th>
                    <th class="p-3 text-left">Sale Time</th>
                    <th class="p-3 text-left">Total Orders</th>
                    <th class="p-3 text-left">Total Price</th>
                    <?php if ($role === 'admin'): ?>
                        <th class="p-3 text-left">Cashier</th>
                    <?php endif; ?>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders): ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-b border-gray-200">
                        <td class="p-3"><?php echo $order['order_id']; ?></td>
                        <td class="p-3"><?php echo $order['sale_time']; ?></td>
                        <td class="p-3"><?php echo $order['total_quantity']; ?></td>
                        <td class="p-3">₱<?php echo number_format($order['total_price'], 2); ?></td>
                        <?php if ($role === 'admin'): ?>
                            <td class="p-3"><?php echo htmlspecialchars($order['cashier_name'] ?? 'N/A'); ?></td>
                        <?php endif; ?>
                        <td class="p-3 space-x-2">

                        <button onclick="deleteOrder(<?php echo $order['order_id']; ?>)" class="bg-[#A97142] text-white px-3 py-1 rounded hover:bg-[#8C5730] transition">Delete</button>

                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $role === 'admin' ? 6 : 5; ?>" class="p-3 text-center text-gray-500">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded shadow-lg max-w-3xl w-full p-6 relative"> 
                <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-600 hover:text-gray-900 text-xl font-bold">&times;</button>
                <h2 class="text-xl font-semibold mb-4">Order Details</h2>
                <div id="orderDetailsContent" class="overflow-auto max-h-96">
                </div>
            </div>
        </div>
    </main>

    <script>
        function viewOrder(orderId) {
            console.log('viewOrder called with orderId:', orderId);
            fetch('order_details.php?order_id=' + orderId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const contentDiv = document.getElementById('orderDetailsContent');
                        contentDiv.innerHTML = '';
                        const table = document.createElement('table');
                        table.className = 'w-full border-collapse border border-gray-300';
                        const thead = document.createElement('thead');
                        thead.innerHTML = '<tr><th class="border border-gray-300 p-2 text-left">Product</th><th class="border border-gray-300 p-2 text-left">Quantity</th><th class="border border-gray-300 p-2 text-left">Price</th><th class="border border-gray-300 p-2 text-left">Total</th></tr>';
                        table.appendChild(thead);
                        const tbody = document.createElement('tbody');
                        let total = 0;
                        data.items.forEach(item => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="border border-gray-300 p-2">${item.name}</td>
                                <td class="border border-gray-300 p-2">${item.quantity}</td>
                                <td class="border border-gray-300 p-2">₱${item.price.toFixed(2)}</td>
                                <td class="border border-gray-300 p-2">₱${item.total_price.toFixed(2)}</td>
                            `;
                            tbody.appendChild(tr);
                            total += item.total_price;
                        });
                        table.appendChild(tbody);
                        contentDiv.appendChild(table);
                        const totalDiv = document.createElement('div');
                        totalDiv.className = 'mt-4 font-bold text-right';
                        totalDiv.textContent = 'Total: ₱' + total.toFixed(2);
                        contentDiv.appendChild(totalDiv);
                        document.getElementById('orderModal').classList.remove('hidden');
                    } else {
                        alert('Failed to load order details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => alert('Failed to load order details: ' + error.message));
        }

        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }

        function printOrder(orderId) {
            fetch('order_details.php?order_id=' + orderId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Print data:', data);
                    if (data.success) {
                        const printWindow = window.open('', '', 'width=600,height=600');
                        printWindow.document.write('<html><head><title>Order Print</title>');
                        printWindow.document.write('<style>body{font-family: Arial, sans-serif;} table{width:100%;border-collapse: collapse;} th, td{border:1px solid #ccc;padding:8px;text-align:left;} th{background:#f4f4f4;}</style>');
                        printWindow.document.write('</head><body>');
                        printWindow.document.write('<h2>Order ID: ' + orderId + '</h2>');
                        printWindow.document.write('<table><thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead><tbody>');
                        let total = 0;
                        if (Array.isArray(data.items) && data.items.length > 0) {
                            data.items.forEach(item => {
                                printWindow.document.write('<tr><td>' + item.name + '</td><td>' + item.quantity + '</td><td>₱' + item.price.toFixed(2) + '</td><td>₱' + item.total_price.toFixed(2) + '</td></tr>');
                                total += item.total_price;
                            });
                        } else {
                            printWindow.document.write('<tr><td colspan="4" style="text-align:center;">No items found</td></tr>');
                        }
                        printWindow.document.write('<tfoot><tr><td colspan="3" style="text-align:right;font-weight:bold;">Total</td><td>₱' + total.toFixed(2) + '</td></tr></tfoot>');
                        printWindow.document.write('</tbody></table>');
                        if (data.exchange !== undefined) {
                            printWindow.document.write('<div style="margin-top: 20px; font-weight: bold;">Exchange: ₱' + data.exchange.toFixed(2) + '</div>');
                        }
                        printWindow.document.write('</body></html>');
                        printWindow.document.close();
                        printWindow.focus();
                        printWindow.print();
                        printWindow.close();
                    } else {
                        alert('Failed to load order details for printing: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => alert('Failed to load order details for printing: ' + error.message));
        }
    </script>
    <script>
        function deleteOrder(orderId) {
            if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
                return;
            }
            fetch('delete_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ orderId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order deleted successfully.');
                    location.reload();
                } else {
                    alert('Failed to delete order: ' + data.message);
                }
            })
            .catch(() => alert('Failed to delete order.'));
        }
    </script>
</body>
</html>
