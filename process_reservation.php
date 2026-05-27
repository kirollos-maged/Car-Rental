<?php
session_start();
require_once 'conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];

// Check if customer details are completed
$details_query = "SELECT customer_id FROM customer_details WHERE customer_id = ?";
$stmt = mysqli_prepare($conn, $details_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$details_result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($details_result) == 0) {
    header("Location: dashboard.php?error=customer_details_required");
    exit();
}
mysqli_stmt_close($stmt);

// Validate and sanitize input
$car_id = isset($_POST['car_id']) ? intval($_POST['car_id']) : 0;
$pickup_office_id = isset($_POST['pickup_office_id']) ? intval($_POST['pickup_office_id']) : 0;
$return_office_id = isset($_POST['return_office_id']) ? intval($_POST['return_office_id']) : 0;
$start_date = isset($_POST['start_date']) ? mysqli_real_escape_string($conn, $_POST['start_date']) : '';
$end_date = isset($_POST['end_date']) ? mysqli_real_escape_string($conn, $_POST['end_date']) : '';

// Validate required fields
if ($car_id <= 0 || $pickup_office_id <= 0 || $return_office_id <= 0 || 
    empty($start_date) || empty($end_date)) {
    header("Location: reservation.php?car_id=" . $car_id . "&error=missing_fields");
    exit();
}

// Check car availability BEFORE processing
$availability_query = "SELECT status_name FROM cars 
                       JOIN car_status ON cars.current_status_id = car_status.status_id 
                       WHERE car_id = ?";
$stmt = mysqli_prepare($conn, $availability_query);
mysqli_stmt_bind_param($stmt, "i", $car_id);
mysqli_stmt_execute($stmt);
$availability_result = mysqli_stmt_get_result($stmt);
$availability_row = mysqli_fetch_assoc($availability_result);
mysqli_stmt_close($stmt);

if (!$availability_row || $availability_row['status_name'] !== 'Available') {
    header("Location: reservation.php?car_id=" . $car_id . "&error=unavailable");
    exit();
}

// Get car daily price
$car_query = "SELECT daily_price FROM cars WHERE car_id = ?";
$stmt = mysqli_prepare($conn, $car_query);
mysqli_stmt_bind_param($stmt, "i", $car_id);
mysqli_stmt_execute($stmt);
$car_result = mysqli_stmt_get_result($stmt);
$car = mysqli_fetch_assoc($car_result);
mysqli_stmt_close($stmt);

if (!$car) {
    header("Location: index.php?error=car_not_found");
    exit();
}

// Check if customer_details exists and has national_id_number (required for reservations)
$customer_details_check = "SELECT customer_id, national_id_number FROM customer_details WHERE customer_id = ?";
$stmt = mysqli_prepare($conn, $customer_details_check);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer_details_result = mysqli_stmt_get_result($stmt);
$customer_details_data = mysqli_fetch_assoc($customer_details_result);
mysqli_stmt_close($stmt);

if (!$customer_details_data || empty($customer_details_data['national_id_number'])) {
    // Redirect to dashboard with error message
    header("Location: dashboard.php?error=details_required");
    exit();
}

// Calculate total price
$start_datetime = new DateTime($start_date);
$end_datetime = new DateTime($end_date);
$days = $start_datetime->diff($end_datetime)->days;
$days = $days > 0 ? $days : 1; // Minimum 1 day
$total_price = $car['daily_price'] * $days;

// Convert date strings to datetime format for database
$start_datetime_db = date('Y-m-d H:i:s', strtotime($start_date));
$end_datetime_db = date('Y-m-d H:i:s', strtotime($end_date));

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert into RESERVATION
    $reservation_query = "INSERT INTO reservation 
                         (customer_id, car_id, pickup_office_id, return_office_id, 
                          start_date, end_date, status, total_price) 
                         VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)";
    $stmt = mysqli_prepare($conn, $reservation_query);
    mysqli_stmt_bind_param($stmt, "iiiissd", $customer_id, $car_id, $pickup_office_id, 
                          $return_office_id, $start_datetime_db, $end_datetime_db, $total_price);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to create reservation: " . mysqli_error($conn));
    }
    
    $reservation_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Commit transaction
    mysqli_commit($conn);

    // Redirect to payment page
    header("Location: payment.php?reservation_id=" . $reservation_id);
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    error_log("Reservation error: " . $e->getMessage());
    header("Location: reservation.php?car_id=" . $car_id . "&error=processing_failed");
    exit();
}