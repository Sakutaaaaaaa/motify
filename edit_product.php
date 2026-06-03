<?php
// edit_product.php
session_start();
require_once 'db_connection.php';

// Gatekeeper: Only Admins/Staff allowed
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff')) {
    header("Location: index.php");
    exit();
}

$message = "";
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['selling_price']);
    $restock_amount = intval($_POST['restock_amount']);

    $conn->begin_transaction();
    try {
        // 1. Update Product Details
        $stmt_prod = $conn->prepare("UPDATE products SET product_name = ?, category = ?, selling_price = ? WHERE product_id = ?");
        $stmt_prod->bind_param("ssdi", $name, $category, $price, $product_id);
        $stmt_prod->execute();

        // 2. Update Inventory (Add restock amount to current stock)
        if ($restock_amount > 0) {
            $stmt_inv = $conn->prepare("UPDATE inventory SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
            $stmt_inv->bind_param("ii", $restock_amount, $product_id);
            $stmt_inv->execute();
        }

        $conn->commit();
        $message = "<div style='background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; display: flex; align-items: center; gap: 10px;'>✅ Item successfully updated!</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div style='background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; display: flex; align-items: center; gap: 10px;'>❌ Error updating item: " . $e->getMessage() . "</div>";
    }
}

// --- FETCH CURRENT DATA ---
$product = null;
if ($product_id > 0) {
    // Join Products and Inventory tables to get everything in one go
    $query = "SELECT p.*, i.stock_quantity 
              FROM products p 
              LEFT JOIN inventory i ON p.product_id = i.product_id 
              WHERE p.product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
}

// If product doesn't exist, redirect back
if (!$product) {
    header("Location: inventory.php");
    exit();
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
        
        <a href="inventory.php" style="color: #9ca3af; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 20px; font-size: 14px; transition: 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">
            &larr; Back to Inventory Hub
        </a>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0; color: white; font-size: 28px; display: flex; align-items: center; gap: 10px;">
                    ✏️ Edit Item & Restock
                </h1>
                <p style="margin: 5px 0 0 0; color: #9ca3af;">Update product details or add new shipments to the inventory.</p>
            </div>
            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #3b82f6; padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 13px;">
                SKU: <?php echo $product['SKU'] ? htmlspecialchars($product['SKU']) : 'N/A'; ?>
            </div>
        </div>

        <?php echo $message; ?>

        <form method="POST" action="">
            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                
                <div style="flex: 2; min-width: 300px; background: #1f2937; padding: 30px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                    <h3 style="margin: 0 0 20px 0; color: white; border-bottom: 1px solid #374151; padding-bottom: 10px;">📦 Product Information</h3>
                    
                    <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Part / Accessory Name *</label>
                    <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; box-sizing: border-box;" required>

                    <div style="display: flex; gap: 20px;">
                        <div style="flex: 1;">
                            <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Category *</label>
                            <select name="category" style="width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; box-sizing: border-box;" required>
                                <option value="Motorcycle Parts" <?php if($product['category'] == 'Motorcycle Parts') echo 'selected'; ?>>Motorcycle Parts</option>
                                <option value="Accessories" <?php if($product['category'] == 'Accessories') echo 'selected'; ?>>Accessories</option>
                                <option value="Maintenance Gear" <?php if($product['category'] == 'Maintenance Gear') echo 'selected'; ?>>Maintenance Gear</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Selling Price (₱) *</label>
                            <input type="number" step="0.01" name="selling_price" value="<?php echo htmlspecialchars($product['selling_price']); ?>" style="width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: #10b981; font-weight: bold; box-sizing: border-box;" required>
                        </div>
                    </div>
                </div>

                <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 20px;">
                    
                    <div style="background: linear-gradient(135deg, #1f2937, #111827); padding: 25px; border-radius: 12px; border: 1px solid #374151; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                        <p style="margin: 0 0 5px 0; color: #9ca3af; font-size: 14px; font-weight: bold;">Current Stock Level</p>
                        <h2 style="margin: 0; font-size: 48px; color: <?php echo ($product['stock_quantity'] <= 5) ? '#ef4444' : '#white'; ?>;">
                            <?php echo htmlspecialchars($product['stock_quantity']); ?>
                        </h2>
                        <p style="margin: 5px 0 0 0; color: #9ca3af; font-size: 12px;">Units available in warehouse</p>
                    </div>

                    <div style="background: rgba(16, 185, 129, 0.05); padding: 25px; border-radius: 12px; border: 1px dashed #10b981;">
                        <label style="color: #10b981; font-size: 14px; margin-bottom: 8px; display: block; font-weight: bold;">📥 Inbound Shipment</label>
                        <p style="color: #9ca3af; font-size: 12px; margin: 0 0 15px 0;">Enter how many new items arrived to add to current stock.</p>
                        
                        <input type="number" name="restock_amount" value="0" min="0" style="width: 100%; padding: 15px; border-radius: 6px; border: 1px solid #10b981; background: #111827; color: white; font-size: 18px; font-weight: bold; text-align: center; box-sizing: border-box; margin-bottom: 15px;">
                        
                        <button type="submit" name="update_product" style="width: 100%; padding: 15px; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                            Save & Update Item
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>