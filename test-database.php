<?php
/*
==================================================================
 DATABASE CONNECTIVITY TEST
 Tests all database tables and connections
==================================================================
*/

require_once __DIR__ . '/php/config.php';

echo "<h1>🧪 Database Test Suite</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

$results = [];

// Test 1: Database Connection
echo "<div class='test'>";
echo "<h3>Test 1: Database Connection</h3>";
try {
    $db = getDB();
    echo "<span class='pass'>✅ PASS</span> - Connected to database<br>";
    $results[] = true;
} catch (Exception $e) {
    echo "<span class='fail'>❌ FAIL</span> - " . $e->getMessage() . "<br>";
    $results[] = false;
}
echo "</div>";

// Test 2: Required Tables Exist
echo "<div class='test'>";
echo "<h3>Test 2: Required Tables</h3>";
$requiredTables = [
    'users', 'schools', 'student_profiles', 'registrations',
    'hosting_plans', 'school_subscriptions', 'school_registration_requests',
    'payment_history', 'live_classes', 'class_attendance',
    'school_admins', 'platform_news', 'platform_ads', 'settings'
];

$missingTables = [];
foreach ($requiredTables as $table) {
    try {
        $db->query("SELECT 1 FROM {$table} LIMIT 1");
        echo "✅ Table '{$table}' exists<br>";
    } catch (Exception $e) {
        echo "<span class='fail'>❌ Missing table: {$table}</span><br>";
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    echo "<br><span class='pass'>✅ PASS</span> - All tables exist<br>";
    $results[] = true;
} else {
    echo "<br><span class='fail'>❌ FAIL</span> - Missing " . count($missingTables) . " tables<br>";
    $results[] = false;
}
echo "</div>";

// Test 3: Sample Data
echo "<div class='test'>";
echo "<h3>Test 3: Sample Data Verification</h3>";

// Count records
$counts = [
    'Schools' => $db->query("SELECT COUNT(*) FROM schools")->fetchColumn(),
    'Users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'Plans' => $db->query("SELECT COUNT(*) FROM hosting_plans")->fetchColumn(),
    'Settings' => $db->query("SELECT COUNT(*) FROM settings")->fetchColumn()
];

foreach ($counts as $type => $count) {
    echo "{$type}: <strong>{$count}</strong> records<br>";
}

if ($counts['Plans'] > 0) {
    echo "<br><span class='pass'>✅ PASS</span> - Database has sample data<br>";
    $results[] = true;
} else {
    echo "<br><span class='fail'>⚠️ WARNING</span> - No hosting plans found<br>";
    $results[] = false;
}
echo "</div>";

// Test 4: Database Queries
echo "<div class='test'>";
echo "<h3>Test 4: Complex Queries</h3>";

try {
    // Join query
    $stmt = $db->query("
        SELECT s.name, COUNT(sp.id) as student_count
        FROM schools s
        LEFT JOIN student_profiles sp ON s.school_key = sp.school_key
        GROUP BY s.id
    ");
    $schools = $stmt->fetchAll();
    echo "✅ Complex JOIN query working<br>";
    echo "Schools with student counts: " . count($schools) . "<br>";
    
    // Subquery
    $stmt = $db->query("
        SELECT * FROM hosting_plans 
        WHERE id IN (SELECT requested_plan_id FROM school_registration_requests)
    ");
    echo "✅ Subquery working<br>";
    
    echo "<br><span class='pass'>✅ PASS</span> - All queries execute successfully<br>";
    $results[] = true;
} catch (Exception $e) {
    echo "<span class='fail'>❌ FAIL</span> - Query error: " . $e->getMessage() . "<br>";
    $results[] = false;
}
echo "</div>";

// Test 5: Write Operations
echo "<div class='test'>";
echo "<h3>Test 5: Write Operations</h3>";

try {
    $db->beginTransaction();
    
    // Insert test record
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value, category) 
        VALUES ('test_key', 'test_value', 'test')
    ");
    $stmt->execute();
    $testId = $db->lastInsertId();
    echo "✅ INSERT successful (ID: {$testId})<br>";
    
    // Update test record
    $stmt = $db->prepare("UPDATE settings SET setting_value = 'updated_value' WHERE id = ?");
    $stmt->execute([$testId]);
    echo "✅ UPDATE successful<br>";
    
    // Delete test record
    $stmt = $db->prepare("DELETE FROM settings WHERE id = ?");
    $stmt->execute([$testId]);
    echo "✅ DELETE successful<br>";
    
    $db->commit();
    
    echo "<br><span class='pass'>✅ PASS</span> - All write operations successful<br>";
    $results[] = true;
} catch (Exception $e) {
    $db->rollBack();
    echo "<span class='fail'>❌ FAIL</span> - Write error: " . $e->getMessage() . "<br>";
    $results[] = false;
}
echo "</div>";

// Summary
echo "<div class='test' style='background: #e3f2fd;'>";
echo "<h2>📊 Test Summary</h2>";
$passed = count(array_filter($results));
$total = count($results);
$percentage = ($passed / $total) * 100;

echo "<p style='font-size: 18px;'>";
echo "Passed: <strong>{$passed}/{$total}</strong> ({$percentage}%)";
echo "</p>";

if ($percentage == 100) {
    echo "<h3 class='pass'>🎉 ALL TESTS PASSED!</h3>";
    echo "<p>Database is ready for production!</p>";
} else {
    echo "<h3 class='fail'>⚠️ SOME TESTS FAILED</h3>";
    echo "<p>Please fix the issues above before going live.</p>";
}
echo "</div>";