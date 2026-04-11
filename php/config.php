<?php
/* ============================================
   EDUVERSE PORTAL – DATABASE CONFIG
   Fixed version - no early headers
   ============================================ */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'eduverse_db');
define('DB_CHARSET', 'utf8mb4');

// PDO Connection
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
    }
    return $pdo;
}

// JSON response helper
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get JSON request body
function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return $data ?? [];
}
?>