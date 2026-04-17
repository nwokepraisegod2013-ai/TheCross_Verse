<?php
/**
 * Manage school subscriptions and plan enforcement
 */
class SubscriptionManager {
    
    private $db;
    private $school_id;
    
    public function __construct($school_id) {
        $this->db = getDB();
        $this->school_id = $school_id;
    }
    
    /**
     * Create new subscription
     */
    public function createSubscription($plan_id, $billing_cycle, $payment_reference) {
        try {
            // Get plan details
            $stmt = $this->db->prepare("SELECT * FROM hosting_plans WHERE id = ? AND is_active = 1");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch();
            
            if (!$plan) {
                throw new Exception('Invalid plan');
            }
            
            // Calculate dates and amount
            $start_date = date('Y-m-d');
            $billing_map = [
                'monthly' => ['+1 month', $plan['price_monthly']],
                'quarterly' => ['+3 months', $plan['price_quarterly']],
                'yearly' => ['+1 year', $plan['price_yearly']]
            ];
            
            list($interval, $amount) = $billing_map[$billing_cycle];
            $end_date = date('Y-m-d', strtotime($start_date . ' ' . $interval));
            $next_billing = date('Y-m-d', strtotime($end_date));
            
            // Create subscription
            $stmt = $this->db->prepare("
                INSERT INTO school_subscriptions (
                    school_id, plan_id, status, start_date, end_date, 
                    billing_cycle, amount_paid, next_billing_date, transaction_reference
                ) VALUES (?, ?, 'active', ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->school_id, $plan_id, $start_date, $end_date,
                $billing_cycle, $amount, $next_billing, $payment_reference
            ]);
            
            $subscription_id = $this->db->lastInsertId();
            
            // Update school status
            $stmt = $this->db->prepare("
                UPDATE schools 
                SET subscription_status = 'active' 
                WHERE id = ?
            ");
            $stmt->execute([$this->school_id]);
            
            // Initialize storage tracking
            $this->initializeStorage($plan['max_storage_gb']);
            
            return [
                'success' => true,
                'subscription_id' => $subscription_id,
                'end_date' => $end_date
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check if feature is available in current plan
     */
    public function hasFeature($feature_name) {
        $stmt = $this->db->prepare("
            SELECT hp.{$feature_name}
            FROM school_subscriptions ss
            JOIN hosting_plans hp ON ss.plan_id = hp.id
            WHERE ss.school_id = ? 
            AND ss.status = 'active'
            AND ss.end_date >= CURDATE()
            LIMIT 1
        ");
        $stmt->execute([$this->school_id]);
        $result = $stmt->fetch();
        
        return $result && $result[$feature_name] == 1;
    }
    
    /**
     * Check usage limits
     */
    public function checkLimit($limit_type) {
        $stmt = $this->db->prepare("
            SELECT ss.current_{$limit_type}, hp.max_{$limit_type}
            FROM school_subscriptions ss
            JOIN hosting_plans hp ON ss.plan_id = hp.id
            WHERE ss.school_id = ? 
            AND ss.status = 'active'
            AND ss.end_date >= CURDATE()
            LIMIT 1
        ");
        $stmt->execute([$this->school_id]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['allowed' => false, 'message' => 'No active subscription'];
        }
        
        $current = $result['current_' . $limit_type];
        $max = $result['max_' . $limit_type];
        
        if ($current >= $max) {
            return [
                'allowed' => false,
                'message' => "Limit reached. You have {$current}/{$max} {$limit_type}",
                'upgrade_required' => true
            ];
        }
        
        return ['allowed' => true, 'current' => $current, 'max' => $max];
    }
    
    /**
     * Update usage count
     */
    public function incrementUsage($usage_type) {
        $stmt = $this->db->prepare("
            UPDATE school_subscriptions 
            SET current_{$usage_type} = current_{$usage_type} + 1
            WHERE school_id = ? 
            AND status = 'active'
        ");
        return $stmt->execute([$this->school_id]);
    }
    
    /**
     * Get current subscription
     */
    public function getCurrentSubscription() {
        $stmt = $this->db->prepare("
            SELECT ss.*, hp.plan_name, hp.max_students, hp.max_teachers, hp.max_storage_gb
            FROM school_subscriptions ss
            JOIN hosting_plans hp ON ss.plan_id = hp.id
            WHERE ss.school_id = ? 
            AND ss.status = 'active'
            AND ss.end_date >= CURDATE()
            ORDER BY ss.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$this->school_id]);
        return $stmt->fetch();
    }
    
    /**
     * Initialize storage tracking
     */
    private function initializeStorage($limit_gb) {
        $stmt = $this->db->prepare("
            INSERT INTO storage_usage (school_id, storage_limit_gb)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE storage_limit_gb = ?
        ");
        $stmt->execute([$this->school_id, $limit_gb, $limit_gb]);
    }
    
    /**
     * Check subscription expiry and send warnings
     */
    public static function checkExpiringSubscriptions() {
        $db = getDB();
        
        // Get subscriptions expiring in 7 days
        $stmt = $db->prepare("
            SELECT ss.*, s.school_name, s.contact_email, hp.plan_name
            FROM school_subscriptions ss
            JOIN schools s ON ss.school_id = s.id
            JOIN hosting_plans hp ON ss.plan_id = hp.id
            WHERE ss.status = 'active'
            AND ss.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND ss.renewal_reminder_sent = 0
        ");
        $stmt->execute();
        $expiring = $stmt->fetchAll();
        
        foreach ($expiring as $sub) {
            // Send renewal email
            self::sendRenewalEmail($sub);
            
            // Mark as sent
            $update = $db->prepare("UPDATE school_subscriptions SET renewal_reminder_sent = 1 WHERE id = ?");
            $update->execute([$sub['id']]);
        }
        
        // Expire subscriptions
        $db->exec("
            UPDATE school_subscriptions SET status = 'expired'
            WHERE end_date < CURDATE() AND status = 'active'
        ");
        
        $db->exec("
            UPDATE schools SET subscription_status = 'expired'
            WHERE id IN (
                SELECT school_id FROM school_subscriptions 
                WHERE status = 'expired'
            )
        ");
    }
    
    /**
     * Send renewal email
     */
    private static function sendRenewalEmail($subscription) {
        // Implement email sending with PHPMailer
        // Include renewal link with Paystack payment
    }
}
```