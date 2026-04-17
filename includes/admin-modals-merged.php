<?php
/* ============================================
   ADMIN PANEL MODALS - MERGED & COMPLETE
   All popup forms for admin operations
   ============================================ */
?>

<!-- ===== EXISTING MODALS ===== -->

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

<!-- ===== NEW SAAS MODALS ===== -->

<!-- School Registration Approval Modal -->
<div class="modal-overlay" id="schoolApprovalModal" style="display:none;">
  <div class="modal" style="max-width:600px;">
    <h3 class="modal-title">🏫 Approve School Registration</h3>
    <div id="schoolApprovalDetails" style="background:rgba(255,255,255,0.05);padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
      <!-- School details loaded here -->
    </div>
    
    <div class="admin-form-group">
      <label class="admin-form-label">Assign Plan</label>
      <select class="admin-form-select" id="approvalPlanId">
        <!-- Plans loaded dynamically -->
      </select>
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Start Date</label>
      <input type="date" class="admin-form-input" id="approvalStartDate">
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Admin Notes</label>
      <textarea class="admin-form-textarea" id="approvalNotes" rows="3"></textarea>
    </div>
    
    <input type="hidden" id="approvalRequestId">
    
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('schoolApprovalModal')">Cancel</button>
      <button class="btn-save" onclick="approveSchoolRegistration()">✓ Approve & Create School</button>
    </div>
  </div>
</div>

<!-- Hosting Plan Modal -->
<div class="modal-overlay" id="planModal" style="display:none;">
  <div class="modal" style="max-width:700px;">
    <h3 class="modal-title" id="planModalTitle">💰 Add Hosting Plan</h3>
    
    <input type="hidden" id="planId">
    
    <div class="admin-form-grid">
      <div class="admin-form-group">
        <label class="admin-form-label">Plan Name *</label>
        <input type="text" class="admin-form-input" id="planName" placeholder="Professional">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Slug *</label>
        <input type="text" class="admin-form-input" id="planSlug" placeholder="professional">
      </div>
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Description</label>
      <textarea class="admin-form-textarea" id="planDescription" rows="2"></textarea>
    </div>
    
    <div class="admin-form-grid" style="margin-top:1rem;">
      <div class="admin-form-group">
        <label class="admin-form-label">Monthly Price (₦)</label>
        <input type="number" class="admin-form-input" id="planPriceMonthly" step="0.01" min="0">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Quarterly Price (₦)</label>
        <input type="number" class="admin-form-input" id="planPriceQuarterly" step="0.01" min="0">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Yearly Price (₦)</label>
        <input type="number" class="admin-form-input" id="planPriceYearly" step="0.01" min="0">
      </div>
    </div>
    
    <div class="admin-form-grid" style="margin-top:1rem;">
      <div class="admin-form-group">
        <label class="admin-form-label">Max Students</label>
        <input type="number" class="admin-form-input" id="planMaxStudents" min="1">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Max Teachers</label>
        <input type="number" class="admin-form-input" id="planMaxTeachers" min="1">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Storage (GB)</label>
        <input type="number" class="admin-form-input" id="planStorageGb" min="1">
      </div>
    </div>
    
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:1rem;">
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" id="planCustomDomain" style="accent-color:var(--admin-primary);">
        <span>Custom Domain</span>
      </label>
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" id="planPhoneSupport" style="accent-color:var(--admin-primary);">
        <span>Phone Support</span>
      </label>
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" id="planApiAccess" style="accent-color:var(--admin-primary);">
        <span>API Access</span>
      </label>
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" id="planWhiteLabel" style="accent-color:var(--admin-primary);">
        <span>White Label</span>
      </label>
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" id="planIsFeatured" style="accent-color:var(--admin-primary);">
        <span>⭐ Featured</span>
      </label>
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" id="planIsActive" checked style="accent-color:var(--admin-primary);">
        <span>Active</span>
      </label>
    </div>
    
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('planModal')">Cancel</button>
      <button class="btn-save" onclick="savePlan()">💾 Save Plan</button>
    </div>
  </div>
</div>

<!-- News Article Modal -->
<div class="modal-overlay" id="newsModal" style="display:none;">
  <div class="modal" style="max-width:800px;">
    <h3 class="modal-title" id="newsModalTitle">📰 Create News Article</h3>
    
    <input type="hidden" id="newsId">
    
    <div class="admin-form-group">
      <label class="admin-form-label">Title *</label>
      <input type="text" class="admin-form-input" id="newsTitle" placeholder="Article title">
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Slug (URL)</label>
      <input type="text" class="admin-form-input" id="newsSlug" placeholder="article-title">
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Excerpt</label>
      <textarea class="admin-form-textarea" id="newsExcerpt" rows="2" placeholder="Brief summary"></textarea>
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Content *</label>
      <textarea class="admin-form-textarea" id="newsContent" rows="8" placeholder="Full article content"></textarea>
    </div>
    
    <div class="admin-form-grid" style="margin-top:1rem;">
      <div class="admin-form-group">
        <label class="admin-form-label">Category</label>
        <select class="admin-form-select" id="newsCategory">
          <option value="announcement">Announcement</option>
          <option value="update">Platform Update</option>
          <option value="tutorial">Tutorial</option>
          <option value="success_story">Success Story</option>
          <option value="general">General</option>
        </select>
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Featured Image URL</label>
        <input type="text" class="admin-form-input" id="newsFeaturedImage" placeholder="https://...">
      </div>
    </div>
    
    <div class="admin-form-grid" style="margin-top:1rem;">
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" id="newsIsFeatured" style="accent-color:var(--admin-primary);">
        <span>⭐ Featured Article</span>
      </label>
      <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
        <input type="checkbox" id="newsIsPublished" style="accent-color:var(--admin-primary);">
        <span>✓ Publish Immediately</span>
      </label>
    </div>
    
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('newsModal')">Cancel</button>
      <button class="btn-save" onclick="saveNews()">📰 Publish Article</button>
    </div>
  </div>
</div>

<!-- Advertisement Modal -->
<div class="modal-overlay" id="adModal" style="display:none;">
  <div class="modal" style="max-width:600px;">
    <h3 class="modal-title" id="adModalTitle">📢 Create Advertisement</h3>
    
    <input type="hidden" id="adId">
    
    <div class="admin-form-group">
      <label class="admin-form-label">Title *</label>
      <input type="text" class="admin-form-input" id="adTitle" placeholder="Ad title">
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Advertiser Name</label>
      <input type="text" class="admin-form-input" id="adAdvertiser" placeholder="Company name">
    </div>
    
    <div class="admin-form-grid" style="margin-top:1rem;">
      <div class="admin-form-group">
        <label class="admin-form-label">Ad Type</label>
        <select class="admin-form-select" id="adType">
          <option value="banner">Banner</option>
          <option value="sidebar">Sidebar</option>
          <option value="popup">Popup</option>
          <option value="inline">Inline</option>
        </select>
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Position</label>
        <select class="admin-form-select" id="adPosition">
          <option value="top">Top</option>
          <option value="bottom">Bottom</option>
          <option value="sidebar-left">Sidebar Left</option>
          <option value="sidebar-right">Sidebar Right</option>
        </select>
      </div>
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Image URL *</label>
      <input type="text" class="admin-form-input" id="adImageUrl" placeholder="https://...">
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Link URL *</label>
      <input type="text" class="admin-form-input" id="adLinkUrl" placeholder="https://...">
    </div>
    
    <div class="admin-form-grid" style="margin-top:1rem;">
      <div class="admin-form-group">
        <label class="admin-form-label">Start Date *</label>
        <input type="date" class="admin-form-input" id="adStartDate">
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">End Date *</label>
        <input type="date" class="admin-form-input" id="adEndDate">
      </div>
    </div>
    
    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;margin-top:1rem;">
      <input type="checkbox" id="adIsActive" checked style="accent-color:var(--admin-primary);">
      <span>Active</span>
    </label>
    
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('adModal')">Cancel</button>
      <button class="btn-save" onclick="saveAd()">📢 Save Advertisement</button>
    </div>
  </div>
</div>

<!-- Payment Record Modal -->
<div class="modal-overlay" id="paymentModal" style="display:none;">
  <div class="modal" style="max-width:550px;">
    <h3 class="modal-title">💳 Record Payment</h3>
    
    <div id="paymentSchoolInfo" style="background:rgba(255,255,255,0.05);padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
      <!-- School info loaded here -->
    </div>
    
    <input type="hidden" id="paymentSchoolId">
    <input type="hidden" id="paymentSubscriptionId">
    
    <div class="admin-form-group">
      <label class="admin-form-label">Amount (₦) *</label>
      <input type="number" class="admin-form-input" id="paymentAmount" step="0.01" min="0">
    </div>
    
    <div class="admin-form-grid" style="margin-top:1rem;">
      <div class="admin-form-group">
        <label class="admin-form-label">Payment Method</label>
        <select class="admin-form-select" id="paymentMethod">
          <option value="bank_transfer">Bank Transfer</option>
          <option value="card">Card</option>
          <option value="cash">Cash</option>
          <option value="paystack">Paystack</option>
          <option value="flutterwave">Flutterwave</option>
        </select>
      </div>
      <div class="admin-form-group">
        <label class="admin-form-label">Status</label>
        <select class="admin-form-select" id="paymentStatus">
          <option value="completed">Completed</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
        </select>
      </div>
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Transaction Reference</label>
      <input type="text" class="admin-form-input" id="paymentReference" placeholder="TXN-12345">
    </div>
    
    <div class="admin-form-group" style="margin-top:1rem;">
      <label class="admin-form-label">Notes</label>
      <textarea class="admin-form-textarea" id="paymentNotes" rows="2"></textarea>
    </div>
    
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('paymentModal')">Cancel</button>
      <button class="btn-save" onclick="savePayment()">💾 Record Payment</button>
    </div>
  </div>
</div>

<!-- ===== STYLES ===== -->
<style>
/* Modal Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
  padding: 2rem;
  overflow-y: auto;
}

.modal-overlay.show {
  display: flex;
}

/* Modal Container */
.modal {
  background: var(--admin-card-bg, #1a1a2e);
  border-radius: 16px;
  padding: 2rem;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  position: relative;
}

/* Modal Title */
.modal-title {
  font-family: var(--font-title);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  color: var(--admin-primary, #6BCBF7);
}

/* Modal Footer */
.modal-footer {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.1);
}

/* Form Grid */
.admin-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.admin-form-group.full {
  grid-column: 1 / -1;
}

/* Toast Container */
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
  background: var(--admin-card-bg, #1a1a2e);
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  transform: translateX(400px);
  opacity: 0;
  transition: all 0.3s ease;
  min-width: 300px;
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

/* School Switch Button */
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
  width: 100%;
}

.school-switch-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--admin-primary, #6BCBF7);
}

/* Responsive */
@media (max-width: 768px) {
  .admin-form-grid {
    grid-template-columns: 1fr;
  }
  
  .modal {
    width: 95%;
    padding: 1.5rem;
  }
}
</style>

<!-- ===== JAVASCRIPT ===== -->
<script>
// Modal Management Functions
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('show');
    document.body.style.overflow = ''; // Restore scrolling
  }
}

// Close modal on outside click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('show');
    document.body.style.overflow = '';
  }
});

// ESC key to close modal
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.show').forEach(m => {
      m.classList.remove('show');
      document.body.style.overflow = '';
    });
  }
});

// Toast notification function
function showToast(message, type = 'info') {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  
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

// School switcher
function switchActiveSchool(schoolKey) {
  localStorage.setItem('activeSchool', schoolKey);
  const label = document.getElementById('activeSchoolLabel');
  
  if (schoolKey === 'all') {
    label.textContent = '🏫 All Schools';
  } else if (schoolKey === 'brightstar') {
    label.textContent = '🦁 BrightStar';
  } else if (schoolKey === 'moonrise') {
    label.textContent = '🦅 Moonrise';
  }
  
  closeModal('schoolSwitchModal');
  showToast(`Switched to: ${label.textContent}`, 'success');
  
  // Reload data for active school
  if (typeof loadDashboardData === 'function') {
    loadDashboardData();
  }
}

function openSchoolModal() {
  openModal('schoolSwitchModal');
}

// Placeholder functions - implement in admin.js or inline
function saveUser() {
  console.log('Save user function');
  showToast('User saved successfully!', 'success');
  closeModal('userModal');
}

function approveSchoolRegistration() {
  console.log('Approve school registration');
  showToast('School registration approved!', 'success');
  closeModal('schoolApprovalModal');
}

function savePlan() {
  console.log('Save hosting plan');
  showToast('Hosting plan saved!', 'success');
  closeModal('planModal');
}

function saveNews() {
  console.log('Save news article');
  showToast('News article published!', 'success');
  closeModal('newsModal');
}

function saveAd() {
  console.log('Save advertisement');
  showToast('Advertisement saved!', 'success');
  closeModal('adModal');
}

function savePayment() {
  console.log('Save payment record');
  showToast('Payment recorded successfully!', 'success');
  closeModal('paymentModal');
}
</script>