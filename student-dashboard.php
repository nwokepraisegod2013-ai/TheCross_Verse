<?php
/* ============================================
   EDUVERSE - STUDENT DASHBOARD
   Professional student portal with results, assignments, and more
   ============================================ */

session_start();
require_once __DIR__ . '/php/config.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch student data
try {
    $db = getDB();
    
    // Get student profile
    $stmt = $db->prepare("
        SELECT sp.*, u.username, u.first_name, u.last_name, u.email,
               s.name as school_name, ag.name as age_group_name
        FROM student_profiles sp
        JOIN users u ON sp.user_id = u.id
        LEFT JOIN schools s ON sp.school_key = s.school_key
        LEFT JOIN age_groups ag ON sp.age_group_key = ag.group_key
        WHERE sp.user_id = ?
    ");
    $stmt->execute([$userId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        die("Student profile not found");
    }
    
    // Get current session and term
    $currentSession = $db->query("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $currentTerm = $db->query("SELECT * FROM academic_terms WHERE is_current = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    // Get recent results
    $stmt = $db->prepare("
        SELECT sr.*, s.subject_name, at.type_name, sr.marks_obtained, sr.max_marks, sr.percentage, sr.grade
        FROM student_results sr
        JOIN subjects s ON sr.subject_id = s.id
        JOIN assessment_types at ON sr.assessment_type_id = at.id
        WHERE sr.student_id = ? AND sr.is_published = 1
        ORDER BY sr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$student['id']]);
    $recentResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get latest result card
    $stmt = $db->prepare("
        SELECT rc.*, ast.session_name, at.term_name
        FROM result_cards rc
        JOIN academic_sessions ast ON rc.session_id = ast.id
        JOIN academic_terms at ON rc.term_id = at.id
        WHERE rc.student_id = ? AND rc.is_published = 1
        ORDER BY rc.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student['id']]);
    $latestResultCard = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get pending assignments
    $stmt = $db->prepare("
        SELECT a.*, s.subject_name, asub.status as submission_status
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
        WHERE a.school_key = ? AND a.class_name = ? AND a.is_active = 1
        AND (asub.status IS NULL OR asub.status = 'pending')
        ORDER BY a.due_date ASC
        LIMIT 5
    ");
    $stmt->execute([$student['id'], $student['school_key'], $student['class_name']]);
    $pendingAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attendance summary (last 30 days)
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
        FROM attendance
        WHERE student_id = ? AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$student['id']]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    $attendancePercentage = $attendance['total_days'] > 0 ? 
        round(($attendance['present_days'] / $attendance['total_days']) * 100, 1) : 0;
    
    // Get notifications
    $stmt = $db->prepare("
        SELECT * FROM student_notifications
        WHERE student_id = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$student['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die("Error loading dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EduVerse</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/student-dashboard.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <span class="logo-icon">🎓</span>
                <span class="logo-text">EduVerse</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <a href="#dashboard" class="nav-item active" data-page="dashboard">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="#results" class="nav-item" data-page="results">
                <span class="nav-icon">📈</span>
                <span class="nav-text">Results</span>
            </a>
            <a href="#assignments" class="nav-item" data-page="assignments">
                <span class="nav-icon">📝</span>
                <span class="nav-text">Assignments</span>
            </a>
            <a href="#attendance" class="nav-item" data-page="attendance">
                <span class="nav-icon">📅</span>
                <span class="nav-text">Attendance</span>
            </a>
            <a href="#subjects" class="nav-item" data-page="subjects">
                <span class="nav-icon">📚</span>
                <span class="nav-text">Subjects</span>
            </a>
            <a href="#fees" class="nav-item" data-page="fees">
                <span class="nav-icon">💳</span>
                <span class="nav-text">Fees</span>
            </a>
            <a href="#profile" class="nav-item" data-page="profile">
                <span class="nav-icon">👤</span>
                <span class="nav-text">Profile</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="php/logout.php" class="logout-btn">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="page-title" id="pageTitle">Dashboard</h1>
            </div>
            
            <div class="header-right">
                <div class="notifications-btn" id="notificationsBtn">
                    <span class="notification-icon">🔔</span>
                    <?php if (count($notifications) > 0): ?>
                    <span class="notification-badge"><?php echo count($notifications); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="user-menu">
                    <img src="<?php echo $student['photo_url'] ?? 'images/default-avatar.png'; ?>" 
                         alt="Profile" class="user-avatar">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($student['student_id']); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="content-area">
            <!-- Dashboard Page -->
            <div class="page active" id="page-dashboard">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <span class="stat-label">Overall Grade</span>
                            <span class="stat-value"><?php echo $latestResultCard['overall_grade'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="stat-trend">
                            <span class="trend-value"><?php echo number_format($latestResultCard['overall_percentage'] ?? 0, 1); ?>%</span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-success">
                        <div class="stat-icon">📅</div>
                        <div class="stat-info">
                            <span class="stat-label">Attendance</span>
                            <span class="stat-value"><?php echo $attendancePercentage; ?>%</span>
                        </div>
                        <div class="stat-trend">
                            <span class="trend-label">Last 30 days</span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">📝</div>
                        <div class="stat-info">
                            <span class="stat-label">Pending Tasks</span>
                            <span class="stat-value"><?php echo count($pendingAssignments); ?></span>
                        </div>
                        <div class="stat-trend">
                            <span class="trend-label">Assignments</span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-info">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-info">
                            <span class="stat-label">Class Rank</span>
                            <span class="stat-value"><?php echo $latestResultCard['rank_in_class'] ?? '-'; ?></span>
                        </div>
                        <div class="stat-trend">
                            <span class="trend-label">Out of <?php echo $latestResultCard['total_students'] ?? '-'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Content -->
                <div class="dashboard-grid">
                    <!-- Recent Results -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2 class="card-title">📈 Recent Results</h2>
                            <a href="#results" class="card-action" onclick="showPage('results')">View All →</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentResults) > 0): ?>
                                <div class="results-list">
                                    <?php foreach ($recentResults as $result): ?>
                                    <div class="result-item">
                                        <div class="result-subject"><?php echo htmlspecialchars($result['subject_name']); ?></div>
                                        <div class="result-type"><?php echo htmlspecialchars($result['type_name']); ?></div>
                                        <div class="result-marks">
                                            <span class="marks"><?php echo $result['marks_obtained']; ?>/<?php echo $result['max_marks']; ?></span>
                                            <span class="grade grade-<?php echo strtolower($result['grade']); ?>"><?php echo $result['grade']; ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <span class="empty-icon">📄</span>
                                    <p>No results published yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pending Assignments -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2 class="card-title">📝 Pending Assignments</h2>
                            <a href="#assignments" class="card-action" onclick="showPage('assignments')">View All →</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($pendingAssignments) > 0): ?>
                                <div class="assignments-list">
                                    <?php foreach ($pendingAssignments as $assignment): ?>
                                    <div class="assignment-item">
                                        <div class="assignment-info">
                                            <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                            <div class="assignment-subject"><?php echo htmlspecialchars($assignment['subject_name']); ?></div>
                                        </div>
                                        <div class="assignment-due">
                                            <span class="due-date">
                                                <?php 
                                                $dueDate = new DateTime($assignment['due_date']);
                                                $now = new DateTime();
                                                $diff = $now->diff($dueDate);
                                                if ($dueDate < $now) {
                                                    echo '<span class="overdue">Overdue</span>';
                                                } else {
                                                    echo $diff->days . ' days';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <span class="empty-icon">✅</span>
                                    <p>All caught up!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Attendance Chart -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2 class="card-title">📅 Attendance Summary</h2>
                            <a href="#attendance" class="card-action" onclick="showPage('attendance')">View Details →</a>
                        </div>
                        <div class="card-body">
                            <div class="attendance-summary">
                                <div class="attendance-chart">
                                    <div class="chart-circle">
                                        <svg viewBox="0 0 36 36" class="circular-chart">
                                            <path class="circle-bg"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                            />
                                            <path class="circle"
                                                stroke-dasharray="<?php echo $attendancePercentage; ?>, 100"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                            />
                                            <text x="18" y="20.35" class="percentage"><?php echo $attendancePercentage; ?>%</text>
                                        </svg>
                                    </div>
                                </div>
                                <div class="attendance-stats">
                                    <div class="attendance-stat">
                                        <span class="stat-dot stat-present"></span>
                                        <span class="stat-text">Present: <?php echo $attendance['present_days'] ?? 0; ?></span>
                                    </div>
                                    <div class="attendance-stat">
                                        <span class="stat-dot stat-absent"></span>
                                        <span class="stat-text">Absent: <?php echo $attendance['absent_days'] ?? 0; ?></span>
                                    </div>
                                    <div class="attendance-stat">
                                        <span class="stat-dot stat-late"></span>
                                        <span class="stat-text">Late: <?php echo $attendance['late_days'] ?? 0; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Page (will load via AJAX) -->
            <div class="page" id="page-results"></div>
            
            <!-- Assignments Page -->
            <div class="page" id="page-assignments"></div>
            
            <!-- Attendance Page -->
            <div class="page" id="page-attendance"></div>
            
            <!-- Subjects Page -->
            <div class="page" id="page-subjects"></div>
            
            <!-- Fees Page -->
            <div class="page" id="page-fees"></div>
            
            <!-- Profile Page -->
            <div class="page" id="page-profile"></div>
        </div>
    </main>

    <!-- Notifications Dropdown -->
    <div class="notifications-dropdown" id="notificationsDropdown">
        <div class="notifications-header">
            <h3>Notifications</h3>
            <button class="mark-all-read">Mark all read</button>
        </div>
        <div class="notifications-list">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="notification-item" data-id="<?php echo $notif['id']; ?>">
                    <div class="notification-icon notification-<?php echo $notif['type']; ?>">
                        <?php
                        $icons = [
                            'result' => '📊',
                            'assignment' => '📝',
                            'fee' => '💳',
                            'announcement' => '📢',
                            'attendance' => '📅',
                            'general' => 'ℹ️'
                        ];
                        echo $icons[$notif['type']] ?? 'ℹ️';
                        ?>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                        <div class="notification-time">
                            <?php 
                            $time = new DateTime($notif['created_at']);
                            echo $time->format('M d, Y - h:i A');
                            ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-notifications">
                    <span class="empty-icon">🔔</span>
                    <p>No new notifications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/student-dashboard.js"></script>
</body>
</html>