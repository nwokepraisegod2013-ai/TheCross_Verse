
<?php
/* ============================================
   EDUVERSE PORTAL – DYNAMIC LANDING PAGE
   Pulls data from database + ALL animations
   ============================================ */

require_once __DIR__ . '/php/config.php';

// Fetch schools from database
try {
    $db = getDB();
    
    // Get schools
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
    $schools = [
        [
            'school_key' => 'brightstar',
            'name' => 'BrightStar Academy',
            'motto' => '✨ "Where Every Star Shines"',
            'description' => 'A premier institution focusing on STEM excellence, arts, and holistic development.',
            'features' => json_encode(['🔬 STEM Excellence Program','🎭 Arts & Drama Studio','⚽ Sports Academy','💻 Digital Innovation Lab'])
        ],
        [
            'school_key' => 'moonrise',
            'name' => 'Moonrise Institute',
            'motto' => '🌙 "Reach Beyond the Stars"',
            'description' => 'Nurturing creativity, environmental consciousness, and academic excellence.',
            'features' => json_encode(['🎵 Music & Performing Arts','🌿 Eco & Nature Studies','📖 Advanced Literature','🤖 Robotics & AI Club'])
        ]
    ];
    $ageGroups = [];
    $settings = ['portal_name' => 'EduVerse Portal'];
}

// Helper function to decode features
function getFeatures($school) {
    if (is_string($school['features'])) {
        return json_decode($school['features'], true) ?: [];
    }
    return is_array($school['features']) ? $school['features'] : [];
}

// Get BrightStar and Moonrise data
$brightstar = null;
$moonrise = null;
foreach ($schools as $school) {
    if ($school['school_key'] === 'brightstar') $brightstar = $school;
    if ($school['school_key'] === 'moonrise') $moonrise = $school;
}

// Fallback if not found
if (!$brightstar) {
    $brightstar = $schools[0] ?? ['school_key' => 'brightstar', 'name' => 'BrightStar Academy', 'motto' => '✨ "Where Every Star Shines"', 'description' => 'Excellence in education', 'features' => '[]'];
}
if (!$moonrise) {
    $moonrise = $schools[1] ?? ['school_key' => 'moonrise', 'name' => 'Moonrise Institute', 'motto' => '🌙 "Reach Beyond the Stars"', 'description' => 'Nurturing creativity', 'features' => '[]'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($settings['portal_name'] ?? 'TheCross Verse Portal'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;600;700;800&family=Nunito:wght@400;600;700;800&family=Fredoka+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/animations.css">
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
      <span class="brand-text"><?php echo htmlspecialchars($settings['portal_name'] ?? 'TheCross Verse'); ?></span>
    </a>
    <div class="nav-links">
      <a href="#schools" class="nav-link">Schools</a>
      <a href="#age-groups" class="nav-link">Age Groups</a>
      <a href="#about" class="nav-link">About</a>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <a href="login.html" class="nav-btn btn-login">Login</a>
  </nav>

  <!-- Hero Section with Orbiting Planet -->
  <section class="hero" id="hero">
    <div class="hero-content">
      <div class="hero-text">
        <div class="badge badge-pulse">🎯 Enrolling Now for 2025</div>
        <h1 class="hero-title">
          <span class="word-reveal">Welcome to</span>
          <span class="shimmer-text"><?php echo htmlspecialchars($settings['portal_name'] ?? 'TheCross Verse'); ?></span>
        </h1>
        <p class="hero-desc">
          Two exceptional schools, countless opportunities. Choose your path to academic excellence.
        </p>
        <div class="hero-cta">
          <a href="#schools" class="btn btn-primary ripple">Explore Schools 🏫</a>
          <a href="register.php" class="btn btn-secondary">Register Now →</a>
        </div>
        <div class="hero-stats">
          <div class="stat-item"><span class="counter" data-target="2180">0</span><span>Students</span></div>
          <div class="stat-item"><span class="counter" data-target="157">0</span><span>Teachers</span></div>
          <div class="stat-item"><span class="counter" data-target="2">0</span><span>Schools</span></div>
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
  </section>

  <!-- Schools Section with 3D Tilt Cards -->
  <section class="schools-section" id="schools">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">🏫 Our Schools</span>
        <h2 class="section-title">Two Paths to Excellence</h2>
        <p class="section-desc">Choose the school that aligns with your child's interests and aspirations</p>
      </div>

      <div class="schools-grid">
        <!-- BrightStar Card -->
        <div class="school-card brightstar tilt-card slide-up" data-school="brightstar">
          <div class="school-card-inner">
            <div class="school-mascot bounce-cont">🦁</div>
            <h3 class="school-name"><?php echo htmlspecialchars($brightstar['name']); ?></h3>
            <p class="school-motto"><?php echo htmlspecialchars($brightstar['motto']); ?></p>
            <p class="school-desc"><?php echo htmlspecialchars($brightstar['description']); ?></p>
            
            <div class="school-features">
              <?php 
              $features = getFeatures($brightstar);
              foreach ($features as $feature): 
              ?>
                <span class="feature-badge"><?php echo htmlspecialchars($feature); ?></span>
              <?php endforeach; ?>
            </div>
            
            <div class="school-actions">
              <a href="register.php?school=brightstar" class="btn btn-primary ripple">
                Register Now 🚀
              </a>
              <a href="#age-groups" class="btn btn-secondary">View Age Groups</a>
            </div>
          </div>
        </div>

         <!-- VS Divider -->
      <div class="vs-divider">
        <div class="vs-circle rotate-slow">VS</div>
        <div class="vs-sparks">✨✨✨</div>
      </div>

        <!-- Moonrise Card -->
        <div class="school-card moonrise tilt-card slide-up" style="animation-delay: 0.2s;" data-school="moonrise">
          <div class="school-card-inner">
            <div class="school-mascot bounce-cont">🦅</div>
            <h3 class="school-name"><?php echo htmlspecialchars($moonrise['name']); ?></h3>
            <p class="school-motto"><?php echo htmlspecialchars($moonrise['motto']); ?></p>
            <p class="school-desc"><?php echo htmlspecialchars($moonrise['description']); ?></p>
            
            <div class="school-features">
              <?php 
              $features = getFeatures($moonrise);
              foreach ($features as $feature): 
              ?>
                <span class="feature-badge"><?php echo htmlspecialchars($feature); ?></span>
              <?php endforeach; ?>
            </div>
            
            <div class="school-actions">
              <a href="register.php?school=moonrise" class="btn btn-primary ripple">
                Register Now 🚀
              </a>
              <a href="#age-groups" class="btn btn-secondary">View Age Groups</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Age Groups Section -->
  <section class="age-groups-section" id="age-groups">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">🎂 Age Groups</span>
        <h2 class="section-title">Learning Journeys for Every Stage</h2>
        <p class="section-desc">Age-appropriate curricula designed to nurture growth at every level</p>
      </div>

      <div class="age-groups-grid">
        <?php 
        // Fallback if no age groups in database
        if (empty($ageGroups)) {
          $ageGroups = [
            ['icon' => '🌱', 'name' => 'Tiny Sprouts', 'min_age' => 3, 'max_age' => 5, 'level_label' => 'Nursery', 'description' => 'Play-based learning, sensory exploration, and early literacy adventures'],
            ['icon' => '🌿', 'name' => 'Junior Explorers', 'min_age' => 6, 'max_age' => 8, 'level_label' => 'Primary 1–2', 'description' => 'Reading, writing, numbers and creative problem-solving'],
            ['icon' => '🌳', 'name' => 'Discoverers', 'min_age' => 9, 'max_age' => 11, 'level_label' => 'Primary 3–5', 'description' => 'Science experiments, coding basics, and deeper academic foundations'],
            ['icon' => '🚀', 'name' => 'Pioneers', 'min_age' => 12, 'max_age' => 14, 'level_label' => 'Junior High', 'description' => 'Critical thinking, leadership skills, and advanced STEM pathways'],
            ['icon' => '🏆', 'name' => 'Champions', 'min_age' => 15, 'max_age' => 18, 'level_label' => 'Senior High', 'description' => 'University prep, career pathways, and elite academic competitions']
          ];
        }
        
        foreach ($ageGroups as $index => $group): 
        ?>
        <div class="age-card bounce-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
          <div class="age-icon float-up-down"><?php echo $group['icon']; ?></div>
          <h4 class="age-name"><?php echo htmlspecialchars($group['name']); ?></h4>
          <p class="age-range">Ages <?php echo $group['min_age']; ?>–<?php echo $group['max_age']; ?></p>
          <p class="age-level"><?php echo htmlspecialchars($group['level_label']); ?></p>
          <p class="age-desc"><?php echo htmlspecialchars($group['description']); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features-section" id="about">
    <div class="container">
      <div class="section-header">
        <span class="section-tag wiggle">✨ Why Choose Us</span>
        <h2 class="section-title">Excellence in Education</h2>
      </div>

      <div class="features-grid">
        <div class="feature-card slide-up" style="animation-delay: 0s;">
          <div class="feature-icon float-up-down">👨‍🏫</div>
          <h4>Expert Faculty</h4>
          <p>Certified educators passionate about student success</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.1s;">
          <div class="feature-icon float-up-down">🏆</div>
          <h4>Proven Results</h4>
          <p>Outstanding academic achievements year after year</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.2s;">
          <div class="feature-icon float-up-down">🌍</div>
          <h4>Global Outlook</h4>
          <p>Preparing students for success in a connected world</p>
        </div>
        <div class="feature-card slide-up" style="animation-delay: 0.3s;">
          <div class="feature-icon float-up-down">💡</div>
          <h4>Innovation</h4>
          <p>Cutting-edge facilities and modern teaching methods</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section with Confetti -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-content">
        <h2 class="cta-title shimmer-text">Ready to Join Us?</h2>
        <p class="cta-desc">Start your journey towards academic excellence today</p>
        <div class="cta-buttons">
          <a href="register.html" class="btn btn-primary btn-large ripple pulse-btn" id="ctaBtn">
            Register Now 🎉
          </a>
          <a href="login.html" class="btn btn-secondary btn-large">
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
          <span class="brand-text"><?php echo htmlspecialchars($settings['portal_name'] ?? 'TheCross Verse'); ?></span>
        </div>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">
          Empowering minds, shaping futures
        </p>
      </div>
      <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); color: var(--text-muted);">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['portal_name'] ?? 'TheCross Verse Portal'); ?>. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Load main.js for all animations -->
  <script src="js/main.js"></script>
  
  <!-- Additional dynamic features -->
  <script>
    // Ensure all animations work with dynamic content
    document.addEventListener('DOMContentLoaded', function() {
      console.log('✅ Dynamic landing page loaded with animations');
      
      // Re-initialize any animations that need it
      if (typeof initAnimations === 'function') {
        initAnimations();
      }
    });
  </script>
</body>
</html>