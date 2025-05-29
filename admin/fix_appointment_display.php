<?php
require_once('db.php');
require_once('session_handler.php');

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    echo "Unauthorized access. Please log in as admin.";
    exit;
}

// Get the appointment ID from the URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// If there's a specific appointment to update
if ($appointment_id > 0 && ($action == 'approve' || $action == 'cancel')) {
    $status = ($action == 'approve') ? 'booked' : 'cancelled';
    
    // Update the appointment status directly
    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $appointment_id);
    
    if ($stmt->execute()) {
        echo "Appointment #{$appointment_id} has been updated to {$status}.<br>";
    } else {
        echo "Error updating appointment: " . $conn->error . "<br>";
    }
}

// Fix any inconsistencies in the database
echo "<h2>Fixing Appointment Status Issues</h2>";

// 1. Fix any NULL statuses
$updateNullStatus = "UPDATE appointments SET status = 'pending' WHERE status IS NULL OR status = ''";
if ($conn->query($updateNullStatus)) {
    echo "✓ Updated NULL statuses to 'pending'.<br>";
} else {
    echo "✗ Error updating NULL statuses: " . $conn->error . "<br>";
}

// 2. Ensure all approved appointments have status 'booked'
$updateApprovedStatus = "UPDATE appointments SET status = 'booked' WHERE status = 'approved' OR status = 'upcoming'";
if ($conn->query($updateApprovedStatus)) {
    echo "✓ Standardized approved appointment statuses.<br>";
} else {
    echo "✗ Error updating approved statuses: " . $conn->error . "<br>";
}

// 3. Fix any inconsistencies in the database
$fixInconsistencies = "UPDATE appointments SET status = 'pending' WHERE status NOT IN ('pending', 'booked', 'rescheduled', 'cancelled')";
if ($conn->query($fixInconsistencies)) {
    echo "✓ Fixed inconsistent statuses.<br>";
} else {
    echo "✗ Error fixing inconsistent statuses: " . $conn->error . "<br>";
}

// 4. Check for duplicate appointments in different statuses
echo "<h3>Current Appointment Status Counts:</h3>";
$statusQuery = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
$result = $conn->query($statusQuery);
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['status']) . ": " . $row['count'] . " appointments</li>";
    }
    echo "</ul>";
}

// Add JavaScript to force a complete page refresh of appointments.php
echo "
<script>
function refreshAppointmentsPage() {
    // Force a complete page refresh with cache busting
    window.opener.location.href = 'appointments.php?refresh=' + new Date().getTime();
    // Close this window after a delay
    setTimeout(function() {
        window.close();
    }, 3000);
}
</script>

<div style='margin-top: 20px; padding: 10px; background-color: #f0f0f0; border-radius: 5px;'>
    <p>The database has been updated. This page will close in 3 seconds.</p>
    <p><button onclick='refreshAppointmentsPage()' style='padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;'>Refresh Appointments Page</button></p>
    <p><a href='appointments.php' target='_blank'>Or click here to open appointments page in a new tab</a></p>
</div>

<script>
// Auto-refresh the appointments page
refreshAppointmentsPage();
</script>
";
?>
