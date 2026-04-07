<!-- ===== ADMIN PANEL MODALS ===== -->

<!-- Add/Edit User Modal -->
<div class="modal-overlay" id="userModal" style="display:none;">
  <div class="modal">
    <h3 class="modal-title" id="userModalTitle">👤 Add New User</h3>
    <div class="admin-form-grid">
      <div class="admin-form-group">
        <label class="admin-form-label">First Name *</label>
        <input type="text" class="admin-form-input" id="uFirstName" placeholder="First name">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Last Name *</label>
        <input type="text" class="admin-form-input" id="uLastName" placeholder="Last name">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Username *</label>
        <input type="text" class="admin-form-input" id="uUsername" placeholder="e.g. alex.johnson">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Password *</label>
        <input type="password" class="admin-form-input" id="uPassword" placeholder="Set password">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Role *</label>
        <select class="admin-form-select" id="uRole">
          <option value="student">🎒 Student</option>
          <option value="parent">👨‍👩‍👧 Parent</option>
          <option value="teacher">👩‍🏫 Teacher</option>
          <option value="admin">🔑 Admin</option>
        </select>
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Status</label>
        <select class="admin-form-select" id="uStatus">
          <option value="active">✅ Active</option>
          <option value="inactive">🔒 Inactive</option>
        </select>
      </div>
      <div class="admin-form-group full">
        <label class="admin-form-label">Email</label>
        <input type="email" class="admin-form-input" id="uEmail" placeholder="user@email.com">
      </div>
    </div>
    <input type="hidden" id="uEditId" value="">
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('userModal')">Cancel</button>
      <button class="btn-save" onclick="saveUser()">💾 Save User</button>
    </div>
  </div>
</div>

<!-- School Switcher Modal -->
<div class="modal-overlay" id="schoolSwitchModal" style="display:none;">
  <div class="modal" style="max-width:360px;">
    <h3 class="modal-title">🏫 Switch School View</h3>
    <div style="display:flex; flex-direction:column; gap:0.8rem; margin-bottom:1.5rem;">
      <button class="school-switch-btn" style="justify-content:flex-start; gap:1rem; padding:1rem;" onclick="switchActiveSchool('all')">
        <span style="font-size:2rem;">🏫</span>
        <div>
          <div style="font-weight:800;">All Schools</div>
          <div style="font-size:0.8rem; color:var(--admin-muted);">View everything</div>
        </div>
      </button>
      <button class="school-switch-btn" style="justify-content:flex-start; gap:1rem; padding:1rem;" onclick="switchActiveSchool('brightstar')">
        <span style="font-size:2rem;">🦁</span>
        <div>
          <div style="font-weight:800;">BrightStar Academy</div>
          <div style="font-size:0.8rem; color:var(--admin-muted);">BrightStar only</div>
        </div>
      </button>
      <button class="school-switch-btn" style="justify-content:flex-start; gap:1rem; padding:1rem;" onclick="switchActiveSchool('moonrise')">
        <span style="font-size:2rem;">🦅</span>
        <div>
          <div style="font-weight:800;">Moonrise Institute</div>
          <div style="font-size:0.8rem; color:var(--admin-muted);">Moonrise only</div>
        </div>
      </button>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('schoolSwitchModal')">Close</button>
    </div>
  </div>
</div>

<style>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
}

.modal {
  background: var(--admin-card-bg);
  border-radius: 16px;
  padding: 2rem;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.modal-title {
  font-family: var(--font-title);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  color: var(--admin-primary);
}

.modal-footer {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

.admin-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.admin-form-group.full {
  grid-column: 1 / -1;
}

.toast-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 10000;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.toast {
  background: var(--admin-card-bg);
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  transform: translateX(400px);
  opacity: 0;
  transition: all 0.3s ease;
}

.toast.show {
  transform: translateX(0);
  opacity: 1;
}

.toast-success {
  border-left: 4px solid #4ADE80;
}

.toast-error {
  border-left: 4px solid #F87171;
}

.toast-info {
  border-left: 4px solid #60A5FA;
}

.school-switch-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.8rem 1.2rem;
  border-radius: 8px;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 700;
  transition: all 0.2s;
}

.school-switch-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--admin-primary);
}
</style>