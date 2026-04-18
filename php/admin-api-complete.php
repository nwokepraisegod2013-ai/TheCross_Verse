<?php
/* ============================================
   ADMIN API - COMPLETE SAAS PLATFORM
   All admin operations + SaaS management
   ============================================ */

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/config.php';

$db = getDB();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        
        // ==================== DASHBOARD STATS ====================
        case 'dashboard_stats':
            $stats = [];
            
            // Platform stats
            $stats['total_schools'] = $db->query("SELECT COUNT(*) FROM schools WHERE status = 'active'")->fetchColumn();
            $stats['total_students'] = $db->query("SELECT COUNT(*) FROM student_profiles WHERE status = 'active'")->fetchColumn();
            $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
            $stats['pending_registrations'] = $db->query("SELECT COUNT(*) FROM registrations WHERE status = 'pending'")->fetchColumn();
            
            // SaaS stats
            $stats['pending_school_approvals'] = $db->query("SELECT COUNT(*) FROM school_registration_requests WHERE status = 'pending'")->fetchColumn();
            $stats['active_subscriptions'] = $db->query("SELECT COUNT(*) FROM school_subscriptions WHERE status = 'active' AND end_date >= CURDATE()")->fetchColumn();
            
            // Revenue
            $stats['monthly_revenue'] = $db->query("
                SELECT COALESCE(SUM(amount), 0) 
                FROM payment_history 
                WHERE payment_status = 'completed' 
                AND MONTH(payment_date) = MONTH(CURDATE())
                AND YEAR(payment_date) = YEAR(CURDATE())
            ")->fetchColumn();
            
            // Students by school
            $stmt = $db->query("
                SELECT s.school_key, s.name, COUNT(sp.id) as student_count
                FROM schools s
                LEFT JOIN student_profiles sp ON s.school_key = sp.school_key AND sp.status = 'active'
                WHERE s.status = 'active'
                GROUP BY s.id
            ");
            $stats['by_school'] = $stmt->fetchAll();
            
            jsonResponse(['success' => true, 'data' => $stats]);
            break;

        // ==================== SCHOOL REGISTRATION APPROVALS ====================
        case 'get_school_approvals':
            $status = $_GET['status'] ?? 'pending';
            
            $sql = "SELECT srr.*, hp.plan_name, hp.price_monthly
                    FROM school_registration_requests srr
                    LEFT JOIN hosting_plans hp ON srr.requested_plan_id = hp.id
                    WHERE 1=1";
            $params = [];
            
            if ($status !== 'all') {
                $sql .= " AND srr.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY srr.created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $approvals = $stmt->fetchAll();
            
            jsonResponse(['success' => true, 'data' => $approvals]);
            break;

        case 'approve_school':
            $data = getRequestBody();
            $requestId = $data['request_id'];
            
            $db->beginTransaction();
            
            try {
                // Get request details
                $stmt = $db->prepare("SELECT * FROM school_registration_requests WHERE id = ?");
                $stmt->execute([$requestId]);
                $request = $stmt->fetch();
                
                if (!$request) {
                    throw new Exception('Request not found');
                }
                
                // Create school record
                $schoolKey = strtolower(str_replace([' ', '-'], '_', $request['school_name']));
                $stmt = $db->prepare("
                    INSERT INTO schools (
                        school_key, name, motto, description, status,
                        subdomain, contact_email, contact_phone, 
                        subscription_status, trial_ends_at
                    ) VALUES (?, ?, '', '', 'active', ?, ?, ?, 'trial', DATE_ADD(CURDATE(), INTERVAL 14 DAY))
                ");
                $stmt->execute([
                    $schoolKey, 
                    $request['school_name'],
                    $request['preferred_subdomain'],
                    $request['contact_email'],
                    $request['contact_phone']
                ]);
                $schoolId = $db->lastInsertId();
                
                // Create sub-admin account
                require_once __DIR__ . '/../classes/SubAdminManager.php';
                $subAdminManager = new SubAdminManager($db);
                $adminCredentials = $subAdminManager->createSchoolAdmin(
                    $schoolId,
                    $request['school_name'],
                    $request['contact_email'],
                    $request['contact_name']
                );
                
                // Create pending subscription
                $stmt = $db->prepare("
                    INSERT INTO school_subscriptions (
                        school_id, plan_id, status, start_date, end_date,
                        billing_cycle, amount_paid
                    ) VALUES (?, ?, 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'monthly', 0.00)
                ");
                $stmt->execute([$schoolId, $request['requested_plan_id']]);
                
                // Generate payment link if not free
                $stmt = $db->prepare("SELECT price_monthly FROM hosting_plans WHERE id = ?");
                $stmt->execute([$request['requested_plan_id']]);
                $price = $stmt->fetchColumn();
                
                $paymentLink = '';
                if ($price > 0) {
                    require_once __DIR__ . '/../classes/PaystackAPI.php';
                    $paystack = new PaystackAPI();
                    
                    $reference = 'EDU-' . $schoolId . '-' . time();
                    $paymentResult = $paystack->initializePayment(
                        $request['contact_email'],
                        $price * 100, // Convert to kobo
                        $reference,
                        [
                            'school_id' => $schoolId,
                            'plan_id' => $request['requested_plan_id'],
                            'type' => 'subscription'
                        ]
                    );
                    
                    if ($paymentResult['success']) {
                        $paymentLink = $paymentResult['authorization_url'];
                    }
                }
                
                // Update request status
                $stmt = $db->prepare("
                    UPDATE school_registration_requests 
                    SET status = 'approved', 
                        reviewed_at = NOW(), 
                        reviewed_by = ?,
                        approved_at = NOW(),
                        payment_link = ?
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $paymentLink, $requestId]);
                
                // Deploy website if uploaded
                if ($request['has_website'] && !empty($request['website_zip_path'])) {
                    require_once __DIR__ . '/../classes/WebsiteManager.php';
                    $websiteManager = new WebsiteManager($db);
                    $websiteManager->deploySchoolWebsite(
                        $schoolId, 
                        $request['preferred_subdomain'],
                        $request['website_zip_path']
                    );
                }
                
                $db->commit();
                
                // Send welcome email
                sendWelcomeEmail(
                    $request['contact_email'],
                    $request['school_name'],
                    $adminCredentials['username'],
                    $adminCredentials['password'],
                    $paymentLink,
                    $request['preferred_subdomain']
                );
                
                jsonResponse([
                    'success' => true, 
                    'message' => 'School approved successfully',
                    'data' => [
                        'school_id' => $schoolId,
                        'admin_username' => $adminCredentials['username'],
                        'subdomain' => $request['preferred_subdomain'] . '.eduverse.ng',
                        'payment_link' => $paymentLink
                    ]
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'reject_school':
            $data = getRequestBody();
            $stmt = $db->prepare("
                UPDATE school_registration_requests 
                SET status = 'rejected', 
                    reviewed_at = NOW(), 
                    reviewed_by = ?,
                    rejection_reason = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $data['reason'], $data['request_id']]);
            
            jsonResponse(['success' => true, 'message' => 'School registration rejected']);
            break;

        // ==================== HOSTING PLANS ====================
        case 'get_hosting_plans':
            $plans = $db->query("SELECT * FROM hosting_plans ORDER BY display_order")->fetchAll();
            jsonResponse(['success' => true, 'data' => $plans]);
            break;

        case 'save_plan':
            $data = getRequestBody();
            
            if (isset($data['id']) && $data['id']) {
                $stmt = $db->prepare("
                    UPDATE hosting_plans SET
                        plan_name = ?, slug = ?, description = ?,
                        price_monthly = ?, price_quarterly = ?, price_yearly = ?,
                        max_students = ?, max_teachers = ?, max_storage_gb = ?,
                        custom_domain = ?, phone_support = ?, api_access = ?,
                        white_label = ?, is_featured = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['plan_name'], $data['slug'], $data['description'],
                    $data['price_monthly'], $data['price_quarterly'], $data['price_yearly'],
                    $data['max_students'], $data['max_teachers'], $data['max_storage_gb'],
                    $data['custom_domain'] ? 1 : 0, $data['phone_support'] ? 1 : 0, 
                    $data['api_access'] ? 1 : 0, $data['white_label'] ? 1 : 0,
                    $data['is_featured'] ? 1 : 0, $data['is_active'] ? 1 : 0,
                    $data['id']
                ]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO hosting_plans (
                        plan_name, slug, description,
                        price_monthly, price_quarterly, price_yearly,
                        max_students, max_teachers, max_storage_gb,
                        custom_domain, phone_support, api_access, white_label,
                        is_featured, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['plan_name'], $data['slug'], $data['description'],
                    $data['price_monthly'], $data['price_quarterly'], $data['price_yearly'],
                    $data['max_students'], $data['max_teachers'], $data['max_storage_gb'],
                    $data['custom_domain'] ? 1 : 0, $data['phone_support'] ? 1 : 0,
                    $data['api_access'] ? 1 : 0, $data['white_label'] ? 1 : 0,
                    $data['is_featured'] ? 1 : 0, $data['is_active'] ? 1 : 0
                ]);
            }
            
            jsonResponse(['success' => true, 'message' => 'Plan saved successfully']);
            break;

        // ==================== EXISTING STUDENT MANAGEMENT ====================
        case 'get_registrations':
            $school = $_GET['school'] ?? 'all';
            $status = $_GET['status'] ?? 'all';
            
            $sql = "SELECT * FROM registrations WHERE 1=1";
            $params = [];
            
            if ($school !== 'all') {
                $sql .= " AND school = ?";
                $params[] = $school;
            }
            
            if ($status !== 'all') {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $registrations = $stmt->fetchAll();
            
            jsonResponse(['success' => true, 'data' => $registrations]);
            break;

        case 'update_registration_status':
            $data = getRequestBody();
            $stmt = $db->prepare("UPDATE registrations SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$data['status'], $data['notes'] ?? '', $data['id']]);
            
            if ($data['status'] === 'approved') {
                $reg = $db->query("SELECT * FROM registrations WHERE id = " . $data['id'])->fetch();
                
                $username = strtolower($reg['first_name'] . '.' . $reg['last_name']);
                $password = password_hash('student123', PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO users (username, password, email, role, first_name, last_name, status) VALUES (?, ?, ?, 'student', ?, ?, 'active')");
                $stmt->execute([$username, $password, $reg['parent_email'], $reg['first_name'], $reg['last_name']]);
                $userId = $db->lastInsertId();
                
                $studentId = 'STU' . date('Y') . str_pad($userId, 4, '0', STR_PAD_LEFT);
                $stmt = $db->prepare("
                    INSERT INTO student_profiles 
                    (user_id, student_id, registration_id, school_key, age_group_key, date_of_birth, gender, parent_name, parent_email, parent_phone, address, admission_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active')
                ");
                $stmt->execute([
                    $userId, $studentId, $reg['id'], $reg['school'], $reg['age_group'],
                    $reg['date_of_birth'], $reg['gender'], $reg['parent_name'], 
                    $reg['parent_email'], $reg['phone'], $reg['address']
                ]);
            }
            
            jsonResponse(['success' => true, 'message' => 'Registration updated']);
            break;

        case 'get_students':
            $stmt = $db->query("
                SELECT 
                    sp.*,
                    u.username,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    s.name as school_name,
                    ag.name as age_group_name
                FROM student_profiles sp
                JOIN users u ON sp.user_id = u.id
                LEFT JOIN schools s ON sp.school_key = s.school_key
                LEFT JOIN age_groups ag ON sp.age_group_key = ag.group_key
                ORDER BY sp.created_at DESC
            ");
            $students = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $students]);
            break;

        case 'get_users':
            $stmt = $db->query("
                SELECT u.*, CONCAT(u.first_name, ' ', u.last_name) as full_name
                FROM users u
                ORDER BY u.created_at DESC
            ");
            $users = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $users]);
            break;

        case 'save_user':
            $data = getRequestBody();
            
            if (isset($data['id']) && $data['id']) {
                $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, status = ?";
                $params = [$data['first_name'], $data['last_name'], $data['email'], $data['role'], $data['status']];
                
                if (!empty($data['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $data['id'];
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                jsonResponse(['success' => true, 'message' => 'User updated']);
            } else {
                $password = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (username, password, email, role, first_name, last_name, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['username'], $password, $data['email'], $data['role'],
                    $data['first_name'], $data['last_name'], $data['status']
                ]);
                
                jsonResponse(['success' => true, 'message' => 'User created']);
            }
            break;

        case 'get_announcements':
            $stmt = $db->query("
                SELECT * FROM announcements 
                WHERE is_active = 1 
                ORDER BY created_at DESC
            ");
            $announcements = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $announcements]);
            break;

        case 'post_announcement':
            $data = getRequestBody();
            $stmt = $db->prepare("
                INSERT INTO announcements 
                (title, content, school_key, priority, target_audience, is_active, created_by, start_date, end_date)
                VALUES (?, ?, ?, ?, 'all', 1, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))
            ");
            $stmt->execute([
                $data['title'], $data['content'], 
                empty($data['school_key']) ? null : $data['school_key'],
                $data['priority'], $_SESSION['user_id']
            ]);
            jsonResponse(['success' => true, 'message' => 'Announcement posted']);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }

} catch (PDOException $e) {
    error_log("Admin API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function getRequestBody() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? $_POST;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function sendWelcomeEmail($email, $schoolName, $username, $password, $paymentLink, $subdomain) {
    $to = $email;
    $subject = "Welcome to EduVerse Platform - $schoolName";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2 style='color: #6bcbf7;'>Welcome to EduVerse Platform!</h2>
        <p>Congratulations! Your school <strong>$schoolName</strong> has been approved.</p>
        
        <h3>Your Admin Credentials:</h3>
        <p><strong>Username:</strong> $username</p>
        <p><strong>Temporary Password:</strong> $password</p>
        <p><strong>Login URL:</strong> <a href='https://eduverse.ng/login.php'>https://eduverse.ng/login.php</a></p>
        
        <h3>Your School Website:</h3>
        <p><strong>URL:</strong> <a href='https://$subdomain.eduverse.ng'>https://$subdomain.eduverse.ng</a></p>
        
        " . ($paymentLink ? "<h3>Complete Payment:</h3><p><a href='$paymentLink' style='background: #6bcbf7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Click here to complete your subscription payment</a></p>" : "") . "
        
        <p>Thank you for choosing EduVerse Platform!</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: EduVerse Platform <noreply@eduverse.ng>' . "\r\n";
    
    mail($to, $subject, $message, $headers);
}