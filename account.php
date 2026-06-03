<?php
// account.php - PUBLIC FACING CUSTOMER DASHBOARD
session_start();
require_once 'db_connection.php';

$auth_message = "";

// --- 1. HANDLE LOGOUT ---
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_name']);
    header("Location: account.php");
    exit();
}

// --- 2. HANDLE REGISTRATION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_customer'])) {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    $check_stmt = $conn->prepare("SELECT customer_id FROM Customers WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $auth_message = "<div style='color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #ef4444;'>❌ Email is already registered!</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO Customers (first_name, last_name, phone_number, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fname, $lname, $phone, $email, $password);
        if ($stmt->execute()) {
            $auth_message = "<div style='color:#10b981; background:rgba(16, 185, 129, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #10b981;'>✅ Account created! You can now log in.</div>";
        } else {
            $auth_message = "<div style='color:#ef4444;'>❌ Error: " . $conn->error . "</div>";
        }
    }
}

// --- 3. HANDLE LOGIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_customer'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT customer_id, first_name, last_name, password FROM Customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['customer_id'] = $row['customer_id'];
            $_SESSION['customer_name'] = $row['first_name'] . ' ' . $row['last_name'];
            $_SESSION['customer_initials'] = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
            
            header("Location: account.php"); 
            exit();
        } else {
            $auth_message = "<div style='color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #ef4444;'>❌ Incorrect password.</div>";
        }
    } else {
        $auth_message = "<div style='color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #ef4444;'>❌ Account not found.</div>";
    }
}

// --- 4. HANDLE ADD BIKE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_bike']) && isset($_SESSION['customer_id'])) {
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $customer_id = $_SESSION['customer_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO Customer_Bikes (customer_id, brand, model, year) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $customer_id, $brand, $model, $year);
        $stmt->execute();
        $auth_message = "<div style='color:#10b981; background:rgba(16, 185, 129, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #10b981;'>✅ Bike successfully added to your garage!</div>";
    } catch (Exception $e) {
        $auth_message = "<div style='color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #ef4444;'>❌ Error saving bike data.</div>";
    }
}

// --- 5. HANDLE EDIT PROFILE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_profile']) && isset($_SESSION['customer_id'])) {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $phone = trim($_POST['phone_number']);
    $cid = $_SESSION['customer_id'];

    try {
        $stmt = $conn->prepare("UPDATE Customers SET first_name=?, last_name=?, phone_number=? WHERE customer_id=?");
        $stmt->bind_param("sssi", $fname, $lname, $phone, $cid);
        $stmt->execute();

        $_SESSION['customer_name'] = $fname . ' ' . $lname;
        $_SESSION['customer_initials'] = strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));

        $auth_message = "<div style='color:#10b981; background:rgba(16, 185, 129, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #10b981;'>✅ Profile updated successfully!</div>";
    } catch (Exception $e) {
        $auth_message = "<div style='color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #ef4444;'>❌ Error updating profile.</div>";
    }
}

// --- 6. HANDLE ADD ADDRESS (API INTEGRATION) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_address']) && isset($_SESSION['customer_id'])) {
    $label = trim($_POST['address_label']);
    
    $region = trim($_POST['region']);
    $province = trim($_POST['province']);
    $city = trim($_POST['city']);
    $barangay = trim($_POST['barangay']);
    $postal = trim($_POST['postal_code']);
    $street = trim($_POST['street_name']);

    $full_address = "$street, Brgy. $barangay, $city, $province, $region, $postal";
    $cid = $_SESSION['customer_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO customer_addresses (customer_id, address_label, full_address) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $cid, $label, $full_address);
        $stmt->execute();
        $auth_message = "<div style='color:#10b981; background:rgba(16, 185, 129, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #10b981;'>✅ Address successfully saved to your Address Book!</div>";
    } catch (Exception $e) {
        $auth_message = "<div style='color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:10px; border-radius:6px; margin-bottom:15px; border: 1px solid #ef4444;'>❌ Error saving address data. Make sure your database has the 'customer_addresses' table!</div>";
    }
}

// --- 7. FETCH ALL DASHBOARD DATA ---
$my_bikes = [];
$user_data = [];
$notifications = [];
$my_orders = [];
$my_bookings = [];
$my_addresses = [];
$unread_count = 0;

if (isset($_SESSION['customer_id'])) {
    $cid = $_SESSION['customer_id'];
    
    if (isset($_GET['read_notif'])) {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE customer_id = $cid");
        header("Location: account.php");
        exit();
    }

    $user_result = $conn->query("SELECT first_name, last_name, phone_number, email FROM Customers WHERE customer_id = $cid");
    if ($user_result) $user_data = $user_result->fetch_assoc();

    $bike_result = $conn->query("SELECT * FROM Customer_Bikes WHERE customer_id = $cid ORDER BY bike_id DESC");
    if ($bike_result) { while($b = $bike_result->fetch_assoc()) $my_bikes[] = $b; }
    
    $addr_result = $conn->query("SELECT * FROM customer_addresses WHERE customer_id = $cid ORDER BY created_at DESC");
    if ($addr_result) { while($a = $addr_result->fetch_assoc()) $my_addresses[] = $a; }
    
    $notif_result = $conn->query("SELECT * FROM notifications WHERE customer_id = $cid ORDER BY created_at DESC LIMIT 5");
    if ($notif_result) {
        while($n = $notif_result->fetch_assoc()) {
            $notifications[] = $n;
            if ($n['is_read'] == 0) $unread_count++;
        }
    }

    $order_result = $conn->query("SELECT s.transaction_date, s.total_amount, s.quantity, p.product_name 
                                  FROM sales s 
                                  JOIN products p ON s.product_id = p.product_id 
                                  WHERE s.customer_id = $cid 
                                  ORDER BY s.transaction_date DESC LIMIT 5");
    if ($order_result) { while($o = $order_result->fetch_assoc()) $my_orders[] = $o; }

    $booking_result = $conn->query("SELECT * FROM service_bookings WHERE customer_id = $cid ORDER BY booking_date DESC LIMIT 5");
    if ($booking_result) { while($bk = $booking_result->fetch_assoc()) $my_bookings[] = $bk; }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify Garage - Rider Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <script>
        function openAddAddressModal() { document.getElementById('addAddressModal').style.display = 'flex'; }
        function closeAddAddressModal() { document.getElementById('addAddressModal').style.display = 'none'; }
    </script>
</head>
<body class="store-body">

    <header class="store-header">
        <a href="home.php" class="store-logo">MOTIFY.</a>
        <nav class="store-nav-links">
            <a href="home.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="booking.php">Services</a>
            <a href="shop.php">Wishlist ❤️</a>
            <a href="account.php" style="color: #ef4444;">Account</a>
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
                                <a href="?read_notif=true" style="font-size: 11px; color: #3b82f6; text-decoration: none;">Mark all as read</a>
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

    <div class="store-layout-container" style="display:block; padding-top: 40px;">
        
        <?php echo $auth_message; ?>

        <?php if (!isset($_SESSION['customer_id'])): ?>
            <div class="auth-container">
                <div class="auth-box">
                    <h2 style="margin-top:0; color:white;">Welcome Back</h2>
                    <p style="color:#9ca3af; font-size:14px; margin-bottom:20px;">Log in to access your garage and track your orders.</p>
                    <form method="POST" action="">
                        <input type="email" name="email" class="auth-input" placeholder="Email Address" required>
                        <input type="password" name="password" class="auth-input" placeholder="Password" required>
                        <button type="submit" name="login_customer" class="btn-generate" style="width:100%; justify-content:center;">Access Dashboard 🏍️</button>
                    </form>
                </div>

                <div class="auth-box">
                    <h2 style="margin-top:0; color:white;">Create an Account</h2>
                    <p style="color:#9ca3af; font-size:14px; margin-bottom:20px;">Join Motify to book services and build your dream ride.</p>
                    <form method="POST" action="">
                        <div style="display:flex; gap:15px;">
                            <input type="text" name="first_name" class="auth-input" placeholder="First Name" required>
                            <input type="text" name="last_name" class="auth-input" placeholder="Last Name" required>
                        </div>
                        <input type="text" name="phone" class="auth-input" placeholder="Phone Number (Optional)">
                        <input type="email" name="email" class="auth-input" placeholder="Email Address" required>
                        <input type="password" name="password" class="auth-input" placeholder="Create Password" required>
                        <button type="submit" name="register_customer" style="width:100%; padding:12px; background:transparent; border:1px solid #ef4444; color:#ef4444; font-weight:bold; border-radius:6px; cursor:pointer; transition:0.3s;" onmouseover="this.style.background='#ef4444'; this.style.color='white';" onmouseout="this.style.background='transparent'; this.style.color='#ef4444';">Register Account</button>
                    </form>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" style="color:#9ca3af; text-decoration:none; font-size:14px; font-weight:bold; border: 1px dashed #374151; padding: 10px 20px; border-radius: 6px; transition: 0.2s;" onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6';" onmouseout="this.style.borderColor='#374151'; this.style.color='#9ca3af';">👔 Staff / Admin Login</a>
            </div>

        <?php else: ?>
            <h1 style="font-size: 2.5rem; margin: 0 0 5px 0; color: white;">Rider Dashboard</h1>
            <p style="color: #9ca3af; margin: 0 0 30px 0;">Manage your rides, locations, and service history.</p>

            <div style="display: flex; flex-wrap: wrap; gap: 30px; align-items: flex-start;">

                <div style="flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 25px;">
                    <div style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 30px; text-align: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                        <div style="width: 90px; height: 90px; background: linear-gradient(135deg, #ef4444, #b91c1c); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 900; margin: 0 auto 15px auto; border: 4px solid #111827;">
                            <?php echo $_SESSION['customer_initials']; ?>
                        </div>
                        <h2 style="margin: 0 0 5px 0; color: white; font-size: 22px;"><?php echo htmlspecialchars($_SESSION['customer_name']); ?></h2>
                        <p style="margin: 0 0 20px 0; color: #10b981; font-size: 13px; font-weight: bold;">Verified Rider ✓</p>
                        <button type="button" onclick="openEditProfileModal()" style="width: 100%; padding: 12px; background: #374151; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#4b5563'" onmouseout="this.style.background='#374151'">Edit Profile ⚙️</button>
                        <a href="account.php?action=logout" style="display: block; text-align: center; width: 100%; padding: 12px; margin-top: 10px; background: transparent; color: #ef4444; border: 1px solid #ef4444; border-radius: 6px; font-weight: bold; text-decoration: none; box-sizing: border-box; transition: 0.2s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'" onmouseout="this.style.background='transparent'">Sign Out 🚪</a>
                    </div>

                    <div style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 25px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                        <h3 style="margin: 0 0 20px 0; color: white; display: flex; justify-content: space-between; align-items: center;">
                            <span>📍 Address Book</span>
                            <span style="font-size: 12px; background: #374151; padding: 4px 8px; border-radius: 4px; color: #9ca3af;"><?php echo count($my_addresses); ?> Saved</span>
                        </h3>
                        <?php if (empty($my_addresses)): ?>
                            <div style="text-align: center; padding: 25px 10px; background: #111827; border: 1px dashed #374151; border-radius: 8px;">
                                <p style="color: #9ca3af; margin: 0 0 15px 0; font-size: 14px;">No addresses saved yet.</p>
                                <button type="button" style="background: transparent; color: #3b82f6; border: 1px dashed #3b82f6; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.2s;" onmouseover="this.style.background='rgba(59, 130, 246, 0.1)'" onmouseout="this.style.background='transparent'" onclick="openAddAddressModal()">+ Add Delivery Address</button>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach($my_addresses as $addr): ?>
                                    <div style="background: #111827; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                                        <div style="font-weight: 800; color: white; font-size: 14px; margin-bottom: 4px;">
                                            <?php echo $addr['address_label'] == 'Home' ? '🏠 Home' : '🏢 Office'; ?>
                                        </div>
                                        <div style="color: #9ca3af; font-size: 12px; line-height: 1.4;">
                                            <?php echo htmlspecialchars($addr['full_address']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <button type="button" style="width: 100%; padding: 12px; background: transparent; color: #9ca3af; border: 1px dashed #374151; border-radius: 8px; margin-top: 5px; cursor: pointer; font-weight: bold; transition: 0.2s;" onmouseover="this.style.color='#3b82f6'; this.style.borderColor='#3b82f6';" onmouseout="this.style.color='#9ca3af'; this.style.borderColor='#374151';" onclick="openAddAddressModal()">+ Add Another Address</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 25px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                        <h3 style="margin: 0 0 20px 0; color: white; display: flex; justify-content: space-between; align-items: center;">
                            <span>🏍️ My Garage</span>
                            <span style="font-size: 12px; background: #374151; padding: 4px 8px; border-radius: 4px; color: #9ca3af;"><?php echo count($my_bikes); ?> Bikes</span>
                        </h3>
                        <?php if (empty($my_bikes)): ?>
                            <div style="text-align: center; padding: 25px 10px; background: #111827; border: 1px dashed #374151; border-radius: 8px;">
                                <p style="color: #9ca3af; margin: 0 0 15px 0; font-size: 14px;">No motorcycles registered yet.</p>
                                <button type="button" style="background: transparent; color: #ef4444; border: 1px dashed #ef4444; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.2s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'" onmouseout="this.style.background='transparent'" onclick="openAddBikeModal()">+ Add Your First Bike</button>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach($my_bikes as $bike): ?>
                                    <div style="background: #111827; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                                        <div style="font-weight: 800; color: white; font-size: 16px; margin-bottom: 4px;"><?php echo htmlspecialchars($bike['brand'] . ' ' . $bike['model']); ?></div>
                                        <div style="color: #9ca3af; font-size: 12px; display: flex; justify-content: space-between;">
                                            <span><?php echo htmlspecialchars($bike['year']); ?> Model</span>
                                            <span style="color: #10b981; font-weight: bold;">● Active</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <button type="button" style="width: 100%; padding: 12px; background: transparent; color: #9ca3af; border: 1px dashed #374151; border-radius: 8px; margin-top: 5px; cursor: pointer; font-weight: bold; transition: 0.2s;" onmouseover="this.style.color='#ef4444'; this.style.borderColor='#ef4444';" onmouseout="this.style.color='#9ca3af'; this.style.borderColor='#374151';" onclick="openAddBikeModal()">+ Add Another Bike</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="flex: 2; min-width: 400px; display: flex; flex-direction: column; gap: 25px;">
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <a href="shop.php" style="flex: 1; min-width: 180px; background: linear-gradient(135deg, #1f2937, #111827); border: 1px solid #374151; border-radius: 12px; padding: 25px; text-decoration: none; display: flex; align-items: center; gap: 20px; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);" onmouseover="this.style.borderColor='#ef4444'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='#374151'; this.style.transform='translateY(0)';">
                            <div style="font-size: 36px;">🛒</div>
                            <div>
                                <div style="color: white; font-weight: 900; font-size: 18px;">Shop Parts</div>
                                <div style="color: #9ca3af; font-size: 13px; margin-top: 4px;">Browse online store</div>
                            </div>
                        </a>
                        <a href="booking.php" style="flex: 1; min-width: 180px; background: linear-gradient(135deg, #1f2937, #111827); border: 1px solid #374151; border-radius: 12px; padding: 25px; text-decoration: none; display: flex; align-items: center; gap: 20px; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);" onmouseover="this.style.borderColor='#ef4444'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='#374151'; this.style.transform='translateY(0)';">
                            <div style="font-size: 36px;">🔧</div>
                            <div>
                                <div style="color: white; font-weight: 900; font-size: 18px;">Book Service</div>
                                <div style="color: #9ca3af; font-size: 13px; margin-top: 4px;">Schedule maintenance</div>
                            </div>
                        </a>
                    </div>

                    <div style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 25px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                        <h3 style="margin: 0 0 20px 0; color: white; border-bottom: 1px solid #374151; padding-bottom: 15px;">📦 Recent Orders</h3>
                        <?php if (empty($my_orders)): ?>
                            <div style="text-align: center; padding: 30px 0;">
                                <div style="font-size: 40px; margin-bottom: 15px; opacity: 0.3;">🛍️</div>
                                <p style="color: #9ca3af; font-size: 15px; margin: 0;">You haven't placed any orders yet.</p>
                                <a href="shop.php" style="color: #ef4444; font-size: 14px; text-decoration: none; margin-top: 15px; display: inline-block; font-weight: bold; border-bottom: 1px dashed #ef4444;">Start shopping &rarr;</a>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach($my_orders as $order): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; background: #111827; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                                        <div>
                                            <div style="font-weight: bold; color: white; font-size: 14px;"><?php echo htmlspecialchars($order['product_name']); ?> (x<?php echo $order['quantity']; ?>)</div>
                                            <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;"><?php echo date("M d, Y • h:i A", strtotime($order['transaction_date'])); ?></div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="color: white; font-weight: bold; font-size: 14px;">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="background: #1f2937; border: 1px solid #374151; border-radius: 12px; padding: 25px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                        <h3 style="margin: 0 0 20px 0; color: white; border-bottom: 1px solid #374151; padding-bottom: 15px;">📅 Service Appointments</h3>
                        <?php if (empty($my_bookings)): ?>
                            <div style="text-align: center; padding: 30px 0;">
                                <p style="color: #9ca3af; font-size: 15px; margin: 0;">No service appointments yet.</p>
                                <a href="booking.php" style="color: #ef4444; font-size: 14px; text-decoration: none; margin-top: 15px; display: inline-block; font-weight: bold; border-bottom: 1px dashed #ef4444;">Book a service &rarr;</a>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach($my_bookings as $booking): 
                                    $b_status = $booking['booking_status'];
                                    $s_color = '#9ca3af'; $s_bg = 'rgba(156, 163, 175, 0.1)';
                                    if($b_status == 'Pending') { $s_color = '#f59e0b'; $s_bg = 'rgba(245, 158, 11, 0.1)'; }
                                    if($b_status == 'Approved') { $s_color = '#3b82f6'; $s_bg = 'rgba(59, 130, 246, 0.1)'; }
                                    if($b_status == 'In Progress') { $s_color = '#8b5cf6'; $s_bg = 'rgba(139, 92, 246, 0.1)'; }
                                    if($b_status == 'Completed') { $s_color = '#10b981'; $s_bg = 'rgba(16, 185, 129, 0.1)'; }
                                    if($b_status == 'Cancelled') { $s_color = '#ef4444'; $s_bg = 'rgba(239, 68, 68, 0.1)'; }
                                ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; background: #111827; padding: 15px; border-radius: 8px; border-left: 4px solid <?php echo $s_color; ?>;">
                                        <div>
                                            <div style="font-weight: 900; color: white; font-size: 15px;"><?php echo htmlspecialchars($booking['service_type']); ?></div>
                                            <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;"><?php echo date("M d, Y", strtotime($booking['booking_date'])); ?> • <?php echo date("h:i A", strtotime($booking['booking_time'])); ?></div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="color: <?php echo $s_color; ?>; font-weight: 900; font-size: 12px; background: <?php echo $s_bg; ?>; padding: 4px 10px; border-radius: 20px; display: inline-block;"><?php echo $b_status; ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="addAddressModal" class="modal-overlay">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeAddAddressModal()">&times;</span>
                    <h2 style="color: white; margin-top: 0; border-bottom: 1px solid #374151; padding-bottom: 15px;">📍 Add Delivery Address</h2>
                    
                    <form method="POST" action="">
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Label</label>
                        <select name="address_label" class="auth-input" style="background:#111827; border:1px solid #374151; margin-bottom: 20px; width: 100%; box-sizing: border-box;" required>
                            <option value="Home">🏠 Home</option>
                            <option value="Office">🏢 Office</option>
                        </select>

                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Region *</label>
                                <select name="region" id="ph-region-acc" class="auth-input" style="background:#111827; border:1px solid #374151; width: 100%; box-sizing: border-box;" required>
                                    <option value="" disabled selected>Loading Regions...</option>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Province *</label>
                                <select name="province" id="ph-province-acc" class="auth-input" style="background:#111827; border:1px solid #374151; width: 100%; box-sizing: border-box;" required>
                                    <option value="" disabled selected>Select Province</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">City / Municipality *</label>
                                <select name="city" id="ph-city-acc" class="auth-input" style="background:#111827; border:1px solid #374151; width: 100%; box-sizing: border-box;" required>
                                    <option value="" disabled selected>Select City</option>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Barangay *</label>
                                <select name="barangay" id="ph-barangay-acc" class="auth-input" style="background:#111827; border:1px solid #374151; width: 100%; box-sizing: border-box;" required>
                                    <option value="" disabled selected>Select Barangay</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; margin-bottom: 25px;">
                            <div style="flex: 1;">
                                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Postal Code *</label>
                                <input type="number" name="postal_code" class="auth-input" placeholder="e.g. 1870" style="background:#111827; border:1px solid #374151; width: 100%; box-sizing: border-box;" required>
                            </div>
                            <div style="flex: 2;">
                                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Street Name, Building, House No. *</label>
                                <input type="text" name="street_name" class="auth-input" placeholder="e.g. 123 Main St., Blk 4" style="background:#111827; border:1px solid #374151; width: 100%; box-sizing: border-box;" required>
                            </div>
                        </div>

                        <button type="submit" name="add_address" class="btn-generate" style="width: 100%; justify-content: center; padding: 15px; font-size: 16px; background: #3b82f6;">Save Address to Book</button>
                    </form>
                </div>
            </div>

            <div id="addBikeModal" class="modal-overlay">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeAddBikeModal()">&times;</span>
                    <h2 style="color: white; margin-top: 0; border-bottom: 1px solid #374151; padding-bottom: 15px;">🏍️ Register a Motorcycle</h2>
                    <form method="POST" action="">
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Brand (e.g., Yamaha, Honda)</label>
                        <input type="text" name="brand" class="auth-input" style="background:#111827; border:1px solid #374151; margin-bottom: 20px; width: 100%; box-sizing: border-box;" required>
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Model (e.g., NMAX 155, Click 125i)</label>
                        <input type="text" name="model" class="auth-input" style="background:#111827; border:1px solid #374151; margin-bottom: 20px; width: 100%; box-sizing: border-box;" required>
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Year Model</label>
                        <input type="number" name="year" class="auth-input" placeholder="2023" style="background:#111827; border:1px solid #374151; margin-bottom: 25px; width: 100%; box-sizing: border-box;" required>
                        <button type="submit" name="add_bike" class="btn-generate" style="width: 100%; justify-content: center; padding: 15px; font-size: 16px;">Save to Garage</button>
                    </form>
                </div>
            </div>

            <div id="editProfileModal" class="modal-overlay">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeEditProfileModal()">&times;</span>
                    <h2 style="color: white; margin-top: 0; border-bottom: 1px solid #374151; padding-bottom: 15px;">⚙️ Edit Profile</h2>
                    <form method="POST" action="">
                        <div style="display:flex; gap:15px; margin-bottom: 20px;">
                            <div style="flex:1;">
                                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">First Name</label>
                                <input type="text" name="first_name" class="auth-input" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" style="background:#111827; border:1px solid #374151; width: 100%; box-sizing: border-box;" required>
                            </div>
                            <div style="flex:1;">
                                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Last Name</label>
                                <input type="text" name="last_name" class="auth-input" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" style="background:#111827; border:1px solid #374151; width: 100%; box-sizing: border-box;" required>
                            </div>
                        </div>
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Phone Number</label>
                        <input type="text" name="phone_number" class="auth-input" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" style="background:#111827; border:1px solid #374151; margin-bottom: 20px; width: 100%; box-sizing: border-box;" required>
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Email (Read Only)</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" class="auth-input" style="background:#374151; border:1px solid #374151; margin-bottom: 25px; width: 100%; box-sizing: border-box; color: #9ca3af; cursor: not-allowed;" disabled>
                        <button type="submit" name="edit_profile" class="btn-generate" style="width: 100%; justify-content: center; padding: 15px; font-size: 16px;">Save Changes</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>