<?php

// Check if already installed
$installedFile = 'installed.lock';
if (file_exists($installedFile)) {
    die("Application already installed. Delete 'installed.lock' to reinstall.");
}

// Function to get input from the web form (using $_POST)
function getInput($fieldName, $required = true, $validation = null) {
    if (isset($_POST[$fieldName])) {
        $input = trim($_POST[$fieldName]);

        if ($required && empty($input)) {
            return null; // Return null to indicate missing required input
        }

        if ($validation && !preg_match($validation, $input)) {
            return null; // Return null for invalid input
        }

        return $input;
    }
    return null; // Return null if the field is not set
}

// Function to display the installation form
function displayInstallForm($errors = []) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>CyberSecurity Sapienza Installation</title>
        <style>
            body { font-family: sans-serif; }
            .error { color: red; }
            label { display: block; margin-bottom: 5px; }
            input, textarea { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
            button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h1>CyberSecurity Sapienza Installation</h1>

        <?php if (!empty($errors)): ?>
            <ul class="error">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post">
            <label for="db_host">Database Host:</label><input type="text" name="db_host" id="db_host" required>
            <label for="db_user">Database User:</label><input type="text" name="db_user" id="db_user" required>
            <label for="db_password">Database Password:</label><input type="password" name="db_password" id="db_password" required>
            <label for="db_name">Database Name:</label><input type="text" name="db_name" id="db_name" required>
            <label for="admin_user">Admin Username:</label><input type="text" name="admin_user" id="admin_user" required>
            <label for="admin_password">Admin Password (min. 8 characters, at least one uppercase, one lowercase, and one number):</label><input type="password" name="admin_password" id="admin_password" required>
            <label for="admin_email">Admin Email:</label><input type="email" name="admin_email" id="admin_email" required>

            <label for="smtp_host">SMTP Host:</label><input type="text" name="smtp_host" id="smtp_host">
            <label for="smtp_port">SMTP Port:</label><input type="number" name="smtp_port" id="smtp_port">
            <label for="smtp_user">SMTP User:</label><input type="text" name="smtp_user" id="smtp_user">
            <label for="smtp_password">SMTP Password:</label><input type="password" name="smtp_password" id="smtp_password">

            <label for="ldap_host">LDAP Host:</label><input type="text" name="ldap_host" id="ldap_host">
            <label for="ldap_port">LDAP Port:</label><input type="number" name="ldap_port" id="ldap_port">
            <label for="ldap_base_dn">LDAP Base DN:</label><input type="text" name="ldap_base_dn" id="ldap_base_dn">
            <label for="ldap_bind_dn">LDAP Bind DN:</label><input type="text" name="ldap_bind_dn" id="ldap_bind_dn">
            <label for="ldap_bind_password">LDAP Bind Password:</label><input type="password" name="ldap_bind_password" id="ldap_bind_password">

            <button type="submit">Install</button>
        </form>
    </body>
    </html>
    <?php
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validate inputs
    $dbHost = getInput('db_host');
    $dbUser = getInput('db_user');
    $dbPassword = getInput('db_password');
    $dbName = getInput('db_name');
    $adminUser = getInput('admin_user');
    $adminPassword = getInput('admin_password');
    $adminEmail = getInput('admin_email');
    $smtpHost = getInput('smtp_host');
    $smtpPort = getInput('smtp_port');
    $smtpUser = getInput('smtp_user');
    $smtpPassword = getInput('smtp_password');
    $ldapHost = getInput('ldap_host');
    $ldapPort = getInput('ldap_port');
    $ldapBaseDn = getInput('ldap_base_dn');
    $ldapBindDn = getInput('ldap_bind_dn');
    $ldapBindPassword = getInput('ldap_bind_password');

    if (empty($dbHost) || empty($dbUser) || empty($dbPassword) || empty($dbName) || empty($adminUser) || empty($adminPassword) || empty($adminEmail)) {
        $errors[] = "All required fields must be filled.";
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $adminPassword)) {
        $errors[] = "Admin password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.";
    }

    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid admin email format.";
    }

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

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'user', -- 'admin' or 'user'
                email VARCHAR(255)
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
        $configContent .= "];\n";

        file_put_contents(__DIR__ . '/config.php', $configContent);

        // Create installed.lock file
        touch($installedFile);

        // Remove install.php itself AFTER successful installation
        unlink(__FILE__); // __FILE__ is the path to the current file (install.php)

        echo "Installation complete!\n";
        echo "Remember to change the admin password after logging in.\n";
        echo "install.php has been automatically removed.\n"; // Inform the user

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
    displayInstallForm(); // Display the form if it's a GET request (initial page load)
}

?>