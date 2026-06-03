<?php
// customers.php - ADMIN / STAFF CUSTOMER DIRECTORY
session_start();
require_once 'db_connection.php';

// Gatekeeper: Only Staff and Admins allowed
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch all customers from the database
$query = "SELECT customer_id, first_name, last_name, phone_number, email, created_at FROM customers ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Customer Directory</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                    👥 Customer Directory
                </h1>
                <p style="margin: 5px 0 0 0; color: #9ca3af;">View and manage registered riders and walk-in clients.</p>
            </div>
            
            <div>
                <input type="text" placeholder="Search customers..." style="padding: 10px 15px; border-radius: 6px; background: #111827; border: 1px solid #374151; color: white; width: 250px;">
            </div>
        </div>

        <div style="background: #1f2937; border-radius: 12px; border: 1px solid #374151; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: rgba(17, 24, 39, 0.5); border-bottom: 1px solid #374151; color: #9ca3af; font-size: 12px; text-transform: uppercase;">
                        <th style="padding: 15px 20px;">ID</th>
                        <th style="padding: 15px 20px;">Customer Name</th>
                        <th style="padding: 15px 20px;">Contact Info</th>
                        <th style="padding: 15px 20px;">Account Status</th>
                        <th style="padding: 15px 20px;">Date Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $formatted_date = date("M d, Y", strtotime($row['created_at']));
                            
                            // Determine if they are a registered user (has email) or a walk-in (no email)
                            $is_registered = !empty($row['email']);
                            $badge_color = $is_registered ? '#10b981' : '#f59e0b';
                            $badge_bg = $is_registered ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)';
                            $badge_text = $is_registered ? 'Registered' : 'Walk-in';
                        ?>
                            <tr style="border-bottom: 1px solid #374151; color: white; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 15px 20px; color: #9ca3af;">#<?php echo $row['customer_id']; ?></td>
                                
                                <td style="padding: 15px 20px; font-weight: bold; display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 35px; height: 35px; background: #374151; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold;">
                                        <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                </td>
                                
                                <td style="padding: 15px 20px;">
                                    <div style="font-size: 14px;"><?php echo htmlspecialchars($row['email'] ?? 'No email provided'); ?></div>
                                    <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">📞 <?php echo htmlspecialchars($row['phone_number'] ?? 'No phone'); ?></div>
                                </td>
                                
                                <td style="padding: 15px 20px;">
                                    <span style="color: <?php echo $badge_color; ?>; background: <?php echo $badge_bg; ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid <?php echo $badge_color; ?>;">
                                        <?php echo $badge_text; ?>
                                    </span>
                                </td>
                                
                                <td style="padding: 15px 20px; color: #9ca3af; font-size: 14px;">
                                    <?php echo $formatted_date; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: #9ca3af;">No customers found in the database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>