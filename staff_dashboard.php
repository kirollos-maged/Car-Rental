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

$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - DRIVETuple</title>
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
            max-width: 1200px;
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-size: 36px;
            color: #e0e0e0;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #b0bec5;
            font-size: 18px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .module-card {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.7);
        }

        .module-card h2 {
            color: #e0e0e0;
            margin-bottom: 15px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .module-card p {
            color: #b0bec5;
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .module-card ul {
            list-style: none;
            color: #90a4ae;
            margin-top: 15px;
        }

        .module-card ul li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }

        .module-card ul li:before {
            content: "▸";
            position: absolute;
            left: 0;
            color: #e0e0e0;
        }

        .module-icon {
            font-size: 32px;
        }

        @media screen and (max-width: 768px) {
            .modules-grid {
                grid-template-columns: 1fr;
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
                <li><a href="index.php">Home</a></li>
                <li><a href="staff_dashboard.php">Staff Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($staff_name); ?>!</h1>
            <p>Staff Operations Dashboard</p>
        </div>

        <div class="modules-grid">
            <!-- Car Operations Module -->
            <a href="staff_car_operations.php" class="module-card">
                <h2>
                    <span class="module-icon">🚗</span>
                    Car Operations
                </h2>
                <p>Manage car status, pricing, and view maintenance history</p>
                <ul>
                    <li>View car status history</li>
                    <li>Update car status (Active → Maintenance)</li>
                    <li>Update daily pricing</li>
                </ul>
            </a>

            <!-- Invoice Management Module -->
            <a href="staff_invoice_management.php" class="module-card">
                <h2>
                    <span class="module-icon">📄</span>
                    Invoice Management
                </h2>
                <p>Search, view, and edit customer invoices</p>
                <ul>
                    <li>Search invoices by ID or reservation</li>
                    <li>Edit invoice amounts and details</li>
                    <li>Notify customers of changes</li>
                </ul>
            </a>

            <!-- Operational Reports Module -->
            <a href="staff_operational_reports.php" class="module-card">
                <h2>
                    <span class="module-icon">📊</span>
                    Operational Reports
                </h2>
                <p>Daily operational view of car movements</p>
                <ul>
                    <li>Cars leaving today</li>
                    <li>Cars returning today</li>
                    <li>Daily activity overview</li>
                </ul>
            </a>

            <!-- Send Notifications Module -->
            <a href="staff_send_notifications.php" class="module-card">
                <h2>
                    <span class="module-icon">🔔</span>
                    Send Notifications
                </h2>
                <p>Send notifications to customers</p>
                <ul>
                    <li>Send to all customers</li>
                    <li>Send to specific customer</li>
                    <li>Link to reservations or invoices</li>
                </ul>
            </a>
        </div>
    </div>
</body>
</html>