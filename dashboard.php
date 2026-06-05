<?php
// dashboard.php - ADMIN COMMAND CENTER
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// === 🚀 SILENT DATABASE AUTO-REPAIR SCRIPT 🚀 ===
$conn->query("ALTER TABLE service_bookings MODIFY COLUMN booking_status VARCHAR(50) DEFAULT 'Pending'");
$check_rating_col = $conn->query("SHOW COLUMNS FROM service_bookings LIKE 'service_rating'");
if ($check_rating_col && $check_rating_col->num_rows == 0) {
    $conn->query("ALTER TABLE service_bookings ADD COLUMN service_rating INT DEFAULT 0");
}
// ================================================

// 1. Total Revenue
$rev_q = $conn->query("SELECT SUM(total_amount) as total FROM sales");
$revenue = $rev_q->fetch_assoc()['total'] ?? 0;

// 2. Overall Product Rating (Strictly Physical Parts)
$rate_q = $conn->query("SELECT SUM(rating_sum) as s_sum, SUM(rating_count) as s_count FROM products WHERE category NOT IN ('Service', 'Services')");
$rate_data = $rate_q->fetch_assoc();
$p_sum = $rate_data['s_sum'] ?? 0;
$p_count = $rate_data['s_count'] ?? 0;
$avg_rating = ($p_count > 0) ? round($p_sum / $p_count, 1) : 0;
$total_reviews = $p_count;

// 2b. Overall Service Rating (THE FIX: Now correctly reads from the service_bookings table!)
$srv_rate_q = $conn->query("SELECT SUM(service_rating) as s_sum, COUNT(service_rating) as s_count FROM service_bookings WHERE service_rating > 0");
$srv_rate_data = $srv_rate_q->fetch_assoc();
$s_sum = $srv_rate_data['s_sum'] ?? 0;
$s_count = $srv_rate_data['s_count'] ?? 0;
$srv_avg_rating = ($s_count > 0) ? round($s_sum / $s_count, 1) : 0;
$srv_total_reviews = $s_count;

// 3. Low Stock Alerts (Excludes Services)
$stock_q = $conn->query("
    SELECT COUNT(*) as low 
    FROM inventory i 
    JOIN products p ON i.product_id = p.product_id 
    WHERE i.stock_quantity <= 5 
    AND p.category NOT IN ('Service', 'Services')
");
$low_stock = $stock_q->fetch_assoc()['low'] ?? 0;

// 4. Total Customers
$cust_q = $conn->query("SELECT COUNT(*) as c_count FROM customers");
$customers = $cust_q->fetch_assoc()['c_count'] ?? 0;

// 5. Best Selling Parts (Top 5)
$best_sellers = $conn->query("
    SELECT p.product_name, SUM(s.quantity) as qty_sold, SUM(s.total_amount) as rev_gen
    FROM sales s
    JOIN products p ON s.product_id = p.product_id
    WHERE p.category NOT IN ('Service', 'Services')
    GROUP BY p.product_id
    ORDER BY qty_sold DESC
    LIMIT 5
");

// 6. Highest Rated Parts (Top 5 - Filters out services)
$top_rated = $conn->query("
    SELECT product_name, rating_sum, rating_count, (rating_sum / rating_count) as avg_score 
    FROM products 
    WHERE rating_count > 0 AND category NOT IN ('Service', 'Services')
    ORDER BY avg_score DESC, rating_count DESC 
    LIMIT 5
");

// 7. Dynamic Monthly Chart Data (Jan - Dec of Current Year)
$chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$chart_data = array_fill(0, 12, 0); 

$current_year = date('Y');
$monthly_q = $conn->query("
    SELECT MONTH(transaction_date) as m, SUM(total_amount) as monthly_total 
    FROM sales 
    WHERE YEAR(transaction_date) = '$current_year' 
    GROUP BY MONTH(transaction_date)
");

if ($monthly_q && $monthly_q->num_rows > 0) {
    while ($row = $monthly_q->fetch_assoc()) {
        $month_index = (int)$row['m'] - 1; 
        $chart_data[$month_index] = (float)$row['monthly_total'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Garage Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-top: 0; margin-bottom: 5px;">Garage Command Center</h1>
        <p style="color: #9ca3af; margin-bottom: 25px;">Welcome back, Admin. Here is your store's performance.</p>

        <div style="display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 180px; background: #1f2937; padding: 20px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Total Revenue</div>
                <div style="color: white; font-size: 26px; font-weight: 900;">₱<?php echo number_format($revenue, 2); ?></div>
            </div>
            
            <div style="flex: 1; min-width: 180px; background: #1f2937; padding: 20px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Product Ratings</div>
                <div style="display: flex; align-items: baseline; gap: 8px;">
                    <div style="color: #f59e0b; font-size: 26px; font-weight: 900;"><?php echo number_format($avg_rating, 1); ?> ⭐</div>
                    <div style="color: #9ca3af; font-size: 11px;">(<?php echo $total_reviews; ?>)</div>
                </div>
            </div>

            <div style="flex: 1; min-width: 180px; background: #1f2937; padding: 20px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Service Ratings</div>
                <div style="display: flex; align-items: baseline; gap: 8px;">
                    <div style="color: #3b82f6; font-size: 26px; font-weight: 900;"><?php echo number_format($srv_avg_rating, 1); ?> ⭐</div>
                    <div style="color: #9ca3af; font-size: 11px;">(<?php echo $srv_total_reviews; ?>)</div>
                </div>
            </div>

            <?php 
                $stock_border = ($low_stock > 0) ? '#ef4444' : '#374151';
                $stock_text = ($low_stock > 0) ? '#ef4444' : '#10b981';
                $stock_icon = ($low_stock > 0) ? '⚠️' : '✅';
            ?>
            <div style="flex: 1; min-width: 180px; background: #1f2937; padding: 20px; border-radius: 12px; border: 1px solid <?php echo $stock_border; ?>; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Low Stock Alerts</div>
                <div style="color: <?php echo $stock_text; ?>; font-size: 26px; font-weight: 900;"><?php echo $low_stock; ?> <span style="font-size: 14px;"><?php echo $stock_icon; ?></span></div>
            </div>

            <div style="flex: 1; min-width: 180px; background: #1f2937; padding: 20px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Total Customers</div>
                <div style="color: white; font-size: 26px; font-weight: 900;"><?php echo $customers; ?></div>
            </div>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <div style="flex: 2; min-width: 500px; background: #1f2937; padding: 25px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px;">Sales Analytics (<?php echo $current_year; ?>)</div>
                <canvas id="salesChart" 
                        data-labels='<?php echo json_encode($chart_labels); ?>' 
                        data-values='<?php echo json_encode($chart_data); ?>'
                        style="max-height: 400px;"></canvas>
            </div>

            <div style="flex: 1; min-width: 350px; display: flex; flex-direction: column; gap: 20px;">
                
                <div style="background: #1f2937; padding: 25px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                    <div style="color: white; font-size: 16px; font-weight: 900; border-bottom: 1px solid #374151; padding-bottom: 15px; margin-bottom: 15px;">🔥 Best Selling Parts</div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if($best_sellers && $best_sellers->num_rows > 0): ?>
                            <?php while($item = $best_sellers->fetch_assoc()): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="color: white; font-size: 14px; font-weight: bold;"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div style="color: #10b981; font-size: 13px; font-weight: 900; background: rgba(16, 185, 129, 0.1); padding: 4px 8px; border-radius: 4px;">
                                        <?php echo $item['qty_sold']; ?> Sold
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="color: #9ca3af; font-size: 13px;">No sales data yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="background: #1f2937; padding: 25px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                    <div style="color: white; font-size: 16px; font-weight: 900; border-bottom: 1px solid #374151; padding-bottom: 15px; margin-bottom: 15px;">⭐ Highest Rated Parts</div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if($top_rated && $top_rated->num_rows > 0): ?>
                            <?php while($item = $top_rated->fetch_assoc()): 
                                $avg = round($item['avg_score'], 1);
                            ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="color: white; font-size: 14px; font-weight: bold;"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div style="color: #f59e0b; font-size: 13px; font-weight: 900; background: rgba(245, 158, 11, 0.1); padding: 4px 8px; border-radius: 4px;">
                                        <?php echo number_format($avg, 1); ?> ⭐
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="color: #9ca3af; font-size: 13px;">No ratings yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>