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
$error = '';
$success = '';

// Handle notification sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_notification') {
    $message = trim($_POST['message'] ?? '');
    $target_type = $_POST['target_type'] ?? 'all'; // 'all' or 'specific'
    $target_user_id = isset($_POST['target_user_id']) && $_POST['target_user_id'] !== '' ? intval($_POST['target_user_id']) : null;
    $related_reservation_id = isset($_POST['related_reservation_id']) && $_POST['related_reservation_id'] !== '' ? intval($_POST['related_reservation_id']) : null;
    $related_invoice_id = isset($_POST['related_invoice_id']) && $_POST['related_invoice_id'] !== '' ? intval($_POST['related_invoice_id']) : null;
    
    if (empty($message)) {
        $error = 'Message is required.';
    } else {
        if ($target_type == 'all') {
            // Send to all customers
            $customers_query = "SELECT user_id FROM users WHERE role = 'CUSTOMER'";
            $customers_result = mysqli_query($conn, $customers_query);
            
            $sent_count = 0;
            while ($customer = mysqli_fetch_assoc($customers_result)) {
                $insert_query = "INSERT INTO notifications (user_id, message, related_reservation_id, related_invoice_id) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "isii", $customer['user_id'], $message, $related_reservation_id, $related_invoice_id);
                if (mysqli_stmt_execute($stmt)) {
                    $sent_count++;
                }
                mysqli_stmt_close($stmt);
            }
            
            if ($sent_count > 0) {
                $success = "Notification sent to $sent_count customers successfully!";
            } else {
                $error = 'Failed to send notifications.';
            }
        } else {
            // Send to specific customer
            if ($target_user_id === null) {
                $error = 'Please select a customer.';
            } else {
                $insert_query = "INSERT INTO notifications (user_id, message, related_reservation_id, related_invoice_id) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "isii", $target_user_id, $message, $related_reservation_id, $related_invoice_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Notification sent successfully!';
                } else {
                    $error = 'Failed to send notification.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Fetch all customers for dropdown
$customers_query = "SELECT user_id, full_name, email FROM users WHERE role = 'CUSTOMER' ORDER BY full_name";
$customers_result = mysqli_query($conn, $customers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notifications - DRIVETuple Staff</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 40px;
            width: auto;
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

        .notification-form {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
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

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            font-size: 16px;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-option input[type="radio"] {
            width: auto;
        }

        .btn-submit {
            background: #f5f5f5;
            color: #1a1a1a;
            padding: 14px 30px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            background: #ffffff;
            transform: translateY(-2px);
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
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="staff_dashboard.php" class="logo">
                <img src="uploads/logo/logo.png" alt="DRIVETuple Logo" style="height: 40px; width: auto;" onerror="this.style.display='none'">
                <span>DRIVETuple</span>
            </a>
            <ul class="nav-menu">
                <li><a href="staff_dashboard.php">Dashboard</a></li>
                <li><a href="index.php">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Send Notifications</h1>
            <a href="staff_dashboard.php">← Back to Dashboard</a>
        </div>

        <div class="notification-form">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="send_notification">
                
                <div class="form-group">
                    <label>Target Audience <span style="color: #ff6b6b;">*</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="target_all" name="target_type" value="all" checked onchange="toggleCustomerSelect()">
                            <label for="target_all">All Customers</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="target_specific" name="target_type" value="specific" onchange="toggleCustomerSelect()">
                            <label for="target_specific">Specific Customer</label>
                        </div>
                    </div>
                </div>

                <div class="form-group" id="customer_select_group" style="display: none;">
                    <label for="target_user_id">Select Customer</label>
                    <select id="target_user_id" name="target_user_id">
                        <option value="">Select a customer</option>
                        <?php 
                        mysqli_data_seek($customers_result, 0);
                        while ($customer = mysqli_fetch_assoc($customers_result)): 
                        ?>
                            <option value="<?php echo $customer['user_id']; ?>">
                                <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['email'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Message <span style="color: #ff6b6b;">*</span></label>
                    <textarea id="message" name="message" required placeholder="Enter notification message..."></textarea>
                </div>

                <div class="form-group">
                    <label for="related_reservation_id">Related Reservation ID (optional)</label>
                    <input type="number" id="related_reservation_id" name="related_reservation_id" min="1" placeholder="Leave empty if not related to a reservation">
                </div>

                <div class="form-group">
                    <label for="related_invoice_id">Related Invoice ID (optional)</label>
                    <input type="number" id="related_invoice_id" name="related_invoice_id" min="1" placeholder="Leave empty if not related to an invoice">
                </div>

                <button type="submit" class="btn-submit">Send Notification</button>
            </form>
        </div>
    </div>

    <script>
        function toggleCustomerSelect() {
            const targetSpecific = document.getElementById('target_specific').checked;
            const customerSelectGroup = document.getElementById('customer_select_group');
            const customerSelect = document.getElementById('target_user_id');
            
            if (targetSpecific) {
                customerSelectGroup.style.display = 'block';
                customerSelect.required = true;
            } else {
                customerSelectGroup.style.display = 'none';
                customerSelect.required = false;
                customerSelect.value = '';
            }
        }
    </script>
</body>
</html>

