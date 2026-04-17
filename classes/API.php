<?php
/**
 * Base API Class
 * Handles authentication, request validation, and response formatting
 */

class API {
    
    protected $db;
    protected $requestMethod;
    protected $requestData;
    protected $headers;
    protected $school_id;
    protected $user_id;
    
    public function __construct() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        $this->db = $this->getDB();
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->headers = getallheaders();
        $this->requestData = $this->getRequestData();
    }
    
    /**
     * Get database connection
     */
    private function getDB() {
        require_once __DIR__ . '/../php/config.php';
        return getDB();
    }
    
    /**
     * Get request data (JSON or POST)
     */
    private function getRequestData() {
        $data = [];
        
        if ($this->requestMethod === 'GET') {
            $data = $_GET;
        } else {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? $_POST;
        }
        
        return $data;
    }
    
    /**
     * Validate API token
     */
    protected function validateToken() {
        $authHeader = $this->headers['Authorization'] ?? '';
        
        if (empty($authHeader)) {
            $this->sendError('Authorization token required', 401);
        }
        
        // Extract token from "Bearer <token>"
        $token = str_replace('Bearer ', '', $authHeader);
        
        try {
            $stmt = $this->db->prepare("
                SELECT at.*, s.id as school_id 
                FROM api_tokens at
                JOIN schools s ON at.school_id = s.id
                WHERE at.token = ? AND at.is_active = 1
                AND (at.expires_at IS NULL OR at.expires_at > NOW())
            ");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                $this->sendError('Invalid or expired token', 401);
            }
            
            // Check rate limit
            if ($this->checkRateLimit($tokenData['id']) === false) {
                $this->sendError('Rate limit exceeded', 429);
            }
            
            $this->school_id = $tokenData['school_id'];
            
            // Update last used
            $stmt = $this->db->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?");
            $stmt->execute([$tokenData['id']]);
            
            return $tokenData;
            
        } catch (PDOException $e) {
            $this->sendError('Database error', 500);
        }
    }
    
    /**
     * Check rate limit
     */
    private function checkRateLimit($tokenId) {
        try {
            $stmt = $this->db->prepare("
                SELECT rate_limit_per_hour, requests_this_hour, hour_reset_at 
                FROM api_tokens 
                WHERE id = ?
            ");
            $stmt->execute([$tokenId]);
            $limit = $stmt->fetch();
            
            $now = new DateTime();
            $resetTime = new DateTime($limit['hour_reset_at']);
            
            // Reset counter if hour has passed
            if ($now > $resetTime) {
                $newResetTime = $now->modify('+1 hour')->format('Y-m-d H:i:s');
                $stmt = $this->db->prepare("
                    UPDATE api_tokens 
                    SET requests_this_hour = 1, hour_reset_at = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$newResetTime, $tokenId]);
                return true;
            }
            
            // Check if limit exceeded
            if ($limit['requests_this_hour'] >= $limit['rate_limit_per_hour']) {
                return false;
            }
            
            // Increment counter
            $stmt = $this->db->prepare("
                UPDATE api_tokens 
                SET requests_this_hour = requests_this_hour + 1 
                WHERE id = ?
            ");
            $stmt->execute([$tokenId]);
            
            return true;
            
        } catch (PDOException $e) {
            return true; // Allow on error
        }
    }
    
    /**
     * Validate session (for web requests)
     */
    protected function validateSession() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            $this->sendError('Session required', 401);
        }
        
        $this->user_id = $_SESSION['user_id'];
        
        // Get school_id if user is school admin
        if (isset($_SESSION['school_id'])) {
            $this->school_id = $_SESSION['school_id'];
        }
        
        return true;
    }
    
    /**
     * Check if school subscription is active
     */
    protected function checkSubscriptionActive() {
        if (!$this->school_id) {
            $this->sendError('School ID not found', 400);
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT ss.*, hp.* 
                FROM school_subscriptions ss
                JOIN hosting_plans hp ON ss.plan_id = hp.id
                WHERE ss.school_id = ? 
                AND ss.status = 'active'
                AND ss.end_date >= CURDATE()
                ORDER BY ss.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$this->school_id]);
            $subscription = $stmt->fetch();
            
            if (!$subscription) {
                $this->sendError('No active subscription', 403);
            }
            
            return $subscription;
            
        } catch (PDOException $e) {
            $this->sendError('Database error', 500);
        }
    }
    
    /**
     * Check plan feature access
     */
    protected function checkFeatureAccess($featureName) {
        $subscription = $this->checkSubscriptionActive();
        
        if (!isset($subscription[$featureName]) || !$subscription[$featureName]) {
            $this->sendError("Feature '$featureName' not available in your plan", 403);
        }
        
        return true;
    }
    
    /**
     * Check usage limit
     */
    protected function checkUsageLimit($limitType, $currentValue) {
        $subscription = $this->checkSubscriptionActive();
        
        $limitField = 'max_' . $limitType;
        
        if (!isset($subscription[$limitField])) {
            return true; // No limit defined
        }
        
        if ($currentValue >= $subscription[$limitField]) {
            $this->sendError("Usage limit exceeded for $limitType. Please upgrade your plan.", 403);
        }
        
        return true;
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($fields) {
        $missing = [];
        
        foreach ($fields as $field) {
            if (!isset($this->requestData[$field]) || empty($this->requestData[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('Missing required fields: ' . implode(', ', $missing), 400);
        }
        
        return true;
    }
    
    /**
     * Sanitize input
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Send success response
     */
    protected function sendSuccess($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
        exit();
    }
    
    /**
     * Send error response
     */
    protected function sendError($message, $code = 400, $errors = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => time()
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response);
        exit();
    }
    
    /**
     * Send paginated response
     */
    protected function sendPaginated($data, $total, $page, $limit) {
        $totalPages = ceil($total / $limit);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$limit,
                'current_page' => (int)$page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ],
            'timestamp' => time()
        ]);
        exit();
    }
    
    /**
     * Log activity
     */
    protected function logActivity($type, $description, $value = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO usage_logs (school_id, log_type, description, value_int, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->school_id,
                $type,
                $description,
                $value,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            // Silent fail - logging shouldn't break the API
        }
    }
}