<?php
// customers.php - ADMIN FACING
session_start();
require_once 'db_connection.php';

// Admin Access Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$customers = [];

// Try to fetch customers WITH their pending orders count
try {
    // This query counts how many 'Pending' orders belong to each customer
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM Orders o WHERE o.customer_id = c.customer_id AND o.status = 'Pending') AS pending_orders 
            FROM Customers c 
            ORDER BY c.first_name ASC";
            
    $result = $conn->query($sql);
    
    // If the query fails (e.g., Orders table doesn't exist yet), it throws an exception
    if (!$result) {
        throw new Exception("Orders table not found or missing columns.");
    }
    
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
} catch (Exception $e) {
    // FALLBACK: If your Orders table isn't set up yet, it safely loads the customers anyway!
    $sql_fallback = "SELECT *, 0 AS pending_orders FROM Customers ORDER BY first_name ASC";
    $result_fallback = $conn->query($sql_fallback);
    if ($result_fallback) {
        while ($row = $result_fallback->fetch_assoc()) {
            $customers[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Client Directory</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="store-body">

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="color: white; margin-top: 15px; font-size: 28px; margin-bottom: 30px;">👥 Client Directory</h1>

        <div style="background: #1f2937; border: 1px solid #374151; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; color: white; font-size: 20px;">Registered Clients</h2>
            <input type="text" placeholder="🔍 Search by name or phone..." style="padding: 10px 15px; background: #111827; border: 1px solid #374151; color: white; border-radius: 6px; width: 300px;">
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            
            <?php if (empty($customers)): ?>
                <p style="color: #9ca3af;">No customers registered yet.</p>
            <?php else: ?>
                <?php foreach ($customers as $client): ?>
                    
                    <?php 
                        // Get the first letter of their first name for the avatar
                        $initial = strtoupper(substr($client['first_name'], 0, 1)); 
                        $fullName = htmlspecialchars($client['first_name'] . ' ' . $client['last_name']);
                        $phone = !empty($client['phone_number']) ? htmlspecialchars($client['phone_number']) : 'No phone on record';
                        $email = !empty($client['email']) ? htmlspecialchars($client['email']) : 'No email on record';
                        $pending_count = isset($client['pending_orders']) ? intval($client['pending_orders']) : 0;
                    ?>

                    <div class="client-card" style="background: #1f2937; border: 1px solid #374151; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px; position: relative; transition: 0.2s;">
                        
                        <div style="width: 50px; height: 50px; background: #374151; color: white; font-weight: bold; font-size: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%; flex-shrink: 0;">
                            <?php echo $initial; ?>
                        </div>

                        <div style="flex-grow: 1; overflow: hidden;">
                            <h3 style="margin: 0 0 5px 0; color: white; font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo $fullName; ?>
                            </h3>
                            <div style="color: #9ca3af; font-size: 12px; margin-bottom: 3px;">📞 <?php echo $phone; ?></div>
                            <div style="color: #9ca3af; font-size: 12px;">✉️ <?php echo $email; ?></div>
                        </div>

                        <?php if ($pending_count > 0): ?>
                            <div style="position: absolute; top: -10px; right: -10px; background: #f59e0b; color: #111827; font-weight: bold; font-size: 12px; padding: 4px 10px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                📦 <?php echo $pending_count; ?> Pending
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>