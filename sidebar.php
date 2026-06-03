<?php

$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'Staff'; 
?>
<div class="sidebar">
    <h2>MOTIFY.</h2>
    <div style="text-align: center; color: #9ca3af; font-size: 12px; margin-top: -15px; margin-bottom: 20px;">
        Logged in as: <strong style="color: #10b981;"><?= $user_role ?></strong>
    </div>

    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>
    <a href="inventory.php" class="<?= $current_page == 'inventory.php' ? 'active' : '' ?>">📦 Inventory</a>
    <a href="sales.php" class="<?= $current_page == 'sales.php' ? 'active' : '' ?>">🛒 POS Terminal</a>
    <a href="customers.php" class="<?= $current_page == 'customers.php' ? 'active' : '' ?>">👥 Customers</a>
    <a href="services.php" class="<?= $current_page == 'services.php' ? 'active' : '' ?>">🔧 Services</a>
    
    <?php if ($user_role === 'Admin'): ?>
        <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">📊 Reports</a>
    <?php endif; ?>
    
    <a href="logout.php" class="logout-btn">🚪 Logout</a>
</div>