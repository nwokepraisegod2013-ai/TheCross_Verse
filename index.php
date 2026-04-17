<?php
/* ============================================
   EDUVERSE SAAS PLATFORM – MAIN LANDING PAGE
   Multi-tenant school management platform
   ============================================ */

session_start();
require_once __DIR__ . '/php/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin.php');
        exit;
    } elseif ($role === 'student') {
        header('Location: student-dashboard.php');
        exit;
    }
}

// Fetch platform data
try {
    $db = getDB();
    
    // Get platform settings
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE category IN ('general', 'features', 'contact')");
    $settingsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $settings = [];
    foreach ($settingsRaw as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Get hosting plans
    $stmt = $db->query("SELECT * FROM hosting_plans WHERE is_active = 1 ORDER BY display_order, price_monthly");
    $hostingPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get registered schools (for directory)
    $stmt = $db->query("
        SELECT id, school_key, name, motto, description, logo_url, website_url, subscription_status
        FROM schools 
        WHERE status = 'active' 
        ORDER BY name
        LIMIT 12
    ");
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get featured news
    $stmt = $db->query("
        SELECT id, title, slug, excerpt, featured_image, category, publish_date, views
        FROM platform_news 
        WHERE is_published = 1 AND publish_date <= NOW()
        ORDER BY is_featured DESC, publish_date DESC
        LIMIT 6
    ");
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active advertisements
    $stmt = $db->query("
        SELECT * FROM platform_ads 
        WHERE is_active = 1 
        AND CURDATE() BETWEEN start_date AND end_date
        ORDER BY RAND()
        LIMIT 3
    ");
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Platform stats
    $totalSchools = $db->query("SELECT COUNT(*) FROM schools WHERE status = 'active'")->fetchColumn();
    $totalStudents = $db->query("SELECT COUNT(*) FROM student_profiles WHERE status = 'active'")->fetchColumn();
    $totalTeachers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active'")->fetchColumn();
    
} catch (Exception $e) {
    error_log("Index.php Error: " . $e->getMessage());
    $settings = [
        'platform_name' => 'EduVerse Portal Platform',
        'platform_tagline' => 'Empowering Schools with Technology',
        'support_email' => 'support@eduverse.com'
    ];
    $hostingPlans = [];
    $schools = [];
    $news = [];
    $ads = [];
    $totalSchools = 0;
    $totalStudents = 0;
    $totalTeachers = 0;
}

$platformName = $settings['platform_name'] ?? 'EduVerse Portal Platform';
$platformTagline = $settings['platform_tagline'] ?? 'Empowering Schools with Technology';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($platformName); ?> - School Management SaaS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Nunito:wght@400;600;700;800&family=Fredoka+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/animations.css">
  <style>
    /* Additional SaaS-specific styles */
    .pricing-card {
      background: rgba(255,255,255,0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 20px;
      padding: 2rem;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }
    .pricing-card.featured {
      border: 2px solid var(--sky);
      box-shadow: 0 0 30px rgba(107,203,247,0.3);
      transform: scale(1.05);
    }
    .pricing-card:hover {
      transform: translateY(-10px);
      border-color: var(--sky);
    }
    .pricing-badge {
      position: absolute;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, var(--sky), var(--purple));
      color: white;
      padding: 0.4rem 1rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
    }
    .price-amount {
      font-size: 3rem;
      font-weight: 800;
      color: var(--sky);
      font-family: var(--font-title);
    }
    .price-currency {
      font-size: 1.5rem;
      vertical-align: super;
    }
    .price-period {
      font-size: 1rem;
      color: var(--text-muted);
    }
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 1.5rem 0;
    }
    .feature-list li {
      padding: 0.5rem 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .feature-list li:before {
      content: "✓";
      color: var(--grass);
      font-weight: bold;
      font-size: 1.2rem;
    }
    .news-card {
      background: rgba(255,255,255,0.05);
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.3s;
      border: 1px solid rgba(255,255,255,0.1);
    }
    .news-card:hover {
      transform: translateY(-5px);
      border-color: var(--sky);
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .news-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      background: linear-gradient(135deg, rgba(107,203,247,0.2), rgba(167,139,250,0.2));
    }
    .news-content {
      padding: 1.5rem;
    }
    .school-directory-card {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      padding: 1.5rem;
      text-align: center;
      transition: all 0.3s;
    }
    .school-directory-card:hover {
      border-color: var(--sky);
      transform: translateY(-5px);
    }
    .school-logo {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      margin: 0 auto 1rem;
      background: linear-gradient(135deg, var(--sky), var(--purple));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
    }
  </style>
</head>
<body>

  <!-- Page loader -->
  <div class="page-loader" id="pageLoader">
    <div class="loader-inner">
      <div class="loader-dot"></div>
      <div class="loader-dot"></div>
      <div class="loader-dot"></div>
    </div>
  </div>

  <!-- Floating background shapes -->
  <div class="bg-shapes" aria-hidden="true">
    <div class="shape s1">⭐</div><div class="shape s2">🚀</div>
    <div class="shape s3">🌟</div><div class="shape s4">🌈</div>
    <div class="shape s5">📚</div><div class="shape s6">✏️</div>
    <div class="shape s7">🎓</div><div class="shape s8">💡</div>
    <div class="shape s9">✏️</div><div class="shape s10">🎨</div>
    <div class="shape s11">💡</div><div class="shape s12">📖</div>
  </div>

  <!-- Navbar -->
  <nav class="navbar" id="navbar">
    <a href="#" class="nav-brand">
      <span class="brand-icon spin-slow">🎓</span>
      <span class="brand-text"><?php echo htmlspecialchars($platformName); ?></span>
    </a>
    <div class="nav-links">
      <a href="#pricing" class="nav-link">Pricing</a>
      <a href="#schools" class="nav-link">Schools</a>
      <a href="#news" class="nav-link">News</a>
      <a href="#about" class="nav-link">About</a>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <div style="display:flex;gap:0.5rem;">
      <a href="login.php" class="nav-btn btn-login">Login</a>
      <a href="school-register.php" class="nav-btn btn-primary">Register School</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero" id="hero">
    <div class="hero-content">
      <div class="hero-text">
        <div class="badge badge-pulse">🚀 Join <?php echo $totalSchools; ?>+ Schools Already Using Our Platform</div>
        <h1 class="hero-title">
          <span class="word-reveal">Welcome to</span>
          <span class="shimmer-text"><?php echo htmlspecialchars($platformName); ?></span>
        </h1>
        <p class="hero-desc">
          <?php echo htmlspecialchars($platformTagline); ?>. Complete school management solution with free domain and affordable hosting.
        </p>
        <div class="hero-cta">
          <a href="school-register.php" class="btn btn-primary ripple">Start Free Trial 🎯</a>
          <a href="#pricing" class="btn btn-secondary">View Pricing →</a>
        </div>
        <div class="hero-stats">
          <div class="stat-item"><span class="counter" data-target="<?php echo $totalSchools; ?>">0</span><span>Schools</span></div>
          <div class="stat-item"><span class="counter" data-target="<?php echo $totalStudents; ?>">0</span><span>Students</span></div>
          <div class="stat-item"><span class="counter" data-target="<?php echo $totalTeachers; ?>">0</span><span>Teachers</span></div>
        </div>
      </div>
      
      <!-- Animated Orbiting Planet -->
      <div class="hero-visual">
        <div class="planet-wrapper">
          <div class="planet" id="heroPlanet">
            <div class="planet-ring"></div>
            <div class="planet-core">🌍</div>
            <div class="orbit orbit-1"><span class="satellite">📚</span></div>
            <div class="orbit orbit-2"><span class="satellite">🎓</span></div>
            <div class="orbit orbit-3"><span class="satellite">⚡</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing Section -->
  <section class="schools-section" id="pricing">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">💰 Pricing Plans</span>
        <h2 class="section-title">Choose Your Perfect Plan</h2>
        <p class="section-desc">
          <strong style="color:var(--grass);">FREE Domain Name Included!</strong> 
          Transparent pricing with no hidden fees
        </p>
      </div>

      <div class="schools-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
        <?php foreach ($hostingPlans as $plan): ?>
        <div class="pricing-card <?php echo $plan['is_featured'] ? 'featured' : ''; ?> slide-up">
          <?php if ($plan['is_featured']): ?>
          <div class="pricing-badge">⭐ POPULAR</div>
          <?php endif; ?>
          
          <div style="text-align:center;">
            <h3 style="font-size:1.8rem;font-family:var(--font-title);margin-bottom:1rem;">
              <?php echo htmlspecialchars($plan['plan_name']); ?>
            </h3>
            <p style="color:var(--text-muted);margin-bottom:1.5rem;">
              <?php echo htmlspecialchars($plan['description']); ?>
            </p>
            
            <div class="price-amount">
              <span class="price-currency">₦</span><?php echo number_format($plan['price_monthly'], 0); ?>
              <span class="price-period">/mo</span>
            </div>
            
            <?php if ($plan['price_yearly'] > 0): ?>
            <p style="font-size:0.85rem;color:var(--text-muted);margin-top:0.5rem;">
              or ₦<?php echo number_format($plan['price_yearly'], 0); ?>/year (save <?php echo round((1 - ($plan['price_yearly']/($plan['price_monthly']*12))) * 100); ?>%)
            </p>
            <?php endif; ?>
            
            <ul class="feature-list" style="text-align:left;margin-top:2rem;">
              <li>🌐 <strong>FREE</strong> .eduverse.ng subdomain</li>
              <?php if ($plan['custom_domain']): ?>
              <li>🔗 Custom domain support</li>
              <?php endif; ?>
              <li>👥 Up to <?php echo number_format($plan['max_students']); ?> students</li>
              <li>👨‍🏫 Up to <?php echo $plan['max_teachers']; ?> teachers</li>
              <li>💾 <?php echo $plan['max_storage_gb']; ?>GB storage</li>
              <li>🔒 SSL Certificate included</li>
              <?php if ($plan['email_support']): ?>
              <li>📧 Email support</li>
              <?php endif; ?>
              <?php if ($plan['phone_support']): ?>
              <li>📞 Priority phone support</li>
              <?php endif; ?>
              <?php if ($plan['api_access']): ?>
              <li>🔌 API access</li>
              <?php endif; ?>
              <?php if ($plan['white_label']): ?>
              <li>🎨 White-label branding</li>
              <?php endif; ?>
            </ul>
            
            <a href="school-register.php?plan=<?php echo $plan['slug']; ?>" 
               class="btn btn-primary ripple" 
               style="width:100%;margin-top:1.5rem;">
              <?php echo $plan['price_monthly'] == 0 ? 'Start Free Trial' : 'Get Started'; ?> →
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Schools Directory -->
  <section class="age-groups-section" id="schools">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">🏫 School Directory</span>
        <h2 class="section-title">Schools Using Our Platform</h2>
        <p class="section-desc">Join our growing community of educational institutions</p>
      </div>

      <div class="age-groups-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
        <?php foreach ($schools as $school): ?>
        <div class="school-directory-card bounce-in">
          <div class="school-logo">
            <?php if ($school['logo_url']): ?>
            <img src="<?php echo htmlspecialchars($school['logo_url']); ?>" alt="Logo" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
            <?php else: ?>
            🏫
            <?php endif; ?>
          </div>
          <h4 style="font-family:var(--font-title);font-size:1.1rem;margin-bottom:0.5rem;">
            <?php echo htmlspecialchars($school['name']); ?>
          </h4>
          <p style="font-size:0.85rem;color:var(--text-muted);font-style:italic;margin-bottom:0.5rem;">
            <?php echo htmlspecialchars($school['motto'] ?? ''); ?>
          </p>
          <span class="badge" style="background:<?php echo $school['subscription_status'] === 'active' ? 'var(--grass)' : 'var(--text-muted)'; ?>">
            <?php echo ucfirst($school['subscription_status']); ?>
          </span>
          <?php if ($school['website_url']): ?>
          <a href="<?php echo htmlspecialchars($school['website_url']); ?>" 
             class="btn btn-secondary" 
             style="margin-top:1rem;font-size:0.85rem;padding:0.5rem 1rem;"
             target="_blank">
            Visit Website →
          </a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      
      <?php if (count($schools) >= 12): ?>
      <div style="text-align:center;margin-top:3rem;">
        <a href="schools-directory.php" class="btn btn-primary">View All Schools →</a>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- News Section -->
  <section class="features-section" id="news">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">📰 Latest News</span>
        <h2 class="section-title">Platform Updates & Success Stories</h2>
      </div>

      <div class="features-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
        <?php foreach ($news as $article): ?>
        <div class="news-card slide-up">
          <?php if ($article['featured_image']): ?>
          <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
               alt="<?php echo htmlspecialchars($article['title']); ?>" 
               class="news-image">
          <?php else: ?>
          <div class="news-image" style="display:flex;align-items:center;justify-content:center;font-size:3rem;">
            📰
          </div>
          <?php endif; ?>
          
          <div class="news-content">
            <span class="badge" style="background:var(--purple);">
              <?php echo ucfirst($article['category']); ?>
            </span>
            <h4 style="margin:1rem 0 0.5rem;font-family:var(--font-title);">
              <?php echo htmlspecialchars($article['title']); ?>
            </h4>
            <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;">
              <?php echo htmlspecialchars(substr($article['excerpt'], 0, 100)) . '...'; ?>
            </p>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.85rem;color:var(--text-muted);">
              <span>👁️ <?php echo number_format($article['views']); ?> views</span>
              <a href="news.php?slug=<?php echo $article['slug']; ?>" class="btn btn-secondary" style="padding:0.5rem 1rem;">
                Read More →
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <?php if (count($news) >= 6): ?>
      <div style="text-align:center;margin-top:3rem;">
        <a href="news.php" class="btn btn-primary">View All News →</a>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section" id="about">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">✨ Why Choose Us</span>
        <h2 class="section-title">Complete School Management Solution</h2>
      </div>

      <div class="features-grid">
        <div class="feature-card slide-up">
          <div class="feature-icon float-up-down">🌐</div>
          <h4>FREE Domain</h4>
          <p>Get a professional .eduverse.ng subdomain absolutely free</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.1s;">
          <div class="feature-icon float-up-down">⚡</div>
          <h4>Fast & Reliable</h4>
          <p>99.9% uptime guaranteed with lightning-fast performance</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.2s;">
          <div class="feature-icon float-up-down">🔒</div>
          <h4>Secure & Safe</h4>
          <p>SSL encryption and regular backups protect your data</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.3s;">
          <div class="feature-icon float-up-down">📱</div>
          <h4>Mobile Ready</h4>
          <p>Access from anywhere on any device</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.4s;">
          <div class="feature-icon float-up-down">🎨</div>
          <h4>Customizable</h4>
          <p>Brand it your way with custom themes and logos</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.5s;">
          <div class="feature-icon float-up-down">💬</div>
          <h4>24/7 Support</h4>
          <p>Expert help whenever you need it</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Advertisements Banner (if available) -->
  <?php if (!empty($ads)): ?>
  <section style="padding:3rem 0;background:rgba(255,255,255,0.02);">
    <div class="container">
      <div class="section-header" style="margin-bottom:2rem;">
        <span class="section-tag">📢 Featured Partners</span>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:2rem;">
        <?php foreach ($ads as $ad): ?>
        <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" 
           target="_blank" 
           style="display:block;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.1);transition:transform 0.3s;"
           onmouseover="this.style.transform='scale(1.05)'"
           onmouseout="this.style.transform='scale(1)'">
          <?php if ($ad['image_url']): ?>
          <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" 
               alt="<?php echo htmlspecialchars($ad['title']); ?>"
               style="width:100%;height:200px;object-fit:cover;">
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-content">
        <h2 class="cta-title shimmer-text">Ready to Transform Your School?</h2>
        <p class="cta-desc">Start your 14-day free trial today. No credit card required!</p>
        <div class="cta-buttons">
          <a href="school-register.php" class="btn btn-primary btn-large ripple pulse-btn">
            Register Your School 🎉
          </a>
          <a href="login.php" class="btn btn-secondary btn-large">
            Already Registered? Login →
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <span class="brand-icon">🎓</span>
          <span class="brand-text"><?php echo htmlspecialchars($platformName); ?></span>
        </div>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">
          <?php echo htmlspecialchars($platformTagline); ?>
        </p>
        <div style="margin-top:1rem;display:flex;gap:1rem;flex-wrap:wrap;">
          <a href="mailto:<?php echo htmlspecialchars($settings['support_email'] ?? ''); ?>" style="color:var(--text-muted);">
            📧 <?php echo htmlspecialchars($settings['support_email'] ?? 'support@eduverse.com'); ?>
          </a>
          <span style="color:var(--text-muted);">|</span>
          <span style="color:var(--text-muted);">
            📞 <?php echo htmlspecialchars($settings['support_phone'] ?? '+234 800 0000 000'); ?>
          </span>
        </div>
      </div>
      <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); color: var(--text-muted);">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($platformName); ?>. All rights reserved.</p>
        <div style="margin-top:1rem;">
          <a href="#" style="color:var(--text-muted);margin:0 1rem;">Privacy Policy</a>
          <a href="#" style="color:var(--text-muted);margin:0 1rem;">Terms of Service</a>
          <a href="#" style="color:var(--text-muted);margin:0 1rem;">Contact Us</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="js/main.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      console.log('✅ SaaS Platform loaded');
      if (typeof initAnimations === 'function') {
        initAnimations();
      }
    });
  </script>
</body>
</html>