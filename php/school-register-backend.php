<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

try {
    $db = getDB();
    
    // Handle file upload
    $websiteZipPath = null;
    $hasWebsite = isset($_FILES['websiteZip']) && $_FILES['websiteZip']['error'] === UPLOAD_ERR_OK;
    
    if ($hasWebsite) {
        $uploadDir = __DIR__ . '/../uploads/temp/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $zipFile = $_FILES['websiteZip'];
        $fileName = 'website_' . time() . '.zip';
        $websiteZipPath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($zipFile['tmp_name'], $websiteZipPath)) {
            throw new Exception('Failed to upload website');
        }
    }
    
    // Insert registration request
    $stmt = $db->prepare("
        INSERT INTO school_registration_requests (
            school_name, school_email, school_phone, school_address, school_type,
            contact_name, contact_position, contact_email, contact_phone,
            requested_plan_id, billing_cycle, preferred_subdomain,
            has_website, website_zip_path, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $_POST['schoolName'], $_POST['schoolEmail'], $_POST['schoolPhone'],
        $_POST['schoolAddress'], $_POST['schoolType'] ?? 'mixed',
        $_POST['contactName'], $_POST['contactPosition'],
        $_POST['contactEmail'], $_POST['contactPhone'],
        $_POST['planId'], $_POST['billingCycle'], $_POST['subdomain'],
        $hasWebsite ? 1 : 0, $websiteZipPath
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Registration submitted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}