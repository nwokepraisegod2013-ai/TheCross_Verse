<?php
/* ============================================
   EDUVERSE PORTAL – LOGIN / AUTH HANDLER
   POST /php/login.php
   Body: { username, password, school, role }
   ============================================ */

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$body = getRequestBody();
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';
$school   = $body['school'] ?? '';

if (empty($username) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Username and password are required']);
}

try {
    $db = getDB();

    // Look up user
    $stmt = $db->prepare("
        SELECT u.*, s.name AS school_name
        FROM users u
        LEFT JOIN schools s ON s.school_key = u.school_key
        WHERE u.username = :username
          AND u.status = 'active'
        LIMIT 1
    ");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        // Slight delay to prevent timing attacks
        usleep(300000);
        jsonResponse(['success' => false, 'message' => 'Invalid username or password']);
    }

    // Verify password (bcrypt)
    if (!password_verify($password, $user['password'])) {
        usleep(300000);
        jsonResponse(['success' => false, 'message' => 'Invalid username or password']);
    }

    // Check school match (skip for admin or "both")
    if ($user['role'] !== 'admin' && $user['school_key'] !== 'both' && !empty($school) && $school !== 'all' && $user['school_key'] !== $school) {
        jsonResponse(['success' => false, 'message' => 'Invalid username or password for this school']);
    }

    // Update last_login
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
       ->execute(['id' => $user['id']]);

    // Start session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['school']    = $user['school_key'];
    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];

    // Return success (never return password)
    jsonResponse([
        'success'   => true,
        'role'      => $user['role'],
        'school'    => $user['school_key'],
        'username'  => $user['username'],
        'fullName'  => $user['first_name'] . ' ' . $user['last_name'],
        'ageGroup'  => $user['age_group_key'],
        'message'   => 'Login successful'
    ]);

} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error. Please try again.'], 500);
}
