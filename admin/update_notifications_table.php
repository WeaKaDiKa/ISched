<?php
require_once('db.php');

// Check if the admin_notifications table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'admin_notifications'");

if ($tableCheckResult->num_rows > 0) {
    // Table exists, check if it has the user_photo column
    $columnCheckResult = $conn->query("SHOW COLUMNS FROM admin_notifications LIKE 'user_photo'");
    
    if ($columnCheckResult->num_rows == 0) {
        // Add the new columns for user information
        $alterTableSql = "ALTER TABLE admin_notifications 
            ADD COLUMN user_id INT AFTER id,
            ADD COLUMN user_name VARCHAR(100) AFTER user_id,
            ADD COLUMN user_photo VARCHAR(255) AFTER user_name";
        
        if ($conn->query($alterTableSql)) {
            echo "<p>Successfully updated admin_notifications table structure.</p>";
        } else {
            echo "<p>Error updating table structure: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>The admin_notifications table already has the required columns.</p>";
    }
} else {
    // Create the table with the new structure
    $createTableSql = "CREATE TABLE admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_name VARCHAR(100),
        user_photo VARCHAR(255),
        message VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTableSql)) {
        echo "<p>Successfully created admin_notifications table.</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

// Clear existing notifications for demo purposes
$conn->query("TRUNCATE TABLE admin_notifications");

// Insert sample notifications with user photos
$sampleNotifications = [
    [
        'user_id' => 1,
        'user_name' => 'John Smith',
        'user_photo' => '../assets/photo/patient1.jpg',
        'message' => 'reacted to a video you shared: "WHAHAHHA".',
        'type' => 'reaction',
        'reference_id' => 101
    ],
    [
        'user_id' => 2,
        'user_name' => 'Maria Garcia',
        'user_photo' => '../assets/photo/patient2.jpg',
        'message' => 'booked a new appointment for Dental Check-up on June 5, 2025.',
        'type' => 'appointment',
        'reference_id' => 102
    ],
    [
        'user_id' => 3,
        'user_name' => 'Robert Johnson',
        'user_photo' => '../assets/photo/patient3.jpg',
        'message' => 'left a 5-star review: "Amazing service. The doctor made me feel comfortable."',
        'type' => 'review',
        'reference_id' => 103
    ],
    [
        'user_id' => 4,
        'user_name' => 'Sarah Williams',
        'user_photo' => '',  // Empty photo to demonstrate fallback
        'message' => 'canceled her appointment scheduled for tomorrow.',
        'type' => 'cancellation',
        'reference_id' => 104
    ],
    [
        'user_id' => 5,
        'user_name' => 'Michael Brown',
        'user_photo' => '../assets/photo/patient5.jpg',
        'message' => 'requested to reschedule his appointment to next week.',
        'type' => 'reschedule',
        'reference_id' => 105
    ]
];

// Insert the sample notifications
$insertCount = 0;
foreach ($sampleNotifications as $notification) {
    $stmt = $conn->prepare("INSERT INTO admin_notifications (user_id, user_name, user_photo, message, type, reference_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", 
        $notification['user_id'],
        $notification['user_name'],
        $notification['user_photo'],
        $notification['message'],
        $notification['type'],
        $notification['reference_id']
    );
    
    if ($stmt->execute()) {
        $insertCount++;
    }
}

echo "<p>Inserted $insertCount sample notifications with user photos.</p>";
echo "<p><a href='index.php'>Go to Admin Dashboard</a></p>";
?>
