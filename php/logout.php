<?php
/* ============================================
   LOGOUT - Destroy session and redirect
   ============================================ */

session_start();

// Log the logout
if (isset($_SESSION['username'])) {
    error_log("🔓 Logout: User={$_SESSION['username']}, Role={$_SESSION['role']}");
}

// Destroy session
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: ../login.php');
exit;
?>