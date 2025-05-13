<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

require_once('db.php');

// Check if the script has already been run
$sql = "SHOW COLUMNS FROM appointments LIKE 'is_seen'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, so add it
    $sql = "ALTER TABLE appointments ADD COLUMN is_seen TINYINT(1) NOT NULL DEFAULT 0 AFTER status";
    if ($conn->query($sql)) {
        echo "Added is_seen column successfully<br>";
    } else {
        echo "Error adding is_seen column: " . $conn->error . "<br>";
        exit;
    }
    
    echo "Finished updating appointments table<br>";
} else {
    echo "is_seen column already exists<br>";
}

$conn->close();

// Add a link to go back to appointments page
echo '<br><a href="appointments.php">Go back to appointments</a>';
?> 