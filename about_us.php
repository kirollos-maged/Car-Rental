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
    <title>About Us - DRIVETuple</title>
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

        .hero-section {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid rgba(150, 150, 150, 0.2);
        }

        .hero-section img {
            max-width: 200px;
            margin-bottom: 20px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .feature-card {
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(150, 150, 150, 0.1);
        }

        .feature-card h3 {
            color: #e0e0e0;
            margin-bottom: 10px;
        }

        .contact-info {
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="logo.jpeg" alt="DRIVETuple Logo" style="height: 40px; width: auto;" onerror="this.style.display='none'">
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
            <div class="hero-section">
                <img src="logo.jpeg" alt="DRIVETuple Logo" onerror="this.style.display='none'">
                <h1>About DRIVETuple</h1>
                <p style="font-size: 18px; color: #d0d0d0;">Your Trusted Car Rental Partner</p>
            </div>

            <h2>Our Mission</h2>
            <p>DRIVETuple is committed to providing exceptional car rental services with a focus on customer satisfaction, safety, and convenience. We strive to make car rental accessible, affordable, and hassle-free for everyone.</p>

            <h2>Who We Are</h2>
            <p>Founded with a vision to revolutionize the car rental industry, DRIVETuple combines cutting-edge technology with personalized service. We understand that every journey is unique, and we're here to make yours memorable.</p>

            <h2>What We Offer</h2>
            <div class="features">
                <div class="feature-card">
                    <h3>🚗 Wide Selection</h3>
                    <p>Choose from our diverse fleet of well-maintained vehicles to suit your needs and budget.</p>
                </div>
                <div class="feature-card">
                    <h3>📍 Multiple Locations</h3>
                    <p>Convenient pickup and drop-off locations across multiple cities and countries.</p>
                </div>
                <div class="feature-card">
                    <h3>💰 Competitive Pricing</h3>
                    <p>Transparent pricing with special offers and discounts for our valued customers.</p>
                </div>
                <div class="feature-card">
                    <h3>🔒 Secure & Safe</h3>
                    <p>All vehicles undergo regular maintenance and safety checks for your peace of mind.</p>
                </div>
                <div class="feature-card">
                    <h3>📱 Easy Booking</h3>
                    <p>Simple online reservation system with instant confirmation and 24/7 support.</p>
                </div>
                <div class="feature-card">
                    <h3>⭐ Customer First</h3>
                    <p>Dedicated customer service team ready to assist you at every step of your journey.</p>
                </div>
            </div>

            <h2>Our Values</h2>
            <p>At DRIVETuple, we believe in:</p>
            <ul>
                <li><strong>Integrity:</strong> Honest and transparent business practices</li>
                <li><strong>Excellence:</strong> Striving for the highest quality in everything we do</li>
                <li><strong>Innovation:</strong> Continuously improving our services through technology</li>
                <li><strong>Customer Focus:</strong> Putting our customers at the heart of our operations</li>
                <li><strong>Sustainability:</strong> Committed to environmental responsibility</li>
            </ul>

            <h2>Why Choose DRIVETuple?</h2>
            <p>With years of experience in the car rental industry, we've built a reputation for reliability, excellent service, and customer satisfaction. Our team of professionals is dedicated to ensuring you have a smooth and enjoyable rental experience.</p>

            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>We'd love to hear from you! Contact us at:</p>
                <p><strong>Email:</strong> info@drivetuple.com<br>
                <strong>Phone:</strong> +1 (555) 123-4567<br>
                <strong>Address:</strong> DRIVETuple Headquarters</p>
                <p style="margin-top: 15px;">
                    <a href="privacy_policy.php" style="color: #64b5f6; margin-right: 20px;">Privacy Policy</a>
                    <a href="terms_conditions.php" style="color: #64b5f6;">Terms & Conditions</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

