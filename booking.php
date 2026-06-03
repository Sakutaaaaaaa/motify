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
        
        $conn->commit();
        $message = "<div style='background:#10b981; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold; text-align:center;'>✅ Booking Request Sent! Our staff will review your schedule for: $service_type.</div>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div style='background:#ef4444; color:white; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold; text-align:center;'>❌ Error processing booking: " . $e->getMessage() . "</div>";
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
        <div>
            <?php if(isset($_SESSION['customer_id'])): ?>
                <a href="account.php?action=logout" style="color:#ef4444; text-decoration:none; font-size:14px; font-weight:bold; border: 1px solid #ef4444; padding: 8px 15px; border-radius: 6px;">Sign Out 🚪</a>
            <?php else: ?>
                <a href="index.php" style="color:#9ca3af; text-decoration:none; font-size:14px; font-weight:bold; border: 1px solid #374151; padding: 8px 15px; border-radius: 6px;">Staff Login 🔒</a>
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