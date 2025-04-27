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

/**
 * Process the payment and save the sale records.
 * Returns an array with keys: success (bool), message (string), change (float), order (array), amount_paid (float)
 */
function processPayment($db, $order, $amount_paid, $user_id) {
    $total_amount = 0;

    // Validate order and calculate total
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

    // Save sales and update stock in transaction
    try {
        $db->beginTransaction();

        // Insert a new sale order record to group the items
        $stmt = $db->prepare("INSERT INTO sales_orders (cashier_id, sale_time) VALUES (:cashier_id, NOW())");
        // For admin user, assign cashier_id as NULL and allow NULL in DB or assign a valid user id
        if ($_SESSION['role'] === 'cashier') {
            $cashierIdToUse = $user_id;
        } else {
            // Assign admin's user id or NULL if DB allows
            $cashierIdToUse = $user_id; // or NULL if you modify DB to allow NULL
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

            // Insert each item with reference to sale_order_id
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
        return ['success' => true, 'message' => "Payment successful. Total: $" . number_format($total_amount, 2) . ". Change: $" . number_format($change, 2), 'change' => $change, 'order' => $order, 'amount_paid' => $amount_paid, 'sale_order_id' => $sale_order_id];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => "Error processing payment: " . $e->getMessage()];
    }
}

// Handle payment form submission
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

// Fetch categories and products for display
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
            background-color: #4f46e5; /* Indigo-600 */
            color: white;
        }
        .product-card {
            cursor: pointer;
            border: 1px solid #d1d5db; /* Gray-300 */
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
            border-bottom: 1px solid #e5e7eb; /* Gray-200 */
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-indigo-600 text-white p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">Sell Product</h1>
        <div>
            <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'cashier_dashboard.php'; ?>" class="hover:underline mr-4 text-white">Dashboard</a>
            <a href="logout.php" class="hover:underline text-white">Logout</a>
        </div>
    </header>
    <div class="mb-4">
            <a href="admin_dashboard.php" class="inline-block text-indigo-600 hover:underline">&larr; Back to Dashboard</a>
        </div>
    <main class="p-6 max-w-7xl mx-auto flex space-x-6">
        <!-- Section 1: Categories -->
        <section class="w-1/5 bg-white rounded shadow p-4 overflow-y-auto max-h-[80vh]">
            <h2 class="text-lg font-semibold mb-4">Categories</h2>
            <ul id="categoryList" class="space-y-2">
                <li class="category-item active" data-category="All">All</li>
                <?php foreach ($categories as $category): ?>
                    <li class="category-item" data-category="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- Section 2: Products -->
        <section class="w-2/5 bg-white rounded shadow p-4 overflow-y-auto max-h-[80vh]">
            <h2 class="text-lg font-semibold mb-4">Products</h2>
            <div id="productGrid" class="grid grid-cols-2 gap-4">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>">
                        <div class="font-semibold"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="text-indigo-600 font-bold">$<?php echo number_format($product['price'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section 3: Bill -->
        <section class="w-2/5 bg-white rounded shadow p-4 flex flex-col max-h-[80vh]">
            <h2 class="text-lg font-semibold mb-4">Bill</h2>
            <?php if ($message): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form id="billForm" method="POST" action="sell_product.php" class="flex-grow flex flex-col">
                <input type="hidden" name="action" value="pay" />
                <input type="hidden" name="order" id="orderInput" />
            <div id="billItems" class="flex-grow overflow-y-auto border border-gray-300 rounded p-2 mb-4">
                <p class="text-gray-500">No items added.</p>
            </div>
            <div class="mb-4 flex justify-between items-center">
                <div class="font-semibold text-lg">Total: <span id="billTotal">$0.00</span></div>
                <label for="amount_paid" class="block font-semibold mb-1">Amount Paid</label>
                <input type="number" step="0.01" min="0" id="amount_paid" name="amount_paid" value="<?php echo htmlspecialchars($amount_paid ?? ''); ?>" required class="w-32 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div class="flex space-x-4">
                <button type="submit" id="payBtn" class="flex-1 bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">Pay</button>
                <button type="button" id="clearBillBtn" class="flex-1 bg-red-600 text-white py-2 rounded hover:bg-red-700 transition">Clear Bill</button>
            </div>
            </form>
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

        // Filter products by category
        function filterProducts(category) {
            currentCategory = category;
            const productCards = productGrid.querySelectorAll('.product-card');
            productCards.forEach(card => {
                // Show products with category matching the selected category or category 'All'
                if (category === 'All' || card.dataset.category === category || card.dataset.category === 'All') {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
            updateCategoryActive();
            addProductCardClickListeners(); // Re-add click listeners after filtering
        }


        // Update active category highlight
        function updateCategoryActive() {
            const categoryItems = categoryList.querySelectorAll('.category-item');
            categoryItems.forEach(item => {
                if (item.dataset.category === currentCategory) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
                // Add click event listener to category items to filter products
                item.addEventListener('click', () => {
                    filterProducts(item.dataset.category);
                });
            });
        }

        // Add product to bill
        function addToBill(id, name, price) {
            if (bill[id]) {
                bill[id].quantity += 1;
            } else {
                bill[id] = { name, price, quantity: 1 };
            }
            renderBill();
        }

        // Remove product from bill
        function removeFromBill(id) {
            delete bill[id];
            renderBill();
        }

        // Change quantity in bill
        function changeQuantity(id, quantity) {
            if (quantity <= 0) {
                removeFromBill(id);
            } else {
                bill[id].quantity = quantity;
            }
            renderBill();
        }

        // Render bill items
        function renderBill() {
            billItems.innerHTML = '';
            const ids = Object.keys(bill);
            if (ids.length === 0) {
                billItems.innerHTML = '<p class="text-gray-500">No items added.</p>';
                billTotal.textContent = '$0.00';
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
                    <div class="w-20 text-right">$${itemTotal.toFixed(2)}</div>
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
            billTotal.textContent = '$' + total.toFixed(2);

            // Update hidden input with order JSON
            orderInput.value = JSON.stringify(bill);
        }

        // Clear bill
        clearBillBtn.addEventListener('click', () => {
            bill = {};
            renderBill();
        });

        // Add click event to product cards
        function addProductCardClickListeners() {
            productGrid.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('click', () => {
                    addToBill(card.dataset.id, card.dataset.name, parseFloat(card.dataset.price));
                });
            });
        }
        addProductCardClickListeners();

        // Show all products initially
        filterProducts('All');
    </script>
</body>
</html>
