<?php
class PaystackAPI {
    private $secretKey;
    private $baseUrl = 'https://api.paystack.co';
    
    public function __construct() {
        global $db;
        $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'paystack_secret_key' LIMIT 1");
        $this->secretKey = $stmt ? $stmt->fetchColumn() : 'sk_test_YOUR_KEY';
    }
    
    public function initializePayment($email, $amount, $reference, $metadata = []) {
        $data = [
            'email' => $email,
            'amount' => $amount,
            'reference' => $reference,
            'callback_url' => 'https://eduverse.ng/payment-callback.php',
            'metadata' => $metadata
        ];
        
        $response = $this->makeRequest('POST', '/transaction/initialize', $data);
        
        return $response['status'] ? [
            'success' => true,
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $response['data']['reference']
        ] : ['success' => false, 'message' => $response['message'] ?? 'Failed'];
    }
    
    public function verifyPayment($reference) {
        $response = $this->makeRequest('GET', '/transaction/verify/' . $reference);
        
        return $response['status'] ? [
            'success' => true,
            'amount' => $response['data']['amount'] / 100,
            'paid_at' => $response['data']['paid_at'],
            'metadata' => $response['data']['metadata']
        ] : ['success' => false];
    }
    
    private function makeRequest($method, $endpoint, $data = []) {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/json'
            ]
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?: [];
    }
}