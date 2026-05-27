<?php
session_start();
require_once 'conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

$error = '';
$success = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'customer_details_required') {
        $error = 'Please complete your customer details before making a reservation.';
    }
}

// Fetch customer profile
$profile_query = "SELECT full_name, email, phone, address, city, country 
                  FROM users 
                  WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $profile_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$profile_result = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($profile_result);
mysqli_stmt_close($stmt);

// Fetch booking history
$history_query = "SELECT r.reservation_id, r.start_date, r.end_date, r.status, r.total_price,
                         c.brand, c.model, i.invoice_id
                  FROM reservation r
                  JOIN cars c ON r.car_id = c.car_id
                  LEFT JOIN invoices i ON r.reservation_id = i.reservation_id
                  WHERE r.customer_id = ?
                  ORDER BY r.created_at DESC";
$stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$history_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Fetch notifications
$notifications_query = "SELECT notification_id, message, is_read, created_at, related_invoice_id, related_reservation_id
                        FROM notifications
                        WHERE user_id = ?
                        ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $notifications_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$notifications_result = mysqli_stmt_get_result($stmt);
$notifications = [];
$unread_count = 0;
while ($row = mysqli_fetch_assoc($notifications_result)) {
    $notifications[] = $row;
    if (!$row['is_read']) {
        $unread_count++;
    }
}
mysqli_stmt_close($stmt);

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mark_read') {
    $notification_id = intval($_POST['notification_id']);
    $update_query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $customer_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    // Refresh page
    header("Location: dashboard.php");
    exit();
}

// Handle profile update
$profile_error = '';
$profile_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    if (empty($full_name)) {
        $profile_error = 'Full name is required.';
    } else {
        $update_query = "UPDATE users SET full_name = ?, phone = ?, address = ?, city = ?, country = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        $empty_phone = empty($phone) ? null : $phone;
        $empty_address = empty($address) ? null : $address;
        $empty_city = empty($city) ? null : $city;
        $empty_country = empty($country) ? null : $country;
        
        mysqli_stmt_bind_param($stmt, "sssssi", $full_name, $empty_phone, $empty_address, $empty_city, $empty_country, $customer_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $profile_success = 'Profile updated successfully!';
            // Refresh profile data
            mysqli_stmt_close($stmt);
            $profile_query = "SELECT full_name, email, phone, address, city, country 
                              FROM users 
                              WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $profile_query);
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            $profile_result = mysqli_stmt_get_result($stmt);
            $profile = mysqli_fetch_assoc($profile_result);
            mysqli_stmt_close($stmt);
        } else {
            $profile_error = 'Failed to update profile.';
        }
    }
}

// Handle customer details upload
$details_error = '';
$details_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_details') {
    $national_id_number = trim($_POST['national_id_number'] ?? '');
    
    // Handle file uploads - create customer-specific directory
    $upload_dir = 'uploads/customers/' . $customer_id . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $profile_image_path = null;
    $driving_license_path = null;
    
    // Profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $profile_tmp = $_FILES['profile_image']['tmp_name'];
        $profile_name = basename($_FILES['profile_image']['name']);
        $profile_ext = strtolower(pathinfo($profile_name, PATHINFO_EXTENSION));
        if (in_array($profile_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $profile_new_name = 'profile_' . $customer_id . '_' . time() . '.' . $profile_ext;
            $profile_path = $upload_dir . $profile_new_name;
            if (move_uploaded_file($profile_tmp, $profile_path)) {
                $profile_image_path = $profile_path;
            }
        }
    }
    
    // Driving license upload
    if (isset($_FILES['driving_license_image']) && $_FILES['driving_license_image']['error'] == UPLOAD_ERR_OK) {
        $license_tmp = $_FILES['driving_license_image']['tmp_name'];
        $license_name = basename($_FILES['driving_license_image']['name']);
        $license_ext = strtolower(pathinfo($license_name, PATHINFO_EXTENSION));
        if (in_array($license_ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])) {
            $license_new_name = 'license_' . $customer_id . '_' . time() . '.' . $license_ext;
            $license_path = $upload_dir . $license_new_name;
            if (move_uploaded_file($license_tmp, $license_path)) {
                $driving_license_path = $license_path;
            }
        }
    }
    
    if (empty($national_id_number)) {
        $details_error = 'National ID Number is required.';
    } else {
        // Check if customer_details already exists
        $check_query = "SELECT customer_id FROM customer_details WHERE customer_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "i", $customer_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing
            $update_query = "UPDATE customer_details SET national_id_number = ?";
            $params = [$national_id_number];
            $types = "s";
            if ($profile_image_path) {
                $update_query .= ", profile_image_url = ?";
                $params[] = $profile_image_path;
                $types .= "s";
            }
            if ($driving_license_path) {
                $update_query .= ", driving_license_image_url = ?";
                $params[] = $driving_license_path;
                $types .= "s";
            }
            $update_query .= " WHERE customer_id = ?";
            $params[] = $customer_id;
            $types .= "i";
            
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        } else {
            // Insert new
            $insert_query = "INSERT INTO customer_details (customer_id, national_id_number, profile_image_url, driving_license_image_url) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "isss", $customer_id, $national_id_number, $profile_image_path, $driving_license_path);
        }
        mysqli_stmt_close($check_stmt);
        
        if (mysqli_stmt_execute($stmt)) {
            $details_success = 'Customer details saved successfully!';
        } else {
            $details_error = 'Failed to save customer details.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch customer details
$customer_details_query = "SELECT national_id_number, profile_image_url, driving_license_image_url 
                          FROM customer_details 
                          WHERE customer_id = ?";
$details_stmt = mysqli_prepare($conn, $customer_details_query);
mysqli_stmt_bind_param($details_stmt, "i", $customer_id);
mysqli_stmt_execute($details_stmt);
$customer_details_result = mysqli_stmt_get_result($details_stmt);
$customer_details = mysqli_fetch_assoc($customer_details_result);
mysqli_stmt_close($details_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - DRIVETuple</title>
    <link rel="stylesheet" href="css/dashboard.css">

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
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">My Profile</a></li>
                <li class="notification-badge">
                    <span class="notification-icon" onclick="toggleNotifications()">🔔</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-count"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                    <div class="notifications-dropdown" id="notificationsDropdown">
                        <div class="notifications-header">
                            <h3>Notifications</h3>
                        </div>
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                    <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                    <div class="notification-time"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></div>
                                    <?php if (!$notif['is_read']): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="notification_id" value="<?php echo $notif['notification_id']; ?>">
                                            <button type="submit" class="mark-read-btn">Mark as Read</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center; color: #90a4ae;">No notifications</div>
                        <?php endif; ?>
                    </div>
                </li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($profile['full_name']); ?>!</h1>
            <p>Manage your profile and view your booking history</p>
        </div>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('profile')">Profile</button>
            <button class="tab-button" onclick="switchTab('details')">Customer Details</button>
            <button class="tab-button" onclick="switchTab('history')">History</button>
            <?php if (count($notifications) > 0): ?>
                <button class="tab-button" onclick="switchTab('notifications')">
                    Notifications
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #ff4444; color: white; border-radius: 10px; padding: 2px 8px; margin-left: 5px; font-size: 12px;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin: 20px 0;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Profile Tab -->
        <div id="profile" class="tab-content active">
            <div class="profile-section">
                <h2>Profile Information</h2>
                
                <?php if ($profile_error): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;"><?php echo htmlspecialchars($profile_error); ?></div>
                <?php endif; ?>
                
                <?php if ($profile_success): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo htmlspecialchars($profile_success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="profile-grid">
                        <div class="profile-item">
                            <div class="profile-label">Full Name <span style="color: #ff6b6b;">*</span></div>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($profile['full_name']); ?>" 
                                   style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 2px solid rgba(150, 150, 150, 0.2); border-radius: 8px; color: #e0e0e0; font-size: 16px;" required>
                        </div>
                        <div class="profile-item">
                            <div class="profile-label">Email</div>
                            <div class="profile-value"><?php echo htmlspecialchars($profile['email']); ?> <span style="color: #90a4ae; font-size: 12px;">(Cannot be changed)</span></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-label">Phone</div>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" 
                                   style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 2px solid rgba(150, 150, 150, 0.2); border-radius: 8px; color: #e0e0e0; font-size: 16px;">
                        </div>
                        <div class="profile-item">
                            <div class="profile-label">Address</div>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>" 
                                   style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 2px solid rgba(150, 150, 150, 0.2); border-radius: 8px; color: #e0e0e0; font-size: 16px;">
                        </div>
                        <div class="profile-item">
                            <div class="profile-label">City</div>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>" 
                                   style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 2px solid rgba(150, 150, 150, 0.2); border-radius: 8px; color: #e0e0e0; font-size: 16px;">
                        </div>
                        <div class="profile-item">
                            <div class="profile-label">Country</div>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($profile['country'] ?? ''); ?>" 
                                   style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 2px solid rgba(150, 150, 150, 0.2); border-radius: 8px; color: #e0e0e0; font-size: 16px;">
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" style="margin-top: 20px; width: auto; padding: 12px 30px;">Update Profile</button>
                </form>
            </div>
        </div>

        <!-- Customer Details Tab -->
        <div id="details" class="tab-content">
            <div class="profile-section">
                <h2>Customer Details</h2>
                <p style="color: #90a4ae; margin-bottom: 20px;">Please upload your details to complete your profile. These details are required for car reservations.</p>
                
                <?php if ($details_error): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;"><?php echo htmlspecialchars($details_error); ?></div>
                <?php endif; ?>
                
                <?php if ($details_success): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo htmlspecialchars($details_success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="upload_details">
                    <div class="profile-grid">
                        <div class="profile-item" style="grid-column: 1 / -1;">
                            <div class="profile-label">National ID Number <span style="color: #ff6b6b;">*</span></div>
                            <input type="text" name="national_id_number" 
                                   value="<?php echo htmlspecialchars($customer_details['national_id_number'] ?? ''); ?>" 
                                   style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 2px solid rgba(150, 150, 150, 0.2); border-radius: 8px; color: #e0e0e0; font-size: 16px;" 
                                   required placeholder="Enter your National ID Number">
                        </div>
                        <div class="profile-item" style="grid-column: 1 / -1;">
                            <div class="profile-label">Profile Image</div>
                            <input type="file" name="profile_image" accept="image/*" 
                                   style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 2px solid rgba(150, 150, 150, 0.2); border-radius: 8px; color: #e0e0e0; font-size: 16px;">
                            <?php if (!empty($customer_details['profile_image_url'])): ?>
                                <p style="margin-top: 10px;"><img src="<?php echo htmlspecialchars($customer_details['profile_image_url']); ?>" alt="Profile Image" style="max-width: 100px; max-height: 100px;"></p>
                            <?php endif; ?>
                        </div>
                        <div class="profile-item" style="grid-column: 1 / -1;">
                            <div class="profile-label">Driving License Image</div>
                            <input type="file" name="driving_license_image" accept="image/*" 
                                   style="width: 100%; padding: 10px; background: rgba(0, 0, 0, 0.3); border: 2px solid rgba(150, 150, 150, 0.2); border-radius: 8px; color: #e0e0e0; font-size: 16px;">
                            <?php if (!empty($customer_details['driving_license_image_url'])): ?>
                                <p style="margin-top: 10px;"><img src="<?php echo htmlspecialchars($customer_details['driving_license_image_url']); ?>" alt="Driving License" style="max-width: 100px; max-height: 100px;"></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" style="margin-top: 20px; width: auto; padding: 12px 30px;">Save Details</button>
                </form>
                
                <?php if ($customer_details): ?>
                    <div style="margin-top: 30px; padding: 20px; background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); border-radius: 8px;">
                        <h3 style="color: #4caf50; margin-bottom: 15px;">Current Details</h3>
                        <div class="profile-grid">
                            <div class="profile-item">
                                <div class="profile-label">National ID</div>
                                <div class="profile-value"><?php echo htmlspecialchars($customer_details['national_id_number']); ?></div>
                            </div>
                            <?php if ($customer_details['profile_image_url']): ?>
                                <div class="profile-item">
                                    <div class="profile-label">Profile Image</div>
                                    <div class="profile-value"><img src="<?php echo htmlspecialchars($customer_details['profile_image_url']); ?>" alt="Profile Image" style="max-width: 100px; max-height: 100px;"></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($customer_details['driving_license_image_url']): ?>
                                <div class="profile-item">
                                    <div class="profile-label">Driving License</div>
                                    <div class="profile-value">
                                        <?php 
                                        $ext = strtolower(pathinfo($customer_details['driving_license_image_url'], PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                            <img src="<?php echo htmlspecialchars($customer_details['driving_license_image_url']); ?>" alt="Driving License" style="max-width: 100px; max-height: 100px;">
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($customer_details['driving_license_image_url']); ?>" target="_blank" style="color: #64b5f6;">View Document</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- History Tab -->
        <div id="history" class="tab-content">
            <div class="history-section">
                <h2>Booking History</h2>
                <?php if (mysqli_num_rows($history_result) > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Car Model</th>
                                <th>Pick-up Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Total Price</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($history_result, 0);
                            while ($booking = mysqli_fetch_assoc($history_result)): 
                                $status_class = 'status-' . strtolower($booking['status']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($booking['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($booking['end_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <?php if ($booking['invoice_id']): ?>
                                            <a href="invoice.php?invoice_id=<?php echo $booking['invoice_id']; ?>" 
                                               class="btn-invoice" target="_blank">
                                                View Invoice
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #90a4ae;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-history">
                        <p>You haven't made any bookings yet.</p>
                        <p style="margin-top: 10px;">
                            <a href="index.php" style="color: #f5f5f5; text-decoration: underline;">Browse our cars</a> to get started!
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notifications Tab -->
        <?php if (count($notifications) > 0): ?>
            <div id="notifications" class="tab-content">
                <div class="history-section">
                    <h2>Notifications</h2>
                    <div style="margin-top: 20px;">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" style="margin-bottom: 15px;">
                                <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                <div class="notification-time"><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></div>
                                <?php if ($notif['related_invoice_id']): ?>
                                    <a href="invoice.php?invoice_id=<?php echo $notif['related_invoice_id']; ?>" 
                                       class="btn-invoice" style="margin-top: 10px; display: inline-block;" target="_blank">
                                        View Invoice
                                    </a>
                                <?php endif; ?>
                                <?php if (!$notif['is_read']): ?>
                                    <form method="POST" action="" style="display: inline; margin-left: 10px;">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?php echo $notif['notification_id']; ?>">
                                        <button type="submit" class="mark-read-btn">Mark as Read</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>