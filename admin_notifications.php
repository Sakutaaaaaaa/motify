<?php
// admin_notifications.php
session_start();
require_once 'db_connection.php';

// Mark a single notification as read
if (isset($_GET['read_id'])) {
    $id = intval($_GET['read_id']);
    $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE id = $id");
    header("Location: admin_notifications.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all'])) {
    $conn->query("UPDATE admin_notifications SET is_read = 1");
    header("Location: admin_notifications.php");
    exit();
}

// Fetch all notifications
$notifications = $conn->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 50");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Admin Notifications</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1 class="dash-header">Admin Notifications</h1>
                <p class="dash-subtitle">Recent orders, service bookings, and system alerts.</p>
            </div>
            <?php if($admin_unread_count > 0): ?>
                <a href="admin_notifications.php?mark_all=true" style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #3b82f6; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.2s;" onmouseover="this.style.background='#3b82f6'; this.style.color='white';" onmouseout="this.style.background='rgba(59, 130, 246, 0.1)'; this.style.color='#3b82f6';">✓ Mark All as Read</a>
            <?php endif; ?>
        </div>

        <div style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
            <?php if ($notifications && $notifications->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php while($notif = $notifications->fetch_assoc()): 
                        $is_unread = ($notif['is_read'] == 0);
                        $bg_color = $is_unread ? 'rgba(59, 130, 246, 0.05)' : 'transparent';
                        $border_color = $is_unread ? '#3b82f6' : '#374151';
                    ?>
                        <div style="background: <?php echo $bg_color; ?>; border: 1px solid <?php echo $border_color; ?>; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                    <?php if($is_unread): ?>
                                        <span style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #ef4444;"></span>
                                    <?php endif; ?>
                                    <strong style="color: <?php echo $is_unread ? 'white' : '#d1d5db'; ?>; font-size: 16px;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                                </div>
                                <div style="color: #9ca3af; font-size: 14px; margin-left: <?php echo $is_unread ? '20px' : '0'; ?>; margin-bottom: 8px;">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </div>
                                <div style="color: #6b7280; font-size: 12px; margin-left: <?php echo $is_unread ? '20px' : '0'; ?>;">
                                    <?php echo date("F d, Y • h:i A", strtotime($notif['created_at'])); ?>
                                </div>
                            </div>
                            
                            <?php if($is_unread): ?>
                                <a href="admin_notifications.php?read_id=<?php echo $notif['id']; ?>" style="color: #9ca3af; text-decoration: none; font-size: 20px; border-radius: 50%; padding: 5px 10px; transition: 0.2s;" onmouseover="this.style.color='#10b981'; this.style.background='rgba(16, 185, 129, 0.1)';" title="Mark as read">✓</a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 0; color: #9ca3af;">
                    <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;">📭</div>
                    <p>No system notifications at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>