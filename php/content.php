<?php
/* ============================================
   EDUVERSE PORTAL – CONTENT MANAGEMENT API
   POST /php/content.php
   Manage schools, age groups, announcements
   ============================================ */

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

function requireAdmin(): void {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

$body   = getRequestBody();
$action = $body['action'] ?? $_GET['action'] ?? '';

try {
    $db = getDB();

    switch ($action) {

        // ---- GET SCHOOLS ----
        case 'get_schools':
            $stmt = $db->query("SELECT * FROM schools ORDER BY id");
            $schools = $stmt->fetchAll();
            foreach ($schools as &$s) {
                $s['features'] = json_decode($s['features'] ?? '[]', true);
            }
            jsonResponse(['success' => true, 'schools' => $schools]);
            break;

        // ---- UPDATE SCHOOL ----
        case 'update_school':
            requireAdmin();
            $key     = preg_replace('/[^a-z_]/', '', $body['key'] ?? '');
            $data    = $body['data'] ?? [];
            $name    = htmlspecialchars($data['name']   ?? '', ENT_QUOTES);
            $motto   = htmlspecialchars($data['motto']  ?? '', ENT_QUOTES);
            $desc    = htmlspecialchars($data['desc']   ?? '', ENT_QUOTES);
            $features= json_encode($data['features']    ?? []);

            if (!in_array($key, ['brightstar', 'moonrise'])) {
                jsonResponse(['success' => false, 'message' => 'Invalid school key']);
            }
            $db->prepare("UPDATE schools SET name=:n, motto=:m, description=:d, features=:f WHERE school_key=:k")
               ->execute(['n'=>$name,'m'=>$motto,'d'=>$desc,'f'=>$features,'k'=>$key]);
            jsonResponse(['success' => true, 'message' => 'School updated']);
            break;

        // ---- GET AGE GROUPS ----
        case 'get_age_groups':
            $stmt = $db->query("SELECT * FROM age_groups ORDER BY sort_order");
            jsonResponse(['success' => true, 'ageGroups' => $stmt->fetchAll()]);
            break;

        // ---- CREATE AGE GROUP ----
        case 'create_age_group':
            requireAdmin();
            $stmt = $db->prepare("
                INSERT INTO age_groups (group_key, icon, name, min_age, max_age, level_label, description, sort_order)
                VALUES (:key, :icon, :name, :min, :max, :lvl, :desc, :sort)
            ");
            $stmt->execute([
                'key'  => preg_replace('/[^a-z_]/', '', strtolower($body['groupKey'] ?? '')),
                'icon' => $body['icon'] ?? '📚',
                'name' => htmlspecialchars($body['name'] ?? '', ENT_QUOTES),
                'min'  => (int)($body['minAge'] ?? 0),
                'max'  => (int)($body['maxAge'] ?? 0),
                'lvl'  => htmlspecialchars($body['level'] ?? '', ENT_QUOTES),
                'desc' => htmlspecialchars($body['description'] ?? '', ENT_QUOTES),
                'sort' => (int)($body['sortOrder'] ?? 99)
            ]);
            jsonResponse(['success' => true, 'id' => (int)$db->lastInsertId()]);
            break;

        // ---- UPDATE AGE GROUP ----
        case 'update_age_group':
            requireAdmin();
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'ID required']);
            $db->prepare("
                UPDATE age_groups SET icon=:icon, name=:name, min_age=:min, max_age=:max, level_label=:lvl, description=:desc WHERE id=:id
            ")->execute([
                'icon' => $body['icon'] ?? '📚',
                'name' => htmlspecialchars($body['name'] ?? '', ENT_QUOTES),
                'min'  => (int)($body['minAge'] ?? 0),
                'max'  => (int)($body['maxAge'] ?? 0),
                'lvl'  => htmlspecialchars($body['level'] ?? '', ENT_QUOTES),
                'desc' => htmlspecialchars($body['description'] ?? '', ENT_QUOTES),
                'id'   => $id
            ]);
            jsonResponse(['success' => true, 'message' => 'Age group updated']);
            break;

        // ---- DELETE AGE GROUP ----
        case 'delete_age_group':
            requireAdmin();
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonResponse(['success' => false, 'message' => 'ID required']);
            $db->prepare("DELETE FROM age_groups WHERE id = :id")->execute(['id' => $id]);
            jsonResponse(['success' => true, 'message' => 'Deleted']);
            break;

        // ---- LIST REGISTRATIONS ----
        case 'list_registrations':
            requireAdmin();
            $school = $body['school'] ?? 'all';
            $status = $body['status'] ?? 'all';
            $sql = "SELECT r.*, s.name AS school_name, ag.name AS age_group_name FROM registrations r LEFT JOIN schools s ON s.school_key = r.school_key LEFT JOIN age_groups ag ON ag.group_key = r.age_group_key WHERE 1=1";
            $params = [];
            if ($school !== 'all') { $sql .= " AND r.school_key = :school"; $params['school'] = $school; }
            if ($status !== 'all') { $sql .= " AND r.status = :status"; $params['status'] = $status; }
            $sql .= " ORDER BY r.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $regs = $stmt->fetchAll();
            foreach ($regs as &$r) $r['interests'] = json_decode($r['interests'] ?? '[]', true);
            jsonResponse(['success' => true, 'registrations' => $regs]);
            break;

        // ---- UPDATE REGISTRATION STATUS ----
        case 'update_registration':
            requireAdmin();
            $id     = (int)($body['id'] ?? 0);
            $status = $body['status'] ?? '';
            if (!$id || !in_array($status, ['pending','approved','rejected'])) {
                jsonResponse(['success' => false, 'message' => 'Invalid data']);
            }
            $db->prepare("UPDATE registrations SET status=:s, reviewed_at=NOW(), reviewed_by=:uid WHERE id=:id")
               ->execute(['s'=>$status,'uid'=>$_SESSION['user_id'],'id'=>$id]);
            jsonResponse(['success' => true, 'message' => "Registration {$status}"]);
            break;

        // ---- POST ANNOUNCEMENT ----
        case 'post_announcement':
            requireAdmin();
            $title    = htmlspecialchars(trim($body['title'] ?? ''), ENT_QUOTES);
            $annBody  = htmlspecialchars(trim($body['body']  ?? ''), ENT_QUOTES);
            $target   = $body['school']   ?? 'all';
            $priority = $body['priority'] ?? 'normal';
            if (!$title || !$annBody) jsonResponse(['success'=>false,'message'=>'Title and body required']);
            if (!in_array($target,   ['all','brightstar','moonrise'])) $target = 'all';
            if (!in_array($priority, ['normal','urgent','info']))      $priority = 'normal';
            $db->prepare("INSERT INTO announcements (title, body, target, priority, posted_by) VALUES (:t,:b,:tg,:p,:uid)")
               ->execute(['t'=>$title,'b'=>$annBody,'tg'=>$target,'p'=>$priority,'uid'=>$_SESSION['user_id']]);
            jsonResponse(['success'=>true,'id'=>(int)$db->lastInsertId(),'message'=>'Announcement posted']);
            break;

        // ---- GET ANNOUNCEMENTS (public) ----
        case 'get_announcements':
            $school = $body['school'] ?? $_GET['school'] ?? 'all';
            $sql = "SELECT a.*, u.first_name, u.last_name FROM announcements a LEFT JOIN users u ON u.id = a.posted_by WHERE a.target = 'all' OR a.target = :school ORDER BY a.posted_at DESC LIMIT 20";
            $stmt = $db->prepare($sql);
            $stmt->execute(['school' => $school]);
            jsonResponse(['success' => true, 'announcements' => $stmt->fetchAll()]);
            break;

        // ---- DELETE ANNOUNCEMENT ----
        case 'delete_announcement':
            requireAdmin();
            $id = (int)($body['id'] ?? 0);
            $db->prepare("DELETE FROM announcements WHERE id = :id")->execute(['id' => $id]);
            jsonResponse(['success' => true, 'message' => 'Deleted']);
            break;

        // ---- DASHBOARD STATS ----
        case 'get_stats':
            requireAdmin();
            $stats = [];
            $stats['total_students'] = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
            $stats['total_teachers'] = $db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
            $stats['total_parents']  = $db->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();
            $stats['pending_regs']   = $db->query("SELECT COUNT(*) FROM registrations WHERE status='pending'")->fetchColumn();
            $stats['students_bs']    = $db->query("SELECT COUNT(*) FROM users WHERE role='student' AND school_key='brightstar'")->fetchColumn();
            $stats['students_mr']    = $db->query("SELECT COUNT(*) FROM users WHERE role='student' AND school_key='moonrise'")->fetchColumn();
            $stats['by_age_group']   = $db->query("SELECT age_group_key, COUNT(*) as count FROM users WHERE role='student' GROUP BY age_group_key")->fetchAll();
            jsonResponse(['success' => true, 'stats' => $stats]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
    }

} catch (PDOException $e) {
    error_log('Content API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}