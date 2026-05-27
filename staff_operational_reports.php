<?php
session_start();
require_once 'conn.php';

// Check if user is logged in and is STAFF or ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'STAFF' && $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: dashboard.php");
    exit();
}

// Get today's date
$today = date('Y-m-d');

// Fetch cars leaving today (start_date = today)
$leaving_query = "SELECT r.reservation_id, r.start_date, r.end_date, 
                  c.brand, c.model, c.plate_id,
                  u.full_name as customer_name, u.phone as customer_phone,
                  po.office_name as pickup_office, po.address as pickup_address
                  FROM reservation r
                  JOIN cars c ON r.car_id = c.car_id
                  JOIN users u ON r.customer_id = u.user_id
                  JOIN offices po ON r.pickup_office_id = po.office_id
                  WHERE DATE(r.start_date) = ?
                  AND r.status IN ('Pending', 'Confirmed')
                  ORDER BY r.start_date ASC";
$stmt = mysqli_prepare($conn, $leaving_query);
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$leaving_result = mysqli_stmt_get_result($stmt);
$leaving_cars = [];
while ($row = mysqli_fetch_assoc($leaving_result)) {
    $leaving_cars[] = $row;
}
mysqli_stmt_close($stmt);

// Fetch cars returning today (end_date = today)
$returning_query = "SELECT r.reservation_id, r.start_date, r.end_date, 
                    c.brand, c.model, c.plate_id,
                    u.full_name as customer_name, u.phone as customer_phone,
                    ro.office_name as return_office, ro.address as return_address
                    FROM reservation r
                    JOIN cars c ON r.car_id = c.car_id
                    JOIN users u ON r.customer_id = u.user_id
                    JOIN offices ro ON r.return_office_id = ro.office_id
                    WHERE DATE(r.end_date) = ?
                    AND r.status IN ('Confirmed', 'Completed')
                    ORDER BY r.end_date ASC";
$stmt = mysqli_prepare($conn, $returning_query);
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$returning_result = mysqli_stmt_get_result($stmt);
$returning_cars = [];
while ($row = mysqli_fetch_assoc($returning_result)) {
    $returning_cars[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operational Reports - Staff Dashboard</title>
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
            font-size: 32px;
            color: #e0e0e0;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #b0bec5;
            font-size: 16px;
        }

        .back-link {
            display: inline-block;
            color: #b0bec5;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #e0e0e0;
        }

        .section {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
            margin-bottom: 30px;
        }

        .section h2 {
            color: #e0e0e0;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            font-size: 28px;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .reports-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .reports-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
            color: #b0bec5;
        }

        .reports-table tr:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .count-badge {
            display: inline-block;
            background: rgba(100, 181, 246, 0.3);
            color: #64b5f6;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #90a4ae;
            font-size: 18px;
        }

        .time-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        @media screen and (max-width: 768px) {
            .reports-table {
                font-size: 14px;
            }

            .reports-table th,
            .reports-table td {
                padding: 10px;
            }
        }

        @media print {
            .navbar, .nav-menu, .page-header a, .print-actions {
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
            .reports-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 10pt;
            }
            .reports-table th,
            .reports-table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            .reports-table th {
                background: #f0f0f0;
                font-weight: bold;
            }
            .status-confirmed {
                background: white !important;
                color: #000 !important;
            }
            .status-pending {
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
            <a href="index.php" class="logo">
                <img src="uploads/logo/logo.png" alt="DRIVETuple Logo" style="height: 40px; width: auto; margin-right: 10px;" onerror="this.style.display='none'">
                <span>DRIVETuple</span>
            </a>
            <ul class="nav-menu">
                <li><a href="staff_dashboard.php">Staff Dashboard</a></li>
                <li><a href="index.php">Home</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="print-header" style="display: none;">
            <h1>DRIVETuple Car Rental System - Daily Operational Reports</h1>
            <p>Report Date: <?php echo date('F d, Y'); ?> | Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
        </div>

        <a href="staff_dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="print-actions" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print()" class="btn-print" style="background: #f5f5f5; color: #1a1a1a; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">🖨️ Print Reports</button>
        </div>
        
        <div class="page-header">
            <h1>Daily Operational Reports</h1>
            <p>View cars leaving and returning today (<?php echo date('F d, Y'); ?>)</p>
        </div>

        <div class="print-actions">
            <button onclick="window.print()" class="btn-print">🖨️ Print Reports</button>
        </div>

        <!-- Cars Leaving Today -->
        <div class="section">
            <h2>
                <span class="section-icon">🚗</span>
                Cars Leaving Today
                <span class="count-badge"><?php echo count($leaving_cars); ?></span>
            </h2>
            <?php if (count($leaving_cars) > 0): ?>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Car</th>
                            <th>Customer</th>
                            <th>Pickup Time</th>
                            <th>Return Date</th>
                            <th>Pickup Office</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaving_cars as $car): ?>
                            <tr>
                                <td>#<?php echo $car['reservation_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></strong><br>
                                    <small style="color: #90a4ae;"><?php echo htmlspecialchars($car['plate_id']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($car['customer_name']); ?><br>
                                    <small style="color: #90a4ae;"><?php echo htmlspecialchars($car['customer_phone'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <span class="time-badge">
                                        <?php echo date('H:i', strtotime($car['start_date'])); ?>
                                    </span><br>
                                    <?php echo date('M d, Y', strtotime($car['start_date'])); ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($car['end_date'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($car['pickup_office']); ?></strong><br>
                                    <small style="color: #90a4ae;"><?php echo htmlspecialchars($car['pickup_address']); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No cars leaving today.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cars Returning Today -->
        <div class="section">
            <h2>
                <span class="section-icon">🔄</span>
                Cars Returning Today
                <span class="count-badge"><?php echo count($returning_cars); ?></span>
            </h2>
            <?php if (count($returning_cars) > 0): ?>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Car</th>
                            <th>Customer</th>
                            <th>Start Date</th>
                            <th>Return Time</th>
                            <th>Return Office</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returning_cars as $car): ?>
                            <tr>
                                <td>#<?php echo $car['reservation_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></strong><br>
                                    <small style="color: #90a4ae;"><?php echo htmlspecialchars($car['plate_id']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($car['customer_name']); ?><br>
                                    <small style="color: #90a4ae;"><?php echo htmlspecialchars($car['customer_phone'] ?? 'N/A'); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($car['start_date'])); ?></td>
                                <td>
                                    <span class="time-badge">
                                        <?php echo date('H:i', strtotime($car['end_date'])); ?>
                                    </span><br>
                                    <?php echo date('M d, Y', strtotime($car['end_date'])); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($car['return_office']); ?></strong><br>
                                    <small style="color: #90a4ae;"><?php echo htmlspecialchars($car['return_address']); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>No cars returning today.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="print-footer" style="display: none;">
            <p>DRIVETuple Car Rental System - Daily Operational Report</p>
            <p>Page 1 of 1</p>
        </div>
    </div>
</body>
</html>