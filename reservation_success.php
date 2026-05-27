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
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;

if ($reservation_id <= 0 || $invoice_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Verify reservation belongs to current user
$verify_query = "SELECT reservation_id FROM reservation WHERE reservation_id = ? AND customer_id = ?";
$stmt = mysqli_prepare($conn, $verify_query);
mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $customer_id);
mysqli_stmt_execute($stmt);
$verify_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($verify_result) == 0) {
    header("Location: dashboard.php");
    exit();
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Successful - Car Rental</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-container {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        h1 {
            color: #e0e0e0;
            margin-bottom: 20px;
            font-size: 32px;
        }

        p {
            color: #b0bec5;
            margin-bottom: 30px;
            font-size: 18px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #f5f5f5;
            color: #1a1a1a;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-primary:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        .btn-secondary {
            background: rgba(117, 117, 117, 0.3);
            color: #b0bec5;
            border: 1px solid rgba(117, 117, 117, 0.5);
        }

        .btn-secondary:hover {
            background: rgba(117, 117, 117, 0.5);
            border-color: rgba(117, 117, 117, 0.8);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Reservation Confirmed!</h1>
        <p>Your reservation has been successfully created. You can view and print your invoice below.</p>
        <div class="btn-group">
            <a href="invoice.php?invoice_id=<?php echo $invoice_id; ?>" class="btn btn-primary" target="_blank">
                Print Invoice
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>