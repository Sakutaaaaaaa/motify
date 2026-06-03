<?php
// reports.php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: sales.php");
    exit();
}

$report_html = "";
$show_chart = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_report'])) {
    
    // Trigger the Python Microservice
    $command = escapeshellcmd('python report_engine.py');
    $output = shell_exec($command);
    
    // Decode the JSON data sent back from Python
    $data = json_decode($output, true);
    
    // Format the UI based on Python's response
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
        $error_msg = isset($data['message']) ? $data['message'] : "Python script failed to execute. Check your CMD environment.";
        $report_html = "
            <div style='background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 20px; border-radius: 12px; margin-top: 30px;'>
                <h3 style='color: #ef4444; margin-top: 0;'>❌ Microservice Error</h3>
                <p style='color: #f3f4f6;'>$error_msg</p>
                <p style='color: #9ca3af; font-size: 14px;'>Ensure 'report_engine.py' is in the same directory and Python is added to your Windows PATH.</p>
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
                <h2 style="margin: 0 0 10px 0; color: #f3f4f6;">Python Report Engine</h2>
                <p style="margin: 0; color: #9ca3af;">Compile the latest sales data using the backend microservice.</p>
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