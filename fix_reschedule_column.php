<?php
// Direct database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "isched_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Connection Successful</h2>";

// Try to add the column directly
$sql = "ALTER TABLE appointments ADD COLUMN reschedule_reason VARCHAR(255) DEFAULT NULL";

try {
    if ($conn->query($sql) === TRUE) {
        echo "<p>Successfully added 'reschedule_reason' column to appointments table.</p>";
    } else {
        echo "<p>Error adding column: " . $conn->error . "</p>";
        
        // Check if the table exists
        $tableCheck = "SHOW TABLES LIKE 'appointments'";
        $result = $conn->query($tableCheck);
        
        if ($result->num_rows > 0) {
            echo "<p>The 'appointments' table exists.</p>";
            
            // Show the current structure of the table
            $describeTable = "DESCRIBE appointments";
            $descResult = $conn->query($describeTable);
            
            if ($descResult->num_rows > 0) {
                echo "<h3>Current structure of appointments table:</h3>";
                echo "<table border='1'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                while($row = $descResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["Field"] . "</td>";
                    echo "<td>" . $row["Type"] . "</td>";
                    echo "<td>" . $row["Null"] . "</td>";
                    echo "<td>" . $row["Key"] . "</td>";
                    echo "<td>" . $row["Default"] . "</td>";
                    echo "<td>" . $row["Extra"] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>Could not get the structure of the appointments table.</p>";
            }
        } else {
            echo "<p>The 'appointments' table does not exist!</p>";
            
            // Show all tables in the database
            $showTables = "SHOW TABLES";
            $tablesResult = $conn->query($showTables);
            
            if ($tablesResult->num_rows > 0) {
                echo "<h3>Tables in the database:</h3>";
                echo "<ul>";
                
                while($row = $tablesResult->fetch_row()) {
                    echo "<li>" . $row[0] . "</li>";
                }
                
                echo "</ul>";
            } else {
                echo "<p>No tables found in the database.</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p>Exception: " . $e->getMessage() . "</p>";
}

echo "<p><a href='reschedule_appointment.php'>Return to Reschedule Appointment</a></p>";

$conn->close();
?>
