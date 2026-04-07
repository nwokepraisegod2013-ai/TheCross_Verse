/* ============================================
   ADMIN PANEL - JavaScript
   Dynamic data loading and interactions
   ============================================ */

// Global state
let currentSchoolFilter = 'all';
let allRegistrations = [];
let allUsers = [];

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
    // Load dashboard data
    loadDashboardStats();
    loadRecentRegistrations();
    
    // Initialize counters animation
    animateCounters();
    
    console.log('Admin panel loaded. User:', SESSION_DATA.adminName);
});

// ==================== PAGE NAVIGATION ====================
function showPage(pageId) {
    // Hide all pages
    document.querySelectorAll('.admin-page').forEach(page => {
        page.classList.remove('active');
    });
    
    // Remove active from all nav items
    document.querySelectorAll('.nav-item').forEach(nav => {
        nav.classList.remove('active');
    });
    
    // Show selected page
    const page = document.getElementById('page-' + pageId);
    if (page) {
        page.classList.add('active');
    }
    
    // Highlight nav item
    const navItem = document.getElementById('nav-' + pageId);
    if (navItem) {
        navItem.classList.add('active');
    }
    
    // Update topbar title
    const titles = {
        'dashboard': 'Dashboard',
        'registrations': 'Registrations',
        'students': 'Students',
        'results': 'Results Management',
        'users': 'User Management',
        'sessions': 'Academic Sessions',
        'subjects': 'Subjects',
        'attendance': 'Attendance',
        'assignments': 'Assignments',
        'schools': 'School Profiles',
        'agegroups': 'Age Groups',
        'announcements': 'Announcements',
        'fees': 'Fee Management',
        'settings': 'Settings'
    };
    document.getElementById('topbarTitle').textContent = titles[pageId] || 'Admin Panel';
    
    // Load page-specific data
    switch(pageId) {
        case 'registrations':
            loadRegistrations();
            break;
        case 'students':
            loadStudents();
            break;
        case 'results':
            loadResults();
            break;
        case 'users':
            loadUsers();
            break;
        case 'schools':
            loadSchools();
            break;
        case 'agegroups':
            loadAgeGroups();
            break;
        case 'announcements':
            loadAnnouncements();
            break;
        case 'sessions':
            loadSessions();
            break;
        case 'subjects':
            loadSubjects();
            break;
    }
}

// ==================== DASHBOARD ====================
async function loadDashboardStats() {
    try {
        const response = await fetch('php/admin-api.php?action=dashboard_stats');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Update stat cards
            document.getElementById('stat-students').textContent = data.total_students;
            document.getElementById('stat-students').setAttribute('data-target', data.total_students);
            
            document.getElementById('stat-users').textContent = data.total_users;
            document.getElementById('stat-users').setAttribute('data-target', data.total_users);
            
            document.getElementById('stat-pending').textContent = data.pending_registrations;
            document.getElementById('stat-pending').setAttribute('data-target', data.pending_registrations);
            
            document.getElementById('stat-schools').textContent = data.active_schools;
            
            // Update badge
            document.getElementById('pendingBadge').textContent = data.pending_registrations;
            if (data.pending_registrations > 0) {
                document.getElementById('pendingBadge').style.display = 'inline-block';
            } else {
                document.getElementById('pendingBadge').style.display = 'none';
            }
            
            // Render school stats
            renderSchoolStats(data.by_school);
            
            // Render age group chart
            renderAgeGroupChart(data.by_age_group);
            
            // Animate counters
            animateCounters();
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

function renderSchoolStats(schools) {
    const container = document.getElementById('schoolStatsContainer');
    if (!container) return;
    
    let html = '';
    const total = schools.reduce((sum, school) => sum + parseInt(school.student_count), 0);
    
    schools.forEach(school => {
        const percentage = total > 0 ? (school.student_count / total * 100) : 0;
        const emoji = school.school_key === 'brightstar' ? '🦁' : '🦅';
        const color = school.school_key === 'brightstar' ? 'var(--admin-primary)' : '#14B8A6';
        
        html += `
            <div style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                    <span style="font-size:0.9rem;">${emoji} ${school.name}</span>
                    <span style="font-size:0.9rem; color:${color};">${school.student_count} students</span>
                </div>
                <div style="background:rgba(255,255,255,0.08); border-radius:4px; height:10px; overflow:hidden;">
                    <div style="height:100%; width:${percentage}%; background:linear-gradient(90deg,${color},#4F46E5); border-radius:4px; animation:barGrow 1.5s ease both;"></div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function renderAgeGroupChart(groups) {
    const container = document.getElementById('ageBarChart');
    if (!container) return;
    
    const maxCount = Math.max(...groups.map(g => g.student_count), 1);
    
    let html = '';
    groups.forEach(group => {
        const percentage = (group.student_count / maxCount) * 100;
        html += `
            <div style="margin-bottom:0.8rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.3rem; font-size:0.85rem;">
                    <span>${group.icon} ${group.name}</span>
                    <span style="color:var(--admin-primary);">${group.student_count}</span>
                </div>
                <div style="background:rgba(255,255,255,0.08); border-radius:4px; height:8px; overflow:hidden;">
                    <div style="height:100%; width:${percentage}%; background:linear-gradient(90deg,var(--admin-primary),#A78BFA); border-radius:4px;"></div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

async function loadRecentRegistrations() {
    try {
        const response = await fetch('php/admin-api.php?action=get_registrations&limit=5');
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('recentRegsTbody');
            
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--admin-muted);">No recent registrations</td></tr>';
                return;
            }
            
            tbody.innerHTML = result.data.slice(0, 5).map(reg => `
                <tr>
                    <td><strong>${reg.first_name} ${reg.last_name}</strong></td>
                    <td>${getSchoolEmoji(reg.school)} ${capitalizeFirst(reg.school)}</td>
                    <td>${capitalizeFirst(reg.age_group)}</td>
                    <td><span class="badge badge-${reg.status}">${capitalizeFirst(reg.status)}</span></td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading recent registrations:', error);
    }
}

// ==================== REGISTRATIONS ====================
async function loadRegistrations() {
    try {
        const school = currentSchoolFilter;
        const status = document.getElementById('statusFilter')?.value || 'all';
        
        const response = await fetch(`php/admin-api.php?action=get_registrations&school=${school}&status=${status}`);
        const result = await response.json();
        
        if (result.success) {
            allRegistrations = result.data;
            renderRegistrations(result.data);
        }
    } catch (error) {
        console.error('Error loading registrations:', error);
        showToast('Error loading registrations', 'error');
    }
}

function renderRegistrations(registrations) {
    const tbody = document.getElementById('regsTbody');
    
    if (registrations.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--admin-muted);">No registrations found</td></tr>';
        return;
    }
    
    tbody.innerHTML = registrations.map(reg => `
        <tr>
            <td><strong>${reg.first_name} ${reg.last_name}</strong></td>
            <td>${getSchoolEmoji(reg.school)} ${capitalizeFirst(reg.school)}</td>
            <td>${capitalizeFirst(reg.age_group)}</td>
            <td>${reg.parent_name}</td>
            <td><a href="mailto:${reg.parent_email}">${reg.parent_email}</a></td>
            <td>${formatDate(reg.created_at)}</td>
            <td><span class="badge badge-${reg.status}">${capitalizeFirst(reg.status)}</span></td>
            <td>
                <div style="display:flex;gap:0.5rem;">
                    ${reg.status === 'pending' ? `
                        <button class="action-btn success" onclick="approveRegistration(${reg.id})" title="Approve">✓</button>
                        <button class="action-btn error" onclick="rejectRegistration(${reg.id})" title="Reject">✗</button>
                    ` : `
                        <button class="action-btn" onclick="viewRegistration(${reg.id})" title="View">👁</button>
                    `}
                </div>
            </td>
        </tr>
    `).join('');
}

async function approveRegistration(id) {
    if (!confirm('Approve this registration? This will create a student account.')) return;
    
    try {
        const response = await fetch('php/admin-api.php?action=update_registration_status', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, status: 'approved'})
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('Registration approved! Student account created.', 'success');
            loadRegistrations();
            loadDashboardStats();
        }
    } catch (error) {
        console.error('Error approving registration:', error);
        showToast('Error approving registration', 'error');
    }
}

async function rejectRegistration(id) {
    const reason = prompt('Reason for rejection (optional):');
    if (reason === null) return;
    
    try {
        const response = await fetch('php/admin-api.php?action=update_registration_status', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, status: 'rejected', notes: reason})
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('Registration rejected', 'success');
            loadRegistrations();
            loadDashboardStats();
        }
    } catch (error) {
        console.error('Error rejecting registration:', error);
        showToast('Error rejecting registration', 'error');
    }
}

function filterBySchool(school, btn) {
    currentSchoolFilter = school;
    
    // Update button states
    document.querySelectorAll('.school-selector-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    
    loadRegistrations();
}

function filterRegistrations() {
    loadRegistrations();
}

// ==================== STUDENTS ====================
async function loadStudents() {
    try {
        const response = await fetch('php/admin-api.php?action=get_students');
        const result = await response.json();
        
        if (result.success) {
            renderStudents(result.data);
        }
    } catch (error) {
        console.error('Error loading students:', error);
    }
}

function renderStudents(students) {
    const tbody = document.getElementById('studentsTbody');
    
    if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">No students found</td></tr>';
        return;
    }
    
    tbody.innerHTML = students.map(student => `
        <tr>
            <td><strong>${student.student_id}</strong></td>
            <td>${student.full_name}</td>
            <td>${getSchoolEmoji(student.school_key)} ${student.school_name || capitalizeFirst(student.school_key)}</td>
            <td>${student.class_name || 'N/A'}</td>
            <td>${student.age_group_name || capitalizeFirst(student.age_group_key)}</td>
            <td><span class="badge badge-${student.status}">${capitalizeFirst(student.status)}</span></td>
            <td>
                <button class="action-btn" onclick="viewStudent(${student.id})" title="View">👁</button>
            </td>
        </tr>
    `).join('');
}

// ==================== RESULTS ====================
async function loadResults() {
    try {
        const response = await fetch('php/admin-api.php?action=get_results');
        const result = await response.json();
        
        if (result.success) {
            renderResults(result.data);
        }
    } catch (error) {
        console.error('Error loading results:', error);
    }
}

function renderResults(results) {
    const tbody = document.getElementById('resultsTbody');
    
    if (results.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;">No results found</td></tr>';
        return;
    }
    
    tbody.innerHTML = results.map(result => `
        <tr>
            <td>${result.student_name} (${result.student_id})</td>
            <td>${result.subject_name}</td>
            <td>${result.session_year} - ${result.term_name}</td>
            <td>${result.type_name}</td>
            <td>${result.marks_obtained}/${result.max_marks}</td>
            <td><strong>${result.grade || 'N/A'}</strong></td>
            <td><span class="badge badge-${result.is_published ? 'success' : 'warning'}">${result.is_published ? 'Published' : 'Draft'}</span></td>
            <td>
                <button class="action-btn" onclick="editResult(${result.id})" title="Edit">✏️</button>
            </td>
        </tr>
    `).join('');
}

// ==================== USERS ====================
async function loadUsers() {
    try {
        const response = await fetch('php/admin-api.php?action=get_users');
        const result = await response.json();
        
        if (result.success) {
            allUsers = result.data;
            renderUsers(result.data);
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

function renderUsers(users) {
    const tbody = document.getElementById('usersTbody');
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td><strong>${user.full_name}</strong></td>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td><span class="badge badge-${user.role}">${getRoleEmoji(user.role)} ${capitalizeFirst(user.role)}</span></td>
            <td><span class="badge badge-${user.status}">${capitalizeFirst(user.status)}</span></td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <div style="display:flex;gap:0.5rem;">
                    <button class="action-btn" onclick="openUserModal(${user.id})" title="Edit">✏️</button>
                    <button class="action-btn error" onclick="deleteUser(${user.id})" title="Delete">🗑</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function openUserModal(userId) {
    const modal = document.getElementById('userModal');
    modal.style.display = 'flex';
    
    if (userId) {
        const user = allUsers.find(u => u.id === userId);
        if (user) {
            document.getElementById('userModalTitle').textContent = '✏️ Edit User';
            document.getElementById('uFirstName').value = user.first_name;
            document.getElementById('uLastName').value = user.last_name;
            document.getElementById('uUsername').value = user.username;
            document.getElementById('uEmail').value = user.email;
            document.getElementById('uRole').value = user.role;
            document.getElementById('uStatus').value = user.status;
            document.getElementById('uEditId').value = user.id;
            document.getElementById('uPassword').value = '';
            document.getElementById('uPassword').placeholder = 'Leave blank to keep current';
        }
    } else {
        document.getElementById('userModalTitle').textContent = '👤 Add New User';
        modal.querySelector('form')?.reset();
        document.getElementById('uEditId').value = '';
        document.getElementById('uPassword').placeholder = 'Set password';
    }
}

async function saveUser() {
    const data = {
        id: document.getElementById('uEditId').value,
        first_name: document.getElementById('uFirstName').value,
        last_name: document.getElementById('uLastName').value,
        username: document.getElementById('uUsername').value,
        email: document.getElementById('uEmail').value,
        role: document.getElementById('uRole').value,
        status: document.getElementById('uStatus').value,
        password: document.getElementById('uPassword').value
    };
    
    if (!data.first_name || !data.last_name || !data.username || !data.email) {
        showToast('Please fill all required fields', 'error');
        return;
    }
    
    if (!data.id && !data.password) {
        showToast('Password is required for new users', 'error');
        return;
    }
    
    try {
        const response = await fetch('php/admin-api.php?action=save_user', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            closeModal('userModal');
            loadUsers();
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showToast('Error saving user', 'error');
    }
}

async function deleteUser(id) {
    if (!confirm('Delete this user? This cannot be undone.')) return;
    
    try {
        const response = await fetch(`php/admin-api.php?action=delete_user&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            showToast('User deleted', 'success');
            loadUsers();
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showToast('Error deleting user', 'error');
    }
}

// ==================== SCHOOLS ====================
async function loadSchools() {
    try {
        const response = await fetch('php/admin-api.php?action=get_schools');
        const result = await response.json();
        
        if (result.success) {
            renderSchoolsPage(result.data);
        }
    } catch (error) {
        console.error('Error loading schools:', error);
    }
}

function renderSchoolsPage(schools) {
    const container = document.getElementById('schoolsContainer');
    
    container.innerHTML = schools.map(school => {
        const emoji = school.school_key === 'brightstar' ? '🦁' : '🦅';
        const badgeClass = school.school_key === 'brightstar' ? 'badge-brightstar' : 'badge-moonrise';
        const borderColor = school.school_key === 'brightstar' ? 'rgba(107,203,247,0.3)' : 'rgba(20,184,166,0.3)';
        
        return `
            <div class="chart-card" style="border:1px solid ${borderColor};">
                <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem;">
                    <span style="font-size:3rem;">${emoji}</span>
                    <div>
                        <h3 style="font-family:var(--font-title);">${school.name}</h3>
                        <span class="badge ${badgeClass}">${school.school_key}</span>
                    </div>
                    <button class="btn-save" style="margin-left:auto;" onclick="openSchoolEdit('${school.school_key}')">✏️ Edit</button>
                </div>
                <div class="admin-form-group">
                    <div class="admin-form-label">Motto</div>
                    <div style="color:var(--admin-muted); font-style:italic;">${school.motto || 'No motto set'}</div>
                </div>
                <div class="admin-form-group" style="margin-top:1rem;">
                    <div class="admin-form-label">Description</div>
                    <div style="color:var(--admin-muted); font-size:0.9rem; line-height:1.6;">${school.description || 'No description'}</div>
                </div>
            </div>
        `;
    }).join('');
}

// ==================== AGE GROUPS ====================
async function loadAgeGroups() {
    try {
        const response = await fetch('php/admin-api.php?action=get_age_groups');
        const result = await response.json();
        
        if (result.success) {
            renderAgeGroups(result.data);
        }
    } catch (error) {
        console.error('Error loading age groups:', error);
    }
}

function renderAgeGroups(groups) {
    const tbody = document.getElementById('ageTbody');
    
    tbody.innerHTML = groups.map(group => `
        <tr>
            <td style="font-size:2rem;">${group.icon || '📚'}</td>
            <td><strong>${group.name}</strong></td>
            <td>${group.min_age} - ${group.max_age} years</td>
            <td>${group.level_label || 'N/A'}</td>
            <td>${group.description || 'No description'}</td>
            <td><span class="badge badge-${group.is_active ? 'success' : 'inactive'}">${group.is_active ? 'Active' : 'Inactive'}</span></td>
            <td>
                <button class="action-btn" onclick="openAgeModal(${group.id})" title="Edit">✏️</button>
            </td>
        </tr>
    `).join('');
}

// ==================== ANNOUNCEMENTS ====================
async function loadAnnouncements() {
    try {
        const response = await fetch('php/admin-api.php?action=get_announcements');
        const result = await response.json();
        
        if (result.success) {
            renderAnnouncements(result.data);
        }
    } catch (error) {
        console.error('Error loading announcements:', error);
    }
}

function renderAnnouncements(announcements) {
    const container = document.getElementById('annList');
    
    if (announcements.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--admin-muted);">No announcements yet</div>';
        return;
    }
    
    container.innerHTML = announcements.map(ann => `
        <div class="announcement-item" style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:8px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                <strong>${ann.title}</strong>
                <button class="action-btn error" onclick="deleteAnnouncement(${ann.id})" title="Delete">🗑</button>
            </div>
            <p style="font-size:0.9rem; color:var(--admin-muted); margin:0.5rem 0;">${ann.content}</p>
            <div style="display:flex; gap:0.5rem; margin-top:0.5rem;">
                ${ann.school_key ? `<span class="badge">${getSchoolEmoji(ann.school_key)} ${capitalizeFirst(ann.school_key)}</span>` : '<span class="badge">📢 All Schools</span>'}
                <span class="badge badge-${ann.priority}">${capitalizeFirst(ann.priority)}</span>
            </div>
        </div>
    `).join('');
}

async function postAnnouncement() {
    const data = {
        title: document.getElementById('annTitle').value,
        content: document.getElementById('annBody').value,
        school_key: document.getElementById('annSchool').value,
        priority: document.getElementById('annPriority').value
    };
    
    if (!data.title || !data.content) {
        showToast('Please fill title and message', 'error');
        return;
    }
    
    try {
        const response = await fetch('php/admin-api.php?action=post_announcement', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('Announcement posted!', 'success');
            document.getElementById('annTitle').value = '';
            document.getElementById('annBody').value = '';
            loadAnnouncements();
        }
    } catch (error) {
        console.error('Error posting announcement:', error);
        showToast('Error posting announcement', 'error');
    }
}

async function deleteAnnouncement(id) {
    if (!confirm('Delete this announcement?')) return;
    
    try {
        const response = await fetch(`php/admin-api.php?action=delete_announcement&id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            showToast('Announcement deleted', 'success');
            loadAnnouncements();
        }
    } catch (error) {
        console.error('Error deleting announcement:', error);
    }
}

// ==================== SETTINGS ====================
async function changePassword() {
    const current = document.getElementById('curPass').value;
    const newPass = document.getElementById('newPass').value;
    const confirm = document.getElementById('confPass').value;
    
    if (!current || !newPass || !confirm) {
        showToast('Please fill all password fields', 'error');
        return;
    }
    
    if (newPass !== confirm) {
        showToast('New passwords do not match', 'error');
        return;
    }
    
    try {
        const response = await fetch('php/admin-api.php?action=change_password', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({current_password: current, new_password: newPass})
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('Password updated!', 'success');
            document.getElementById('curPass').value = '';
            document.getElementById('newPass').value = '';
            document.getElementById('confPass').value = '';
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error changing password:', error);
        showToast('Error changing password', 'error');
    }
}

async function saveSettings() {
    const data = {
        site_name: document.getElementById('portalName').value,
        site_email: document.getElementById('adminEmail').value,
        enrollment_open: document.getElementById('regOpen').value
    };
    
    try {
        const response = await fetch('php/admin-api.php?action=update_settings', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('Settings saved!', 'success');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showToast('Error saving settings', 'error');
    }
}

// ==================== UTILITY FUNCTIONS ====================
function animateCounters() {
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target') || counter.textContent);
        const duration = 1500;
        const increment = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, 16);
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function handleLogout() {
    if (confirm('Logout from admin panel?')) {
        window.location.href = 'php/logout.php';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'});
}

function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getSchoolEmoji(schoolKey) {
    return schoolKey === 'brightstar' ? '🦁' : schoolKey === 'moonrise' ? '🦅' : '🏫';
}

function getRoleEmoji(role) {
    const emojis = {
        'admin': '🔑',
        'teacher': '👨‍🏫',
        'student': '🎒',
        'parent': '👨‍👩‍👧'
    };
    return emojis[role] || '👤';
}

// Placeholder functions
function toggleSidebar() { }
function openSchoolModal() { }
function handleSearch(query) { console.log('Searching:', query); }
function exportRegistrations() { showToast('Export feature coming soon', 'info'); }
function exportStudents() { showToast('Export feature coming soon', 'info'); }
function viewRegistration(id) { showToast('View details coming soon', 'info'); }
function viewStudent(id) { showToast('Student details coming soon', 'info'); }
function editResult(id) { showToast('Edit result coming soon', 'info'); }
function openResultModal() { showToast('Upload result modal coming soon', 'info'); }
function openSchoolEdit(key) { showToast('School edit modal coming soon', 'info'); }
function openAgeModal(id) { showToast('Age group edit modal coming soon', 'info'); }
function loadSessions() { showToast('Sessions management coming soon', 'info'); }
function loadSubjects() { showToast('Subjects management coming soon', 'info'); }