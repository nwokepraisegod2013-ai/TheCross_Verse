<?php
/* ============================================
   ONE-CLICK LOGIN FIX
   This will create/fix the admin user
   ============================================ */

require_once __DIR__ . '/php/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fix Login - EduVerse</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; margin-bottom: 20px; }
        .success {
            background: #D1FAE5;
            border: 2px solid #10B981;
            color: #065F46;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: bold;
        }
        .error {
            background: #FEE2E2;
            border: 2px solid #EF4444;
            color: #991B1B;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .info {
            background: #DBEAFE;
            border: 2px solid #3B82F6;
            color: #1E40AF;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background: #5568d3; }
        .btn-success { background: #10B981; }
        .btn-success:hover { background: #059669; }
        code {
            background: #F3F4F6;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
            color: #1F2937;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        th {
            background: #F9FAFB;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 One-Click Login Fix</h1>
        
        <?php
        
        try {
            $db = getDB();
            
            // Check current status
            echo '<h2>📊 Current Status</h2>';
            
            $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                echo '<div class="info">ℹ️ Admin user already exists</div>';
                
                echo '<table>';
                echo '<tr><th>Field</th><th>Value</th></tr>';
                echo '<tr><td>Username</td><td><code>' . $admin['username'] . '</code></td></tr>';
                echo '<tr><td>Role</td><td>' . $admin['role'] . '</td></tr>';
                echo '<tr><td>Status</td><td>' . $admin['status'] . '</td></tr>';
                echo '<tr><td>School</td><td>' . $admin['school_key'] . '</td></tr>';
                echo '</table>';
                
                // Test password
                $testPassword = 'admin123';
                $isCorrect = password_verify($testPassword, $admin['password']);
                
                if ($isCorrect) {
                    echo '<div class="success">';
                    echo '✅ Password is CORRECT!<br><br>';
                    echo '<strong>You can login with:</strong><br>';
                    echo 'Username: <code>admin</code><br>';
                    echo 'Password: <code>admin123</code><br><br>';
                    echo '<a href="login.html" class="btn btn-success">Go to Login Page</a>';
                    echo '</div>';
                } else {
                    echo '<div class="error">❌ Password is WRONG</div>';
                    
                    // Show fix button
                    if (!isset($_POST['fix_password'])) {
                        echo '<form method="POST">';
                        echo '<input type="hidden" name="fix_password" value="1">';
                        echo '<button type="submit" class="btn btn-success">🔧 Fix Password Now</button>';
                        echo '</form>';
                    }
                }
                
            } else {
                echo '<div class="error">❌ Admin user does NOT exist</div>';
                
                // Show create button
                if (!isset($_POST['create_admin'])) {
                    echo '<form method="POST">';
                    echo '<input type="hidden" name="create_admin" value="1">';
                    echo '<button type="submit" class="btn btn-success">➕ Create Admin User</button>';
                    echo '</form>';
                }
            }
            
            // Handle fix actions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                
                echo '<h2>🔧 Fix Results</h2>';
                
                // Fix password
                if (isset($_POST['fix_password'])) {
                    $newHash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    $stmt = $db->prepare("UPDATE users SET password = :hash WHERE username = 'admin'");
                    $stmt->execute(['hash' => $newHash]);
                    
                    echo '<div class="success">';
                    echo '✅ Password has been reset!<br><br>';
                    echo '<strong>New credentials:</strong><br>';
                    echo 'Username: <code>admin</code><br>';
                    echo 'Password: <code>admin123</code><br><br>';
                    echo '<a href="login.html" class="btn btn-success">Go to Login Page</a>';
                    echo '</div>';
                }
                
                // Create admin
                if (isset($_POST['create_admin'])) {
                    $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    $stmt = $db->prepare("
                        INSERT INTO users (first_name, last_name, username, password, role, school_key, status, email, created_at, updated_at)
                        VALUES (:first, :last, :username, :password, :role, :school, :status, :email, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        'first' => 'Admin',
                        'last' => 'User',
                        'username' => 'admin',
                        'password' => $hash,
                        'role' => 'admin',
                        'school' => 'both',
                        'status' => 'active',
                        'email' => 'admin@eduverse.edu'
                    ]);
                    
                    echo '<div class="success">';
                    echo '✅ Admin user created successfully!<br><br>';
                    echo '<strong>Login credentials:</strong><br>';
                    echo 'Username: <code>admin</code><br>';
                    echo 'Password: <code>admin123</code><br><br>';
                    echo '<a href="login.html" class="btn btn-success">Go to Login Page</a>';
                    echo '</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '❌ Error: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        
        ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #E5E7EB; color: #6B7280;">
            <p><strong>💡 Troubleshooting Tips:</strong></p>
            <ul>
                <li>Make sure MySQL is running</li>
                <li>Verify database name is <code>eduverse_db</code></li>
                <li>Check <code>php/config.php</code> has correct credentials</li>
                <li>Hard refresh browser after fixing (Ctrl+F5)</li>
            </ul>
        </div>
        
    </div>
</body>
</html>