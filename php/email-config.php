<?php
/* ============================================
   EMAIL CONFIGURATION - GMAIL SMTP
   Use this for production with Gmail
   
   SETUP:
   1. Enable 2FA on Gmail
   2. Generate App Password
   3. Update credentials below
   ============================================ */

class EmailSender {
    private $config = [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_user' => 'nwokepraisegod2013@gmail.com',      // ← CHANGE THIS
        'smtp_pass' => 'quep scdz rbdf lwnr', // ← CHANGE THIS
        'from_email' => 'noreply@eduverse.ng',
        'from_name' => 'EduVerse Platform'
    ];
    
    public function sendWelcomeEmail($to, $schoolName, $username, $password, $subdomain, $paymentLink = '') {
        // Build email content
        $subject = "Welcome to EduVerse Platform - {$schoolName}";
        $body = $this->getWelcomeEmailTemplate($schoolName, $username, $password, $subdomain, $paymentLink);
        
        // Try to send via SMTP
        $result = $this->sendViaSMTP($to, $subject, $body);
        
        // If SMTP fails, log to file
        if (!$result['success']) {
            $this->logEmailToFile($to, $subject, $body);
            return [
                'success' => false,
                'error' => $result['error'],
                'logged_to_file' => true,
                'message' => 'Email logged to file: logs/emails.log'
            ];
        }
        
        return $result;
    }
    
    private function sendViaSMTP($to, $subject, $body) {
        try {
            // Create socket connection
            $smtp = @fsockopen(
                'tls://' . $this->config['smtp_host'],
                $this->config['smtp_port'],
                $errno,
                $errstr,
                30
            );
            
            if (!$smtp) {
                throw new Exception("Cannot connect to SMTP server: {$errstr} ({$errno})");
            }
            
            // Read server response
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '220') {
                throw new Exception("SMTP connection failed: {$response}");
            }
            
            // EHLO
            fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $response = fgets($smtp, 515);
            
            // AUTH LOGIN
            fputs($smtp, "AUTH LOGIN\r\n");
            fgets($smtp, 515);
            
            // Username
            fputs($smtp, base64_encode($this->config['smtp_user']) . "\r\n");
            fgets($smtp, 515);
            
            // Password
            fputs($smtp, base64_encode($this->config['smtp_pass']) . "\r\n");
            $response = fgets($smtp, 515);
            
            if (substr($response, 0, 3) != '235') {
                throw new Exception("SMTP authentication failed. Check username/password.");
            }
            
            // MAIL FROM
            fputs($smtp, "MAIL FROM: <{$this->config['from_email']}>\r\n");
            fgets($smtp, 515);
            
            // RCPT TO
            fputs($smtp, "RCPT TO: <{$to}>\r\n");
            fgets($smtp, 515);
            
            // DATA
            fputs($smtp, "DATA\r\n");
            fgets($smtp, 515);
            
            // Headers and body
            $headers = "From: {$this->config['from_name']} <{$this->config['from_email']}>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Subject: {$subject}\r\n";
            
            fputs($smtp, $headers . "\r\n" . $body . "\r\n.\r\n");
            fgets($smtp, 515);
            
            // QUIT
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);
            
            return ['success' => true, 'method' => 'SMTP'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function logEmailToFile($to, $subject, $body) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/emails.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = "\n" . str_repeat('=', 80) . "\n";
        $logEntry .= "Timestamp: {$timestamp}\n";
        $logEntry .= "To: {$to}\n";
        $logEntry .= "Subject: {$subject}\n";
        $logEntry .= str_repeat('-', 80) . "\n";
        $logEntry .= strip_tags($body) . "\n";
        $logEntry .= str_repeat('=', 80) . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    private function getWelcomeEmailTemplate($schoolName, $username, $password, $subdomain, $paymentLink) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #6bcbf7, #a78bfa); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .credentials { background: white; padding: 20px; border-left: 4px solid #6bcbf7; margin: 20px 0; }
                .button { display: inline-block; background: #6bcbf7; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Welcome to EduVerse Platform!</h1>
                </div>
                
                <div class='content'>
                    <h2>Congratulations, {$schoolName}!</h2>
                    <p>Your school has been approved.</p>
                    
                    <div class='credentials'>
                        <h3>🔐 Your Admin Credentials</h3>
                        <p><strong>Username:</strong> {$username}</p>
                        <p><strong>Password:</strong> {$password}</p>
                        <p><a href='https://eduverse.ng/login.php'>Login Now</a></p>
                    </div>
                    
                    <div class='credentials'>
                        <h3>🌐 Your School Website</h3>
                        <p><a href='https://{$subdomain}.eduverse.ng'>https://{$subdomain}.eduverse.ng</a></p>
                    </div>
                    
                    " . ($paymentLink ? "
                    <div class='credentials'>
                        <h3>💳 Complete Payment</h3>
                        <a href='{$paymentLink}' class='button'>Pay Now</a>
                    </div>
                    " : "") . "
                </div>
            </div>
        </body>
        </html>
        ";
    }
}