<?php
session_start();
require_once 'conn.php';

// Check if user is logged in and is ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Car Rental</title>
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
            margin-bottom: 50px;
        }

        .dashboard-header h1 {
            font-size: 42px;
            color: #e0e0e0;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #b0bec5;
            font-size: 18px;
        }

        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .module-card {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
            border-color: rgba(200, 200, 200, 0.4);
        }

        .module-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .module-title {
            font-size: 24px;
            color: #e0e0e0;
            margin-bottom: 10px;
        }

        .module-description {
            color: #90a4ae;
            font-size: 14px;
            line-height: 1.6;
        }

        @media screen and (max-width: 768px) {
            .modules-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header h1 {
                font-size: 32px;
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
        <div class="dashboard-header">
            <h1>Admin Dashboard <span class="admin-badge">SUPER USER</span></h1>
            <p>Welcome, <?php echo htmlspecialchars($admin_name); ?> - Manage your car rental system</p>
        </div>

        <div class="modules-grid">
            <a href="admin_manage_staff.php" class="module-card">
                <div class="module-icon">👥</div>
                <div class="module-title">Manage Staff</div>
                <div class="module-description">
                    Create and manage staff user accounts. Only admins can create new staff members.
                </div>
            </a>

            <a href="admin_manage_offices.php" class="module-card">
                <div class="module-icon">🏢</div>
                <div class="module-title">Manage Offices</div>
                <div class="module-description">
                    Create, update, and delete office locations. Full CRUD operations for the OFFICES table.
                </div>
            </a>

            <a href="admin_system_logs.php" class="module-card">
                <div class="module-icon">🔍</div>
                <div class="module-title">System Logs (Watchtower)</div>
                <div class="module-description">
                    View security logs with filters. Monitor critical errors and user activities.
                </div>
            </a>

            <a href="admin_reports.php" class="module-card">
                <div class="module-icon">📊</div>
                <div class="module-title">Advanced Reports</div>
                <div class="module-description">
                    Revenue reports by month and car popularity analytics. Business intelligence dashboard.
                </div>
            </a>

            <a href="admin_manage_users.php" class="module-card">
                <div class="module-icon">👤</div>
                <div class="module-title">Manage Users</div>
                <div class="module-description">
                    View and edit all user accounts. Manage customer, staff, and admin accounts with full edit functionality.
                </div>
            </a>

            <a href="admin_manage_cars.php" class="module-card">
                <div class="module-icon">🚗</div>
                <div class="module-title">Manage Cars</div>
                <div class="module-description">
                    Create, edit, and delete cars. Full CRUD operations for the cars table including all fields.
                </div>
            </a>
        </div>
    </div>
</body>
</html>

