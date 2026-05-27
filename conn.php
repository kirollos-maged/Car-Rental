<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "CarRentalDB"; 

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
//echo "Connected successfully";