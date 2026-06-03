<?php
// inventory.php
session_start();
require_once 'db_connection.php';

// NEW: Safely Delete Item Logic
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
            // Catches 'Part' and any old 'Product' entries
            $parts[] = $row; 
        }
    }
}

// Helper function to render product cards so we don't repeat code
function renderCards($itemsArray) {
    if (empty($itemsArray)) {
        echo "<p style='color:#9ca3af;'>No items in this category yet.</p>";
        return;
    }
    
    echo "<div class='inventory-grid'>";
    foreach ($itemsArray as $row) {
        $img_src = !empty($row["image_path"]) ? htmlspecialchars($row["image_path"]) : 'uploads/default_part.png';
        $id = $row['product_id'];
        $name = htmlspecialchars($row["product_name"]);
        $safe_name = addslashes($row["product_name"]); // Prevents quotes from breaking the Javascript alert
        
        echo "<div class='product-card' style='display:flex; flex-direction:column;'>";
        echo "<img src='" . $img_src . "' alt='Part Image' class='product-image' onerror=\"this.onerror=null; this.src='uploads/default_part.png'\">";
        echo "<div class='card-body' style='display:flex; flex-direction:column; flex-grow:1;'>";
        echo "<h3 class='product-title'>" . $name . "</h3>";
        
        // Hide category label on the card since they are now grouped by section
        echo "<div class='product-price'>₱" . number_format($row["selling_price"], 2) . "</div>";
        
        echo "<div class='stock-status'>";
        if ($row["category"] == 'Service' || $row["category"] == 'Services') {
            echo "<span class='service-badge'>🔧 Service (N/A)</span>";
        } else {
            echo "Stock: <strong>" . $row["stock_quantity"] . "</strong>";
            if ($row["stock_quantity"] <= 5) {
                echo "<span class='low-stock'>⚠ Low</span>";
            }
        }
        echo "</div>"; 
        
        // The Side-by-Side Edit and Delete Buttons
        echo "<div style='display: flex; gap: 8px; margin-top: auto; padding-top: 15px;'>";
        
        // Edit Button
        echo "<a href='edit_product.php?id=" . $id . "' style='flex: 1; text-align: center; background: #374151; color: white; padding: 8px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; transition: 0.2s;' onmouseover=\"this.style.background='#4b5563'\" onmouseout=\"this.style.background='#374151'\">Edit</a>";
        
        // Delete Button
        echo "<form method='POST' style='flex: 1; margin: 0;' onsubmit=\"return confirm('⚠️ Are you sure you want to permanently delete " . $safe_name . "?');\">";
        echo "<input type='hidden' name='delete_id' value='" . $id . "'>";
        echo "<button type='submit' name='delete_item' style='width: 100%; background: transparent; color: #ef4444; border: 1px solid #ef4444; padding: 8px; border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer; transition: 0.2s;' onmouseover=\"this.style.background='#ef4444'; this.style.color='white'\" onmouseout=\"this.style.background='transparent'; this.style.color='#ef4444'\">Delete</button>";
        echo "</form>";
        
        echo "</div>"; // Close button flex container
        echo "</div></div>"; // Close card-body and product-card
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
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="margin: 0;">📦 Inventory Hub</h1>
            <a href="add_product.php" style="background: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;">➕ Add New Item</a>
        </div>

        <h2 class="section-header"><span>⚙️</span> Motorcycle Parts</h2>
        <?php renderCards($parts); ?>

        <h2 class="section-header"><span>🏍️</span> Accessories</h2>
        <?php renderCards($accessories); ?>

        <h2 class="section-header"><span>🔧</span> Services</h2>
        <?php renderCards($services); ?>

    </div>

</body>
</html>