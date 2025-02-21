<?php

session_start();

// Check if config.php exists and run install.php if needed
$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    $installPath = __DIR__ . '/install.php';

    if (file_exists($installPath)) {
        include $installPath;

        if (file_exists($configPath)) {
            echo "<p>Installation complete. Please refresh the page.</p>";
            exit;
        } else {
            die("Installation script failed to create config.php. Check the installation script for errors.");
        }
    } else {
        die("config.php not found and install.php not found. Please create both files.");
    }
}

// Proceed with normal execution if config.php exists
require 'vendor/autoload.php';

$config = include $configPath;

$db = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['name']}", $config['database']['user'], $config['database']['password']);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$templates = new League\Plates\Engine(); // Create the engine instance FIRST
$templates = new League\Plates\Engine(__DIR__ . '/views');

$auth = new App\Auth\Auth($db, $config);

// Routing
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET' => [
        '/' => 'App\Controllers\ServiceController@index',
        '/config' => 'App\Controllers\ServiceController@config',
        '/add' => 'App\Controllers\ServiceController@add',
        '/edit/(\d+)' => 'App\Controllers\ServiceController@edit',
        '/delete/(\d+)' => 'App\Controllers\ServiceController@delete',
        '/login' => 'App\Controllers\ServiceController@login',
        '/logout' => 'App\Controllers\ServiceController@logout',
    ],
    'POST' => [
        '/config' => 'App\Controllers\ServiceController@config',
        '/add' => 'App\Controllers\ServiceController@add',
        '/edit/(\d+)' => 'App\Controllers\ServiceController@edit',
        '/login' => 'App\Controllers\ServiceController@login',
    ],
];

$found = false;
foreach ($routes[$method] as $route => $controllerAction) {
    $pattern = '#^' . $route . '$#';
    if (preg_match($pattern, $uri, $matches)) {
        $found = true;
        list($controller, $action) = explode('@', $controllerAction);
        $controller = new $controller($db, $templates, $config, $auth, new App\Models\ServiceModel($db)); // Pass the model
        $params = array_slice($matches, 1);
        call_user_func_array([$controller, $action], $params);
        break;
    }
}

if (!$found) {
    echo $templates->render('errors/404', ['auth' => $auth]);
    http_response_code(404);
}

?>