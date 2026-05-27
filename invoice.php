<?php
session_start();
require_once 'conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$invoice_id = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;

if ($invoice_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch invoice details
$invoice_query = "SELECT i.*, r.reservation_id, r.start_date, r.end_date, r.status as reservation_status
                  FROM invoices i
                  JOIN reservation r ON i.reservation_id = r.reservation_id
                  WHERE i.invoice_id = ? AND i.customer_id = ?";
$stmt = mysqli_prepare($conn, $invoice_query);
mysqli_stmt_bind_param($stmt, "ii", $invoice_id, $customer_id);
mysqli_stmt_execute($stmt);
$invoice_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($invoice_result) == 0) {
    header("Location: dashboard.php");
    exit();
}

$invoice = mysqli_fetch_assoc($invoice_result);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice_id; ?> - DRIVETuple</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .company-info h1 {
            font-size: 32px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .company-info p {
            color: #666;
            font-size: 14px;
        }

        .invoice-info h2 {
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .invoice-info p {
            color: #666;
            font-size: 14px;
        }

        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .detail-section h3 {
            color: #1a1a1a;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .detail-item {
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
            font-size: 14px;
        }

        .detail-value {
            color: #333;
            font-size: 16px;
            margin-top: 5px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-table th {
            background: #1a1a1a;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .invoice-table td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        .invoice-table tr:hover {
            background: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .total-section {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .total-box {
            width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }

        .total-row.grand-total {
            font-size: 24px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 10px;
        }

        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .print-button {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-print {
            background: #1a1a1a;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-print:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .print-button {
                display: none;
            }

            .invoice-container {
                box-shadow: none;
            }
        }

        @media screen and (max-width: 768px) {
            .invoice-details {
                grid-template-columns: 1fr;
            }

            .invoice-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button class="btn-print" onclick="window.print()">Print Invoice</button>
    </div>

    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-info">
                <h1>DRIVETuple</h1>
                <p>Your Trusted Car Rental Service</p>
                <img src="uploads/logo/logo.png" alt="DRIVETuple Logo" style="height: 60px; margin-top: 10px;" onerror="this.style.display='none'>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p>Invoice #: <?php echo $invoice_id; ?></p>
                <p>Date: <?php echo date('F d, Y', strtotime($invoice['invoice_issue_time'])); ?></p>
            </div>
        </div>

        <div class="invoice-details">
            <div class="detail-section">
                <h3>Customer Information</h3>
                <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($invoice['customer_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Customer ID</div>
                    <div class="detail-value"><?php echo $customer_id; ?></div>
                </div>
            </div>

            <div class="detail-section">
                <h3>Reservation Details</h3>
                <div class="detail-item">
                    <div class="detail-label">Reservation ID</div>
                    <div class="detail-value">#<?php echo $invoice['reservation_id']; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value"><?php echo htmlspecialchars($invoice['reservation_status']); ?></div>
                </div>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>DRIVETuple Car Rental</strong><br>
                        <?php echo htmlspecialchars($invoice['car_brand'] . ' ' . ($invoice['car_type'] ?? '')); ?><br>
                        Color: <?php echo htmlspecialchars($invoice['car_color'] ?? 'N/A'); ?>
                    </td>
                    <td class="text-right">
                        Rental Period<br>
                        <?php echo date('M d, Y H:i', strtotime($invoice['rental_start_time'])); ?><br>
                        to<br>
                        <?php echo date('M d, Y H:i', strtotime($invoice['rental_end_time'])); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Payment Method:</span>
                    <span><?php echo htmlspecialchars($invoice['payment_method']); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Total Amount:</span>
                    <span>$<?php echo number_format($invoice['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="invoice-footer">
            <p>Thank you for choosing DRIVETuple!</p>
            <p>For inquiries, please contact us at info@drivetuple.com</p>
        </div>
    </div>
</body>
</html>