<?php
/* ============================================
   ADMIN PANEL - Main Dashboard
   Dynamic PHP version with database integration
   ============================================ */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

require_once __DIR__ . '/php/config.php';

// Get admin info
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$adminName = $admin['first_name'] . ' ' . $admin['last_name'];
$adminEmail = $admin['email'] ?? 'admin@eduverse.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel – EduVerse Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/admin.css">
  <link rel="stylesheet" href="css/animations.css">
</head>
<body>

  <div class="admin-layout">

    <!-- ===== SIDEBAR ===== -->
    <aside class="admin-sidebar" id="adminSidebar">
      <div class="sidebar-brand">
        <span class="brand-icon">🎓</span>
        <span class="brand-text">EduVerse</span>
      </div>

      <div class="sidebar-school-switcher">
        <div class="school-switch-label">Active School</div>
        <button class="school-switch-btn" onclick="openSchoolModal()">
          <span id="activeSchoolLabel">🏫 All Schools</span>
          <span>⇅</span>
        </button>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section-title">Main</div>
        <div class="nav-item active" onclick="showPage('dashboard')" id="nav-dashboard">
          <span class="nav-icon">📊</span> Dashboard
        </div>
        <div class="nav-item" onclick="showPage('registrations')" id="nav-registrations">
          <span class="nav-icon">📋</span> Registrations
          <span class="nav-badge" id="pendingBadge">0</span>
        </div>
        <div class="nav-item" onclick="showPage('students')" id="nav-students">
          <span class="nav-icon">🎓</span> Students
        </div>
        <div class="nav-item" onclick="showPage('results')" id="nav-results">
          <span class="nav-icon">📝</span> Results
        </div>
        <div class="nav-item" onclick="showPage('users')" id="nav-users">
          <span class="nav-icon">👥</span> Users
        </div>

        <div class="nav-section-title">Academic</div>
        <div class="nav-item" onclick="showPage('sessions')" id="nav-sessions">
          <span class="nav-icon">📅</span> Sessions & Terms
        </div>
        <div class="nav-item" onclick="showPage('subjects')" id="nav-subjects">
          <span class="nav-icon">📚</span> Subjects
        </div>
        <div class="nav-item" onclick="showPage('attendance')" id="nav-attendance">
          <span class="nav-icon">✓</span> Attendance
        </div>
        <div class="nav-item" onclick="showPage('assignments')" id="nav-assignments">
          <span class="nav-icon">📄</span> Assignments
        </div>

        <div class="nav-section-title">Content</div>
        <div class="nav-item" onclick="showPage('schools')" id="nav-schools">
          <span class="nav-icon">🏫</span> School Profiles
        </div>
        <div class="nav-item" onclick="showPage('agegroups')" id="nav-agegroups">
          <span class="nav-icon">🎂</span> Age Groups
        </div>
        <div class="nav-item" onclick="showPage('announcements')" id="nav-announcements">
          <span class="nav-icon">📢</span> Announcements
        </div>

        <div class="nav-section-title">Financial</div>
        <div class="nav-item" onclick="showPage('fees')" id="nav-fees">
          <span class="nav-icon">💰</span> Fee Management
        </div>

        <div class="nav-section-title">Settings</div>
        <div class="nav-item" onclick="showPage('settings')" id="nav-settings">
          <span class="nav-icon">⚙️</span> Settings
        </div>
      </nav>

      <div class="sidebar-footer">
        <div class="admin-user">
          <div class="user-avatar"><?php echo strtoupper(substr($admin['first_name'], 0, 1)); ?></div>
          <div class="user-info">
            <div class="user-name" id="adminName"><?php echo htmlspecialchars($adminName); ?></div>
            <div class="user-role">Super Admin</div>
          </div>
          <button class="logout-btn" onclick="handleLogout()" title="Logout">↩</button>
        </div>
      </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="admin-main">
      <!-- Topbar -->
      <div class="admin-topbar">
        <div style="display:flex; align-items:center; gap:1rem;">
          <button class="topbar-btn" id="sidebarToggle" onclick="toggleSidebar()" style="display:none;">☰</button>
          <h2 class="topbar-title" id="topbarTitle">Dashboard</h2>
        </div>
        <div class="topbar-right">
          <input type="text" class="topbar-search" placeholder="🔍 Search students..." id="globalSearch" oninput="handleSearch(this.value)">
          <button class="topbar-btn ping-badge" title="Notifications" onclick="showPage('registrations')">🔔</button>
          <button class="topbar-btn" title="View Site" onclick="window.open('index.php','_blank')">🌐</button>
        </div>
      </div>

      <!-- ===== DASHBOARD PAGE ===== -->
      <div class="admin-page active" id="page-dashboard">
        <div class="page-header">
          <h2 class="page-title">📊 Dashboard Overview</h2>
          <p class="page-subtitle">Welcome back! Here's what's happening across your schools.</p>
        </div>

        <div class="stats-grid">
          <div class="stat-card" style="--stat-color:#6BCBF7;">
            <span class="stat-icon">👥</span>
            <div class="stat-value counter" data-target="0" id="stat-students">0</div>
            <div class="stat-label">Total Students</div>
            <span class="stat-trend up" id="stat-students-trend">Loading...</span>
          </div>
          <div class="stat-card" style="--stat-color:#A78BFA;">
            <span class="stat-icon">👨‍🏫</span>
            <div class="stat-value counter" data-target="0" id="stat-users">0</div>
            <div class="stat-label">Total Users</div>
            <span class="stat-trend up" id="stat-users-trend">Loading...</span>
          </div>
          <div class="stat-card" style="--stat-color:#FACC15;">
            <span class="stat-icon">📋</span>
            <div class="stat-value counter" data-target="0" id="stat-pending">0</div>
            <div class="stat-label">Pending Registrations</div>
            <span class="stat-trend" style="color:#FACC15;" id="stat-pending-trend">● Pending</span>
          </div>
          <div class="stat-card" style="--stat-color:#4ADE80;">
            <span class="stat-icon">🏫</span>
            <div class="stat-value" id="stat-schools">2</div>
            <div class="stat-label">Active Schools</div>
            <span class="stat-trend up">✓ All Good</span>
          </div>
        </div>

        <div class="two-col-grid">
          <!-- Recent Registrations -->
          <div class="admin-table-wrap">
            <div class="table-header">
              <span class="table-title">🆕 Recent Registrations</span>
              <button class="action-btn" onclick="showPage('registrations')">View All →</button>
            </div>
            <table class="admin-table">
              <thead><tr>
                <th>Student</th><th>School</th><th>Age Group</th><th>Status</th>
              </tr></thead>
              <tbody id="recentRegsTbody">
                <tr><td colspan="4" style="text-align:center;color:var(--admin-muted);padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>

          <!-- Stats by School -->
          <div class="chart-card">
            <div class="chart-title">📈 Students by School</div>
            <div id="schoolStatsContainer">
              <div style="text-align:center;padding:2rem;color:var(--admin-muted);">
                <div class="spinner"></div>
              </div>
            </div>
            
            <div class="chart-title" style="margin-top:1.5rem;">📊 By Age Group</div>
            <div class="mini-bar-chart" id="ageBarChart">
              <div style="text-align:center;padding:1rem;color:var(--admin-muted);">Loading...</div>
            </div>
          </div>
        </div>

        <style>
          @keyframes barGrow { from{width:0} }
          .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: var(--admin-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: inline-block;
          }
          @keyframes spin { to { transform: rotate(360deg); } }
        </style>
      </div>

      <!-- ===== REGISTRATIONS PAGE ===== -->
      <div class="admin-page" id="page-registrations">
        <div class="page-header">
          <h2 class="page-title">📋 Registration Management</h2>
          <p class="page-subtitle">Review, approve, and manage student registrations</p>
        </div>
        <div class="school-header-bar">
          <span style="font-size:0.88rem; color:var(--admin-muted); font-weight:700;">Filter by school:</span>
          <button class="school-selector-btn active" onclick="filterBySchool('all',this)" id="filter-all">All Schools</button>
          <button class="school-selector-btn brightstar" onclick="filterBySchool('brightstar',this)" id="filter-brightstar">🦁 BrightStar</button>
          <button class="school-selector-btn" onclick="filterBySchool('moonrise',this)" id="filter-moonrise">🦅 Moonrise</button>
          <div style="margin-left:auto; display:flex; gap:0.5rem;">
            <select class="admin-form-select" id="statusFilter" onchange="filterRegistrations()" style="padding:0.4rem 1rem; font-size:0.85rem; width:auto;">
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">📝 Applications</span>
            <div class="table-actions">
              <button class="action-btn edit" onclick="exportRegistrations()" title="Export CSV">📥 Export</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>Student Name</th><th>School</th><th>Age Group</th><th>Parent</th><th>Email</th><th>Date</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="regsTbody">
                <tr><td colspan="8" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== STUDENTS PAGE ===== -->
      <div class="admin-page" id="page-students">
        <div class="page-header">
          <h2 class="page-title">🎓 Student Management</h2>
          <p class="page-subtitle">View and manage enrolled students</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">👨‍🎓 All Students</span>
            <div class="table-actions">
              <button class="btn-save" onclick="exportStudents()">📥 Export</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>Student ID</th><th>Name</th><th>School</th><th>Class</th><th>Age Group</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="studentsTbody">
                <tr><td colspan="7" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== RESULTS PAGE ===== -->
      <div class="admin-page" id="page-results">
        <div class="page-header">
          <h2 class="page-title">📝 Results Management</h2>
          <p class="page-subtitle">Upload and manage student results</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">📊 Recent Results</span>
            <div class="table-actions">
              <button class="btn-save" onclick="openResultModal()">+ Upload Results</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>Student</th><th>Subject</th><th>Session/Term</th><th>Assessment</th><th>Marks</th><th>Grade</th><th>Published</th><th>Actions</th>
              </tr></thead>
              <tbody id="resultsTbody">
                <tr><td colspan="8" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== USERS PAGE ===== -->
      <div class="admin-page" id="page-users">
        <div class="page-header">
          <h2 class="page-title">👥 User Management</h2>
          <p class="page-subtitle">Manage login credentials for students, parents, and teachers</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">👤 All Users</span>
            <div class="table-actions">
              <button class="btn-save" onclick="openUserModal(null)">+ Add User</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th>
              </tr></thead>
              <tbody id="usersTbody">
                <tr><td colspan="7" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Additional pages content here... (sessions, subjects, schools, etc.) -->
      <!-- I'll create them in the next files to keep this manageable -->

      <!-- Placeholder pages -->
      <div class="admin-page" id="page-sessions"><h2>Academic Sessions & Terms</h2><p>Coming in next update...</p></div>
      <div class="admin-page" id="page-subjects"><h2>Subjects Management</h2><p>Coming in next update...</p></div>
      <div class="admin-page" id="page-attendance"><h2>Attendance Tracking</h2><p>Coming in next update...</p></div>
      <div class="admin-page" id="page-assignments"><h2>Assignments</h2><p>Coming in next update...</p></div>
      <div class="admin-page" id="page-fees"><h2>Fee Management</h2><p>Coming in next update...</p></div>

      <!-- ===== SCHOOLS PAGE ===== -->
      <div class="admin-page" id="page-schools">
        <div class="page-header">
          <h2 class="page-title">🏫 School Profiles</h2>
          <p class="page-subtitle">Edit school information and descriptions</p>
        </div>
        <div class="two-col-grid" id="schoolsContainer">
          <div style="text-align:center;padding:3rem;"><div class="spinner"></div></div>
        </div>
      </div>

      <!-- ===== AGE GROUPS PAGE ===== -->
      <div class="admin-page" id="page-agegroups">
        <div class="page-header">
          <h2 class="page-title">🎂 Age Groups</h2>
          <p class="page-subtitle">Manage age group definitions</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">Age Group Configuration</span>
            <button class="btn-save" onclick="openAgeModal(null)">+ Add Group</button>
          </div>
          <table class="admin-table">
            <thead><tr>
              <th>Icon</th><th>Name</th><th>Age Range</th><th>Level</th><th>Description</th><th>Active</th><th>Actions</th>
            </tr></thead>
            <tbody id="ageTbody">
              <tr><td colspan="7" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ===== ANNOUNCEMENTS PAGE ===== -->
      <div class="admin-page" id="page-announcements">
        <div class="page-header">
          <h2 class="page-title">📢 Announcements</h2>
          <p class="page-subtitle">Post announcements visible on the portal</p>
        </div>
        <div class="two-col-grid" style="align-items:start;">
          <div class="chart-card">
            <h3 style="margin-bottom:1.2rem; font-size:1rem;">✍️ New Announcement</h3>
            <div class="admin-form-group">
              <div class="admin-form-label">Title</div>
              <input type="text" class="admin-form-input" id="annTitle" placeholder="Announcement title">
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">Message</div>
              <textarea class="admin-form-textarea" id="annBody" placeholder="Write your announcement here..." rows="4"></textarea>
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">Target School</div>
              <select class="admin-form-select" id="annSchool">
                <option value="">📢 All Schools</option>
                <option value="brightstar">🦁 BrightStar Only</option>
                <option value="moonrise">🦅 Moonrise Only</option>
              </select>
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">Priority</div>
              <select class="admin-form-select" id="annPriority">
                <option value="medium">Normal</option>
                <option value="high">🚨 High Priority</option>
                <option value="urgent">⚠️ Urgent</option>
                <option value="low">ℹ️ Info</option>
              </select>
            </div>
            <button class="btn-save" style="margin-top:1.2rem; width:100%;" onclick="postAnnouncement()">📢 Post Announcement</button>
          </div>
          <div class="chart-card">
            <h3 style="margin-bottom:1.2rem; font-size:1rem;">📋 Posted Announcements</h3>
            <div id="annList" style="display:flex; flex-direction:column; gap:0.8rem;">
              <div style="text-align:center;padding:2rem;color:var(--admin-muted);">
                <div class="spinner"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== SETTINGS PAGE ===== -->
      <div class="admin-page" id="page-settings">
        <div class="page-header">
          <h2 class="page-title">⚙️ Portal Settings</h2>
          <p class="page-subtitle">Configure portal options and admin credentials</p>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:start;">
          <div class="chart-card">
            <h3 style="margin-bottom:1.5rem;">🔐 Change Admin Password</h3>
            <div class="admin-form-group">
              <div class="admin-form-label">Current Password</div>
              <input type="password" class="admin-form-input" id="curPass" placeholder="Current password">
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">New Password</div>
              <input type="password" class="admin-form-input" id="newPass" placeholder="New password">
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">Confirm Password</div>
              <input type="password" class="admin-form-input" id="confPass" placeholder="Confirm new password">
            </div>
            <button class="btn-save" style="margin-top:1.2rem;" onclick="changePassword()">🔐 Update Password</button>
          </div>
          <div class="chart-card">
            <h3 style="margin-bottom:1.5rem;">⚙️ Portal Configuration</h3>
            <div class="admin-form-group">
              <div class="admin-form-label">Portal Name</div>
              <input type="text" class="admin-form-input" id="portalName" value="EduVerse Portal" placeholder="Portal name">
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">Admin Email</div>
              <input type="email" class="admin-form-input" id="adminEmail" value="<?php echo htmlspecialchars($adminEmail); ?>" placeholder="admin@school.edu">
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">Registration Status</div>
              <select class="admin-form-select" id="regOpen">
                <option value="1">✅ Open - Accepting Registrations</option>
                <option value="0">🔒 Closed - No New Registrations</option>
              </select>
            </div>
            <button class="btn-save" style="margin-top:1.2rem;" onclick="saveSettings()">💾 Save Settings</button>
          </div>
        </div>
      </div>

    </main>
  </div><!-- .admin-layout -->

  <!-- MODALS - keeping your existing modal structure -->
  <?php include 'includes/admin-modals.php'; ?>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <script>
    // Pass PHP session data to JavaScript
    const SESSION_DATA = {
      userId: <?php echo $_SESSION['user_id']; ?>,
      username: '<?php echo $_SESSION['username']; ?>',
      role: '<?php echo $_SESSION['role']; ?>',
      adminName: '<?php echo addslashes($adminName); ?>'
    };
  </script>
  <script src="js/admin.js"></script>
</body>
</html>