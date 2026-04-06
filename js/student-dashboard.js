/* ============================================
   EDUVERSE STUDENT DASHBOARD - JAVASCRIPT
   Interactive functionality and AJAX loading
   ============================================ */

'use strict';

// Global state
const state = {
  currentPage: 'dashboard',
  notificationsOpen: false,
  sidebarOpen: true
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  console.log('🎓 Student Dashboard Initialized');
  
  initializeNavigation();
  initializeNotifications();
  initializeMobileMenu();
  loadGradientForChart();
});

// ============ NAVIGATION ============
function initializeNavigation() {
  const navItems = document.querySelectorAll('.nav-item');
  
  navItems.forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const page = item.dataset.page;
      if (page) {
        showPage(page);
      }
    });
  });
}

function showPage(pageName) {
  // Update nav items
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.remove('active');
    if (item.dataset.page === pageName) {
      item.classList.add('active');
    }
  });
  
  // Update page title
  const titles = {
    dashboard: 'Dashboard',
    results: 'My Results',
    assignments: 'Assignments',
    attendance: 'Attendance',
    subjects: 'Subjects',
    fees: 'Fee Payments',
    profile: 'My Profile'
  };
  
  document.getElementById('pageTitle').textContent = titles[pageName] || pageName;
  
  // Hide all pages
  document.querySelectorAll('.page').forEach(page => {
    page.classList.remove('active');
  });
  
  // Show selected page
  const targetPage = document.getElementById(`page-${pageName}`);
  if (targetPage) {
    targetPage.classList.add('active');
    
    // Load page content if empty (AJAX)
    if (pageName !== 'dashboard' && targetPage.innerHTML.trim() === '') {
      loadPageContent(pageName, targetPage);
    }
  }
  
  state.currentPage = pageName;
  
  // Close mobile sidebar
  if (window.innerWidth <= 768) {
    document.querySelector('.sidebar').classList.remove('active');
  }
}

// Make showPage available globally
window.showPage = showPage;

// ============ AJAX PAGE LOADING ============
async function loadPageContent(pageName, container) {
  container.innerHTML = '<div class="loading-spinner">Loading...</div>';
  
  try {
    const response = await fetch(`php/student-api.php?page=${pageName}`);
    const data = await response.json();
    
    if (data.success) {
      container.innerHTML = data.html;
      
      // Re-initialize any page-specific functionality
      if (pageName === 'results') {
        initializeResultsPage();
      } else if (pageName === 'assignments') {
        initializeAssignmentsPage();
      }
    } else {
      container.innerHTML = `<div class="error-message">${data.message || 'Failed to load content'}</div>`;
    }
  } catch (error) {
    console.error('Error loading page:', error);
    container.innerHTML = '<div class="error-message">Failed to load content. Please try again.</div>';
  }
}

// ============ NOTIFICATIONS ============
function initializeNotifications() {
  const notifBtn = document.getElementById('notificationsBtn');
  const notifDropdown = document.getElementById('notificationsDropdown');
  
  if (!notifBtn || !notifDropdown) return;
  
  notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleNotifications();
  });
  
  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
      closeNotifications();
    }
  });
  
  // Mark all as read
  const markAllBtn = notifDropdown.querySelector('.mark-all-read');
  if (markAllBtn) {
    markAllBtn.addEventListener('click', markAllNotificationsRead);
  }
  
  // Individual notification clicks
  const notifItems = notifDropdown.querySelectorAll('.notification-item');
  notifItems.forEach(item => {
    item.addEventListener('click', () => {
      const notifId = item.dataset.id;
      markNotificationRead(notifId);
      
      // Navigate to linked page if exists
      const link = item.dataset.link;
      if (link) {
        window.location.href = link;
      }
    });
  });
}

function toggleNotifications() {
  const dropdown = document.getElementById('notificationsDropdown');
  state.notificationsOpen = !state.notificationsOpen;
  
  if (state.notificationsOpen) {
    dropdown.classList.add('active');
  } else {
    dropdown.classList.remove('active');
  }
}

function closeNotifications() {
  const dropdown = document.getElementById('notificationsDropdown');
  dropdown.classList.remove('active');
  state.notificationsOpen = false;
}

async function markNotificationRead(notificationId) {
  try {
    const response = await fetch('php/student-api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'mark_notification_read',
        notification_id: notificationId
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Update UI
      updateNotificationBadge(-1);
    }
  } catch (error) {
    console.error('Error marking notification as read:', error);
  }
}

async function markAllNotificationsRead() {
  try {
    const response = await fetch('php/student-api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'mark_all_notifications_read'
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Update UI
      const badge = document.querySelector('.notification-badge');
      if (badge) {
        badge.style.display = 'none';
      }
      
      // Remove all notification items
      const notifList = document.querySelector('.notifications-list');
      notifList.innerHTML = `
        <div class="empty-notifications">
          <span class="empty-icon">🔔</span>
          <p>No new notifications</p>
        </div>
      `;
    }
  } catch (error) {
    console.error('Error marking all notifications as read:', error);
  }
}

function updateNotificationBadge(change) {
  const badge = document.querySelector('.notification-badge');
  if (!badge) return;
  
  let current = parseInt(badge.textContent) || 0;
  current += change;
  
  if (current <= 0) {
    badge.style.display = 'none';
  } else {
    badge.textContent = current;
    badge.style.display = 'flex';
  }
}

// ============ MOBILE MENU ============
function initializeMobileMenu() {
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.querySelector('.sidebar');
  
  if (!mobileBtn || !sidebar) return;
  
  mobileBtn.addEventListener('click', () => {
    sidebar.classList.toggle('active');
  });
  
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
      if (!sidebar.contains(e.target) && !mobileBtn.contains(e.target)) {
        sidebar.classList.remove('active');
      }
    }
  });
}

// ============ SVG GRADIENT FOR CHART ============
function loadGradientForChart() {
  const svg = document.querySelector('.circular-chart');
  if (!svg) return;
  
  // Create gradient definition
  const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
  const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
  gradient.setAttribute('id', 'gradient');
  gradient.setAttribute('x1', '0%');
  gradient.setAttribute('y1', '0%');
  gradient.setAttribute('x2', '100%');
  gradient.setAttribute('y2', '100%');
  
  const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
  stop1.setAttribute('offset', '0%');
  stop1.setAttribute('style', 'stop-color:#10b981;stop-opacity:1');
  
  const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
  stop2.setAttribute('offset', '100%');
  stop2.setAttribute('style', 'stop-color:#14b8a6;stop-opacity:1');
  
  gradient.appendChild(stop1);
  gradient.appendChild(stop2);
  defs.appendChild(gradient);
  svg.insertBefore(defs, svg.firstChild);
}

// ============ RESULTS PAGE SPECIFIC ============
function initializeResultsPage() {
  console.log('Results page initialized');
  
  // Add filter functionality
  const filterBtns = document.querySelectorAll('.filter-btn');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      
      const filter = btn.dataset.filter;
      filterResults(filter);
    });
  });
  
  // Add download result card functionality
  const downloadBtns = document.querySelectorAll('.download-result-card');
  downloadBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const resultCardId = btn.dataset.resultId;
      downloadResultCard(resultCardId);
    });
  });
}

function filterResults(filter) {
  const resultItems = document.querySelectorAll('.result-card-item');
  
  resultItems.forEach(item => {
    if (filter === 'all') {
      item.style.display = 'block';
    } else {
      const itemTerm = item.dataset.term;
      item.style.display = itemTerm === filter ? 'block' : 'none';
    }
  });
}

async function downloadResultCard(resultCardId) {
  try {
    window.location.href = `php/student-api.php?action=download_result_card&id=${resultCardId}`;
  } catch (error) {
    console.error('Error downloading result card:', error);
    alert('Failed to download result card');
  }
}

// ============ ASSIGNMENTS PAGE SPECIFIC ============
function initializeAssignmentsPage() {
  console.log('Assignments page initialized');
  
  // Add submit assignment functionality
  const submitBtns = document.querySelectorAll('.submit-assignment-btn');
  submitBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const assignmentId = btn.dataset.assignmentId;
      openSubmissionModal(assignmentId);
    });
  });
}

function openSubmissionModal(assignmentId) {
  // Implementation for submission modal
  console.log('Opening submission modal for assignment:', assignmentId);
  // This would open a modal for file upload and text submission
}

// ============ UTILITY FUNCTIONS ============
function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  
  toast.style.cssText = `
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    padding: 1rem 1.5rem;
    background: rgba(26, 26, 62, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    animation: slideIn 0.3s ease;
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

function formatTime(dateString) {
  const date = new Date(dateString);
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  });
}

// ============ EXPORT FUNCTIONS ============
window.studentDashboard = {
  showPage,
  showToast,
  formatDate,
  formatTime
};