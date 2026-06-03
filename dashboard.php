<?php
// dashboard.php
session_start();
require_once 'db_connection.php';
// if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// KPI Queries
$rev_query = $conn->query("SELECT SUM(total_amount) as total FROM Sales");
$total_revenue = $rev_query->fetch_assoc()['total'] ?? 0;

$prod_query = $conn->query("SELECT COUNT(*) as count FROM Products WHERE category='Product'");
$total_products = $prod_query->fetch_assoc()['count'] ?? 0;

$stock_query = $conn->query("SELECT COUNT(*) as count FROM Inventory WHERE stock_quantity <= 5");
$low_stock = $stock_query->fetch_assoc()['count'] ?? 0;

$cust_query = $conn->query("SELECT COUNT(*) as count FROM Customers");
$total_customers = $cust_query->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Motify - Garage Command Center</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-top: 0;">Garage Command Center</h1>
        <p style="color: #9ca3af; margin-bottom: 30px;">Welcome back, Admin.</p>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Revenue</h3>
                <div class="value">₱<?= number_format($total_revenue, 2) ?></div>
            </div>
            <div class="card">
                <h3>Total Products</h3>
                <div class="value"><?= $total_products ?></div>
            </div>
            <div class="card">
                <h3>Low Stock Alerts</h3>
                <div class="value" style="color: #ef4444;"><?= $low_stock ?> <span style="font-size: 12px;">⚠</span></div>
            </div>
            <div class="card">
                <h3>Total Customers</h3>
                <div class="value"><?= $total_customers ?></div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 20px;">
            <h3>Sales Analytics</h3>
            <canvas id="salesChart" height="80"></canvas>
        </div>
    </div>

    <script src="script.js"></script>

</body>
</html>