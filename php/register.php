<?php
/* ============================================
   REGISTRATION SUBMISSION HANDLER
   Processes registration form data
   ============================================ */

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required = ['school', 'ageGroup', 'firstName', 'lastName', 'dob', 'parentName', 'parentEmail', 'phone'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate email
    if (!filter_var($data['parentEmail'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Validate age (3-18 years)
    $dob = new DateTime($data['dob']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
    if ($age < 3 || $age > 18) {
        throw new Exception('Student age must be between 3 and 18 years');
    }
    
    $db = getDB();
    
    // Check if email already exists in registrations
    $stmt = $db->prepare("SELECT id FROM registrations WHERE parent_email = ? AND status != 'rejected'");
    $stmt->execute([$data['parentEmail']]);
    if ($stmt->fetch()) {
        throw new Exception('An application with this email already exists');
    }
    
    // Verify school exists
    $stmt = $db->prepare("SELECT id FROM schools WHERE school_key = ? AND status = 'active'");
    $stmt->execute([$data['school']]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid school selected');
    }
    
    // Verify age group exists
    $stmt = $db->prepare("SELECT id FROM age_groups WHERE group_key = ? AND is_active = 1");
    $stmt->execute([$data['ageGroup']]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid age group selected');
    }
    
    // Prepare interests JSON
    $interests = isset($data['interests']) && is_array($data['interests']) 
        ? json_encode($data['interests']) 
        : null;
    
    // Insert registration
    $stmt = $db->prepare("
        INSERT INTO registrations (
            school, 
            age_group, 
            first_name, 
            last_name, 
            date_of_birth, 
            gender,
            parent_name, 
            parent_email, 
            phone, 
            address,
            interests,
            notes,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $data['school'],
        $data['ageGroup'],
        $data['firstName'],
        $data['lastName'],
        $data['dob'],
        $data['gender'] ?? null,
        $data['parentName'],
        $data['parentEmail'],
        $data['phone'],
        $data['address'] ?? null,
        $interests,
        $data['notes'] ?? null
    ]);
    
    $registrationId = $db->lastInsertId();
    
    // Log success
    error_log("✅ New registration: ID $registrationId - {$data['firstName']} {$data['lastName']}");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration submitted successfully! Admin will review your application and create your login credentials.',
        'registrationId' => $registrationId,
        'id' => $registrationId
    ]);
    
} catch (PDOException $e) {
    error_log("❌ Registration DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
    
} catch (Exception $e) {
    error_log("❌ Registration Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>