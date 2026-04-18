<?php
header('Content-Type: application/json');
require_once 'config.php';

$subdomain = $_GET['subdomain'] ?? '';

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM schools WHERE subdomain = ?
        UNION ALL
        SELECT COUNT(*) FROM school_registration_requests WHERE preferred_subdomain = ? AND status != 'rejected'
    ");
    $stmt->execute([$subdomain, $subdomain]);
    
    $total = array_sum($stmt->fetchAll(PDO::FETCH_COLUMN));
    
    echo json_encode(['available' => $total === 0]);
} catch (Exception $e) {
    echo json_encode(['available' => false, 'error' => $e->getMessage()]);
}