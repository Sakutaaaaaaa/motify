<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'Staff'; 
?>
<div class="sidebar">
    <h2>MOTIFY.</h2>
    <div style="text-align: center; color: #9ca3af; font-size: 12px; margin-top: -15px; margin-bottom: 20px;">
        Logged in as: <strong style="color: #10b981;"><?= htmlspecialchars($user_role) ?></strong>
    </div>

    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">🏠 Dashboard</a>
    
    <a href="inventory.php" class="<?= in_array($current_page, ['inventory.php', 'edit_product.php']) ? 'active' : '' ?>">📦 Inventory</a>
    
    <a href="sales.php" class="<?= $current_page == 'sales.php' ? 'active' : '' ?>">🛒 POS Terminal</a>
    <a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>">📦 Online Orders</a>
    <a href="customers.php" class="<?= $current_page == 'customers.php' ? 'active' : '' ?>">👥 Customers</a>
    <a href="services.php" class="<?= $current_page == 'services.php' ? 'active' : '' ?>">🔧 Services</a>
    
    <?php if ($user_role === 'Admin'): ?>
        <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">📊 Reports</a>
    <?php endif; ?>
    
    <a href="shop.php" target="_blank" style="margin-top: 30px; border: 1px dashed #3b82f6; color: #3b82f6; background: rgba(59, 130, 246, 0.05); text-align: center; border-radius: 6px; transition: 0.2s;" onmouseover="this.style.background='rgba(59, 130, 246, 0.15)'" onmouseout="this.style.background='rgba(59, 130, 246, 0.05)'">
        🌐 View Live Store
    </a>

    <a href="logout.php" class="logout-btn" style="margin-top: 15px;">🚪 Logout</a>
</div>