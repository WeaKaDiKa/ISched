<?php
require_once('db.php');

// Check if the reschedule_reason column already exists
$result = $conn->query("SHOW COLUMNS FROM appointments LIKE 'reschedule_reason'");

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE appointments ADD COLUMN reschedule_reason VARCHAR(255) DEFAULT NULL";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Successfully added 'reschedule_reason' column to appointments table.</p>";
    } else {
        echo "<p>Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>'reschedule_reason' column already exists in appointments table.</p>";
}

echo "<p><a href='appointments.php'>Return to Appointments</a></p>";
