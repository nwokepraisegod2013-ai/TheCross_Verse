<?php
/* ============================================
   ADMIN PANEL - Main Dashboard
   UPDATED: SaaS Platform Management
   Multi-tenant school management system
   ============================================ */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
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
  <title>Admin Panel – EduVerse Portal Platform</title>
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

        <div class="nav-section-title">🚀 SaaS Platform</div>
        <div class="nav-item" onclick="showPage('school-approvals')" id="nav-school-approvals">
          <span class="nav-icon">🏫</span> School Approvals
          <span class="nav-badge" id="schoolApprovalsBadge">0</span>
        </div>
        <div class="nav-item" onclick="showPage('subscriptions')" id="nav-subscriptions">
          <span class="nav-icon">💳</span> Subscriptions
        </div>
        <div class="nav-item" onclick="showPage('hosting-plans')" id="nav-hosting-plans">
          <span class="nav-icon">💰</span> Hosting Plans
        </div>
        <div class="nav-item" onclick="showPage('payments')" id="nav-payments">
          <span class="nav-icon">💵</span> Payments
        </div>
        <div class="nav-item" onclick="showPage('platform-news')" id="nav-platform-news">
          <span class="nav-icon">📰</span> Platform News
        </div>
        <div class="nav-item" onclick="showPage('advertisements')" id="nav-advertisements">
          <span class="nav-icon">📢</span> Advertisements
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
            <div class="user-role">Platform Admin</div>
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
          <input type="text" class="topbar-search" placeholder="🔍 Search..." id="globalSearch" oninput="handleSearch(this.value)">
          <button class="topbar-btn ping-badge" title="Notifications" onclick="showPage('registrations')">🔔</button>
          <button class="topbar-btn" title="View Site" onclick="window.open('index.php','_blank')">🌐</button>
        </div>
      </div>

      <!-- ===== DASHBOARD PAGE ===== -->
      <div class="admin-page active" id="page-dashboard">
        <div class="page-header">
          <h2 class="page-title">📊 Platform Dashboard</h2>
          <p class="page-subtitle">Welcome back! Here's what's happening across the platform.</p>
        </div>

        <div class="stats-grid">
          <div class="stat-card" style="--stat-color:#6BCBF7;">
            <span class="stat-icon">🏫</span>
            <div class="stat-value counter" data-target="0" id="stat-total-schools">0</div>
            <div class="stat-label">Registered Schools</div>
            <span class="stat-trend up" id="stat-schools-trend">Loading...</span>
          </div>
          <div class="stat-card" style="--stat-color:#4ADE80;">
            <span class="stat-icon">👥</span>
            <div class="stat-value counter" data-target="0" id="stat-students">0</div>
            <div class="stat-label">Total Students</div>
            <span class="stat-trend up" id="stat-students-trend">Loading...</span>
          </div>
          <div class="stat-card" style="--stat-color:#FACC15;">
            <span class="stat-icon">📋</span>
            <div class="stat-value counter" data-target="0" id="stat-pending-schools">0</div>
            <div class="stat-label">Pending School Approvals</div>
            <span class="stat-trend" style="color:#FACC15;" id="stat-pending-schools-trend">● Pending</span>
          </div>
          <div class="stat-card" style="--stat-color:#A78BFA;">
            <span class="stat-icon">💰</span>
            <div class="stat-value" id="stat-revenue">₦0.00</div>
            <div class="stat-label">Monthly Revenue</div>
            <span class="stat-trend up">+12%</span>
          </div>
        </div>

        <div class="two-col-grid">
          <!-- Recent School Registrations -->
          <div class="admin-table-wrap">
            <div class="table-header">
              <span class="table-title">🆕 Recent School Registrations</span>
              <button class="action-btn" onclick="showPage('school-approvals')">View All →</button>
            </div>
            <table class="admin-table">
              <thead><tr>
                <th>School</th><th>Plan</th><th>Contact</th><th>Status</th>
              </tr></thead>
              <tbody id="recentSchoolRegsTbody">
                <tr><td colspan="4" style="text-align:center;color:var(--admin-muted);padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>

          <!-- Student Registrations -->
          <div class="admin-table-wrap">
            <div class="table-header">
              <span class="table-title">🎓 Recent Student Registrations</span>
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
        </div>

        <div class="two-col-grid">
          <!-- Subscription Stats -->
          <div class="chart-card">
            <div class="chart-title">📊 Subscriptions by Plan</div>
            <div id="subscriptionStatsContainer">
              <div style="text-align:center;padding:2rem;color:var(--admin-muted);">
                <div class="spinner"></div>
              </div>
            </div>
          </div>

          <!-- Revenue Chart -->
          <div class="chart-card">
            <div class="chart-title">💰 Revenue Trend</div>
            <div id="revenueChartContainer">
              <div style="text-align:center;padding:2rem;color:var(--admin-muted);">
                <div class="spinner"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== SCHOOL APPROVALS PAGE ===== -->
      <div class="admin-page" id="page-school-approvals">
        <div class="page-header">
          <h2 class="page-title">🏫 School Registration Approvals</h2>
          <p class="page-subtitle">Review and approve school registration requests</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">📋 Pending Approvals</span>
            <div class="table-actions">
              <select class="admin-form-select" id="approvalStatusFilter" onchange="filterSchoolApprovals()" style="padding:0.4rem 1rem; font-size:0.85rem; width:auto;">
                <option value="pending">Pending</option>
                <option value="all">All Status</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>School Name</th><th>Contact Person</th><th>Email</th><th>Phone</th><th>Requested Plan</th><th>Subdomain</th><th>Date</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="schoolApprovalsTbody">
                <tr><td colspan="9" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== SUBSCRIPTIONS PAGE ===== -->
      <div class="admin-page" id="page-subscriptions">
        <div class="page-header">
          <h2 class="page-title">💳 School Subscriptions</h2>
          <p class="page-subtitle">Manage active school subscriptions and renewals</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">📊 Active Subscriptions</span>
            <div class="table-actions">
              <button class="action-btn edit" onclick="exportSubscriptions()">📥 Export</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>School</th><th>Plan</th><th>Status</th><th>Start Date</th><th>End Date</th><th>Billing Cycle</th><th>Amount</th><th>Usage</th><th>Actions</th>
              </tr></thead>
              <tbody id="subscriptionsTbody">
                <tr><td colspan="9" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== HOSTING PLANS PAGE ===== -->
      <div class="admin-page" id="page-hosting-plans">
        <div class="page-header">
          <h2 class="page-title">💰 Hosting Plans</h2>
          <p class="page-subtitle">Manage pricing tiers and plan features</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">📦 Available Plans</span>
            <div class="table-actions">
              <button class="btn-save" onclick="openPlanModal(null)">+ Add Plan</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>Plan Name</th><th>Monthly</th><th>Quarterly</th><th>Yearly</th><th>Students</th><th>Teachers</th><th>Storage</th><th>Features</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="hostingPlansTbody">
                <tr><td colspan="10" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== PAYMENTS PAGE ===== -->
      <div class="admin-page" id="page-payments">
        <div class="page-header">
          <h2 class="page-title">💵 Payment History</h2>
          <p class="page-subtitle">Track all subscription payments and transactions</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">💳 All Payments</span>
            <div class="table-actions">
              <button class="btn-save" onclick="openPaymentModal(null)">+ Record Payment</button>
              <button class="action-btn edit" onclick="exportPayments()">📥 Export</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>School</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Date</th><th>Processed By</th><th>Actions</th>
              </tr></thead>
              <tbody id="paymentsTbody">
                <tr><td colspan="8" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== PLATFORM NEWS PAGE ===== -->
      <div class="admin-page" id="page-platform-news">
        <div class="page-header">
          <h2 class="page-title">📰 Platform News</h2>
          <p class="page-subtitle">Manage news articles displayed on the homepage</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">📝 Published Articles</span>
            <div class="table-actions">
              <button class="btn-save" onclick="openNewsModal(null)">+ New Article</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>Title</th><th>Category</th><th>Author</th><th>Views</th><th>Published</th><th>Featured</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="platformNewsTbody">
                <tr><td colspan="8" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== ADVERTISEMENTS PAGE ===== -->
      <div class="admin-page" id="page-advertisements">
        <div class="page-header">
          <h2 class="page-title">📢 Advertisements</h2>
          <p class="page-subtitle">Manage banner ads and promotional content</p>
        </div>
        <div class="admin-table-wrap">
          <div class="table-header">
            <span class="table-title">🎯 Active Ads</span>
            <div class="table-actions">
              <button class="btn-save" onclick="openAdModal(null)">+ New Advertisement</button>
            </div>
          </div>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead><tr>
                <th>Title</th><th>Advertiser</th><th>Type</th><th>Position</th><th>Start Date</th><th>End Date</th><th>Impressions</th><th>Clicks</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="advertisementsTbody">
                <tr><td colspan="10" style="text-align:center;padding:2rem;"><div class="spinner"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ===== EXISTING PAGES (Student Management) ===== -->
      
      <!-- REGISTRATIONS PAGE -->
      <div class="admin-page" id="page-registrations">
        <div class="page-header">
          <h2 class="page-title">📋 Student Registration Management</h2>
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

      <!-- STUDENTS PAGE -->
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

      <!-- RESULTS PAGE -->
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

      <!-- USERS PAGE -->
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

      <!-- Placeholder pages -->
      <div class="admin-page" id="page-sessions"><h2>Academic Sessions & Terms</h2><p>Coming in next update...</p></div>
      <div class="admin-page" id="page-subjects"><h2>Subjects Management</h2><p>Coming in next update...</p></div>
      <div class="admin-page" id="page-attendance"><h2>Attendance Tracking</h2><p>Coming in next update...</p></div>
      <div class="admin-page" id="page-assignments"><h2>Assignments</h2><p>Coming in next update...</p></div>
      <div class="admin-page" id="page-fees"><h2>Fee Management</h2><p>Coming in next update...</p></div>

      <!-- SCHOOLS PAGE -->
      <div class="admin-page" id="page-schools">
        <div class="page-header">
          <h2 class="page-title">🏫 School Profiles</h2>
          <p class="page-subtitle">Edit school information and descriptions</p>
        </div>
        <div class="two-col-grid" id="schoolsContainer">
          <div style="text-align:center;padding:3rem;"><div class="spinner"></div></div>
        </div>
      </div>

      <!-- AGE GROUPS PAGE -->
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

      <!-- ANNOUNCEMENTS PAGE -->
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

      <!-- SETTINGS PAGE -->
      <div class="admin-page" id="page-settings">
        <div class="page-header">
          <h2 class="page-title">⚙️ Platform Settings</h2>
          <p class="page-subtitle">Configure platform options and admin credentials</p>
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
            <h3 style="margin-bottom:1.5rem;">⚙️ Platform Configuration</h3>
            <div class="admin-form-group">
              <div class="admin-form-label">Platform Name</div>
              <input type="text" class="admin-form-input" id="portalName" value="EduVerse Portal Platform" placeholder="Platform name">
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">Admin Email</div>
              <input type="email" class="admin-form-input" id="adminEmail" value="<?php echo htmlspecialchars($adminEmail); ?>" placeholder="admin@eduverse.com">
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">Student Registration Status</div>
              <select class="admin-form-select" id="regOpen">
                <option value="1">✅ Open - Accepting Student Registrations</option>
                <option value="0">🔒 Closed - No New Student Registrations</option>
              </select>
            </div>
            <div class="admin-form-group" style="margin-top:1rem;">
              <div class="admin-form-label">School Registration Status</div>
              <select class="admin-form-select" id="schoolRegOpen">
                <option value="1">✅ Open - Accepting School Registrations</option>
                <option value="0">🔒 Closed - No New School Registrations</option>
              </select>
            </div>
            <button class="btn-save" style="margin-top:1.2rem;" onclick="saveSettings()">💾 Save Settings</button>
          </div>
        </div>
      </div>

    </main>
  </div><!-- .admin-layout -->

  <!-- MODALS - Using merged modal file -->
  <?php include 'includes/admin-modals-merged.php'; ?>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

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