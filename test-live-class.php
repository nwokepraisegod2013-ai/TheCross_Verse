<?php
/*
==================================================================
 LIVE CLASS SYSTEM TEST
 Tests Jitsi integration and attendance tracking
==================================================================
*/

session_start();
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/classes/LiveClassManager.php';

// Mock user session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 999;
    $_SESSION['username'] = 'test_teacher';
    $_SESSION['role'] = 'teacher';
    $_SESSION['full_name'] = 'Test Teacher';
}

echo "<h1>🎓 Live Class System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .pass { color: green; }
    .fail { color: red; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .button { display: inline-block; padding: 10px 20px; background: #6bcbf7; color: white; text-decoration: none; border-radius: 5px; }
</style>";

$db = getDB();
$liveClassManager = new LiveClassManager($db);

echo "<div class='test'>";
echo "<h3>Test 1: Create Live Class</h3>";

try {
    $result = $liveClassManager->createClass([
        'school_id' => 1,
        'teacher_id' => $_SESSION['user_id'],
        'title' => 'Test Mathematics Class',
        'description' => 'This is a test class for QA',
        'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'duration_minutes' => 60
    ]);
    
    if ($result['success']) {
        echo "<span class='pass'>✅ PASS</span><br>";
        echo "Class ID: {$result['class_id']}<br>";
        echo "Room ID: {$result['room_id']}<br>";
        echo "Meeting URL: <a href='{$result['meeting_url']}'>{$result['meeting_url']}</a><br>";
        
        $classId = $result['class_id'];
        $roomId = $result['room_id'];
    } else {
        echo "<span class='fail'>❌ FAIL</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='fail'>❌ Error: " . $e->getMessage() . "</span>";
}
echo "</div>";

echo "<div class='test'>";
echo "<h3>Test 2: Attendance Tracking</h3>";

try {
    // Record join
    $result = $liveClassManager->recordAttendance($classId, $_SESSION['user_id'], 'join');
    echo "✅ Student joined<br>";
    
    // Wait 2 seconds (simulate time in class)
    sleep(2);
    
    // Record leave
    $result = $liveClassManager->recordAttendance($classId, $_SESSION['user_id'], 'leave');
    echo "✅ Student left<br>";
    
    // Check attendance record
    $stmt = $db->prepare("SELECT * FROM class_attendance WHERE class_id = ? AND user_id = ?");
    $stmt->execute([$classId, $_SESSION['user_id']]);
    $attendance = $stmt->fetch();
    
    if ($attendance) {
        echo "<br><span class='pass'>✅ PASS</span><br>";
        echo "Joined at: {$attendance['joined_at']}<br>";
        echo "Left at: {$attendance['left_at']}<br>";
        echo "Duration: {$attendance['duration_minutes']} minutes<br>";
    } else {
        echo "<span class='fail'>❌ FAIL - No attendance record</span>";
    }
} catch (Exception $e) {
    echo "<span class='fail'>❌ Error: " . $e->getMessage() . "</span>";
}
echo "</div>";

echo "<div class='test'>";
echo "<h3>Test 3: Jitsi Integration</h3>";
echo "<p>Click button below to test Jitsi meeting room:</p>";
echo "<a href='live-class/room.php?room={$roomId}' class='button' target='_blank'>🎥 Join Test Class</a>";
echo "<p><small>Note: This will open in a new tab. Allow camera/microphone permissions when prompted.</small></p>";
echo "</div>";

// Cleanup
echo "<div class='test'>";
echo "<h3>Cleanup Test Data</h3>";
echo "<p>Remove test class from database?</p>";
echo "<form method='POST'>";
echo "<button type='submit' name='cleanup'>Delete Test Class</button>";
echo "</form>";

if (isset($_POST['cleanup'])) {
    try {
        $db->prepare("DELETE FROM class_attendance WHERE class_id = ?")->execute([$classId]);
        $db->prepare("DELETE FROM live_classes WHERE id = ?")->execute([$classId]);
        echo "<span class='pass'>✅ Test data cleaned up</span>";
    } catch (Exception $e) {
        echo "<span class='fail'>❌ Cleanup failed: " . $e->getMessage() . "</span>";
    }
}
echo "</div>";