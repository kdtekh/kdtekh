<?php
// Include the main config file which contains database credentials
require_once 'config.php';

// Use the credentials from config.php
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    // Don't expose database errors to users in production
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("Error connecting to database: " . $e->getMessage());
    } else {
        die("Error connecting to database. Please try again later.");
    }
}
?>
