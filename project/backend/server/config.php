<?php
/**
 * Application configuration.
 * Values come from backend/.env (copy .env.example to .env and edit it).
 * Never commit .env and never put real passwords in this file.
 */
function env_load(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

env_load(dirname(__DIR__) . '/.env');

function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value !== false && $value !== null && $value !== '') ? $value : $default;
}

/* ---------- Database (defaults suit a local XAMPP / Laragon setup) ---------- */
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'library_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

/* ---------- Business rules ---------- */
define('FINE_PER_DAY', (int) env('FINE_PER_DAY', 5));   // BDT per overdue day
define('LOAN_DAYS', (int) env('LOAN_DAYS', 14));        // default loan period
define('APP_TZ', env('APP_TZ', 'Asia/Dhaka'));
date_default_timezone_set(APP_TZ);

/* ---------- Book cover uploads (stored in backend/uploads/books) ---------- */
define('BACKEND_DIR', dirname(__DIR__));
define('UPLOAD_DIR', BACKEND_DIR . '/uploads/books/');
define('UPLOAD_URL', 'uploads/books/');                 // saved in DB, relative to backend/
define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024);            // 2 MB
define('UPLOAD_ALLOWED_MIME', [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
]);
