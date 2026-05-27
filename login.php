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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
        // Log failed attempt
        $log_query = "INSERT INTO login_activity (email_attempted, login_result, failure_reason, ip_address, user_agent) VALUES (?, 'FAILED', 'Empty fields', ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_query);
        if ($log_stmt) {
            mysqli_stmt_bind_param($log_stmt, "sss", $email, $ip_address, $user_agent);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    } else {
        // Check user credentials
        $query = "SELECT user_id, full_name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    if ($user['is_active'] == 1) {
                        // Log successful login
                        $log_query = "INSERT INTO login_activity (user_id, email_attempted, login_result, ip_address, user_agent) VALUES (?, ?, 'SUCCESS', ?, ?)";
                        $log_stmt = mysqli_prepare($conn, $log_query);
                        if ($log_stmt) {
                            mysqli_stmt_bind_param($log_stmt, "isss", $user['user_id'], $email, $ip_address, $user_agent);
                            mysqli_stmt_execute($log_stmt);
                            mysqli_stmt_close($log_stmt);
                        }
                        
                        // Log to security_logs
                        $security_log_query = "INSERT INTO security_logs (user_id, action_type, action_details, ip_address, user_agent, log_level) VALUES (?, 'LOGIN', 'User logged in successfully', ?, ?, 'INFO')";
                        $security_log_stmt = mysqli_prepare($conn, $security_log_query);
                        if ($security_log_stmt) {
                            mysqli_stmt_bind_param($security_log_stmt, "iss", $user['user_id'], $ip_address, $user_agent);
                            mysqli_stmt_execute($security_log_stmt);
                            mysqli_stmt_close($security_log_stmt);
                        }
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        
                        // Redirect based on role
                        if ($user['role'] == 'ADMIN') {
                            header("Location: admin_dashboard.php");
                        } elseif ($user['role'] == 'STAFF') {
                            header("Location: staff_dashboard.php");
                        } else {
                            // Redirect based on return URL or default to dashboard
                            $redirect = $_GET['redirect'] ?? 'dashboard.php';
                            header("Location: " . $redirect);
                        }
                        exit();
                    } else {
                        $error = 'Your account has been deactivated. Please contact support.';
                        // Log failed attempt
                        $log_query = "INSERT INTO login_activity (user_id, email_attempted, login_result, failure_reason, ip_address, user_agent) VALUES (?, ?, 'FAILED', 'Account deactivated', ?, ?)";
                        $log_stmt = mysqli_prepare($conn, $log_query);
                        if ($log_stmt) {
                            mysqli_stmt_bind_param($log_stmt, "isss", $user['user_id'], $email, $ip_address, $user_agent);
                            mysqli_stmt_execute($log_stmt);
                            mysqli_stmt_close($log_stmt);
                        }
                    }
                } else {
                    $error = 'Invalid email or password.';
                    // Log failed attempt
                    $log_query = "INSERT INTO login_activity (user_id, email_attempted, login_result, failure_reason, ip_address, user_agent) VALUES (?, ?, 'FAILED', 'Invalid password', ?, ?)";
                    $log_stmt = mysqli_prepare($conn, $log_query);
                    if ($log_stmt) {
                        mysqli_stmt_bind_param($log_stmt, "isss", $user['user_id'], $email, $ip_address, $user_agent);
                        mysqli_stmt_execute($log_stmt);
                        mysqli_stmt_close($log_stmt);
                    }
                }
            } else {
                $error = 'Invalid email or password.';
                // Log failed attempt
                $log_query = "INSERT INTO login_activity (email_attempted, login_result, failure_reason, ip_address, user_agent) VALUES (?, 'FAILED', 'Email not found', ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                if ($log_stmt) {
                    mysqli_stmt_bind_param($log_stmt, "sss", $email, $ip_address, $user_agent);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }
            }
            mysqli_stmt_close($stmt);
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
    <title>Login - DRIVETuple</title>
    <link rel="stylesheet" href="css/login.css">

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
                <li><a href="register.php">Register</a></li>
            </ul>
            <div class="burger" id="burger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-box">
            <h1 class="login-title">Login</h1>
            <p class="login-subtitle">Welcome back! Please login to your account.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <div class="forgot-password">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>

            <div class="register-link">
                <p>Don't have an account?</p>
                <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>

    <script src="js/login.js"></script>
</body>
</html>