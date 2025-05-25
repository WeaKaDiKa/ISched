<?php
require_once('db.php');
require_once('session.php');

// Check if the notifications table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($tableCheckResult->num_rows > 0) {
    // Table exists, check if it has the sender_photo column
    $columnCheckResult = $conn->query("SHOW COLUMNS FROM notifications LIKE 'sender_photo'");
    
    if ($columnCheckResult->num_rows == 0) {
        // Add the new columns for sender information
        $alterTableSql = "ALTER TABLE notifications 
            ADD COLUMN sender_id INT AFTER user_id,
            ADD COLUMN sender_name VARCHAR(100) AFTER sender_id,
            ADD COLUMN sender_photo VARCHAR(255) AFTER sender_name";
        
        if ($conn->query($alterTableSql)) {
            echo "<p>Successfully updated notifications table structure.</p>";
        } else {
            echo "<p>Error updating table structure: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>The notifications table already has the required columns.</p>";
    }
} else {
    // Create the table with the new structure
    $createTableSql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        sender_id INT,
        sender_name VARCHAR(100),
        sender_photo VARCHAR(255),
        message VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES patients(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($createTableSql)) {
        echo "<p>Successfully created notifications table.</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

// Check if there are any patients in the database
$patientResult = $conn->query("SELECT id FROM patients LIMIT 1");
if ($patientResult->num_rows == 0) {
    echo "<p>No patients found in the database. Please add patients first.</p>";
    echo "<p><a href='index.php'>Go to Home</a></p>";
    exit;
}

// Get the first patient ID for our sample notifications
$patientRow = $patientResult->fetch_assoc();
$patientId = $patientRow['id'];

// Clear existing notifications for demo purposes
$conn->query("DELETE FROM notifications WHERE user_id = $patientId");

// Insert sample notifications with sender photos
$sampleNotifications = [
    [
        'user_id' => $patientId,
        'sender_id' => 1,
        'sender_name' => 'Dr. Maria Santos',
        'sender_photo' => 'assets/photo/doctor1.jpg',
        'message' => 'Your dental check-up appointment has been confirmed for tomorrow at 10:00 AM.',
        'type' => 'appointment',
        'reference_id' => 101
    ],
    [
        'user_id' => $patientId,
        'sender_id' => 2,
        'sender_name' => 'Dr. James Rodriguez',
        'sender_photo' => 'assets/photo/doctor2.jpg',
        'message' => 'We need to reschedule your root canal procedure. Please call our office to set a new date.',
        'type' => 'reschedule',
        'reference_id' => 102
    ],
    [
        'user_id' => $patientId,
        'sender_id' => 3,
        'sender_name' => 'M&A Oida Dental Clinic',
        'sender_photo' => 'assets/photo/clinic_logo.jpg',
        'message' => 'Your appointment for Teeth Whitening has been cancelled. Please contact us for details.',
        'type' => 'cancellation',
        'reference_id' => 103
    ],
    [
        'user_id' => $patientId,
        'sender_id' => 4,
        'sender_name' => 'Dr. Anna Reyes',
        'sender_photo' => '',  // Empty photo to demonstrate fallback
        'message' => 'Thank you for your 5-star review! We appreciate your feedback and look forward to seeing you again.',
        'type' => 'review',
        'reference_id' => 104
    ],
    [
        'user_id' => $patientId,
        'sender_id' => 5,
        'sender_name' => 'Appointment System',
        'sender_photo' => '',
        'message' => 'Reminder: You have an upcoming appointment for Dental Check-up on June 5, 2025 at 2:30 PM.',
        'type' => 'reminder',
        'reference_id' => 105
    ],
    [
        'user_id' => $patientId,
        'sender_id' => 6,
        'sender_name' => 'Dr. Michael Tan',
        'sender_photo' => 'assets/photo/doctor3.jpg',
        'message' => 'I\'ve reviewed your dental X-rays. Everything looks good! Keep up the excellent oral hygiene.',
        'type' => 'message',
        'reference_id' => 106
    ]
];

// Insert the sample notifications
$insertCount = 0;
foreach ($sampleNotifications as $notification) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, sender_id, sender_name, sender_photo, message, type, reference_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssi", 
        $notification['user_id'],
        $notification['sender_id'],
        $notification['sender_name'],
        $notification['sender_photo'],
        $notification['message'],
        $notification['type'],
        $notification['reference_id']
    );
    
    if ($stmt->execute()) {
        $insertCount++;
    }
}

echo "<p>Inserted $insertCount sample notifications with sender photos.</p>";
echo "<p><a href='notifications.php'>View Notifications</a></p>";
?>
