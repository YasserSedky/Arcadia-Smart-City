<?php
// Database singleton connection handler
class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserializing of the instance (must be public for PHP 7+)
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

// Add this check to prevent duplicate class declaration
if (!class_exists('DB')) {
    // For backward compatibility
    class DB
    {
        public static function conn()
        {
            return Database::getInstance();
        }
    }
}
