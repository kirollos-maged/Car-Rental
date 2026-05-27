<?php
session_start();
require_once 'conn.php';

// Check if user is logged in and is ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$filter_level = $_GET['filter_level'] ?? '';
$filter_user_id = isset($_GET['filter_user_id']) && $_GET['filter_user_id'] !== '' ? intval($_GET['filter_user_id']) : null;
$filter_username = trim($_GET['filter_username'] ?? '');
$filter_country = trim($_GET['filter_country'] ?? '');

// Build query
$logs_query = "SELECT sl.*, u.full_name, u.email, u.country 
              FROM security_logs sl 
              LEFT JOIN users u ON sl.user_id = u.user_id 
              WHERE 1=1";

$params = [];
$types = '';

if (!empty($filter_level)) {
    $logs_query .= " AND sl.log_level = ?";
    $params[] = $filter_level;
    $types .= 's';
}

if ($filter_user_id !== null) {
    $logs_query .= " AND sl.user_id = ?";
    $params[] = $filter_user_id;
    $types .= 'i';
}

if (!empty($filter_username)) {
    $logs_query .= " AND u.full_name LIKE ?";
    $params[] = '%' . $filter_username . '%';
    $types .= 's';
}

if (!empty($filter_country)) {
    $logs_query .= " AND u.country LIKE ?";
    $params[] = '%' . $filter_country . '%';
    $types .= 's';
}

$logs_query .= " ORDER BY sl.created_at DESC LIMIT 500";

$stmt = mysqli_prepare($conn, $logs_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$logs_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Get all users for filter dropdown
$users_query = "SELECT user_id, full_name, email FROM users ORDER BY full_name";
$users_result = mysqli_query($conn, $users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs (Watchtower) - Admin Panel</title>
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

        .filters-section {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
            margin-bottom: 30px;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 8px;
            color: #b0bec5;
            font-weight: 500;
        }

        .filter-group select, .filter-group input {
            padding: 10px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            font-size: 14px;
        }

        .filter-group select:focus, .filter-group input:focus {
            outline: none;
            border-color: #d0d0d0;
        }

        .btn-filter {
            padding: 10px 20px;
            background: #f5f5f5;
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            background: #ffffff;
        }

        .btn-reset {
            padding: 10px 20px;
            background: rgba(117, 117, 117, 0.3);
            color: #b0bec5;
            border: 1px solid rgba(117, 117, 117, 0.5);
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-reset:hover {
            background: rgba(117, 117, 117, 0.5);
        }

        .logs-section {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        .logs-section h2 {
            color: #e0e0e0;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .logs-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
            color: #b0bec5;
        }

        .logs-table tr:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .log-level {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .level-info {
            background: rgba(100, 181, 246, 0.2);
            color: #64b5f6;
        }

        .level-warning {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .level-error {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .level-critical {
            background: rgba(156, 39, 176, 0.2);
            color: #9c27b0;
        }

        .no-logs {
            text-align: center;
            padding: 40px;
            color: #90a4ae;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="logo">
                <img src="logo.jpeg" alt="DRIVETuple Logo" style="height: 40px; width: auto; margin-right: 10px;" onerror="this.style.display='none'">
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
        <div class="page-header">
            <h1>🔍 System Logs (The Watchtower)</h1>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <label for="filter_level">Log Level</label>
                    <select id="filter_level" name="filter_level">
                        <option value="">All Levels</option>
                        <option value="INFO" <?php echo $filter_level === 'INFO' ? 'selected' : ''; ?>>INFO</option>
                        <option value="WARNING" <?php echo $filter_level === 'WARNING' ? 'selected' : ''; ?>>WARNING</option>
                        <option value="ERROR" <?php echo $filter_level === 'ERROR' ? 'selected' : ''; ?>>ERROR</option>
                        <option value="CRITICAL" <?php echo $filter_level === 'CRITICAL' ? 'selected' : ''; ?>>CRITICAL</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filter_user_id">User ID</label>
                    <input type="number" id="filter_user_id" name="filter_user_id" 
                           value="<?php echo $filter_user_id !== null ? $filter_user_id : ''; ?>" 
                           placeholder="Enter User ID">
                </div>
                <div class="filter-group">
                    <label for="filter_username">Username</label>
                    <input type="text" id="filter_username" name="filter_username" 
                           value="<?php echo htmlspecialchars($filter_username); ?>" 
                           placeholder="Filter by Username">
                </div>
                <div class="filter-group">
                    <label for="filter_country">Country</label>
                    <input type="text" id="filter_country" name="filter_country" 
                           value="<?php echo htmlspecialchars($filter_country); ?>" 
                           placeholder="Filter by Country">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter">Apply Filters</button>
                </div>

                <div class="filter-group">
                    <a href="admin_system_logs.php" class="btn-reset">Reset</a>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="logs-section">
            <h2>Security Logs</h2>
            <?php if (mysqli_num_rows($logs_result) > 0): ?>
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>Level</th>
                            <th>User</th>
                            <th>Action Type</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($logs_result, 0);
                        while ($log = mysqli_fetch_assoc($logs_result)): 
                            $level_class = 'level-' . strtolower($log['log_level']);
                        ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <span class="log-level <?php echo $level_class; ?>">
                                        <?php echo htmlspecialchars($log['log_level']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['user_id']): ?>
                                        <?php echo htmlspecialchars($log['full_name'] ?? 'User #' . $log['user_id']); ?><br>
                                        <small style="color: #90a4ae;"><?php echo htmlspecialchars($log['email'] ?? ''); ?></small>
                                    <?php else: ?>
                                        <span style="color: #90a4ae;">System</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                                <td style="max-width: 300px; word-wrap: break-word;">
                                    <?php echo htmlspecialchars(substr($log['action_details'] ?? 'N/A', 0, 100)); ?>
                                    <?php if (strlen($log['action_details'] ?? '') > 100): ?>...<?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-logs">
                    <p>No logs found matching your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

