<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
    <style>
        body {
    background-color:#fdf6e3;
    font-family: 'Roboto', sans-serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
    </style>

</head>

<div class="bg-[#E6D1B3] p-8 rounded shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Coffee Shop</h1>
        <?php
        session_start();
        if (isset($_SESSION['user_id'])) {
            if ($_SESSION['role'] === 'admin') {
                header('Location: admin_dashboard.php');
                exit;
            } elseif ($_SESSION['role'] === 'cashier') {
                header('Location: cashier_dashboard.php');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require 'config.php';

            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if (empty($username) || empty($password)) {
                $error = "Please enter both username and password.";
            } else {
                $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                        exit;
                    } else {
                        header('Location: cashier_dashboard.php');
                        exit;
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            }
        }
        ?>
        <?php if (!empty($error)) : ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="username" class="block mb-1 font-semibold">Username</label>
                <input type="text" id="username" name="username" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
            </div>
            <div>
                <label for="password" class="block mb-1 font-semibold">Password</label>
                <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
            </div>
            <!-- Updated Login Button with Light Brown Color -->
            <button type="submit" class="w-full bg-[#A97142] text-white py-2 rounded hover:bg-[#916235] transition">Login</button>
        </form>
    </div>
</body>
</html>
