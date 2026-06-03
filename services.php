<?php
// services.php
session_start();
require_once 'db_connection.php';

$message = "";

// Handle updating a status
if (isset($_GET['update_id']) && isset($_GET['new_status'])) {
    $update_id = intval($_GET['update_id']);
    $new_status = $_GET['new_status'];
    
    $stmt_update = $conn->prepare("UPDATE Service_Bookings SET status = ? WHERE booking_id = ?");
    $stmt_update->bind_param("si", $new_status, $update_id);
    if ($stmt_update->execute()) {
        header("Location: services.php"); // Refresh to clear URL
        exit();
    }
}

// Handle adding a new service booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_booking'])) {
    $customer_name = $_POST['customer_name'];
    $motorcycle_model = $_POST['motorcycle_model'];
    $service_type = $_POST['service_type'];
    $booking_date = $_POST['booking_date'];
    
    $stmt = $conn->prepare("INSERT INTO Service_Bookings (customer_name, motorcycle_model, service_type, booking_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $customer_name, $motorcycle_model, $service_type, $booking_date);
    
    if ($stmt->execute()) {
        $message = "<div style='color:#10b981; margin-bottom:15px; font-weight:bold;'>✅ Service successfully booked!</div>";
    } else {
        $message = "<div style='color:#ef4444; margin-bottom:15px;'>❌ Error: " . $conn->error . "</div>";
    }
}

// Fetch all bookings
$sql = "SELECT * FROM Service_Bookings ORDER BY booking_date ASC, booking_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Service Tracking</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .inline-input { padding: 10px; background: #111827; border: 1px solid #374151; color: white; border-radius: 6px; margin-right: 10px; }
        .btn-add { background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-add:hover { background: #dc2626; }
        .action-link { text-decoration: none; font-size: 13px; font-weight: bold; margin-right: 10px; padding: 5px 10px; border-radius: 4px; background: #374151; color: white; }
        .action-link:hover { background: #4b5563; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-top: 0;">🔧 Service & Repair Bay</h1>
        <?php echo $message; ?>

        <!-- Add Booking Form -->
        <div class="service-form">
            <h3 style="margin-top: 0; color: #9ca3af;">Register New Service</h3>
            <form method="POST" action="" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="hidden" name="add_booking" value="1">
                <input type="text" name="customer_name" class="inline-input" placeholder="Customer Name" required>
                <input type="text" name="motorcycle_model" class="inline-input" placeholder="Motorcycle (e.g. NMAX, Click 125)" required>
                <input type="text" name="service_type" class="inline-input" placeholder="Service (e.g. Change Oil)" required>
                <input type="date" name="booking_date" class="inline-input" required>
                <button type="submit" class="btn-add">Add to Queue</button>
            </form>
        </div>

        <!-- Service Tracking Table -->
        <table class="service-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Motorcycle</th>
                    <th>Service Required</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>#" . $row['booking_id'] . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['customer_name']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['motorcycle_model']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['service_type']) . "</td>";
                        echo "<td>" . $row['booking_date'] . "</td>";
                        
                        // Status Badge
                        $statusClass = '';
                        if ($row['status'] == 'Pending') $statusClass = 'status-pending';
                        if ($row['status'] == 'In Progress') $statusClass = 'status-progress';
                        if ($row['status'] == 'Completed') $statusClass = 'status-completed';
                        
                        echo "<td><span class='status-badge $statusClass'>" . $row['status'] . "</span></td>";
                        
                        // Action Buttons
                        echo "<td>";
                        if ($row['status'] != 'In Progress' && $row['status'] != 'Completed') {
                            echo "<a href='services.php?update_id=" . $row['booking_id'] . "&new_status=In Progress' class='action-link'>🛠️ Start Job</a>";
                        }
                        if ($row['status'] != 'Completed') {
                            echo "<a href='services.php?update_id=" . $row['booking_id'] . "&new_status=Completed' class='action-link'>✅ Finish</a>";
                        }
                        echo "</td>";
                        
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center; padding: 30px; color: #9ca3af;'>No services currently queued.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>