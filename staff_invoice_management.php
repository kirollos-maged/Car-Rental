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

// Handle invoice update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_invoice') {
    $invoice_id = intval($_POST['invoice_id']);
    $total_amount = floatval($_POST['total_amount']);
    $payment_method = trim($_POST['payment_method'] ?? '');
    
    // Get current invoice details to get customer_id and reservation_id
    $invoice_query = "SELECT customer_id, reservation_id FROM invoices WHERE invoice_id = ?";
    $stmt = mysqli_prepare($conn, $invoice_query);
    mysqli_stmt_bind_param($stmt, "i", $invoice_id);
    mysqli_stmt_execute($stmt);
    $invoice_result = mysqli_stmt_get_result($stmt);
    $invoice_data = mysqli_fetch_assoc($invoice_result);
    mysqli_stmt_close($stmt);
    
    if ($invoice_data && $total_amount > 0) {
        // Update invoice
        $update_query = "UPDATE invoices SET total_amount = ?, payment_method = ? WHERE invoice_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "dsi", $total_amount, $payment_method, $invoice_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Create notification for customer
            $notification_message = "Your invoice for Reservation #" . $invoice_data['reservation_id'] . " has been updated. Please download the new version.";
            $notification_query = "INSERT INTO notifications (user_id, message, related_reservation_id, related_invoice_id, is_read) 
                                  VALUES (?, ?, ?, ?, 0)";
            $notif_stmt = mysqli_prepare($conn, $notification_query);
            mysqli_stmt_bind_param($notif_stmt, "isii", $invoice_data['customer_id'], $notification_message, 
                                   $invoice_data['reservation_id'], $invoice_id);
            
            if (mysqli_stmt_execute($notif_stmt)) {
                $message = "Invoice updated successfully and customer has been notified!";
                $message_type = "success";
            } else {
                $message = "Invoice updated but failed to send notification.";
                $message_type = "warning";
            }
            mysqli_stmt_close($notif_stmt);
        } else {
            $message = "Failed to update invoice.";
            $message_type = "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Invalid invoice or amount.";
        $message_type = "error";
    }
}

// Handle search
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$invoices = [];

if (!empty($search_term)) {
    // Search by invoice_id or reservation_id
    $search_query = "SELECT i.*, r.start_date, r.end_date, u.full_name as customer_full_name
                     FROM invoices i
                     JOIN reservation r ON i.reservation_id = r.reservation_id
                     JOIN users u ON i.customer_id = u.user_id
                     WHERE i.invoice_id = ? OR i.reservation_id = ?
                     ORDER BY i.invoice_issue_time DESC";
    $stmt = mysqli_prepare($conn, $search_query);
    $search_int = intval($search_term);
    mysqli_stmt_bind_param($stmt, "ii", $search_int, $search_int);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $invoices[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management - Staff Dashboard</title>
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

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            font-size: 16px;
        }

        .search-form input:focus {
            outline: none;
            border-color: #d0d0d0;
        }

        .search-form button {
            padding: 12px 30px;
            background: #f5f5f5;
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-form button:hover {
            background: #ffffff;
            transform: translateY(-2px);
        }

        .invoices-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .invoices-table th {
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .invoices-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
            color: #b0bec5;
        }

        .invoices-table tr:hover {
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
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            max-height: 90vh;
            overflow-y: auto;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            font-size: 16px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #d0d0d0;
        }

        .form-group input[readonly] {
            background: rgba(0, 0, 0, 0.5);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #90a4ae;
            font-size: 18px;
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
            <h1>Invoice Management</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Search Section -->
        <div class="section">
            <h2>Search Invoice</h2>
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Enter Invoice ID or Reservation ID" 
                       value="<?php echo htmlspecialchars($search_term); ?>" required>
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Results Section -->
        <?php if (!empty($search_term)): ?>
            <div class="section">
                <h2>Search Results</h2>
                <?php if (count($invoices) > 0): ?>
                    <table class="invoices-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Reservation ID</th>
                                <th>Customer</th>
                                <th>Issue Date</th>
                                <th>Rental Period</th>
                                <th>Total Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo $invoice['invoice_id']; ?></td>
                                    <td>#<?php echo $invoice['reservation_id']; ?></td>
                                    <td><?php echo htmlspecialchars($invoice['customer_full_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['invoice_issue_time'])); ?></td>
                                    <td>
                                        <?php echo date('M d', strtotime($invoice['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($invoice['end_date'])); ?>
                                    </td>
                                    <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['payment_method'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['invoice_status']); ?></td>
                                    <td>
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($invoice)); ?>)" 
                                                class="btn btn-primary">Edit Invoice</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <p>No invoices found for "<?php echo htmlspecialchars($search_term); ?>"</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Invoice Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2 style="color: #e0e0e0; margin-bottom: 20px;">Edit Invoice</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_invoice">
                <input type="hidden" name="invoice_id" id="edit_invoice_id">
                
                <div class="form-group">
                    <label>Invoice ID</label>
                    <input type="text" id="edit_invoice_id_display" readonly>
                </div>
                
                <div class="form-group">
                    <label>Reservation ID</label>
                    <input type="text" id="edit_reservation_id" readonly>
                </div>
                
                <div class="form-group">
                    <label>Customer</label>
                    <input type="text" id="edit_customer_name" readonly>
                </div>
                
                <div class="form-group">
                    <label for="edit_total_amount">Total Amount ($)</label>
                    <input type="number" name="total_amount" id="edit_total_amount" step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_payment_method">Payment Method</label>
                    <select name="payment_method" id="edit_payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Online">Online</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Invoice</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(invoice) {
            document.getElementById('edit_invoice_id').value = invoice.invoice_id;
            document.getElementById('edit_invoice_id_display').value = 'Invoice #' + invoice.invoice_id;
            document.getElementById('edit_reservation_id').value = 'Reservation #' + invoice.reservation_id;
            document.getElementById('edit_customer_name').value = invoice.customer_full_name;
            document.getElementById('edit_total_amount').value = parseFloat(invoice.total_amount).toFixed(2);
            document.getElementById('edit_payment_method').value = invoice.payment_method || 'Cash';
            document.getElementById('editModal').style.display = 'block';
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