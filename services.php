<?php
// services.php - ADMIN / STAFF SERVICE BAY
session_start();
require_once 'db_connection.php';

// Gatekeeper: Only Staff and Admins allowed
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// --- 1. HANDLE STATUS UPDATES & NOTIFICATIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['new_status'];
    
    // First, we need to know WHICH customer to notify, so we fetch the customer_id for this booking
    $get_cust = $conn->query("SELECT customer_id, service_type FROM service_bookings WHERE booking_id = $booking_id");
    $booking_data = $get_cust->fetch_assoc();
    $cust_id = $booking_data['customer_id'];
    $service_name = $booking_data['service_type'];

    // Update the actual booking status
    $stmt = $conn->prepare("UPDATE service_bookings SET booking_status = ? WHERE booking_id = ?");
    $stmt->bind_param("si", $new_status, $booking_id);
    $stmt->execute();
    
    // Generate the automated notification text
    $title = "Service Update";
    $message = "";
    if ($new_status == 'Approved') {
        $message = "Your request for '$service_name' has been approved. See you at the shop!";
    } elseif ($new_status == 'In Progress') {
        $message = "Good news! Our mechanics have started working on your '$service_name'.";
    } elseif ($new_status == 'Completed') {
        $message = "Your motorcycle is ready! The '$service_name' is complete. You can now pick it up.";
    }

    // Insert the notification into the database
    if ($message != "") {
        $notif_stmt = $conn->prepare("INSERT INTO notifications (customer_id, title, message) VALUES (?, ?, ?)");
        $notif_stmt->bind_param("iss", $cust_id, $title, $message);
        $notif_stmt->execute();
    }
    
    header("Location: services.php");
    exit();
}

// --- 2. HANDLE MANUAL WALK-IN BOOKINGS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $cust_name = trim($_POST['customer_name']);
    $vehicle = trim($_POST['vehicle_model']);
    $service = trim($_POST['service_type']);
    $date = trim($_POST['booking_date']);
    $time = "08:00:00"; // Default walk-in time
    
    // Auto-create a temporary customer profile so the relational database works
    $name_parts = explode(' ', $cust_name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : 'Walk-in';

    $stmt_cust = $conn->prepare("INSERT INTO customers (first_name, last_name) VALUES (?, ?)");
    $stmt_cust->bind_param("ss", $first_name, $last_name);
    $stmt_cust->execute();
    $new_customer_id = $conn->insert_id;

    // Insert the booking
    $stmt_book = $conn->prepare("INSERT INTO service_bookings (customer_id, service_type, booking_date, booking_time, vehicle_model, booking_status) VALUES (?, ?, ?, ?, ?, 'Approved')");
    $stmt_book->bind_param("issss", $new_customer_id, $service, $date, $time, $vehicle);
    $stmt_book->execute();
    
    header("Location: services.php");
    exit();
}

// --- 3. FETCH BOOKINGS (USING A SQL JOIN) ---
$query = "SELECT sb.*, c.first_name, c.last_name 
          FROM service_bookings sb 
          LEFT JOIN customers c ON sb.customer_id = c.customer_id 
          ORDER BY sb.booking_date ASC, sb.booking_time ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Service Bay</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
            🔧 Service & Repair Bay
        </h1>

        <div style="background: #1f2937; padding: 25px; border-radius: 12px; margin-bottom: 30px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: white;">Register Walk-In Service</h3>
            <form method="POST" action="" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="customer_name" placeholder="Customer Name" style="flex: 1; padding: 12px; border-radius: 6px; background: #111827; border: 1px solid #374151; color: white;" required>
                <input type="text" name="vehicle_model" placeholder="Motorcycle (e.g. NMAX)" style="flex: 1; padding: 12px; border-radius: 6px; background: #111827; border: 1px solid #374151; color: white;" required>
                <input type="text" name="service_type" placeholder="Service (e.g. Change Oil)" style="flex: 1; padding: 12px; border-radius: 6px; background: #111827; border: 1px solid #374151; color: white;" required>
                <input type="date" name="booking_date" style="padding: 12px; border-radius: 6px; background: #111827; border: 1px solid #374151; color: white;" required>
                <button type="submit" name="add_service" class="btn-generate" style="padding: 12px 25px; border: none; cursor: pointer;">Add to Queue</button>
            </form>
        </div>

        <div style="background: #1f2937; border-radius: 12px; border: 1px solid #374151; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: rgba(17, 24, 39, 0.5); border-bottom: 1px solid #374151; color: #9ca3af; font-size: 12px; text-transform: uppercase;">
                        <th style="padding: 15px 20px;">ID</th>
                        <th style="padding: 15px 20px;">Customer</th>
                        <th style="padding: 15px 20px;">Motorcycle</th>
                        <th style="padding: 15px 20px;">Service Required</th>
                        <th style="padding: 15px 20px;">Date & Time</th>
                        <th style="padding: 15px 20px;">Status</th>
                        <th style="padding: 15px 20px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            
                            // Color code the status badges
                            $status_color = '#9ca3af'; // Default Grey
                            $bg_color = 'transparent';
                            if ($row['booking_status'] == 'Approved') { $status_color = '#3b82f6'; $bg_color = 'rgba(59, 130, 246, 0.1)'; }
                            if ($row['booking_status'] == 'In Progress') { $status_color = '#f59e0b'; $bg_color = 'rgba(245, 158, 11, 0.1)'; }
                            if ($row['booking_status'] == 'Completed') { $status_color = '#10b981'; $bg_color = 'rgba(16, 185, 129, 0.1)'; }
                            if ($row['booking_status'] == 'Cancelled') { $status_color = '#ef4444'; $bg_color = 'rgba(239, 68, 68, 0.1)'; }
                            
                            // Format the Date and Time beautifully
                            $formatted_date = date("M d, Y", strtotime($row['booking_date']));
                            $formatted_time = date("h:i A", strtotime($row['booking_time']));
                        ?>
                            <tr style="border-bottom: 1px solid #374151; color: white;">
                                <td style="padding: 15px 20px; color: #9ca3af;">#<?php echo $row['booking_id']; ?></td>
                                <td style="padding: 15px 20px; font-weight: bold;">
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo htmlspecialchars($row['vehicle_model']); ?></td>
                                <td style="padding: 15px 20px;"><?php echo htmlspecialchars($row['service_type']); ?></td>
                                <td style="padding: 15px 20px;">
                                    <div><?php echo $formatted_date; ?></div>
                                    <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;"><?php echo $formatted_time; ?></div>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <span style="color: <?php echo $status_color; ?>; background: <?php echo $bg_color; ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; border: 1px solid <?php echo $status_color; ?>;">
                                        <?php echo htmlspecialchars($row['booking_status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 20px;">
                                    <form method="POST" action="" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                        
                                        <?php if ($row['booking_status'] == 'Pending'): ?>
                                            <button type="submit" name="new_status" value="Approved" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;">Approve</button>
                                        <?php endif; ?>
                                        
                                        <?php if ($row['booking_status'] == 'Approved'): ?>
                                            <button type="submit" name="new_status" value="In Progress" style="background: #f59e0b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;">Start Job 🛠️</button>
                                        <?php endif; ?>
                                        
                                        <?php if ($row['booking_status'] == 'In Progress'): ?>
                                            <button type="submit" name="new_status" value="Completed" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;">Finish ✅</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: #9ca3af;">No service appointments in the queue.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>