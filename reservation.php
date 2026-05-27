<?php
session_start();
require_once 'conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Get car_id from URL
$car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;

if ($car_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch car details
$car_query = "SELECT c.*, cs.status_name, o.office_name, o.city 
              FROM cars c 
              LEFT JOIN car_status cs ON c.current_status_id = cs.status_id 
              LEFT JOIN offices o ON c.office_id = o.office_id 
              WHERE c.car_id = $car_id";
$car_result = mysqli_query($conn, $car_query);

if (!$car_result || mysqli_num_rows($car_result) == 0) {
    header("Location: index.php");
    exit();
}

$car = mysqli_fetch_assoc($car_result);

// Handle error messages
$error_message = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'missing_fields') {
        $error_message = 'Please fill in all required details.';
    } elseif ($_GET['error'] == 'unavailable') {
        $error_message = 'Sorry, this car is currently unavailable.';
    }
}

// Fetch offices for dropdowns
$pickup_offices_query = "SELECT DISTINCT o.* FROM offices o JOIN cars c ON o.office_id = c.office_id WHERE c.current_status_id = 1 ORDER BY o.city, o.office_name";
$pickup_offices_result = mysqli_query($conn, $pickup_offices_query);

$return_offices_query = "SELECT * FROM offices ORDER BY city, office_name";
$return_offices_result = mysqli_query($conn, $return_offices_query);

// Fetch payment methods
$payment_methods_query = "SELECT * FROM payment_methods ORDER BY method_id";
$payment_methods_result = mysqli_query($conn, $payment_methods_query);

// Check availability
$is_available = ($car['status_name'] === 'Available');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation - Car Rental</title>
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
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .reservation-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .car-summary {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        .car-summary h2 {
            color: #e0e0e0;
            margin-bottom: 20px;
        }

        .car-info-item {
            padding: 10px 0;
            border-bottom: 1px solid rgba(150, 150, 150, 0.1);
        }

        .car-info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #90a4ae;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #e0e0e0;
            font-size: 16px;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-available {
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            color: #0a0a0a;
        }

        .status-unavailable {
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            color: white;
        }

        .reservation-form {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        .reservation-form h2 {
            color: #e0e0e0;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #b0bec5;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(150, 150, 150, 0.2);
            border-radius: 8px;
            font-size: 16px;
            background: rgba(0, 0, 0, 0.3);
            color: #e0e0e0;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #d0d0d0;
            box-shadow: 0 0 10px rgba(200, 200, 200, 0.2);
        }

        .form-group input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .error-message {
            background: rgba(255, 68, 68, 0.2);
            color: #ff6b6b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 68, 68, 0.5);
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #f5f5f5;
            color: #1a1a1a;
            border: none;
            border-radius: 30px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-submit:hover:not(:disabled) {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .price-calculation {
            background: rgba(150, 150, 150, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .price-row.total {
            font-size: 20px;
            font-weight: bold;
            padding-top: 10px;
            border-top: 2px solid rgba(150, 150, 150, 0.3);
            margin-top: 10px;
        }

        @media screen and (max-width: 768px) {
            .reservation-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🚗 CarRental</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="reservation-container">
            <!-- Car Summary -->
            <div class="car-summary">
                <h2>Car Details</h2>
                <div class="car-info-item">
                    <div class="info-label">Brand & Model</div>
                    <div class="info-value"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></div>
                </div>
                <div class="car-info-item">
                    <div class="info-label">Type</div>
                    <div class="info-value"><?php echo htmlspecialchars($car['car_type'] ?? 'N/A'); ?></div>
                </div>
                <div class="car-info-item">
                    <div class="info-label">Color</div>
                    <div class="info-value"><?php echo htmlspecialchars($car['color'] ?? 'N/A'); ?></div>
                </div>
                <div class="car-info-item">
                    <div class="info-label">Year</div>
                    <div class="info-value"><?php echo htmlspecialchars($car['year']); ?></div>
                </div>
                <div class="car-info-item">
                    <div class="info-label">Transmission</div>
                    <div class="info-value"><?php echo htmlspecialchars($car['transmission_type'] ?? 'N/A'); ?></div>
                </div>
                <div class="car-info-item">
                    <div class="info-label">Fuel Type</div>
                    <div class="info-value"><?php echo htmlspecialchars($car['fuel_type'] ?? 'N/A'); ?></div>
                </div>
                <div class="car-info-item">
                    <div class="info-label">Daily Price</div>
                    <div class="info-value">$<?php echo number_format($car['daily_price'], 2); ?>/day</div>
                </div>
                <div class="car-info-item">
                    <div class="info-label">Current Location</div>
                    <div class="info-value"><?php echo htmlspecialchars($car['office_name'] . ', ' . $car['city']); ?></div>
                </div>
                <div class="status-badge <?php echo $is_available ? 'status-available' : 'status-unavailable'; ?>">
                    <?php echo htmlspecialchars($car['status_name']); ?>
                </div>
            </div>

            <!-- Reservation Form -->
            <div class="reservation-form">
                <h2>Reservation Details</h2>
                <div class="error-message<?php echo !empty($error_message) ? ' show' : ''; ?>" id="errorMessage">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <form id="reservationForm" method="POST" action="process_reservation.php">
                    <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                    
                    <div class="form-group">
                        <label for="pickup_office_id">Pickup Location *</label>
                        <select id="pickup_office_id" name="pickup_office_id" required>
                            <option value="">Select Pickup Location</option>
                            <?php 
                            mysqli_data_seek($pickup_offices_result, 0);
                            while ($office = mysqli_fetch_assoc($pickup_offices_result)): ?>
                                <option value="<?php echo $office['office_id']; ?>">
                                    <?php echo htmlspecialchars($office['office_name'] . ', ' . $office['city']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="return_office_id">Return Location *</label>
                        <select id="return_office_id" name="return_office_id" required>
                            <option value="">Select Return Location</option>
                            <?php 
                            mysqli_data_seek($return_offices_result, 0);
                            while ($office = mysqli_fetch_assoc($return_offices_result)): ?>
                                <option value="<?php echo $office['office_id']; ?>">
                                    <?php echo htmlspecialchars($office['office_name'] . ', ' . $office['city']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Pick-up Date *</label>
                        <input type="datetime-local" id="start_date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">Return Date *</label>
                        <input type="datetime-local" id="end_date" name="end_date" required>
                    </div>

                    <div class="price-calculation">
                        <div class="price-row">
                            <span>Daily Rate:</span>
                            <span id="dailyRate">$<?php echo number_format($car['daily_price'], 2); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Number of Days:</span>
                            <span id="numDays">0</span>
                        </div>
                        <div class="price-row total">
                            <span>Total Price:</span>
                            <span id="totalPrice">$0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" <?php echo !$is_available ? 'disabled' : ''; ?>>
                        Confirm Reservation
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const dailyPrice = <?php echo $car['daily_price']; ?>;
        const isAvailable = <?php echo $is_available ? 'true' : 'false'; ?>;
        const carId = <?php echo $car_id; ?>;

        // Set minimum date to today
        const today = new Date().toISOString().slice(0, 16);
        document.getElementById('start_date').min = today;
        document.getElementById('end_date').min = today;

        // Update end date minimum when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            endDate.min = this.value;
            if (endDate.value && endDate.value < this.value) {
                endDate.value = this.value;
            }
            calculatePrice();
        });

        document.getElementById('end_date').addEventListener('change', calculatePrice);

        function calculatePrice() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const numDays = diffDays > 0 ? diffDays : 1;
                
                document.getElementById('numDays').textContent = numDays;
                document.getElementById('totalPrice').textContent = '$' + (dailyPrice * numDays).toFixed(2);
            } else {
                document.getElementById('numDays').textContent = '0';
                document.getElementById('totalPrice').textContent = '$0.00';
            }
        }

        // Availability validation before submission
        document.getElementById('reservationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!isAvailable) {
                document.getElementById('errorMessage').classList.add('show');
                return false;
            }

            // Check availability via AJAX
            try {
                const response = await fetch('check_availability.php?car_id=' + carId);
                const data = await response.json();
                
                if (data.status !== 'Available') {
                    document.getElementById('errorMessage').classList.add('show');
                    document.getElementById('submitBtn').disabled = true;
                    return false;
                }

                // If available, submit the form
                this.submit();
            } catch (error) {
                console.error('Error checking availability:', error);
                // Proceed with submission if check fails
                this.submit();
            }
        });
    </script>
</body>
</html>