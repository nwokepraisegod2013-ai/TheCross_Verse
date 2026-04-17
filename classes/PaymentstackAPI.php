<?php
/**
 * Paystack Payment Integration
 */
class PaystackAPI {
    
    private $secretKey;
    private $publicKey;
    private $baseUrl = 'https://api.paystack.co';
    
    public function __construct() {
        $this->secretKey = $this->getSetting('paystack_secret_key');
        $this->publicKey = $this->getSetting('paystack_public_key');
    }
    
    /**
     * Initialize payment
     */
    public function initializePayment($email, $amount, $reference, $metadata = []) {
        $url = $this->baseUrl . '/transaction/initialize';
        
        $data = [
            'email' => $email,
            'amount' => $amount * 100, // Convert to kobo
            'reference' => $reference,
            'callback_url' => 'https://eduverse.ng/payment/callback',
            'metadata' => $metadata
        ];
        
        $response = $this->makeRequest('POST', $url, $data);
        
        if ($response && $response->status) {
            return [
                'success' => true,
                'authorization_url' => $response->data->authorization_url,
                'access_code' => $response->data->access_code,
                'reference' => $response->data->reference
            ];
        }
        
        return ['success' => false, 'message' => 'Payment initialization failed'];
    }
    
    /**
     * Verify payment
     */
    public function verifyPayment($reference) {
        $url = $this->baseUrl . '/transaction/verify/' . $reference;
        
        $response = $this->makeRequest('GET', $url);
        
        if ($response && $response->status && $response->data->status === 'success') {
            return [
                'success' => true,
                'amount' => $response->data->amount / 100,
                'paid_at' => $response->data->paid_at,
                'channel' => $response->data->channel,
                'authorization' => $response->data->authorization
            ];
        }
        
        return ['success' => false, 'message' => 'Payment verification failed'];
    }
    
    /**
     * Create subscription plan
     */
    public function createPlan($name, $amount, $interval) {
        $url = $this->baseUrl . '/plan';
        
        $data = [
            'name' => $name,
            'amount' => $amount * 100,
            'interval' => $interval // monthly, quarterly, annually
        ];
        
        return $this->makeRequest('POST', $url, $data);
    }
    
    /**
     * Subscribe customer to plan
     */
    public function createSubscription($customer, $plan, $start_date = null) {
        $url = $this->baseUrl . '/subscription';
        
        $data = [
            'customer' => $customer,
            'plan' => $plan
        ];
        
        if ($start_date) {
            $data['start_date'] = $start_date;
        }
        
        return $this->makeRequest('POST', $url, $data);
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhook($payload, $signature) {
        $webhookSecret = $this->getSetting('paystack_webhook_secret');
        $computedHash = hash_hmac('sha512', $payload, $webhookSecret);
        
        return hash_equals($computedHash, $signature);
    }
    
    /**
     * Make API request
     */
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init($url);
        
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response);
        }
        
        return null;
    }
    
    /**
     * Get platform setting
     */
    private function getSetting($key) {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    }
}
```