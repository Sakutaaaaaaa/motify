<?php
session_start();
require_once 'db_connection.php';

// Fetch Notifications if the user is logged in
$notifications = [];
$unread_count = 0;

if (isset($_SESSION['customer_id'])) {
    $cid = $_SESSION['customer_id'];
    
    // Fetch Notifications
    $notif_result = $conn->query("SELECT * FROM notifications WHERE customer_id = $cid ORDER BY created_at DESC LIMIT 5");
    if ($notif_result) {
        while($n = $notif_result->fetch_assoc()) {
            $notifications[] = $n;
            if ($n['is_read'] == 0) $unread_count++;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify Garage - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="store-body">

    <header class="store-header">
        <a href="home.php" class="store-logo">MOTIFY.</a>
        <nav class="store-nav-links">
            <a href="home.php" style="color: #ef4444;">Home</a>
            <a href="shop.php">Shop</a>
            <a href="booking.php">Services</a>
            <a href="shop.php">Wishlist ❤️</a>
            <a href="account.php">Account</a>
        </nav>
        
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if(isset($_SESSION['customer_id'])): ?>
                <div style="position: relative; display: inline-block; cursor: pointer; padding-top: 5px;" onclick="document.getElementById('notif-dropdown').style.display = document.getElementById('notif-dropdown').style.display === 'block' ? 'none' : 'block';">
                    <span style="font-size: 28px; display: inline-block; transition: 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">🔔</span>
                    <?php if($unread_count > 0): ?>
                        <span style="position: absolute; top: -2px; right: -5px; background: #ef4444; color: white; border-radius: 50%; padding: 3px 7px; font-size: 11px; font-weight: bold; box-shadow: 0 0 0 3px #111827;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                    
                    <div id="notif-dropdown" style="display: none; position: absolute; top: 45px; right: 0; left: auto; width: 300px; background: #1f2937; border: 1px solid #374151; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5); z-index: 100; text-align: left;">
                        <div style="padding: 10px 15px; border-bottom: 1px solid #374151; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: white;">Recent Notifications</strong>
                            <?php if($unread_count > 0): ?>
                                <a href="account.php?read_notif=true" style="font-size: 11px; color: #3b82f6; text-decoration: none;">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if (empty($notifications)): ?>
                                <div style="padding: 15px; text-align: center; color: #9ca3af; font-size: 13px;">No new notifications.</div>
                            <?php else: ?>
                                <?php foreach($notifications as $notif): ?>
                                    <div style="padding: 15px; border-bottom: 1px solid #374151; background: <?php echo $notif['is_read'] ? 'transparent' : 'rgba(59, 130, 246, 0.05)'; ?>;">
                                        <div style="font-size: 13px; font-weight: bold; color: <?php echo $notif['is_read'] ? '#d1d5db' : '#3b82f6'; ?>; margin-bottom: 4px;"><?php echo htmlspecialchars($notif['title']); ?></div>
                                        <div style="font-size: 12px; color: #9ca3af;"><?php echo htmlspecialchars($notif['message']); ?></div>
                                        <div style="font-size: 10px; color: #6b7280; margin-top: 6px;"><?php echo date("M d, h:i A", strtotime($notif['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="store-hero-banner" style="padding: 150px 20px; min-height: 65vh; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
        <div class="store-hero-content">
            <h1 style="font-size: 5rem; margin-bottom: 15px;">Welcome to <span>Motify Garage</span></h1>
            <p style="font-size: 1.5rem; margin-bottom: 40px; color: #d1d5db; max-width: 800px;">Your ultimate destination for premium motorcycle parts and expert services.</p>
            <a href="shop.php" class="btn-generate" style="padding: 18px 40px; font-size: 1.2rem; text-decoration: none; display: inline-block;">ENTER THE SHOP 🛒</a>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>