<?php
// Database configuration and PDO singleton

define('DB_HOST', 'localhost');
define('DB_NAME', 'arcadia_smart_city');
define('DB_USER', 'root'); 
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base path where the app is served under localhost (folder name under htdocs)
// This derives the folder name automatically and rawurlencodes it so spaces
// are represented as %20 in URLs (e.g. Arcadia Smart_City -> /Arcadia%20Smart_City).
// If you use a VirtualHost you can override APP_BASE before including this file.
if (!defined('APP_BASE')) {
    // Get the project folder name from the directory structure
    // __DIR__ in config.php is the backend directory, so dirname(__DIR__) is the project root
    $projectRoot = dirname(__DIR__);
    $projectFolder = basename($projectRoot);
    
    // URL encode the folder name to handle spaces
    // Note: rawurlencode handles spaces, underscores stay as-is
    define('APP_BASE', '/' . rawurlencode($projectFolder));
}

// Secret key required to register admin users via web form.
// Change this value to something secure or set APP-level override before including config.
if (!defined('ADMIN_REG_SECRET')) {
    define('ADMIN_REG_SECRET', 'change_this_admin_secret');
}

class DB
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$pdo;
    }
}

function redirect(string $path): void
{
    // If a root-relative path is provided (starts with '/') and it isn't
    // already prefixed with APP_BASE, prefix it so redirects work when the
    // app is served from a subfolder under localhost.
    if (str_starts_with($path, '/') && !str_starts_with($path, APP_BASE) && !str_starts_with($path, 'http')) {
        $path = APP_BASE . $path;
    }
    header('Location: ' . $path);
    exit;
}

function ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

