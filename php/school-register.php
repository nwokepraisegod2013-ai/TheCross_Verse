<?php
/* ============================================
   SCHOOL REGISTRATION BACKEND HANDLER
   Processes school registration requests
   ============================================ */

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required = ['schoolName', 'schoolEmail', 'schoolPhone', 'contactName', 'contactEmail', 'contactPhone', 'planId', 'subdomain'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate email
    if (!filter_var($data['schoolEmail'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid school email address');
    }
    
    if (!filter_var($data['contactEmail'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid contact email address');
    }
    
    // Validate subdomain
    $subdomain = strtolower(trim($data['subdomain']));
    if (!preg_match('/^[a-z0-9-]{3,50}$/', $subdomain)) {
        throw new Exception('Subdomain must be 3-50 characters (letters, numbers, hyphens only)');
    }
    
    $db = getDB();
    
    // Check if subdomain already exists
    $stmt = $db->prepare("SELECT id FROM schools WHERE subdomain = ?");
    $stmt->execute([$subdomain]);
    if ($stmt->fetch()) {
        throw new Exception('Subdomain already taken. Please choose another.');
    }
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM school_registration_requests WHERE school_email = ? AND status != 'rejected'");
    $stmt->execute([$data['schoolEmail']]);
    if ($stmt->fetch()) {
        throw new Exception('A registration with this email already exists');
    }
    
    // Verify plan exists
    $stmt = $db->prepare("SELECT id, plan_name FROM hosting_plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$data['planId']]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$plan) {
        throw new Exception('Invalid hosting plan selected');
    }
    
    // Insert registration request
    $stmt = $db->prepare("
        INSERT INTO school_registration_requests (
            school_name, school_email, school_phone, school_address, school_website,
            contact_name, contact_email, contact_phone, contact_position,
            requested_plan_id, billing_cycle, preferred_subdomain, custom_domain,
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $data['schoolName'],
        $data['schoolEmail'],
        $data['schoolPhone'],
        $data['schoolAddress'] ?? null,
        $data['schoolWebsite'] ?? null,
        $data['contactName'],
        $data['contactEmail'],
        $data['contactPhone'],
        $data['contactPosition'] ?? null,
        $data['planId'],
        $data['billingCycle'],
        $subdomain,
        $data['customDomain'] ?? null
    ]);
    
    $requestId = $db->lastInsertId();
    
    // Log success
    error_log("✅ New school registration request: ID $requestId - {$data['schoolName']}");
    
    // TODO: Send email notification to admin and school
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration submitted successfully! We will review your application within 24 hours.',
        'requestId' => $requestId
    ]);
    
} catch (PDOException $e) {
    error_log("❌ School Registration DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
    
} catch (Exception $e) {
    error_log("❌ School Registration Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>