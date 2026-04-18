<?php
require_once 'config.php';

$input = file_get_contents('php://input');
$event = json_decode($input, true);

// Verify webhook signature
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$secretKey = 'YOUR_PAYSTACK_SECRET';
$hash = hash_hmac('sha512', $input, $secretKey);

if ($hash !== $signature) {
    http_response_code(401);
    exit;
}

if ($event['event'] === 'charge.success') {
    $data = $event['data'];
    $reference = $data['reference'];
    $metadata = $data['metadata'];
    
    $db = getDB();
    
    // Update subscription
    if (isset($metadata['school_id'])) {
        $stmt = $db->prepare("
            UPDATE school_subscriptions 
            SET status = 'active', amount_paid = amount_paid + ?
            WHERE school_id = ? AND plan_id = ?
        ");
        $stmt->execute([
            $data['amount'] / 100,
            $metadata['school_id'],
            $metadata['plan_id']
        ]);
        
        // Update school status
        $db->prepare("UPDATE schools SET subscription_status = 'active' WHERE id = ?")
           ->execute([$metadata['school_id']]);
    }
    
    // Record payment
    $stmt = $db->prepare("
        INSERT INTO payment_history (
            school_id, amount, transaction_reference, payment_status, payment_date
        ) VALUES (?, ?, ?, 'completed', NOW())
    ");
    $stmt->execute([$metadata['school_id'], $data['amount'] / 100, $reference]);
}

http_response_code(200);