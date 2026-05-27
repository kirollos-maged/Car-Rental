<?php
session_start();
require_once 'conn.php';

// Check if user is logged in and is ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$edit_user_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$user_to_edit = null;

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    $user_id = intval($_POST['user_id']);
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $role = trim($_POST['role'] ?? 'CUSTOMER');
    $password = $_POST['password'] ?? '';
    
    if (empty($full_name) || empty($email)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, ['ADMIN', 'STAFF', 'CUSTOMER'])) {
        $error = 'Invalid role selected.';
    } else {
        // Check if email already exists for another user
        $check_query = "SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1";
        $check_stmt = mysqli_prepare($conn, $check_query);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = 'Email address is already registered to another user.';
                mysqli_stmt_close($check_stmt);
            } else {
                mysqli_stmt_close($check_stmt);
                
                // Build update query
                $empty_phone = empty($phone) ? null : $phone;
                $empty_address = empty($address) ? null : $address;
                $empty_city = empty($city) ? null : $city;
                $empty_country = empty($country) ? null : $country;
                
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = 'Password must be at least 6 characters long.';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, is_active = ?, role = ?, password = ? WHERE user_id = ?";
                        $update_stmt = mysqli_prepare($conn, $update_query);
                        if ($update_stmt) {
                            mysqli_stmt_bind_param($update_stmt, "sssssssssi", $full_name, $email, $empty_phone, $empty_address, $empty_city, $empty_country, $is_active, $role, $hashed_password, $user_id);
                            if (mysqli_stmt_execute($update_stmt)) {
                                $success = 'User updated successfully!';
                                $edit_user_id = 0;
                            } else {
                                $error = 'Failed to update user.';
                            }
                            mysqli_stmt_close($update_stmt);
                        }
                    }
                } else {
                    $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, is_active = ?, role = ? WHERE user_id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    if ($update_stmt) {
                        mysqli_stmt_bind_param($update_stmt, "ssssssssi", $full_name, $email, $empty_phone, $empty_address, $empty_city, $empty_country, $is_active, $role, $user_id);
                        if (mysqli_stmt_execute($update_stmt)) {
                            $success = 'User updated successfully!';
                            $edit_user_id = 0;
                        } else {
                            $error = 'Failed to update user.';
                        }
                        mysqli_stmt_close($update_stmt);
                    }
                }
            }
        }
    }
}

// Fetch user to edit if edit ID is provided
if ($edit_user_id > 0) {
    $edit_query = "SELECT user_id, full_name, email, phone, address, city, country, created_at, is_active, role 
                   FROM users 
                   WHERE user_id = ?";
    $edit_stmt = mysqli_prepare($conn, $edit_query);
    if ($edit_stmt) {
        mysqli_stmt_bind_param($edit_stmt, "i", $edit_user_id);
        mysqli_stmt_execute($edit_stmt);
        $edit_result = mysqli_stmt_get_result($edit_stmt);
        $user_to_edit = mysqli_fetch_assoc($edit_result);
        mysqli_stmt_close($edit_stmt);
    }
}

// Fetch all users
$users_query = "SELECT user_id, full_name, email, phone, address, city, country, created_at, is_active, role 
                FROM users 
                ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .form-section, .list-section {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        .form-section h2, .list-section h2 {
            color: #e0e0e0;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #b0bec5;
            font-weight: 500;
        }

        .form-group label .required {
            color: #ff6b6b;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #d0d0d0;
            box-shadow: 0 0 10px rgba(200, 200, 200, 0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #f5f5f5;
            color: #1a1a1a;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-submit:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
            color: #ffcdd2;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
            color: #c8e6c9;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .users-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .users-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
            color: #b0bec5;
        }

        .users-table tr:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .status-inactive {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .role-admin {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .role-staff {
            background: rgba(33, 150, 243, 0.2);
            color: #2196f3;
        }

        .role-customer {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .btn-edit {
            display: inline-block;
            padding: 5px 15px;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            background: #45a049;
        }

        .no-users {
            text-align: center;
            padding: 40px;
            color: #90a4ae;
        }

        @media screen and (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
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
            <h1>Manage Users</h1>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>

        <div class="content-grid">
            <!-- Edit User Form -->
            <?php if ($edit_user_id > 0 && $user_to_edit): ?>
            <div class="form-section">
                <h2>Edit User</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" value="<?php echo $user_to_edit['user_id']; ?>">
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user_to_edit['full_name']); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user_to_edit['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="role">Role <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="CUSTOMER" <?php echo $user_to_edit['role'] == 'CUSTOMER' ? 'selected' : ''; ?>>CUSTOMER</option>
                            <option value="STAFF" <?php echo $user_to_edit['role'] == 'STAFF' ? 'selected' : ''; ?>>STAFF</option>
                            <option value="ADMIN" <?php echo $user_to_edit['role'] == 'ADMIN' ? 'selected' : ''; ?>>ADMIN</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">New Password (leave empty to keep current)</label>
                        <input type="password" id="password" name="password" minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" 
                               value="<?php echo htmlspecialchars($user_to_edit['address'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               value="<?php echo htmlspecialchars($user_to_edit['city'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" 
                               value="<?php echo htmlspecialchars($user_to_edit['country'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" <?php echo $user_to_edit['is_active'] ? 'checked' : ''; ?>>
                            Active Account
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">Update User</button>
                    <a href="admin_manage_users.php" class="btn-submit" style="display: inline-block; text-align: center; background: #666; margin-top: 10px;">Cancel</a>
                </form>
            </div>
            <?php endif; ?>

            <!-- Users List -->
            <div class="list-section" style="<?php echo $edit_user_id > 0 ? 'grid-column: 1 / -1;' : ''; ?>">
                <h2>All Users</h2>
                
                <?php if ($error && $edit_user_id == 0): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success && $edit_user_id == 0): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if (mysqli_num_rows($users_result) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($users_result, 0);
                                while ($user = mysqli_fetch_assoc($users_result)): 
                                ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $user['user_id']; ?>" class="btn-edit">Edit</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-users">
                        <p>No users found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

