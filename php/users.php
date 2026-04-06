<?php
/* ============================================
   EDUVERSE PORTAL – USERS API
   POST /php/users.php
   Admin-only user management (create, update, delete, list)
   ============================================ */

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// ---- Auth check ----
function requireAdmin(): void {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? 'list';

try {
    $db = getDB();

    switch ($action) {

        // ---- LIST USERS ----
        case 'list':
            requireAdmin();
            $school = $body['school'] ?? $_GET['school'] ?? 'all';
            $role   = $body['role']   ?? $_GET['role']   ?? 'all';
            $sql = "SELECT u.id, u.first_name, u.last_name, u.username, u.email, u.role, u.school_key, u.age_group_key, u.status, u.last_login, u.created_at, s.name AS school_name FROM users u LEFT JOIN schools s ON s.school_key = u.school_key WHERE 1=1";
            $params = [];
            if ($school !== 'all') { $sql .= " AND (u.school_key = :school OR u.school_key = 'both')"; $params['school'] = $school; }
            if ($role   !== 'all') { $sql .= " AND u.role = :role"; $params['role'] = $role; }
            $sql .= " ORDER BY u.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            // Never return passwords
            foreach ($users as &$u) unset($u['password']);
            jsonResponse(['success' => true, 'users' => $users]);
            break;

        // ---- CREATE USER ----
        case 'create':
            requireAdmin();
            $username  = trim($body['username']  ?? '');
            $password  = $body['password']  ?? '';
            $firstName = trim($body['firstName'] ?? '');
            $lastName  = trim($body['lastName']  ?? '');
            $role      = $body['role']   ?? 'student';
            $school    = $body['school'] ?? null;
            $ageGroup  = $body['ageGroup'] ?? null ?: null;
            $email     = trim($body['email'] ?? '');
            $status    = $body['status'] ?? 'active';

            if (empty($username) || empty($password) || empty($firstName)) {
                jsonResponse(['success' => false, 'message' => 'username, password, and firstName are required']);
            }
            if (!in_array($role, ['student','parent','teacher','admin'])) {
                jsonResponse(['success' => false, 'message' => 'Invalid role']);
            }

            // Check duplicate username
            $check = $db->prepare("SELECT id FROM users WHERE username = :u");
            $check->execute(['u' => $username]);
            if ($check->fetch()) {
                jsonResponse(['success' => false, 'message' => "Username '{$username}' already exists"]);
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                INSERT INTO users (first_name, last_name, username, password, email, role, school_key, age_group_key, status)
                VALUES (:fn, :ln, :un, :pw, :em, :role, :school, :age, :status)
            ");
            $stmt->execute([
                'fn'     => $firstName,
                'ln'     => $lastName,
                'un'     => $username,
                'pw'     => $hashed,
                'em'     => $email,
                'role'   => $role,
                'school' => $school ?: null,
                'age'    => $ageGroup,
                'status' => $status
            ]);
            jsonResponse([
                'success'  => true,
                'userId'   => (int)$db->lastInsertId(),
                'username' => $username,
                'message'  => "User '{$firstName} {$lastName}' created successfully"
            ]);
            break;

        // ---- UPDATE USER ----
        case 'update':
            requireAdmin();
            $userId = (int)($body['id'] ?? 0);
            if (!$userId) jsonResponse(['success' => false, 'message' => 'User ID required']);

            $fields = []; $params = ['id' => $userId];
            $allowed = ['first_name'=>'firstName','last_name'=>'lastName','email'=>'email','role'=>'role','school_key'=>'school','age_group_key'=>'ageGroup','status'=>'status'];
            foreach ($allowed as $col => $key) {
                if (isset($body[$key])) {
                    $fields[] = "$col = :$col";
                    $params[$col] = $body[$key] ?: null;
                }
            }
            if (!empty($body['password'])) {
                $fields[] = "password = :password";
                $params['password'] = password_hash($body['password'], PASSWORD_BCRYPT);
            }
            if (empty($fields)) jsonResponse(['success' => false, 'message' => 'Nothing to update']);

            $db->prepare("UPDATE users SET " . implode(',', $fields) . " WHERE id = :id")->execute($params);
            jsonResponse(['success' => true, 'message' => 'User updated']);
            break;

        // ---- DELETE USER ----
        case 'delete':
            requireAdmin();
            $userId = (int)($body['id'] ?? 0);
            if (!$userId) jsonResponse(['success' => false, 'message' => 'User ID required']);
            if ($userId === ($_SESSION['user_id'] ?? 0)) {
                jsonResponse(['success' => false, 'message' => 'Cannot delete yourself']);
            }
            $db->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $userId]);
            jsonResponse(['success' => true, 'message' => 'User deleted']);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
    }

} catch (PDOException $e) {
    error_log('Users API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}