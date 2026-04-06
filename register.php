<?php
/* ============================================
   EDUVERSE PORTAL – DYNAMIC REGISTRATION
   Pulls schools & age groups from database
   ============================================ */

require_once __DIR__ . '/php/config.php';

// Fetch data from database
try {
    $db = getDB();
    
    // Get schools (no status filter if column doesn't exist)
    $stmt = $db->query("SELECT * FROM schools ORDER BY school_key");
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get age groups
    $stmt = $db->query("SELECT * FROM age_groups ORDER BY min_age");
    $ageGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get settings
    $stmt = $db->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Fallback to defaults if database fails
    error_log("Database error in register.php: " . $e->getMessage());
    
    $schools = [
        [
            'id' => 1,
            'school_key' => 'brightstar',
            'name' => 'BrightStar Academy',
            'motto' => '✨ "Where Every Star Shines"',
            'mascot' => '🦁',
            'color_primary' => '#6BCBF7',
            'status' => 'active'
        ],
        [
            'id' => 2,
            'school_key' => 'moonrise',
            'name' => 'Moonrise Institute',
            'motto' => '🌙 "Reach Beyond the Stars"',
            'mascot' => '🦅',
            'color_primary' => '#14B8A6',
            'status' => 'active'
        ]
    ];
    
    $ageGroups = [
        ['id' => 1, 'group_key' => 'tiny', 'name' => 'Tiny Sprouts', 'age_range' => '3-5', 'min_age' => 3, 'max_age' => 5, 'icon' => '🌱', 'description' => 'Play-based learning', 'level_label' => 'Nursery'],
        ['id' => 2, 'group_key' => 'junior', 'name' => 'Junior Explorers', 'age_range' => '6-8', 'min_age' => 6, 'max_age' => 8, 'icon' => '🌿', 'description' => 'Reading & writing', 'level_label' => 'Primary 1-2'],
        ['id' => 3, 'group_key' => 'discover', 'name' => 'Discoverers', 'age_range' => '9-11', 'min_age' => 9, 'max_age' => 11, 'icon' => '🌳', 'description' => 'Science & coding', 'level_label' => 'Primary 3-5'],
        ['id' => 4, 'group_key' => 'pioneer', 'name' => 'Pioneers', 'age_range' => '12-14', 'min_age' => 12, 'max_age' => 14, 'icon' => '🚀', 'description' => 'Critical thinking', 'level_label' => 'Junior High'],
        ['id' => 5, 'group_key' => 'champion', 'name' => 'Champions', 'age_range' => '15-18', 'min_age' => 15, 'max_age' => 18, 'icon' => '🏆', 'description' => 'University prep', 'level_label' => 'Senior High']
    ];
    
    $settings = ['portal_name' => 'EduVerse Portal'];
}

// Check for school parameter
$preselectedSchool = $_GET['school'] ?? null;
$selectedSchoolData = null;

if ($preselectedSchool) {
    foreach ($schools as $school) {
        if ($school['school_key'] === $preselectedSchool) {
            $selectedSchoolData = $school;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register – <?php echo htmlspecialchars($settings['portal_name'] ?? 'EduVerse Portal'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Nunito:wght@400;600;700;800&family=Fredoka+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/animations.css">
  <style>
    .loading-overlay{position:fixed;inset:0;background:rgba(15,14,46,0.95);backdrop-filter:blur(10px);display:none;align-items:center;justify-content:center;z-index:9999;flex-direction:column;gap:1rem;}
    .loading-overlay.show{display:flex;}
    .spinner{width:60px;height:60px;border:4px solid rgba(107,203,247,0.2);border-top-color:var(--sky);border-radius:50%;animation:spin 0.8s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg);}}
    .loading-text{color:var(--text-light);font-size:1.1rem;font-weight:600;}
    .alert-banner{padding:1rem 1.5rem;border-radius:14px;margin-bottom:1.5rem;display:none;align-items:center;gap:0.75rem;font-size:0.95rem;font-weight:600;animation:slideDown 0.3s ease-out;}
    .alert-banner.show{display:flex;}
    .alert-error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#FCA5A5;}
    .alert-success{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86EFAC;}
    .alert-warning{background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.3);color:#FCD34D;}
    @keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
    .form-input.error,.form-select.error{border-color:#EF4444!important;background:rgba(239,68,68,0.05)!important;}
    .field-error{color:#FCA5A5;font-size:0.8rem;margin-top:0.3rem;display:none;}
    .field-error.show{display:block;}
    .school-card{padding:1.5rem;border-radius:16px;border:2px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.03);cursor:pointer;transition:all 0.3s;display:flex;align-items:center;gap:1rem;}
    .school-card:hover{border-color:var(--sky);background:rgba(107,203,247,0.05);transform:translateY(-2px);}
    .school-card.selected{border-color:var(--sky);background:rgba(107,203,247,0.1);box-shadow:0 0 20px rgba(107,203,247,0.3);}
    .interest-btn{font-size:0.82rem;padding:0.6rem 0.4rem;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.03);color:var(--text-muted);cursor:pointer;transition:all 0.3s;}
    .interest-btn:hover{border-color:rgba(107,203,247,0.3);background:rgba(107,203,247,0.05);}
    .interest-btn.active{border-color:var(--sky);background:rgba(107,203,247,0.1);color:var(--text-light);}
  </style>
</head>
<body class="register-body">
  <div class="bg-shapes" aria-hidden="true">
    <div class="shape s1">⭐</div><div class="shape s3">🌟</div><div class="shape s5">📚</div><div class="shape s7">🎓</div>
    <div class="shape s9">✏️</div><div class="shape s11">💡</div><div class="shape s2">🚀</div><div class="shape s4">🌈</div>
  </div>

  <div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div><div class="loading-text" id="loadingText">Processing...</div>
  </div>

  <nav class="navbar">
    <a href="index.php" class="nav-brand" style="font-family:var(--font-title)">
      <span class="brand-icon spin-slow">🎓</span>
      <span class="brand-text"><?php echo htmlspecialchars($settings['portal_name'] ?? 'EduVerse'); ?></span>
    </a>
    <a href="login.html" class="nav-btn btn-login">Already have an account? Login →</a>
  </nav>

  <div class="register-container">
    <div class="register-header">
      <h1 style="font-family:var(--font-title);font-size:clamp(2rem,5vw,3rem);margin-bottom:0.5rem;">
        <span id="headerEmoji"><?php echo $selectedSchoolData['mascot'] ?? '🎓'; ?></span> 
        Join <span id="headerSchoolName" class="shimmer-text"><?php echo htmlspecialchars($selectedSchoolData['name'] ?? $settings['portal_name'] ?? 'EduVerse Portal'); ?></span>
      </h1>
      <p style="color:var(--text-muted);">Complete the form — admin will create your login</p>
    </div>

    <div class="register-steps">
      <div class="step-dot active" id="step1dot"></div>
      <div class="step-dot" id="step2dot"></div>
      <div class="step-dot" id="step3dot"></div>
    </div>

    <div class="register-card">
      <div class="alert-banner alert-error" id="alertError">
        <span style="font-size:1.3rem;">❌</span><span id="errorText">Error</span>
      </div>
      <div class="alert-banner alert-warning" id="alertWarning">
        <span style="font-size:1.3rem;">⚠️</span><span id="warningText">Warning</span>
      </div>

      <div class="register-school-info" id="schoolBanner" style="background:rgba(107,203,247,0.1);border-color:rgba(107,203,247,0.3);">
        <span class="mascot" id="bannerMascot"><?php echo $selectedSchoolData['mascot'] ?? '🎓'; ?></span>
        <div>
          <h3 id="bannerSchoolName"><?php echo htmlspecialchars($selectedSchoolData['name'] ?? 'Select a School'); ?></h3>
          <p id="bannerSchoolMotto"><?php echo htmlspecialchars($selectedSchoolData['motto'] ?? 'Choose your path to excellence'); ?></p>
        </div>
      </div>

      <?php if (!$preselectedSchool): ?>
      <!-- STEP 0: School Selection -->
      <div id="formStep0">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--sky);">🏫 Select Your School</h3>
        <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
          <?php foreach ($schools as $school): ?>
          <div class="school-card" data-school-id="<?php echo $school['id']; ?>" data-school-key="<?php echo htmlspecialchars($school['school_key']); ?>" onclick="selectSchool(this)">
            <span style="font-size:3rem;"><?php echo $school['mascot'] ?? '🎓'; ?></span>
            <div style="flex:1;">
              <h4 style="font-family:var(--font-title);font-size:1.3rem;margin-bottom:0.2rem;"><?php echo htmlspecialchars($school['name']); ?></h4>
              <p style="color:var(--text-muted);font-size:0.9rem;"><?php echo htmlspecialchars($school['motto'] ?? ''); ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="register-submit" onclick="goToStep(1)" id="schoolContinueBtn" disabled style="opacity:0.5;">Continue → 🚀</button>
      </div>
      <?php endif; ?>

      <!-- STEP 1: Personal Information -->
      <div id="formStep1" style="display:<?php echo $preselectedSchool ? 'block' : 'none'; ?>;">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--sky);">📋 Step 1: Personal Information</h3>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" id="firstName" class="form-input" placeholder="e.g. Alex" required>
            <div class="field-error" id="firstNameError">Required (min 2 characters)</div>
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" id="lastName" class="form-input" placeholder="e.g. Johnson" required>
            <div class="field-error" id="lastNameError">Required (min 2 characters)</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date of Birth *</label>
            <input type="date" id="dob" class="form-input" required>
            <div class="field-error" id="dobError">Required (age 3-18)</div>
          </div>
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select id="gender" class="form-select">
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Age Group *</label>
          <select id="ageGroup" class="form-select" required onchange="updateAgeInfo()">
            <option value="">Select your age group</option>
            <?php foreach ($ageGroups as $group): ?>
            <option value="<?php echo $group['id']; ?>" data-description="<?php echo htmlspecialchars($group['description']); ?>">
              <?php echo ($group['icon'] ?? '📚') . ' ' . htmlspecialchars($group['name']) . ' (' . $group['group_key'] . ') – ' . ($group['level_label'] ?? ''); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="field-error" id="ageGroupError">Required</div>
        </div>
        <div id="ageInfoBox" style="display:none;background:rgba(107,203,247,0.08);border:1px solid rgba(107,203,247,0.2);border-radius:14px;padding:1rem;margin-bottom:1rem;font-size:0.88rem;color:var(--text-muted);"></div>
        <button type="button" class="register-submit" onclick="goToStep(2)">Continue → 🚀</button>
      </div>

      <!-- STEP 2: Contact Information -->
      <div id="formStep2" style="display:none;">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--purple);">📞 Step 2: Contact Information</h3>
        <div class="form-group">
          <label class="form-label">Parent/Guardian Name *</label>
          <input type="text" id="parentName" class="form-input" placeholder="Full name" required>
          <div class="field-error" id="parentNameError">Required (min 2 characters)</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Parent Email *</label>
            <input type="email" id="parentEmail" class="form-input" placeholder="parent@email.com" required>
            <div class="field-error" id="parentEmailError">Valid email required</div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone *</label>
            <input type="tel" id="phone" class="form-input" placeholder="+234 800 0000 000" required>
            <div class="field-error" id="phoneError">Required (10+ digits)</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" id="address" class="form-input" placeholder="Street, City, State">
        </div>
        <div style="display:flex;gap:1rem;">
          <button type="button" class="register-submit" onclick="goToStep(1)" style="background:rgba(255,255,255,0.08);border:2px solid rgba(255,255,255,0.2);color:var(--text-light);box-shadow:none;flex:0 0 auto;padding:0.9rem 2rem;">← Back</button>
          <button type="button" class="register-submit" onclick="goToStep(3)" style="flex:1;">Continue → 📝</button>
        </div>
      </div>

      <!-- STEP 3: Confirmation -->
      <div id="formStep3" style="display:none;">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--grass);">🎯 Step 3: Confirmation</h3>
        <div class="form-group">
          <label class="form-label">Interests (select any)</label>
          <div id="interestsGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.6rem;"></div>
        </div>
        <div class="form-group" style="margin-top:1.2rem;">
          <label class="form-label">Notes</label>
          <textarea id="notes" class="form-input" rows="2" placeholder="Additional info..."></textarea>
        </div>
        <div style="background:rgba(255,255,255,0.04);border-radius:14px;padding:1.2rem;margin-bottom:1.5rem;border:1px solid rgba(255,255,255,0.08);">
          <h4 style="font-size:0.9rem;margin-bottom:0.6rem;">📋 Summary</h4>
          <div id="summaryBox" style="font-size:0.85rem;color:var(--text-muted);display:grid;grid-template-columns:1fr 1fr;gap:0.4rem;"></div>
        </div>
        <label style="display:flex;align-items:flex-start;gap:0.8rem;font-size:0.88rem;color:var(--text-muted);cursor:pointer;margin-bottom:1.2rem;">
          <input type="checkbox" id="agreeTerms" style="accent-color:var(--sky);margin-top:2px;"> I confirm this information is accurate
        </label>
        <div style="display:flex;gap:1rem;">
          <button type="button" class="register-submit" onclick="goToStep(2)" style="background:rgba(255,255,255,0.08);border:2px solid rgba(255,255,255,0.2);color:var(--text-light);box-shadow:none;flex:0 0 auto;padding:0.9rem 2rem;">← Back</button>
          <button type="button" class="register-submit" onclick="submitRegistration()" id="submitBtn" style="flex:1;">🎉 Submit Registration</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Overlay -->
  <div class="success-overlay" id="successOverlay">
    <div class="success-card">
      <span class="success-icon">🎊</span>
      <h2 class="success-title">Registration Complete!</h2>
      <p class="success-msg" id="successMsg">Application submitted! Admin will create your login.</p>
      <div id="registrationDetails" style="background:rgba(255,255,255,0.05);padding:1rem;border-radius:12px;margin:1rem 0;font-size:0.9rem;text-align:left;">
        <div><strong>ID:</strong> <span id="regId">#12345</span></div>
        <div><strong>Student:</strong> <span id="regStudent"></span></div>
        <div><strong>School:</strong> <span id="regSchool"></span></div>
      </div>
      <div style="display:flex;flex-direction:column;gap:0.8rem;">
        <a href="index.php" class="btn btn-primary" style="justify-content:center;">🏠 Home</a>
        <a href="login.html" class="btn btn-secondary" style="justify-content:center;">🔑 Login</a>
      </div>
    </div>
  </div>

  <script>
  'use strict';
  
  // PHP data injected into JavaScript
  const schoolsData = <?php echo json_encode($schools); ?>;
  const ageGroupsData = <?php echo json_encode($ageGroups); ?>;
  let selectedSchool = <?php echo $selectedSchoolData ? json_encode($selectedSchoolData) : 'null'; ?>;
  let currentStep = <?php echo $preselectedSchool ? 1 : 0; ?>;
  
  const interests = ['📚 Reading','🔬 Science','🎨 Art','🎵 Music','⚽ Sports','💻 Tech','🌿 Nature','🎭 Drama','🤖 Robotics','📐 Math','🌍 Geography','🗣️ Languages'];
  
  console.log('🎓 Register.php - Dynamic from Database');
  console.log('📊 Schools loaded:', schoolsData.length);
  console.log('📊 Age groups loaded:', ageGroupsData.length);
  if(selectedSchool) console.log('✅ Preselected:', selectedSchool.name);
  
  // Build interests grid
  window.addEventListener('DOMContentLoaded', () => {
    buildInterestsGrid();
  });
  
  function selectSchool(card) {
    const schoolId = parseInt(card.dataset.schoolId);
    selectedSchool = schoolsData.find(s => s.id === schoolId);
    
    if (!selectedSchool) return;
    
    // Update UI
    document.getElementById('headerEmoji').textContent = selectedSchool.mascot || '🎓';
    document.getElementById('headerSchoolName').textContent = selectedSchool.name;
    document.getElementById('bannerMascot').textContent = selectedSchool.mascot || '🎓';
    document.getElementById('bannerSchoolName').textContent = selectedSchool.name;
    document.getElementById('bannerSchoolMotto').textContent = selectedSchool.motto || '';
    
    // Update banner colors
    const banner = document.getElementById('schoolBanner');
    if (selectedSchool.school_key === 'brightstar' || selectedSchool.color_primary === '#6BCBF7') {
      banner.style.background = 'rgba(107,203,247,0.1)';
      banner.style.borderColor = 'rgba(107,203,247,0.3)';
    } else {
      banner.style.background = 'rgba(20,184,166,0.1)';
      banner.style.borderColor = 'rgba(20,184,166,0.3)';
    }
    
    // Highlight card
    document.querySelectorAll('.school-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    
    // Enable continue
    document.getElementById('schoolContinueBtn').disabled = false;
    document.getElementById('schoolContinueBtn').style.opacity = '1';
    
    console.log('✅ Selected:', selectedSchool.name);
  }
  
  function buildInterestsGrid() {
    const grid = document.getElementById('interestsGrid');
    grid.innerHTML = '';
    interests.forEach(i => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = i;
      btn.className = 'interest-btn';
      btn.onclick = function() { this.classList.toggle('active'); };
      grid.appendChild(btn);
    });
  }
  
  function updateAgeInfo() {
    const select = document.getElementById('ageGroup');
    const box = document.getElementById('ageInfoBox');
    const opt = select.options[select.selectedIndex];
    if (opt && opt.value) {
      box.style.display = 'block';
      box.innerHTML = '📌 ' + (opt.dataset.description || '');
    } else {
      box.style.display = 'none';
    }
  }
  
  function showAlert(type, msg) {
    hideAllAlerts();
    const id = type === 'error' ? 'alertError' : 'alertWarning';
    const textId = type === 'error' ? 'errorText' : 'warningText';
    document.getElementById(id).classList.add('show');
    document.getElementById(textId).textContent = msg;
    setTimeout(() => document.getElementById(id).classList.remove('show'), 5000);
  }
  
  function hideAllAlerts() {
    document.querySelectorAll('.alert-banner,.field-error').forEach(e => e.classList.remove('show'));
    document.querySelectorAll('.form-input,.form-select').forEach(e => e.classList.remove('error'));
  }
  
  function validateField(id, eid, validator = null) {
    const f = document.getElementById(id);
    const e = document.getElementById(eid);
    const val = f.value.trim();
    let ok = !!val;
    if (ok && validator) ok = validator(val);
    if (!ok) { f.classList.add('error'); e.classList.add('show'); }
    else { f.classList.remove('error'); e.classList.remove('show'); }
    return ok;
  }
  
  const validateName = n => n.length >= 2;
  const validateEmail = e => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
  const validatePhone = p => p.replace(/\D/g,'').length >= 10;
  const validateAge = d => { const a = new Date().getFullYear() - new Date(d).getFullYear(); return a >= 3 && a <= 18; };
  
  function validateStep1() {
    hideAllAlerts();
    let ok = true;
    ok = validateField('firstName','firstNameError',validateName) && ok;
    ok = validateField('lastName','lastNameError',validateName) && ok;
    ok = validateField('dob','dobError',validateAge) && ok;
    ok = validateField('ageGroup','ageGroupError') && ok;
    if (!ok) showAlert('error', 'Fill all required fields correctly');
    return ok;
  }
  
  function validateStep2() {
    hideAllAlerts();
    let ok = true;
    ok = validateField('parentName','parentNameError',validateName) && ok;
    ok = validateField('parentEmail','parentEmailError',validateEmail) && ok;
    ok = validateField('phone','phoneError',validatePhone) && ok;
    if (!ok) showAlert('error', 'Fill contact information correctly');
    return ok;
  }
  
  function goToStep(s) {
    if (s > currentStep) {
      if (currentStep === 0 && !selectedSchool) { showAlert('warning', 'Select a school first'); return; }
      if (currentStep === 1 && !validateStep1()) return;
      if (currentStep === 2 && !validateStep2()) return;
    }
    for (let i = 0; i <= 3; i++) {
      const el = document.getElementById(`formStep${i}`);
      if (el) el.style.display = i === s ? 'block' : 'none';
    }
    for (let i = 1; i <= 3; i++) {
      const dot = document.getElementById(`step${i}dot`);
      if (dot) {
        dot.className = 'step-dot';
        if (i === s) dot.classList.add('active');
        if (i < s) dot.classList.add('done');
      }
    }
    if (s === 3) buildSummary();
    currentStep = s;
    window.scrollTo({top: 0, behavior: 'smooth'});
  }
  
  function buildSummary() {
    const fn = document.getElementById('firstName').value.trim();
    const ln = document.getElementById('lastName').value.trim();
    const dob = document.getElementById('dob').value;
    const ag = document.getElementById('ageGroup');
    const agt = ag.options[ag.selectedIndex]?.text || '';
    const pn = document.getElementById('parentName').value.trim();
    const pe = document.getElementById('parentEmail').value.trim();
    document.getElementById('summaryBox').innerHTML = `
      <div><strong>Student:</strong> ${fn} ${ln}</div>
      <div><strong>School:</strong> ${selectedSchool?.name || 'N/A'}</div>
      <div><strong>DOB:</strong> ${dob}</div>
      <div><strong>Age:</strong> ${agt.split('(')[0]?.trim()}</div>
      <div><strong>Parent:</strong> ${pn}</div>
      <div><strong>Email:</strong> ${pe}</div>
    `;
  }
  
  function setLoading(on, txt = 'Processing...') {
    const o = document.getElementById('loadingOverlay');
    const t = document.getElementById('loadingText');
    const b = document.getElementById('submitBtn');
    if (on) {
      o.classList.add('show');
      t.textContent = txt;
      if (b) { b.disabled = true; b.textContent = '⏳ Submitting...'; }
    } else {
      o.classList.remove('show');
      if (b) { b.disabled = false; b.textContent = '🎉 Submit Registration'; }
    }
  }
  
  async function submitRegistration() {
    if (!document.getElementById('agreeTerms').checked) { showAlert('warning', 'Agree to terms'); return; }
    if (!selectedSchool) { showAlert('error', 'Select a school'); goToStep(0); return; }
    if (!validateStep1() || !validateStep2()) { showAlert('error', 'Complete all fields'); return; }
    
    setLoading(true, 'Submitting...');
    console.log('📤 Submitting...');
    
    const si = Array.from(document.querySelectorAll('#interestsGrid .interest-btn.active')).map(b => b.textContent.trim());
    
    // Get selected age group details
    const ageGroupSelect = document.getElementById('ageGroup');
    const selectedAgeGroupId = ageGroupSelect.value;
    const selectedAgeGroup = ageGroupsData.find(ag => ag.id == selectedAgeGroupId);
    
    const d = {
      school: selectedSchool.school_key,  // PHP expects 'school' as key (brightstar/moonrise)
      ageGroup: selectedAgeGroup?.group_key || 'unknown',  // PHP expects 'ageGroup' as key
      firstName: document.getElementById('firstName').value.trim(),
      lastName: document.getElementById('lastName').value.trim(),
      dob: document.getElementById('dob').value,
      gender: document.getElementById('gender').value,
      parentName: document.getElementById('parentName').value.trim(),
      parentEmail: document.getElementById('parentEmail').value.trim(),
      phone: document.getElementById('phone').value.trim(),
      address: document.getElementById('address').value.trim(),
      interests: si,
      notes: document.getElementById('notes').value.trim()
    };
    
    console.log('📋 Data:', d);
    
    try {
      const r = await fetch('php/register.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify(d)
      });
      console.log('📡 Status:', r.status);
      if (!r.ok) throw new Error(`Server error: ${r.status}`);
      const res = await r.json();
      console.log('📥 Response:', res);
      if (res.success) {
        console.log('✅ Success!');
        setLoading(false);
        showSuccess(res.message || 'Submitted!', res.registrationId || res.id || 'N/A', d);
      } else {
        setLoading(false);
        console.error('❌ Failed:', res.message);
        showAlert('error', res.message || 'Failed');
      }
    } catch (e) {
      setLoading(false);
      console.error('❌ Error:', e);
      showAlert('error', e.message.includes('fetch') ? 'Cannot connect' : 'Error occurred');
    }
  }
  
  function showSuccess(msg, id, data) {
    document.getElementById('successMsg').textContent = msg;
    document.getElementById('regId').textContent = '#' + id;
    document.getElementById('regStudent').textContent = `${data.firstName} ${data.lastName}`;
    document.getElementById('regSchool').textContent = selectedSchool?.name || 'EduVerse';
    document.getElementById('successOverlay').classList.add('show');
    createConfetti();
  }
  
  function createConfetti() {
    const c = ['#FFD93D','#6BCBF7','#FF6B9D','#A78BFA','#6BCB77'];
    for (let i = 0; i < 50; i++) {
      const p = document.createElement('div');
      p.style.cssText = `position:fixed;left:${Math.random()*100}%;top:-10px;width:${Math.random()*10+5}px;height:${Math.random()*10+5}px;background:${c[Math.floor(Math.random()*c.length)]};border-radius:${Math.random()>0.5?'50%':'3px'};animation:confettiFall ${Math.random()*2+1.5}s ${Math.random()*2}s linear forwards;pointer-events:none;z-index:10000;`;
      document.body.appendChild(p);
      setTimeout(() => p.remove(), 5000);
    }
  }
  </script>
</body>
</html>