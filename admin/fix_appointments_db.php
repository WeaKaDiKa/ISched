<?php
require_once('db.php');

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    echo "Unauthorized access. Please log in as admin.";
    exit;
}

// Update NULL statuses to 'pending'
$updateNullStatus = "UPDATE appointments SET status = 'pending' WHERE status IS NULL OR status = ''";
if ($conn->query($updateNullStatus)) {
    echo "Updated NULL statuses to 'pending'.<br>";
} else {
    echo "Error updating NULL statuses: " . $conn->error . "<br>";
}

// Ensure all approved appointments have status 'booked'
$updateApprovedStatus = "UPDATE appointments SET status = 'booked' WHERE status = 'approved' OR status = 'upcoming'";
if ($conn->query($updateApprovedStatus)) {
    echo "Standardized approved appointment statuses.<br>";
} else {
    echo "Error updating approved statuses: " . $conn->error . "<br>";
}

// Fix any inconsistencies in the database
$fixInconsistencies = "UPDATE appointments SET status = 'pending' WHERE id IN (
    SELECT id FROM (
        SELECT a.id 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.status NOT IN ('pending', 'booked', 'rescheduled', 'cancelled')
    ) AS temp
)";

if ($conn->query($fixInconsistencies)) {
    echo "Fixed inconsistent statuses.<br>";
} else {
    echo "Error fixing inconsistent statuses: " . $conn->error . "<br>";
}

echo "<br>Database fix completed. <a href='appointments.php'>Return to Appointments</a>";
?>
