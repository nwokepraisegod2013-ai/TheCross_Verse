<?php
require_once '../classes/PaystackAPI.php';
require_once '../classes/SubscriptionManager.php';
 
// Get payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
 
// Verify webhook
$paystack = new PaystackAPI();
if (!$paystack->verifyWebhook($payload, $signature)) {
    http_response_code(400);
    exit('Invalid signature');
}
 
// Parse event
$event = json_decode($payload);
 
// Handle different events
switch ($event->event) {
    case 'charge.success':
        handleSuccessfulPayment($event->data);
        break;
        
    case 'subscription.create':
        handleSubscriptionCreated($event->data);
        break;
        
    case 'subscription.disable':
        handleSubscriptionDisabled($event->data);
        break;
}
 
http_response_code(200);
exit('OK');
 
/**
 * Handle successful payment
 */
function handleSuccessfulPayment($data) {
    $db = getDB();
    
    $reference = $data->reference;
    $amount = $data->amount / 100;
    $school_id = $data->metadata->school_id;
    $plan_id = $data->metadata->plan_id;
    $billing_cycle = $data->metadata->billing_cycle;
    
    // Update payment record
    $stmt = $db->prepare("
        UPDATE payment_history 
        SET payment_status = 'completed', 
            paystack_reference = ?,
            authorization_code = ?
        WHERE transaction_reference = ?
    ");
    $stmt->execute([
        $data->reference,
        $data->authorization->authorization_code ?? null,
        $reference
    ]);
    
    // Create subscription
    $subManager = new SubscriptionManager($school_id);
    $subManager->createSubscription($plan_id, $billing_cycle, $reference);
    
    // Send confirmation email
    sendPaymentConfirmation($school_id, $amount, $reference);
}