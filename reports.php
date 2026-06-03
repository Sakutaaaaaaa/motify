<?php
// reports.php
session_start();
require_once 'db_connection.php';

// Gatekeeper: Only Admins allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: sales.php");
    exit();
}

$report_html = "";
$show_chart = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_report'])) {
    
    // --- NATIVE PHP DATA EXTRACTION (Replacing Python) ---
    try {
        $current_month = date('Y-m'); // Format: 2026-06
        $display_month = date('F Y'); // Format: June 2026
        
        // Target the actual `sales` table and columns
        $query = "SELECT COUNT(sales_id) as total_transactions, SUM(total_amount) as total_revenue 
                  FROM sales 
                  WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $current_month);
        $stmt->execute();
        $result = $stmt->get_result();
        $db_data = $result->fetch_assoc();
        
        // Mimic the exact JSON structure the old Python script used
        $data = [
            'status' => 'success',
            'month' => $display_month,
            'total_transactions' => $db_data['total_transactions'] ? $db_data['total_transactions'] : 0,
            'total_revenue' => $db_data['total_revenue'] ? $db_data['total_revenue'] : 0.00
        ];
        
    } catch (Exception $e) {
        $data = [
            'status' => 'error',
            'message' => "Database extraction failed: " . $e->getMessage()
        ];
    }
    
    // --- FORMAT THE UI ---
    if ($data && isset($data['status']) && $data['status'] == 'success') {
        $show_chart = true; 
        
        $revenue = number_format($data['total_revenue'], 2);
        $month = htmlspecialchars($data['month']);
        $transactions = htmlspecialchars($data['total_transactions']);
        
        $report_html = "
            <div class='dashboard-cards' style='margin-top: 30px;'>
                <div class='card' style='border-color: #10b981;'>
                    <h3>Target Month</h3>
                    <div class='value'>$month</div>
                </div>
                <div class='card' style='border-color: #3b82f6;'>
                    <h3>Total Transactions</h3>
                    <div class='value'>$transactions</div>
                </div>
                <div class='card' style='border-color: #ef4444;'>
                    <h3>Gross Revenue</h3>
                    <div class='value' style='color: #10b981;'>₱ $revenue</div>
                </div>
            </div>
        ";
    } else {
        $error_msg = isset($data['message']) ? $data['message'] : "Data engine failed to compile.";
        $report_html = "
            <div style='background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 20px; border-radius: 12px; margin-top: 30px;'>
                <h3 style='color: #ef4444; margin-top: 0;'>❌ Extraction Error</h3>
                <p style='color: #f3f4f6;'>$error_msg</p>
                <p style='color: #9ca3af; font-size: 14px;'>Check your database table names and column structures.</p>
            </div>
        ";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Motify - Data Analytics</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 style="margin-top: 0;">📊 Financial Analytics</h1>
        
        <div class="report-header">
            <div>
                <h2 style="margin: 0 0 10px 0; color: #f3f4f6;">Native Data Engine</h2>
                <p style="margin: 0; color: #9ca3af;">Compile the latest sales data securely from the MySQL database.</p>
            </div>
            <form method="POST" action="">
                <button type="submit" name="generate_report" class="btn-generate">
                    ⚙️ Run Data Extraction
                </button>
            </form>
        </div>

        <?php echo $report_html; ?>

        <?php if ($show_chart): ?>
            <div class="card" style="margin-top: 30px;">
                <h3 style="margin-bottom: 20px;">Revenue vs. Target Projection</h3>
                <canvas id="reportChart" height="100" data-revenue="<?php echo $data['total_revenue']; ?>"></canvas>
            </div>
        <?php endif; ?>

    </div>

    <script src="script.js"></script>

</body>
</html>