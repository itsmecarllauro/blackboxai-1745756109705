<?php
// config.php - Database connection using MySQL

$host = 'localhost';
$dbname = 'coffee_shop';
$user = 'root';
$pass = '';

$db = null;

function connectDB() {
    global $host, $dbname, $user, $pass, $db;
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die("DB ERROR: " . $e->getMessage());
    }
}

$db = connectDB();
?>
