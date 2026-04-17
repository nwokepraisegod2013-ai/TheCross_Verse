<?php
/* ============================================
   SCHOOL REGISTRATION FORM - COMPLETE
   Multi-step registration with website upload
   ============================================ */

session_start();
require_once __DIR__ . '/php/config.php';

// Get hosting plans
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM hosting_plans WHERE is_active = 1 ORDER BY display_order");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get preselected plan
    $preselectedPlan = $_GET['plan'] ?? null;
    $selectedPlanData = null;
    if ($preselectedPlan) {
        foreach ($plans as $plan) {
            if ($plan['slug'] === $preselectedPlan) {
                $selectedPlanData = $plan;
                break;
            }
        }
    }
} catch (Exception $e) {
    error_log("School registration error: " . $e->getMessage());
    $plans = [];
    $selectedPlanData = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Your School - EduVerse Platform</title>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/animations.css">
  <style>
    .upload-zone {
      border: 2px dashed rgba(107,203,247,0.5);
      border-radius: 16px;
      padding: 3rem 2rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      background: rgba(107,203,247,0.05);
    }
    .upload-zone:hover, .upload-zone.drag-over {
      border-color: var(--sky);
      background: rgba(107,203,247,0.1);
      transform: scale(1.02);
    }
    .upload-placeholder {
      color: var(--text-muted);
    }
    .progress-bar {
      width: 100%;
      height: 8px;
      background: rgba(255,255,255,0.1);
      border-radius: 4px;
      overflow: hidden;
      margin: 1rem 0;
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--sky), var(--purple));
      width: 0%;
      transition: width 0.3s;
    }
  </style>
</head>
<body class="register-body">
  <div class="bg-shapes" aria-hidden="true">
    <div class="shape s1">⭐</div><div class="shape s3">🌟</div><div class="shape s5">📚</div><div class="shape s7">🎓</div>
  </div>

  <nav class="navbar">
    <a href="index.php" class="nav-brand">
      <span class="brand-icon spin-slow">🎓</span>
      <span class="brand-text">EduVerse Platform</span>
    </a>
    <a href="login.php" class="nav-btn btn-login">Already have an account? Login →</a>
  </nav>

  <div class="register-container">
    <div class="register-header">
      <h1 style="font-family:var(--font-title);font-size:clamp(2rem,5vw,3rem);margin-bottom:0.5rem;">
        <span class="shimmer-text">Register Your School</span>
      </h1>
      <p style="color:var(--text-muted);">Join our platform and start your 14-day free trial</p>
    </div>

    <div class="register-steps">
      <div class="step-dot active" id="step1dot"></div>
      <div class="step-dot" id="step2dot"></div>
      <div class="step-dot" id="step3dot"></div>
      <div class="step-dot" id="step4dot"></div>
      <div class="step-dot" id="step5dot"></div>
    </div>

    <div class="register-card">
      <!-- Alert banners -->
      <div class="alert-banner alert-error" id="alertError" style="display:none;">
        <span style="font-size:1.3rem;">❌</span><span id="errorText">Error</span>
      </div>
      <div class="alert-banner alert-success" id="alertSuccess" style="display:none;">
        <span style="font-size:1.3rem;">✅</span><span id="successText">Success</span>
      </div>

      <!-- Step 1: School Information -->
      <div id="formStep1">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--sky);">🏫 School Information</h3>
        <div class="form-group">
          <label class="form-label">School Name *</label>
          <input type="text" id="schoolName" class="form-input" placeholder="e.g. BrightStar Academy" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">School Email *</label>
            <input type="email" id="schoolEmail" class="form-input" placeholder="info@school.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">School Phone *</label>
            <input type="tel" id="schoolPhone" class="form-input" placeholder="+234 800 0000 000" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">School Address</label>
          <input type="text" id="schoolAddress" class="form-input" placeholder="Street, City, State">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">School Type</label>
            <select id="schoolType" class="form-select">
              <option value="primary">Primary School</option>
              <option value="secondary">Secondary School</option>
              <option value="tertiary">Tertiary Institution</option>
              <option value="mixed" selected>Mixed (All Levels)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Existing Website (if any)</label>
            <input type="url" id="schoolWebsite" class="form-input" placeholder="https://yourschool.com">
          </div>
        </div>
        <button type="button" class="register-submit" onclick="goToStep(2)">Continue → 👤</button>
      </div>

      <!-- Step 2: Contact Person -->
      <div id="formStep2" style="display:none;">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--purple);">👤 Contact Person</h3>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" id="contactName" class="form-input" placeholder="John Doe" required>
          </div>
          <div class="form-group">
            <label class="form-label">Position *</label>
            <input type="text" id="contactPosition" class="form-input" placeholder="Principal / Director" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" id="contactEmail" class="form-input" placeholder="john@school.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone *</label>
            <input type="tel" id="contactPhone" class="form-input" placeholder="+234 800 0000 000" required>
          </div>
        </div>
        <div style="display:flex;gap:1rem;">
          <button type="button" class="register-submit" onclick="goToStep(1)" style="background:rgba(255,255,255,0.08);flex:0 0 auto;padding:0.9rem 2rem;">← Back</button>
          <button type="button" class="register-submit" onclick="goToStep(3)" style="flex:1;">Continue → 💰</button>
        </div>
      </div>

      <!-- Step 3: Choose Plan -->
      <div id="formStep3" style="display:none;">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--grass);">💰 Choose Your Plan</h3>
        <p style="background:rgba(107,203,247,0.1);border:1px solid rgba(107,203,247,0.3);border-radius:12px;padding:1rem;margin-bottom:1.5rem;font-size:0.9rem;">
          🎉 <strong>FREE Domain:</strong> Get yourschool.eduverse.ng subdomain free with any plan!
        </p>
        
        <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
          <?php foreach ($plans as $plan): ?>
          <div class="school-card" data-plan-id="<?php echo $plan['id']; ?>" data-plan-slug="<?php echo $plan['slug']; ?>" onclick="selectPlan(this)">
            <div style="flex:1;">
              <h4 style="font-family:var(--font-title);font-size:1.3rem;margin-bottom:0.2rem;">
                <?php echo htmlspecialchars($plan['plan_name']); ?>
                <?php if ($plan['price_monthly'] == 0): ?>
                <span class="badge" style="background:var(--grass);">FREE</span>
                <?php endif; ?>
              </h4>
              <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:0.5rem;">
                <?php echo htmlspecialchars($plan['description']); ?>
              </p>
              <p style="font-size:1.5rem;font-weight:bold;color:var(--sky);">
                ₦<?php echo number_format($plan['price_monthly'], 2); ?>/month
              </p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        
        <div class="form-group">
          <label class="form-label">Billing Cycle *</label>
          <select id="billingCycle" class="form-select" required onchange="updatePricing()">
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly (Save 10%)</option>
            <option value="yearly">Yearly (Save 20%)</option>
          </select>
        </div>
        
        <div style="display:flex;gap:1rem;">
          <button type="button" class="register-submit" onclick="goToStep(2)" style="background:rgba(255,255,255,0.08);flex:0 0 auto;padding:0.9rem 2rem;">← Back</button>
          <button type="button" class="register-submit" onclick="goToStep(4)" id="planContinueBtn" disabled style="flex:1;opacity:0.5;">Continue → 🌐</button>
        </div>
      </div>

      <!-- Step 4: Domain Selection -->
      <div id="formStep4" style="display:none;">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--orange);">🌐 Domain Setup</h3>
        
        <div class="form-group">
          <label class="form-label">Choose Your FREE Subdomain *</label>
          <div style="display:flex;gap:0.5rem;align-items:center;">
            <input type="text" id="subdomain" class="form-input" placeholder="yourschool" required style="flex:1;" oninput="checkSubdomain()">
            <span style="color:var(--text-light);font-weight:600;">.eduverse.ng</span>
          </div>
          <div id="subdomainStatus" style="margin-top:0.5rem;font-size:0.85rem;"></div>
        </div>
        
        <div class="form-group" style="margin-top:1.5rem;">
          <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
            <input type="checkbox" id="useCustomDomain" onchange="toggleCustomDomain()" style="accent-color:var(--sky);">
            <span>I have my own domain (optional)</span>
          </label>
        </div>
        
        <div class="form-group" id="customDomainGroup" style="display:none;">
          <label class="form-label">Custom Domain</label>
          <input type="text" id="customDomain" class="form-input" placeholder="www.yourschool.com">
          <p style="font-size:0.8rem;color:var(--text-muted);margin-top:0.5rem;">
            Note: You'll need to point your domain's DNS to our servers
          </p>
        </div>
        
        <div style="display:flex;gap:1rem;">
          <button type="button" class="register-submit" onclick="goToStep(3)" style="background:rgba(255,255,255,0.08);flex:0 0 auto;padding:0.9rem 2rem;">← Back</button>
          <button type="button" class="register-submit" onclick="goToStep(5)" style="flex:1;">Continue → 📁</button>
        </div>
      </div>

      <!-- Step 5: Website Upload (NEW) -->
      <div id="formStep5" style="display:none;">
        <h3 style="font-family:var(--font-fun);margin-bottom:1.5rem;font-size:1.2rem;color:var(--purple);">📁 Upload Your Website (Optional)</h3>
        
        <div class="upload-zone" id="uploadZone">
          <input type="file" id="websiteZip" accept=".zip" style="display:none;">
          <div class="upload-placeholder" id="uploadPlaceholder">
            <span style="font-size:3rem;">📁</span>
            <p style="margin:1rem 0 0.5rem;font-weight:600;">Click or drag ZIP file here</p>
            <small>Max 100MB • Must contain index.html or index.php</small>
            <p style="margin-top:1rem;font-size:0.85rem;color:var(--sky);">
              ✨ Supports: HTML, CSS, JavaScript, PHP, Images, Fonts
            </p>
          </div>
        </div>
        
        <div id="uploadProgress" style="display:none;margin-top:1rem;">
          <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
          </div>
          <p id="progressText" style="text-align:center;margin-top:0.5rem;">0%</p>
        </div>
        
        <div class="checkbox-group" style="margin-top:1.5rem;">
          <input type="checkbox" id="skipWebsite">
          <label for="skipWebsite">I don't have a website yet (we'll create a default template)</label>
        </div>
        
        <label style="display:flex;align-items:flex-start;gap:0.8rem;font-size:0.88rem;color:var(--text-muted);cursor:pointer;margin:1.5rem 0;">
          <input type="checkbox" id="agreeTerms" style="accent-color:var(--sky);margin-top:2px;">
          I agree to the Terms of Service and Privacy Policy
        </label>
        
        <div style="display:flex;gap:1rem;">
          <button type="button" class="register-submit" onclick="goToStep(4)" style="background:rgba(255,255,255,0.08);flex:0 0 auto;padding:0.9rem 2rem;">← Back</button>
          <button type="button" class="register-submit" onclick="submitSchoolRegistration()" id="submitBtn" style="flex:1;">🎉 Complete Registration</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Overlay -->
  <div class="success-overlay" id="successOverlay">
    <div class="success-card">
      <span class="success-icon">🎊</span>
      <h2 class="success-title">Registration Submitted!</h2>
      <p class="success-msg">Your school registration has been received. Our team will review and activate your account within 24 hours.</p>
      <div style="background:rgba(255,255,255,0.05);padding:1rem;border-radius:12px;margin:1rem 0;text-align:left;">
        <div><strong>School:</strong> <span id="regSchool"></span></div>
        <div><strong>Subdomain:</strong> <span id="regSubdomain"></span>.eduverse.ng</div>
        <div><strong>Plan:</strong> <span id="regPlan"></span></div>
        <div id="regWebsiteInfo"></div>
      </div>
      <a href="index.php" class="btn btn-primary" style="width:100%;justify-content:center;">Return Home 🏠</a>
    </div>
  </div>

  <script src="js/main.js"></script>
  <script>
    const plans = <?php echo json_encode($plans); ?>;
    let selectedPlan = <?php echo $selectedPlanData ? json_encode($selectedPlanData) : 'null'; ?>;
    let currentStep = 1;
    let uploadedWebsiteFile = null;
    
    function selectPlan(card) {
      const planId = parseInt(card.dataset.planId);
      selectedPlan = plans.find(p => p.id === planId);
      
      document.querySelectorAll('.school-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      
      document.getElementById('planContinueBtn').disabled = false;
      document.getElementById('planContinueBtn').style.opacity = '1';
      
      updatePricing();
    }
    
    function updatePricing() {
      if (!selectedPlan) return;
      const cycle = document.getElementById('billingCycle').value;
      // Update display if needed
    }
    
    function goToStep(step) {
      for (let i = 1; i <= 5; i++) {
        const el = document.getElementById(`formStep${i}`);
        if (el) el.style.display = i === step ? 'block' : 'none';
        const dot = document.getElementById(`step${i}dot`);
        if (dot) {
          dot.className = 'step-dot';
          if (i === step) dot.classList.add('active');
          if (i < step) dot.classList.add('done');
        }
      }
      currentStep = step;
      window.scrollTo({top: 0, behavior: 'smooth'});
    }
    
    async function checkSubdomain() {
      const input = document.getElementById('subdomain');
      const status = document.getElementById('subdomainStatus');
      const subdomain = input.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
      input.value = subdomain;
      
      if (subdomain.length < 3) {
        status.innerHTML = '⚠️ Minimum 3 characters';
        status.style.color = 'var(--orange)';
        return;
      }
      
      // Check availability via AJAX
      try {
        const response = await fetch(`php/check-subdomain.php?subdomain=${subdomain}`);
        const result = await response.json();
        
        if (result.available) {
          status.innerHTML = '✅ Available!';
          status.style.color = 'var(--grass)';
        } else {
          status.innerHTML = '❌ Already taken';
          status.style.color = 'var(--red)';
        }
      } catch (error) {
        status.innerHTML = '✅ Available!';
        status.style.color = 'var(--grass)';
      }
    }
    
    function toggleCustomDomain() {
      const checkbox = document.getElementById('useCustomDomain');
      const group = document.getElementById('customDomainGroup');
      group.style.display = checkbox.checked ? 'block' : 'none';
    }
    
    // Website Upload Handling
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('websiteZip');
    
    uploadZone.addEventListener('click', () => fileInput.click());
    
    uploadZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadZone.classList.add('drag-over');
    });
    
    uploadZone.addEventListener('dragleave', () => {
      uploadZone.classList.remove('drag-over');
    });
    
    uploadZone.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadZone.classList.remove('drag-over');
      const file = e.dataTransfer.files[0];
      if (file && file.name.endsWith('.zip')) {
        handleFileSelect(file);
      } else {
        showError('Please upload a ZIP file');
      }
    });
    
    fileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) handleFileSelect(file);
    });
    
    function handleFileSelect(file) {
      const maxSize = 100 * 1024 * 1024; // 100MB
      
      if (file.size > maxSize) {
        showError('File too large. Maximum 100MB allowed.');
        return;
      }
      
      uploadedWebsiteFile = file;
      
      document.getElementById('uploadPlaceholder').innerHTML = `
        <span style="font-size:3rem;">✅</span>
        <p style="margin:1rem 0 0.5rem;font-weight:600;">${file.name}</p>
        <small>${(file.size / (1024*1024)).toFixed(2)} MB</small>
        <button onclick="clearWebsiteFile(); event.stopPropagation();" 
                style="margin-top:1rem;padding:0.5rem 1rem;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:8px;color:white;cursor:pointer;">
          Remove File
        </button>
      `;
    }
    
    function clearWebsiteFile() {
      uploadedWebsiteFile = null;
      fileInput.value = '';
      document.getElementById('uploadPlaceholder').innerHTML = `
        <span style="font-size:3rem;">📁</span>
        <p style="margin:1rem 0 0.5rem;font-weight:600;">Click or drag ZIP file here</p>
        <small>Max 100MB • Must contain index.html or index.php</small>
        <p style="margin-top:1rem;font-size:0.85rem;color:var(--sky);">
          ✨ Supports: HTML, CSS, JavaScript, PHP, Images, Fonts
        </p>
      `;
    }
    
    async function submitSchoolRegistration() {
      if (!document.getElementById('agreeTerms').checked) {
        showError('Please agree to the Terms of Service');
        return;
      }
      
      if (!selectedPlan) {
        showError('Please select a plan');
        goToStep(3);
        return;
      }
      
      const formData = new FormData();
      
      // School info
      formData.append('schoolName', document.getElementById('schoolName').value);
      formData.append('schoolEmail', document.getElementById('schoolEmail').value);
      formData.append('schoolPhone', document.getElementById('schoolPhone').value);
      formData.append('schoolAddress', document.getElementById('schoolAddress').value);
      formData.append('schoolType', document.getElementById('schoolType').value);
      formData.append('schoolWebsite', document.getElementById('schoolWebsite').value);
      
      // Contact info
      formData.append('contactName', document.getElementById('contactName').value);
      formData.append('contactPosition', document.getElementById('contactPosition').value);
      formData.append('contactEmail', document.getElementById('contactEmail').value);
      formData.append('contactPhone', document.getElementById('contactPhone').value);
      
      // Plan info
      formData.append('planId', selectedPlan.id);
      formData.append('billingCycle', document.getElementById('billingCycle').value);
      
      // Domain info
      formData.append('subdomain', document.getElementById('subdomain').value);
      formData.append('customDomain', document.getElementById('useCustomDomain').checked ? document.getElementById('customDomain').value : '');
      
      // Website file
      if (uploadedWebsiteFile) {
        formData.append('websiteZip', uploadedWebsiteFile);
        formData.append('hasWebsite', '1');
      } else {
        formData.append('hasWebsite', '0');
      }
      
      try {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        
        // Show progress if file upload
        if (uploadedWebsiteFile) {
          document.getElementById('uploadProgress').style.display = 'block';
        }
        
        const response = await fetch('php/school-register.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          document.getElementById('regSchool').textContent = formData.get('schoolName');
          document.getElementById('regSubdomain').textContent = formData.get('subdomain');
          document.getElementById('regPlan').textContent = selectedPlan.plan_name;
          
          if (uploadedWebsiteFile) {
            document.getElementById('regWebsiteInfo').innerHTML = `<div><strong>Website:</strong> Uploaded (${(uploadedWebsiteFile.size / (1024*1024)).toFixed(2)}MB)</div>`;
          }
          
          document.getElementById('successOverlay').classList.add('show');
        } else {
          showError(result.message || 'Registration failed');
          submitBtn.disabled = false;
          submitBtn.textContent = '🎉 Complete Registration';
        }
      } catch (error) {
        console.error('Error:', error);
        showError('An error occurred. Please try again.');
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').textContent = '🎉 Complete Registration';
      }
    }
    
    function showError(message) {
      const alertError = document.getElementById('alertError');
      const errorText = document.getElementById('errorText');
      errorText.textContent = message;
      alertError.style.display = 'flex';
      setTimeout(() => {
        alertError.style.display = 'none';
      }, 5000);
      window.scrollTo({top: 0, behavior: 'smooth'});
    }
    
    // Auto-select plan if preselected
    if (selectedPlan) {
      setTimeout(() => {
        const card = document.querySelector(`[data-plan-slug="${selectedPlan.slug}"]`);
        if (card) selectPlan(card);
      }, 500);
    }
  </script>
</body>
</html>