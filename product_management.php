<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require 'config.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category = trim($_POST['category'] ?? 'Uncategorized');
    $id = intval($_POST['id'] ?? 0);

    $imageFileName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'product_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $tmpName = $_FILES['image']['tmp_name'];
        $originalName = basename($_FILES['image']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($extension, $allowedExtensions)) {
            $imageFileName = uniqid() . '.' . $extension;
            $destination = $uploadDir . $imageFileName;
            move_uploaded_file($tmpName, $destination);
        } else {
            $message = "Invalid image file type. Allowed types: jpg, jpeg, png, gif.";
        }
    }

    if ($action === 'add' && $name && $price > 0) {
        $stmt = $db->prepare("INSERT INTO products (name, price, stock, category, image) VALUES (:name, :price, :stock, :category, :image)");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':stock', $stock);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':image', $imageFileName);
        $stmt->execute();
        $message = "Product added successfully.";
    } elseif ($action === 'update' && $id && $name && $price > 0) {
        if ($imageFileName) {
            $stmt = $db->prepare("UPDATE products SET name = :name, price = :price, stock = :stock, category = :category, image = :image WHERE id = :id");
            $stmt->bindValue(':image', $imageFileName);
        } else {
            $stmt = $db->prepare("UPDATE products SET name = :name, price = :price, stock = :stock, category = :category WHERE id = :id");
        }
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':stock', $stock);
        $stmt->bindValue(':category', $category);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $message = "Product updated successfully.";
    } elseif ($action === 'delete' && $id) {
        $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $message = "Product deleted successfully.";
    }
}

$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Product Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="bg-[#F5F5DC] min-h-screen"> <!-- light beige -->
<header class="bg-[#A97142] text-white p-4 flex justify-between items-center"> <!-- nude brown -->
        <h1 class="text-xl font-bold">Product Management</h1>
        <div>
            <a href="admin_dashboard.php" class=" mr-4 text-white">Dashboard</a>
            <a href="logout.php" class="text-white">Logout</a>
        </div>
    </header>
    <div class="mb-4">
    <a href="admin_dashboard.php" class="inline-block text-[#A97142]">&larr; Back to Dashboard</a>
        </div>
    <main class="p-6 max-w-4xl mx-auto">
        <?php if ($message): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <section class="mb-8">
            <h2 class="text-lg font-semibold mb-4">Add New Product</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4 bg-[#FDF6EC] p-6 rounded shadow">


                <input type="hidden" name="action" value="add" />
                <div>
                    <label for="name" class="block mb-1 font-semibold">Product Name</label>
                    <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div>
                    <label for="price" class="block mb-1 font-semibold">Price</label>
                    <input type="number" step="0.01" id="price" name="price" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div>
                    <label for="stock" class="block mb-1 font-semibold">Stock</label>
                    <input type="number" id="stock" name="stock" value="0" min="0" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div>
                    <label for="category" class="block mb-1 font-semibold">Category</label>
                    <input type="text" id="category" name="category" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>
                <div>
                    <label for="image" class="block mb-1 font-semibold">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*" class="w-full" />
                </div>
                <button type="submit" class="bg-[#A97142] text-white py-2 px-4 rounded hover:bg-[#8b5e34] transition">Add Product</button>

            </form>
        </section>
        <section>
            <h2 class="text-lg font-semibold mb-4">Existing Products</h2>
            <table class="w-full bg-[#FDF6EC] rounded shadow overflow-hidden">
            <thead class="bg-[#A97142] text-white">

                    <tr>
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Price</th>
                        <th class="p-3 text-left">Stock</th>
                        <th class="p-3 text-left">Category</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr class="border-b border-gray-200">
                        <td class="p-3"><?php echo $product['id']; ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td class="p-3">₱<?php echo number_format($product['price'], 2); ?></td>
                        <td class="p-3"><?php echo $product['stock']; ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($product['category']); ?></td>
                    <td class="p-3 space-x-2">
                    <button onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['price']; ?>, <?php echo $product['stock']; ?>, '<?php echo htmlspecialchars(addslashes($product['category'])); ?>', '<?php echo htmlspecialchars(addslashes($product['image'] ?? '')); ?>')" class="bg-[#C8A17D] text-white px-3 py-1 rounded hover:bg-[#A57C4D] transition">Edit</button>

                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>" />
                            <button type="submit" class="bg-[#A97142] text-white px-3 py-1 rounded hover:bg-[#8C5730] transition">Delete</button>

                        </form>
                    </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" class="p-3 text-center text-gray-500">No products found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-[#FDF6EC] p-6 rounded shadow max-w-md w-full">

                <h3 class="text-lg font-semibold mb-4">Edit Product</h3>
                <form method="POST" id="editForm" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="id" id="editId" />
                    <div>
                        <label for="editName" class="block mb-1 font-semibold">Product Name</label>
                        <input type="text" id="editName" name="name" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="editPrice" class="block mb-1 font-semibold">Price</label>
                        <input type="number" step="0.01" id="editPrice" name="price" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="editStock" class="block mb-1 font-semibold">Stock</label>
                        <input type="number" id="editStock" name="stock" min="0" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="editCategory" class="block mb-1 font-semibold">Category</label>
                        <input type="text" id="editCategory" name="category" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="editImage" class="block mb-1 font-semibold">Product Image</label>
                        <input type="file" id="editImage" name="image" accept="image/*" class="w-full" />
                        <div id="currentImageContainer" class="mt-2"></div>
                    </div>
                    <div class="flex justify-end space-x-4">
                    
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded border border-[#C8A17D] bg-[#C8A17D] hover:bg-[#A57C4D] transition">Cancel</button>
                        <button type="submit" class="bg-[#A97142] text-white px-4 py-2 rounded hover:bg-[#8c5e30] transition">Save</button>

                    </div>
                </form>
            </div>
        </div>

    </main>
    <script>
        function editProduct(id, name, price, stock, category, image) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editPrice').value = price;
            document.getElementById('editStock').value = stock;
            document.getElementById('editCategory').value = category;
            const currentImageContainer = document.getElementById('currentImageContainer');
            if (image) {
                currentImageContainer.innerHTML = `<img src="product_images/${image}" alt="Current Image" class="w-24 h-24 object-cover rounded" />`;
            } else {
                currentImageContainer.innerHTML = '';
            }
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>
