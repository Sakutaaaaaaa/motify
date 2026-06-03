<?php
// sales.php
session_start();
require_once 'db_connection.php';

$message = "";

// 1. Handle Checkout Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_data'])) {
    $cart = json_decode($_POST['cart_data'], true);
    
    if (!empty($cart)) {
        $conn->begin_transaction();
        try {
            foreach ($cart as $item) {
                $id = $item['id'];
                $qty = $item['qty'];
                $price = $item['price'];
                $total_amount = $qty * $price;
                
                // Insert into your Sales table (assuming columns: product_id, quantity, total_amount)
                $stmt_sale = $conn->prepare("INSERT INTO Sales (product_id, quantity, total_amount) VALUES (?, ?, ?)");
                $stmt_sale->bind_param("iid", $id, $qty, $total_amount);
                $stmt_sale->execute();
                
                // Only deduct inventory if it is NOT a Service
                if ($item['category'] != 'Service') {
                    $stmt_inv = $conn->prepare("UPDATE Inventory SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
                    $stmt_inv->bind_param("ii", $qty, $id);
                    $stmt_inv->execute();
                }
            }
            $conn->commit();
            $message = "<div style='color:#10b981; margin-bottom:15px; font-weight:bold;'>✅ Sale successfully processed!</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div style='color:#ef4444; margin-bottom:15px;'>❌ Error processing sale: " . $e->getMessage() . "</div>";
        }
    }
}

// 2. Fetch Inventory for the Catalog
$sql = "SELECT p.product_id, p.product_name, p.category, p.selling_price, p.image_path, i.stock_quantity 
        FROM Products p
        INNER JOIN Inventory i ON p.product_id = i.product_id
        WHERE i.stock_quantity > 0 OR p.category = 'Service'
        ORDER BY p.product_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - POS Terminal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-top: 0; margin-bottom: 5px;">🛒 Terminal</h1>
        <?php echo $message; ?>

        <div class="pos-container">
            <div class="pos-catalog">
                <div class="pos-grid">
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $img = !empty($row["image_path"]) ? htmlspecialchars($row["image_path"]) : 'uploads/default_part.png';
                            $id = $row['product_id'];
                            $name = htmlspecialchars($row['product_name']);
                            $price = $row['selling_price'];
                            $cat = $row['category'];
                            
                            // UPDATED: Now calls POSTerminal.addToCart
                            echo "<div class='pos-item' onclick='POSTerminal.addToCart($id, \"$name\", $price, \"$cat\")'>";
                            echo "<img src='$img' onerror=\"this.onerror=null; this.src='uploads/default_part.png'\">";
                            echo "<div style='font-size:14px; color:#f3f4f6; font-weight:bold;'>$name</div>";
                            echo "<div style='color:#10b981; font-size:14px;'>₱" . number_format($price, 2) . "</div>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="pos-cart">
                <h2 style="margin-top: 0; color: #f3f4f6; border-bottom: 1px solid #374151; padding-bottom: 10px;">Current Order</h2>
                
                <div class="cart-items" id="cart-items">
                    <p style="color:#9ca3af; text-align:center; margin-top:50px;">Cart is empty.<br>Select items to begin.</p>
                </div>

                <div class="cart-total">
                    Total: ₱<span id="cart-total-display">0.00</span>
                </div>

                <form method="POST" id="checkout-form">
                    <input type="hidden" name="cart_data" id="cart-data-input">
                    <button type="button" class="btn-checkout" onclick="POSTerminal.processCheckout()">Complete Sale</button>
                </form>
            </div>
        </div>
    </div>
    
    <div id="printable-receipt">
        <div class="receipt-header">
        <h2>MOTIFY GARAGE</h2>
        <p>Motorcycle Parts & Services</p>
        <p id="receipt-date"></p>
    </div>
    
    <table class="receipt-table">
        <thead>
            <tr>
                <th>Item</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Price</th>
            </tr>
        </thead>
        <tbody id="receipt-body">
            </tbody>
    </table>
    
    <div class="receipt-total">
        TOTAL: ₱<span id="receipt-total-display">0.00</span>
    </div>
    
    <div class="receipt-footer">
        <p>Thank you for trusting Motify!</p>
        <p>Ride Better. Ride Faster.</p>
    </div>
</div>

    <script src="script.js"></script>

</body>
</html>