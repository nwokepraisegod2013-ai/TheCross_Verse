<?php
/* ============================================
   DATABASE CONFIGURATION FOR XAMPP
   Updated for Windows/XAMPP default settings
   ============================================ */

// Database configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'eduverse_db');
define('DB_USER', 'root');              // Default XAMPP user
define('DB_PASS', '');                  // Default XAMPP password (empty)
define('DB_CHARSET', 'utf8mb4');

// Base URL configuration
define('BASE_URL', 'http://eduverse.local');
define('ENVIRONMENT', 'development'); // Change to 'production' when live

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get database connection
 * Returns PDO instance or dies with error
 */
function getDB() {
    static $db = null;
    
    if ($db !== null) {
        return $db;
    }
    
    try {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $db;
        
    } catch (PDOException $e) {
        // Log the error
        $errorMsg = date('Y-m-d H:i:s') . " - Database Connection Error: " . $e->getMessage() . "\n";
        $logDir = __DIR__ . '/../logs';
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logDir . '/db-errors.log', $errorMsg, FILE_APPEND);
        
        // Show user-friendly error in development
        if (ENVIRONMENT === 'development') {
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
                'hint' => 'Check if MySQL is running in XAMPP Control Panel'
            ]));
        } else {
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please contact administrator.'
            ]));
        }
    }
}

/**
 * Helper function for JSON responses
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get request body (JSON or POST)
 */
function getRequestBody() {
    $input = file_get_contents('php://input');
    $json = json_decode($input, true);
    return $json ?? $_POST;
}