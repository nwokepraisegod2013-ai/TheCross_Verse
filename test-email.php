<?php
/*
==================================================================
 EMAIL CONFIGURATION TEST
 Tests email sending functionality
==================================================================
*/

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/email-config.php';

echo "<h1>📧 Email Test Suite</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .pass { color: green; }
    .fail { color: red; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
    input { padding: 10px; width: 300px; margin: 5px 0; }
    button { padding: 10px 20px; background: #6bcbf7; color: white; border: none; border-radius: 5px; cursor: pointer; }
</style>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='test'>";
    echo "<h3>Sending Test Email...</h3>";
    
    $toEmail = $_POST['email'] ?? '';
    
    if (empty($toEmail)) {
        echo "<span class='fail'>❌ Please provide an email address</span>";
    } else {
        try {
            $emailSender = new EmailSender();
            
            $result = $emailSender->sendWelcomeEmail(
                $toEmail,
                'Test School Academy',
                'testuser_admin',
                'TempPass123!',
                'testschool',
                'https://paystack.com/pay/test123'
            );
            
            if ($result['success']) {
                echo "<span class='pass'>✅ Email sent successfully!</span><br>";
                echo "Check inbox: {$toEmail}<br>";
                echo "<p><small>Note: Check spam folder if not received within 2 minutes</small></p>";
            } else {
                echo "<span class='fail'>❌ Failed to send email</span><br>";
                echo "Error: " . ($result['error'] ?? 'Unknown error') . "<br>";
            }
        } catch (Exception $e) {
            echo "<span class='fail'>❌ Error: " . $e->getMessage() . "</span>";
        }
    }
    echo "</div>";
}

echo "<div class='test'>";
echo "<h3>Send Test Email</h3>";
echo "<form method='POST'>";
echo "<input type='email' name='email' placeholder='your-email@example.com' required><br>";
echo "<button type='submit'>Send Test Email</button>";
echo "</form>";
echo "</div>";

// Check email configuration
echo "<div class='test'>";
echo "<h3>Email Configuration Check</h3>";

try {
    $emailSender = new EmailSender();
    echo "<span class='pass'>✅ EmailSender class loaded</span><br>";
    echo "SMTP Host: smtp.gmail.com<br>";
    echo "Port: 587<br>";
    echo "From: noreply@eduverse.ng<br>";
} catch (Exception $e) {
    echo "<span class='fail'>❌ Email configuration error</span><br>";
    echo "Error: " . $e->getMessage();
}

echo "</div>";