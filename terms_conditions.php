<?php
session_start();
require_once 'conn.php';
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - DRIVETuple</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
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
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .content {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        h1 {
            font-size: 36px;
            color: #e0e0e0;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 24px;
            color: #e0e0e0;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        p {
            color: #b0bec5;
            margin-bottom: 15px;
        }

        ul {
            color: #b0bec5;
            margin-left: 30px;
            margin-bottom: 15px;
        }

        li {
            margin-bottom: 8px;
        }

        .last-updated {
            color: #90a4ae;
            font-size: 14px;
            font-style: italic;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="uploads/logo/logo.png" alt="DRIVETuple Logo" style="height: 40px; width: auto;" onerror="this.style.display='none'">
                <span>DRIVETuple</span>
            </a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="content">
            <h1>Terms & Conditions</h1>
            <p class="last-updated">Last Updated: December 2025</p>

            <h2>1. Agreement to Terms</h2>
            <p>By accessing and using DRIVETuple's car rental service, you agree to be bound by these Terms and Conditions. If you do not agree, please do not use our service.</p>

            <h2>2. Eligibility</h2>
            <p>To rent a car, you must:</p>
            <ul>
                <li>Be at least 21 years old</li>
                <li>Hold a valid driver's license</li>
                <li>Provide valid identification documents</li>
                <li>Have a valid payment method</li>
                <li>Complete your customer profile with required details</li>
            </ul>

            <h2>3. Reservations</h2>
            <p>Reservations are subject to availability and confirmation. We reserve the right to cancel or modify reservations due to:</p>
            <ul>
                <li>Unavailability of the requested vehicle</li>
                <li>Failure to provide required documentation</li>
                <li>Payment issues</li>
                <li>Violation of these terms</li>
            </ul>

            <h2>4. Rental Period and Fees</h2>
            <p>Rental fees are calculated based on the daily rate and rental period. Additional charges may apply for:</p>
            <ul>
                <li>Late returns</li>
                <li>Additional mileage</li>
                <li>Damage to the vehicle</li>
                <li>Fuel charges</li>
                <li>Traffic violations</li>
            </ul>

            <h2>5. Vehicle Use</h2>
            <p>You agree to:</p>
            <ul>
                <li>Use the vehicle only for lawful purposes</li>
                <li>Not allow unauthorized drivers</li>
                <li>Return the vehicle in the same condition</li>
                <li>Comply with all traffic laws</li>
                <li>Not use the vehicle for commercial purposes without permission</li>
            </ul>

            <h2>6. Liability and Insurance</h2>
            <p>You are responsible for any damage to the vehicle during the rental period. We recommend purchasing appropriate insurance coverage.</p>

            <h2>7. Cancellation Policy</h2>
            <p>Cancellations must be made at least 24 hours before the rental start time to receive a full refund. Late cancellations may incur fees.</p>

            <h2>8. Payment</h2>
            <p>Payment is required at the time of reservation. We accept cash, card, and online payment methods. All prices are in USD unless otherwise stated.</p>

            <h2>9. Limitation of Liability</h2>
            <p>DRIVETuple is not liable for any indirect, incidental, or consequential damages arising from the use of our service.</p>

            <h2>10. Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. Continued use of our service constitutes acceptance of the modified terms.</p>

            <h2>11. Contact Information</h2>
            <p>For questions about these Terms & Conditions, contact us at:</p>
            <p>Email: legal@drivetuple.com<br>
            Address: DRIVETuple Headquarters</p>
        </div>
    </div>
</body>
</html>

