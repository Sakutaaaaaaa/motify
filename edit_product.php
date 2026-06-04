<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $name = $conn->real_escape_string($_POST['product_name']);
    $category = $conn->real_escape_string($_POST['category']);
    $price = floatval($_POST['selling_price']);
    $add_stock = isset($_POST['add_stock']) ? intval($_POST['add_stock']) : 0;

    // Update Product Details
    $stmt = $conn->prepare("UPDATE products SET product_name=?, category=?, selling_price=? WHERE product_id=?");
    $stmt->bind_param("ssdi", $name, $category, $price, $product_id);
    $stmt->execute();

    // Only update inventory if it is NOT a service, and they actually typed a number
    if ($category != 'Service' && $category != 'Services' && $add_stock > 0) {
        $inv_stmt = $conn->prepare("UPDATE inventory SET stock_quantity = stock_quantity + ? WHERE product_id=?");
        $inv_stmt->bind_param("ii", $add_stock, $product_id);
        $inv_stmt->execute();
    }

    $message = "<div style='background:#10b981; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>✅ Item updated successfully!</div>";
}

// Fetch Current Data
$query = "SELECT p.*, i.stock_quantity FROM products p LEFT JOIN inventory i ON p.product_id = i.product_id WHERE p.product_id = $product_id";
$result = $conn->query($query);
$product = $result->fetch_assoc();

if (!$product) {
    die("<h2 style='color:white; text-align:center; padding: 50px;'>Product not found!</h2>");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Edit Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <a href="inventory.php" style="color: #9ca3af; text-decoration: none; font-size: 14px; transition: 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='#9ca3af'">&larr; Back to Inventory Hub</a>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; margin-bottom: 25px;">
            <h1 style="margin: 0; display: flex; align-items: center; gap: 10px;">✏️ Edit Item & Restock</h1>
            <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid #3b82f6;">SKU: N/A</span>
        </div>

        <p style="color: #9ca3af; margin-bottom: 25px;">Update product details or add new shipments to the inventory.</p>

        <?php echo $message; ?>

        <form method="POST" action="" style="display: flex; gap: 20px; align-items: stretch; flex-wrap: wrap;">
            
            <div style="flex: 2; min-width: 400px; background: #1f2937; padding: 30px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <h3 style="margin-top: 0; color: white; border-bottom: 1px solid #374151; padding-bottom: 15px; margin-bottom: 20px;">📦 Product Information</h3>
                
                <label style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block;">Part / Accessory Name *</label>
                <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; margin-bottom: 20px; box-sizing: border-box;" required>

                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block;">Category *</label>
                        <select name="category" id="categorySelect" onchange="toggleStockField()" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; box-sizing: border-box;" required>
                            <option value="Motorcycle Parts" <?php if($product['category'] == 'Motorcycle Parts') echo 'selected'; ?>>Motorcycle Parts</option>
                            <option value="Accessories" <?php if($product['category'] == 'Accessories') echo 'selected'; ?>>Accessories</option>
                            <option value="Service" <?php if($product['category'] == 'Service' || $product['category'] == 'Services') echo 'selected'; ?>>Service</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block;">Selling Price (₱) *</label>
                        <input type="number" step="0.01" name="selling_price" value="<?php echo $product['selling_price']; ?>" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; box-sizing: border-box;" required>
                    </div>
                </div>
            </div>

            <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px;">
                
                <div id="stockGroup" style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="background: #1f2937; padding: 30px; border-radius: 12px; border: 1px solid #374151; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                        <div style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 10px;">Current Stock Level</div>
                        <div style="color: #ef4444; font-size: 48px; font-weight: 900; line-height: 1;"><?php echo $product['stock_quantity'] ?? 0; ?></div>
                        <div style="color: #6b7280; font-size: 12px; margin-top: 5px;">Units available in warehouse</div>
                    </div>

                    <div style="background: rgba(16, 185, 129, 0.05); padding: 25px; border-radius: 12px; border: 1px dashed #10b981; flex-grow: 1;">
                        <h4 style="margin-top: 0; color: #10b981; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">🚢 Inbound Shipment</h4>
                        <p style="color: #9ca3af; font-size: 12px; margin-bottom: 15px;">Enter how many new items arrived to add to current stock.</p>
                        <input type="number" name="add_stock" value="0" min="0" style="width: 100%; padding: 15px; border-radius: 6px; border: 1px solid #10b981; background: #111827; color: white; font-size: 18px; text-align: center; font-weight: bold; box-sizing: border-box;">
                    </div>
                </div>

                <div id="serviceNotice" style="display: none; background: rgba(59, 130, 246, 0.05); padding: 30px; border-radius: 12px; border: 1px dashed #3b82f6; text-align: center; flex-grow: 1; align-items: center; justify-content: center; flex-direction: column;">
                    <div style="font-size: 40px; margin-bottom: 15px;">🔧</div>
                    <h4 style="margin-top: 0; color: #3b82f6; margin-bottom: 10px;">Service Rendered</h4>
                    <p style="color: #9ca3af; font-size: 13px; line-height: 1.5; margin: 0;">This item is classified as a service. It does not require physical inventory tracking or restocking.</p>
                </div>

                <button type="submit" name="update_product" class="btn-generate" style="width: 100%; padding: 15px; justify-content: center; font-size: 16px; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; margin-top: auto;">Save & Update Item</button>
            </div>

        </form>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>