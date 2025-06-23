<?php
$database_server_name = "localhost";        // The server address (localhost = same computer)
$database_username = "root";                // Username to access the database
$database_password = "";                    // Password for the database (empty for local development)
$database_name = "car_rental";              // Name of our specific database

$database_connection = mysqli_connect(
    $database_server_name,    // Server address
    $database_username,       // Username
    $database_password,       // Password
    $database_name           // Database name
);


if (!$database_connection) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($database_connection, "utf8");
$conn = $database_connection;
?>