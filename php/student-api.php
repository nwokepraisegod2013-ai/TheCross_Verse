<?php
/* ============================================
   EDUVERSE STUDENT API
   AJAX endpoints for student dashboard
   ============================================ */

session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$page = $_GET['page'] ?? '';

try {
    $db = getDB();
    
    // Get student profile
    $stmt = $db->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        jsonResponse(['success' => false, 'message' => 'Student profile not found']);
    }
    
    // Route requests
    if ($page) {
        handlePageLoad($page, $student, $db);
    } elseif ($action) {
        handleAction($action, $student, $db);
    } else {
        jsonResponse(['success' => false, 'message' => 'No action specified']);
    }
    
} catch (Exception $e) {
    error_log('Student API Error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error'], 500);
}

// Page load handler
function handlePageLoad($page, $student, $db) {
    $html = '';
    
    switch ($page) {
        case 'results':
            $html = generateResultsHTML($student, $db);
            break;
        case 'assignments':
            $html = generateAssignmentsHTML($student, $db);
            break;
        case 'attendance':
            $html = generateAttendanceHTML($student, $db);
            break;
        default:
            $html = '<p>Page not found</p>';
    }
    
    jsonResponse(['success' => true, 'html' => $html]);
}

// Generate results HTML
function generateResultsHTML($student, $db) {
    $stmt = $db->prepare("
        SELECT rc.*, ases.session_name, at.term_name
        FROM result_cards rc
        JOIN academic_sessions ases ON rc.session_id = ases.id
        JOIN academic_terms at ON rc.term_id = at.id
        WHERE rc.student_id = ? AND rc.is_published = 1
        ORDER BY ases.session_year DESC
    ");
    $stmt->execute([$student['id']]);
    $resultCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="results-page">
        <h2>📈 My Results</h2>
        <?php if (count($resultCards) > 0): ?>
            <?php foreach ($resultCards as $card): ?>
            <div class="result-card-item">
                <h3><?php echo htmlspecialchars($card['session_name'] . ' - ' . $card['term_name']); ?></h3>
                <p>Grade: <?php echo $card['overall_grade']; ?> (<?php echo $card['overall_percentage']; ?>%)</p>
                <p>Rank: <?php echo $card['rank_in_class']; ?> / <?php echo $card['total_students']; ?></p>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No results published yet</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Generate assignments HTML  
function generateAssignmentsHTML($student, $db) {
    return '<div><h2>📝 Assignments</h2><p>Coming soon...</p></div>';
}

// Generate attendance HTML
function generateAttendanceHTML($student, $db) {
    return '<div><h2>📅 Attendance</h2><p>Coming soon...</p></div>';
}

// Action handler
function handleAction($action, $student, $db) {
    switch ($action) {
        case 'mark_notification_read':
            markNotificationRead($student, $db);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action']);
    }
}

function markNotificationRead($student, $db) {
    $body = getRequestBody();
    $notificationId = $body['notification_id'] ?? 0;
    
    $stmt = $db->prepare("UPDATE student_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND student_id = ?");
    $stmt->execute([$notificationId, $student['id']]);
    
    jsonResponse(['success' => true]);
}