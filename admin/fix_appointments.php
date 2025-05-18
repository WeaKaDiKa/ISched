<?php
require_once('db.php');
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

// Check and fix table structure
$sql = "SHOW TABLES LIKE 'appointments'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Create appointments table if it doesn't exist
    $sql = "CREATE TABLE appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference_number VARCHAR(20),
        patient_id INT,
        services VARCHAR(255),
        appointment_date DATE,
        appointment_time TIME,
        clinic_branch VARCHAR(255) DEFAULT 'Maligaya Park Branch',
        status VARCHAR(20) DEFAULT 'pending',
        is_seen TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_ref (reference_number)
    )";
    
    if ($conn->query($sql)) {
        echo "Created appointments table successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Add test appointment if table is empty
$sql = "SELECT COUNT(*) as count FROM appointments";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Add a test appointment
    $sql = "INSERT INTO appointments (
        reference_number,
        patient_id,
        services,
        appointment_date,
        appointment_time,
        status
    ) VALUES (
        'APP-2024-00001',
        1,
        'General Consultation',
        CURRENT_DATE(),
        '09:00:00',
        'pending'
    )";
    
    if ($conn->query($sql)) {
        echo "Added test appointment successfully<br>";
    } else {
        echo "Error adding test appointment: " . $conn->error . "<br>";
    }
}

// Ensure all required columns exist
$required_columns = [
    ['name' => 'reference_number', 'type' => 'VARCHAR(20)'],
    ['name' => 'patient_id', 'type' => 'INT'],
    ['name' => 'services', 'type' => 'VARCHAR(255)'],
    ['name' => 'appointment_date', 'type' => 'DATE'],
    ['name' => 'appointment_time', 'type' => 'TIME'],
    ['name' => 'clinic_branch', 'type' => 'VARCHAR(255)'],
    ['name' => 'status', 'type' => 'VARCHAR(20)'],
    ['name' => 'is_seen', 'type' => 'TINYINT(1)']
];

foreach ($required_columns as $column) {
    $sql = "SHOW COLUMNS FROM appointments LIKE '{$column['name']}'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE appointments ADD COLUMN {$column['name']} {$column['type']}";
        if ($conn->query($sql)) {
            echo "Added {$column['name']} column successfully<br>";
        } else {
            echo "Error adding {$column['name']} column: " . $conn->error . "<br>";
        }
    }
}

// Fix any NULL statuses
$sql = "UPDATE appointments SET status = 'pending' WHERE status IS NULL";
$conn->query($sql);

// Fix any missing reference numbers
$sql = "SELECT id FROM appointments WHERE reference_number IS NULL";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ref = sprintf("APP-%d-%05d", date('Y'), $row['id']);
        $sql = "UPDATE appointments SET reference_number = '$ref' WHERE id = {$row['id']}";
        $conn->query($sql);
    }
    echo "Fixed missing reference numbers<br>";
}

$conn->close();

echo "<br><a href='appointments.php'>Go back to appointments</a>";
?> 