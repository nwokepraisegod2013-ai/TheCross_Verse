<?php
/* ============================================
   KEY FLOW VERIFICATION SCRIPT
   Check if age_group and school keys match between frontend and backend
   ============================================ */

require_once __DIR__ . '/php/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Key Flow Verification</title>
    <style>
        body { font-family: monospace; background: #0f0e2e; color: #fff; padding: 2rem; line-height: 1.8; }
        .box { background: #1a1940; padding: 1.5rem; margin: 1rem 0; border-radius: 8px; border: 1px solid #333; }
        .success { color: #6BCB77; font-weight: bold; }
        .error { color: #FF6B9D; font-weight: bold; }
        .warning { color: #FFD93D; font-weight: bold; }
        h1, h2 { color: #6BCBF7; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #444; padding: 0.8rem; text-align: left; }
        th { background: #1a1940; color: #6BCBF7; }
        .highlight { background: #FFD93D; color: #000; padding: 0.2rem 0.5rem; border-radius: 4px; }
        .code { background: #000; padding: 0.3rem 0.6rem; border-radius: 4px; font-family: monospace; color: #6BCB77; }
        pre { background: #000; padding: 1rem; border-radius: 4px; overflow-x: auto; color: #6BCB77; }
    </style>
</head>
<body>

<h1>🔍 Key Flow Verification</h1>
<p>Checking if school_key and group_key are correctly flowing from database to frontend to backend...</p>

<?php
try {
    $db = getDB();
    
    // ============ TEST 1: Check Schools Table Structure ============
    echo "<div class='box'>";
    echo "<h2>TEST 1: Schools Table Structure</h2>";
    
    try {
        $columns = $db->query("DESCRIBE schools")->fetchAll(PDO::FETCH_ASSOC);
        
        $hasSchoolKey = false;
        echo "<table><tr><th>Column Name</th><th>Type</th><th>Status</th></tr>";
        foreach ($columns as $col) {
            $status = '';
            if ($col['Field'] === 'school_key') {
                $hasSchoolKey = true;
                $status = "<span class='success'>✅ REQUIRED</span>";
            }
            echo "<tr><td><strong>{$col['Field']}</strong></td><td>{$col['Type']}</td><td>$status</td></tr>";
        }
        echo "</table>";
        
        if ($hasSchoolKey) {
            echo "<p class='success'>✅ schools.school_key column EXISTS</p>";
        } else {
            echo "<p class='error'>❌ schools.school_key column MISSING - ADD IT!</p>";
            echo "<pre>ALTER TABLE schools ADD COLUMN school_key VARCHAR(50) NOT NULL;</pre>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    // ============ TEST 2: Check Age Groups Table Structure ============
    echo "<div class='box'>";
    echo "<h2>TEST 2: Age Groups Table Structure</h2>";
    
    try {
        $columns = $db->query("DESCRIBE age_groups")->fetchAll(PDO::FETCH_ASSOC);
        
        $hasGroupKey = false;
        echo "<table><tr><th>Column Name</th><th>Type</th><th>Status</th></tr>";
        foreach ($columns as $col) {
            $status = '';
            if ($col['Field'] === 'group_key') {
                $hasGroupKey = true;
                $status = "<span class='success'>✅ REQUIRED</span>";
            }
            echo "<tr><td><strong>{$col['Field']}</strong></td><td>{$col['Type']}</td><td>$status</td></tr>";
        }
        echo "</table>";
        
        if ($hasGroupKey) {
            echo "<p class='success'>✅ age_groups.group_key column EXISTS</p>";
        } else {
            echo "<p class='error'>❌ age_groups.group_key column MISSING - ADD IT!</p>";
            echo "<pre>ALTER TABLE age_groups ADD COLUMN group_key VARCHAR(50) NOT NULL;</pre>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    // ============ TEST 3: Check Actual School Data ============
    echo "<div class='box'>";
    echo "<h2>TEST 3: Schools Data - school_key Values</h2>";
    
    try {
        $schools = $db->query("SELECT * FROM schools ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($schools) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>school_key</th><th>Status</th></tr>";
            
            foreach ($schools as $school) {
                $schoolKey = $school['school_key'] ?? '<span class="error">MISSING</span>';
                $keyClass = empty($school['school_key']) ? 'error' : 'success';
                
                echo "<tr>";
                echo "<td>{$school['id']}</td>";
                echo "<td>{$school['name']}</td>";
                echo "<td class='$keyClass'><strong>" . htmlspecialchars($schoolKey) . "</strong></td>";
                echo "<td>{$school['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check if all schools have keys
            $missingKeys = 0;
            foreach ($schools as $school) {
                if (empty($school['school_key'])) {
                    $missingKeys++;
                }
            }
            
            if ($missingKeys === 0) {
                echo "<p class='success'>✅ All schools have school_key values</p>";
            } else {
                echo "<p class='error'>❌ $missingKeys schools missing school_key - UPDATE THEM!</p>";
                echo "<pre>";
                echo "UPDATE schools SET school_key = 'brightstar' WHERE id = 1;\n";
                echo "UPDATE schools SET school_key = 'moonrise' WHERE id = 2;";
                echo "</pre>";
            }
            
        } else {
            echo "<p class='warning'>⚠️ No schools found in database</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    // ============ TEST 4: Check Actual Age Group Data ============
    echo "<div class='box'>";
    echo "<h2>TEST 4: Age Groups Data - group_key Values</h2>";
    
    try {
        $ageGroups = $db->query("SELECT * FROM age_groups ORDER BY min_age")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($ageGroups) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Age Range</th><th>group_key</th></tr>";
            
            foreach ($ageGroups as $group) {
                $groupKey = $group['group_key'] ?? '<span class="error">MISSING</span>';
                $keyClass = empty($group['group_key']) ? 'error' : 'success';
                
                echo "<tr>";
                echo "<td>{$group['id']}</td>";
                echo "<td>{$group['name']}</td>";
                echo "<td>{$group['min_age']}-{$group['max_age']}</td>";
                echo "<td class='$keyClass'><strong>" . htmlspecialchars($groupKey) . "</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check if all age groups have keys
            $missingKeys = 0;
            foreach ($ageGroups as $group) {
                if (empty($group['group_key'])) {
                    $missingKeys++;
                }
            }
            
            if ($missingKeys === 0) {
                echo "<p class='success'>✅ All age groups have group_key values</p>";
            } else {
                echo "<p class='error'>❌ $missingKeys age groups missing group_key - UPDATE THEM!</p>";
                echo "<p>Expected values: tiny, junior, discover, pioneer, champion</p>";
            }
            
        } else {
            echo "<p class='warning'>⚠️ No age groups found in database</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    // ============ TEST 5: Data Flow Simulation ============
    echo "<div class='box'>";
    echo "<h2>TEST 5: Data Flow Simulation</h2>";
    
    echo "<h3>Step 1: Database → PHP</h3>";
    try {
        $schools = $db->query("SELECT id, name, school_key FROM schools ORDER BY school_key")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>\$schools = " . json_encode($schools, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";</pre>";
        
        if (count($schools) > 0 && isset($schools[0]['school_key'])) {
            echo "<p class='success'>✅ PHP can read school_key from database</p>";
        } else {
            echo "<p class='error'>❌ school_key not available in PHP</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h3>Step 2: PHP → JavaScript</h3>";
    echo "<p>In register.php, this data becomes:</p>";
    echo "<pre>const schoolsData = " . json_encode($schools ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ";</pre>";
    
    if (isset($schools[0]['school_key'])) {
        echo "<p class='success'>✅ JavaScript receives school_key</p>";
    } else {
        echo "<p class='error'>❌ JavaScript won't have school_key</p>";
    }
    
    echo "<h3>Step 3: JavaScript → Backend (Form Submission)</h3>";
    echo "<p>When user submits form, JavaScript should send:</p>";
    echo "<pre>";
    echo "{\n";
    echo "  school: selectedSchool.school_key,  // e.g., 'brightstar'\n";
    echo "  ageGroup: selectedAgeGroup.group_key,  // e.g., 'discover'\n";
    echo "  ...\n";
    echo "}";
    echo "</pre>";
    
    if (isset($schools[0]['school_key'])) {
        echo "<p class='success'>✅ Form will send: <span class='code'>school: '{$schools[0]['school_key']}'</span></p>";
    } else {
        echo "<p class='error'>❌ Form will send: <span class='code'>school: undefined</span></p>";
    }
    
    echo "<h3>Step 4: Backend Receives</h3>";
    echo "<p>php/register.php expects:</p>";
    echo "<pre>";
    echo "\$body['school'] = 'brightstar';  // school_key value\n";
    echo "\$body['ageGroup'] = 'discover';  // group_key value";
    echo "</pre>";
    
    echo "</div>";
    
    // ============ TEST 6: Final Verdict ============
    echo "<div class='box'>";
    echo "<h2>TEST 6: Final Verdict</h2>";
    
    $hasSchoolKeyCol = false;
    $hasGroupKeyCol = false;
    $schoolsHaveKeys = false;
    $ageGroupsHaveKeys = false;
    
    try {
        // Check columns
        $cols = $db->query("DESCRIBE schools")->fetchAll(PDO::FETCH_COLUMN);
        $hasSchoolKeyCol = in_array('school_key', $cols);
        
        $cols = $db->query("DESCRIBE age_groups")->fetchAll(PDO::FETCH_COLUMN);
        $hasGroupKeyCol = in_array('group_key', $cols);
        
        // Check data
        $schoolCheck = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN school_key IS NOT NULL AND school_key != '' THEN 1 ELSE 0 END) as with_keys FROM schools")->fetch();
        $schoolsHaveKeys = ($schoolCheck['total'] > 0 && $schoolCheck['total'] == $schoolCheck['with_keys']);
        
        $ageCheck = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN group_key IS NOT NULL AND group_key != '' THEN 1 ELSE 0 END) as with_keys FROM age_groups")->fetch();
        $ageGroupsHaveKeys = ($ageCheck['total'] > 0 && $ageCheck['total'] == $ageCheck['with_keys']);
        
    } catch (Exception $e) {
        // Ignore errors for verdict
    }
    
    echo "<table>";
    echo "<tr><th>Check</th><th>Status</th><th>Result</th></tr>";
    echo "<tr><td>schools table has school_key column</td><td>" . ($hasSchoolKeyCol ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</td><td>" . ($hasSchoolKeyCol ? "Good" : "ADD COLUMN") . "</td></tr>";
    echo "<tr><td>age_groups table has group_key column</td><td>" . ($hasGroupKeyCol ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</td><td>" . ($hasGroupKeyCol ? "Good" : "ADD COLUMN") . "</td></tr>";
    echo "<tr><td>All schools have school_key values</td><td>" . ($schoolsHaveKeys ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</td><td>" . ($schoolsHaveKeys ? "Good" : "UPDATE DATA") . "</td></tr>";
    echo "<tr><td>All age groups have group_key values</td><td>" . ($ageGroupsHaveKeys ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</td><td>" . ($ageGroupsHaveKeys ? "Good" : "UPDATE DATA") . "</td></tr>";
    echo "</table>";
    
    if ($hasSchoolKeyCol && $hasGroupKeyCol && $schoolsHaveKeys && $ageGroupsHaveKeys) {
        echo "<h2 class='success'>✅ PERFECT! Key flow is working correctly!</h2>";
        echo "<p>Your registration system will work properly:</p>";
        echo "<ul>";
        echo "<li>✅ Database has school_key and group_key columns</li>";
        echo "<li>✅ All records have key values</li>";
        echo "<li>✅ Frontend will receive correct keys</li>";
        echo "<li>✅ Backend will validate correctly</li>";
        echo "</ul>";
    } else {
        echo "<h2 class='error'>❌ ISSUES FOUND - Keys not flowing correctly!</h2>";
        echo "<p><strong>Problems:</strong></p>";
        echo "<ul>";
        if (!$hasSchoolKeyCol) echo "<li class='error'>❌ Missing school_key column in schools table</li>";
        if (!$hasGroupKeyCol) echo "<li class='error'>❌ Missing group_key column in age_groups table</li>";
        if (!$schoolsHaveKeys) echo "<li class='error'>❌ Some schools missing school_key values</li>";
        if (!$ageGroupsHaveKeys) echo "<li class='error'>❌ Some age groups missing group_key values</li>";
        echo "</ul>";
        
        echo "<p><strong>What will happen:</strong></p>";
        echo "<ul>";
        echo "<li class='warning'>⚠️ Form submission will send: <span class='code'>school: undefined</span> or <span class='code'>ageGroup: undefined</span></li>";
        echo "<li class='warning'>⚠️ Backend will reject with: <span class='code'>Missing required fields: school, ageGroup</span></li>";
        echo "<li class='warning'>⚠️ Registration will FAIL</li>";
        echo "</ul>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>❌ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<hr>
<p><a href="register.php" style="color:#6BCBF7;">← Back to Register</a> | 
<a href="debug-register.php" style="color:#6BCBF7;">Debug Page →</a></p>

</body>
</html>