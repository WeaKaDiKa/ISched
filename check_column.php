<?php
//require_once('db.php');

// Check if doctor_id column exists in appointments table
$query = "SELECT COLUMN_NAME 
          FROM INFORMATION_SCHEMA.COLUMNS 
          WHERE TABLE_SCHEMA = 'dental_clinic' 
          AND TABLE_NAME = 'appointments' 
          AND COLUMN_NAME = 'doctor_id'";

$result = $conn->query($query);

if ($result) {
    if ($result->num_rows > 0) {
        echo "doctor_id column exists in the appointments table.";
    } else {
        echo "doctor_id column does NOT exist in the appointments table.";

        // Show all columns in the table
        echo "<h3>Available columns in appointments table:</h3>";
        $columnsQuery = "SELECT COLUMN_NAME 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = 'dental_clinic' 
                         AND TABLE_NAME = 'appointments'";

        $columnsResult = $conn->query($columnsQuery);

        if ($columnsResult && $columnsResult->num_rows > 0) {
            echo "<ul>";
            while ($row = $columnsResult->fetch_assoc()) {
                echo "<li>" . $row['COLUMN_NAME'] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "Error getting columns: " . $conn->error;
        }
    }
} else {
    echo "Error executing query: " . $conn->error;
}
?>