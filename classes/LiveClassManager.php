<?php
class LiveClassManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function createClass($data) {
        $roomId = 'room_' . uniqid() . '_' . time();
        
        $stmt = $this->db->prepare("
            INSERT INTO live_classes (
                school_id, teacher_id, room_id, title, description,
                scheduled_at, duration_minutes, platform, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'jitsi', 'scheduled')
        ");
        
        $stmt->execute([
            $data['school_id'], $data['teacher_id'], $roomId,
            $data['title'], $data['description'] ?? '',
            $data['scheduled_at'], $data['duration_minutes'] ?? 60
        ]);
        
        $classId = $this->db->lastInsertId();
        $meetingUrl = "https://eduverse.ng/live-class/room.php?room={$roomId}";
        
        $this->db->prepare("UPDATE live_classes SET meeting_url = ? WHERE id = ?")
             ->execute([$meetingUrl, $classId]);
        
        return ['success' => true, 'class_id' => $classId, 'room_id' => $roomId, 'url' => $meetingUrl];
    }
    
    public function recordAttendance($classId, $userId, $action = 'join') {
        if ($action === 'join') {
            $this->db->prepare("INSERT INTO class_attendance (class_id, user_id, joined_at) VALUES (?, ?, NOW())")
                 ->execute([$classId, $userId]);
            $this->db->exec("UPDATE live_classes SET current_participants = current_participants + 1 WHERE id = {$classId}");
        } else {
            $this->db->prepare("UPDATE class_attendance SET left_at = NOW(), duration_minutes = TIMESTAMPDIFF(MINUTE, joined_at, NOW()) WHERE class_id = ? AND user_id = ? AND left_at IS NULL")
                 ->execute([$classId, $userId]);
            $this->db->exec("UPDATE live_classes SET current_participants = GREATEST(current_participants - 1, 0) WHERE id = {$classId}");
        }
        return ['success' => true];
    }
}