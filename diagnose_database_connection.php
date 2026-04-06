<?php
/* ============================================
   DATABASE CONNECTION DIAGNOSTIC
   Check if database is properly configured
   ============================================ */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Connection Diagnostic</title>
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
        pre { 
            background: #000; 
            padding: 1rem; 
            border-radius: 4px; 
            overflow-x: auto; 
            color: #6BCB77; 
        }
        .code {
            background: #000;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            color: #6BCB77;
            font-family: monospace;
        }
    </style>
</head>
<body>

<h1>🔍 Database Connection Diagnostic</h1>
<p>Checking your database configuration and connection...</p>

<?php
// ============ TEST 1: Check if config.php exists ============
echo "<div class='box'>";
echo "<h2>TEST 1: Configuration File</h2>";

$configPath = __DIR__ . '/php/config.php';
if (file_exists($configPath)) {
    echo "<p class='success'>✅ config.php file exists</p>";
    echo "<p>Location: <code class='code'>$configPath</code></p>";
    
    // Show config file contents (with password hidden)
    $configContents = file_get_contents($configPath);
    $configContents = preg_replace("/('password'\s*=>\s*')[^']*(')/", "$1***HIDDEN***$2", $configContents);
    echo "<pre>" . htmlspecialchars($configContents) . "</pre>";
} else {
    echo "<p class='error'>❌ config.php file NOT found!</p>";
    echo "<p class='warning'>Expected location: <code class='code'>$configPath</code></p>";
    echo "<p>You need to create this file!</p>";
}
echo "</div>";

// ============ TEST 2: Try to load config ============
echo "<div class='box'>";
echo "<h2>TEST 2: Load Configuration</h2>";

try {
    if (file_exists($configPath)) {
        require_once $configPath;
        echo "<p class='success'>✅ config.php loaded successfully</p>";
        
        // Check if getDB function exists
        if (function_exists('getDB')) {
            echo "<p class='success'>✅ getDB() function exists</p>";
        } else {
            echo "<p class='error'>❌ getDB() function NOT found!</p>";
        }
    } else {
        echo "<p class='error'>❌ Cannot load config.php - file missing</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error loading config: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ============ TEST 3: Database Connection ============
echo "<div class='box'>";
echo "<h2>TEST 3: Database Connection</h2>";

try {
    if (function_exists('getDB')) {
        $db = getDB();
        echo "<p class='success'>✅ Database connection successful!</p>";
        
        // Get connection info
        $stmt = $db->query("SELECT DATABASE() as current_db, USER() as current_user");
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Property</th><th>Value</th></tr>";
        echo "<tr><td>Connected Database</td><td><strong>" . htmlspecialchars($info['current_db']) . "</strong></td></tr>";
        echo "<tr><td>Connected User</td><td>" . htmlspecialchars($info['current_user']) . "</td></tr>";
        echo "</table>";
        
    } else {
        echo "<p class='error'>❌ Cannot test connection - getDB() function missing</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection FAILED!</p>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Parse error for common issues
    $errorMsg = $e->getMessage();
    
    if (strpos($errorMsg, 'Access denied') !== false) {
        echo "<div style='margin-top:1rem; padding:1rem; background:rgba(255,107,157,0.1); border-left:4px solid #FF6B9D;'>";
        echo "<h3 class='error'>❌ Access Denied</h3>";
        echo "<p><strong>Problem:</strong> Username or password is incorrect</p>";
        echo "<p><strong>Solution:</strong> Check your config.php settings:</p>";
        echo "<pre>";
        echo "return [\n";
        echo "    'host' => 'localhost',\n";
        echo "    'dbname' => 'eduverse_db',\n";
        echo "    'username' => 'root',    // ← Check this\n";
        echo "    'password' => '',        // ← Check this (usually empty for XAMPP)\n";
        echo "];\n";
        echo "</pre>";
        echo "</div>";
        
    } elseif (strpos($errorMsg, 'Unknown database') !== false) {
        echo "<div style='margin-top:1rem; padding:1rem; background:rgba(255,211,61,0.1); border-left:4px solid #FFD93D;'>";
        echo "<h3 class='warning'>⚠️ Database Not Found</h3>";
        echo "<p><strong>Problem:</strong> Database 'eduverse_db' doesn't exist</p>";
        echo "<p><strong>Solution:</strong> Run COMPLETE-DATABASE-SETUP.sql to create it</p>";
        echo "</div>";
        
    } elseif (strpos($errorMsg, 'Connection refused') !== false) {
        echo "<div style='margin-top:1rem; padding:1rem; background:rgba(255,107,157,0.1); border-left:4px solid #FF6B9D;'>";
        echo "<h3 class='error'>❌ MySQL Not Running</h3>";
        echo "<p><strong>Problem:</strong> MySQL server is not running</p>";
        echo "<p><strong>Solution:</strong> Start MySQL in XAMPP Control Panel</p>";
        echo "</div>";
    }
}
echo "</div>";

// ============ TEST 4: Check Database Exists ============
if (function_exists('getDB')) {
    echo "<div class='box'>";
    echo "<h2>TEST 4: Check eduverse_db Database</h2>";
    
    try {
        // Connect to MySQL without specifying database
        $tempDb = new PDO('mysql:host=localhost', 'root', '');
        $tempDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if eduverse_db exists
        $stmt = $tempDb->query("SHOW DATABASES LIKE 'eduverse_db'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "<p class='success'>✅ eduverse_db database exists</p>";
            
            // Get database info
            $stmt = $tempDb->query("
                SELECT 
                    DEFAULT_CHARACTER_SET_NAME as charset,
                    DEFAULT_COLLATION_NAME as collation
                FROM information_schema.SCHEMATA 
                WHERE SCHEMA_NAME = 'eduverse_db'
            ");
            $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Property</th><th>Value</th><th>Status</th></tr>";
            echo "<tr>";
            echo "<td>Character Set</td>";
            echo "<td>{$dbInfo['charset']}</td>";
            echo "<td>" . ($dbInfo['charset'] === 'utf8mb4' ? "<span class='success'>✅ GOOD</span>" : "<span class='warning'>⚠️ Should be utf8mb4</span>") . "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>Collation</td>";
            echo "<td>{$dbInfo['collation']}</td>";
            echo "<td>" . ($dbInfo['collation'] === 'utf8mb4_unicode_ci' ? "<span class='success'>✅ GOOD</span>" : "<span class='warning'>⚠️ Should be utf8mb4_unicode_ci</span>") . "</td>";
            echo "</tr>";
            echo "</table>";
            
        } else {
            echo "<p class='error'>❌ eduverse_db database does NOT exist</p>";
            echo "<p class='warning'>⚠️ You need to create it first!</p>";
            echo "<p><strong>Solution:</strong> Run COMPLETE-DATABASE-SETUP.sql in phpMyAdmin</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Cannot check database: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
}

// ============ TEST 5: Check Tables ============
if (function_exists('getDB')) {
    echo "<div class='box'>";
    echo "<h2>TEST 5: Check Tables in eduverse_db</h2>";
    
    try {
        $db = getDB();
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p class='success'>✅ Found " . count($tables) . " tables</p>";
            
            echo "<table>";
            echo "<tr><th>#</th><th>Table Name</th></tr>";
            foreach ($tables as $index => $table) {
                echo "<tr><td>" . ($index + 1) . "</td><td>$table</td></tr>";
            }
            echo "</table>";
            
            // Check for essential tables
            $requiredTables = ['users', 'schools', 'age_groups', 'student_profiles', 'academic_sessions'];
            $missingTables = [];
            
            foreach ($requiredTables as $requiredTable) {
                if (!in_array($requiredTable, $tables)) {
                    $missingTables[] = $requiredTable;
                }
            }
            
            if (count($missingTables) > 0) {
                echo "<p class='warning'>⚠️ Missing essential tables: " . implode(', ', $missingTables) . "</p>";
            } else {
                echo "<p class='success'>✅ All essential tables present</p>";
            }
            
        } else {
            echo "<p class='warning'>⚠️ No tables found in database</p>";
            echo "<p>Database exists but is empty. Run COMPLETE-DATABASE-SETUP.sql to create tables.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Cannot check tables: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
}

// ============ TEST 6: Check Sample Data ============
if (function_exists('getDB')) {
    echo "<div class='box'>";
    echo "<h2>TEST 6: Check Sample Data</h2>";
    
    try {
        $db = getDB();
        
        // Check users
        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>Users: <strong>$userCount</strong> " . ($userCount > 0 ? "<span class='success'>✅</span>" : "<span class='warning'>⚠️ Empty</span>") . "</p>";
        
        // Check schools
        $schoolCount = $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
        echo "<p>Schools: <strong>$schoolCount</strong> " . ($schoolCount > 0 ? "<span class='success'>✅</span>" : "<span class='warning'>⚠️ Empty</span>") . "</p>";
        
        // Check students
        $studentCount = $db->query("SELECT COUNT(*) FROM student_profiles")->fetchColumn();
        echo "<p>Students: <strong>$studentCount</strong> " . ($studentCount > 0 ? "<span class='success'>✅</span>" : "<span class='warning'>⚠️ Empty</span>") . "</p>";
        
        if ($userCount == 0 && $schoolCount == 0) {
            echo "<p class='warning'>⚠️ No data found. Run SAMPLE-DATA.sql to populate with test data.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠️ Cannot check data: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Some tables might be missing. Run COMPLETE-DATABASE-SETUP.sql</p>";
    }
    echo "</div>";
}

// ============ FINAL VERDICT ============
echo "<div class='box'>";
echo "<h2>FINAL VERDICT</h2>";

$hasConfig = file_exists($configPath);
$canConnect = false;
$dbExists = false;
$hasTables = false;

if ($hasConfig && function_exists('getDB')) {
    try {
        $db = getDB();
        $canConnect = true;
        
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $hasTables = count($tables) > 0;
        $dbExists = true;
        
    } catch (PDOException $e) {
        $canConnect = false;
    }
}

echo "<table>";
echo "<tr><th>Check</th><th>Status</th><th>Action Needed</th></tr>";

echo "<tr>";
echo "<td>config.php exists</td>";
echo "<td>" . ($hasConfig ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</td>";
echo "<td>" . ($hasConfig ? "Good" : "CREATE config.php file") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>Database connection works</td>";
echo "<td>" . ($canConnect ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</td>";
echo "<td>" . ($canConnect ? "Good" : "Fix config.php settings") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>eduverse_db exists</td>";
echo "<td>" . ($dbExists ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</td>";
echo "<td>" . ($dbExists ? "Good" : "Run COMPLETE-DATABASE-SETUP.sql") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>Tables created</td>";
echo "<td>" . ($hasTables ? "<span class='success'>✅ YES</span>" : "<span class='error'>❌ NO</span>") . "</td>";
echo "<td>" . ($hasTables ? "Good" : "Run COMPLETE-DATABASE-SETUP.sql") . "</td>";
echo "</tr>";

echo "</table>";

if ($hasConfig && $canConnect && $dbExists && $hasTables) {
    echo "<h2 class='success'>✅ EVERYTHING IS WORKING!</h2>";
    echo "<p>Your database is properly configured and ready to use.</p>";
} else {
    echo "<h2 class='error'>❌ ISSUES FOUND</h2>";
    echo "<p><strong>Follow these steps:</strong></p>";
    echo "<ol>";
    
    if (!$hasConfig) {
        echo "<li>Create <code class='code'>php/config.php</code> file</li>";
    }
    
    if (!$canConnect) {
        echo "<li>Fix database connection settings in config.php</li>";
        echo "<li>Make sure MySQL is running in XAMPP</li>";
    }
    
    if (!$dbExists || !$hasTables) {
        echo "<li>Run COMPLETE-DATABASE-SETUP.sql in phpMyAdmin</li>";
        echo "<li>Run SAMPLE-DATA.sql for test data</li>";
    }
    
    echo "</ol>";
}

echo "</div>";

// ============ SHOW CORRECT CONFIG ============
echo "<div class='box'>";
echo "<h2>CORRECT config.php File</h2>";
echo "<p>Your php/config.php should look like this:</p>";
echo "<pre>";
echo htmlspecialchars('<?php
/* Database Configuration */

function getDB() {
    $config = [
        \'host\' => \'localhost\',
        \'dbname\' => \'eduverse_db\',
        \'username\' => \'root\',
        \'password\' => \'\',  // Empty for XAMPP default
        \'charset\' => \'utf8mb4\'
    ];
    
    try {
        $dsn = "mysql:host={$config[\'host\']};dbname={$config[\'dbname\']};charset={$config[\'charset\']}";
        $pdo = new PDO($dsn, $config[\'username\'], $config[\'password\']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        throw $e;
    }
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header(\'Content-Type: application/json\');
    echo json_encode($data);
    exit;
}

function getRequestBody() {
    return json_decode(file_get_contents(\'php://input\'), true) ?? [];
}
?>');
echo "</pre>";
echo "</div>";

?>

<hr>
<p><a href="index.php" style="color:#6BCBF7;">← Back to Home</a></p>

</body>
</html>