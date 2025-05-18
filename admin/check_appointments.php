<?php
require_once('db.php');

$sql = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
$result = $conn->query($sql);

if ($result) {
    echo "<h3>Appointment Status Counts:</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "Status: " . $row['status'] . " - Count: " . $row['count'] . "<br>";
    }
} else {
    echo "Error querying database: " . $conn->error;
}

echo "<h3>Recent Appointments:</h3>";
$sql = "SELECT * FROM appointments ORDER BY appointment_date DESC LIMIT 5";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . 
             " | Status: " . $row['status'] . 
             " | Date: " . $row['appointment_date'] . 
             " | Time: " . $row['appointment_time'] . 
             " | Patient ID: " . $row['patient_id'] . "<br>";
    }
} else {
    echo "Error querying database: " . $conn->error;
}

$conn->close();
