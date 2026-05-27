<?php
session_start();
require_once 'conn.php';

// Check if user is logged in and is ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

// Revenue Report: Total amount from PAYMENTS grouped by Month
$revenue_query = "SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    DATE_FORMAT(payment_date, '%M %Y') as month_name,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_revenue
                  FROM payments 
                  WHERE payment_status = 'Paid'
                  GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";
$revenue_result = mysqli_query($conn, $revenue_query);

// Car Popularity: Which car_id appears most in RESERVATIONS
$popularity_query = "SELECT 
                       c.car_id,
                       c.brand,
                       c.model,
                       COUNT(r.reservation_id) as reservation_count,
                       SUM(r.total_price) as total_revenue
                     FROM cars c
                     LEFT JOIN reservation r ON c.car_id = r.car_id
                     GROUP BY c.car_id, c.brand, c.model
                     ORDER BY reservation_count DESC, total_revenue DESC
                     LIMIT 20";
$popularity_result = mysqli_query($conn, $popularity_query);

// Handle report generation
$report_data = [];
$report_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_type = $_POST['report_type'] ?? '';
    
    if ($report_type == 'reservations_period') {
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if ($start_date && $end_date) {
            $query = "SELECT r.reservation_id, r.start_date, r.end_date, r.status, r.total_price,
                             c.brand, c.model, c.plate_id, c.car_type, c.color, c.year,
                             u.full_name, u.email, u.phone, u.address, u.city, u.country,
                             po.office_name as pickup_office, ro.office_name as return_office
                      FROM reservation r
                      JOIN cars c ON r.car_id = c.car_id
                      JOIN users u ON r.customer_id = u.user_id
                      JOIN offices po ON r.pickup_office_id = po.office_id
                      JOIN offices ro ON r.return_office_id = ro.office_id
                      WHERE DATE(r.created_at) BETWEEN ? AND ?
                      ORDER BY r.created_at DESC";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($report_type == 'car_status_day') {
        $report_date = $_POST['report_date'] ?? '';
        
        if ($report_date) {
            $query = "SELECT c.car_id, c.plate_id, c.brand, c.model, c.car_type, c.color,
                             cs.status_name, c.daily_price, o.office_name, o.city,
                             COALESCE(csh.status_date, c.created_at) as last_status_change
                      FROM cars c
                      JOIN car_status cs ON c.current_status_id = cs.status_id
                      LEFT JOIN offices o ON c.office_id = o.office_id
                      LEFT JOIN car_status_history csh ON c.car_id = csh.car_id 
                        AND csh.status_date = (
                            SELECT MAX(status_date) 
                            FROM car_status_history 
                            WHERE car_id = c.car_id AND DATE(status_date) <= ?
                        )
                      WHERE DATE(COALESCE(csh.status_date, c.created_at)) <= ?
                      ORDER BY c.car_id";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $report_date, $report_date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($report_type == 'customer_reservations') {
        $customer_id = intval($_POST['customer_id'] ?? 0);
        
        if ($customer_id > 0) {
            $query = "SELECT r.reservation_id, r.start_date, r.end_date, r.status, r.total_price,
                             c.brand, c.model, c.plate_id,
                             u.full_name, u.email, u.phone, u.address, u.city, u.country
                      FROM reservation r
                      JOIN cars c ON r.car_id = c.car_id
                      JOIN users u ON r.customer_id = u.user_id
                      WHERE r.customer_id = ?
                      ORDER BY r.created_at DESC";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($report_type == 'payments_period') {
        $start_date = $_POST['payment_start_date'] ?? '';
        $end_date = $_POST['payment_end_date'] ?? '';
        
        if ($start_date && $end_date) {
            $query = "SELECT p.payment_id, p.amount, p.payment_date, p.payment_status,
                             r.reservation_id, r.start_date, r.end_date,
                             c.brand, c.model, c.plate_id,
                             u.full_name as customer_name, u.email,
                             pm.method_name as payment_method
                      FROM payments p
                      JOIN reservation r ON p.reservation_id = r.reservation_id
                      JOIN cars c ON r.car_id = c.car_id
                      JOIN users u ON p.customer_id = u.user_id
                      JOIN payment_methods pm ON p.payment_method_id = pm.method_id
                      WHERE DATE(p.payment_date) BETWEEN ? AND ?
                      ORDER BY p.payment_date DESC";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get customers for dropdown
$customers_query = "SELECT u.user_id, u.full_name, u.email FROM users u JOIN customer_details cd ON u.user_id = cd.customer_id ORDER BY u.full_name";
$customers_result = mysqli_query($conn, $customers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Reports - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #e0e0e0;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1f1f1f 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(150, 150, 150, 0.2);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #e0e0e0;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
            align-items: center;
        }

        .nav-menu a {
            text-decoration: none;
            color: #b0bec5;
            font-weight: 500;
            transition: all 0.3s;
        }

        .nav-menu a:hover {
            color: #e0e0e0;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 36px;
            color: #e0e0e0;
            margin-bottom: 10px;
        }

        .page-header a {
            color: #b0bec5;
            text-decoration: none;
            transition: all 0.3s;
        }

        .page-header a:hover {
            color: #e0e0e0;
        }

        .reports-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .report-section {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        .report-section h2 {
            color: #e0e0e0;
            margin-bottom: 20px;
            font-size: 28px;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
            padding-bottom: 10px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .report-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .report-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
            color: #b0bec5;
        }

        .report-table tr:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .highlight {
            color: #e0e0e0;
            font-weight: bold;
        }

        .revenue-total {
            background: rgba(76, 175, 80, 0.1);
            border-left: 4px solid #4caf50;
        }

        .popularity-rank {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #f5f5f5 0%, #d0d0d0 100%);
            color: #1a1a1a;
            font-weight: bold;
            margin-right: 10px;
        }

        .rank-1 {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        }

        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
        }

        .rank-3 {
            background: linear-gradient(135deg, #cd7f32 0%, #e6a857 100%);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #90a4ae;
        }

        .print-actions {
            margin-bottom: 20px;
            text-align: right;
        }

        .btn-submit {
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            color: #0a0a0a;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #00cc6a 0%, #00aa55 100%);
            transform: translateY(-1px);
        }
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-print:hover {
            background: #ffffff;
        }

        @media print {
            .navbar, .nav-menu, .page-header a, .print-actions, .report-forms {
                display: none !important;
            }
            body {
                background: white !important;
                color: #000 !important;
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: none;
                margin: 0;
                padding: 0;
            }
            .report-section {
                page-break-inside: avoid;
                margin-bottom: 30px;
                border: 1px solid #000;
                padding: 20px;
            }
            .report-section h2 {
                color: #000;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
                margin-bottom: 20px;
                font-size: 18pt;
                text-align: center;
            }
            .report-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            .report-table th,
            .report-table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
                font-size: 10pt;
            }
            .report-table th {
                background: #f0f0f0;
                font-weight: bold;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .highlight {
                font-weight: bold;
            }
            .revenue-total {
                border-top: 2px solid #000;
                font-weight: bold;
            }
            .rank-1, .rank-2, .rank-3 {
                background: white !important;
                color: #000 !important;
            }
            @page {
                margin: 0.5in;
                size: A4;
            }
            .print-header {
                display: block !important;
                text-align: center;
                border-bottom: 1px solid #000;
                padding-bottom: 10px;
                margin-bottom: 20px;
                font-size: 12pt;
            }
            .print-footer {
                display: block !important;
                text-align: center;
                border-top: 1px solid #000;
                padding-top: 10px;
                margin-top: 20px;
                font-size: 10pt;
                color: #666;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="logo">
                <img src="uploads/logo/logo.png" alt="DRIVETuple Logo" style="height: 40px; width: auto; margin-right: 10px;" onerror="this.style.display='none'">
                <span>DRIVETuple - Admin</span>
            </a>
            <ul class="nav-menu">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="index.php">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="print-header" style="display: none;">
            <h1>DRIVETuple Car Rental System - Reports</h1>
            <p>Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
        </div>

        <div class="page-header">
            <h1>📊 Advanced Reports</h1>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>

        <div class="print-actions">
            <button onclick="window.print()" class="btn-print">🖨️ Print Reports</button>
        </div>

        <!-- Report Generation Forms -->
        <div class="report-forms" style="margin-bottom: 30px;">
            <div class="report-section">
                <h2>Generate Custom Reports</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    
                    <!-- Reservations by Period -->
                    <div class="form-group" style="background: rgba(40, 40, 40, 0.5); padding: 20px; border-radius: 10px;">
                        <h3 style="color: #e0e0e0; margin-bottom: 15px;">📅 Reservations by Period</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="report_type" value="reservations_period">
                            <div style="margin-bottom: 10px;">
                                <label style="color: #b0bec5; display: block; margin-bottom: 5px;">Start Date:</label>
                                <input type="date" name="start_date" required style="width: 100%; padding: 8px; border: 1px solid #555; border-radius: 5px; background: rgba(0,0,0,0.3); color: #e0e0e0;">
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="color: #b0bec5; display: block; margin-bottom: 5px;">End Date:</label>
                                <input type="date" name="end_date" required style="width: 100%; padding: 8px; border: 1px solid #555; border-radius: 5px; background: rgba(0,0,0,0.3); color: #e0e0e0;">
                            </div>
                            <button type="submit" class="btn-submit" style="width: 100%;">Generate Report</button>
                        </form>
                    </div>

                    <!-- Car Status on Date -->
                    <div class="form-group" style="background: rgba(40, 40, 40, 0.5); padding: 20px; border-radius: 10px;">
                        <h3 style="color: #e0e0e0; margin-bottom: 15px;">🚗 Car Status on Specific Day</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="report_type" value="car_status_day">
                            <div style="margin-bottom: 15px;">
                                <label style="color: #b0bec5; display: block; margin-bottom: 5px;">Report Date:</label>
                                <input type="date" name="report_date" required style="width: 100%; padding: 8px; border: 1px solid #555; border-radius: 5px; background: rgba(0,0,0,0.3); color: #e0e0e0;">
                            </div>
                            <button type="submit" class="btn-submit" style="width: 100%;">Generate Report</button>
                        </form>
                    </div>

                    <!-- Customer Reservations -->
                    <div class="form-group" style="background: rgba(40, 40, 40, 0.5); padding: 20px; border-radius: 10px;">
                        <h3 style="color: #e0e0e0; margin-bottom: 15px;">👤 Customer Reservations</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="report_type" value="customer_reservations">
                            <div style="margin-bottom: 15px;">
                                <label style="color: #b0bec5; display: block; margin-bottom: 5px;">Select Customer:</label>
                                <select name="customer_id" required style="width: 100%; padding: 8px; border: 1px solid #555; border-radius: 5px; background: rgba(0,0,0,0.3); color: #e0e0e0;">
                                    <option value="">Choose Customer</option>
                                    <?php 
                                    mysqli_data_seek($customers_result, 0);
                                    while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                                        <option value="<?php echo $customer['user_id']; ?>">
                                            <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['email'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-submit" style="width: 100%;">Generate Report</button>
                        </form>
                    </div>

                    <!-- Payments by Period -->
                    <div class="form-group" style="background: rgba(40, 40, 40, 0.5); padding: 20px; border-radius: 10px;">
                        <h3 style="color: #e0e0e0; margin-bottom: 15px;">💰 Daily Payments by Period</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="report_type" value="payments_period">
                            <div style="margin-bottom: 10px;">
                                <label style="color: #b0bec5; display: block; margin-bottom: 5px;">Start Date:</label>
                                <input type="date" name="payment_start_date" required style="width: 100%; padding: 8px; border: 1px solid #555; border-radius: 5px; background: rgba(0,0,0,0.3); color: #e0e0e0;">
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="color: #b0bec5; display: block; margin-bottom: 5px;">End Date:</label>
                                <input type="date" name="payment_end_date" required style="width: 100%; padding: 8px; border: 1px solid #555; border-radius: 5px; background: rgba(0,0,0,0.3); color: #e0e0e0;">
                            </div>
                            <button type="submit" class="btn-submit" style="width: 100%;">Generate Report</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generated Report Display -->
        <?php if (!empty($report_data)): ?>
        <div class="report-section">
            <h2><?php 
                if ($report_type == 'reservations_period') echo '📅 Reservations Report';
                elseif ($report_type == 'car_status_day') echo '🚗 Car Status Report';
                elseif ($report_type == 'customer_reservations') echo '👤 Customer Reservations Report';
                elseif ($report_type == 'payments_period') echo '💰 Payments Report';
            ?></h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <?php if ($report_type == 'reservations_period'): ?>
                            <th>Reservation ID</th>
                            <th>Customer</th>
                            <th>Car Details</th>
                            <th>Pickup/Return</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Total</th>
                        <?php elseif ($report_type == 'car_status_day'): ?>
                            <th>Car ID</th>
                            <th>Plate ID</th>
                            <th>Brand & Model</th>
                            <th>Status</th>
                            <th>Office</th>
                            <th>Daily Price</th>
                        <?php elseif ($report_type == 'customer_reservations'): ?>
                            <th>Reservation ID</th>
                            <th>Car (Plate/Model)</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Total</th>
                        <?php elseif ($report_type == 'payments_period'): ?>
                            <th>Payment ID</th>
                            <th>Customer</th>
                            <th>Car Details</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $row): ?>
                        <tr>
                            <?php if ($report_type == 'reservations_period'): ?>
                                <td><?php echo $row['reservation_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?><br><small><?php echo htmlspecialchars($row['email']); ?></small></td>
                                <td><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?><br><small>Plate: <?php echo $row['plate_id']; ?>, Type: <?php echo $row['car_type']; ?></small></td>
                                <td>Pickup: <?php echo htmlspecialchars($row['pickup_office']); ?><br>Return: <?php echo htmlspecialchars($row['return_office']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?> -<br><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                                <td><?php echo $row['status']; ?></td>
                                <td class="text-right">$<?php echo number_format($row['total_price'], 2); ?></td>
                            <?php elseif ($report_type == 'car_status_day'): ?>
                                <td><?php echo $row['car_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['plate_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?><br><small>Type: <?php echo $row['car_type']; ?>, Color: <?php echo $row['color']; ?></small></td>
                                <td><?php echo $row['status_name']; ?></td>
                                <td><?php echo htmlspecialchars($row['office_name'] . ', ' . $row['city']); ?></td>
                                <td class="text-right">$<?php echo number_format($row['daily_price'], 2); ?></td>
                            <?php elseif ($report_type == 'customer_reservations'): ?>
                                <td><?php echo $row['reservation_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?><br><small>Plate: <?php echo $row['plate_id']; ?></small></td>
                                <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?> -<br><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                                <td><?php echo $row['status']; ?></td>
                                <td class="text-right">$<?php echo number_format($row['total_price'], 2); ?></td>
                            <?php elseif ($report_type == 'payments_period'): ?>
                                <td><?php echo $row['payment_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['customer_name']); ?><br><small><?php echo htmlspecialchars($row['email']); ?></small></td>
                                <td><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?><br><small>Plate: <?php echo $row['plate_id']; ?></small></td>
                                <td><?php echo $row['payment_method']; ?></td>
                                <td class="text-right">$<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($row['payment_date'])); ?></td>
                                <td><?php echo $row['payment_status']; ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="reports-grid">
            <!-- Revenue Report -->
            <div class="report-section">
                <h2>Revenue Report (Last 12 Months)</h2>
                <?php if (mysqli_num_rows($revenue_result) > 0): ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th class="text-center">Transactions</th>
                                <th class="text-right">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            $total_transactions = 0;
                            mysqli_data_seek($revenue_result, 0);
                            while ($row = mysqli_fetch_assoc($revenue_result)): 
                                $grand_total += $row['total_revenue'];
                                $total_transactions += $row['transaction_count'];
                            ?>
                                <tr>
                                    <td class="highlight"><?php echo htmlspecialchars($row['month_name']); ?></td>
                                    <td class="text-center"><?php echo number_format($row['transaction_count']); ?></td>
                                    <td class="text-right highlight">$<?php echo number_format($row['total_revenue'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <tr class="revenue-total">
                                <td class="highlight"><strong>Grand Total</strong></td>
                                <td class="text-center"><strong><?php echo number_format($total_transactions); ?></strong></td>
                                <td class="text-right highlight"><strong>$<?php echo number_format($grand_total, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No revenue data available.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Car Popularity Report -->
            <div class="report-section">
                <h2>Car Popularity Report</h2>
                <?php if (mysqli_num_rows($popularity_result) > 0): ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Car Model</th>
                                <th class="text-center">Reservations</th>
                                <th class="text-right">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            mysqli_data_seek($popularity_result, 0);
                            while ($row = mysqli_fetch_assoc($popularity_result)): 
                                $rank_class = '';
                                if ($rank == 1) $rank_class = 'rank-1';
                                elseif ($rank == 2) $rank_class = 'rank-2';
                                elseif ($rank == 3) $rank_class = 'rank-3';
                            ?>
                                <tr>
                                    <td>
                                        <span class="popularity-rank <?php echo $rank_class; ?>">
                                            <?php echo $rank; ?>
                                        </span>
                                    </td>
                                    <td class="highlight">
                                        <?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?>
                                        <br>
                                        <small style="color: #90a4ae;">Car ID: <?php echo $row['car_id']; ?></small>
                                    </td>
                                    <td class="text-center highlight"><?php echo number_format($row['reservation_count']); ?></td>
                                    <td class="text-right highlight">$<?php echo number_format($row['total_revenue'] ?? 0, 2); ?></td>
                                </tr>
                            <?php 
                                $rank++;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No car popularity data available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="print-footer" style="display: none;">
            <p>DRIVETuple Car Rental System - Confidential Report</p>
            <p>Page 1 of 1</p>
        </div>
    </div>
</body>
</html>

