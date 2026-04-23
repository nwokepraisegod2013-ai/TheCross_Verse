<?php
/* ============================================
   SUB-ADMIN MANAGER
   Auto-creates school admin accounts
   ============================================ */

class SubAdminManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function createSchoolAdmin($schoolId, $schoolName, $contactEmail, $contactName) {
        // Generate username
        $baseUsername = strtolower(str_replace([' ', '-', '.'], '', $schoolName)) . '_admin';
        $username = $this->ensureUniqueUsername($baseUsername);
        
        // Generate secure random password
        $password = $this->generateSecurePassword();
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Create user account
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password, email, role, first_name, last_name, status)
            VALUES (?, ?, ?, 'admin', ?, '', 'active')
        ");
        $stmt->execute([$username, $passwordHash, $contactEmail, $contactName]);
        $userId = $this->db->lastInsertId();
        
        // Create school_admins entry
        $stmt = $this->db->prepare("
            INSERT INTO school_admins (
                user_id, school_id, admin_level,
                can_manage_students, can_manage_teachers, can_view_reports,
                can_manage_website, can_manage_billing, can_manage_settings
            ) VALUES (?, ?, 'owner', 1, 1, 1, 1, 1, 1)
        ");
        $stmt->execute([$userId, $schoolId]);
        
        return [
            'user_id' => $userId,
            'username' => $username,
            'password' => $password
        ];
    }
    
    private function ensureUniqueUsername($baseUsername) {
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->usernameExists($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    private function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function generateSecurePassword($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }
}