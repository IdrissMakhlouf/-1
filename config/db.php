<?php
/**
 * Database Configuration File
 * Heritage Platform - Algeria Cultural Heritage
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'heritage_platform');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_URL', 'http://localhost/heritage_platform/');
define('SITE_NAME', 'منصة التراث والتاريخ الجزائري');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Database connection class using PDO
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

/**
 * Authentication Helper Functions
 */
class Auth {
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . SITE_URL . 'login.php');
            exit();
        }
    }
    
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header('Location: ' . SITE_URL . 'index.php');
            exit();
        }
    }
    
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getCurrentUserRole() {
        return $_SESSION['role'] ?? null;
    }
}

// Return database connection
$db = Database::getInstance()->getConnection();
?>