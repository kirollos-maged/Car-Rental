<?php
session_start();
require_once 'conn.php';

header('Content-Type: application/json');

$car_id = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;

if ($car_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid car ID']);
    exit();
}

// Check car availability
$query = "SELECT status_name FROM cars 
          JOIN car_status ON cars.current_status_id = car_status.status_id 
          WHERE car_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $car_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(['status' => $row['status_name']]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Car not found']);
}

mysqli_stmt_close($stmt);