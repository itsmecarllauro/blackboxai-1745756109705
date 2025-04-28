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
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($action === 'add' && $username && $password && in_array($role, ['admin', 'cashier'])) {
        // Check if username exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        if ($stmt->fetch()) {
            $message = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':password', $hashed_password);
            $stmt->bindValue(':role', $role);
            $stmt->execute();
            $message = "Staff added successfully.";
        }
    } elseif ($action === 'update' && $id && $username && in_array($role, ['admin', 'cashier'])) {
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = :username, password = :password, role = :role WHERE id = :id");
            $stmt->bindValue(':password', $hashed_password);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = :username, role = :role WHERE id = :id");
        }
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':role', $role);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $message = "Staff updated successfully.";
    } elseif ($action === 'delete' && $id) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $message = "Staff deleted successfully.";
    }
}

$staff = $db->query("SELECT id, username, role FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Staff Management</title>
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
        <h1 class="text-xl font-bold">Staff Management</h1>
        <div>
            <a href="admin_dashboard.php" class="mr-4 text-white">Dashboard</a>
            <a href="logout.php" class="text-white">Logout</a>
        </div>
    </header>
    <div class="mb-4">
            <a href="admin_dashboard.php" class="inline-block text-indigo-600 ">&larr; Back to Dashboard</a>
        </div>
    <main class="p-6 max-w-4xl mx-auto">
        <?php if ($message): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <section class="mb-8 flex justify-center">
            <div class="w-full max-w-md">
                <h2 class="text-lg font-semibold mb-4 text-center">Add New Staff</h2>
                <form method="POST" class="space-y-4 bg-white p-6 rounded shadow">
                    <input type="hidden" name="action" value="add" />
                    <div>
                        <label for="username" class="block mb-1 font-semibold">Username</label>
                        <input type="text" id="username" name="username" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="password" class="block mb-1 font-semibold">Password</label>
                        <input type="password" id="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="role" class="block mb-1 font-semibold">Role</label>
                        <select id="role" name="role" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 transition">Add Staff</button>
                </form>
            </div>
        </section>
        <section>
            <h2 class="text-lg font-semibold mb-4">Existing Staff</h2>
            <table class="w-full bg-white rounded shadow overflow-hidden">
                <thead class="bg-indigo-600 text-white">
                    <tr>
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Username</th>
                        <th class="p-3 text-left">Role</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $member): ?>
                    <tr class="border-b border-gray-200">
                        <td class="p-3"><?php echo $member['id']; ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($member['username']); ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($member['role']); ?></td>
                        <td class="p-3 space-x-2">
                            <button onclick="editStaff(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars(addslashes($member['username'])); ?>', '<?php echo $member['role']; ?>')" class="bg-yellow-400 text-white px-3 py-1 rounded hover:bg-yellow-500 transition">Edit</button>
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?php echo $member['id']; ?>" />
                                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($staff)): ?>
                    <tr>
                        <td colspan="4" class="p-3 text-center text-gray-500">No staff found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white p-6 rounded shadow max-w-md w-full">
                <h3 class="text-lg font-semibold mb-4">Edit Staff</h3>
                <form method="POST" id="editForm" class="space-y-4">
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="id" id="editId" />
                    <div>
                        <label for="editUsername" class="block mb-1 font-semibold">Username</label>
                        <input type="text" id="editUsername" name="username" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="editPassword" class="block mb-1 font-semibold">Password (leave blank to keep current)</label>
                        <input type="password" id="editPassword" name="password" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label for="editRole" class="block mb-1 font-semibold">Role</label>
                        <select id="editRole" name="role" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="admin">Admin</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">Cancel</button>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">Save</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
    <script>
        function editStaff(id, username, role) {
            document.getElementById('editId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editRole').value = role;
            document.getElementById('editPassword').value = '';
            document.getElementById('editModal').classList.remove('hidden');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>
