<?php
/**
 * Check plan limits before allowing action
 */
function checkPlanLimit($school_id, $action) {
    $subManager = new SubscriptionManager($school_id);
    
    $limits = [
        'add_student' => 'students',
        'add_teacher' => 'teachers',
        'upload_file' => 'storage_gb'
    ];
    
    if (isset($limits[$action])) {
        $check = $subManager->checkLimit($limits[$action]);
        
        if (!$check['allowed']) {
            return [
                'success' => false,
                'message' => $check['message'],
                'upgrade_url' => '/upgrade-plan'
            ];
        }
    }
    
    return ['success' => true];
}
 
// Usage in your code:
$limitCheck = checkPlanLimit($_SESSION['school_id'], 'add_student');
if (!$limitCheck['success']) {
    die(json_encode($limitCheck));
}