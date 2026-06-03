<?php
// index.php
session_start();
require_once 'db_connection.php';

// If already logged in, push them straight to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = "";

// Handle the login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Search the database for this user
    $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM User_Accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // FOOLPROOF CHECK: Directly compare the text
        if ($password === $row['password_hash']) {
            // Success! Set the session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            
            // Send them to the system
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Invalid password.";
        }
    } else {
        $error_message = "User not found.";
    }
    $stmt->close();
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
            <input type="text" name="username" placeholder="Username" required autocomplete="off">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">LOGIN TO SYSTEM</button>
        </form>
    </div>
</body>
</html>