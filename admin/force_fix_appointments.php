<?php
require_once('db.php');
require_once('session_handler.php');

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Set page styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Fix Appointments Display</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .container { max-width: 800px; margin: 0 auto; }
        .btn { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fixing Appointment Display Issues</h1>';

// STEP 1: Force update all approved appointments to have status='booked'
$updateApproved = "UPDATE appointments SET status = 'booked' WHERE status IN ('approved', 'upcoming')";
if ($conn->query($updateApproved)) {
    echo '<p class="success">✓ Successfully updated all approved appointments to status "booked"</p>';
} else {
    echo '<p class="error">✗ Error updating approved appointments: ' . $conn->error . '</p>';
}

// STEP 2: Force update all pending appointments to have status='pending'
$updatePending = "UPDATE appointments SET status = 'pending' WHERE status IS NULL OR status = ''";
if ($conn->query($updatePending)) {
    echo '<p class="success">✓ Successfully updated all NULL status appointments to "pending"</p>';
} else {
    echo '<p class="error">✗ Error updating pending appointments: ' . $conn->error . '</p>';
}

// STEP 3: Force update all cancelled appointments to have status='cancelled'
$updateCancelled = "UPDATE appointments SET status = 'cancelled' WHERE status IN ('canceled', 'declined')";
if ($conn->query($updateCancelled)) {
    echo '<p class="success">✓ Successfully standardized all cancelled appointment statuses</p>';
} else {
    echo '<p class="error">✗ Error updating cancelled appointments: ' . $conn->error . '</p>';
}

// STEP 4: Check for any appointments that might be in multiple sections
echo '<div class="card">
    <h3>Current Appointment Status Counts:</h3>
    <table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">
    <tr><th>Status</th><th>Count</th></tr>';

$statusQuery = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
$result = $conn->query($statusQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['status'] ?: 'NULL') . "</td><td>" . $row['count'] . "</td></tr>";
    }
}
echo '</table></div>';

// STEP 5: Add a direct link to modify the appointments.php file
echo '<div class="card">
    <h3>Direct Fix for Appointments Display</h3>
    <p>This will update the appointments.php file to ensure approved appointments don\'t show in the pending section.</p>
    <form method="post" action="">
        <input type="hidden" name="fix_file" value="1">
        <button type="submit" class="btn">Apply Fix to appointments.php</button>
    </form>
</div>';

// Process the file fix if requested
if (isset($_POST['fix_file'])) {
    $file = 'appointments.php';
    $content = file_get_contents($file);
    
    if ($content !== false) {
        // Make sure the pending section only shows pending appointments
        $pendingPattern = '/WHERE\s+a\.status\s*=\s*\'pending\'/i';
        
        if (preg_match($pendingPattern, $content)) {
            echo '<p class="success">✓ The appointments.php file already has the correct query for pending appointments.</p>';
        } else {
            // Try to fix the file
            $fixedContent = preg_replace(
                '/WHERE\s+(?:\(a\.status\s*=\s*\'pending\'\s*OR\s*a\.status\s*IS\s*NULL\)\s*AND\s*\(a\.status\s*IS\s*NULL\s*OR\s*a\.status\s*NOT\s*IN\s*\(\'booked\',\s*\'upcoming\',\s*\'cancelled\'\)\)|a\.status\s*(?:!=|<>)\s*\'cancelled\')/i',
                "WHERE a.status = 'pending'",
                $content
            );
            
            if ($fixedContent !== $content && file_put_contents($file, $fixedContent)) {
                echo '<p class="success">✓ Successfully updated the appointments.php file!</p>';
            } else {
                echo '<p class="error">✗ Could not update the appointments.php file. Please check file permissions.</p>';
            }
        }
    } else {
        echo '<p class="error">✗ Could not read the appointments.php file.</p>';
    }
}

// STEP 6: Add a button to clear browser cache and refresh appointments page
echo '<div class="card">
    <h3>Refresh Appointments Page</h3>
    <p>Click the button below to refresh the appointments page with a cache-busting URL:</p>
    <button onclick="refreshPage()" class="btn">Refresh Appointments Page</button>
</div>

<script>
function refreshPage() {
    // Open appointments page with cache busting
    window.location.href = "appointments.php?refresh=" + new Date().getTime();
}
</script>
</div>
</body>
</html>';
?>
