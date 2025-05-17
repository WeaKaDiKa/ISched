<?php
require_once('db.php');

echo "<h2>Appointments Table Structure:</h2>";
$sql = "DESCRIBE appointments";
$result = $conn->query($sql);

if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "Error getting table structure: " . $conn->error;
}

echo "<h2>Sample Appointments Data:</h2>";
$sql = "SELECT * FROM appointments LIMIT 5";
$result = $conn->query($sql);

if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "Error getting appointments data: " . $conn->error;
}

echo "<h2>Test Query:</h2>";
$sql = "SELECT a.*, p.first_name, p.middle_name, p.last_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE (a.status = 'pending' OR a.status IS NULL) 
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$result = $conn->query($sql);

if ($result) {
    echo "Found " . $result->num_rows . " pending appointments<br>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "Error running test query: " . $conn->error;
}

$conn->close();
?>