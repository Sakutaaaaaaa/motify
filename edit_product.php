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

    // 1. Update basic info
    $stmt = $conn->prepare("UPDATE products SET product_name=?, category=?, selling_price=? WHERE product_id=?");
    $stmt->bind_param("ssdi", $name, $category, $price, $product_id);
    $stmt->execute();

    // 2. Handle File Upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/";
        
        // Ensure folder exists
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $upd_stmt = $conn->prepare("UPDATE products SET image_path=? WHERE product_id=?");
            $upd_stmt->bind_param("si", $target_file, $product_id);
            $upd_stmt->execute();
        }
    }

    // 3. Update Inventory
    if ($category != 'Service' && $category != 'Services' && $add_stock > 0) {
        $inv_stmt = $conn->prepare("UPDATE inventory SET stock_quantity = stock_quantity + ? WHERE product_id=?");
        $inv_stmt->bind_param("ii", $add_stock, $product_id);
        $inv_stmt->execute();
    }

    $message = "<div style='background:#10b981; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>✅ Item updated successfully!</div>";
}

$query = "SELECT p.*, i.stock_quantity FROM products p LEFT JOIN inventory i ON p.product_id = i.product_id WHERE p.product_id = $product_id";
$result = $conn->query($query);
$product = $result->fetch_assoc();
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
        <a href="inventory.php" style="color: #9ca3af; text-decoration: none;">&larr; Back to Inventory Hub</a>
        
        <h1 style="margin: 15px 0 25px 0;">✏️ Edit Item & Restock</h1>
        <?php echo $message; ?>

        <form method="POST" action="" enctype="multipart/form-data" style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <div style="flex: 2; min-width: 400px; background: #1f2937; padding: 30px; border-radius: 12px; border: 1px solid #374151;">
                <h3 style="margin-top: 0; color: white;">📦 Product Information</h3>
                
                <label style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block;">Part Name</label>
                <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; margin-bottom: 20px; box-sizing: border-box;" required>

                <label style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block;">Upload Product Image</label>
                <input type="file" name="product_image" style="width: 100%; padding: 10px; background: #111827; color: #9ca3af; border: 1px dashed #374151; margin-bottom: 20px; border-radius: 6px;">

                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block;">Category</label>
                        <select name="category" id="categorySelect" onchange="toggleStockField()" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; box-sizing: border-box;" required>
                            <option value="Motorcycle Parts" <?php if($product['category'] == 'Motorcycle Parts') echo 'selected'; ?>>Motorcycle Parts</option>
                            <option value="Accessories" <?php if($product['category'] == 'Accessories') echo 'selected'; ?>>Accessories</option>
                            <option value="Service" <?php if($product['category'] == 'Service') echo 'selected'; ?>>Service</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 8px; display: block;">Selling Price (₱)</label>
                        <input type="number" step="0.01" name="selling_price" value="<?php echo $product['selling_price']; ?>" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #374151; background: #111827; color: white; box-sizing: border-box;" required>
                    </div>
                </div>
            </div>

            <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px;">
                <div id="stockGroup" style="background: #1f2937; padding: 30px; border-radius: 12px; border: 1px solid #374151; text-align: center;">
                    <div style="color: #9ca3af; font-size: 13px; font-weight: bold; margin-bottom: 10px;">Current Stock</div>
                    <div style="color: #ef4444; font-size: 48px; font-weight: 900;"><?php echo $product['stock_quantity'] ?? 0; ?></div>
                    <input type="number" name="add_stock" value="0" min="0" placeholder="Add stock..." style="width: 100%; padding: 12px; margin-top: 15px; border-radius: 6px; border: 1px solid #10b981; background: #111827; color: white;">
                </div>
                <div id="serviceNotice" style="display: none; padding: 20px; border: 1px dashed #3b82f6; color: #3b82f6; text-align: center; border-radius: 12px;">Service item - No stock needed.</div>
                <button type="submit" name="update_product" class="btn-generate" style="width: 100%; padding: 15px; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: auto;">Save & Update Item</button>
            </div>
        </form>
    </div>
    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>