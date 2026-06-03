<?php
session_start();
require_once 'db_connection.php';

// Admin Access Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: sales.php");
    exit();
}

$message = "";

// 1. Check ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: inventory.php");
    exit();
}

$product_id = intval($_GET['id']);

// 2. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // We allow editing the name now so you can fix typos!
    $name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $new_price = floatval($_POST['selling_price']);
    $restock_amount = isset($_POST['restock_amount']) ? intval($_POST['restock_amount']) : 0;

    $conn->begin_transaction();

    try {
        // Update Products table
        $stmt_update = $conn->prepare("UPDATE Products SET product_name = ?, category = ?, selling_price = ? WHERE product_id = ?");
        $stmt_update->bind_param("ssdi", $name, $category, $new_price, $product_id);
        $stmt_update->execute();
        $stmt_update->close();

        // Handle Inventory Logic
        if ($category === 'Service' || $category === 'Services') {
            // Remove from inventory if it was converted to a service
            $conn->query("DELETE FROM Inventory WHERE product_id = $product_id");
        } else {
            // Check if it exists in inventory
            $check_inv = $conn->query("SELECT stock_quantity FROM Inventory WHERE product_id = $product_id");
            if ($check_inv->num_rows > 0) {
                if ($restock_amount > 0) {
                    // Add to existing stock using your custom logic
                    $stmt_stock = $conn->prepare("UPDATE Inventory SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
                    $stmt_stock->bind_param("ii", $restock_amount, $product_id);
                    $stmt_stock->execute();
                    $stmt_stock->close();
                }
            } else {
                // It was a service, but the user changed it to a physical item! Create an inventory slot for it.
                $stmt_insert = $conn->prepare("INSERT INTO Inventory (product_id, stock_quantity) VALUES (?, ?)");
                $stmt_insert->bind_param("ii", $product_id, $restock_amount);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }

        $conn->commit();
        $message = "<div class='alert-success'>✅ Item successfully updated!</div>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert-error'>❌ Error updating item: " . $e->getMessage() . "</div>";
    }
}

// 3. Fetch current item details 
// Changed to LEFT JOIN so items without inventory (Services) still load correctly!
$stmt = $conn->prepare("SELECT p.product_name, p.selling_price, p.category, i.stock_quantity 
                        FROM Products p 
                        LEFT JOIN Inventory i ON p.product_id = i.product_id 
                        WHERE p.product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    die("Error: Part or Accessory not found.");
}
$stmt->close();

$current_category = $item['category'];
$current_stock = $item['stock_quantity'] !== null ? $item['stock_quantity'] : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Edit Item</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="store-body">

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <a href="inventory.php" class="back-link">&larr; Back to Inventory Hub</a>
        
        <h1 style="color: white; margin-top: 15px; font-size: 28px;">✏️ Edit Item & Restock</h1>

        <?php echo $message; ?>

        <div class="edit-container">
            <form method="POST" action="">
                
                <label class="form-label">Part / Accessory Name:</label>
                <input type="text" name="product_name" class="form-input" value="<?php echo htmlspecialchars($item['product_name']); ?>" required>

                <label class="form-label">Category:</label>
                <select name="category" id="categorySelect" class="form-select">
                    <option value="Motorcycle Parts" <?php if($current_category == 'Motorcycle Parts' || $current_category == 'Part') echo 'selected'; ?>>Motorcycle Parts</option>
                    <option value="Accessories" <?php if($current_category == 'Accessories' || $current_category == 'Accessory') echo 'selected'; ?>>Accessories</option>
                    <option value="Service" <?php if($current_category == 'Service' || $current_category == 'Services') echo 'selected'; ?>>Service</option>
                </select>

                <label class="form-label">Update Selling Price (PHP):</label>
                <input type="number" step="0.01" name="selling_price" class="form-input" value="<?php echo htmlspecialchars($item['selling_price']); ?>" required>

                <div id="stockGroup">
                    <div class="stock-info-box">
                        <strong>Current Stock Level:</strong> <?php echo $current_stock; ?> units
                    </div>
                    
                    <label class="form-label">Add Stock (Restock Amount):</label>
                    <input type="number" name="restock_amount" class="form-input" value="0" min="0">
                    <small class="form-help-text">Enter how many new items arrived to add to current stock.</small>
                </div>

                <div id="serviceNotice" class="service-notice-box">
                    <p style="margin:0;">ℹ️ <strong>Service Selected:</strong> Inventory tracking is disabled.</p>
                </div>

                <button type="submit" class="btn-generate" style="width: 100%; justify-content: center; padding: 15px; font-size: 16px;">Save Changes & Restock ✅</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>