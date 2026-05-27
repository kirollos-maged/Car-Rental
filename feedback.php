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
$car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;
$search_car = isset($_GET['search_car']) ? trim($_GET['search_car']) : '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_feedback') {
    $car_id_feedback = intval($_POST['car_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment'] ?? '');
    $car_image_url = trim($_POST['car_image_url'] ?? '');
    
    // Check if customer has completed a reservation for this car
    $reservation_check = "SELECT reservation_id FROM reservation 
                         WHERE customer_id = ? AND car_id = ? AND status = 'Completed' 
                         LIMIT 1";
    $check_stmt = mysqli_prepare($conn, $reservation_check);
    mysqli_stmt_bind_param($check_stmt, "ii", $customer_id, $car_id_feedback);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        $error = 'You can only add feedback for cars you have reserved and completed a rental with.';
        mysqli_stmt_close($check_stmt);
    } else {
        mysqli_stmt_close($check_stmt);
        
        // Check if feedback already exists
        $existing_check = "SELECT feedback_id FROM feedback WHERE customer_id = ? AND car_id = ?";
        $existing_stmt = mysqli_prepare($conn, $existing_check);
        mysqli_stmt_bind_param($existing_stmt, "ii", $customer_id, $car_id_feedback);
        mysqli_stmt_execute($existing_stmt);
        $existing_result = mysqli_stmt_get_result($existing_stmt);
        
        if (mysqli_num_rows($existing_result) > 0) {
            // Update existing feedback
            $update_query = "UPDATE feedback SET rating = ?, comment = ?, car_image_url = ? WHERE customer_id = ? AND car_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            $empty_image = empty($car_image_url) ? null : $car_image_url;
            mysqli_stmt_bind_param($update_stmt, "issii", $rating, $comment, $empty_image, $customer_id, $car_id_feedback);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $success = 'Feedback updated successfully!';
            } else {
                $error = 'Failed to update feedback.';
            }
            mysqli_stmt_close($update_stmt);
        } else {
            // Insert new feedback
            $insert_query = "INSERT INTO feedback (customer_id, car_id, rating, comment, car_image_url) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            $empty_image = empty($car_image_url) ? null : $car_image_url;
            mysqli_stmt_bind_param($insert_stmt, "iiiss", $customer_id, $car_id_feedback, $rating, $comment, $empty_image);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $success = 'Feedback submitted successfully!';
            } else {
                $error = 'Failed to submit feedback.';
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($existing_stmt);
    }
}

// Fetch cars for search
$cars_for_search = [];
if (!empty($search_car)) {
    $search_query = "SELECT car_id, brand, model, plate_id FROM cars 
                     WHERE brand LIKE ? OR model LIKE ? OR plate_id LIKE ?
                     ORDER BY brand, model";
    $search_stmt = mysqli_prepare($conn, $search_query);
    $search_pattern = '%' . $search_car . '%';
    mysqli_stmt_bind_param($search_stmt, "sss", $search_pattern, $search_pattern, $search_pattern);
    mysqli_stmt_execute($search_stmt);
    $search_result = mysqli_stmt_get_result($search_stmt);
    while ($row = mysqli_fetch_assoc($search_result)) {
        $cars_for_search[] = $row;
    }
    mysqli_stmt_close($search_stmt);
}

// Fetch feedback for selected car or all feedback
if ($car_id > 0) {
    $feedback_query = "SELECT f.*, u.full_name, c.brand, c.model 
                       FROM feedback f
                       JOIN users u ON f.customer_id = u.user_id
                       JOIN cars c ON f.car_id = c.car_id
                       WHERE f.car_id = ?
                       ORDER BY f.feedback_date DESC";
    $feedback_stmt = mysqli_prepare($conn, $feedback_query);
    mysqli_stmt_bind_param($feedback_stmt, "i", $car_id);
    mysqli_stmt_execute($feedback_stmt);
    $feedback_result = mysqli_stmt_get_result($feedback_stmt);
} else {
    $feedback_query = "SELECT f.*, u.full_name, c.brand, c.model 
                       FROM feedback f
                       JOIN users u ON f.customer_id = u.user_id
                       JOIN cars c ON f.car_id = c.car_id
                       ORDER BY f.feedback_date DESC
                       LIMIT 50";
    $feedback_result = mysqli_query($conn, $feedback_query);
}

// Get user's existing feedback for selected car
$user_feedback = null;
if ($car_id > 0) {
    $user_feedback_query = "SELECT * FROM feedback WHERE customer_id = ? AND car_id = ?";
    $user_feedback_stmt = mysqli_prepare($conn, $user_feedback_query);
    mysqli_stmt_bind_param($user_feedback_stmt, "ii", $customer_id, $car_id);
    mysqli_stmt_execute($user_feedback_stmt);
    $user_feedback_result = mysqli_stmt_get_result($user_feedback_stmt);
    $user_feedback = mysqli_fetch_assoc($user_feedback_result);
    mysqli_stmt_close($user_feedback_stmt);
}

// Get car details if car_id is set
$car_details = null;
if ($car_id > 0) {
    $car_query = "SELECT c.*, cs.status_name FROM cars c 
                  LEFT JOIN car_status cs ON c.current_status_id = cs.status_id
                  WHERE c.car_id = ?";
    $car_stmt = mysqli_prepare($conn, $car_query);
    mysqli_stmt_bind_param($car_stmt, "i", $car_id);
    mysqli_stmt_execute($car_stmt);
    $car_result = mysqli_stmt_get_result($car_stmt);
    $car_details = mysqli_fetch_assoc($car_result);
    mysqli_stmt_close($car_stmt);
    
    // Check if user can add feedback (has completed reservation)
    $can_add_feedback = false;
    $can_add_query = "SELECT reservation_id FROM reservation 
                     WHERE customer_id = ? AND car_id = ? AND status = 'Completed' 
                     LIMIT 1";
    $can_add_stmt = mysqli_prepare($conn, $can_add_query);
    mysqli_stmt_bind_param($can_add_stmt, "ii", $customer_id, $car_id);
    mysqli_stmt_execute($can_add_stmt);
    $can_add_result = mysqli_stmt_get_result($can_add_stmt);
    $can_add_feedback = mysqli_num_rows($can_add_result) > 0;
    mysqli_stmt_close($can_add_stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - DRIVETuple</title>
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 36px;
            color: #e0e0e0;
            margin-bottom: 10px;
        }

        .search-section, .feedback-section {
            background: linear-gradient(135deg, rgba(40, 40, 40, 0.9) 0%, rgba(30, 30, 30, 0.9) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(150, 150, 150, 0.2);
            margin-bottom: 30px;
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
            min-height: 100px;
            resize: vertical;
        }

        .rating-stars {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .star {
            font-size: 30px;
            color: #666;
            cursor: pointer;
            transition: all 0.2s;
        }

        .star:hover, .star.active {
            color: #ffd700;
        }

        .btn-submit {
            background: #f5f5f5;
            color: #1a1a1a;
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: #ffffff;
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .feedback-list {
            margin-top: 30px;
        }

        .feedback-item {
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid rgba(150, 150, 150, 0.1);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .feedback-author {
            font-weight: bold;
            color: #e0e0e0;
        }

        .feedback-rating {
            color: #ffd700;
        }

        .feedback-date {
            color: #90a4ae;
            font-size: 14px;
        }

        .feedback-comment {
            color: #b0bec5;
            margin-top: 10px;
        }

        .car-search-results {
            margin-top: 15px;
        }

        .car-result-item {
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .car-result-item:hover {
            background: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="uploads/logo/logo.png" alt="DRIVETuple Logo" onerror="this.style.display='none'">
                <span>DRIVETuple</span>
            </a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="advanced_search.php">Advanced Search</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
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
        <div class="page-header">
            <h1>Feedback & Ratings</h1>
            <p>Share your experience and help others make informed decisions</p>
        </div>

        <!-- Search Car Section -->
        <div class="search-section">
            <h2 style="color: #e0e0e0; margin-bottom: 20px;">Search Car Feedback</h2>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="search_car">Search by Brand, Model, or Plate ID</label>
                    <input type="text" id="search_car" name="search_car" value="<?php echo htmlspecialchars($search_car); ?>" placeholder="e.g., Toyota, Camry, ABC-123">
                </div>
                <button type="submit" class="btn-submit">Search</button>
            </form>

            <?php if (!empty($cars_for_search)): ?>
                <div class="car-search-results">
                    <h3 style="color: #e0e0e0; margin-top: 20px; margin-bottom: 10px;">Select a car:</h3>
                    <?php foreach ($cars_for_search as $car): ?>
                        <div class="car-result-item" onclick="window.location.href='feedback.php?car_id=<?php echo $car['car_id']; ?>'">
                            <strong><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></strong> - <?php echo htmlspecialchars($car['plate_id']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add Feedback Section -->
        <?php if ($car_id > 0 && $car_details): ?>
            <div class="feedback-section">
                <h2 style="color: #e0e0e0; margin-bottom: 20px;">
                    Feedback for <?php echo htmlspecialchars($car_details['brand'] . ' ' . $car_details['model']); ?>
                </h2>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($can_add_feedback): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="submit_feedback">
                        <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                        
                        <div class="form-group">
                            <label>Rating <span style="color: #ff6b6b;">*</span></label>
                            <div class="rating-stars" id="ratingStars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star" data-rating="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">★</span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="rating" value="<?php echo $user_feedback['rating'] ?? 0; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="comment">Comment</label>
                            <textarea id="comment" name="comment" placeholder="Share your experience..."><?php echo htmlspecialchars($user_feedback['comment'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="car_image_url">Car Image URL (optional)</label>
                            <input type="url" id="car_image_url" name="car_image_url" value="<?php echo htmlspecialchars($user_feedback['car_image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                        </div>

                        <button type="submit" class="btn-submit"><?php echo $user_feedback ? 'Update Feedback' : 'Submit Feedback'; ?></button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error">
                        You can only add feedback for cars you have reserved and completed a rental with.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- View Feedback Section -->
        <div class="feedback-section">
            <h2 style="color: #e0e0e0; margin-bottom: 20px;">
                <?php echo $car_id > 0 ? 'All Feedback for ' . htmlspecialchars($car_details['brand'] . ' ' . $car_details['model']) : 'Recent Feedback'; ?>
            </h2>

            <div class="feedback-list">
                <?php if (isset($feedback_result) && mysqli_num_rows($feedback_result) > 0): ?>
                    <?php 
                    mysqli_data_seek($feedback_result, 0);
                    while ($feedback = mysqli_fetch_assoc($feedback_result)): 
                    ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <div>
                                    <span class="feedback-author"><?php echo htmlspecialchars($feedback['full_name']); ?></span>
                                    <span style="color: #90a4ae; margin-left: 10px;">on</span>
                                    <span style="color: #e0e0e0; margin-left: 5px;"><?php echo htmlspecialchars($feedback['brand'] . ' ' . $feedback['model']); ?></span>
                                </div>
                                <div class="feedback-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $feedback['rating']): ?>
                                            ★
                                        <?php else: ?>
                                            ☆
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="feedback-date">
                                <?php echo date('M d, Y H:i', strtotime($feedback['feedback_date'])); ?>
                            </div>
                            <?php if (!empty($feedback['comment'])): ?>
                                <div class="feedback-comment">
                                    <?php echo nl2br(htmlspecialchars($feedback['comment'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($feedback['car_image_url'])): ?>
                                <div style="margin-top: 10px;">
                                    <img src="<?php echo htmlspecialchars($feedback['car_image_url']); ?>" alt="Car Image" style="max-width: 300px; border-radius: 8px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #90a4ae;">
                        <?php if ($car_id > 0): ?>
                            <p>No feedback yet for this car. Be the first to review!</p>
                        <?php else: ?>
                            <p>No feedback found. Search for a car to view or add feedback.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function setRating(rating) {
            document.getElementById('rating').value = rating;
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // Initialize rating display
        <?php if ($user_feedback && isset($user_feedback['rating'])): ?>
            setRating(<?php echo $user_feedback['rating']; ?>);
        <?php endif; ?>
    </script>
</body>
</html>
