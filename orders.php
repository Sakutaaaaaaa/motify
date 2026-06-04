<?php
// orders.php - ADMIN / STAFF ONLINE ORDERS
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// --- HANDLE STATUS UPDATES & NOTIFY CUSTOMER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $sales_id = intval($_POST['sales_id']); 
    $new_status = $_POST['new_status'];
    $customer_id = intval($_POST['customer_id']);
    $product_name = $_POST['product_name'];

    $stmt = $conn->prepare("UPDATE sales SET order_status = ? WHERE sales_id = ?");
    $stmt->bind_param("si", $new_status, $sales_id);
    $stmt->execute();

    if ($customer_id > 0) {
        $notif_title = "Package Update 📦";
        $notif_msg = "Your order for $product_name is now: $new_status.";
        
        $stmt_notif = $conn->prepare("INSERT INTO notifications (customer_id, title, message) VALUES (?, ?, ?)");
        $stmt_notif->bind_param("iss", $customer_id, $notif_title, $notif_msg);
        $stmt_notif->execute();
    }

    header("Location: orders.php");
    exit();
}

$query = "SELECT s.sales_id, s.customer_id, s.transaction_date, s.total_amount, s.quantity, s.order_status, 
                 p.product_name, c.first_name, c.last_name, c.address, c.phone_number
          FROM sales s 
          JOIN products p ON s.product_id = p.product_id 
          LEFT JOIN customers c ON s.customer_id = c.customer_id 
          WHERE s.order_status IS NOT NULL
          ORDER BY s.transaction_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Online Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-top: 0; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">📦 Online Orders Hub</h1>

        <div style="background: #1f2937; border-radius: 12px; border: 1px solid #374151; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: rgba(17, 24, 39, 0.5); border-bottom: 1px solid #374151; color: #9ca3af; font-size: 12px; text-transform: uppercase;">
                        <th style="padding: 15px 20px;">Date</th>
                        <th style="padding: 15px 20px;">Customer Details</th>
                        <th style="padding: 15px 20px;">Item & Qty</th>
                        <th style="padding: 15px 20px;">Total</th>
                        <th style="padding: 15px 20px;">Status</th>
                        <th style="padding: 15px 20px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            // Custom Colors matching the 4 steps
                            $bg_color = 'rgba(245, 158, 11, 0.1)'; $text_color = '#f59e0b'; // To Ship = Yellow
                            if ($row['order_status'] == 'Shipped') { $bg_color = 'rgba(59, 130, 246, 0.1)'; $text_color = '#3b82f6'; } // Shipped = Blue
                            if ($row['order_status'] == 'Received') { $bg_color = 'rgba(139, 92, 246, 0.1)'; $text_color = '#8b5cf6'; } // Received = Purple
                            if ($row['order_status'] == 'Delivered') { $bg_color = 'rgba(16, 185, 129, 0.1)'; $text_color = '#10b981'; } // Delivered = Green
                        ?>
                            <tr style="border-bottom: 1px solid #374151; color: white;">
                                <td style="padding: 15px 20px; font-size: 13px; color: #9ca3af;"><?php echo date("M d, Y", strtotime($row['transaction_date'])); ?></td>
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: bold;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                    <div style="font-size: 12px; color: #9ca3af;"><?php echo htmlspecialchars($row['phone_number']); ?></div>
                                    <div style="font-size: 11px; color: #6b7280; margin-top: 4px; max-width: 200px;"><?php echo htmlspecialchars($row['address']); ?></div>
                                </td>
                                <td style="padding: 15px 20px; font-weight: bold;"><?php echo htmlspecialchars($row['product_name']); ?> <span style="color:#9ca3af;">(x<?php echo $row['quantity']; ?>)</span></td>
                                <td style="padding: 15px 20px; font-weight: bold; color: #10b981;">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td style="padding: 15px 20px;">
                                    <span style="background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                                        <?php echo htmlspecialchars($row['order_status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <form method="POST" action="" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="sales_id" value="<?php echo $row['sales_id']; ?>">
                                        <input type="hidden" name="customer_id" value="<?php echo $row['customer_id']; ?>">
                                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($row['product_name']); ?>">
                                        <input type="hidden" name="update_order" value="1">

                                        <?php if ($row['order_status'] == 'To Ship'): ?>
                                            <button type="submit" name="new_status" value="Shipped" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;">Ship Out 🚚</button>
                                        <?php elseif ($row['order_status'] == 'Received'): ?>
                                            <button type="submit" name="new_status" value="Delivered" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;">Mark Delivered ✅</button>
                                        <?php elseif ($row['order_status'] == 'Shipped'): ?>
                                            <span style="font-size: 12px; color: #9ca3af; font-style: italic;">Waiting for Customer</span>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: #9ca3af;">Completed</span>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="padding: 30px; text-align: center; color: #9ca3af;">No online orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>