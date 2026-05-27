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
    $plate_id = trim($_POST['plate_id'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $car_type = trim($_POST['car_type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
    $engine_size = isset($_POST['engine_size']) && $_POST['engine_size'] !== '' ? floatval($_POST['engine_size']) : null;
    $fuel_type = trim($_POST['fuel_type'] ?? '');
    $transmission_type = trim($_POST['transmission_type'] ?? '');
    $daily_price = isset($_POST['daily_price']) ? floatval($_POST['daily_price']) : 0;
    $offer_price = isset($_POST['offer_price']) && $_POST['offer_price'] !== '' ? floatval($_POST['offer_price']) : null;
    $office_id = isset($_POST['office_id']) && $_POST['office_id'] !== '' ? intval($_POST['office_id']) : null;
    $current_status_id = isset($_POST['current_status_id']) && $_POST['current_status_id'] !== '' ? intval($_POST['current_status_id']) : null;
    
    // Set default status to Available (1) if not specified
    if ($current_status_id === null) {
        $current_status_id = 1;
    }
    
    // Prepare empty variables for nullable fields
    $empty_car_type = empty($car_type) ? null : $car_type;
    $empty_color = empty($color) ? null : $color;
    $empty_engine_size = $engine_size === null ? null : $engine_size;
    $empty_fuel_type = empty($fuel_type) ? null : $fuel_type;
    $empty_transmission = empty($transmission_type) ? null : $transmission_type;
    
    // Handle car image upload
    $car_image_path = null;
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/cars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $car_tmp = $_FILES['car_image']['tmp_name'];
        $car_name = basename($_FILES['car_image']['name']);
        $car_ext = strtolower(pathinfo($car_name, PATHINFO_EXTENSION));
        if (in_array($car_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $car_new_name = 'car_' . time() . '_' . uniqid() . '.' . $car_ext;
            $car_path = $upload_dir . $car_new_name;
            if (move_uploaded_file($car_tmp, $car_path)) {
                $car_image_path = $car_path;
            }
        }
    }
    $car_id = isset($_POST['car_id']) ? intval($_POST['car_id']) : 0;
    
    // Validation
    if (empty($plate_id) || empty($brand) || empty($model) || $year < 1900 || $daily_price <= 0) {
        $error = 'Please fill in all required fields (Plate ID, Brand, Model, Year, Daily Price).';
    } else {
        if ($car_id > 0) {
            // Update existing car
            $update_query = "UPDATE cars SET plate_id = ?, brand = ?, model = ?, car_type = ?, color = ?, 
                           year = ?, engine_size = ?, fuel_type = ?, transmission_type = ?, 
                           daily_price = ?, offer_price = ?, office_id = ?, current_status_id = ?";
            $params = [$plate_id, $brand, $model, $empty_car_type, $empty_color, $year, $empty_engine_size, $empty_fuel_type, $empty_transmission, $daily_price, $offer_price, $office_id, $current_status_id];
            $types = "sssssidssddii";
            if ($car_image_path) {
                $update_query .= ", car_image_url = ?";
                $params[] = $car_image_path;
                $types .= "s";
            }
            $update_query .= " WHERE car_id = ?";
            $params[] = $car_id;
            $types .= "i";
            
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Car updated successfully!';
                $editing_id = null;
            } else {
                $error = 'Failed to update car. ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            // Check if plate_id already exists
            $check_query = "SELECT car_id FROM cars WHERE plate_id = ? LIMIT 1";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $plate_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = 'Plate ID already exists.';
                mysqli_stmt_close($check_stmt);
            } else {
                mysqli_stmt_close($check_stmt);
                
                // Create new car
                $insert_query = "INSERT INTO cars (plate_id, brand, model, car_type, color, year, engine_size, 
                               fuel_type, transmission_type, daily_price, offer_price, office_id, current_status_id, car_image_url) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                
                mysqli_stmt_bind_param($stmt, "sssssidssddiis", $plate_id, $brand, $model, $empty_car_type, 
                                     $empty_color, $year, $empty_engine_size, $empty_fuel_type, 
                                     $empty_transmission, $daily_price, $offer_price, $office_id, $current_status_id, $car_image_path);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Car created successfully!';
                    $_POST = array();
                } else {
                    $error = 'Failed to create car. ' . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM cars WHERE car_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Car deleted successfully!';
    } else {
        $error = 'Failed to delete car. It may be in use by existing reservations.';
    }
    mysqli_stmt_close($stmt);
}

// Handle Edit
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editing_id = intval($_GET['edit']);
    $edit_query = "SELECT * FROM cars WHERE car_id = ?";
    $stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($stmt, "i", $editing_id);
    mysqli_stmt_execute($stmt);
    $edit_result = mysqli_stmt_get_result($stmt);
    $edit_data = mysqli_fetch_assoc($edit_result);
    mysqli_stmt_close($stmt);
}

// Fetch all offices for dropdown
$offices_query = "SELECT office_id, office_name, city FROM offices ORDER BY city, office_name";
$offices_result = mysqli_query($conn, $offices_query);

// Fetch all car statuses for dropdown
$statuses_query = "SELECT status_id, status_name FROM car_status ORDER BY status_id";
$statuses_result = mysqli_query($conn, $statuses_query);

// Fetch all cars
$cars_query = "SELECT c.*, cs.status_name, o.office_name, o.city 
               FROM cars c 
               LEFT JOIN car_status cs ON c.current_status_id = cs.status_id 
               LEFT JOIN offices o ON c.office_id = o.office_id 
               ORDER BY c.car_id DESC";
$cars_result = mysqli_query($conn, $cars_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Admin Panel</title>
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-submit {
            background: #f5f5f5;
            color: #1a1a1a;
        }

        .btn-submit:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        .btn-cancel {
            background: #666;
            color: #e0e0e0;
        }

        .btn-cancel:hover {
            background: #777;
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

        .cars-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .cars-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .cars-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
            color: #b0bec5;
        }

        .cars-table tr:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .btn-edit, .btn-delete {
            display: inline-block;
            padding: 5px 15px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
            margin-right: 5px;
        }

        .btn-edit {
            background: #4caf50;
        }

        .btn-edit:hover {
            background: #45a049;
        }

        .btn-delete {
            background: #f44336;
        }

        .btn-delete:hover {
            background: #da190b;
        }

        .no-cars {
            text-align: center;
            padding: 40px;
            color: #90a4ae;
        }

        @media screen and (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
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
        <div class="page-header">
            <h1>Manage Cars</h1>
            <a href="admin_dashboard.php">← Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Create/Edit Car Form -->
            <div class="form-section">
                <h2><?php echo $editing_id ? 'Edit Car' : 'Create New Car'; ?></h2>

                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if ($editing_id): ?>
                        <input type="hidden" name="car_id" value="<?php echo $editing_id; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="plate_id">Plate ID <span class="required">*</span></label>
                        <input type="text" id="plate_id" name="plate_id" 
                               value="<?php echo htmlspecialchars($edit_data['plate_id'] ?? $_POST['plate_id'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="brand">Brand <span class="required">*</span></label>
                            <input type="text" id="brand" name="brand" 
                                   value="<?php echo htmlspecialchars($edit_data['brand'] ?? $_POST['brand'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="model">Model <span class="required">*</span></label>
                            <input type="text" id="model" name="model" 
                                   value="<?php echo htmlspecialchars($edit_data['model'] ?? $_POST['model'] ?? ''); ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="car_type">Car Type</label>
                            <input type="text" id="car_type" name="car_type" 
                                   value="<?php echo htmlspecialchars($edit_data['car_type'] ?? $_POST['car_type'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="color">Color</label>
                            <input type="text" id="color" name="color" 
                                   value="<?php echo htmlspecialchars($edit_data['color'] ?? $_POST['color'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="year">Year <span class="required">*</span></label>
                            <input type="number" id="year" name="year" min="1900" max="2099" 
                                   value="<?php echo $edit_data['year'] ?? $_POST['year'] ?? date('Y'); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="engine_size">Engine Size (L)</label>
                            <input type="number" id="engine_size" name="engine_size" step="0.1" min="0" 
                                   value="<?php echo $edit_data['engine_size'] ?? $_POST['engine_size'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fuel_type">Fuel Type</label>
                            <select id="fuel_type" name="fuel_type">
                                <option value="">Select Fuel Type</option>
                                <option value="Petrol" <?php echo (($edit_data['fuel_type'] ?? $_POST['fuel_type'] ?? '') == 'Petrol') ? 'selected' : ''; ?>>Petrol</option>
                                <option value="Diesel" <?php echo (($edit_data['fuel_type'] ?? $_POST['fuel_type'] ?? '') == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                                <option value="Electric" <?php echo (($edit_data['fuel_type'] ?? $_POST['fuel_type'] ?? '') == 'Electric') ? 'selected' : ''; ?>>Electric</option>
                                <option value="Hybrid" <?php echo (($edit_data['fuel_type'] ?? $_POST['fuel_type'] ?? '') == 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="transmission_type">Transmission</label>
                            <select id="transmission_type" name="transmission_type">
                                <option value="">Select Transmission</option>
                                <option value="Manual" <?php echo (($edit_data['transmission_type'] ?? $_POST['transmission_type'] ?? '') == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                                <option value="Automatic" <?php echo (($edit_data['transmission_type'] ?? $_POST['transmission_type'] ?? '') == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="daily_price">Daily Price ($) <span class="required">*</span></label>
                            <input type="number" id="daily_price" name="daily_price" step="0.01" min="0.01" 
                                   value="<?php echo $edit_data['daily_price'] ?? $_POST['daily_price'] ?? ''; ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="offer_price">Offer Price ($)</label>
                            <input type="number" id="offer_price" name="offer_price" step="0.01" min="0.01" 
                                   value="<?php echo $edit_data['offer_price'] ?? $_POST['offer_price'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="office_id">Office</label>
                            <select id="office_id" name="office_id">
                                <option value="">Select Office</option>
                                <?php 
                                mysqli_data_seek($offices_result, 0);
                                while ($office = mysqli_fetch_assoc($offices_result)): 
                                ?>
                                    <option value="<?php echo $office['office_id']; ?>" 
                                            <?php echo (($edit_data['office_id'] ?? $_POST['office_id'] ?? '') == $office['office_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['office_name'] . ' - ' . $office['city']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="current_status_id">Status</label>
                            <select id="current_status_id" name="current_status_id">
                                <option value="">Available</option>
                                <option value="">Rented</option>
                                <option value="">Out_of_service</option>
                            
                                <?php 
                                mysqli_data_seek($statuses_result, 0);
                                while ($status = mysqli_fetch_assoc($statuses_result)): 
                                ?>
                                    <option value="<?php echo $status['status_id']; ?>" 
                                            <?php echo (($edit_data['current_status_id'] ?? $_POST['current_status_id'] ?? '') == $status['status_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($status['status_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="car_image">Car Image</label>
                            <input type="file" id="car_image" name="car_image" accept="image/*">
                            <?php if (!empty($edit_data['car_image_url'])): ?>
                                <p style="margin-top: 10px;"><img src="<?php echo htmlspecialchars($edit_data['car_image_url']); ?>" alt="Car Image" style="max-width: 200px; max-height: 150px;"></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn-submit"><?php echo $editing_id ? 'Update Car' : 'Create Car'; ?></button>
                        <?php if ($editing_id): ?>
                            <a href="admin_manage_cars.php" class="btn-cancel" style="text-align: center; line-height: 48px;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Cars List -->
            <div class="list-section">
                <h2>All Cars</h2>
                <?php if (mysqli_num_rows($cars_result) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="cars-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Plate</th>
                                    <th>Brand/Model</th>
                                    <th>Year</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Office</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($cars_result, 0);
                                while ($car = mysqli_fetch_assoc($cars_result)): 
                                ?>
                                    <tr>
                                        <td><?php echo $car['car_id']; ?></td>
                                        <td><?php echo htmlspecialchars($car['plate_id']); ?></td>
                                        <td><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></td>
                                        <td><?php echo $car['year']; ?></td>
                                        <td>
                                            $<?php echo number_format($car['daily_price'], 2); ?>
                                            <?php if ($car['offer_price']): ?>
                                                <br><span style="color: #ffd700;">Offer: $<?php echo number_format($car['offer_price'], 2); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($car['status_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($car['office_name'] ?? 'N/A'); ?></td>
                                        <td><?php if ($car['car_image_url']): ?><img src="<?php echo htmlspecialchars($car['car_image_url']); ?>" alt="Car" style="max-width: 50px; max-height: 50px;"><?php endif; ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $car['car_id']; ?>" class="btn-edit">Edit</a>
                                            <a href="?delete=<?php echo $car['car_id']; ?>" class="btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this car?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-cars">
                        <p>No cars found. Create your first car using the form.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

