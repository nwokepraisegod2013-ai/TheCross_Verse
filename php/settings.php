<?php
/* ============================================
   EDUVERSE PORTAL – SETTINGS & AUTH API
   POST /php/settings.php  &  /php/auth.php
   ============================================ */

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

function requireAdmin(): void {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

function requireAuth(): void {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
    }
}

$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? '';
$script = basename($_SERVER['SCRIPT_NAME']);

try {
    $db = getDB();

    // ==================== SETTINGS ====================
    if ($script === 'settings.php') {
        switch ($action) {

            case 'save_settings':
                requireAdmin();
                $allowed = ['portal_name','registration_open','admin_email','theme_color'];
                foreach ($allowed as $key) {
                    if (isset($body[$key === 'portal_name' ? 'portalName' : ($key === 'registration_open' ? 'regOpen' : ($key === 'admin_email' ? 'adminEmail' : $key))])) {
                        $val = htmlspecialchars($body[$key === 'portal_name' ? 'portalName' : ($key === 'registration_open' ? 'regOpen' : ($key === 'admin_email' ? 'adminEmail' : $key))] ?? '', ENT_QUOTES);
                        $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2")
                           ->execute(['k' => $key, 'v' => $val, 'v2' => $val]);
                    }
                }
                jsonResponse(['success' => true, 'message' => 'Settings saved']);
                break;

            case 'get_settings':
                requireAdmin();
                $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
                $rows = $stmt->fetchAll();
                $settings = [];
                foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];
                jsonResponse(['success' => true, 'settings' => $settings]);
                break;

            default:
                jsonResponse(['success' => false, 'message' => 'Unknown action']);
        }
        exit;
    }

    // ==================== AUTH ====================
    if ($script === 'auth.php') {
        switch ($action) {

            // ---- Check session ----
            case 'check':
                if (isset($_SESSION['user_id'])) {
                    jsonResponse([
                        'success'  => true,
                        'loggedIn' => true,
                        'role'     => $_SESSION['role'],
                        'school'   => $_SESSION['school'],
                        'username' => $_SESSION['username'],
                        'fullName' => $_SESSION['full_name']
                    ]);
                }
                jsonResponse(['success' => true, 'loggedIn' => false]);
                break;

            // ---- Logout ----
            case 'logout':
                session_destroy();
                jsonResponse(['success' => true, 'message' => 'Logged out']);
                break;

            // ---- Change password ----
            case 'change_password':
                requireAuth();
                $current = $body['current'] ?? '';
                $newPass = $body['new']     ?? '';

                if (empty($current) || empty($newPass)) {
                    jsonResponse(['success' => false, 'message' => 'Both fields required']);
                }
                if (strlen($newPass) < 6) {
                    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters']);
                }

                // Verify current
                $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($current, $user['password'])) {
                    jsonResponse(['success' => false, 'message' => 'Current password is incorrect']);
                }

                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $db->prepare("UPDATE users SET password = :pw WHERE id = :id")
                   ->execute(['pw' => $hashed, 'id' => $_SESSION['user_id']]);
                jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
                break;

            // ---- Reset password (admin) ----
            case 'reset_user_password':
                requireAdmin();
                $userId  = (int)($body['userId'] ?? 0);
                $newPass = $body['password'] ?? '';
                if (!$userId || strlen($newPass) < 6) {
                    jsonResponse(['success' => false, 'message' => 'Invalid data']);
                }
                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $db->prepare("UPDATE users SET password = :pw WHERE id = :id")
                   ->execute(['pw' => $hashed, 'id' => $userId]);
                jsonResponse(['success' => true, 'message' => 'Password reset successfully']);
                break;

            default:
                jsonResponse(['success' => false, 'message' => 'Unknown action']);
        }
        exit;
    }

    jsonResponse(['success' => false, 'message' => 'Invalid endpoint'], 404);

} catch (PDOException $e) {
    error_log('Auth/Settings error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}