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
$message = '';
$message_type = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $car_id = intval($_POST['car_id']);
    $new_status_id = intval($_POST['new_status_id']);
    
    // Get current price of the car for history
    $car_query = "SELECT daily_price FROM cars WHERE car_id = ?";
    $stmt = mysqli_prepare($conn, $car_query);
    mysqli_stmt_bind_param($stmt, "i", $car_id);
    mysqli_stmt_execute($stmt);
    $car_result = mysqli_stmt_get_result($stmt);
    $car = mysqli_fetch_assoc($car_result);
    mysqli_stmt_close($stmt);
    
    // Update car status
    $update_query = "UPDATE cars SET current_status_id = ? WHERE car_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $new_status_id, $car_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Insert into history
        $history_query = "INSERT INTO car_status_history (car_id, status_id, changed_by_user_id, price_at_that_time, status_date) 
                         VALUES (?, ?, ?, ?, NOW())";
        $history_stmt = mysqli_prepare($conn, $history_query);
        mysqli_stmt_bind_param($history_stmt, "iiid", $car_id, $new_status_id, $staff_id, $car['daily_price']);
        
        if (mysqli_stmt_execute($history_stmt)) {
            $message = "Car status updated successfully!";
            $message_type = "success";
        } else {
            $message = "Status updated but failed to log history.";
            $message_type = "warning";
        }
        mysqli_stmt_close($history_stmt);
    } else {
        $message = "Failed to update car status.";
        $message_type = "error";
    }
    mysqli_stmt_close($stmt);
}

// Handle price update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_price') {
    $car_id = intval($_POST['car_id']);
    $new_price = floatval($_POST['new_price']);
    
    if ($new_price > 0) {
        $update_query = "UPDATE cars SET daily_price = ? WHERE car_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "di", $new_price, $car_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Car pricing updated successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to update car pricing.";
            $message_type = "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Invalid price. Please enter a positive number.";
        $message_type = "error";
    }
}

// Handle offer price update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_offer') {
    $car_id = intval($_POST['car_id']);
    $offer_price = isset($_POST['offer_price']) && $_POST['offer_price'] !== '' ? floatval($_POST['offer_price']) : null;
    
    if ($offer_price === null || $offer_price > 0) {
        $update_query = "UPDATE cars SET offer_price = ? WHERE car_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "di", $offer_price, $car_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Car offer price updated successfully!";
            $message_type = "success";
        } else {
            $message = "Failed to update car offer price.";
            $message_type = "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Invalid offer price. Please enter a positive number or leave empty to remove offer.";
        $message_type = "error";
    }
}

// Fetch all cars with their current status
$cars_query = "SELECT c.car_id, c.plate_id, c.brand, c.model, c.daily_price, c.offer_price,
               cs.status_name, cs.status_id
               FROM cars c
               LEFT JOIN car_status cs ON c.current_status_id = cs.status_id
               ORDER BY c.car_id";
$cars_result = mysqli_query($conn, $cars_query);

// Fetch available statuses
$statuses_query = "SELECT status_id, status_name FROM car_status ORDER BY status_id";
$statuses_result = mysqli_query($conn, $statuses_query);
$statuses = [];
while ($row = mysqli_fetch_assoc($statuses_result)) {
    $statuses[] = $row;
}

// Get selected car for history view
$selected_car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;
$car_history = [];
$selected_car = null;

if ($selected_car_id > 0) {
    // Fetch car details
    $car_query = "SELECT c.*, cs.status_name FROM cars c 
                  LEFT JOIN car_status cs ON c.current_status_id = cs.status_id
                  WHERE c.car_id = ?";
    $stmt = mysqli_prepare($conn, $car_query);
    mysqli_stmt_bind_param($stmt, "i", $selected_car_id);
    mysqli_stmt_execute($stmt);
    $car_result = mysqli_stmt_get_result($stmt);
    $selected_car = mysqli_fetch_assoc($car_result);
    mysqli_stmt_close($stmt);
    
    // Fetch history
    if ($selected_car) {
        $history_query = "SELECT csh.*, cs.status_name, u.full_name as changed_by_name
                         FROM car_status_history csh
                         JOIN car_status cs ON csh.status_id = cs.status_id
                         LEFT JOIN users u ON csh.changed_by_user_id = u.user_id
                         WHERE csh.car_id = ?
                         ORDER BY csh.status_date DESC";
        $stmt = mysqli_prepare($conn, $history_query);
        mysqli_stmt_bind_param($stmt, "i", $selected_car_id);
        mysqli_stmt_execute($stmt);
        $history_result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($history_result)) {
            $car_history[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Operations - Staff Dashboard</title>
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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
            color: #c8e6c9;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
            color: #ffcdd2;
        }

        .alert-warning {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.5);
            color: #fff9c4;
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
        }

        .cars-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .cars-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .cars-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
            color: #b0bec5;
        }

        .cars-table tr:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin-right: 5px;
        }

        .btn-primary {
            background: #f5f5f5;
            color: #1a1a1a;
        }

        .btn-primary:hover {
            background: #ffffff;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(100, 181, 246, 0.3);
            color: #64b5f6;
            border: 1px solid rgba(100, 181, 246, 0.5);
        }

        .btn-secondary:hover {
            background: rgba(100, 181, 246, 0.5);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.98) 0%, rgba(30, 30, 30, 0.98) 100%);
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            border: 1px solid rgba(150, 150, 150, 0.3);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
        }

        .close {
            color: #b0bec5;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close:hover {
            color: #e0e0e0;
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

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            font-size: 16px;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #d0d0d0;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
        }

        .history-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            font-weight: 600;
        }

        .history-table td {
            color: #b0bec5;
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
        <a href="staff_dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="page-header">
            <h1>Car Operations</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Cars List Section -->
        <div class="section">
            <h2>All Cars</h2>
            <table class="cars-table">
                <thead>
                    <tr>
                        <th>Car ID</th>
                        <th>Plate ID</th>
                        <th>Brand & Model</th>
                        <th>Daily Price</th>
                        <th>Current Status</th>
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
                            <td>
                                $<?php echo number_format($car['daily_price'], 2); ?>
                                <?php if ($car['offer_price']): ?>
                                    <br><span style="color: #ffd700; font-weight: bold;">Offer: $<?php echo number_format($car['offer_price'], 2); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($car['status_name'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="?car_id=<?php echo $car['car_id']; ?>" class="btn btn-secondary">View History</a>
                                <button onclick="openStatusModal(<?php echo $car['car_id']; ?>, '<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>', <?php echo $car['status_id']; ?>)" 
                                        class="btn btn-primary">Update Status</button>
                                <button onclick="openPriceModal(<?php echo $car['car_id']; ?>, '<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>', <?php echo $car['daily_price']; ?>, <?php echo $car['offer_price'] ?? 'null'; ?>)" 
                                        class="btn btn-primary">Update Price</button>
                                <button onclick="openOfferModal(<?php echo $car['car_id']; ?>, '<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>', <?php echo $car['offer_price'] ?? 'null'; ?>)" 
                                        class="btn btn-secondary" style="margin-top: 5px;">Update Offer</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Car History Section -->
        <?php if ($selected_car): ?>
            <div class="section">
                <h2>Status History - <?php echo htmlspecialchars($selected_car['brand'] . ' ' . $selected_car['model'] . ' (' . $selected_car['plate_id'] . ')'); ?></h2>
                <p style="color: #b0bec5; margin-bottom: 20px;">
                    Current Status: <strong><?php echo htmlspecialchars($selected_car['status_name'] ?? 'N/A'); ?></strong> | 
                    Current Price: <strong>$<?php echo number_format($selected_car['daily_price'], 2); ?></strong>
                </p>
                
                <?php if (count($car_history) > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Changed By</th>
                                <th>Price at Time</th>
                                <th>Total Rent Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($car_history as $history): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($history['status_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($history['status_name']); ?></td>
                                    <td><?php echo htmlspecialchars($history['changed_by_name'] ?? 'System'); ?></td>
                                    <td>$<?php echo number_format($history['price_at_that_time'] ?? 0, 2); ?></td>
                                    <td><?php echo $history['total_rent_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #90a4ae; text-align: center; padding: 20px;">No history available for this car.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('statusModal')">&times;</span>
            <h2 style="color: #e0e0e0; margin-bottom: 20px;">Update Car Status</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="car_id" id="status_car_id">
                
                <div class="form-group">
                    <label>Car</label>
                    <input type="text" id="status_car_name" readonly style="background: rgba(0, 0, 0, 0.5);">
                </div>
                
                <div class="form-group">
                    <label for="new_status_id">New Status</label>
                    <select name="new_status_id" id="new_status_id" required>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status['status_id']; ?>">
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
            </form>
        </div>
    </div>

    <!-- Price Update Modal -->
    <div id="priceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('priceModal')">&times;</span>
            <h2 style="color: #e0e0e0; margin-bottom: 20px;">Update Car Pricing</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_price">
                <input type="hidden" name="car_id" id="price_car_id">
                
                <div class="form-group">
                    <label>Car</label>
                    <input type="text" id="price_car_name" readonly style="background: rgba(0, 0, 0, 0.5);">
                </div>
                
                <div class="form-group">
                    <label for="new_price">New Daily Price ($)</label>
                    <input type="number" name="new_price" id="new_price" step="0.01" min="0.01" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Price</button>
            </form>
        </div>
    </div>

    <!-- Offer Price Update Modal -->
    <div id="offerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('offerModal')">&times;</span>
            <h2 style="color: #e0e0e0; margin-bottom: 20px;">Update Offer Price</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_offer">
                <input type="hidden" name="car_id" id="offer_car_id">
                
                <div class="form-group">
                    <label>Car</label>
                    <input type="text" id="offer_car_name" readonly style="background: rgba(0, 0, 0, 0.5);">
                </div>
                
                <div class="form-group">
                    <label for="offer_price">Offer Price ($) - Leave empty to remove offer</label>
                    <input type="number" name="offer_price" id="offer_price" step="0.01" min="0.01">
                    <small style="color: #90a4ae; display: block; margin-top: 5px;">Enter a special offer price or leave empty to remove the offer.</small>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Offer</button>
            </form>
        </div>
    </div>

    <script>
        function openStatusModal(carId, carName, currentStatusId) {
            document.getElementById('status_car_id').value = carId;
            document.getElementById('status_car_name').value = carName;
            document.getElementById('new_status_id').value = currentStatusId;
            document.getElementById('statusModal').style.display = 'block';
        }

        function openPriceModal(carId, carName, currentPrice, currentOffer) {
            document.getElementById('price_car_id').value = carId;
            document.getElementById('price_car_name').value = carName;
            document.getElementById('new_price').value = currentPrice;
            document.getElementById('priceModal').style.display = 'block';
        }

        function openOfferModal(carId, carName, currentOffer) {
            document.getElementById('offer_car_id').value = carId;
            document.getElementById('offer_car_name').value = carName;
            document.getElementById('offer_price').value = currentOffer || '';
            document.getElementById('offerModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>