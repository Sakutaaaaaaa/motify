<?php
session_start();
require_once 'db_connection.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: sales.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $selling_price = $_POST['selling_price'];
    $initial_stock = $_POST['initial_stock'];
    $supplier_id = NULL; 
    
    // --- Image Upload Logic ---
    $image_path = 'uploads/default_part.png'; // Fallback image
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/";
        // Create a unique file name to prevent overwriting
        $file_name = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Allow certain file formats
        if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg") {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $message = "<p class='error-msg'>Sorry, there was an error uploading your file.</p>";
            }
        } else {
            $message = "<p class='error-msg'>Sorry, only JPG, JPEG, & PNG files are allowed.</p>";
        }
    }

    if (empty($message)) {
        $conn->begin_transaction();
        try {
            // Insert product WITH the image path
            $stmt_product = $conn->prepare("INSERT INTO Products (supplier_id, product_name, description, category, selling_price, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_product->bind_param("isssds", $supplier_id, $product_name, $description, $category, $selling_price, $image_path);
            $stmt_product->execute();
            $new_product_id = $conn->insert_id;

            $stmt_inventory = $conn->prepare("INSERT INTO Inventory (product_id, stock_quantity) VALUES (?, ?)");
            $stmt_inventory->bind_param("ii", $new_product_id, $initial_stock);
            $stmt_inventory->execute();

            $conn->commit();
            $message = "<p class='success-msg'>Successfully added '$product_name' with image!</p>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<p class='error-msg'>Error adding item: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Add Motorcycle Part</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container { background: #1f2937; padding: 30px; border-radius: 12px; max-width: 600px; border: 1px solid #374151; }
        label { color: #9ca3af; display: block; margin: 15px 0 5px 0; font-size: 14px; text-transform: uppercase; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 12px; background: #111827; border: 1px solid #374151; color: white; border-radius: 6px; box-sizing: border-box; }
        input[type="file"] { width: 100%; padding: 10px; color: #9ca3af; background: #111827; border: 1px dashed #374151; border-radius: 6px; }
        button[type="submit"] { background: #ef4444; color: white; border: none; padding: 15px; width: 100%; border-radius: 6px; font-weight: bold; margin-top: 25px; cursor: pointer; transition: 0.3s; }
        button[type="submit"]:hover { background: #dc2626; }
        .success-msg { color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 6px; border: 1px solid #10b981; }
        .error-msg { color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 6px; border: 1px solid #ef4444; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-top:0;">Add New Part / Service</h1>
        
        <?php echo $message; ?>

        <div class="form-container">
            <!-- enctype is required for file uploads to work -->
            <form method="POST" action="" enctype="multipart/form-data">
                <label>Upload Part Image:</label>
                <input type="file" name="product_image" accept="image/*">

                <label>Part / Accessory Name:</label>
                <input type="text" name="product_name" placeholder="e.g., Ceramic Brake Pads" required>

                <label>Description (Optional):</label>
                <textarea name="description" rows="3" placeholder="Enter specifications..."></textarea>
                
                <label>Category:</label>
                <select name="category" required>
                    <option value="Part">⚙️ Motorcycle Part</option>
                    <option value="Accessory">🏍️ Accessory</option>
                    <option value="Service">🔧 Service (No Inventory Tracking)</option>
                </select>

                <label>Selling Price (PHP):</label>
                <input type="number" step="0.01" name="selling_price" placeholder="0.00" required>

                <label>Initial Stock (Enter 0 for Services):</label>
                <input type="number" name="initial_stock" value="0" min="0" required>

                <button type="submit">Save to Database</button>
            </form>
        </div>
    </div>
</body>
</html>