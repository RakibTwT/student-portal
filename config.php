<?php
// Database configuration
$databaseHost = 'localhost';
$databaseName = 'student_portal_db';
$databaseUsername = 'root';
$databasePassword = ''; // Blank for XAMPP by default

// Create connection
$conn = new mysqli($databaseHost, $databaseUsername, $databasePassword, $databaseName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session for login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
