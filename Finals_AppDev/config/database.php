<?php
// ============================================================
// Database Configuration - Cup of Jude's Machinery
// ============================================================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cup_of_judes_machinery';

// Create connection using MySQLi
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");
