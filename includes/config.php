<?php
/**
 * DATABASE CONFIGURATION FILE
 *
 * This file contains all the database connection settings for our car rental system.
 * It establishes a connection to the MySQL database and makes it available to other files.
 *
 * BEGINNER EXPLANATION:
 * - Think of this like a phone book that tells our website how to call the database
 * - The database is where we store all our car information, customer details, and reservations
 * - Every time we need to save or get information, we use this connection
 */

// =============================================================================
// DATABASE CONNECTION SETTINGS
// =============================================================================

// Database server details (where our database lives)
$database_server_name = "localhost";        // The server address (localhost = same computer)
$database_username = "root";                // Username to access the database
$database_password = "";                    // Password for the database (empty for local development)
$database_name = "car_rental";              // Name of our specific database

// =============================================================================
// CREATE DATABASE CONNECTION
// =============================================================================

// Connect to the MySQL database using the settings above
// mysqli_connect() is a PHP function that creates a connection to MySQL
$database_connection = mysqli_connect(
    $database_server_name,    // Server address
    $database_username,       // Username
    $database_password,       // Password
    $database_name           // Database name
);

// =============================================================================
// CHECK IF CONNECTION WAS SUCCESSFUL
// =============================================================================

// If the connection failed, stop the website and show an error message
if (!$database_connection) {
    // die() stops the website immediately and shows a message
    die("❌ Database connection failed: " . mysqli_connect_error());
}

// =============================================================================
// SET CHARACTER ENCODING (IMPORTANT FOR FRENCH CHARACTERS)
// =============================================================================

// Set the character encoding to UTF-8 to properly handle French characters (é, à, ç, etc.)
mysqli_set_charset($database_connection, "utf8");

// =============================================================================
// MAKE CONNECTION AVAILABLE TO OTHER FILES
// =============================================================================

// Create a shorter variable name for easier use in other files
// $conn is what we'll use throughout the website to talk to the database
$conn = $database_connection;

// Optional: Display success message for debugging (remove in production)
// echo "✅ Database connected successfully!";

?>