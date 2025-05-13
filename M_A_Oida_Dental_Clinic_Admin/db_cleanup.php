<?php
// Database Cleanup and Optimization Script
require_once('db.php');

echo "<h1>Database Cleanup and Optimization</h1>";

// Function to execute SQL safely and report results
function executeSql($conn, $sql, $description) {
    echo "<h3>$description</h3>";
    echo "<pre>$sql</pre>";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green'>✓ Success</p>";
        return true;
    } else {
        echo "<p style='color:red'>✗ Error: " . $conn->error . "</p>";
        return false;
    }
}

// 1. Fix appointments table structure
echo "<h2>Fixing Appointments Table</h2>";

// Check if is_seen column exists and add if missing
$sql = "SHOW COLUMNS FROM appointments LIKE 'is_seen'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    executeSql($conn, 
        "ALTER TABLE appointments ADD COLUMN is_seen TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
        "Adding is_seen column to appointments table");
} else {
    echo "<p>✓ is_seen column already exists</p>";
}

// 2. Optimize tables
echo "<h2>Optimizing Database Tables</h2>";

// Get all tables
$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        executeSql($conn, "OPTIMIZE TABLE $table", "Optimizing table: $table");
    }
}

// 3. Add indexes for better performance
echo "<h2>Adding Performance Indexes</h2>";

// Check if patient_id index exists in appointments
$sql = "SHOW INDEX FROM appointments WHERE Key_name = 'idx_patient_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    executeSql($conn, 
        "ALTER TABLE appointments ADD INDEX idx_patient_id (patient_id)",
        "Adding index on patient_id in appointments table");
}

// Check if status index exists in appointments
$sql = "SHOW INDEX FROM appointments WHERE Key_name = 'idx_status'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    executeSql($conn, 
        "ALTER TABLE appointments ADD INDEX idx_status (status)",
        "Adding index on status in appointments table");
}

// 4. Show current structure of key tables
echo "<h2>Current Table Structures</h2>";

$tables = ['appointments', 'patients', 'reviews', 'admin_users'];
foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    $sql = "DESCRIBE $table";
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Table $table does not exist or cannot be accessed</p>";
    }
}

// Close connection
$conn->close();

echo "<br><a href='dashboard.php' style='display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Return to Dashboard</a>";
?>
