<?php
// inventory.php
session_start();
require_once 'db_connection.php';

// Safely Delete Item Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_item'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // Safety precaution: Delete from child table (Inventory) first, then parent (Products)
    $conn->query("DELETE FROM Inventory WHERE product_id = $delete_id");
    $conn->query("DELETE FROM Products WHERE product_id = $delete_id");
    
    // Refresh the page instantly
    header("Location: inventory.php");
    exit();
}

// Fetch all items from the database
$sql = "SELECT 
            p.product_id, 
            p.product_name, 
            p.category, 
            p.selling_price, 
            p.image_path,
            i.stock_quantity 
        FROM Products p
        INNER JOIN Inventory i ON p.product_id = i.product_id
        ORDER BY p.product_name ASC";

$result = $conn->query($sql);

// Initialize arrays for our three categories
$parts = [];
$accessories = [];
$services = [];

// Sort the database results into the correct arrays
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if ($row['category'] == 'Accessory' || $row['category'] == 'Accessories') {
            $accessories[] = $row;
        } elseif ($row['category'] == 'Service' || $row['category'] == 'Services') {
            $services[] = $row;
        } else {
            $parts[] = $row; 
        }
    }
}

// Helper function to render product cards
function renderCards($itemsArray) {
    if (empty($itemsArray)) {
        echo "<p style='color:#9ca3af;'>No items in this category yet.</p>";
        return;
    }
    
    $fallback_img = "https://via.placeholder.com/300x200/111827/9ca3af?text=No+Image";
    
    echo "<div class='inventory-grid'>";
    foreach ($itemsArray as $row) {
        $id = $row['product_id'];
        $name = htmlspecialchars($row["product_name"]);
        $safe_name = addslashes($row["product_name"]); 
        $category = $row["category"];
        
        echo "<div class='product-card' style='display:flex; flex-direction:column; overflow:hidden; border: 1px solid #374151; border-radius: 8px; background: #1f2937;'>";
        
        // ==========================================
        // THE FIX: NO IMAGES FOR SERVICES
        // ==========================================
        if ($category == 'Service' || $category == 'Services') {
            // Render a clean blue box with a wrench icon instead of an image
            echo "<div style='width: 100%; height: 180px; background: rgba(59, 130, 246, 0.05); border-bottom: 1px solid #374151; display: flex; align-items: center; justify-content: center; font-size: 60px;'>🔧</div>";
        } else {
            // Render the normal image for physical products
            if (!empty($row["image_path"]) && $row["image_path"] !== 'uploads/default_part.png') {
                $img_src = htmlspecialchars($row["image_path"]);
            } else {
                $img_src = $fallback_img;
            }
            echo "<img src='" . $img_src . "' alt='" . $name . "' class='product-image' style='width: 100%; height: 180px; object-fit: cover;' onerror=\"this.onerror=null; this.src='" . $fallback_img . "'\">";
        }
        // ==========================================
        
        echo "<div class='card-body' style='display:flex; flex-direction:column; flex-grow:1; padding: 15px;'>";
        echo "<h3 class='product-title' style='margin-top:0; font-size: 16px; color: white;'>" . $name . "</h3>";
        
        echo "<div class='product-price' style='color:#10b981; font-weight:bold; font-size:18px; margin-bottom:10px;'>₱" . number_format($row["selling_price"], 2) . "</div>";
        
        echo "<div class='stock-status' style='font-size:13px; color:#9ca3af; margin-bottom:15px;'>";
        if ($category == 'Service' || $category == 'Services') {
            echo "<span style='background:rgba(59,130,246,0.1); color:#3b82f6; padding:4px 8px; border-radius:4px; font-weight:bold;'>🔧 Service (N/A)</span>";
        } else {
            echo "Stock: <strong style='color:white;'>" . $row["stock_quantity"] . "</strong>";
            if ($row["stock_quantity"] <= 5) {
                echo "<span style='background:rgba(239,68,68,0.1); color:#ef4444; padding:2px 6px; border-radius:4px; margin-left:8px; font-size:11px; font-weight:bold;'>⚠ Low</span>";
            }
        }
        echo "</div>"; 
        
        echo "<div style='display: flex; gap: 8px; margin-top: auto;'>";
        
        echo "<a href='edit_product.php?id=" . $id . "' style='flex: 1; text-align: center; background: #374151; color: white; padding: 8px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; transition: 0.2s;' onmouseover=\"this.style.background='#4b5563'\" onmouseout=\"this.style.background='#374151'\">Edit</a>";
        
        echo "<form method='POST' style='flex: 1; margin: 0;' onsubmit=\"return confirm('⚠️ Are you sure you want to permanently delete " . $safe_name . "?');\">";
        echo "<input type='hidden' name='delete_id' value='" . $id . "'>";
        echo "<button type='submit' name='delete_item' style='width: 100%; background: transparent; color: #ef4444; border: 1px solid #ef4444; padding: 8px; border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer; transition: 0.2s;' onmouseover=\"this.style.background='#ef4444'; this.style.color='white'\" onmouseout=\"this.style.background='transparent'; this.style.color='#ef4444'\">Delete</button>";
        echo "</form>";
        
        echo "</div>"; 
        echo "</div></div>"; 
    }
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Premium Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0;">📦 Inventory Hub</h1>
            <a href="add_product.php" style="background: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">➕ Add New Item</a>
        </div>

        <h2 class="section-header" style="margin-top: 20px; color: white; border-bottom: 1px solid #374151; padding-bottom: 10px;"><span>⚙️</span> Motorcycle Parts</h2>
        <?php renderCards($parts); ?>

        <h2 class="section-header" style="margin-top: 40px; color: white; border-bottom: 1px solid #374151; padding-bottom: 10px;"><span>🏍️</span> Accessories</h2>
        <?php renderCards($accessories); ?>

        <h2 class="section-header" style="margin-top: 40px; color: white; border-bottom: 1px solid #374151; padding-bottom: 10px;"><span>🔧</span> Services</h2>
        <?php renderCards($services); ?>

    </div>

</body>
</html>