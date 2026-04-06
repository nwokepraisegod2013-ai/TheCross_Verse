<?php
/* ============================================
   TEST PAGE - Check Database Connection
   ============================================ */

require_once __DIR__ . '/php/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Test</title>";
echo "<style>body{font-family:Arial;padding:2rem;background:#0f0e2e;color:#fff;}";
echo "table{border-collapse:collapse;width:100%;margin:1rem 0;}";
echo "th,td{border:1px solid #444;padding:0.8rem;text-align:left;}";
echo "th{background:#1a1940;}.success{color:#6BCB77;}.error{color:#FF6B9D;}</style></head><body>";

echo "<h1>🔍 EduVerse Database Test</h1>";
echo "<p>Testing database connection and current school data...</p><hr>";

try {
    $db = getDB();
    echo "<p class='success'>✅ Database connection successful!</p>";
    
    // Test 1: Get schools
    echo "<h2>📊 Schools Table</h2>";
    $stmt = $db->query("SELECT * FROM schools ORDER BY school_key");
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($schools) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Key</th><th>Name</th><th>Motto</th><th>Mascot</th><th>Status</th></tr>";
        foreach ($schools as $school) {
            echo "<tr>";
            echo "<td>{$school['id']}</td>";
            echo "<td>{$school['school_key']}</td>";
            echo "<td><strong>{$school['name']}</strong></td>";
            echo "<td>{$school['motto']}</td>";
            echo "<td style='font-size:2rem;'>{$school['mascot']}</td>";
            echo "<td>{$school['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='success'>✅ Found " . count($schools) . " schools</p>";
    } else {
        echo "<p class='error'>❌ No schools found in database</p>";
    }
    
    // Test 2: Get age groups
    echo "<h2>📚 Age Groups Table</h2>";
    $stmt = $db->query("SELECT * FROM age_groups ORDER BY min_age");
    $ageGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($ageGroups) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Icon</th><th>Name</th><th>Age Range</th><th>Level</th><th>Description</th></tr>";
        foreach ($ageGroups as $group) {
            echo "<tr>";
            echo "<td>{$group['id']}</td>";
            echo "<td style='font-size:1.5rem;'>{$group['icon']}</td>";
            echo "<td><strong>{$group['name']}</strong></td>";
            echo "<td>{$group['min_age']}-{$group['max_age']}</td>";
            echo "<td>{$group['level_label']}</td>";
            echo "<td>{$group['description']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='success'>✅ Found " . count($ageGroups) . " age groups</p>";
    } else {
        echo "<p class='error'>❌ No age groups found in database</p>";
    }
    
    // Test 3: Check what register.php will see
    echo "<h2>🎯 What Register.php Will Display</h2>";
    echo "<div style='background:#1a1940;padding:1rem;border-radius:8px;'>";
    foreach ($schools as $school) {
        echo "<h3>{$school['mascot']} {$school['name']}</h3>";
        echo "<p style='color:#888;'>{$school['motto']}</p>";
        echo "<p><strong>School Key:</strong> {$school['school_key']}</p>";
        echo "<hr style='border-color:#444;'>";
    }
    echo "</div>";
    
    // Test 4: JSON output (what JavaScript will get)
    echo "<h2>📋 JSON Data (for JavaScript)</h2>";
    echo "<div style='background:#000;padding:1rem;border-radius:8px;overflow:auto;'>";
    echo "<pre style='color:#6BCB77;'>const schoolsData = " . json_encode($schools, JSON_PRETTY_PRINT) . ";</pre>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Check your php/config.php file and database credentials.</p>";
}

echo "<hr><p><a href='register.php' style='color:#6BCBF7;'>→ Go to Register Page</a> | ";
echo "<a href='index.php' style='color:#6BCBF7;'>→ Go to Home Page</a></p>";
echo "</body></html>";
?>