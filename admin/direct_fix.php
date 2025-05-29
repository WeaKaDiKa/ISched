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
    <title>Direct Appointment Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .container { max-width: 800px; margin: 0 auto; }
        .btn { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Direct Fix for Duplicate Appointments</h1>';

// STEP 1: Get all appointment IDs
$allAppointments = [];
$query = "SELECT id, reference_number, status FROM appointments";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allAppointments[] = $row;
    }
    echo '<p>Found ' . count($allAppointments) . ' total appointments in the database.</p>';
} else {
    echo '<p class="error">Error retrieving appointments: ' . $conn->error . '</p>';
}

// STEP 2: Find duplicate appointments (same reference number but different statuses)
$duplicates = [];
$refNumbers = [];
foreach ($allAppointments as $appt) {
    $refNum = $appt['reference_number'];
    if (!isset($refNumbers[$refNum])) {
        $refNumbers[$refNum] = [];
    }
    $refNumbers[$refNum][] = $appt;
}

foreach ($refNumbers as $refNum => $appts) {
    if (count($appts) > 1) {
        $duplicates[$refNum] = $appts;
    }
}

// STEP 3: Fix duplicate appointments
if (count($duplicates) > 0) {
    echo '<div class="card">
        <h3>Found ' . count($duplicates) . ' duplicate appointments to fix:</h3>
        <table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">
        <tr><th>Reference Number</th><th>ID</th><th>Current Status</th><th>Action</th></tr>';
    
    foreach ($duplicates as $refNum => $appts) {
        $hasApproved = false;
        $hasPending = false;
        
        // Check if one is approved and one is pending
        foreach ($appts as $appt) {
            if ($appt['status'] == 'booked' || $appt['status'] == 'approved') {
                $hasApproved = true;
            }
            if ($appt['status'] == 'pending' || $appt['status'] == null) {
                $hasPending = true;
            }
        }
        
        foreach ($appts as $appt) {
            echo "<tr>
                <td>{$refNum}</td>
                <td>{$appt['id']}</td>
                <td>{$appt['status']}</td>
                <td>";
            
            // If we have both approved and pending, and this one is pending, mark it for deletion
            if ($hasApproved && $hasPending && ($appt['status'] == 'pending' || $appt['status'] == null)) {
                // Delete this duplicate
                $deleteStmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
                $deleteStmt->bind_param('i', $appt['id']);
                if ($deleteStmt->execute()) {
                    echo "<span class='success'>Deleted duplicate</span>";
                } else {
                    echo "<span class='error'>Error deleting: {$conn->error}</span>";
                }
            } 
            // If this is approved but we have a duplicate, make sure it's status is 'booked'
            else if ($hasApproved && ($appt['status'] == 'approved' || $appt['status'] == 'upcoming')) {
                $updateStmt = $conn->prepare("UPDATE appointments SET status = 'booked' WHERE id = ?");
                $updateStmt->bind_param('i', $appt['id']);
                if ($updateStmt->execute()) {
                    echo "<span class='success'>Updated to 'booked'</span>";
                } else {
                    echo "<span class='error'>Error updating: {$conn->error}</span>";
                }
            }
            else {
                echo "No action needed";
            }
            
            echo "</td></tr>";
        }
    }
    echo '</table></div>';
} else {
    echo '<p>No duplicate appointments found.</p>';
}

// STEP 4: Update all appointments with status 'approved' to 'booked'
$updateApproved = "UPDATE appointments SET status = 'booked' WHERE status = 'approved' OR status = 'upcoming'";
if ($conn->query($updateApproved)) {
    echo '<p class="success">Successfully updated all approved appointments to status "booked"</p>';
} else {
    echo '<p class="error">Error updating approved appointments: ' . $conn->error . '</p>';
}

// STEP 5: Check for any appointments that might be in multiple sections
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
