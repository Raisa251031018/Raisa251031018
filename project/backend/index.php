<?php
if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'PHP 8.0 or newer is required. Change the PHP version in cPanel (Select PHP Version).']);
    exit;
}

/**
 * API entry point:  backend/index.php?api=ACTION
 */
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

require __DIR__ . '/server/config.php';
require __DIR__ . '/server/database.php';
require __DIR__ . '/server/helpers.php';
require __DIR__ . '/server/router.php';

spl_autoload_register(function (string $class) {
    $file = __DIR__ . '/controllers/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

if (isset($_GET['api'])) {
    run_api(require __DIR__ . '/routes/api.php');
    exit;
}

out(['message' => 'Library API is running. Use index.php?api=ACTION']);
