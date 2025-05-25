<?php
require_once('session.php');
require_once('db.php');

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Mark notification as read
    if ($action === 'mark_read') {
        $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if ($notificationId > 0) {
            // Mark specific notification as read
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notificationId, $userId);
        } else {
            // Mark all notifications as read
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to mark notification as read']);
        }
        exit;
    }
}

// If not a POST request or invalid action
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request']);
?>
