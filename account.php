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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify Garage - Rider Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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
        <div>
            <?php if(isset($_SESSION['customer_id'])): ?>
                <a href="account.php?action=logout" style="color:#ef4444; text-decoration:none; font-size:14px; font-weight:bold; border: 1px solid #ef4444; padding: 8px 15px; border-radius: 6px;">Sign Out 🚪</a>
            <?php else: ?>
                <a href="index.php" style="color:#9ca3af; text-decoration:none; font-size:14px; font-weight:bold; border: 1px solid #374151; padding: 8px 15px; border-radius: 6px;">Staff Login 🔒</a>
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

        <?php else: ?>
            <h1 style="font-size: 2.5rem; margin: 0 0 5px 0; color: white;">Rider Dashboard</h1>
            <p style="color: #9ca3af; margin: 0 0 20px 0;">Manage your rides and service history.</p>

            <div class="dashboard-grid">
                
                <div>
                    <div class="panel" style="text-align: center;">
                        <div style="width: 80px; height: 80px; background: #ef4444; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; margin: 0 auto 15px auto;">
                            <?php echo $_SESSION['customer_initials']; ?>
                        </div>
                        <h2 style="margin: 0; color: white;"><?php echo htmlspecialchars($_SESSION['customer_name']); ?></h2>
                        <button class="btn-generate" style="width: 100%; justify-content: center; margin-top: 20px; background: #374151; color: white;">Edit Profile ⚙️</button>
                    </div>

                    <div class="panel">
                        <h3 style="margin: 0 0 15px 0; color: white; border-bottom: 1px solid #374151; padding-bottom: 10px;">🏍️ My Garage</h3>
                        <div style="background: #111827; padding: 15px; border-radius: 6px; border: 1px solid #10b981;">
                            <div style="font-weight: bold; color: white;">Yamaha NMAX 155</div>
                            <div style="color: #9ca3af; font-size: 12px; margin-top: 5px;">2023 Model • Active</div>
                        </div>
                        <p style="font-size: 12px; color: #10b981; margin-top: 15px;">✓ Smart Recommendations Active</p>
                        <button style="width: 100%; padding: 10px; background: transparent; color: #ef4444; border: 1px dashed #ef4444; border-radius: 6px; margin-top: 10px; cursor: pointer;">+ Add Another Bike</button>
                    </div>
                </div>

                <div>
                    <div style="display: flex; gap: 15px; margin-bottom: 25px;">
                        <a href="shop.php" class="stat-box">
                            <div style="font-size: 24px; margin-bottom: 5px;">🛒</div>
                            <div style="color: white; font-weight: bold;">Shop Parts</div>
                        </a>
                        <a href="booking.php" class="stat-box">
                            <div style="font-size: 24px; margin-bottom: 5px;">🔧</div>
                            <div style="color: white; font-weight: bold;">Book Service</div>
                        </a>
                    </div>

                    <div class="panel">
                        <h3 style="margin: 0 0 15px 0; color: white; border-bottom: 1px solid #374151; padding-bottom: 10px;">📦 Recent Orders</h3>
                        
                        <div style="text-align: center; padding: 30px 0;">
                            <div style="font-size: 30px; margin-bottom: 10px; opacity: 0.5;">🛒</div>
                            <p style="color: #9ca3af; font-size: 14px; margin: 0;">No recent orders found.</p>
                            <a href="shop.php" style="color: #ef4444; font-size: 13px; text-decoration: none; margin-top: 10px; display: inline-block;">Start shopping &rarr;</a>
                        </div>
                    </div>

                    <div class="panel">
                        <h3 style="margin: 0 0 15px 0; color: white; border-bottom: 1px solid #374151; padding-bottom: 10px;">📅 Service Appointments</h3>
                        <div class="history-row">
                            <div>
                                <div style="font-weight: bold;">CVT Cleaning & Tuning</div>
                                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">May 15, 2026 • 2:00 PM</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="color: #10b981; font-weight: bold; font-size: 14px;">Completed ✅</div>
                                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">₱1,200.00</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>