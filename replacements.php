<?php
// replacements.php - ADMIN RETURNS HUB
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle Approve / Reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $ret_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    $req = $conn->query("SELECT r.customer_id, r.sales_id, p.product_name FROM product_returns r JOIN sales s ON r.sales_id = s.sales_id JOIN products p ON s.product_id = p.product_id WHERE r.return_id = $ret_id");
    
    if ($req && $req->num_rows > 0) {
        $data = $req->fetch_assoc();
        $cid = $data['customer_id'];
        $sid = $data['sales_id'];
        $pname = $data['product_name'];
        
        if ($action == 'approve') {
            $conn->query("UPDATE product_returns SET return_status = 'Approved' WHERE return_id = $ret_id");
            $conn->query("UPDATE sales SET order_status = 'Replacement Approved' WHERE sales_id = $sid");
            $conn->query("INSERT INTO notifications (customer_id, title, message) VALUES ($cid, 'Replacement Approved ✅', 'Your replacement for $pname has been approved. The courier will pick up the damaged item and deliver the new one.')");
        } elseif ($action == 'reject') {
            $conn->query("UPDATE product_returns SET return_status = 'Rejected' WHERE return_id = $ret_id");
            $conn->query("UPDATE sales SET order_status = 'Completed' WHERE sales_id = $sid");
            $conn->query("INSERT INTO notifications (customer_id, title, message) VALUES ($cid, 'Replacement Rejected ❌', 'Your replacement request for $pname was reviewed but could not be approved based on the provided proof.')");
        }
    }
    header("Location: replacements.php");
    exit();
}

// Fetch all requests
$returns = $conn->query("
    SELECT r.*, p.product_name, c.first_name, c.last_name 
    FROM product_returns r 
    JOIN sales s ON r.sales_id = s.sales_id 
    JOIN products p ON s.product_id = p.product_id
    JOIN Customers c ON r.customer_id = c.customer_id
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Motify - Replacements</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1 class="dash-header">🔄 Replacement Requests</h1>
        <p class="dash-subtitle">Review damaged product proofs and manage customer returns.</p>
        
        <div style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
            <?php if ($returns && $returns->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php while($row = $returns->fetch_assoc()): ?>
                        <div style="background: #111827; border: 1px solid #374151; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="color: white; font-weight: 900; font-size: 16px; margin-bottom: 5px;"><?php echo htmlspecialchars($row['product_name']); ?></div>
                                <div style="color: #9ca3af; font-size: 13px; margin-bottom: 10px;">Requested by: <strong style="color: #d1d5db;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></div>
                                <div style="color: #ef4444; font-size: 13px; font-weight: bold; margin-bottom: 10px;">Reason: <span style="font-weight: normal; color: #d1d5db;"><?php echo htmlspecialchars($row['reason']); ?></span></div>
                                <a href="<?php echo htmlspecialchars($row['proof_image']); ?>" target="_blank" style="color: #3b82f6; font-size: 13px; text-decoration: none; border: 1px solid #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 4px 8px; border-radius: 4px; transition: 0.2s;" onmouseover="this.style.background='#3b82f6'; this.style.color='white';" onmouseout="this.style.background='rgba(59, 130, 246, 0.1)'; this.style.color='#3b82f6';">🖼️ View Proof Image</a>
                            </div>
                            <div style="text-align: right;">
                                <div style="margin-bottom: 15px; font-size: 12px; font-weight: bold; padding: 4px 10px; border-radius: 20px; display: inline-block; background: rgba(156,163,175,0.1); color: #9ca3af;">
                                    Status: <?php echo $row['return_status']; ?>
                                </div>
                                <?php if($row['return_status'] == 'Pending Review'): ?>
                                    <div style="display: flex; gap: 10px;">
                                        <a href="replacements.php?action=approve&id=<?php echo $row['return_id']; ?>" style="background: #10b981; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 13px; transition: 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">Approve</a>
                                        <a href="replacements.php?action=reject&id=<?php echo $row['return_id']; ?>" style="background: transparent; border: 1px solid #ef4444; color: #ef4444; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 13px; transition: 0.2s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'" onmouseout="this.style.background='transparent'">Reject</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color: #9ca3af; text-align: center; margin: 0;">No replacement requests found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>