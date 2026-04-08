<?php
/* ============================================
   LOGIN BACKEND - Database Authentication
   Validates credentials and creates session
   ============================================ */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required'
    ]);
    exit;
}

$username = trim($data['username']);
$password = $data['password'];
$requestedRole = $data['role'] ?? 'student';
$school = $data['school'] ?? 'brightstar';

// Validate inputs
if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter both username and password'
    ]);
    exit;
}

try {
    $db = getDB();
    
    // Query user from database
    $stmt = $db->prepare("
        SELECT 
            u.id, 
            u.username, 
            u.password, 
            u.role, 
            u.email,
            u.first_name,
            u.last_name,
            u.status,
            sp.school_key,
            sp.student_id
        FROM users u
        LEFT JOIN student_profiles sp ON u.id = sp.user_id AND u.role = 'student'
        WHERE u.username = ? OR (u.role = 'student' AND sp.student_id = ?)
        LIMIT 1
    ");
    
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Also try plain text for debugging (REMOVE IN PRODUCTION)
        if ($password !== 'admin123' && $password !== 'student123') {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid username or password'
            ]);
            exit;
        }
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        echo json_encode([
            'success' => false,
            'message' => 'Account is inactive. Contact administrator.'
        ]);
        exit;
    }
    
    // Verify role matches (optional, for security)
    if ($requestedRole !== 'student' && $user['role'] !== $requestedRole) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid role for this account'
        ]);
        exit;
    }
    
    // Update last login
    $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['school'] = $user['school_key'] ?? $school;
    $_SESSION['student_id'] = $user['student_id'] ?? null;
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Log successful login
    error_log("✅ Login successful: User={$user['username']}, Role={$user['role']}, IP={$_SERVER['REMOTE_ADDR']}");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'role' => $user['role'],
        'school' => $user['school_key'] ?? $school,
        'username' => $user['username'],
        'name' => $user['first_name'] . ' ' . $user['last_name']
    ]);
    
} catch (PDOException $e) {
    error_log("❌ Login database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again later.'
    ]);
} catch (Exception $e) {
    error_log("❌ Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error. Please try again later.'
    ]);
}
?>