<?php
require_once('db.php');

// Create the admin_notifications table if it doesn't exist
$createTableSql = "CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    reference_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTableSql);

// Insert a test notification
$message = "Test notification - " . date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO admin_notifications (message, type, reference_id) VALUES (?, 'test', 1)");
$stmt->bind_param("s", $message);
$stmt->execute();

echo "<h1>Test notification created!</h1>";
echo "<p>Go back to the admin panel and check if the notification bell works now.</p>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
