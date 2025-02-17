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
$adminPassword = getInput("Admin Password", true); // Store plain text temporarily
$adminEmail = getInput("Admin Email", true, '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'); // Email validation

try {
    // Database connection and schema creation
    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
    $pdo->exec("USE `$dbName`");
    // ... (Table creation - same as before)


    // Hash the admin password
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

    // Create admin user (using email now)
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
    // ... (Add other config parameters - SMTP, LDAP, etc.)
    $configContent .= "    'admin' => [\n";
    $configContent .= "        'user' => '$adminUser',\n";
    $configContent .= "        'password' => '$hashedPassword',\n"; // Store hashed password
    $configContent .= "        'email' => '$adminEmail',\n"; // Store admin email
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

    // ... (installed.lock and unlink(__FILE__) - same as before)

} catch (PDOException $e) {
    die("Error during installation: " . $e->getMessage());
} catch (Exception $e) {
    die("Error during installation: " . $e->getMessage());
}

?>
