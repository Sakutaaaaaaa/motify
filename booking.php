<!DOCTYPE html>
<html>
<head>
    <title>Motify Garage - Book a Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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
        <div><a href="index.php" style="color:#9ca3af; text-decoration:none; font-size:14px; font-weight:bold; border: 1px solid #374151; padding: 8px 15px; border-radius: 6px;">Staff Login 🔒</a></div>
    </header>

    <div class="store-hero-banner" style="min-height: 30vh; display:flex; align-items:center; justify-content:center; padding: 40px 20px;">
        <div class="store-hero-content">
            <h1 style="font-size: 3rem; margin:0;">Expert <span style="color:#ef4444;">Maintenance & Tuning</span></h1>
            <p>Professional care for peak performance.</p>
        </div>
    </div>

    <div class="store-layout-container" style="padding-top: 40px; display: block;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid #374151; padding-bottom: 15px;">
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
                <button class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="alert('Phase 3 Feature: Booking Modal will open here allowing date/time selection!')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Basic PMS (Preventive Maintenance)</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Comprehensive check of vital components, tightening of bolts, and overall system inspection.</p>
                <div class="service-meta">
                    <span>⏱️ 1 Hour</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱300.00</span> 
                </div>
                <button class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="alert('Phase 3 Feature: Booking Modal will open here allowing date/time selection!')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Brake Bleeding & Fluid Replacement</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Flushing old brake fluid, bleeding the lines of air, and refilling to restore optimal braking power.</p>
                <div class="service-meta">
                    <span>⏱️ 45 Minutes</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱200.00</span> 
                </div>
                <button class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="alert('Phase 3 Feature: Booking Modal will open here allowing date/time selection!')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Chain Cleaning and Lubrication</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Deep cleaning of the drive chain to remove dirt and grime, followed by high-quality lubrication.</p>
                <div class="service-meta">
                    <span>⏱️ 30 Minutes</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱150.00</span> 
                </div>
                <button class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="alert('Phase 3 Feature: Booking Modal will open here allowing date/time selection!')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Change Oil & Gear Oil Service</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Draining old engine and gear oil, replacing filters, and refilling to keep your engine running smoothly.</p>
                <div class="service-meta">
                    <span>⏱️ 30 Minutes</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱100.00</span> 
                </div>
                <button class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="alert('Phase 3 Feature: Booking Modal will open here allowing date/time selection!')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">CVT Cleaning (For Scooters)</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Disassembly, deep cleaning, and inspection of CVT components for optimal scooter acceleration.</p>
                <div class="service-meta">
                    <span>⏱️ 1 Hour</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱250.00</span> 
                </div>
                <button class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="alert('Phase 3 Feature: Booking Modal will open here allowing date/time selection!')">Book Appointment 📅</button>
            </div>

            <div class="service-card">
                <h3 style="margin: 0 0 10px 0; color: white; font-size: 18px;">Tire Mounting & Alignment</h3>
                <p style="color: #9ca3af; font-size: 13px; margin: 0 0 15px 0;">Professional removal of old tires, mounting of new ones, and proper wheel alignment.</p>
                <div class="service-meta">
                    <span>⏱️ 45 Minutes</span>
                    <span style="color: #10b981; font-weight: bold;">Est. ₱150.00</span> 
                </div>
                <button class="btn-generate" style="width: 100%; justify-content: center; margin-top: 10px;" onclick="alert('Phase 3 Feature: Booking Modal will open here allowing date/time selection!')">Book Appointment 📅</button>
            </div>

        </div>
    </div>

</body>
</html>