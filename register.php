<?php
session_start();
require_once 'conn.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Helper function to get IP address
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ip_address = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists
        $check_query = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $check_stmt = mysqli_prepare($conn, $check_query);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = 'Email address is already registered. Please login instead.';
                mysqli_stmt_close($check_stmt);
            } else {
                mysqli_stmt_close($check_stmt);
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_query = "INSERT INTO users (full_name, email, phone, password, address, city, country, role) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'CUSTOMER')";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                
                if ($insert_stmt) {
                    $empty_phone = empty($phone) ? null : $phone;
                    $empty_address = empty($address) ? null : $address;
                    $empty_city = empty($city) ? null : $city;
                    $empty_country = empty($country) ? null : $country;
                    
                    mysqli_stmt_bind_param($insert_stmt, "sssssss", 
                        $full_name, $email, $empty_phone, $hashed_password, 
                        $empty_address, $empty_city, $empty_country);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $new_user_id = mysqli_insert_id($conn);
                        
                        // Log registration to security_logs
                        $security_log_query = "INSERT INTO security_logs (user_id, action_type, action_details, ip_address, user_agent, log_level) VALUES (?, 'REGISTRATION', 'New user registered', ?, ?, 'INFO')";
                        $security_log_stmt = mysqli_prepare($conn, $security_log_query);
                        if ($security_log_stmt) {
                            mysqli_stmt_bind_param($security_log_stmt, "iss", $new_user_id, $ip_address, $user_agent);
                            mysqli_stmt_execute($security_log_stmt);
                            mysqli_stmt_close($security_log_stmt);
                        }
                        
                        $success = 'Registration successful! You can now login.';
                        // Clear form data
                        $_POST = array();
                    } else {
                        $error = 'Registration failed. Please try again later.';
                    }
                    mysqli_stmt_close($insert_stmt);
                } else {
                    $error = 'Database error. Please try again later.';
                }
            }
        } else {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DRIVETuple</title>
    <link rel="stylesheet" href="css/register.css">

</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="uploads/logo/logo.png" alt="DRIVETuple Logo" style="height: 40px; width: auto; margin-right: 10px;" onerror="this.style.display='none'">
                <span>DRIVETuple</span>
            </a>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="advanced_search.php">Advanced Search</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
            <div class="burger" id="burger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Register Container -->
    <div class="register-container">
        <div class="register-box">
            <h1 class="register-title">Create Account</h1>
            <p class="register-subtitle">Join us and start renting cars today!</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" 
                               placeholder="Enter your full name" 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               placeholder="Enter your email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="Enter your phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               placeholder="Enter your city" 
                               value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" 
                           placeholder="Enter your address" 
                           value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                </div>

                <div class="form-group full-width">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" 
                           placeholder="Enter your country" 
                           value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter password" 
                               required minlength="6">
                        <div class="password-hint">Minimum 6 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm password" 
                               required minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn-register">Create Account</button>
            </form>

            <div class="login-link">
                <p>Already have an account?</p>
                <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script src="js/register.js"></script>
</body>
</html>