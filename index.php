<?php

session_start();

// Check if config.php exists
$configPath = __DIR__ . '/config.php'; // Adjust path if config.php is in a subdirectory

if (!file_exists($configPath)) {
    // If config.php doesn't exist, try to run install.php
    $installPath = __DIR__ . '/install.php'; // Adjust path if install.php is in a subdirectory

    if (file_exists($installPath)) {
        // Include and execute install.php
        include $installPath;

        // After successful installation, redirect to the main page or display a message
        if (file_exists($configPath)) { // Check if config.php was created
            echo "<p>Installation complete.  Please refresh the page.</p>";
            exit; // Stop further execution to prevent errors if config.php was missing before.
        } else {
            die("Installation script failed to create config.php.  Check the installation script for errors.");
        }

    } else {
        die("config.php not found and install.php not found.  Please create both files.");
    }
}

// If config.php exists, proceed as normal
require 'vendor/autoload.php';

$config = include $configPath; // Now you can safely include config.php

// ... (rest of index.php - database connection, auth, routing, etc.)

// ... (Rest of your index.php code remains the same)

?>
