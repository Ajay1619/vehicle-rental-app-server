<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'rental_app');
define('DB_USER', 'project');
define('DB_PASSWORD', 'project');

try {
    // Create a PDO connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4"; // DSN for MySQL
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Set default fetch mode to associative array
        PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements if possible
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
} catch (PDOException $exception) {
    // Catch and display any errors that occur during the connection attempt
    die("Failed to connect to MySQL: " . $exception->getMessage());
}
