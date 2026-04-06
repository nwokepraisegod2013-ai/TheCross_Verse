<?php
/* ============================================
   EDUVERSE PORTAL – REGISTRATION HANDLER (FIXED)
   POST /php/register.php
   ============================================ */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$body = getRequestBody();

// ---- Validate required fields ----
$required = ['firstName', 'lastName', 'dob', 'ageGroup', 'parentName', 'parentEmail', 'phone', 'school'];
$missing = [];
foreach ($required as $field) {
    if (empty(trim($body[$field] ?? ''))) {
        $missing[] = $field;
    }
}
if (!empty($missing)) {
    jsonResponse(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
}

// ---- Sanitize input ----
$data = [
    'school_key'       => preg_replace('/[^a-z_]/', '', strtolower($body['school'])),
    'first_name'       => htmlspecialchars(trim($body['firstName']), ENT_QUOTES, 'UTF-8'),
    'last_name'        => htmlspecialchars(trim($body['lastName']), ENT_QUOTES, 'UTF-8'),
    'date_of_birth'    => $body['dob'] ?? null,
    'gender'           => htmlspecialchars(trim($body['gender'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'age_group_key'    => preg_replace('/[^a-z_]/', '', strtolower($body['ageGroup'])),
    'parent_name'      => htmlspecialchars(trim($body['parentName']), ENT_QUOTES, 'UTF-8'),
    'parent_email'     => filter_var(trim($body['parentEmail']), FILTER_SANITIZE_EMAIL),
    'phone'            => htmlspecialchars(trim($body['phone']), ENT_QUOTES, 'UTF-8'),
    'address'          => htmlspecialchars(trim($body['address'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'emergency_name'   => htmlspecialchars(trim($body['emergencyName'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'emergency_phone'  => htmlspecialchars(trim($body['emergencyPhone'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'medical_notes'    => htmlspecialchars(trim($body['medical'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'interests'        => json_encode($body['interests'] ?? [], JSON_UNESCAPED_UNICODE),
    'notes'            => htmlspecialchars(trim($body['notes'] ?? ''), ENT_QUOTES, 'UTF-8'),
];

// ---- Additional Validation ----

// Validate email format
if (!filter_var($data['parent_email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Invalid parent email address']);
}

// Validate date of birth format (YYYY-MM-DD)
if (!empty($data['date_of_birth']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid date of birth format']);
}

// Validate age (must be between 3 and 18)
if (!empty($data['date_of_birth'])) {
    $birthDate = new DateTime($data['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    
    if ($age < 3 || $age > 18) {
        jsonResponse(['success' => false, 'message' => 'Student age must be between 3 and 18 years old']);
    }
}

// Validate school key exists in database
try {
    $db = getDB();
    
    $schoolCheck = $db->prepare("SELECT id FROM schools WHERE school_key = :key LIMIT 1");
    $schoolCheck->execute(['key' => $data['school_key']]);
    if (!$schoolCheck->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Invalid school selection']);
    }
    
} catch (Exception $e) {
    // If schools table doesn't exist, fall back to hardcoded validation
    if (!in_array($data['school_key'], ['brightstar', 'moonrise'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid school selection']);
    }
}

// Validate age group key exists in database
try {
    $ageGroupCheck = $db->prepare("SELECT id FROM age_groups WHERE group_key = :key LIMIT 1");
    $ageGroupCheck->execute(['key' => $data['age_group_key']]);
    if (!$ageGroupCheck->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Invalid age group selection']);
    }
} catch (Exception $e) {
    // If age_groups table doesn't exist, fall back to hardcoded validation
    $validAgeGroups = ['tiny', 'junior', 'discover', 'pioneer', 'champion'];
    if (!in_array($data['age_group_key'], $validAgeGroups)) {
        jsonResponse(['success' => false, 'message' => 'Invalid age group selection']);
    }
}

// ---- Insert Registration ----
try {
    // Check for duplicate registration (same name + parent email in last 7 days)
    $dupCheck = $db->prepare("
        SELECT id FROM registrations
        WHERE first_name = :fn AND last_name = :ln AND parent_email = :email
          AND school_key = :school AND created_at > (NOW() - INTERVAL 7 DAY)
        LIMIT 1
    ");
    $dupCheck->execute([
        'fn'     => $data['first_name'],
        'ln'     => $data['last_name'],
        'email'  => $data['parent_email'],
        'school' => $data['school_key']
    ]);
    
    if ($dupCheck->fetch()) {
        jsonResponse([
            'success' => false, 
            'message' => 'A registration for this student was already submitted recently. Please contact the school office if you need assistance.'
        ]);
    }

    // Insert new registration
    $stmt = $db->prepare("
        INSERT INTO registrations
          (school_key, first_name, last_name, date_of_birth, gender, age_group_key,
           parent_name, parent_email, phone, address, emergency_name, emergency_phone,
           medical_notes, interests, notes, status, created_at)
        VALUES
          (:school_key, :first_name, :last_name, :date_of_birth, :gender, :age_group_key,
           :parent_name, :parent_email, :phone, :address, :emergency_name, :emergency_phone,
           :medical_notes, :interests, :notes, 'pending', NOW())
    ");
    
    $stmt->execute($data);
    $registrationId = $db->lastInsertId();

    // Log successful registration
    error_log("New registration: ID={$registrationId}, Student={$data['first_name']} {$data['last_name']}, School={$data['school_key']}, Parent={$data['parent_email']}");

    // Optional: Send notification email to admin
    try {
        $settings = $db->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $adminEmail = $settings['admin_email'] ?? 'admin@eduverse.edu';
        $portalName = $settings['portal_name'] ?? 'EduVerse Portal';
        
        $emailSubject = "New Registration #{$registrationId} - {$portalName}";
        $emailBody = "New registration received!\n\n";
        $emailBody .= "Registration ID: {$registrationId}\n";
        $emailBody .= "Student: {$data['first_name']} {$data['last_name']}\n";
        $emailBody .= "School: " . ucfirst($data['school_key']) . "\n";
        $emailBody .= "Age Group: {$data['age_group_key']}\n";
        $emailBody .= "Date of Birth: {$data['date_of_birth']}\n";
        $emailBody .= "Parent/Guardian: {$data['parent_name']}\n";
        $emailBody .= "Parent Email: {$data['parent_email']}\n";
        $emailBody .= "Phone: {$data['phone']}\n";
        $emailBody .= "Submitted: " . date('Y-m-d H:i:s') . "\n\n";
        $emailBody .= "Please review this registration in the admin panel.";
        
        $emailHeaders = "From: noreply@eduverse.edu\r\n";
        $emailHeaders .= "Reply-To: {$data['parent_email']}\r\n";
        $emailHeaders .= "X-Mailer: PHP/" . phpversion();
        
        @mail($adminEmail, $emailSubject, $emailBody, $emailHeaders);
    } catch (Exception $e) {
        // Silently fail if email sending fails - don't block registration
        error_log("Failed to send admin notification email: " . $e->getMessage());
    }

    // Return success response
    jsonResponse([
        'success'        => true,
        'registrationId' => $registrationId,
        'id'             => $registrationId, // Alternative key for compatibility
        'message'        => "Thank you, {$data['first_name']}! Your registration has been received. An admin will review your application and create your login credentials. You will be notified at {$data['parent_email']}."
    ]);

} catch (PDOException $e) {
    error_log('Registration database error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error. Please try again or contact support.'], 500);
} catch (Exception $e) {
    error_log('Registration general error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
}