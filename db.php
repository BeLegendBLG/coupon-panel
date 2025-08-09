<?php
// db.php - Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "coupon_panel";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to check expired coupons and update status
function updateExpiredCoupons() {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE coupons SET status = 'expired' WHERE end_date < CURDATE() AND status != 'expired'");
    $stmt->execute();
}

// Function to format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function to format date
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Update expired coupons on every page load
updateExpiredCoupons();
?>
