<?php
session_start();
require_once 'conn.php';

// Fetch cars from database
$cars_query = "SELECT c.*, cs.status_name, o.office_name, o.city 
               FROM cars c 
               LEFT JOIN car_status cs ON c.current_status_id = cs.status_id 
               LEFT JOIN offices o ON c.office_id = o.office_id 
               WHERE cs.status_name IN ('Available', 'Rented')
               ORDER BY c.car_id DESC";
$cars_result = mysqli_query($conn, $cars_query);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRIVETuple - Car Rental</title>
    <link rel="stylesheet" href="css/index.css">

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
                <?php if ($is_logged_in): ?>
                    <?php if ($_SESSION['user_role'] == 'ADMIN'): ?>
                        <li><a href="admin_dashboard.php">Admin Panel</a></li>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php elseif ($_SESSION['user_role'] == 'STAFF'): ?>
                        <li><a href="staff_dashboard.php">Staff Dashboard</a></li>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="dashboard.php">My Profile</a></li>
                        <li><a href="#catalog">View Cars</a></li>
                        <li><a href="advanced_search.php">Advanced Search</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="#catalog">View Cars</a></li>
                    <li><a href="advanced_search.php">Advanced Search</a></li>
                <?php endif; ?>
            </ul>
            <div class="burger" id="burger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Find Your Perfect Ride</h1>
        <p>Explore our wide selection of quality vehicles</p>
        <a href="#catalog" class="btn-primary">Browse Cars</a>
    </section>

    <!-- Car Catalog -->
    <section class="catalog-section" id="catalog">
        <h2 class="section-title">Our Car Collection</h2>
        <div class="cars-grid">
            <?php if ($cars_result && mysqli_num_rows($cars_result) > 0): ?>
                <?php while ($car = mysqli_fetch_assoc($cars_result)): ?>
                    <div class="car-card" onclick="openModal(<?php echo htmlspecialchars(json_encode($car), ENT_QUOTES, 'UTF-8'); ?>)">
                        <div class="car-image">
                            <?php if (!empty($car['car_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($car['car_image_url']); ?>" alt="<?php echo htmlspecialchars($car['model']); ?>">
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                                    <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="car-info">
                            <div class="car-model"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></div>
                            <div class="car-details">Color: <?php echo htmlspecialchars($car['color'] ?? 'N/A'); ?></div>
                            <div class="car-price">
                                <?php if (!empty($car['offer_price'])): ?>
                                    <span style="text-decoration: line-through; color: #90a4ae; font-size: 18px;">$<?php echo number_format($car['daily_price'], 2); ?></span>
                                    <span style="color: #ffd700; font-weight: bold; font-size: 24px; margin-left: 10px;">$<?php echo number_format($car['offer_price'], 2); ?>/day</span>
                                    <span style="display: block; color: #ffd700; font-size: 12px; margin-top: 5px;">Special Offer!</span>
                                <?php else: ?>
                                    $<?php echo number_format($car['daily_price'], 2); ?>/day
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #90a4ae;">No cars available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal -->
    <div id="carModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="modalContent"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>Contact Us</h3>
                <p>📞 Phone: +1 (555) 123-4567</p>
                <p>✉️ Email: info@drivetuple.com</p>
                <p>📍 Address: 123 Main Street, City, Country</p>
            </div>
            <div class="footer-column">
                <h3>Important Links</h3>
                <a href="privacy_policy.php">Privacy Policy</a>
                <a href="terms_conditions.php">Terms & Conditions</a>
                <a href="about_us.php">About Us</a>
            </div>
            <div class="footer-column">
                <h3>Feedback</h3>
                <p>Share your experience with us!</p>
                <a href="feedback.php" class="btn-primary" style="margin-top: 10px; text-align: center;">Rate Cars</a>
            </div>
        </div>
        <div class="footer-bottom">
                <p>&copy; 2025 DRIVETuple. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/index.js"></script>
    <script>
        function bookCar(carId) {
            <?php if ($is_logged_in): ?>
                window.location.href = 'reservation.php?car_id=' + carId;
            <?php else: ?>
                alert('Please login to book a car.');
                window.location.href = 'login.php';
            <?php endif; ?>
        }
    </script>
</body>
</html>