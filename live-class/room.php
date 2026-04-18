<?php
session_start();
require_once '../php/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$roomId = $_GET['room'] ?? null;
$db = getDB();
$stmt = $db->prepare("SELECT * FROM live_classes WHERE room_id = ?");
$stmt->execute([$roomId]);
$class = $stmt->fetch();

if (!$class) die('Class not found');

$jitsiRoom = 'eduverse_' . $roomId;
$displayName = $_SESSION['full_name'] ?? $_SESSION['username'];
$userRole = $_SESSION['role'];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($class['title']); ?> - Live Class</title>
    <script src='https://8x8.vc/vpaas-magic-cookie-YOUR_APP_ID/external_api.js'></script>
    <style>
        body { margin: 0; padding: 0; overflow: hidden; }
        #meet { width: 100vw; height: 100vh; }
    </style>
</head>
<body>
    <div id="meet"></div>
    <script>
        const domain = '8x8.vc';
        const options = {
            roomName: 'vpaas-magic-cookie-YOUR_APP_ID/<?php echo $jitsiRoom; ?>',
            width: '100%',
            height: '100%',
            parentNode: document.querySelector('#meet'),
            userInfo: {
                displayName: '<?php echo htmlspecialchars($displayName); ?>'
            },
            configOverwrite: {
                startWithAudioMuted: <?php echo $userRole === 'student' ? 'true' : 'false'; ?>,
                startWithVideoMuted: <?php echo $userRole === 'student' ? 'true' : 'false'; ?>,
                enableWelcomePage: false,
                prejoinPageEnabled: true
            }
        };
        
        const api = new JitsiMeetExternalAPI(domain, options);
        
        api.addEventListener('videoConferenceJoined', () => {
            fetch('../api/v1/live-classes.php?action=join', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({room_id: '<?php echo $roomId; ?>', user_id: <?php echo $_SESSION['user_id']; ?>})
            });
        });
        
        api.addEventListener('videoConferenceLeft', () => {
            fetch('../api/v1/live-classes.php?action=leave', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({room_id: '<?php echo $roomId; ?>', user_id: <?php echo $_SESSION['user_id']; ?>})
            });
            window.location.href = '../index.php';
        });
    </script>
</body>
</html>