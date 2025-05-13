<?php
// Direct script to fix appointments table without requiring login
// Include database connection
require_once('db.php');

echo "<h2>Fixing Appointments Table</h2>";

// Check if is_seen column exists
$sql = "SHOW COLUMNS FROM appointments LIKE 'is_seen'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Add is_seen column
    $sql = "ALTER TABLE appointments ADD COLUMN is_seen TINYINT(1) NOT NULL DEFAULT 0 AFTER status";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green'>✓ Added is_seen column successfully</p>";
    } else {
        echo "<p style='color:red'>✗ Error adding is_seen column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:blue'>ℹ is_seen column already exists</p>";
}

// Check all appointments table columns
echo "<h3>Current Appointments Table Structure:</h3>";
$sql = "DESCRIBE appointments";
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
}

// Close connection
$conn->close();

echo "<br><a href='appointments.php' style='display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Go back to appointments</a>";
?>
