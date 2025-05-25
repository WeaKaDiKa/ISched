<?php
require_once('db.php');
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Mark notification as read
    if ($action === 'mark_read') {
        $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if ($notificationId > 0) {
            // Mark specific notification as read
            $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
            $stmt->bind_param("i", $notificationId);
        } else {
            // Mark all notifications as read
            $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1");
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to mark notification as read']);
        }
        exit;
    }
}

// Get notifications
$stmt = $conn->prepare("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get unread count
$unreadStmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result()->fetch_assoc();
$unreadCount = $unreadResult['count'];

// Return JSON
echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unreadCount
]);
