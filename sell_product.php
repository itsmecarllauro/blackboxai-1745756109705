<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'cashier'])) {
    header('Location: index.php');
    exit;
}
require 'config.php';

$message = '';
$change = null;
$amount_paid = null;
$print_bill_data = null;

function processPayment($db, $order, $amount_paid, $user_id) {
    $total_amount = 0;

    foreach ($order as $product_id => $item) {
        $product_id = intval($product_id);
        $quantity = intval($item['quantity']);
        if ($product_id <= 0 || $quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid product or quantity.'];
        }
        $stmt = $db->prepare("SELECT price FROM products WHERE id = :id");
        $stmt->bindValue(':id', $product_id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }
        $total_amount += $product['price'] * $quantity;
    }

    if ($amount_paid < $total_amount) {
        return ['success' => false, 'message' => 'Amount paid is less than total amount.'];
    }

    $change = $amount_paid - $total_amount;

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO sales_orders (cashier_id, sale_time) VALUES (:cashier_id, NOW())");
        if ($_SESSION['role'] === 'cashier') {
            $cashierIdToUse = $user_id;
        } else {
            $cashierIdToUse = $user_id;
        }
        $stmt->bindValue(':cashier_id', $cashierIdToUse);
        $stmt->execute();
        $sale_order_id = $db->lastInsertId();

        foreach ($order as $product_id => $item) {
            $product_id = intval($product_id);
            $quantity = intval($item['quantity']);
            if ($product_id <= 0 || $quantity <= 0) {
                throw new Exception('Invalid product or quantity.');
            }
            $stmt = $db->prepare("SELECT price, stock FROM products WHERE id = :id");
            $stmt->bindValue(':id', $product_id);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                throw new Exception('Product not found.');
            }
            if ($product['stock'] < $quantity) {
                throw new Exception("Insufficient stock for product ID $product_id.");
            }
            $total_price = $product['price'] * $quantity;

            $stmt = $db->prepare("INSERT INTO sales (sale_order_id, product_id, quantity, total_price, cashier_id) VALUES (:sale_order_id, :product_id, :quantity, :total_price, :cashier_id)");
            $stmt->bindValue(':sale_order_id', $sale_order_id);
            $stmt->bindValue(':product_id', $product_id);
            $stmt->bindValue(':quantity', $quantity);
            $stmt->bindValue(':total_price', $total_price);
            $stmt->bindValue(':cashier_id', $user_id);
            $stmt->execute();

            $stmt = $db->prepare("UPDATE products SET stock = stock - :quantity WHERE id = :id");
            $stmt->bindValue(':quantity', $quantity);
            $stmt->bindValue(':id', $product_id);
            $stmt->execute();
        }
        $db->commit();
        return ['success' => true, 'message' => "Payment successful. Total: ₱" . number_format($total_amount, 2) . ". Change: ₱" . number_format($change, 2), 'change' => $change, 'order' => $order, 'amount_paid' => $amount_paid, 'sale_order_id' => $sale_order_id];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => "Error processing payment: " . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $order = json_decode($_POST['order'] ?? '[]', true);
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if (empty($order)) {
        $message = "Order is empty.";
    } elseif ($amount_paid <= 0) {
        $message = "Please enter the amount paid.";
    } else {
        $result = processPayment($db, $order, $amount_paid, $user_id);
        $message = $result['message'];
        if ($result['success']) {
            $change = $result['change'];
            $print_bill_data = [
                'order' => $result['order'],
                'amount_paid' => $result['amount_paid'],
                'change' => $result['change'],
            ];
        }
    }
}

$categories = $db->query("SELECT DISTINCT category FROM products ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
$products = $db->query("SELECT * FROM products ORDER BY category ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sell Product - Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;

        }
        .category-item {
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        .category-item:hover, .category-item.active {
            background-color: #4f46e5; 
            color: white;
        }
        .product-card {
            cursor: pointer;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 1rem;
            transition: box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .product-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .bill-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb; 
        }
    </style>
</head>
<body class="bg-[#F5F5DC] min-h-screen"> <!-- light beige -->
<header class="bg-[#A97142] text-white p-4 flex justify-between items-center"> <!-- nude brown -->
        <h1 class="text-xl font-bold">Sell Product</h1>
        <div>
            <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'cashier_dashboard.php'; ?>" class=" mr-4 text-white">Dashboard</a>
            <a href="logout.php" class=" text-white">Logout</a>
        </div>
    </header>
    <div class="mb-4">
    <a href="admin_dashboard.php" class="inline-block text-[#A97142]">&larr; Back to Dashboard</a>

        </div>
    <main class="p-6 max-w-7xl mx-auto flex space-x-6">

    <section class="w-1/5 bg-[#FDF6EC] rounded shadow p-4 ...">
            <h2 class="text-lg font-semibold mb-4">Categories</h2>
            <ul id="categoryList" class="space-y-2">
    <li class="category-item active" data-category="All">
        <button class="w-full bg-[#D2B29D] text-white py-2 rounded-lg hover:bg-[#6F4225] transition duration-300">All</button>
    </li>
    <?php foreach ($categories as $category): ?>
        <li class="category-item" data-category="<?php echo htmlspecialchars($category); ?>">
            <button class="w-full bg-[#D2B29D] text-white py-2 rounded-lg hover:bg-[#6F4225] transition duration-300"><?php echo htmlspecialchars($category); ?></button>
        </li>
    <?php endforeach; ?>
</ul>
    </section>

    <section class="w-2/5 bg-[#FDF6EC] rounded shadow p-4 ...">
            <h2 class="text-lg font-semibold mb-4">Products</h2>
            <div id="productGrid" class="grid grid-cols-2 gap-4">
                <?php foreach ($products as $product): ?>
                    <div class="product-card <?php echo ($product['stock'] <= 0) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'; ?>" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['stock']; ?>">
                        <img src="<?php echo htmlspecialchars(isset($product['image']) && $product['image'] ? 'product_images/' . $product['image'] : 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="mb-2 w-24 h-24 object-cover rounded" />
                        <div class="font-semibold"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="text indigo 600 font-bold">₱<?php echo number_format($product['price'], 2); ?></div>
                        <div class="text-sm <?php echo ($product['stock'] <= 0) ? 'text-red-600 font-bold' : 'text-gray-600'; ?>">
                            Stock: <?php echo $product['stock']; ?>
                            <?php if ($product['stock'] <= 0): ?>
                                <span> - Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section 3: Bill -->
        <section class="w-2/5 bg-[#FDF6EC] rounded shadow p-4 ...">
            <h2 class="text-lg font-semibold mb-4">Bill</h2>
            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form id="billForm" method="POST" action="sell_product.php" class="flex-grow flex flex-col">
                <input type="hidden" name="action" value="pay" />
                <input type="hidden" name="order" id="orderInput" />
            <div id="billItems" class="flex-grow overflow-y-auto border border-gray-300 rounded p-2 mb-4">
                <p class="text-gray-500">No orders added.</p>
            </div>
            <div class="mb-4 flex justify-between items-center">
                <div class="font-semibold text-lg">Total: <span id="billTotal">₱0.00</span></div>
                <label for="amount_paid" class="block font-semibold mb-1">Amount Paid</label>
                <input type="number" step="0.01" min="0" id="amount_paid" name="amount_paid" value="<?php echo htmlspecialchars($amount_paid ?? ''); ?>" required class="w-32 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div class="flex space-x-4">
            <button type="submit" id="payBtn" class="flex-1 bg-[#A97142] text-white py-2 rounded-lg hover:bg-[#8C5E30] transition duration-300">Pay</button>
            <button type="button" id="clearBillBtn" class="flex-1 bg-[#C8A17D] text-white py-2 rounded-lg hover:bg-[#A57C4D] transition duration-300">Clear</button>

        </section>
    </main>

    <script>
        const categoryList = document.getElementById('categoryList');
        const productGrid = document.getElementById('productGrid');
        const billItems = document.getElementById('billItems');
        const billTotal = document.getElementById('billTotal');
        const clearBillBtn = document.getElementById('clearBillBtn');
        const billForm = document.getElementById('billForm');
        const orderInput = document.getElementById('orderInput');
        const amountPaidInput = document.getElementById('amount_paid');

        let currentCategory = 'All';
        let bill = {};

        function filterProducts(category) {
            currentCategory = category;
            const productCards = productGrid.querySelectorAll('.product-card');
            productCards.forEach(card => {

                if (category === 'All' || card.dataset.category === category || card.dataset.category === 'All') {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            updateCategoryActive();
            addProductCardClickListeners(); 
        }


        function updateCategoryActive() {
            const categoryItems = categoryList.querySelectorAll('.category-item');
            categoryItems.forEach(item => {
                if (item.dataset.category === currentCategory) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }

                item.addEventListener('click', () => {
                    filterProducts(item.dataset.category);
                });
            });
        }

        function addToBill(id, name, price) {
            const productCard = document.querySelector(`.product-card[data-id="${id}"]`);
            const stock = parseInt(productCard.dataset.stock);
            if (stock <= 0) {
                alert('This product is out of stock and cannot be added.');
                return;
            }
            if (bill[id]) {
                if (bill[id].quantity < stock) {
                    bill[id].quantity += 1;
                } else {
                    alert('You have reached the maximum stock available for this product.');
                    return;
                }
            } else {
                bill[id] = { name, price, quantity: 1 };
            }
            renderBill();
        }

        function removeFromBill(id) {
            delete bill[id];
            renderBill();
        }
        function changeQuantity(id, quantity) {
            if (quantity <= 0) {
                removeFromBill(id);
            } else {
                bill[id].quantity = quantity;
            }
            renderBill();
        }

        function renderBill() {
            billItems.innerHTML = '';
            const ids = Object.keys(bill);
            if (ids.length === 0) {
                billItems.innerHTML = '<p class="text-gray-500">No items added.</p>';
                billTotal.textContent = '₱0.00';
                orderInput.value = '';
                amountPaidInput.value = '';
                return;
            }
            let total = 0;
            ids.forEach(id => {
                const item = bill[id];
                const itemTotal = item.price * item.quantity;
                total += itemTotal;

                const div = document.createElement('div');
                div.className = 'bill-item';

                div.innerHTML = `
                    <div class="flex-grow font-semibold">${item.name}</div>
                    <input type="number" min="1" value="${item.quantity}" class="w-16 border border-gray-300 rounded px-2 py-1 mr-2" />
                    <div class="w-20 text-right">₱${itemTotal.toFixed(2)}</div>
                    <button class="text-red-600 hover:text-red-800 ml-2 font-bold">&times;</button>
                `;

                const input = div.querySelector('input');
                input.addEventListener('change', (e) => {
                    const qty = parseInt(e.target.value);
                    if (isNaN(qty) || qty < 1) {
                        e.target.value = item.quantity;
                        return;
                    }
                    changeQuantity(id, qty);
                });

                const removeBtn = div.querySelector('button');
                removeBtn.addEventListener('click', () => {
                    removeFromBill(id);
                });

                billItems.appendChild(div);
            });
            billTotal.textContent = '₱' + total.toFixed(2);

            orderInput.value = JSON.stringify(bill);
        }

        clearBillBtn.addEventListener('click', () => {
            bill = {};
            renderBill();
        });

        productGrid.addEventListener('click', (event) => {
            let target = event.target;
            while (target && !target.classList.contains('product-card')) {
                target = target.parentElement;
            }
            if (target && target.classList.contains('product-card')) {
                addToBill(target.dataset.id, target.dataset.name, parseFloat(target.dataset.price));
            }
        });

        filterProducts('All');
    </script>
    <?php if ($print_bill_data): ?>
    <script>
        (function() {
            const printData = <?php echo json_encode($print_bill_data); ?>;
            function printReceipt(data) {
                const printWindow = window.open('', '', 'width=600,height=600');
                printWindow.document.write('<html><head><title>Receipt</title>');
                printWindow.document.write('<style>body{font-family: Arial, sans-serif; padding: 20px;} h2{text-align:center;} table{width:100%;border-collapse: collapse;margin-top: 20px;} th, td{border:1px solid #ccc;padding:8px;text-align:left;} th{background:#f4f4f4;} .total-row td{font-weight:bold;} .summary{margin-top: 20px; font-weight: bold;}</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write('<h2>Coffee Shop Receipt</h2>');
                printWindow.document.write('<table><thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead><tbody>');
                let total = 0;
                for (const [id, item] of Object.entries(data.order)) {
                    const itemTotal = item.price * item.quantity;
                    printWindow.document.write('<tr><td>' + item.name + '</td><td>' + item.quantity + '</td><td>₱' + item.price.toFixed(2) + '</td><td>₱' + itemTotal.toFixed(2) + '</td></tr>');
                    total += itemTotal;
                }
                printWindow.document.write('<tr class="total-row"><td colspan="3" style="text-align:right;">Total</td><td>₱' + total.toFixed(2) + '</td></tr>');
                printWindow.document.write('</tbody></table>');
                printWindow.document.write('<div class="summary">Amount Paid: ₱' + data.amount_paid.toFixed(2) + '</div>');
                printWindow.document.write('<div class="summary">Change: ₱' + data.change.toFixed(2) + '</div>');
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            }
            printReceipt(printData);
        })();
    </script>
    <?php endif; ?>
</body>
</html>
