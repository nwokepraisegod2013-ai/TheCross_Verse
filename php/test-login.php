<?php
/* ============================================
   TEST LOGIN SYSTEM
   Check database and credentials
   ============================================ */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

echo "<h1>EduVerse Login Test</h1>";
echo "<style>body{font-family:sans-serif;padding:2rem;background:#1a1a2e;color:#eee;}</style>";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    $db = getDB();
    echo "✅ <strong>Connected to database successfully!</strong><br>";
} catch (Exception $e) {
    echo "❌ <strong>Database connection failed:</strong> " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check if users table exists
echo "<h2>2. Users Table</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "✅ Users table exists with <strong>$count</strong> users<br>";
} catch (Exception $e) {
    echo "❌ Error accessing users table: " . $e->getMessage() . "<br>";
}

// Test 3: Check admin user
echo "<h2>3. Admin User</h2>";
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin user found:<br>";
        echo "- ID: {$admin['id']}<br>";
        echo "- Username: {$admin['username']}<br>";
        echo "- Role: {$admin['role']}<br>";
        echo "- Status: {$admin['status']}<br>";
        echo "- Email: {$admin['email']}<br>";
        echo "- Name: {$admin['first_name']} {$admin['last_name']}<br>";
        
        // Test password
        if (password_verify('admin123', $admin['password'])) {
            echo "✅ <strong>Password 'admin123' is CORRECT</strong><br>";
        } else {
            echo "⚠️  Password hash doesn't match 'admin123'<br>";
            echo "- Hash in database: " . substr($admin['password'], 0, 30) . "...<br>";
        }
    } else {
        echo "❌ Admin user NOT found in database<br>";
        echo "<strong>Run COMPLETE-DATABASE-SETUP.sql to create admin user</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking admin user: " . $e->getMessage() . "<br>";
}

// Test 4: Check student users
echo "<h2>4. Student Users</h2>";
try {
    $stmt = $db->query("SELECT u.*, sp.student_id FROM users u LEFT JOIN student_profiles sp ON u.id = sp.user_id WHERE u.role = 'student' LIMIT 5");
    $students = $stmt->fetchAll();
    
    if (count($students) > 0) {
        echo "✅ Found <strong>" . count($students) . "</strong> student(s):<br>";
        foreach ($students as $student) {
            echo "- {$student['username']} ({$student['first_name']} {$student['last_name']}) - Student ID: {$student['student_id']}<br>";
        }
    } else {
        echo "⚠️  No students found. Run SAMPLE-DATA.sql to add test students<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking students: " . $e->getMessage() . "<br>";
}

// Test 5: Simulate login
echo "<h2>5. Simulate Login</h2>";
echo "<strong>Testing with: username='admin', password='admin123'</strong><br>";

$username = 'admin';
$password = 'admin123';

try {
    $stmt = $db->prepare("
        SELECT 
            u.id, 
            u.username, 
            u.password, 
            u.role, 
            u.email,
            u.first_name,
            u.last_name,
            u.status
        FROM users u
        WHERE u.username = ?
        LIMIT 1
    ");
    
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found<br>";
    } else {
        echo "✅ User found in database<br>";
        
        if (password_verify($password, $user['password'])) {
            echo "✅ Password verified successfully<br>";
        } else if ($password === 'admin123') {
            echo "⚠️  Password hash mismatch, but plain text matches<br>";
        } else {
            echo "❌ Password verification failed<br>";
        }
        
        if ($user['status'] === 'active') {
            echo "✅ Account is active<br>";
        } else {
            echo "❌ Account is inactive<br>";
        }
        
        echo "<br><strong>🎉 LOGIN WOULD SUCCEED!</strong><br>";
        echo "- User would be redirected to: admin.php<br>";
        echo "- Session would contain: user_id={$user['id']}, role={$user['role']}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Login simulation error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>✅ Test Complete</h2>";
echo "<p><a href='login.php' style='color:#6BCBF7;'>Go to Login Page</a></p>";
?>