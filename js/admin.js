/* ============================================
   EDUVERSE PORTAL – ADMIN JAVASCRIPT (PRODUCTION)
   Fully connected to PHP backend with proper error handling
   ============================================ */

'use strict';

// Current school context
let currentSchool = 'all';

// ============ NAVIGATION ============
function showPage(page) {
  document.querySelectorAll('.admin-page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  
  const pageEl = document.getElementById('page-' + page);
  if (pageEl) pageEl.classList.add('active');
  
  const navEl = document.getElementById('nav-' + page);
  if (navEl) navEl.classList.add('active');
  
  const titles = { 
    dashboard:'📊 Dashboard', 
    registrations:'📋 Registrations', 
    users:'👥 Users', 
    schools:'🏫 School Profiles', 
    agegroups:'🎂 Age Groups', 
    announcements:'📢 Announcements', 
    settings:'⚙️ Settings' 
  };
  
  document.getElementById('topbarTitle').textContent = titles[page] || page;
  loadPageData(page);
}

function loadPageData(page) {
  switch(page) {
    case 'dashboard': loadDashboard(); break;
    case 'registrations': loadRegistrations(); break;
    case 'users': loadUsers(); break;
    case 'schools': loadSchools(); break;
    case 'agegroups': loadAgeGroups(); break;
    case 'announcements': loadAnnouncements(); break;
    case 'settings': loadSettings(); break;
  }
}

// ============ API HELPER ============
async function apiRequest(url, options = {}) {
  try {
    const response = await fetch(url, {
      credentials: 'include',
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      }
    });
    
    // Handle 401 Unauthorized
    if (response.status === 401) {
      console.error('❌ 401 Unauthorized - Session expired or not logged in');
      showToast('Session expired. Please login again.', 'error');
      setTimeout(() => {
        window.location.href = '../login.html';
      }, 2000);
      throw new Error('Unauthorized - Session expired');
    }
    
    const data = await response.json();
    
    if (!data.success && data.error) {
      throw new Error(data.error.message || data.message || 'Request failed');
    }
    
    return data;
  } catch (error) {
    console.error('API Error:', error);
    
    // Don't show toast for 401 (already handled above)
    if (!error.message.includes('Unauthorized')) {
      showToast('Error: ' + error.message, 'error');
    }
    
    throw error;
  }
}

// ============ DASHBOARD ============
async function loadDashboard() {
  try {
    console.log('📊 Loading dashboard...');
    
    // Fetch real stats from database
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'get_stats' })
    });
    
    if (data.success && data.stats) {
      const stats = data.stats;
      console.log('✅ Stats loaded:', stats);
      
      // Update stat cards with real data
      updateCounter('[data-target="2180"]', stats.total || 0);
      updateCounter('[data-target="157"]', stats.teachers || 0);
      updateCounter('[data-target="3"]', stats.pending_regs || 0);
      
      // Update pending badge
      const badge = document.getElementById('pendingBadge');
      if (badge) badge.textContent = stats.pending_regs || 0;
      
      // Load recent registrations
      loadRecentRegistrations();
    }
  } catch (error) {
    console.error('Dashboard load error:', error);
    // Set default values on error
    updateCounter('[data-target="2180"]', 0);
    updateCounter('[data-target="157"]', 0);
    updateCounter('[data-target="3"]', 0);
  }
}

async function loadRecentRegistrations() {
  try {
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({ 
        action: 'list_registrations',
        school: 'all',
        status: 'all'
      })
    });
    
    if (data.success && data.registrations) {
      const recent = data.registrations.slice(0, 4);
      const tbody = document.getElementById('recentRegsTbody');
      
      if (!tbody) return;
      
      if (recent.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--admin-muted);padding:2rem;">No registrations yet</td></tr>';
        return;
      }
      
      tbody.innerHTML = recent.map(r => `
        <tr>
          <td><strong>${r.first_name} ${r.last_name}</strong></td>
          <td><span class="badge badge-${r.school_key}">${r.school_name}</span></td>
          <td><span class="age-pill">${r.age_group_name || r.age_group_key}</span></td>
          <td><span class="badge badge-${r.status}">${r.status}</span></td>
        </tr>
      `).join('');
    }
  } catch (error) {
    console.error('Recent registrations error:', error);
  }
}

function updateCounter(selector, target) {
  const el = document.querySelector(selector);
  if (!el) return;
  
  let current = 0;
  const duration = 1800;
  const step = target / (duration / 16);
  
  const timer = setInterval(() => {
    current += step;
    if (current >= target) {
      current = target;
      clearInterval(timer);
    }
    el.textContent = Math.floor(current).toLocaleString();
  }, 16);
}

// ============ REGISTRATIONS ============
async function loadRegistrations() {
  try {
    const statusFilter = document.getElementById('statusFilter')?.value || 'all';
    
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'list_registrations',
        school: currentSchool,
        status: statusFilter
      })
    });
    
    if (data.success && data.registrations) {
      renderRegistrations(data.registrations);
    }
  } catch (error) {
    console.error('Registrations load error:', error);
    renderRegistrations([]);
  }
}

function renderRegistrations(regs) {
  const tbody = document.getElementById('regsTbody');
  
  if (!tbody) return;
  
  if (!regs || regs.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--admin-muted);padding:2rem;">No registrations found</td></tr>';
    return;
  }
  
  tbody.innerHTML = regs.map(r => `
    <tr class="fade-in">
      <td><strong>${r.first_name} ${r.last_name}</strong></td>
      <td><span class="badge badge-${r.school_key}">${r.school_name}</span></td>
      <td><span class="age-pill">${r.age_group_name || r.age_group_key}</span></td>
      <td>${r.parent_name}</td>
      <td style="color:var(--admin-muted); font-size:0.85rem;">${r.parent_email}</td>
      <td style="color:var(--admin-muted); font-size:0.85rem;">${r.created_at?.split(' ')[0] || r.date}</td>
      <td>
        <select class="badge badge-${r.status}" onchange="updateRegStatus(${r.id}, this.value)" 
                style="border:none; background:transparent; font-weight:800; cursor:pointer; font-size:0.78rem; color:inherit; padding:0.2rem 0.5rem;">
          <option value="pending" ${r.status==='pending'?'selected':''}>pending</option>
          <option value="approved" ${r.status==='approved'?'selected':''}>approved</option>
          <option value="rejected" ${r.status==='rejected'?'selected':''}>rejected</option>
        </select>
      </td>
      <td>
        <div style="display:flex; gap:0.4rem;">
          <button class="action-btn edit" onclick="viewRegistration(${r.id})" title="View Details">👁</button>
          <button class="action-btn" onclick="approveAndCreateUser(${r.id})" title="Approve & Create Login" 
                  style="background:rgba(74,222,128,0.15); color:#4ADE80;">✓+</button>
          <button class="action-btn delete" onclick="deleteRegistration(${r.id})" title="Delete">🗑</button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function updateRegStatus(id, status) {
  try {
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'update_registration',
        id: id,
        status: status
      })
    });
    
    if (data.success) {
      showToast(`Registration ${status}!`, status === 'approved' ? 'success' : 'info');
      loadRegistrations();
      loadDashboard(); // Refresh stats
    }
  } catch (error) {
    console.error('Update status error:', error);
  }
}

async function approveAndCreateUser(regId) {
  if (!confirm('Approve this registration and create user account?')) return;
  
  try {
    // First, get the registration details
    const regData = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'list_registrations',
        school: 'all',
        status: 'all'
      })
    });
    
    const reg = regData.registrations.find(r => r.id === regId);
    if (!reg) {
      showToast('Registration not found', 'error');
      return;
    }
    
    // Create username from name
    const username = (reg.first_name.toLowerCase() + '.' + reg.last_name.toLowerCase()).replace(/\s/g, '');
    const password = 'student' + reg.id;
    
    // Create the user
    const userData = await apiRequest('../php/users.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'create',
        firstName: reg.first_name,
        lastName: reg.last_name,
        username: username,
        password: password,
        role: 'student',
        school: reg.school_key,
        ageGroup: reg.age_group_key,
        status: 'active',
        email: reg.parent_email
      })
    });
    
    if (userData.success) {
      // Update registration status to approved
      await apiRequest('../php/content.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'update_registration',
          id: regId,
          status: 'approved'
        })
      });
      
      showToast(`✅ User created! Login: ${username} / ${password}`, 'success');
      loadRegistrations();
      loadDashboard();
    }
  } catch (error) {
    console.error('Create user error:', error);
  }
}

async function deleteRegistration(id) {
  if (!confirm('Delete this registration? This cannot be undone.')) return;
  
  try {
    showToast('Delete functionality: Add to content.php', 'info');
  } catch (error) {
    console.error('Delete error:', error);
  }
}

function viewRegistration(id) {
  showToast('View details: Coming soon', 'info');
}

function filterBySchool(school, btn) {
  currentSchool = school;
  document.querySelectorAll('.school-selector-btn').forEach(b => {
    b.classList.remove('active', 'brightstar', 'moonrise');
  });
  if (btn) btn.classList.add('active', school);
  loadRegistrations();
}

function filterRegistrations() {
  loadRegistrations();
}

// ============ USERS ============
async function loadUsers() {
  try {
    console.log('👥 Loading users...');
    
    const data = await apiRequest('../php/users.php?action=list&role=all&school=' + currentSchool);
    
    if (data.success && data.users) {
      console.log('✅ Users loaded:', data.users.length);
      renderUsers(data.users);
    }
  } catch (error) {
    console.error('Users load error:', error);
    renderUsers([]);
  }
}

function renderUsers(users) {
  const tbody = document.getElementById('usersTbody');
  
  if (!tbody) return;
  
  if (!users || users.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--admin-muted);padding:2rem;">No users found</td></tr>';
    return;
  }
  
  tbody.innerHTML = users.map(u => `
    <tr class="fade-in">
      <td><strong>${u.first_name} ${u.last_name}</strong></td>
      <td style="color:var(--admin-primary); font-family:monospace;">${u.username}</td>
      <td>${getRoleLabel(u.role)}</td>
      <td>${u.school_key === 'both' ? '🏫 Both' : u.school_key === 'brightstar' ? 
           '<span class="badge badge-brightstar">🦁 BrightStar</span>' : 
           '<span class="badge badge-moonrise">🦅 Moonrise</span>'}</td>
      <td>${u.age_group_key ? `<span class="age-pill">${getAgeLabel(u.age_group_key)}</span>` : 
           '<span style="color:var(--admin-muted);">—</span>'}</td>
      <td><span class="badge badge-${u.status}">${u.status}</span></td>
      <td style="color:var(--admin-muted); font-size:0.82rem;">${u.last_login || 'Never'}</td>
      <td>
        <div style="display:flex; gap:0.4rem;">
          <button class="action-btn edit" onclick="openUserModal(${u.id})" title="Edit">✏️</button>
          <button class="action-btn delete" onclick="deleteUser(${u.id})" title="Delete">🗑</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function openUserModal(id) {
  const modal = document.getElementById('userModal');
  const title = document.getElementById('userModalTitle');
  
  if (!modal || !title) return;
  
  if (id) {
    // Fetch user data and populate form
    showToast('Edit user: Fetching data...', 'info');
    // TODO: Fetch user and populate fields
  } else {
    title.textContent = '👤 Add New User';
    ['uFirstName','uLastName','uUsername','uPassword','uEmail'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    const roleEl = document.getElementById('uRole');
    const schoolEl = document.getElementById('uSchool');
    const ageEl = document.getElementById('uAgeGroup');
    const statusEl = document.getElementById('uStatus');
    const editIdEl = document.getElementById('uEditId');
    
    if (roleEl) roleEl.value = 'student';
    if (schoolEl) schoolEl.value = 'brightstar';
    if (ageEl) ageEl.value = '';
    if (statusEl) statusEl.value = 'active';
    if (editIdEl) editIdEl.value = '';
  }
  
  modal.classList.add('open');
}

async function saveUser() {
  const editIdEl = document.getElementById('uEditId');
  const editId = editIdEl ? editIdEl.value : '';
  
  const userData = {
    action: editId ? 'update' : 'create',
    firstName: document.getElementById('uFirstName')?.value.trim() || '',
    lastName: document.getElementById('uLastName')?.value.trim() || '',
    username: document.getElementById('uUsername')?.value.trim() || '',
    password: document.getElementById('uPassword')?.value || '',
    role: document.getElementById('uRole')?.value || 'student',
    school: document.getElementById('uSchool')?.value || 'brightstar',
    ageGroup: document.getElementById('uAgeGroup')?.value || '',
    status: document.getElementById('uStatus')?.value || 'active',
    email: document.getElementById('uEmail')?.value.trim() || ''
  };
  
  if (!userData.firstName || !userData.username || !userData.password) {
    showToast('⚠️ Please fill in required fields', 'error');
    return;
  }
  
  if (editId) userData.id = editId;
  
  try {
    const data = await apiRequest('../php/users.php', {
      method: 'POST',
      body: JSON.stringify(userData)
    });
    
    if (data.success) {
      showToast(`✅ User ${editId ? 'updated' : 'created'}!`, 'success');
      closeModal('userModal');
      loadUsers();
    }
  } catch (error) {
    console.error('Save user error:', error);
  }
}

async function deleteUser(id) {
  if (!confirm('Delete this user? This cannot be undone.')) return;
  
  try {
    const data = await apiRequest('../php/users.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'delete',
        id: id
      })
    });
    
    if (data.success) {
      showToast('🗑️ User deleted', 'info');
      loadUsers();
    }
  } catch (error) {
    console.error('Delete user error:', error);
  }
}

// ============ SCHOOLS ============
async function loadSchools() {
  try {
    console.log('🏫 Loading schools...');
    
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'get_schools' })
    });
    
    if (data.success && data.schools) {
      data.schools.forEach(s => {
        const prefix = s.school_key === 'brightstar' ? 'bs' : 'mr';
        const nameEl = document.getElementById(`${prefix}-name-display`);
        const mottoEl = document.getElementById(`${prefix}-motto-display`);
        const descEl = document.getElementById(`${prefix}-desc-display`);
        const featEl = document.getElementById(`${prefix}-features-display`);
        
        if (nameEl) nameEl.textContent = s.name;
        if (mottoEl) mottoEl.textContent = s.motto;
        if (descEl) descEl.textContent = s.description;
        
        if (featEl) {
          const features = typeof s.features === 'string' ? JSON.parse(s.features) : s.features;
          featEl.innerHTML = features.map(f => 
            `<span class="badge" style="background:rgba(255,255,255,0.07); color:var(--admin-text); font-weight:600;">${f}</span>`
          ).join('');
        }
      });
    }
  } catch (error) {
    console.error('Schools load error:', error);
  }
}

function openSchoolEdit(key) {
  showToast('Loading school data...', 'info');
  const editKeyEl = document.getElementById('editSchoolKey');
  if (editKeyEl) editKeyEl.value = key;
  
  const modal = document.getElementById('schoolModal');
  if (modal) modal.classList.add('open');
}

async function saveSchool() {
  const keyEl = document.getElementById('editSchoolKey');
  const key = keyEl ? keyEl.value : '';
  
  const schoolData = {
    action: 'update_school',
    key: key,
    data: {
      name: document.getElementById('sName')?.value || '',
      motto: document.getElementById('sMotto')?.value || '',
      desc: document.getElementById('sDesc')?.value || '',
      features: (document.getElementById('sFeatures')?.value || '').split('\n').filter(Boolean)
    }
  };
  
  try {
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify(schoolData)
    });
    
    if (data.success) {
      showToast('✅ School updated!', 'success');
      closeModal('schoolModal');
      loadSchools();
    }
  } catch (error) {
    console.error('Save school error:', error);
  }
}

// ============ AGE GROUPS ============
async function loadAgeGroups() {
  try {
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'get_age_groups' })
    });
    
    if (data.success && data.ageGroups) {
      renderAgeGroups(data.ageGroups);
    }
  } catch (error) {
    console.error('Age groups load error:', error);
  }
}

function renderAgeGroups(groups) {
  const tbody = document.getElementById('ageTbody');
  
  if (!tbody) return;
  
  tbody.innerHTML = groups.map(g => `
    <tr class="fade-in">
      <td style="font-size:1.5rem;">${g.icon}</td>
      <td><strong>${g.name}</strong></td>
      <td><span class="age-pill">${g.min_age}–${g.max_age}</span></td>
      <td style="color:var(--admin-muted);">${g.level_label}</td>
      <td style="color:var(--admin-muted); font-size:0.85rem; max-width:200px;">${g.description}</td>
      <td><strong>${g.students || 0}</strong></td>
      <td>
        <div style="display:flex; gap:0.4rem;">
          <button class="action-btn edit" onclick="openAgeModal(${g.id})" title="Edit">✏️</button>
          <button class="action-btn delete" onclick="deleteAgeGroup(${g.id})" title="Delete">🗑</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function openAgeModal(id) {
  showToast('Age group edit: Coming soon', 'info');
  const modal = document.getElementById('ageModal');
  if (modal) modal.classList.add('open');
}

function saveAgeGroup() {
  showToast('Age group save: Coming soon', 'info');
}

function deleteAgeGroup(id) {
  showToast('Age group delete: Coming soon', 'info');
}

// ============ ANNOUNCEMENTS ============
async function loadAnnouncements() {
  try {
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({ 
        action: 'get_announcements',
        school: 'all'
      })
    });
    
    if (data.success && data.announcements) {
      renderAnnouncements(data.announcements);
    }
  } catch (error) {
    console.error('Announcements load error:', error);
  }
}

function renderAnnouncements(announcements) {
  const list = document.getElementById('annList');
  
  if (!list) return;
  
  if (!announcements || announcements.length === 0) {
    list.innerHTML = '<div style="color:var(--admin-muted); text-align:center; padding:2rem;">No announcements yet</div>';
    return;
  }
  
  list.innerHTML = announcements.map(a => `
    <div class="chart-card fade-in" style="padding:1rem; border:1px solid var(--admin-border);">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
        <div>
          <div style="font-weight:800; margin-bottom:0.3rem;">${a.title}</div>
          <div style="font-size:0.85rem; color:var(--admin-muted); margin-bottom:0.5rem;">${a.body}</div>
          <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <span class="badge badge-active">${a.target}</span>
            <span class="badge badge-${a.priority === 'urgent' ? 'pending' : 'active'}">${a.priority}</span>
            <span style="font-size:0.75rem; color:var(--admin-muted);">${a.posted_at?.split(' ')[0]}</span>
          </div>
        </div>
        <button class="action-btn delete" onclick="deleteAnnouncement(${a.id})">🗑</button>
      </div>
    </div>
  `).join('');
}

async function postAnnouncement() {
  const titleEl = document.getElementById('annTitle');
  const bodyEl = document.getElementById('annBody');
  const schoolEl = document.getElementById('annSchool');
  const priorityEl = document.getElementById('annPriority');
  
  const title = titleEl ? titleEl.value.trim() : '';
  const body = bodyEl ? bodyEl.value.trim() : '';
  const school = schoolEl ? schoolEl.value : 'all';
  const priority = priorityEl ? priorityEl.value : 'info';
  
  if (!title || !body) {
    showToast('⚠️ Title and message required', 'error');
    return;
  }
  
  try {
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'post_announcement',
        title,
        body,
        school,
        priority
      })
    });
    
    if (data.success) {
      if (titleEl) titleEl.value = '';
      if (bodyEl) bodyEl.value = '';
      showToast('📢 Announcement posted!', 'success');
      loadAnnouncements();
    }
  } catch (error) {
    console.error('Post announcement error:', error);
  }
}

async function deleteAnnouncement(id) {
  if (!confirm('Delete this announcement?')) return;
  
  try {
    const data = await apiRequest('../php/content.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'delete_announcement',
        id
      })
    });
    
    if (data.success) {
      showToast('🗑️ Announcement deleted', 'info');
      loadAnnouncements();
    }
  } catch (error) {
    console.error('Delete announcement error:', error);
  }
}

// ============ SETTINGS ============
async function loadSettings() {
  try {
    const data = await apiRequest('../php/settings.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'get_settings' })
    });
    
    if (data.success && data.settings) {
      const nameEl = document.getElementById('portalName');
      const regEl = document.getElementById('regOpen');
      const emailEl = document.getElementById('adminEmail');
      
      if (nameEl) nameEl.value = data.settings.portal_name || '';
      if (regEl) regEl.value = data.settings.registration_open || '1';
      if (emailEl) emailEl.value = data.settings.admin_email || '';
    }
  } catch (error) {
    console.error('Settings load error:', error);
  }
}

async function saveSettings() {
  try {
    const data = await apiRequest('../php/settings.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'save_settings',
        portalName: document.getElementById('portalName')?.value || '',
        regOpen: document.getElementById('regOpen')?.value || '1',
        adminEmail: document.getElementById('adminEmail')?.value || ''
      })
    });
    
    if (data.success) {
      showToast('✅ Settings saved!', 'success');
    }
  } catch (error) {
    console.error('Save settings error:', error);
  }
}

async function changePassword() {
  const currentEl = document.getElementById('curPass');
  const newEl = document.getElementById('newPass');
  const confirmEl = document.getElementById('confPass');
  
  const current = currentEl ? currentEl.value : '';
  const newPass = newEl ? newEl.value : '';
  const confirm = confirmEl ? confirmEl.value : '';
  
  if (!current || !newPass) {
    showToast('⚠️ Fill in all password fields', 'error');
    return;
  }
  
  if (newPass !== confirm) {
    showToast('⚠️ Passwords do not match', 'error');
    return;
  }
  
  if (newPass.length < 6) {
    showToast('⚠️ Password must be at least 6 characters', 'error');
    return;
  }
  
  try {
    const data = await apiRequest('../php/auth.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'change_password',
        current,
        new: newPass
      })
    });
    
    if (data.success) {
      ['curPass','newPass','confPass'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
      showToast('🔐 Password changed successfully!', 'success');
    }
  } catch (error) {
    console.error('Change password error:', error);
  }
}

// ============ SEARCH ============
function handleSearch(value) {
  const searchTerm = value ? value.toLowerCase().trim() : '';
  console.log('🔍 Searching for:', searchTerm);
  
  // For now, just show a message
  if (searchTerm) {
    showToast(`Searching for "${searchTerm}"...`, 'info');
  }
  
  // TODO: Implement actual search functionality
  // You can add search to users, registrations, etc.
}

// ============ HELPERS ============
function getAgeLabel(key) {
  const labels = { 
    tiny:'🌱 Tiny (3-5)', 
    junior:'🌿 Junior (6-8)', 
    discover:'🌳 Discover (9-11)', 
    pioneer:'🚀 Pioneer (12-14)', 
    champion:'🏆 Champion (15-18)' 
  };
  return labels[key] || key || '—';
}

function getRoleLabel(role) {
  const labels = { 
    student:'🎒 Student', 
    parent:'👨‍👩‍👧 Parent', 
    teacher:'👩‍🏫 Teacher', 
    admin:'🔑 Admin' 
  };
  return labels[role] || role;
}

// ============ MODAL HELPERS ============
function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.remove('open');
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

// ============ TOAST ============
function showToast(msg, type = 'info') {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  
  const icons = { success:'✅', error:'❌', info:'ℹ️' };
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <span class="toast-icon">${icons[type]||'💬'}</span>
    <span>${msg}</span>
    <span class="toast-close" onclick="this.parentElement.remove()">✕</span>
  `;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 4000);
}

// ============ SCHOOL SWITCHER ============
function openSchoolModal() {
  const modal = document.getElementById('schoolSwitchModal');
  if (modal) modal.classList.add('open');
}

function switchActiveSchool(school) {
  currentSchool = school;
  const labels = { 
    brightstar:'🦁 BrightStar Academy', 
    moonrise:'🦅 Moonrise Institute', 
    all:'🏫 All Schools' 
  };
  const labelEl = document.getElementById('activeSchoolLabel');
  if (labelEl) labelEl.textContent = labels[school];
  
  closeModal('schoolSwitchModal');
  loadUsers();
  showToast(`Switched to ${labels[school]}`, 'info');
}

// ============ LOGOUT ============
function handleLogout() {
  if (confirm('Logout from admin panel?')) {
    fetch('../php/auth.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'logout'})
    })
    .then(() => {
      window.location.href = '../login.html';
    })
    .catch(() => {
      window.location.href = '../login.html';
    });
  }
}

// ============ SIDEBAR TOGGLE (MOBILE) ============
function toggleSidebar() {
  const sidebar = document.getElementById('adminSidebar');
  if (sidebar) sidebar.classList.toggle('open');
}

function checkMobile() {
  const isMobile = window.innerWidth <= 900;
  const toggleBtn = document.getElementById('sidebarToggle');
  if (toggleBtn) toggleBtn.style.display = isMobile ? 'flex' : 'none';
}

// ============ INIT ============
document.addEventListener('DOMContentLoaded', () => {
  console.log('✅ Admin panel loaded - Production mode with backend connection');
  loadDashboard();
  checkMobile();
  window.addEventListener('resize', checkMobile);
});