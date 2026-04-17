<?php
/* ============================================
   ADMIN API - Backend for Admin Panel
   Handles all CRUD operations
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
            
            // Total students
            $stats['total_students'] = $db->query("SELECT COUNT(*) FROM student_profiles WHERE status = 'active'")->fetchColumn();
            
            // Total users
            $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
            
            // Pending registrations
            $stats['pending_registrations'] = $db->query("SELECT COUNT(*) FROM registrations WHERE status = 'pending'")->fetchColumn();
            
            // Active schools
            $stats['active_schools'] = $db->query("SELECT COUNT(*) FROM schools WHERE status = 'active'")->fetchColumn();
            
            // Students by school
            $stmt = $db->query("
                SELECT s.school_key, s.name, COUNT(sp.id) as student_count
                FROM schools s
                LEFT JOIN student_profiles sp ON s.school_key = sp.school_key AND sp.status = 'active'
                WHERE s.status = 'active'
                GROUP BY s.id
            ");
            $stats['by_school'] = $stmt->fetchAll();
            
            // Students by age group
            $stmt = $db->query("
                SELECT ag.name, ag.icon, COUNT(sp.id) as student_count
                FROM age_groups ag
                LEFT JOIN student_profiles sp ON ag.group_key = sp.age_group_key AND sp.status = 'active'
                WHERE ag.is_active = 1
                GROUP BY ag.id
                ORDER BY ag.display_order
            ");
            $stats['by_age_group'] = $stmt->fetchAll();
            
            jsonResponse(['success' => true, 'data' => $stats]);
            break;

        // ==================== REGISTRATIONS ====================
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
            
            // If approved, create user and student profile
            if ($data['status'] === 'approved') {
                $reg = $db->query("SELECT * FROM registrations WHERE id = " . $data['id'])->fetch();
                
                // Generate username from name
                $username = strtolower($reg['first_name'] . '.' . $reg['last_name']);
                $password = password_hash('student123', PASSWORD_DEFAULT);
                
                // Create user
                $stmt = $db->prepare("INSERT INTO users (username, password, email, role, first_name, last_name, status) VALUES (?, ?, ?, 'student', ?, ?, 'active')");
                $stmt->execute([$username, $password, $reg['parent_email'], $reg['first_name'], $reg['last_name']]);
                $userId = $db->lastInsertId();
                
                // Create student profile
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

        // ==================== STUDENTS ====================
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

        // ==================== RESULTS ====================
        case 'get_results':
            $stmt = $db->query("
                SELECT 
                    sr.*,
                    CONCAT(u.first_name, ' ', u.last_name) as student_name,
                    sp.student_id,
                    subj.subject_name,
                    sess.session_year,
                    term.term_name,
                    at.type_name
                FROM student_results sr
                JOIN student_profiles sp ON sr.student_id = sp.id
                JOIN users u ON sp.user_id = u.id
                JOIN subjects subj ON sr.subject_id = subj.id
                JOIN academic_sessions sess ON sr.session_id = sess.id
                JOIN academic_terms term ON sr.term_id = term.id
                JOIN assessment_types at ON sr.assessment_type_id = at.id
                ORDER BY sr.created_at DESC
                LIMIT 100
            ");
            $results = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $results]);
            break;

        case 'upload_result':
            $data = getRequestBody();
            
            // Auto-calculate percentage
            $percentage = ($data['marks_obtained'] / $data['max_marks']) * 100;
            
            // Determine grade
            $grade = $db->query("
                SELECT grade FROM grade_configuration 
                WHERE $percentage BETWEEN min_percentage AND max_percentage 
                AND is_active = 1
                LIMIT 1
            ")->fetchColumn();
            
            $stmt = $db->prepare("
                INSERT INTO student_results 
                (student_id, session_id, term_id, subject_id, assessment_type_id, marks_obtained, max_marks, grade, is_published, created_by, entry_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $stmt->execute([
                $data['student_id'], $data['session_id'], $data['term_id'],
                $data['subject_id'], $data['assessment_type_id'],
                $data['marks_obtained'], $data['max_marks'], $grade,
                $data['is_published'] ?? 0, $_SESSION['user_id']
            ]);
            
            jsonResponse(['success' => true, 'message' => 'Result uploaded']);
            break;

        // ==================== USERS ====================
        case 'get_users':
            $stmt = $db->query("
                SELECT 
                    u.*,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name
                FROM users u
                ORDER BY u.created_at DESC
            ");
            $users = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $users]);
            break;

        case 'save_user':
            $data = getRequestBody();
            
            if (isset($data['id']) && $data['id']) {
                // Update existing user
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
                // Create new user
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

        case 'delete_user':
            $id = $_GET['id'];
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'User deleted']);
            break;

        // ==================== SCHOOLS ====================
        case 'get_schools':
            $schools = $db->query("SELECT * FROM schools ORDER BY school_key")->fetchAll();
            jsonResponse(['success' => true, 'data' => $schools]);
            break;

        case 'update_school':
            $data = getRequestBody();
            $stmt = $db->prepare("
                UPDATE schools 
                SET name = ?, motto = ?, description = ?
                WHERE school_key = ?
            ");
            $stmt->execute([$data['name'], $data['motto'], $data['description'], $data['school_key']]);
            jsonResponse(['success' => true, 'message' => 'School updated']);
            break;

        // ==================== AGE GROUPS ====================
        case 'get_age_groups':
            $groups = $db->query("SELECT * FROM age_groups ORDER BY display_order")->fetchAll();
            jsonResponse(['success' => true, 'data' => $groups]);
            break;

        case 'save_age_group':
            $data = getRequestBody();
            
            if (isset($data['id']) && $data['id']) {
                $stmt = $db->prepare("
                    UPDATE age_groups 
                    SET name = ?, min_age = ?, max_age = ?, icon = ?, description = ?, level_label = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['name'], $data['min_age'], $data['max_age'], $data['icon'],
                    $data['description'], $data['level_label'], $data['is_active'], $data['id']
                ]);
                jsonResponse(['success' => true, 'message' => 'Age group updated']);
            } else {
                $groupKey = strtolower(str_replace(' ', '_', $data['name']));
                $stmt = $db->prepare("
                    INSERT INTO age_groups (group_key, name, min_age, max_age, icon, description, level_label, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $groupKey, $data['name'], $data['min_age'], $data['max_age'],
                    $data['icon'], $data['description'], $data['level_label'], $data['is_active']
                ]);
                jsonResponse(['success' => true, 'message' => 'Age group created']);
            }
            break;

        // ==================== ANNOUNCEMENTS ====================
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

        case 'delete_announcement':
            $id = $_GET['id'];
            $db->prepare("UPDATE announcements SET is_active = 0 WHERE id = ?")->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'Announcement deleted']);
            break;

        // ==================== SETTINGS ====================
        case 'get_settings':
            $stmt = $db->query("SELECT * FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            jsonResponse(['success' => true, 'data' => $settings]);
            break;

        case 'update_settings':
            $data = getRequestBody();
            
            foreach ($data as $key => $value) {
                $stmt = $db->prepare("
                    INSERT INTO settings (setting_key, setting_value, is_public) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ");
                $stmt->execute([$key, $value, $value]);
            }
            
            jsonResponse(['success' => true, 'message' => 'Settings updated']);
            break;

        case 'change_password':
            $data = getRequestBody();
            
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $currentHash = $stmt->fetchColumn();
            
            if (!password_verify($data['current_password'], $currentHash)) {
                jsonResponse(['success' => false, 'message' => 'Current password is incorrect'], 400);
            }
            
            // Update password
            $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $_SESSION['user_id']]);
            
            jsonResponse(['success' => true, 'message' => 'Password updated successfully']);
            break;

        // ==================== SESSIONS & TERMS ====================
        case 'get_sessions':
            $sessions = $db->query("SELECT * FROM academic_sessions ORDER BY start_date DESC")->fetchAll();
            jsonResponse(['success' => true, 'data' => $sessions]);
            break;

        case 'get_terms':
            $stmt = $db->query("
                SELECT t.*, s.session_year
                FROM academic_terms t
                JOIN academic_sessions s ON t.session_id = s.id
                ORDER BY t.start_date DESC
            ");
            $terms = $stmt->fetchAll();
            jsonResponse(['success' => true, 'data' => $terms]);
            break;

        // ==================== SUBJECTS ====================
        case 'get_subjects':
            $subjects = $db->query("SELECT * FROM subjects ORDER BY school_key, display_order")->fetchAll();
            jsonResponse(['success' => true, 'data' => $subjects]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }

} catch (PDOException $e) {
    error_log("Admin API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}