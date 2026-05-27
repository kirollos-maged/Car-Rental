<?php
session_start();
require_once 'conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$reservation_id = isset($_GET['reservation_id']) ? intval($_GET['reservation_id']) : 0;

if ($reservation_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch reservation details
$reservation_query = "SELECT r.*, c.brand, c.model, c.car_type, c.color, c.daily_price,
                             o1.office_name as pickup_office, o1.city as pickup_city,
                             o2.office_name as return_office, o2.city as return_city,
                             u.full_name, u.email, u.phone
                      FROM reservation r
                      JOIN cars c ON r.car_id = c.car_id
                      JOIN offices o1 ON r.pickup_office_id = o1.office_id
                      JOIN offices o2 ON r.return_office_id = o2.office_id
                      JOIN users u ON r.customer_id = u.user_id
                      WHERE r.reservation_id = ? AND r.customer_id = ?";
$stmt = mysqli_prepare($conn, $reservation_query);
mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $customer_id);
mysqli_stmt_execute($stmt);
$reservation_result = mysqli_stmt_get_result($stmt);
$reservation = mysqli_fetch_assoc($reservation_result);
mysqli_stmt_close($stmt);

if (!$reservation) {
    header("Location: dashboard.php");
    exit();
}

// Check if payment already exists
$payment_check_query = "SELECT payment_id FROM payments WHERE reservation_id = ?";
$stmt = mysqli_prepare($conn, $payment_check_query);
mysqli_stmt_bind_param($stmt, "i", $reservation_id);
mysqli_stmt_execute($stmt);
$payment_result = mysqli_stmt_get_result($stmt);
$payment_exists = mysqli_num_rows($payment_result) > 0;
mysqli_stmt_close($stmt);

$error = '';
$success = '';

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'process_payment') {
    if ($payment_exists) {
        $error = 'Payment already processed for this reservation.';
    } else {
        $payment_method_id = isset($_POST['payment_method_id']) ? intval($_POST['payment_method_id']) : 0;
        
        if ($payment_method_id <= 0) {
            $error = 'Please select a payment method.';
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert payment
                $payment_query = "INSERT INTO payments 
                                 (reservation_id, customer_id, payment_method_id, 
                                  pickup_office_id, return_office_id, amount, payment_status) 
                                 VALUES (?, ?, ?, ?, ?, ?, 'Paid')";
                $stmt = mysqli_prepare($conn, $payment_query);
                mysqli_stmt_bind_param($stmt, "iiiiid", $reservation_id, $customer_id, $payment_method_id,
                                      $reservation['pickup_office_id'], $reservation['return_office_id'], 
                                      $reservation['total_price']);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to process payment: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
                
                // Update reservation status to Confirmed
                $update_reservation_query = "UPDATE reservation SET status = 'Confirmed' WHERE reservation_id = ?";
                $stmt = mysqli_prepare($conn, $update_reservation_query);
                mysqli_stmt_bind_param($stmt, "i", $reservation_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to update reservation: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
                
                // Create invoice
                $invoice_query = "INSERT INTO invoices 
                                 (reservation_id, customer_id, invoice_status, customer_name, 
                                  car_type, car_brand, car_color, payment_method, 
                                  invoice_issue_time, rental_start_time, rental_end_time, total_amount) 
                                 VALUES (?, ?, 'ISSUED', ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";
                
                $payment_method_name_query = "SELECT method_name FROM payment_methods WHERE method_id = ?";
                $stmt = mysqli_prepare($conn, $payment_method_name_query);
                mysqli_stmt_bind_param($stmt, "i", $payment_method_id);
                mysqli_stmt_execute($stmt);
                $method_result = mysqli_stmt_get_result($stmt);
                $method = mysqli_fetch_assoc($method_result);
                mysqli_stmt_close($stmt);
                
                $stmt = mysqli_prepare($conn, $invoice_query);
                mysqli_stmt_bind_param($stmt, "iisssssssd", $reservation_id, $customer_id, $reservation['full_name'],
                                      $reservation['car_type'], $reservation['brand'], $reservation['color'], 
                                      $method['method_name'], $reservation['start_date'], $reservation['end_date'], 
                                      $reservation['total_price']);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to create invoice: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
                
                mysqli_commit($conn);
                $success = 'Payment processed successfully! Your reservation is confirmed.';
                $payment_exists = true;
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }
    }
}

// Fetch payment methods
$payment_methods_query = "SELECT * FROM payment_methods ORDER BY method_id";
$payment_methods_result = mysqli_query($conn, $payment_methods_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Car Rental</title>
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
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .payment-container {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        .payment-container h2 {
            color: #e0e0e0;
            margin-bottom: 25px;
            text-align: center;
        }

        .reservation-summary {
            background: rgba(150, 150, 150, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-item.total {
            font-size: 18px;
            font-weight: bold;
            padding-top: 10px;
            border-top: 2px solid rgba(150, 150, 150, 0.3);
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #b0bec5;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            font-size: 16px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            transition: all 0.3s;
        }

        .form-group select:focus {
            outline: none;
            border-color: #d0d0d0;
            box-shadow: 0 0 10px rgba(200, 200, 200, 0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            color: #0a0a0a;
            border: none;
            border-radius: 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 255, 136, 0.3);
        }

        .btn-submit:hover:not(:disabled) {
            background: linear-gradient(135deg, #00cc6a 0%, #00aa55 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 255, 136, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background: rgba(0, 255, 136, 0.2);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 136, 0.5);
        }

        .error {
            background: rgba(255, 68, 68, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(255, 68, 68, 0.5);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #b0bec5;
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #e0e0e0;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🚗 CarRental</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="payment-container">
            <h2>Payment Details</h2>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="reservation-summary">
                <div class="summary-item">
                    <span>Car:</span>
                    <span><?php echo htmlspecialchars($reservation['brand'] . ' ' . $reservation['model']); ?></span>
                </div>
                <div class="summary-item">
                    <span>Pickup:</span>
                    <span><?php echo htmlspecialchars($reservation['pickup_office'] . ', ' . $reservation['pickup_city']); ?> on <?php echo date('M d, Y H:i', strtotime($reservation['start_date'])); ?></span>
                </div>
                <div class="summary-item">
                    <span>Return:</span>
                    <span><?php echo htmlspecialchars($reservation['return_office'] . ', ' . $reservation['return_city']); ?> on <?php echo date('M d, Y H:i', strtotime($reservation['end_date'])); ?></span>
                </div>
                <div class="summary-item total">
                    <span>Total Amount:</span>
                    <span>$<?php echo number_format($reservation['total_price'], 2); ?></span>
                </div>
            </div>

            <?php if (!$payment_exists): ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="process_payment">
                    
                    <div class="form-group">
                        <label for="payment_method_id">Payment Method *</label>
                        <select id="payment_method_id" name="payment_method_id" required>
                            <option value="">Select Payment Method</option>
                            <?php 
                            mysqli_data_seek($payment_methods_result, 0);
                            while ($method = mysqli_fetch_assoc($payment_methods_result)): ?>
                                <option value="<?php echo $method['method_id']; ?>">
                                    <?php echo htmlspecialchars($method['method_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit">Pay Now</button>
                </form>
            <?php else: ?>
                <div class="message success">Payment has already been processed for this reservation.</div>
            <?php endif; ?>

            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>