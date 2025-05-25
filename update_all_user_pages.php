<?php
require_once('db.php');

// Define the main pages that need updating
$mainPages = [
    'index.php',
    'services.php',
    'about.php',
    'reviews.php',
    'contact.php',
    'profile.php',
    'bookings.php',
    'mybookings.php',
    'myreviews.php',
    'success.php',
    'clinics.php',
    'terms.php'
];

$updatedCount = 0;
$errorCount = 0;

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Update All User Pages</title>\n<style>\nbody { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }\n.success { color: green; }\n.error { color: red; }\n.header { background: #124085; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }\n.footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }\n.btn { display: inline-block; background: #124085; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; }\n</style>\n</head>\n<body>\n<div class=\"header\">\n<h1>Updating User Pages with New Notification Design</h1>\n</div>";

echo "<h2>Processing Pages:</h2>\n<ul>";

foreach ($mainPages as $page) {
    $filePath = __DIR__ . '/' . $page;
    
    if (!file_exists($filePath)) {
        echo "<li class=\"error\">Error: File not found - {$page}</li>";
        $errorCount++;
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $changes = [];
    
    // 1. Add notification CSS if not already present
    if (strpos($content, 'notification.css') === false) {
        // Try to add it after profile-icon.css or before font-awesome
        if (strpos($content, 'profile-icon.css') !== false) {
            $content = str_replace(
                '<link rel="stylesheet" href="assets/css/profile-icon.css">',
                '<link rel="stylesheet" href="assets/css/profile-icon.css">\n  <link rel="stylesheet" href="assets/css/notification.css">',
                $content
            );
            $changes[] = "Added notification.css after profile-icon.css";
        } 
        // Or try to add it before font-awesome
        else if (preg_match('/<link[^>]*font-awesome[^>]*>/', $content)) {
            $content = preg_replace(
                '/(<link[^>]*font-awesome[^>]*>)/',
                '<link rel="stylesheet" href="assets/css/notification.css">\n  $1',
                $content
            );
            $changes[] = "Added notification.css before font-awesome";
        }
        // Or just add it before </head>
        else {
            $content = str_replace(
                '</head>',
                '  <link rel="stylesheet" href="assets/css/notification.css">\n</head>',
                $content
            );
            $changes[] = "Added notification.css before </head>";
        }
    }
    
    // 2. Replace old notification bell code
    // Look for different patterns of notification bell implementation
    $patterns = [
        // Pattern 1: Standard notification wrapper
        '/<div class=["\']notification-wrapper["\']>\s*<div class=["\']notification-toggle["\']>\s*<i class=["\']fa[s]?[\s-]fa-bell["\']><\/i>\s*<\/div>\s*<div class=["\']notification-dropdown["\']>\s*<p class=["\']empty-message["\']>[^<]*<\/p>\s*<\/div>\s*<\/div>/s',
        
        // Pattern 2: Notification wrapper with different structure
        '/<div class=["\']notification-wrapper["\']>\s*<div class=["\']notification-toggle["\']>\s*<i class=["\']fa[s]?[\s-]fa-bell["\']><\/i>\s*<\/div>\s*<div class=["\']notification-dropdown["\']>[\s\S]*?<\/div>\s*<\/div>/s'
    ];
    
    $replaced = false;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '<?php include(\'user_notification_bell.php\'); ?>', $content);
            $changes[] = "Replaced notification bell code using pattern";
            $replaced = true;
            break;
        }
    }
    
    // If not replaced by pattern, try to find notification section by comment
    if (!$replaced && strpos($content, '<!-- NOTIFICATIONS') !== false) {
        $notificationSection = '<!-- NOTIFICATIONS: only when logged in -->\n      <?php if ($user !== null): ?>\n        <?php include(\'user_notification_bell.php\'); ?>\n      <?php endif; ?>';
        
        // Try to replace the entire notification section
        $pattern = '/<!-- NOTIFICATIONS[^>]*-->\s*<\?php if \(\$user !== null\): \?>\s*.*?<\?php endif; \?>/s';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $notificationSection, $content);
            $changes[] = "Replaced notification section using comment marker";
            $replaced = true;
        }
    }
    
    // Save changes if the content was modified
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $updatedCount++;
        echo "<li class=\"success\">Updated {$page}: " . implode(", ", $changes) . "</li>";
    } else {
        echo "<li>No changes needed for {$page}</li>";
    }
}

echo "</ul>";

// Create a sample notification for testing
echo "<h2>Setting up sample notifications for testing</h2>";

// Check if the notifications table exists and has the required columns
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($tableCheckResult->num_rows > 0) {
    // Check if sender_photo column exists
    $columnCheckResult = $conn->query("SHOW COLUMNS FROM notifications LIKE 'sender_photo'");
    
    if ($columnCheckResult->num_rows == 0) {
        // Add the new columns for sender information
        $alterTableSql = "ALTER TABLE notifications 
            ADD COLUMN sender_id INT AFTER user_id,
            ADD COLUMN sender_name VARCHAR(100) AFTER sender_id,
            ADD COLUMN sender_photo VARCHAR(255) AFTER sender_name";
        
        if ($conn->query($alterTableSql)) {
            echo "<p class=\"success\">Successfully updated notifications table structure.</p>";
        } else {
            echo "<p class=\"error\">Error updating table structure: " . $conn->error . "</p>";
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
        echo "<p class=\"success\">Successfully created notifications table.</p>";
    } else {
        echo "<p class=\"error\">Error creating table: " . $conn->error . "</p>";
    }
}

// Get all patients to add sample notifications
$patientResult = $conn->query("SELECT id FROM patients LIMIT 10");
if ($patientResult->num_rows > 0) {
    $insertCount = 0;
    
    while ($patientRow = $patientResult->fetch_assoc()) {
        $patientId = $patientRow['id'];
        
        // Sample notifications for each patient
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
                'sender_name' => 'M&A Oida Dental Clinic',
                'sender_photo' => 'assets/photos/logo.jpg',
                'message' => 'Thank you for your recent visit! We hope you had a great experience.',
                'type' => 'message',
                'reference_id' => 102
            ]
        ];
        
        // Insert sample notifications for this patient
        foreach ($sampleNotifications as $notification) {
            // Check if a similar notification already exists
            $checkStmt = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = ? AND message = ? LIMIT 1");
            $checkStmt->bind_param("iss", $notification['user_id'], $notification['type'], $notification['message']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            // Only insert if no similar notification exists
            if ($checkResult->num_rows == 0) {
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
        }
    }
    
    echo "<p class=\"success\">Added {$insertCount} sample notifications for " . $patientResult->num_rows . " patients.</p>";
} else {
    echo "<p class=\"error\">No patients found in the database. Please add patients first.</p>";
}

echo "<div class=\"footer\">\n<p><strong>Summary:</strong> Updated {$updatedCount} pages successfully. {$errorCount} errors encountered.</p>\n<p><a href='index.php' class=\"btn\">Go to Home Page</a></p>\n</div>\n</body>\n</html>";
?>
