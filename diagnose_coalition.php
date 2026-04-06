<?php
/* ============================================
   COLLATION DIAGNOSTIC TOOL
   Find tables/columns with wrong collation
   ============================================ */

require_once __DIR__ . '/php/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Collation Diagnostic</title>
    <style>
        body { 
            font-family: monospace; 
            background: #0f0e2e; 
            color: #fff; 
            padding: 2rem; 
            line-height: 1.8; 
        }
        .box { 
            background: #1a1940; 
            padding: 1.5rem; 
            margin: 1rem 0; 
            border-radius: 8px; 
            border: 1px solid #333; 
        }
        .success { color: #6BCB77; font-weight: bold; }
        .error { color: #FF6B9D; font-weight: bold; }
        .warning { color: #FFD93D; font-weight: bold; }
        h1, h2 { color: #6BCBF7; }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 1rem 0; 
        }
        th, td { 
            border: 1px solid #444; 
            padding: 0.8rem; 
            text-align: left; 
        }
        th { 
            background: #1a1940; 
            color: #6BCBF7; 
        }
        .highlight { 
            background: #FFD93D; 
            color: #000; 
            padding: 0.2rem 0.5rem; 
            border-radius: 4px; 
        }
        pre { 
            background: #000; 
            padding: 1rem; 
            border-radius: 4px; 
            overflow-x: auto; 
            color: #6BCB77; 
        }
        .copy-btn {
            background: #6366f1;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .copy-btn:hover {
            background: #4f46e5;
        }
    </style>
</head>
<body>

<h1>🔍 Database Collation Diagnostic</h1>
<p>Checking for collation mismatches in eduverse_db...</p>

<?php
try {
    $db = getDB();
    
    // ============ TEST 1: Database Collation ============
    echo "<div class='box'>";
    echo "<h2>TEST 1: Database Default Collation</h2>";
    
    $stmt = $db->query("
        SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME 
        FROM information_schema.SCHEMATA 
        WHERE SCHEMA_NAME = 'eduverse_db'
    ");
    $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th><th>Status</th></tr>";
    echo "<tr>";
    echo "<td>Character Set</td>";
    echo "<td>{$dbInfo['DEFAULT_CHARACTER_SET_NAME']}</td>";
    echo "<td>" . ($dbInfo['DEFAULT_CHARACTER_SET_NAME'] === 'utf8mb4' ? "<span class='success'>✅ GOOD</span>" : "<span class='error'>❌ SHOULD BE utf8mb4</span>") . "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Collation</td>";
    echo "<td>{$dbInfo['DEFAULT_COLLATION_NAME']}</td>";
    echo "<td>" . ($dbInfo['DEFAULT_COLLATION_NAME'] === 'utf8mb4_unicode_ci' ? "<span class='success'>✅ GOOD</span>" : "<span class='warning'>⚠️ SHOULD BE utf8mb4_unicode_ci</span>") . "</td>";
    echo "</tr>";
    echo "</table>";
    
    if ($dbInfo['DEFAULT_COLLATION_NAME'] !== 'utf8mb4_unicode_ci') {
        echo "<p class='warning'>⚠️ Database default collation needs to be changed!</p>";
        echo "<pre>ALTER DATABASE eduverse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</pre>";
    }
    echo "</div>";
    
    // ============ TEST 2: Table Collations ============
    echo "<div class='box'>";
    echo "<h2>TEST 2: Table Collations</h2>";
    
    $stmt = $db->query("
        SELECT TABLE_NAME, TABLE_COLLATION
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = 'eduverse_db'
        ORDER BY TABLE_NAME
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $wrongTables = [];
    
    echo "<table>";
    echo "<tr><th>Table Name</th><th>Collation</th><th>Status</th></tr>";
    
    foreach ($tables as $table) {
        $isCorrect = ($table['TABLE_COLLATION'] === 'utf8mb4_unicode_ci');
        if (!$isCorrect) {
            $wrongTables[] = $table['TABLE_NAME'];
        }
        
        echo "<tr>";
        echo "<td><strong>{$table['TABLE_NAME']}</strong></td>";
        echo "<td>{$table['TABLE_COLLATION']}</td>";
        echo "<td>" . ($isCorrect ? "<span class='success'>✅ CORRECT</span>" : "<span class='error'>❌ WRONG</span>") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (count($wrongTables) > 0) {
        echo "<p class='error'>❌ Found " . count($wrongTables) . " table(s) with wrong collation!</p>";
        echo "<p><strong>Tables that need fixing:</strong></p>";
        echo "<pre>";
        foreach ($wrongTables as $tableName) {
            echo "ALTER TABLE {$tableName} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        }
        echo "</pre>";
        echo "<button class='copy-btn' onclick=\"copyToClipboard('table-fix')\">Copy SQL Commands</button>";
        echo "<textarea id='table-fix' style='display:none;'>";
        foreach ($wrongTables as $tableName) {
            echo "ALTER TABLE {$tableName} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        }
        echo "</textarea>";
    } else {
        echo "<p class='success'>✅ All tables have correct collation!</p>";
    }
    
    echo "</div>";
    
    // ============ TEST 3: Column Collations ============
    echo "<div class='box'>";
    echo "<h2>TEST 3: Column Collations (Text/VARCHAR fields)</h2>";
    
    $stmt = $db->query("
        SELECT TABLE_NAME, COLUMN_NAME, COLLATION_NAME, DATA_TYPE
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = 'eduverse_db' 
          AND COLLATION_NAME IS NOT NULL
        ORDER BY TABLE_NAME, COLUMN_NAME
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $wrongColumns = [];
    
    echo "<table>";
    echo "<tr><th>Table</th><th>Column</th><th>Type</th><th>Collation</th><th>Status</th></tr>";
    
    foreach ($columns as $col) {
        $isCorrect = ($col['COLLATION_NAME'] === 'utf8mb4_unicode_ci');
        if (!$isCorrect) {
            $wrongColumns[] = $col;
        }
        
        echo "<tr>";
        echo "<td>{$col['TABLE_NAME']}</td>";
        echo "<td>{$col['COLUMN_NAME']}</td>";
        echo "<td>{$col['DATA_TYPE']}</td>";
        echo "<td>{$col['COLLATION_NAME']}</td>";
        echo "<td>" . ($isCorrect ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (count($wrongColumns) > 0) {
        echo "<p class='error'>❌ Found " . count($wrongColumns) . " column(s) with wrong collation!</p>";
        echo "<p class='warning'>⚠️ The table conversion commands above should fix these automatically.</p>";
    } else {
        echo "<p class='success'>✅ All columns have correct collation!</p>";
    }
    
    echo "</div>";
    
    // ============ TEST 4: Joins that might fail ============
    echo "<div class='box'>";
    echo "<h2>TEST 4: Common Joins in Student Dashboard</h2>";
    
    echo "<p>Testing joins that are used in student-dashboard.php...</p>";
    
    $testQueries = [
        'student_profiles JOIN users' => "SELECT sp.school_key, u.username FROM student_profiles sp JOIN users u ON sp.user_id = u.id LIMIT 1",
        'student_profiles JOIN schools' => "SELECT sp.school_key, s.school_key FROM student_profiles sp LEFT JOIN schools s ON sp.school_key = s.school_key LIMIT 1",
        'student_profiles JOIN age_groups' => "SELECT sp.age_group_key, ag.group_key FROM student_profiles sp LEFT JOIN age_groups ag ON sp.age_group_key = ag.group_key LIMIT 1",
    ];
    
    echo "<table>";
    echo "<tr><th>Join Type</th><th>Status</th><th>Error (if any)</th></tr>";
    
    foreach ($testQueries as $joinName => $query) {
        try {
            $stmt = $db->query($query);
            $result = $stmt->fetch();
            echo "<tr>";
            echo "<td>{$joinName}</td>";
            echo "<td><span class='success'>✅ WORKS</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td>{$joinName}</td>";
            echo "<td><span class='error'>❌ FAILS</span></td>";
            echo "<td class='error'>{$e->getMessage()}</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "</div>";
    
    // ============ FINAL VERDICT ============
    echo "<div class='box'>";
    echo "<h2>FINAL VERDICT</h2>";
    
    $hasIssues = (count($wrongTables) > 0 || count($wrongColumns) > 0 || $dbInfo['DEFAULT_COLLATION_NAME'] !== 'utf8mb4_unicode_ci');
    
    if ($hasIssues) {
        echo "<h2 class='error'>❌ COLLATION ISSUES FOUND!</h2>";
        echo "<p><strong>Quick Fix:</strong></p>";
        echo "<ol>";
        echo "<li>Download <code>fix-collation.sql</code></li>";
        echo "<li>Open phpMyAdmin</li>";
        echo "<li>Select eduverse_db database</li>";
        echo "<li>Click 'SQL' tab</li>";
        echo "<li>Paste and execute the SQL</li>";
        echo "<li>Refresh this page to verify</li>";
        echo "</ol>";
        
        echo "<p><strong>OR run this in MySQL command line:</strong></p>";
        echo "<pre>mysql -u root -p eduverse_db < fix-collation.sql</pre>";
    } else {
        echo "<h2 class='success'>✅ NO COLLATION ISSUES!</h2>";
        echo "<p>All tables and columns are using utf8mb4_unicode_ci correctly.</p>";
        echo "<p>If you're still seeing the error, it might be from a different cause. Check:</p>";
        echo "<ul>";
        echo "<li>Browser console for JavaScript errors</li>";
        echo "<li>PHP error log for other database errors</li>";
        echo "<li>Make sure all tables exist</li>";
        echo "</ul>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>❌ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<script>
function copyToClipboard(elementId) {
    const el = document.getElementById(elementId);
    el.style.display = 'block';
    el.select();
    document.execCommand('copy');
    el.style.display = 'none';
    alert('✅ SQL commands copied to clipboard!');
}
</script>

<hr>
<p><a href="student-dashboard.php" style="color:#6BCBF7;">← Back to Dashboard</a></p>

</body>
</html>