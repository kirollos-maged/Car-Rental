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
$editing_id = null;
$edit_data = null;

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $office_name = trim($_POST['office_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $office_id = isset($_POST['office_id']) ? intval($_POST['office_id']) : 0;
    
    // Validation
    if (empty($office_name) || empty($address) || empty($city) || empty($country)) {
        $error = 'Please fill in all required fields.';
    } else {
        if ($office_id > 0) {
            // Update existing office
            $update_query = "UPDATE offices SET office_name = ?, address = ?, city = ?, country = ?, 
                           latitude = ?, longitude = ?, phone = ? WHERE office_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            $empty_lat = empty($latitude) ? null : $latitude;
            $empty_lng = empty($longitude) ? null : $longitude;
            $empty_phone = empty($phone) ? null : $phone;
            mysqli_stmt_bind_param($stmt, "ssssddsi", $office_name, $address, $city, $country, 
                                 $empty_lat, $empty_lng, $empty_phone, $office_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Office updated successfully!';
                $editing_id = null;
            } else {
                $error = 'Failed to update office.';
            }
            mysqli_stmt_close($stmt);
        } else {
            // Create new office
            $insert_query = "INSERT INTO offices (office_name, address, city, country, latitude, longitude, phone) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            $empty_lat = empty($latitude) ? null : $latitude;
            $empty_lng = empty($longitude) ? null : $longitude;
            $empty_phone = empty($phone) ? null : $phone;
            mysqli_stmt_bind_param($stmt, "ssssdds", $office_name, $address, $city, $country, 
                                 $empty_lat, $empty_lng, $empty_phone);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Office created successfully!';
                $_POST = array();
            } else {
                $error = 'Failed to create office.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM offices WHERE office_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Office deleted successfully!';
    } else {
        $error = 'Failed to delete office. It may be in use by existing reservations.';
    }
    mysqli_stmt_close($stmt);
}

// Handle Edit
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editing_id = intval($_GET['edit']);
    $edit_query = "SELECT * FROM offices WHERE office_id = ?";
    $stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($stmt, "i", $editing_id);
    mysqli_stmt_execute($stmt);
    $edit_result = mysqli_stmt_get_result($stmt);
    $edit_data = mysqli_fetch_assoc($edit_result);
    mysqli_stmt_close($stmt);
}

// Fetch all offices
$offices_query = "SELECT * FROM offices ORDER BY country, city, office_name";
$offices_result = mysqli_query($conn, $offices_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Offices - Admin Panel</title>
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
            grid-template-columns: 1fr 1fr;
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

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #d0d0d0;
            box-shadow: 0 0 10px rgba(200, 200, 200, 0.2);
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-submit, .btn-cancel {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit {
            background: #f5f5f5;
            color: #1a1a1a;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-submit:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        .btn-cancel {
            background: rgba(117, 117, 117, 0.3);
            color: #b0bec5;
            border: 1px solid rgba(117, 117, 117, 0.5);
        }

        .btn-cancel:hover {
            background: rgba(117, 117, 117, 0.5);
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

        .offices-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .offices-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .offices-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
            color: #b0bec5;
        }

        .offices-table tr:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-edit {
            background: rgba(100, 181, 246, 0.3);
            color: #64b5f6;
            border: 1px solid rgba(100, 181, 246, 0.5);
        }

        .btn-edit:hover {
            background: rgba(100, 181, 246, 0.5);
        }

        .btn-delete {
            background: rgba(244, 67, 54, 0.3);
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.5);
        }

        .btn-delete:hover {
            background: rgba(244, 67, 54, 0.5);
        }

        .no-offices {
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
            <h1>Manage Offices</h1>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>

        <div class="content-grid">
            <!-- Create/Edit Office Form -->
            <div class="form-section">
                <h2><?php echo $editing_id ? 'Edit Office' : 'Create New Office'; ?></h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php if ($editing_id): ?>
                        <input type="hidden" name="office_id" value="<?php echo $editing_id; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="office_name">Office Name <span class="required">*</span></label>
                        <input type="text" id="office_name" name="office_name" 
                               value="<?php echo htmlspecialchars($edit_data['office_name'] ?? $_POST['office_name'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address <span class="required">*</span></label>
                        <input type="text" id="address" name="address" 
                               value="<?php echo htmlspecialchars($edit_data['address'] ?? $_POST['address'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" 
                               value="<?php echo htmlspecialchars($edit_data['city'] ?? $_POST['city'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="country">Country <span class="required">*</span></label>
                        <input type="text" id="country" name="country" 
                               value="<?php echo htmlspecialchars($edit_data['country'] ?? $_POST['country'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="latitude">Latitude</label>
                        <input type="number" step="any" id="latitude" name="latitude" 
                               value="<?php echo htmlspecialchars($edit_data['latitude'] ?? $_POST['latitude'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="longitude">Longitude</label>
                        <input type="number" step="any" id="longitude" name="longitude" 
                               value="<?php echo htmlspecialchars($edit_data['longitude'] ?? $_POST['longitude'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($edit_data['phone'] ?? $_POST['phone'] ?? ''); ?>">
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn-submit">
                            <?php echo $editing_id ? 'Update Office' : 'Create Office'; ?>
                        </button>
                        <?php if ($editing_id): ?>
                            <a href="admin_manage_offices.php" class="btn-cancel" style="text-align: center; line-height: 48px;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Offices List -->
            <div class="list-section">
                <h2>Existing Offices</h2>
                <?php if (mysqli_num_rows($offices_result) > 0): ?>
                    <table class="offices-table">
                        <thead>
                            <tr>
                                <th>Office Name</th>
                                <th>Location</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($offices_result, 0);
                            while ($office = mysqli_fetch_assoc($offices_result)): 
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($office['office_name']); ?></td>
                                    <td><?php echo htmlspecialchars($office['city'] . ', ' . $office['country']); ?></td>
                                    <td><?php echo htmlspecialchars($office['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="admin_manage_offices.php?edit=<?php echo $office['office_id']; ?>" class="btn-edit">Edit</a>
                                            <a href="admin_manage_offices.php?delete=<?php echo $office['office_id']; ?>" 
                                               class="btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this office?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-offices">
                        <p>No offices found. Create your first office using the form.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

