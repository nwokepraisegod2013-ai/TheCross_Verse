<?php
/*
==================================================================
 DATABASE CONNECTION TROUBLESHOOTER
 Run this to diagnose and fix database connection issues
==================================================================
*/

echo "<h1>🔧 Database Connection Troubleshooter</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0; }
    .section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; }
    pre { background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .fix-button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
</style>";

// Test 1: Check if config.php exists
echo "<div class='section'>";
echo "<h2>Test 1: Check config.php File</h2>";

$configPath = __DIR__ . '/php/config.php';

if (file_exists($configPath)) {
    echo "<span class='success'>✅ config.php exists</span><br>";
    echo "Location: <code>{$configPath}</code><br>";
} else {
    echo "<span class='error'>❌ config.php NOT FOUND</span><br>";
    echo "Expected location: <code>{$configPath}</code><br>";
    echo "<div class='info'>";
    echo "<strong>FIX:</strong> Create config.php file or check if it's in the correct location.";
    echo "</div>";
}

echo "</div>";

// Test 2: Check MySQL Connection
echo "<div class='section'>";
echo "<h2>Test 2: MySQL Connection Test</h2>";

$mysqlHost = 'localhost';
$mysqlUser = 'root';
$mysqlPass = '';

try {
    $testConn = new mysqli($mysqlHost, $mysqlUser, $mysqlPass);
    
    if ($testConn->connect_error) {
        throw new Exception($testConn->connect_error);
    }
    
    echo "<span class='success'>✅ MySQL connection successful</span><br>";
    echo "Host: {$mysqlHost}<br>";
    echo "User: {$mysqlUser}<br>";
    echo "MySQL Version: " . $testConn->server_info . "<br>";
    
    $testConn->close();
    
} catch (Exception $e) {
    echo "<span class='error'>❌ MySQL connection failed</span><br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    
    echo "<div class='info'>";
    echo "<h3>Possible Fixes:</h3>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Check if MySQL is running (should show 'Running' with green highlight)</li>";
    echo "<li>If not running, click 'Start' button for MySQL</li>";
    echo "<li>If it won't start, check port 3306 is not used by another program</li>";
    echo "</ol>";
    echo "</div>";
}

echo "</div>";

// Test 3: Check if database exists
echo "<div class='section'>";
echo "<h2>Test 3: Check Database</h2>";

try {
    $conn = new mysqli($mysqlHost, $mysqlUser, $mysqlPass);
    
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    
    $dbName = 'eduverse_db';
    $result = $conn->query("SHOW DATABASES LIKE '{$dbName}'");
    
    if ($result->num_rows > 0) {
        echo "<span class='success'>✅ Database '{$dbName}' exists</span><br>";
        
        // Check tables
        $conn->select_db($dbName);
        $tablesResult = $conn->query("SHOW TABLES");
        $tableCount = $tablesResult->num_rows;
        
        echo "Tables found: <strong>{$tableCount}</strong><br>";
        
        if ($tableCount < 10) {
            echo "<span class='warning'>⚠️ Low table count. Database may not be fully imported.</span><br>";
        }
        
    } else {
        echo "<span class='error'>❌ Database '{$dbName}' NOT FOUND</span><br><br>";
        
        echo "<div class='info'>";
        echo "<h3>FIX: Create Database</h3>";
        echo "<p>Option 1 - Via phpMyAdmin:</p>";
        echo "<ol>";
        echo "<li>Open: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
        echo "<li>Click 'New' in left sidebar</li>";
        echo "<li>Database name: <strong>eduverse_db</strong></li>";
        echo "<li>Collation: <strong>utf8mb4_unicode_ci</strong></li>";
        echo "<li>Click 'Create'</li>";
        echo "<li>Click 'Import' tab</li>";
        echo "<li>Choose file: DATABASE_SCHEMA_COMPLETE_FIXED.sql</li>";
        echo "<li>Click 'Go'</li>";
        echo "</ol>";
        
        echo "<p>Option 2 - Create Now (Automatic):</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='create_db' class='fix-button'>Create Database Now</button>";
        echo "</form>";
        echo "</div>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error checking database</span><br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "</div>";

// Handle database creation
if (isset($_POST['create_db'])) {
    echo "<div class='section'>";
    echo "<h3>Creating Database...</h3>";
    
    try {
        $conn = new mysqli($mysqlHost, $mysqlUser, $mysqlPass);
        
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        
        $sql = "CREATE DATABASE IF NOT EXISTS eduverse_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        if ($conn->query($sql) === TRUE) {
            echo "<span class='success'>✅ Database created successfully!</span><br>";
            echo "<p><strong>Next Step:</strong> Import DATABASE_SCHEMA_COMPLETE_FIXED.sql via phpMyAdmin</p>";
        } else {
            throw new Exception($conn->error);
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        echo "<span class='error'>❌ Failed to create database</span><br>";
        echo "Error: " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
}

// Test 4: Check config.php content
echo "<div class='section'>";
echo "<h2>Test 4: Config.php Settings</h2>";

if (file_exists($configPath)) {
    $configContent = file_get_contents($configPath);
    
    // Check for database credentials
    $checks = [
        'DB_HOST' => "define('DB_HOST'",
        'DB_NAME' => "define('DB_NAME'",
        'DB_USER' => "define('DB_USER'",
        'DB_PASS' => "define('DB_PASS'"
    ];
    
    foreach ($checks as $name => $search) {
        if (strpos($configContent, $search) !== false) {
            echo "✅ {$name} defined<br>";
        } else {
            echo "<span class='error'>❌ {$name} NOT defined</span><br>";
        }
    }
    
    echo "<br><h3>Current Configuration:</h3>";
    echo "<pre>";
    
    // Extract and display current settings (safely)
    preg_match("/define\('DB_HOST',\s*'([^']*)'/", $configContent, $hostMatch);
    preg_match("/define\('DB_NAME',\s*'([^']*)'/", $configContent, $nameMatch);
    preg_match("/define\('DB_USER',\s*'([^']*)'/", $configContent, $userMatch);
    
    echo "DB_HOST: " . ($hostMatch[1] ?? 'NOT SET') . "\n";
    echo "DB_NAME: " . ($nameMatch[1] ?? 'NOT SET') . "\n";
    echo "DB_USER: " . ($userMatch[1] ?? 'NOT SET') . "\n";
    echo "DB_PASS: [hidden for security]\n";
    echo "</pre>";
    
    // Check if values are correct for XAMPP
    $needsFix = false;
    if (($hostMatch[1] ?? '') !== 'localhost') $needsFix = true;
    if (($nameMatch[1] ?? '') !== 'eduverse_db') $needsFix = true;
    if (($userMatch[1] ?? '') !== 'root') $needsFix = true;
    
    if ($needsFix) {
        echo "<div class='info'>";
        echo "<h3>⚠️ Configuration needs update for XAMPP</h3>";
        echo "<p>XAMPP default settings:</p>";
        echo "<pre>";
        echo "define('DB_HOST', 'localhost');\n";
        echo "define('DB_NAME', 'eduverse_db');\n";
        echo "define('DB_USER', 'root');\n";
        echo "define('DB_PASS', '');  // Empty password for XAMPP\n";
        echo "</pre>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='fix_config' class='fix-button'>Fix Config Now</button>";
        echo "</form>";
        echo "</div>";
    }
}

echo "</div>";

// Handle config fix
if (isset($_POST['fix_config'])) {
    echo "<div class='section'>";
    echo "<h3>Fixing config.php...</h3>";
    
    $newConfig = file_get_contents(__DIR__ . '/config.php');
    
    if (file_put_contents($configPath, $newConfig)) {
        echo "<span class='success'>✅ config.php updated successfully!</span><br>";
        echo "<p>Updated settings:</p>";
        echo "<pre>";
        echo "DB_HOST: localhost\n";
        echo "DB_NAME: eduverse_db\n";
        echo "DB_USER: root\n";
        echo "DB_PASS: (empty)\n";
        echo "</pre>";
    } else {
        echo "<span class='error'>❌ Failed to update config.php</span><br>";
        echo "Please update manually.";
    }
    
    echo "</div>";
}

// Test 5: Test PDO Connection
echo "<div class='section'>";
echo "<h2>Test 5: PDO Connection Test</h2>";

if (file_exists($configPath)) {
    require_once $configPath;
    
    try {
        $db = getDB();
        echo "<span class='success'>✅ PDO connection successful!</span><br>";
        
        // Test a simple query
        $stmt = $db->query("SELECT 1");
        echo "✅ Can execute queries<br>";
        
        // Check tables
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<br><strong>Tables in database:</strong> " . count($tables) . "<br>";
        
        if (count($tables) > 0) {
            echo "<details>";
            echo "<summary>Show table list</summary>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>{$table}</li>";
            }
            echo "</ul>";
            echo "</details>";
        } else {
            echo "<span class='warning'>⚠️ No tables found. Please import DATABASE_SCHEMA_COMPLETE_FIXED.sql</span>";
        }
        
    } catch (Exception $e) {
        echo "<span class='error'>❌ PDO connection failed</span><br>";
        echo "Error: " . $e->getMessage() . "<br>";
    }
}

echo "</div>";

// Summary
echo "<div class='section' style='background: #e8f5e9;'>";
echo "<h2>📊 Summary & Next Steps</h2>";

$issues = [];
$fixes = [];

// Check all conditions
if (!file_exists($configPath)) {
    $issues[] = "config.php file missing";
    $fixes[] = "Copy config.php to C:\\xampp\\htdocs\\school-portal\\php\\";
}

try {
    $testConn = new mysqli($mysqlHost, $mysqlUser, $mysqlPass);
    if ($testConn->connect_error) {
        $issues[] = "MySQL not running";
        $fixes[] = "Start MySQL in XAMPP Control Panel";
    } else {
        $result = $testConn->query("SHOW DATABASES LIKE 'eduverse_db'");
        if ($result->num_rows === 0) {
            $issues[] = "Database doesn't exist";
            $fixes[] = "Create database via phpMyAdmin or click 'Create Database Now' above";
        } else {
            $testConn->select_db('eduverse_db');
            $tablesResult = $testConn->query("SHOW TABLES");
            if ($tablesResult->num_rows < 10) {
                $issues[] = "Database not fully imported";
                $fixes[] = "Import DATABASE_SCHEMA_COMPLETE_FIXED.sql via phpMyAdmin";
            }
        }
    }
    $testConn->close();
} catch (Exception $e) {
    $issues[] = "Cannot connect to MySQL";
    $fixes[] = "Check if MySQL is running in XAMPP";
}

if (count($issues) === 0) {
    echo "<h3 class='success'>🎉 Everything is working!</h3>";
    echo "<p>Your database connection is configured correctly.</p>";
    echo "<p><a href='http://eduverse.local'>Go to Platform</a></p>";
} else {
    echo "<h3 class='error'>❌ Issues Found: " . count($issues) . "</h3>";
    echo "<ol>";
    foreach ($issues as $i => $issue) {
        echo "<li><strong>{$issue}</strong><br>Fix: {$fixes[$i]}</li>";
    }
    echo "</ol>";
}

echo "</div>";