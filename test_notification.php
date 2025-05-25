<?php
// This is a test file to demonstrate the notification bell functionality

require_once('session.php');
require_once('db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to use this feature.";
    exit;
}

// Check if notifications table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($tableCheckResult->num_rows == 0) {
    // Create notifications table if it doesn't exist
    $createTableSql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES patients(id) ON DELETE CASCADE
    )";
    $conn->query($createTableSql);
    echo "Notifications table created.<br>";
} else {
    echo "Notifications table already exists.<br>";
}

// Create a test notification
$userId = $_SESSION['user_id'];
$message = "This is a test notification. Your appointment has been approved by the admin.";
$notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'test', 1)");
$notifStmt->bind_param('is', $userId, $message);

if ($notifStmt->execute()) {
    echo "Test notification created successfully. Go back to the main page and check the notification bell.";
} else {
    echo "Error creating test notification: " . $conn->error;
}
?>
