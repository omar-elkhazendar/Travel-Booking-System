<?php
// Database configuration
$host = 'localhost';
$dbname = 'travel_booking';
$username = 'root';
$password = '';

// Create connection
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Function to check connection and reconnect if necessary
function ensureConnection() {
    global $conn;
    if (!mysqli_ping($conn)) {
        global $host, $username, $password, $dbname;
        $conn = mysqli_connect($host, $username, $password, $dbname);
        if (!$conn) {
            die("Database connection lost and could not be reestablished: " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, "utf8mb4");
    }
    return $conn;
}
?> 