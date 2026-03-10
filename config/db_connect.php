<?php
// config/db_connect.php

/**
 * This file is the central point for connecting to the database.
 * It will be included at the top of almost every other PHP file.
 */

// We start a session on every page. A session is like a memory for the browser,
// allowing us to remember if a user is logged in as they move from page to page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DATABASE CREDENTIALS ---
// These are the details needed to log into our MySQL database.
// For a default XAMPP installation, they are usually the same.
$DB_HOST = 'localhost'; // The server where the database is located (your own computer).
$DB_USER = 'root';      // The default username for XAMPP's MySQL.
$DB_PASS = '';          // The default password for XAMPP's MySQL is empty.
$DB_NAME = 'tenancy_plus_db'; // The name of the database we created.

// --- ESTABLISH THE CONNECTION ---
// This line uses the credentials above to try and connect to the MySQL database.
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// --- CHECK FOR ERRORS ---
// This is a safety check. If the connection fails for any reason
// (like wrong password or MySQL not running), it stops the script and shows an error.
if ($conn->connect_error) {
    // 'die()' immediately stops the program.
    die("Database Connection Failed: " . $conn->connect_error);
}

/**
 * A helper function to protect our pages.
 * We can call this function on any page that should only be seen by logged-in users.
 * If the user is not logged in, it redirects them to the login page.
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        // THE FIX IS HERE: Redirect to 'login.php' instead of 'index.php'
        header("Location: /tenancy-plus/login.php");
        die(); // Stop the script after redirecting.
    }
}
?>

