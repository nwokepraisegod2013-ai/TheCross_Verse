<?php
/* ============================================
   DEBUG SCRIPT - Show Exact Database Content
   ============================================ */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/php/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration Debug</title>
    <style>
        body { font-family: monospace; background: #0f0e2e; color: #fff; padding: 2rem; line-height: 1.6; }
        .box { background: #1a1940; padding: 1.5rem; margin: 1rem 0; border-radius: 8px; border: 1px solid #333; }
        .success { color: #6BCB77; }
        .error { color: #FF6B9D; }
        .warning { color: #FFD93D; }
        h1, h2 { color: #6BCBF7; }
        pre { background: #000; padding: 1rem; border-radius: 4px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #444; padding: 0.8rem; text-align: left; }
        th { background: #1a1940; color: #6BCBF7; }
        .highlight { background: #FFD93D; color: #000; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

<h1>🔍 Registration Page Debug Report</h1>
<p>Generated: <?php echo date('Y-m-d H:i:s'); ?></p>

<?php

// TEST 1: Check if config file exists
echo "<div class='box'>";
echo "<h2>TEST 1: Configuration File</h2>";
if (file_exists(__DIR__ . '/php/config.php')) {
    echo "<p class='success'>✅ php/config.php exists</p>";
} else {
    echo "<p class='error'>❌ php/config.php NOT FOUND</p>";
    echo "<p>Path checked: " . __DIR__ . "/php/config.php</p>";
    exit;
}
echo "</div>";

// TEST 2: Database Connection
echo "<div class='box'>";
echo "<h2>TEST 2: Database Connection</h2>";
try {
    $db = getDB();
    echo "<p class='success'>✅ Database connection successful!</p>";
    
    // Get database name
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>Connected to database: <span class='highlight'>$dbName</span></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection FAILED</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
echo "</div>";

// TEST 3: Check Schools Table
echo "<div class='box'>";
echo "<h2>TEST 3: Schools Table - RAW DATA</h2>";
try {
    // Check if table exists
    $tables = $db->query("SHOW TABLES LIKE 'schools'")->fetchAll();
    if (count($tables) === 0) {
        echo "<p class='error'>❌ 'schools' table does NOT exist!</p>";
    } else {
        echo "<p class='success'>✅ 'schools' table exists</p>";
        
        // Get all columns
        $columns = $db->query("DESCRIBE schools")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>Table Structure:</strong></p>";
        echo "<table><tr><th>Column</th><th>Type</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        }
        echo "</table>";
        
        // Query exactly like register.php does
        echo "<h3>Query Used by register.php:</h3>";
        echo "<pre>SELECT * FROM schools WHERE status = 'active' ORDER BY school_key</pre>";
        
        $stmt = $db->query("SELECT * FROM schools WHERE status = 'active' ORDER BY school_key");
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Results: " . count($schools) . " schools found</strong></p>";
        
        if (count($schools) > 0) {
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($schools[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            
            foreach ($schools as $school) {
                echo "<tr>";
                foreach ($school as $key => $value) {
                    if ($key === 'name') {
                        echo "<td><span class='highlight'>" . htmlspecialchars($value) . "</span></td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
            
            // Show as JSON (what JavaScript will see)
            echo "<h3>JSON Output (what JavaScript receives):</h3>";
            echo "<pre>" . json_encode($schools, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
        } else {
            echo "<p class='error'>❌ No schools found with status='active'</p>";
            
            // Check if there are ANY schools
            $allSchools = $db->query("SELECT * FROM schools")->fetchAll(PDO::FETCH_ASSOC);
            if (count($allSchools) > 0) {
                echo "<p class='warning'>⚠️ Found " . count($allSchools) . " schools but none are 'active'</p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>Status</th></tr>";
                foreach ($allSchools as $s) {
                    echo "<tr><td>{$s['id']}</td><td>{$s['name']}</td><td><strong>{$s['status']}</strong></td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ Schools table is completely EMPTY</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error querying schools table</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
echo "</div>";

// TEST 4: Check Age Groups Table
echo "<div class='box'>";
echo "<h2>TEST 4: Age Groups Table</h2>";
try {
    $stmt = $db->query("SELECT * FROM age_groups ORDER BY min_age");
    $ageGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Found " . count($ageGroups) . " age groups</p>";
    
    if (count($ageGroups) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Icon</th><th>Name</th><th>Age Range</th><th>Level</th></tr>";
        foreach ($ageGroups as $g) {
            echo "<tr>";
            echo "<td>{$g['id']}</td>";
            echo "<td style='font-size:1.5rem;'>{$g['icon']}</td>";
            echo "<td><span class='highlight'>{$g['name']}</span></td>";
            echo "<td>{$g['min_age']}-{$g['max_age']}</td>";
            echo "<td>{$g['level_label']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// TEST 5: Check what register.php will display
echo "<div class='box'>";
echo "<h2>TEST 5: What register.php SHOULD Display</h2>";

if (isset($schools) && count($schools) > 0) {
    echo "<p>When you open register.php, you should see these school names:</p>";
    
    foreach ($schools as $school) {
        echo "<div style='background:#000;padding:1rem;margin:1rem 0;border-radius:8px;'>";
        echo "<p style='font-size:2rem;margin:0;'>{$school['mascot']}</p>";
        echo "<h3 style='margin:0.5rem 0;color:#6BCBF7;'><span class='highlight'>{$school['name']}</span></h3>";
        echo "<p style='color:#888;margin:0;'>{$school['motto']}</p>";
        echo "<p style='font-size:0.85rem;margin-top:0.5rem;'>School Key: {$school['school_key']} | Status: {$school['status']}</p>";
        echo "</div>";
    }
    
    echo "<p class='warning'>⚠️ If register.php shows DIFFERENT names, there's a caching issue or wrong file!</p>";
} else {
    echo "<p class='error'>❌ No schools to display!</p>";
}
echo "</div>";

// TEST 6: File Check
echo "<div class='box'>";
echo "<h2>TEST 6: Registration Files Check</h2>";

$files = [
    'register.php' => __DIR__ . '/register.php',
    'register.html' => __DIR__ . '/register.html',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $modified = date('Y-m-d H:i:s', filemtime($path));
        
        if ($name === 'register.html') {
            echo "<p class='warning'>⚠️ $name EXISTS (size: $size bytes, modified: $modified)</p>";
            echo "<p style='margin-left:2rem;'>→ This OLD file should be DELETED!</p>";
        } else {
            echo "<p class='success'>✅ $name exists (size: $size bytes, modified: $modified)</p>";
        }
    } else {
        if ($name === 'register.html') {
            echo "<p class='success'>✅ $name does NOT exist (good!)</p>";
        } else {
            echo "<p class='error'>❌ $name does NOT exist (bad!)</p>";
        }
    }
}
echo "</div>";

// TEST 7: Admin Changes
echo "<div class='box'>";
echo "<h2>TEST 7: How to Test Admin Changes</h2>";
echo "<ol>";
echo "<li>Go to your admin panel</li>";
echo "<li>Change a school name (e.g., 'BrightStar Academy' → 'Test Name')</li>";
echo "<li>Save the changes</li>";
echo "<li>Come back to this page and REFRESH (Ctrl+F5)</li>";
echo "<li>Check TEST 3 above - it should show 'Test Name'</li>";
echo "<li>Then check register.php - it should also show 'Test Name'</li>";
echo "</ol>";
echo "<p class='warning'>⚠️ If this page shows new name but register.php doesn't:</p>";
echo "<ul>";
echo "<li>You're accessing the wrong file (register.html instead of register.php)</li>";
echo "<li>OR browser cache is preventing update (try Incognito mode)</li>";
echo "<li>OR there are TWO register.php files in different directories</li>";
echo "</ul>";
echo "</div>";

?>

<div class='box'>
<h2>🎯 Next Steps</h2>
<p><strong>1. Check the school names in TEST 3 above</strong></p>
<p style='margin-left:2rem;'>→ These are the ACTUAL names in your database right now</p>

<p><strong>2. Go to register.php and check what it shows</strong></p>
<p style='margin-left:2rem;'>→ <a href="register.php" style="color:#6BCBF7;">Open register.php</a></p>

<p><strong>3. Compare the names</strong></p>
<p style='margin-left:2rem;'>→ If different: You have a problem (see below)</p>
<p style='margin-left:2rem;'>→ If same: Database is correct, just need to update in admin</p>

<p><strong>4. If register.php shows WRONG names:</strong></p>
<ul style='margin-left:2rem;'>
<li>Open browser DevTools (F12)</li>
<li>Go to Console tab</li>
<li>Look for: <code>🎓 Register.php - Dynamic from Database</code></li>
<li>Look for: <code>📊 Schools loaded: X</code></li>
<li>Take a screenshot and send it</li>
</ul>
</div>

<hr>
<p><a href="index.php" style="color:#6BCBF7;">← Back to Home</a> | 
<a href="register.php" style="color:#6BCBF7;">Go to Register →</a> | 
<a href="test-database.php" style="color:#6BCBF7;">Simple Test Page →</a></p>

</body>
</html>