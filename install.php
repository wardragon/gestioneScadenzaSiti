<?php

// Check if already installed
$installedFile = 'installed.lock';
if (file_exists($installedFile)) {
    die("Application already installed. Delete 'installed.lock' to reinstall.");
}

// ... (getInput function - same as before)

// ... (displayInstallForm function - same as before)


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // ... (Input validation - same as before)

    if (!empty($errors)) {
        displayInstallForm($errors);
        exit; // Stop further execution
    }

    try {
        // Database connection and schema creation
        $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
        $pdo->exec("USE `$dbName`");

        // ... (Table creation - same as before)

        // Hash the admin password
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

        // Create admin user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminUser, $hashedPassword, 'admin', $adminEmail]);

        // Generate config.php
        $configContent = "<?php\nreturn [\n";
        $configContent .= "    'database' => [\n";
        $configContent .= "        'host' => '$dbHost',\n";
        $configContent .= "        'user' => '$dbUser',\n";
        $configContent .= "        'password' => '$dbPassword',\n";
        $configContent .= "        'name' => '$dbName',\n";
        $configContent .= "    ],\n";
        $configContent .= "    'admin' => [\n";
        $configContent .= "        'user' => '$adminUser',\n";
        $configContent .= "        'password' => '$hashedPassword',\n";
        $configContent .= "        'email' => '$adminEmail',\n";
        $configContent .= "    ],\n";
        $configContent .= "    'smtp' => [\n";
        $configContent .= "        'host' => '$smtpHost',\n";
        $configContent .= "        'port' => $smtpPort,\n";
        $configContent .= "        'user' => '$smtpUser',\n";
        $configContent .= "        'password' => '$smtpPassword',\n";
        $configContent .= "    ],\n";
        $configContent .= "    'ldap' => [\n";
        $configContent .= "        'host' => '$ldapHost',\n";
        $configContent .= "        'port' => $ldapPort,\n";
        $configContent .= "        'base_dn' => '$ldapBaseDn',\n";
        $configContent .= "        'bind_dn' => '$ldapBindDn',\n";
        $configContent .= "        'bind_password' => '$ldapBindPassword',\n";
        $configContent .= "        'user_filter' => '(uid=%username%)',\n";
        $configContent .= "        'attributes' => ['uid', 'cn', 'mail', 'memberOf'],\n";
        $configContent .= "    ],\n";
        $configContent .= "    'auth' => [\n";
        $configContent .= "        'method' => 'database',\n"; // Or 'ldap'
        $configContent .= "    ],\n";
        $configContent .= "    'email_template' => __DIR__ . '/../views/email_template.txt',\n";
        $configContent .= "];\n"; // Close the array and the PHP tag

        file_put_contents(__DIR__ . '/config.php', $configContent);

        // ... (installed.lock and unlink(__FILE__) - same as before)

    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
        displayInstallForm($errors); // Display errors on the form
        exit;
    } catch (Exception $e) {
        $errors[] = "An error occurred: " . $e->getMessage();
        displayInstallForm($errors); // Display errors on the form
        exit;
    }
} else {
    displayInstallForm(); // Display the form if it's a GET request
}

?>