<?php
// index.php
session_start();
require_once 'db_connection.php';

// If already logged in as Admin, push them straight to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
// If already logged in as a Customer, push them to the shop
if (isset($_SESSION['customer_id'])) {
    header("Location: shop.php");
    exit();
}

$error_message = "";

// Handle the login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST['username']); // Works for both admin username or customer email
    $password = $_POST['password'];

    // --- STEP 1: CHECK IF IT IS AN ADMIN / STAFF ---
    $stmt_admin = $conn->prepare("SELECT user_id, password_hash, role FROM User_Accounts WHERE username = ?");
    $stmt_admin->bind_param("s", $username_or_email);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();

    if ($row_admin = $result_admin->fetch_assoc()) {
        // FOOLPROOF CHECK: Directly compare the text for admins
        if ($password === $row_admin['password_hash']) {
            // Success! Set the session variables for ADMIN
            $_SESSION['user_id'] = $row_admin['user_id'];
            $_SESSION['role'] = $row_admin['role'];
            
            // Send them to the admin dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Invalid password.";
        }
    } else {
        // --- STEP 2: NOT AN ADMIN? CHECK IF IT IS A CUSTOMER ---
        $stmt_cust = $conn->prepare("SELECT customer_id, first_name, last_name, password FROM Customers WHERE email = ?");
        $stmt_cust->bind_param("s", $username_or_email);
        $stmt_cust->execute();
        $result_cust = $stmt_cust->get_result();

        if ($row_cust = $result_cust->fetch_assoc()) {
            // Customers use secure hashing from account.php, so we use password_verify()
            if (password_verify($password, $row_cust['password'])) {
                // Success! Set the session variables for CUSTOMER
                $_SESSION['customer_id'] = $row_cust['customer_id'];
                $_SESSION['customer_name'] = $row_cust['first_name'] . ' ' . $row_cust['last_name'];
                $_SESSION['customer_initials'] = strtoupper(substr($row_cust['first_name'], 0, 1) . substr($row_cust['last_name'], 0, 1));
                
                // Send them to the public shop
                header("Location: shop.php"); 
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            // They don't exist in either database table
            $error_message = "User not found. Please check your credentials.";
        }
        $stmt_cust->close();
    }
    $stmt_admin->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="hero-image"></div>
    <div class="login-section">
        <h1>MOTIFY.</h1>
        <p>Garage Command Center</p>
        
        <?php if ($error_message) echo "<div class='login-error'>$error_message</div>"; ?>
        
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username or Email" required autocomplete="off">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">LOGIN TO SYSTEM</button>
        </form>
    </div>
</body>
</html>