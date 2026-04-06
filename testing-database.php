<?php
/* ============================================
   SIMPLE DATABASE CONNECTION TEST
   Fixed for MariaDB compatibility
   ============================================ */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Test</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; 
            padding: 2rem; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .success { color: #4ade80; font-weight: bold; font-size: 1.2rem; }
        .error { color: #f87171; font-weight: bold; font-size: 1.2rem; }
        .warning { color: #fbbf24; font-weight: bold; }
        h1 { color: #fff; font-size: 2.5rem; margin-bottom: 2rem; text-align: center; }
        h2 { color: #e0e7ff; border-bottom: 2px solid rgba(255, 255, 255, 0.3); padding-bottom: 0.5rem; margin-top: 2rem; }
        .info-box { background: rgba(255, 255, 255, 0.05); border-left: 4px solid #4ade80; padding: 1rem; margin: 1rem 0; border-radius: 8px; }
        .error-box { background: rgba(248, 113, 113, 0.1); border-left: 4px solid #f87171; padding: 1rem; margin: 1rem 0; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; background: rgba(255, 255, 255, 0.05); border-radius: 8px; overflow: hidden; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        th { background: rgba(255, 255, 255, 0.1); font-weight: 600; }
        code { background: rgba(0, 0, 0, 0.3); padding: 0.2rem 0.6rem; border-radius: 4px; font-family: 'Courier New', monospace; color: #4ade80; }
        .btn { display: inline-block; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #4ade80, #22c55e); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 0.5rem; transition: transform 0.2s; }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Database Test</h1>

<?php
$configPath = __DIR__ . '/php/config.php';

if (!file_exists($configPath)) {
    echo "<div class='error-box'><p class='error'>❌ config.php not found</p></div>";
    exit;
}

require_once $configPath;
echo "<h2>✅ Config Loaded</h2><div class='info-box'><p class='success'>✅ config.php loaded</p></div>";

try {
    $db = getDB();
    $currentDb = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "<h2>✅ Connected</h2><div class='info-box'><p class='success'>✅ Connected to: <code>$currentDb</code></p></div>";
    
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<h2>📊 Tables (" . count($tables) . ")</h2>";
        echo "<table><tr><th>#</th><th>Table</th><th>Rows</th></tr>";
        foreach ($tables as $i => $t) {
            $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "<tr><td>" . ($i+1) . "</td><td><strong>$t</strong></td><td>$count</td></tr>";
        }
        echo "</table>";
        
        $required = ['users', 'schools', 'age_groups', 'student_profiles'];
        $missing = array_diff($required, $tables);
        
        if (empty($missing)) {
            echo "<div class='info-box'><p class='success'>✅ All required tables exist!</p></div>";
        } else {
            echo "<div class='error-box'><p class='warning'>⚠️ Missing: " . implode(', ', $missing) . "</p></div>";
        }
    } else {
        echo "<div class='error-box'><p class='warning'>⚠️ No tables found</p><p>Run COMPLETE-DATABASE-SETUP.sql</p></div>";
    }
    
    echo "<h2>🎯 Status</h2>";
    if (count($tables) >= 20) {
        echo "<div class='info-box'><p class='success'>✅ DATABASE IS WORKING!</p>";
        echo "<p><a href='index.php' class='btn'>Home</a> <a href='login.html' class='btn'>Login</a></p></div>";
    } else {
        echo "<div class='error-box'><p class='error'>❌ Setup incomplete</p><p>Run COMPLETE-DATABASE-SETUP.sql in phpMyAdmin</p></div>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Error</h2><div class='error-box'><p class='error'>Connection failed</p>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
}
?>

</div>
</body>
</html>