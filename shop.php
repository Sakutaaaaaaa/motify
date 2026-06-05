<?php
// shop.php - PUBLIC FACING
session_start();
require_once 'db_connection.php';

$message = "";

// 1. Handle Online Checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_data'])) {
    $cart = json_decode($_POST['cart_data'], true);
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];
    $delivery_method = isset($_POST['delivery_method']) ? $_POST['delivery_method'] : 'pickup';
    
    // Address Handling
    if ($delivery_method === 'delivery') {
        if (isset($_POST['saved_address']) && !empty($_POST['saved_address'])) {
            $customer_address = trim($_POST['saved_address']);
        } else {
            $region = isset($_POST['region']) ? trim($_POST['region']) : '';
            $province = isset($_POST['province']) ? trim($_POST['province']) : '';
            $city = isset($_POST['city']) ? trim($_POST['city']) : '';
            $brgy = isset($_POST['barangay']) ? trim($_POST['barangay']) : '';
            $postal = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : '';
            $street = isset($_POST['street_name']) ? trim($_POST['street_name']) : '';
            $customer_address = "$street, Brgy. $brgy, $city, $province, $region, $postal";
        }
    } else {
        $customer_address = ""; 
    }
    
    if (!empty($cart) && !empty($customer_name)) {
        $conn->begin_transaction();
        try {
            $final_customer_id = null;

            if (isset($_SESSION['customer_id'])) {
                $final_customer_id = $_SESSION['customer_id'];
            } elseif (!empty($customer_phone) && $customer_phone !== "Registered-Account") {
                $name_parts = explode(' ', $customer_name, 2);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                
                $stmt_crm = $conn->prepare("INSERT INTO Customers (first_name, last_name, phone_number, address) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE address = VALUES(address)");
                $stmt_crm->bind_param("ssss", $first_name, $last_name, $customer_phone, $customer_address);
                $stmt_crm->execute();
                
                $get_cid = $conn->query("SELECT customer_id FROM Customers WHERE phone_number = '$customer_phone' LIMIT 1");
                if ($get_cid && $get_cid->num_rows > 0) {
                    $final_customer_id = $get_cid->fetch_assoc()['customer_id'];
                } else {
                    $final_customer_id = $conn->insert_id;
                }
            }

            foreach ($cart as $item) {
                $id = $item['id'];
                $qty = $item['qty'];
                $price = $item['price'];
                $total_amount = $qty * $price;
                
                if ($final_customer_id) {
                    $stmt_sale = $conn->prepare("INSERT INTO Sales (product_id, customer_id, quantity, total_amount, order_status) VALUES (?, ?, ?, ?, 'To Ship')");
                    $stmt_sale->bind_param("iiid", $id, $final_customer_id, $qty, $total_amount);
                } else {
                    $stmt_sale = $conn->prepare("INSERT INTO Sales (product_id, quantity, total_amount, order_status) VALUES (?, ?, ?, 'To Ship')");
                    $stmt_sale->bind_param("iid", $id, $qty, $total_amount);
                }
                $stmt_sale->execute();
                
                $stmt_inv = $conn->prepare("UPDATE Inventory SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
                $stmt_inv->bind_param("ii", $qty, $id);
                $stmt_inv->execute();
            }
            $conn->commit();

            if (isset($_SESSION['customer_id'])) {
                $cid = $_SESSION['customer_id'];
                $notif_title = "Order Placed Successfully";
                $notif_msg = ($delivery_method === 'delivery') ? "Your items are being prepared for delivery." : "Your items are being prepared for pickup at the garage.";
                $stmt_notif = $conn->prepare("INSERT INTO notifications (customer_id, title, message) VALUES (?, ?, ?)");
                $stmt_notif->bind_param("iss", $cid, $notif_title, $notif_msg);
                $stmt_notif->execute();
            }

            $admin_title = "New Online Order";
            $admin_msg = "A new online order has been placed via " . strtoupper($delivery_method) . ". Check the POS Terminal.";
            $admin_notif_stmt = $conn->prepare("INSERT INTO admin_notifications (title, message) VALUES (?, ?)");
            $admin_notif_stmt->bind_param("ss", $admin_title, $admin_msg);
            $admin_notif_stmt->execute();
            
            $message = ($delivery_method === 'delivery') 
                ? "<div style='background:#10b981; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>✅ Order placed! Your items will be delivered to: " . htmlspecialchars($customer_address) . "</div>"
                : "<div style='background:#10b981; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>✅ Order placed successfully! You can pick it up at the shop.</div>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div style='background:#ef4444; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// 2. Fetch Items + NEW RATING COLUMNS (FIX: Removed the > 0 stock limit so Out of Stock items show up!)
$sql = "SELECT p.product_id, p.product_name, p.category, p.selling_price, p.image_path, p.rating_sum, p.rating_count, i.stock_quantity 
        FROM Products p
        INNER JOIN Inventory i ON p.product_id = i.product_id
        WHERE p.category NOT IN ('Service', 'Services')
        ORDER BY p.product_name ASC";
$result = $conn->query($sql);

// 3. Fetch Notifications & Addresses
$notifications = [];
$my_addresses = [];
$unread_count = 0;

if (isset($_SESSION['customer_id'])) {
    $cid = $_SESSION['customer_id'];
    if (isset($_GET['read_notif'])) {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE customer_id = $cid");
        header("Location: shop.php"); exit();
    }
    $notif_result = $conn->query("SELECT * FROM notifications WHERE customer_id = $cid ORDER BY created_at DESC LIMIT 5");
    if ($notif_result) { while($n = $notif_result->fetch_assoc()) { $notifications[] = $n; if ($n['is_read'] == 0) $unread_count++; } }
    $addr_result = $conn->query("SELECT * FROM customer_addresses WHERE customer_id = $cid ORDER BY created_at DESC");
    if ($addr_result) { while($a = $addr_result->fetch_assoc()) $my_addresses[] = $a; }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify Garage - Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="store-body">

    <header class="store-header">
        <a href="shop.php" class="store-logo">MOTIFY.</a>
        <nav class="store-nav-links">
            <a href="home.php">Home</a>
            <a href="shop.php" style="color: #ef4444;">Shop</a>
            <a href="booking.php">Services</a>
            <a href="#" id="nav-wishlist" onclick="Storefront.toggleWishlistView(event)">Wishlist ❤️</a>
            <a href="account.php">Account</a>
        </nav>
        
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if(isset($_SESSION['customer_id'])): ?>
                <div style="position: relative; display: inline-block; cursor: pointer; padding-top: 5px;" onclick="document.getElementById('notif-dropdown').style.display = document.getElementById('notif-dropdown').style.display === 'block' ? 'none' : 'block';">
                    <span style="font-size: 28px; display: inline-block; transition: 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">🔔</span>
                    <?php if($unread_count > 0): ?>
                        <span style="position: absolute; top: -2px; right: -5px; background: #ef4444; color: white; border-radius: 50%; padding: 3px 7px; font-size: 11px; font-weight: bold; box-shadow: 0 0 0 3px #111827;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                    
                    <div id="notif-dropdown" style="display: none; position: absolute; top: 45px; right: 0; left: auto; width: 300px; background: #1f2937; border: 1px solid #374151; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5); z-index: 100; text-align: left;">
                        <div style="padding: 10px 15px; border-bottom: 1px solid #374151; display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: white;">Recent Notifications</strong>
                            <?php if($unread_count > 0): ?>
                                <a href="shop.php?read_notif=true" style="font-size: 11px; color: #3b82f6; text-decoration: none;">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if (empty($notifications)): ?>
                                <div style="padding: 15px; text-align: center; color: #9ca3af; font-size: 13px;">No new notifications.</div>
                            <?php else: ?>
                                <?php foreach($notifications as $notif): ?>
                                    <div style="padding: 15px; border-bottom: 1px solid #374151; background: <?php echo $notif['is_read'] ? 'transparent' : 'rgba(59, 130, 246, 0.05)'; ?>;">
                                        <div style="font-size: 13px; font-weight: bold; color: <?php echo $notif['is_read'] ? '#d1d5db' : '#3b82f6'; ?>; margin-bottom: 4px;"><?php echo htmlspecialchars($notif['title']); ?></div>
                                        <div style="font-size: 12px; color: #9ca3af;"><?php echo htmlspecialchars($notif['message']); ?></div>
                                        <div style="font-size: 10px; color: #6b7280; margin-top: 6px;"><?php echo date("M d, h:i A", strtotime($notif['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="store-hero-banner">
        <div class="store-hero-content">
            <h1>Gear Up For <span>The Ride</span></h1>
            <p>Premium parts, accessories, and maintenance gear for your machine.</p>
        </div>
    </div>

    <div class="store-layout-container">
        
        <aside class="store-sidebar">
            <h3 style="margin-top: 0; color: #f3f4f6; border-bottom: 1px solid #374151; padding-bottom: 10px;">Search Parts</h3>
            <div style="margin-bottom: 25px;">
                <input type="text" id="searchInput" placeholder="Type to search..." onkeyup="Storefront.filterProducts()" style="width: 100%; padding: 12px; background: #111827; border: 1px solid #374151; color: white; border-radius: 6px; box-sizing: border-box;">
            </div>

            <h3 style="margin-top: 0; color: #f3f4f6; border-bottom: 1px solid #374151; padding-bottom: 10px;">Categories</h3>
            <div id="categoryFilters" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px;">
                <label style="color: #9ca3af; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px;">
                    <input type="checkbox" value="all" checked onchange="Storefront.toggleAllCategories(); Storefront.filterProducts()" id="filter-all"> All Categories
                </label>
                <label style="color: #9ca3af; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px;">
                    <input type="checkbox" value="Accessories" onchange="Storefront.filterProducts()" class="filter-category"> Accessories
                </label>
                <label style="color: #9ca3af; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 14px;">
                    <input type="checkbox" value="Motorcycle Parts" onchange="Storefront.filterProducts()" class="filter-category"> Motorcycle Parts
                </label>
            </div>

            <h3 style="margin-top: 0; color: #f3f4f6; border-bottom: 1px solid #374151; padding-bottom: 10px;">Max Price: ₱<span id="priceLabel">10000</span></h3>
            <input type="range" id="priceRange" min="0" max="10000" step="100" value="10000" oninput="Storefront.updatePriceLabel(); Storefront.filterProducts()" style="width: 100%; accent-color: #ef4444;">
        </aside>

        <main class="store-main-catalog">
            <h1 style="margin-top:0; font-size: 28px;">Browse Parts & Accessories</h1>
            <?php echo isset($message) ? $message : ''; ?>
            
            <div class="premium-product-grid">
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $img = !empty($row["image_path"]) ? htmlspecialchars($row["image_path"]) : 'uploads/default_part.png';
                        $id = $row['product_id'];
                        $name = htmlspecialchars($row['product_name']);
                        $safe_name = addslashes($name); 
                        $price = $row['selling_price'];
                        $category = htmlspecialchars($row['category']);
                        $stock = $row['stock_quantity'];
                        
                        // NEW DYNAMIC RATING LOGIC
                        $r_count = isset($row['rating_count']) ? $row['rating_count'] : 0;
                        $r_sum = isset($row['rating_sum']) ? $row['rating_sum'] : 0;
                        $avg_rating = ($r_count > 0) ? round($r_sum / $r_count) : 0;
                        
                        $stars_html = "";
                        if ($r_count == 0) {
                            $stars_html = "<span style='color:#9ca3af; font-size: 12px; font-style: italic;'>No ratings yet</span>";
                        } else {
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avg_rating) {
                                    $stars_html .= "⭐"; // Bright yellow star
                                } else {
                                    $stars_html .= "<span style='opacity: 0.2; filter: grayscale(100%);'>⭐</span>"; // Dim, gray star
                                }
                            }
                            $stars_html .= " <span style='color:#9ca3af; font-size:12px; margin-left:5px;'>($r_count)</span>";
                        }

                        echo "<div class='product-card'>";
                        echo "  <div class='product-image-wrapper'>";
                        echo "      <div class='badge-container'>";
                        echo "          <span class='badge badge-category'>$category</span>";
                        
                        // FIX: Updated Stock Logic
                        if ($stock <= 0) {
                            echo "          <span class='badge' style='background:#ef4444;'>Out of Stock</span>";
                        } else if ($stock <= 5) {
                            echo "          <span class='badge' style='background:#f59e0b;'>Low Stock</span>";
                        } else {
                            echo "          <span class='badge badge-stock'>In Stock</span>";
                        }
                        
                        echo "      </div>";
                        echo "      <button class='btn-wishlist' onclick='Storefront.toggleWishlist(this, event)' title='Save to Wishlist'>❤️</button>";
                        echo "      <img src='$img' class='product-image' onerror=\"this.onerror=null; this.src='uploads/default_part.png'\">";
                        echo "  </div>";
                        
                        echo "  <div class='card-body'>";
                        echo "      <h3 class='product-title' style='margin:0; font-size:17px;'>$name</h3>";
                        // INJECTED DYNAMIC RATING HERE
                        echo "      <div class='product-meta'><span style='display:flex; align-items:center; gap:2px;'>$stars_html</span> <span style='margin-left:auto;'>Universal Fit</span></div>";
                        echo "      <div class='product-price' style='color:#ef4444; font-weight:900; font-size:22px; margin-top:auto;'>₱" . number_format($price, 2) . "</div>";
                        
                        // FIX: Change button depending on stock availability
                        if ($stock <= 0) {
                            echo "      <button type='button' disabled style='width:100%; justify-content:center; margin-top:15px; background:#374151; color:#9ca3af; border:none; padding:12px; border-radius:6px; font-weight:bold; cursor:not-allowed; opacity: 0.7;'>Out of Stock</button>";
                        } else {
                            echo "      <button type='button' class='btn-generate' style='width:100%; justify-content:center; margin-top:15px; transition:0.3s;' onclick=\"Storefront.addToCart($id, '$safe_name', $price)\">Add to Cart 🛒</button>";
                        }
                        
                        echo "  </div>";
                        echo "</div>";
                    }
                } else {
                    echo "<p style='color:#9ca3af;'>No products available at the moment.</p>";
                }
                ?>
            </div>
        </main>

        <aside class="store-cart-panel">
            <h2 style="margin-top: 0; color: #f3f4f6; border-bottom: 1px solid #374151; padding-bottom: 10px;">Your Cart</h2>
            <div id="cart-items" style="min-height: 150px; max-height: 400px; overflow-y: auto; margin-bottom: 20px; padding-right: 5px;">
                <p style="color:#9ca3af; text-align:center; margin-top:50px;">Your cart is empty.</p>
            </div>
            
            <div id="cart-receipt" style="display:none;">
                <div style="display:flex; justify-content:space-between; color:#9ca3af; margin-bottom:8px; font-size:14px;">
                    <span>Subtotal:</span><span>₱<span id="cart-subtotal-display">0.00</span></span>
                </div>
                <div style="display:flex; justify-content:space-between; color:#9ca3af; margin-bottom:15px; font-size:14px; border-bottom:1px dashed #374151; padding-bottom:15px;">
                    <span id="shipping-label">Store Pickup:</span><span>₱<span id="cart-shipping-display">0.00</span></span>
                </div>
                <div class="cart-total" style="font-size: 24px; font-weight: 900; color: #10b981; text-align: right; margin-bottom: 20px;">
                    Total: ₱<span id="cart-total-display">0.00</span>
                </div>
            </div>

            <form method="POST" id="checkout-form">
                <input type="hidden" name="cart_data" id="cart-data-input">
                <div class="customer-details-form" id="checkout-details" style="display:none; border-top: 1px solid #374151; padding-top: 15px;">
                    
                    <label style="color: #9ca3af; font-size: 13px; margin-bottom: 12px; display: block; font-weight: bold;">How would you like to receive your order?</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <label style="flex: 1; background: #111827; border: 1px solid #374151; padding: 15px 10px; border-radius: 6px; color: white; cursor: pointer; text-align: center; font-size: 14px; transition: 0.2s;" id="label-pickup">
                            <input type="radio" name="delivery_method" value="pickup" style="display:none;" onchange="updateShippingCost()">
                            🏪 Store Pickup<br><span style="color:#10b981; font-size:12px; font-weight:bold;">Free</span>
                        </label>
                        <label style="flex: 1; background: #111827; border: 1px solid #374151; padding: 15px 10px; border-radius: 6px; color: white; cursor: pointer; text-align: center; font-size: 14px; transition: 0.2s;" id="label-delivery">
                            <input type="radio" name="delivery_method" value="delivery" style="display:none;" onchange="updateShippingCost()">
                            🚚 Delivery<br><span style="color:#f59e0b; font-size:12px; font-weight:bold;">₱150.00</span>
                        </label>
                    </div>

                    <?php if(isset($_SESSION['customer_id'])): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 12px; border-radius: 6px; border: 1px solid #10b981; margin-bottom: 15px;">
                            <div style="color: #10b981; font-size: 12px; font-weight: bold; margin-bottom: 4px;">✓ Logged In As</div>
                            <div style="color: white; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['customer_name']); ?></div>
                        </div>
                        <input type="hidden" name="customer_name" value="<?php echo htmlspecialchars($_SESSION['customer_name']); ?>">
                        <input type="hidden" name="customer_phone" value="Registered-Account">
                        
                        <div id="address-group" style="display: none; margin-bottom: 15px;">
                            <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Select Delivery Address *</label>
                            <?php if(empty($my_addresses)): ?>
                                <div style="background: rgba(239, 68, 68, 0.1); border: 1px dashed #ef4444; padding: 12px; border-radius: 6px; text-align: center;">
                                    <p style="color: #ef4444; font-size: 12px; margin: 0 0 8px 0; font-weight: bold;">No addresses found in your Address Book.</p>
                                    <a href="account.php" style="color: white; background: #ef4444; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: bold; display: inline-block;">Go to Account to Add Address</a>
                                </div>
                            <?php else: ?>
                                <select name="saved_address" style="width:100%; padding:12px; border-radius:6px; border: 1px solid #374151; background:#111827; color:white; box-sizing:border-box;">
                                    <option value="" disabled selected>Choose a saved address...</option>
                                    <?php foreach($my_addresses as $addr): ?>
                                        <option value="<?php echo htmlspecialchars($addr['full_address']); ?>">
                                            <?php echo $addr['address_label'] == 'Home' ? '🏠 Home' : '🏢 Office'; ?> - <?php echo htmlspecialchars($addr['full_address']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Customer Details</label>
                        <input type="text" name="customer_name" placeholder="Full Name" style="width:100%; padding:10px; margin-bottom:10px; border-radius:4px; border:none; background:#111827; color:white; border: 1px solid #374151;" required>
                        <input type="text" name="customer_phone" placeholder="Phone Number" style="width:100%; padding:10px; margin-bottom:10px; border-radius:4px; border:none; background:#111827; color:white; border: 1px solid #374151;" required>
                        
                        <div id="address-group" style="display: none; margin-bottom: 15px; border-top: 1px dashed #374151; padding-top: 15px;">
                            <label style="color: #10b981; font-size: 13px; margin-bottom: 15px; display: block; font-weight: bold;">Delivery Location</label>
                            
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex: 1;">
                                    <select name="region" id="ph-region-shop" style="width:100%; padding:10px; border-radius:4px; border: 1px solid #374151; background:#111827; color:white;">
                                        <option value="" disabled selected>Loading Regions...</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <select name="province" id="ph-province-shop" style="width:100%; padding:10px; border-radius:4px; border: 1px solid #374151; background:#111827; color:white;">
                                        <option value="" disabled selected>Select Province</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex: 1;">
                                    <select name="city" id="ph-city-shop" style="width:100%; padding:10px; border-radius:4px; border: 1px solid #374151; background:#111827; color:white;">
                                        <option value="" disabled selected>Select City</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <select name="barangay" id="ph-barangay-shop" style="width:100%; padding:10px; border-radius:4px; border: 1px solid #374151; background:#111827; color:white;">
                                        <option value="" disabled selected>Select Barangay</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex: 1;">
                                    <input type="number" name="postal_code" placeholder="Postal Code" style="width:100%; padding:10px; border-radius:4px; border:none; background:#111827; color:white; border: 1px solid #374151;">
                                </div>
                                <div style="flex: 2;">
                                    <input type="text" name="street_name" placeholder="Street Name, Building No." style="width:100%; padding:10px; border-radius:4px; border:none; background:#111827; color:white; border: 1px solid #374151;">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" style="width:100%; padding:15px; background:#ef4444; color:white; border:none; border-radius:6px; font-weight:900; font-size:16px; cursor:pointer;" onclick="validateAndCheckout()">SECURE CHECKOUT</button>
                </div>
            </form>
        </aside>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>