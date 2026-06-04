<?php
// dashboard.php - ADMIN COMMAND CENTER
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 1. Total Revenue
$rev_q = $conn->query("SELECT SUM(total_amount) as total FROM sales");
$revenue = $rev_q->fetch_assoc()['total'] ?? 0;

// 2. Overall Store Rating
$rate_q = $conn->query("SELECT SUM(rating_sum) as s_sum, SUM(rating_count) as s_count FROM products");
$rate_data = $rate_q->fetch_assoc();
$avg_rating = ($rate_data['s_count'] > 0) ? round($rate_data['s_sum'] / $rate_data['s_count'], 1) : 0;
$total_reviews = $rate_data['s_count'] ?? 0;

// 3. Low Stock Alerts
$stock_q = $conn->query("SELECT COUNT(*) as low FROM inventory WHERE stock_quantity <= 5 AND stock_quantity > 0");
$low_stock = $stock_q->fetch_assoc()['low'] ?? 0;

// 4. Total Customers
$cust_q = $conn->query("SELECT COUNT(*) as c_count FROM customers");
$customers = $cust_q->fetch_assoc()['c_count'] ?? 0;

// 5. Best Selling Parts (Top 5)
$best_sellers = $conn->query("
    SELECT p.product_name, SUM(s.quantity) as qty_sold, SUM(s.total_amount) as rev_gen
    FROM sales s
    JOIN products p ON s.product_id = p.product_id
    GROUP BY p.product_id
    ORDER BY qty_sold DESC
    LIMIT 5
");

// 6. Highest Rated Parts (Top 5)
$top_rated = $conn->query("
    SELECT product_name, rating_sum, rating_count, (rating_sum / rating_count) as avg_score 
    FROM products 
    WHERE rating_count > 0 
    ORDER BY avg_score DESC, rating_count DESC 
    LIMIT 5
");
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

        <div style="display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px; background: #1f2937; padding: 25px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Total Revenue</div>
                <div style="color: white; font-size: 28px; font-weight: 900;">₱<?php echo number_format($revenue, 2); ?></div>
            </div>
            
            <div style="flex: 1; min-width: 200px; background: #1f2937; padding: 25px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Overall Rating</div>
                <div style="display: flex; align-items: baseline; gap: 10px;">
                    <div style="color: #f59e0b; font-size: 28px; font-weight: 900;"><?php echo number_format($avg_rating, 1); ?> ⭐</div>
                    <div style="color: #9ca3af; font-size: 12px;">(<?php echo $total_reviews; ?> Reviews)</div>
                </div>
            </div>

            <div style="flex: 1; min-width: 200px; background: #1f2937; padding: 25px; border-radius: 12px; border: 1px solid #ef4444; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Low Stock Alerts</div>
                <div style="color: #ef4444; font-size: 28px; font-weight: 900;"><?php echo $low_stock; ?> <span style="font-size: 16px;">⚠️</span></div>
            </div>

            <div style="flex: 1; min-width: 200px; background: #1f2937; padding: 25px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px;">Total Customers</div>
                <div style="color: white; font-size: 28px; font-weight: 900;"><?php echo $customers; ?></div>
            </div>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            
            <div style="flex: 2; min-width: 500px; background: #1f2937; padding: 25px; border-radius: 12px; border: 1px solid #374151; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <div style="color: #9ca3af; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px;">Sales Analytics</div>
                <canvas id="salesChart" style="max-height: 400px;"></canvas>
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