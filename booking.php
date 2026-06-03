<?php
// booking.php - PUBLIC FACING
session_start();
require_once 'db_connection.php';

$message = "";

// Handle Booking Form Submission from the Modal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_booking'])) {
    $service_type = trim($_POST['service_type']);
    $booking_date = trim($_POST['booking_date']);
    $booking_time = trim($_POST['booking_time']);
    $vehicle_model = trim($_POST['vehicle_model']);
    $notes = trim($_POST['notes']);
    
    $customer_id = null;
    $conn->begin_transaction();

    try {
        // 1. Identify or Register the Customer
        if (isset($_SESSION['customer_id'])) {
            $customer_id = $_SESSION['customer_id'];
        } else {
            $customer_name = trim($_POST['customer_name']);
            $customer_phone = trim($_POST['customer_phone']);
            
            // Check if customer already exists by phone
            $stmt_check = $conn->prepare("SELECT customer_id FROM Customers WHERE phone_number = ?");
            $stmt_check->bind_param("s", $customer_phone);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $customer_id = $row['customer_id'];
            } else {
                // Auto-register new guest
                $name_parts = explode(' ', $customer_name, 2);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                
                $stmt_insert = $conn->prepare("INSERT INTO Customers (first_name, last_name, phone_number) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("sss", $first_name, $last_name, $customer_phone);
                $stmt_insert->execute();
                $customer_id = $conn->insert_id;
            }
        }

        // 2. Insert the Booking
        $stmt_book = $conn->prepare("INSERT INTO service_bookings (customer_id, service_type, booking_date, booking_time, vehicle_model, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_book->bind_param("isssss", $customer_id, $service_type, $booking_date, $booking_time, $vehicle_model, $notes);
        $stmt_book->execute();
        
        // 3. SEND THE NOTIFICATION TO THE RIDER'S BELL
        $notif_title = "Booking Request Sent";
        $notif_msg = "Your appointment for $service_type on " . date("M d, Y", strtotime($booking_date)) . " is pending approval.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (customer_id, title, message) VALUES (?, ?, ?)");
        $notif_stmt->bind_param("iss", $customer_id, $notif_title, $notif_msg);
        $notif_stmt->execute();

        // 4. SEND THE NOTIFICATION TO THE ADMIN/STAFF BELL
        $admin_title = "New Service Walk-in/Booking";
        $admin_msg = "A new booking for $service_type has been requested. Check the Service Bay.";
        $admin_notif_stmt = $conn->prepare("INSERT INTO admin_notifications (title, message) VALUES (?, ?)");
        $admin_notif_stmt->bind_param("ss", $admin_title, $admin_msg);
        $admin_notif_stmt->execute();
        
        $conn->commit();
        $message = "<div style='background:#10b981; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold; text-align:center;'>✅ Booking Request Sent! Our staff will review your schedule for: $service_type.</div>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div style='background:#ef4444; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold; text-align:center;'>❌ Error processing booking: " . $e->getMessage() . "</div>";
    }
}

// 3. Fetch Notifications if the user is logged in
$notifications = [];
$unread_count = 0;

if (isset($_SESSION['customer_id'])) {
    $cid = $_SESSION['customer_id'];
    
    // Handle marking notifications as read
    if (isset($_GET['read_notif'])) {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE customer_id = $cid");
        header("Location: booking.php");
        exit();
    }

    // Fetch Notifications
    $notif_result = $conn->query("SELECT * FROM notifications WHERE customer_id = $cid ORDER BY created_at DESC LIMIT 5");
    if ($notif_result) {
        while($n = $notif_result->fetch_assoc()) {
            $notifications[] = $n;
            if ($n['is_read'] == 0) $unread_count++;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify Garage - Book a Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body class="store-body">

    <header class="store-header">
        <a href="home.php" class="store-logo">MOTIFY.</a>
        <nav class="store-nav-links">
            <a href="home.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="booking.php" style="color: #ef4444;">Services</a>
            <a href="shop.php">Wishlist ❤️</a>
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
                                <a href="booking.php?read_notif=true" style="font-size: 11px; color: #3b82f6; text-decoration: none;">Mark all as read</a>
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

    <div class="store-hero-banner" style="min-height: 30vh; display:flex; align-items:center; justify-content:center; padding: 40px 20px;">
        <div class="store-hero-content">
            <h1 style="font-size: 3rem; margin:0;">Expert <span style="color:#ef4444;">Maintenance & Tuning</span></h1>
            <p>Professional care for peak performance.</p>
        </div>
    </div>

    <div class="store-layout-container" style="padding-top: 40px; display: block;">
        
        <?php echo $message; ?>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid #374151; padding-bottom: 15px; margin-bottom: 25px;">
            <div>
                <h2 style="margin: 0; font-size: 24px; color: white;">Available Services</h2>
                <p style="margin: 5px 0 0 0; color: #9ca3af;">Select a service to schedule your appointment.</p>
            </div>
            <select style="padding: 10px; background: #111827; color: white; border: 1px solid #374151; border-radius: 6px;">
                <option>All Categories</option>
                <option>Maintenance</option>
                <option>Repair</option>
                <option>Electrical</option>
            </select>
        </div>

        <div class="service-grid">
            
            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Auxiliary Light Wiring & Installation</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Professional installation and safe relay wiring of aftermarket auxiliary lights for better night visibility.</p>
                <div class="service-meta">
                    <span>⏱️ 1.5 Hours</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱500.00</span> 
                </div>
                <button type="button" class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="openBookingModal('Auxiliary Light Wiring & Installation')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Basic PMS (Preventive Maintenance)</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Comprehensive check of vital components, tightening of bolts, and overall system inspection.</p>
                <div class="service-meta">
                    <span>⏱️ 1 Hour</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱300.00</span> 
                </div>
                <button type="button" class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="openBookingModal('Basic PMS (Preventive Maintenance)')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Brake Bleeding & Fluid Replacement</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Flushing old brake fluid, bleeding the lines of air, and refilling to restore optimal braking power.</p>
                <div class="service-meta">
                    <span>⏱️ 45 Minutes</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱200.00</span> 
                </div>
                <button type="button" class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="openBookingModal('Brake Bleeding & Fluid Replacement')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Chain Cleaning and Lubrication</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Deep cleaning of the drive chain to remove dirt and grime, followed by high-quality lubrication.</p>
                <div class="service-meta">
                    <span>⏱️ 30 Minutes</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱150.00</span> 
                </div>
                <button type="button" class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="openBookingModal('Chain Cleaning and Lubrication')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Change Oil & Gear Oil Service</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Draining old engine and gear oil, replacing filters, and refilling to keep your engine running smoothly.</p>
                <div class="service-meta">
                    <span>⏱️ 30 Minutes</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱100.00</span> 
                </div>
                <button type="button" class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="openBookingModal('Change Oil & Gear Oil Service')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">CVT Cleaning (For Scooters)</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Disassembly, deep cleaning, and inspection of CVT components for optimal scooter acceleration.</p>
                <div class="service-meta">
                    <span>⏱️ 1 Hour</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱250.00</span> 
                </div>
                <button type="button" class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="openBookingModal('CVT Cleaning (For Scooters)')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Tire Mounting & Alignment</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Professional removal of old tires, mounting of new ones, and proper wheel alignment.</p>
                <div class="service-meta">
                    <span>⏱️ 45 Minutes</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱150.00</span> 
                </div>
                <button type="button" class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="openBookingModal('Tire Mounting & Alignment')">Book Appointment 📅</button>
            </div>

        </div>
    </div>

    <div id="bookingModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-btn" onclick="closeBookingModal()">&times;</span>
            <h2 style="color: white; margin-top: 0; border-bottom: 1px solid #374151; padding-bottom: 15px;">📅 Schedule Service</h2>
            
            <form method="POST" action="">
                
                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Selected Service</label>
                <input type="text" id="modalServiceType" name="service_type" style="width:100%; padding:12px; margin-bottom:20px; border-radius:6px; border: 1px solid #374151; background:#111827; color:#10b981; font-weight:bold; box-sizing:border-box;" readonly>

                <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Preferred Date *</label>
                        <input type="date" name="booking_date" id="bookingDate" style="width:100%; padding:12px; border-radius:6px; border: 1px solid #374151; background:#111827; color:white; box-sizing:border-box;" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Preferred Time *</label>
                        <input type="time" name="booking_time" style="width:100%; padding:12px; border-radius:6px; border: 1px solid #374151; background:#111827; color:white; box-sizing:border-box;" required>
                    </div>
                </div>

                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Vehicle Make & Model *</label>
                <input type="text" name="vehicle_model" placeholder="e.g. Yamaha NMAX 155 v2" style="width:100%; padding:12px; margin-bottom:20px; border-radius:6px; border: 1px solid #374151; background:#111827; color:white; box-sizing:border-box;" required>

                <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Issue Notes (Optional)</label>
                <textarea name="notes" placeholder="Describe any specific issues..." style="width:100%; padding:12px; margin-bottom:20px; border-radius:6px; border: 1px solid #374151; background:#111827; color:white; box-sizing:border-box; height: 80px; resize: none;"></textarea>

                <?php if(isset($_SESSION['customer_id'])): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 6px; border: 1px solid #10b981; margin-bottom: 20px;">
                        <div style="color: #10b981; font-size: 12px; font-weight: bold;">✓ Logged In As: <?php echo htmlspecialchars($_SESSION['customer_name']); ?></div>
                    </div>
                <?php else: ?>
                    <div style="border-top: 1px dashed #374151; padding-top: 15px; margin-bottom: 20px;">
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Full Name *</label>
                        <input type="text" name="customer_name" placeholder="John Doe" style="width:100%; padding:12px; margin-bottom:15px; border-radius:6px; border: 1px solid #374151; background:#111827; color:white; box-sizing:border-box;" required>
                        
                        <label style="color: #9ca3af; font-size: 13px; margin-bottom: 8px; display: block; font-weight: bold;">Phone Number *</label>
                        <input type="text" name="customer_phone" placeholder="09123456789" style="width:100%; padding:12px; border-radius:6px; border: 1px solid #374151; background:#111827; color:white; box-sizing:border-box;" required>
                    </div>
                <?php endif; ?>

                <button type="submit" name="submit_booking" class="btn-generate" style="width: 100%; justify-content: center; padding: 15px; font-size: 16px;">Confirm Booking 🔧</button>
            </form>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>

</body>
</html>