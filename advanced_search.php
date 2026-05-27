<?php
session_start();
require_once 'conn.php';

// Fetch offices for filter
$offices_query = "SELECT office_id, office_name, city FROM offices ORDER BY city, office_name";
$offices_result = mysqli_query($conn, $offices_query);

// Fetch unique car types
$car_types_query = "SELECT DISTINCT car_type FROM cars WHERE car_type IS NOT NULL ORDER BY car_type";
$car_types_result = mysqli_query($conn, $car_types_query);

// Fetch unique values for dropdowns
$brands_query = "SELECT DISTINCT brand FROM cars WHERE brand IS NOT NULL ORDER BY brand";
$brands_result = mysqli_query($conn, $brands_query);

$fuel_types_query = "SELECT DISTINCT fuel_type FROM cars WHERE fuel_type IS NOT NULL ORDER BY fuel_type";
$fuel_types_result = mysqli_query($conn, $fuel_types_query);

$transmission_types_query = "SELECT DISTINCT transmission_type FROM cars WHERE transmission_type IS NOT NULL ORDER BY transmission_type";
$transmission_types_result = mysqli_query($conn, $transmission_types_query);

// Get filter values from GET parameters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$office_id = $_GET['office_id'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$car_type = $_GET['car_type'] ?? '';
$brand = $_GET['brand'] ?? '';
$model = $_GET['model'] ?? '';
$color = $_GET['color'] ?? '';
$min_year = $_GET['min_year'] ?? '';
$max_year = $_GET['max_year'] ?? '';
$min_engine_size = $_GET['min_engine_size'] ?? '';
$max_engine_size = $_GET['max_engine_size'] ?? '';
$fuel_type = $_GET['fuel_type'] ?? '';
$transmission_type = $_GET['transmission_type'] ?? '';
$plate_id = $_GET['plate_id'] ?? '';
$has_offer = isset($_GET['has_offer']) && $_GET['has_offer'] == '1' ? true : false;

// Build search query
$search_query = "SELECT c.*, cs.status_name, o.office_name, o.city 
                 FROM cars c 
                 LEFT JOIN car_status cs ON c.current_status_id = cs.status_id 
                 LEFT JOIN offices o ON c.office_id = o.office_id 
                 WHERE cs.status_name IN ('Available', 'Rented')";

$conditions = [];

if (!empty($start_date) && !empty($end_date)) {
    // Check if car is available in the date range
    $conditions[] = "c.car_id NOT IN (
        SELECT car_id FROM reservation 
        WHERE status != 'Cancelled' 
        AND (
            (start_date <= '$start_date' AND end_date >= '$start_date') OR
            (start_date <= '$end_date' AND end_date >= '$end_date') OR
            (start_date >= '$start_date' AND end_date <= '$end_date')
        )
    )";
}

if (!empty($office_id)) {
    $conditions[] = "c.office_id = " . intval($office_id);
}

if (!empty($min_price)) {
    $conditions[] = "c.daily_price >= " . floatval($min_price);
}

if (!empty($max_price)) {
    $conditions[] = "c.daily_price <= " . floatval($max_price);
}

if (!empty($car_type)) {
    $conditions[] = "c.car_type = '" . mysqli_real_escape_string($conn, $car_type) . "'";
}

if (!empty($brand)) {
    $conditions[] = "c.brand LIKE '%" . mysqli_real_escape_string($conn, $brand) . "%'";
}

if (!empty($model)) {
    $conditions[] = "c.model LIKE '%" . mysqli_real_escape_string($conn, $model) . "%'";
}

if (!empty($color)) {
    $conditions[] = "c.color LIKE '%" . mysqli_real_escape_string($conn, $color) . "%'";
}

if (!empty($min_year)) {
    $conditions[] = "c.year >= " . intval($min_year);
}

if (!empty($max_year)) {
    $conditions[] = "c.year <= " . intval($max_year);
}

if (!empty($min_engine_size)) {
    $conditions[] = "c.engine_size >= " . floatval($min_engine_size);
}

if (!empty($max_engine_size)) {
    $conditions[] = "c.engine_size <= " . floatval($max_engine_size);
}

if (!empty($fuel_type)) {
    $conditions[] = "c.fuel_type = '" . mysqli_real_escape_string($conn, $fuel_type) . "'";
}

if (!empty($transmission_type)) {
    $conditions[] = "c.transmission_type = '" . mysqli_real_escape_string($conn, $transmission_type) . "'";
}

if (!empty($plate_id)) {
    $conditions[] = "c.plate_id LIKE '%" . mysqli_real_escape_string($conn, $plate_id) . "%'";
}

if ($has_offer) {
    $conditions[] = "c.offer_price IS NOT NULL AND c.offer_price > 0";
}

if (!empty($conditions)) {
    $search_query .= " AND " . implode(" AND ", $conditions);
}

$search_query .= " ORDER BY c.daily_price ASC";
$search_result = mysqli_query($conn, $search_query);

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - Car Rental</title>
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

        /* Navigation Bar (Same as index.php) */
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
            position: relative;
            padding: 5px 0;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #d0d0d0;
            transition: width 0.3s;
        }

        .nav-menu a:hover {
            color: #e0e0e0;
        }

        .nav-menu a:hover::after {
            width: 100%;
        }

        .burger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 5px;
        }

        .burger span {
            width: 25px;
            height: 3px;
            background: #d0d0d0;
            transition: 0.3s;
        }

        /* Search Section */
        .search-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .search-form {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            margin-bottom: 30px;
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        .form-title {
            font-size: 28px;
            color: #e0e0e0;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #b0bec5;
        }

        .form-group input,
        .form-group select {
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

        .btn-search {
            background: #f5f5f5;
            color: #1a1a1a;
            padding: 12px 40px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-search:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        .btn-reset {
            background: rgba(117, 117, 117, 0.3);
            color: #b0bec5;
            padding: 12px 40px;
            border: 1px solid rgba(117, 117, 117, 0.5);
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 10px;
            transition: all 0.3s;
        }

        .btn-reset:hover {
            background: rgba(117, 117, 117, 0.5);
            border-color: rgba(117, 117, 117, 0.8);
            transform: translateY(-2px);
        }

        /* Results Section */
        .results-section {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
        }

        .results-title {
            font-size: 24px;
            color: #e0e0e0;
            margin-bottom: 25px;
        }

        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .car-card {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            border: 1px solid rgba(150, 150, 150, 0.2);
            position: relative;
        }

        .car-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(200, 200, 200, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .car-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
            border-color: rgba(200, 200, 200, 0.4);
        }

        .car-card:hover::before {
            opacity: 1;
        }

        .car-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .car-info {
            padding: 25px;
            position: relative;
            z-index: 1;
        }

        .car-model {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #e0e0e0;
        }

        .car-details {
            color: #90a4ae;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .car-price {
            font-size: 24px;
            font-weight: bold;
            color: #d0d0d0;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #90a4ae;
            font-size: 18px;
        }

        @media screen and (max-width: 768px) {
            .burger {
                display: flex;
            }

            .nav-menu {
                position: fixed;
                left: -100%;
                top: 70px;
                flex-direction: column;
                background: rgba(10, 10, 10, 0.98);
                backdrop-filter: blur(20px);
                width: 100%;
                text-align: center;
                transition: 0.3s;
                box-shadow: 0 10px 27px rgba(0, 255, 255, 0.2);
                padding: 20px 0;
                gap: 20px;
                border-top: 1px solid rgba(0, 255, 255, 0.2);
            }

            .nav-menu.active {
                left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .cars-grid {
                grid-template-columns: 1fr;
            }
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
                        <li><a href="index.php#catalog">View Cars</a></li>
                        <li><a href="advanced_search.php">Advanced Search</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="index.php#catalog">View Cars</a></li>
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

    <div class="search-container">
        <div class="search-form">
            <h2 class="form-title">Advanced Search</h2>
            <form method="GET" action="advanced_search.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="form-group">
                        <label for="office_id">Office Location</label>
                        <select id="office_id" name="office_id">
                            <option value="">All Offices</option>
                            <?php 
                            mysqli_data_seek($offices_result, 0);
                            while ($office = mysqli_fetch_assoc($offices_result)): ?>
                                <option value="<?php echo $office['office_id']; ?>" <?php echo $office_id == $office['office_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($office['office_name'] . ', ' . $office['city']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="car_type">Car Type</label>
                        <select id="car_type" name="car_type">
                            <option value="">All Types</option>
                            <?php 
                            mysqli_data_seek($car_types_result, 0);
                            while ($type = mysqli_fetch_assoc($car_types_result)): ?>
                                <option value="<?php echo htmlspecialchars($type['car_type']); ?>" <?php echo $car_type == $type['car_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['car_type']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($brand); ?>" placeholder="e.g., Toyota">
                    </div>
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($model); ?>" placeholder="e.g., Camry">
                    </div>
                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="text" id="color" name="color" value="<?php echo htmlspecialchars($color); ?>" placeholder="e.g., Red">
                    </div>
                    <div class="form-group">
                        <label for="plate_id">Plate ID</label>
                        <input type="text" id="plate_id" name="plate_id" value="<?php echo htmlspecialchars($plate_id); ?>" placeholder="e.g., ABC-123">
                    </div>
                    <div class="form-group">
                        <label for="min_year">Min Year</label>
                        <input type="number" id="min_year" name="min_year" value="<?php echo htmlspecialchars($min_year); ?>" min="1900" max="2099" placeholder="e.g., 2020">
                    </div>
                    <div class="form-group">
                        <label for="max_year">Max Year</label>
                        <input type="number" id="max_year" name="max_year" value="<?php echo htmlspecialchars($max_year); ?>" min="1900" max="2099" placeholder="e.g., 2024">
                    </div>
                    <div class="form-group">
                        <label for="min_engine_size">Min Engine Size (L)</label>
                        <input type="number" id="min_engine_size" name="min_engine_size" value="<?php echo htmlspecialchars($min_engine_size); ?>" step="0.1" min="0" placeholder="e.g., 1.5">
                    </div>
                    <div class="form-group">
                        <label for="max_engine_size">Max Engine Size (L)</label>
                        <input type="number" id="max_engine_size" name="max_engine_size" value="<?php echo htmlspecialchars($max_engine_size); ?>" step="0.1" min="0" placeholder="e.g., 3.0">
                    </div>
                    <div class="form-group">
                        <label for="fuel_type">Fuel Type</label>
                        <select id="fuel_type" name="fuel_type">
                            <option value="">All Fuel Types</option>
                            <?php 
                            mysqli_data_seek($fuel_types_result, 0);
                            while ($fuel = mysqli_fetch_assoc($fuel_types_result)): ?>
                                <option value="<?php echo htmlspecialchars($fuel['fuel_type']); ?>" <?php echo $fuel_type == $fuel['fuel_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($fuel['fuel_type']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="transmission_type">Transmission</label>
                        <select id="transmission_type" name="transmission_type">
                            <option value="">All Transmissions</option>
                            <?php 
                            mysqli_data_seek($transmission_types_result, 0);
                            while ($trans = mysqli_fetch_assoc($transmission_types_result)): ?>
                                <option value="<?php echo htmlspecialchars($trans['transmission_type']); ?>" <?php echo $transmission_type == $trans['transmission_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($trans['transmission_type']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="min_price">Min Price ($/day)</label>
                        <input type="number" id="min_price" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="max_price">Max Price ($/day)</label>
                        <input type="number" id="max_price" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="has_offer" name="has_offer" value="1" <?php echo $has_offer ? 'checked' : ''; ?>>
                            Only cars with offers
                        </label>
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-search">Search</button>
                    <button type="button" class="btn-reset" onclick="resetForm()">Reset</button>
                </div>
            </form>
        </div>

        <?php if (!empty($_GET)): ?>
        <div class="results-section">
            <h2 class="results-title">Search Results</h2>
            <?php if ($search_result && mysqli_num_rows($search_result) > 0): ?>
                <div class="cars-grid">
                    <?php while ($car = mysqli_fetch_assoc($search_result)): ?>
                        <div class="car-card" onclick="window.location.href='index.php?car_id=<?php echo $car['car_id']; ?>#catalog'">
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
                                <div class="car-details">Location: <?php echo htmlspecialchars($car['office_name'] ?? 'N/A'); ?></div>
                                <div class="car-price">
                                    <?php if (!empty($car['offer_price'])): ?>
                                        <span style="text-decoration: line-through; color: #90a4ae; font-size: 18px;">$<?php echo number_format($car['daily_price'], 2); ?></span>
                                        <span style="color: #ffd700; font-weight: bold; font-size: 24px; margin-left: 10px;">$<?php echo number_format($car['offer_price'], 2); ?>/day</span>
                                    <?php else: ?>
                                        $<?php echo number_format($car['daily_price'], 2); ?>/day
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-results">No cars found matching your criteria. Please try different filters.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Burger Menu Toggle
        const burger = document.getElementById('burger');
        const navMenu = document.getElementById('navMenu');

        burger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!burger.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('active');
            }
        });

        // Set minimum end date to start date
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            endDate.min = this.value;
            if (endDate.value && endDate.value < this.value) {
                endDate.value = this.value;
            }
        });

        // Reset form function
        function resetForm() {
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('office_id').value = '';
            document.getElementById('car_type').value = '';
            document.getElementById('brand').value = '';
            document.getElementById('model').value = '';
            document.getElementById('color').value = '';
            document.getElementById('plate_id').value = '';
            document.getElementById('min_year').value = '';
            document.getElementById('max_year').value = '';
            document.getElementById('min_engine_size').value = '';
            document.getElementById('max_engine_size').value = '';
            document.getElementById('fuel_type').value = '';
            document.getElementById('transmission_type').value = '';
            document.getElementById('min_price').value = '';
            document.getElementById('max_price').value = '';
            document.getElementById('has_offer').checked = false;
            // Submit form to reload page with empty filters
            window.location.href = 'advanced_search.php';
        }
    </script>
</body>
</html>

