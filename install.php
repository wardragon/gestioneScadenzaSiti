<?php

// Check if already installed
$installedFile = 'installed.lock';
if (file_exists($installedFile)) {
    die("Application already installed. Delete 'installed.lock' to reinstall.");
}

function getInput($prompt, $required = true, $validation = null) {
    // ... (input validation function - same as before)
}

// Get database parameters from user
echo "Welcome to the CyberSecurity Sapienza Installation!\n";

$dbHost = getInput("Database Host (e.g., localhost)", true);
$dbUser = getInput("Database User", true);
$dbPassword = getInput("Database Password", true);
$dbName = getInput("Database Name", true);

// Get admin user details
$adminUser = getInput("Admin Username", true, '/^[a-zA-Z0-9_]+$/');
$adminPassword = getInput("Admin Password", true);

// ... (Get other configuration parameters if needed: SMTP, LDAP, etc.)

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
    $pdo->exec("USE `$dbName`");

    // Create tables (Schema)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user' -- 'admin' or 'user'
        );

        CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(255),
            service TEXT,
            start_date DATE,
            expiration_days INT DEFAULT 10,
            contact VARCHAR(255),
            responsible VARCHAR(255),
            status VARCHAR(20) DEFAULT 'open',  -- open, closed, expired
            referent VARCHAR(255)
        );

        CREATE TABLE IF NOT EXISTS user_preferences (
            user_id INT,
            order_by VARCHAR(255) DEFAULT 'start_date ASC',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // Create admin user (hash the password)
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$adminUser, $hashedPassword, 'admin']);

    // ... (Store other configuration parameters in config.php)

    // Create installed.lock file
    touch($installedFile);

    // Create config.php
    $configContent = "<?php\nreturn [\n";
    $configContent .= "    'database' => [\n";
    $configContent .= "        'host' => '$dbHost',\n";
    $configContent .= "        'user' => '$dbUser',\n";
    $configContent .= "        'password' => '$dbPassword',\n";
    $configContent .= "        'name' => '$dbName',\n";
    $configContent .= "    ],\n";
    // ... (Add other config parameters - SMTP, LDAP, etc.)
    $configContent .= "    'admin' => [\n";
    $configContent .= "        'user' => '$adminUser',\n";
    $configContent .= "        'password' => '$hashedPassword',\n";
    $configContent .= "    ],\n";
    $configContent .= "    'smtp' => [\n"; // Example SMTP config
    $configContent .= "        'host' => 'your_smtp_host',\n";
    $configContent .= "        'port' => 587,\n";
    $configContent .= "        'user' => 'your_smtp_user',\n";
    $configContent .= "        'password' => 'your_smtp_password',\n";
    $configContent .= "    ],\n";
    $configContent .= "    'ldap' => [\n"; // Example LDAP config
    $configContent .= "        'host' => 'your_ldap_host',\n";
    $configContent .= "        'port' => 389,\n";
    $configContent .= "        'base_dn' => 'your_ldap_base_dn',\n";
    $configContent .= "        'bind_dn' => 'cn=binduser,dc=example,dc=com',\n";
    $configContent .= "        'bind_password' => 'your_ldap_bind_password',\n";
    $configContent .= "        'user_filter' => '(uid=%username%)',\n";
    $configContent .= "        'attributes' => ['uid', 'cn', 'mail', 'memberOf'],\n";
    $configContent .= "    ],\n";
    $configContent .= "    'auth' => [\n";
    $configContent .= "        'method' => 'database',\n"; // Or 'ldap'
    $configContent .= "    ],\n";
    $configContent .= "    'email_template' => __DIR__ . '/../views/email_template.txt',\n";
    $configContent .= "];\n";


    file_put_contents(__DIR__ . '/config.php', $configContent);

    echo "Installation complete!\n";
    echo "Remember to change the admin password after logging in.\n";

} catch (PDOException $e) {
    die("Error during installation: " . $e->getMessage());
}

?>
