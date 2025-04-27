<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
    header('Location: index.php');
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cashier Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-indigo-600 text-white p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">Cashier Dashboard</h1>
        <div>
            <span class="mr-4">Welcome, <?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php" class="hover:underline">Logout</a>
        </div>
    </header>
    <main class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="sell_product.php" class="bg-white p-6 rounded shadow hover:shadow-lg transition flex flex-col items-center">
            <i class="fas fa-cash-register fa-3x mb-4 text-indigo-600"></i>
            <h2 class="text-lg font-semibold">Sell Product</h2>
        </a>
        <a href="sales_view.php" class="bg-white p-6 rounded shadow hover:shadow-lg transition flex flex-col items-center">
            <i class="fas fa-chart-line fa-3x mb-4 text-indigo-600"></i>
            <h2 class="text-lg font-semibold">View Sales</h2>
        </a>
    </main>
</body>
</html>
