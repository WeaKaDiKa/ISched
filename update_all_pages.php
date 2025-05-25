<?php
// This script will update all main pages to include the new notification bell component

// Define the main pages to update
$mainPages = [
    'index.php',
    'services.php',
    'about.php', // Already updated
    'reviews.php',
    'contact.php',
    'profile.php',
    'bookings.php',
    'mybookings.php',
    'myreviews.php',
    'success.php'
];

// CSS link to add
$cssLink = "<link rel=\"stylesheet\" href=\"assets/css/notification.css\">";

// Old notification bell code to replace
$oldNotificationCode = [
    "<div class=\"notification-wrapper\">\s*<div class=\"notification-toggle\">\s*<i class=\"fa-solid fa-bell\"></i>\s*</div>\s*<div class=\"notification-dropdown\">\s*<p class=\"empty-message\">No notifications yet</p>\s*</div>\s*</div>",
    "<div class=\"notification-wrapper\">\s*<div class=\"notification-toggle\">\s*<i class=\"fas? fa-bell\"></i>\s*</div>\s*<div class=\"notification-dropdown\">\s*<p class=\"empty-message\">No notifications yet</p>\s*</div>\s*</div>"
];

// New notification bell code
$newNotificationCode = "<?php include('user_notification_bell.php'); ?>";

// Counter for successful updates
$updatedPages = 0;
$cssAdded = 0;
$notificationReplaced = 0;

echo "<h2>Updating all pages with the new notification bell component</h2>";

foreach ($mainPages as $page) {
    $filePath = __DIR__ . '/' . $page;
    
    if (!file_exists($filePath)) {
        echo "<p>File not found: {$page}</p>";
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $updated = false;
    
    // Add CSS link if not already present
    if (strpos($content, 'notification.css') === false) {
        // Find the position to insert the CSS link
        $pattern = '/<link[^>]*font-awesome[^>]*>\s*<\/head>/i';
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $position = $matches[0][1];
            $content = substr($content, 0, $position) . "\n  " . $cssLink . substr($content, $position);
            $cssAdded++;
            $updated = true;
        } else {
            $pattern = '/<\/head>/i';
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $position = $matches[0][1];
                $content = substr($content, 0, $position) . "\n  " . $cssLink . "\n" . substr($content, $position);
                $cssAdded++;
                $updated = true;
            }
        }
    }
    
    // Replace old notification bell code
    $notificationReplaced = false;
    foreach ($oldNotificationCode as $pattern) {
        if (preg_match('/' . $pattern . '/s', $content)) {
            $content = preg_replace('/' . $pattern . '/s', $newNotificationCode, $content);
            $notificationReplaced = true;
            $updated = true;
            break;
        }
    }
    
    // If notification code wasn't found with regex, try to find it manually
    if (!$notificationReplaced) {
        // Look for notification-wrapper div
        $notificationWrapperPos = strpos($content, 'notification-wrapper');
        if ($notificationWrapperPos !== false) {
            // Find the containing PHP if statement
            $phpIfStart = strrpos(substr($content, 0, $notificationWrapperPos), '<?php if');
            if ($phpIfStart !== false) {
                $phpIfEnd = strpos($content, '<?php endif; ?>', $notificationWrapperPos);
                if ($phpIfEnd !== false) {
                    $phpIfEnd += strlen('<?php endif; ?>');
                    $oldCode = substr($content, $phpIfStart, $phpIfEnd - $phpIfStart);
                    $newCode = "<?php if (\$user !== null): ?>\n        {$newNotificationCode}\n      <?php endif; ?>";
                    $content = str_replace($oldCode, $newCode, $content);
                    $notificationReplaced = true;
                    $updated = true;
                }
            }
        }
    }
    
    if ($notificationReplaced) {
        $notificationReplaced++;
    }
    
    // Save the updated content
    if ($updated && $content !== $originalContent) {
        file_put_contents($filePath, $content);
        $updatedPages++;
        echo "<p>Updated: {$page}</p>";
    } else {
        echo "<p>No changes needed for: {$page}</p>";
    }
}

echo "<p>Summary: Updated {$updatedPages} pages, added CSS to {$cssAdded} pages, replaced notification bell in {$notificationReplaced} pages.</p>";
echo "<p><a href='index.php'>Go to Home Page</a></p>";
?>
