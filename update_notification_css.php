<?php
// This script adds the notification CSS to all main user pages

// Define the main pages to update
$mainPages = [
    'index.php',
    'services.php',
    'reviews.php',
    'contact.php',
    'profile.php',
    'bookings.php',
    'mybookings.php',
    'myreviews.php'
];

$updatedCount = 0;

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Update Notification CSS</title>\n<style>\nbody { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }\n.success { color: green; }\n.error { color: red; }\n.header { background: #124085; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }\n.footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }\n.btn { display: inline-block; background: #124085; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; }\n</style>\n</head>\n<body>\n<div class=\"header\">\n<h1>Adding Notification CSS to All Pages</h1>\n</div>";

echo "<h2>Processing Pages:</h2>\n<ul>";

foreach ($mainPages as $page) {
    $filePath = __DIR__ . '/' . $page;
    
    if (!file_exists($filePath)) {
        echo "<li class=\"error\">Error: File not found - {$page}</li>";
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $updated = false;
    
    // Add notification CSS if not already present
    if (strpos($content, 'notification.css') === false) {
        // Try to add it after profile-icon.css
        if (strpos($content, 'profile-icon.css') !== false) {
            $content = str_replace(
                '<link rel="stylesheet" href="assets/css/profile-icon.css">',
                '<link rel="stylesheet" href="assets/css/profile-icon.css">\n  <link rel="stylesheet" href="assets/css/notification.css">',
                $content
            );
            $updated = true;
        } 
        // Or try to add it before font-awesome
        else if (preg_match('/<link[^>]*font-awesome[^>]*>/', $content)) {
            $content = preg_replace(
                '/(<link[^>]*font-awesome[^>]*>)/',
                '<link rel="stylesheet" href="assets/css/notification.css">\n  $1',
                $content
            );
            $updated = true;
        }
        // Or just add it before </head>
        else {
            $content = str_replace(
                '</head>',
                '  <link rel="stylesheet" href="assets/css/notification.css">\n</head>',
                $content
            );
            $updated = true;
        }
    }
    
    // Save the updated content
    if ($updated && $content !== $originalContent) {
        file_put_contents($filePath, $content);
        $updatedCount++;
        echo "<li class=\"success\">Updated: {$page} - Added notification CSS</li>";
    } else if (strpos($content, 'notification.css') !== false) {
        echo "<li>No changes needed for {$page} - Already has notification CSS</li>";
    } else {
        echo "<li class=\"error\">Could not update {$page}</li>";
    }
}

echo "</ul>";

echo "<div class=\"footer\">\n<p><strong>Summary:</strong> Added notification CSS to {$updatedCount} pages.</p>\n<p><a href='index.php' class=\"btn\">Go to Home Page</a></p>\n</div>\n</body>\n</html>";
?>
