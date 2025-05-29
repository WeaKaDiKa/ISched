<?php
require 'db.php';

echo "<h2>Database Debug Information</h2>";

// Check if reviews table exists
echo "<h3>Checking reviews table:</h3>";
$tables = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($tables->num_rows > 0) {
    echo "<p style='color:green'>✓ Reviews table exists</p>";
    
    // Get table structure
    echo "<h3>Reviews table structure:</h3>";
    $structure = $conn->query("DESCRIBE reviews");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($field = $structure->fetch_assoc()) {
        echo "<tr>";
        foreach ($field as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Check review count
    $count = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $count_result = $count->fetch_assoc();
    echo "<p>Total reviews in database: <b>" . $count_result['total'] . "</b></p>";
    
    // Show sample data
    echo "<h3>Sample review data:</h3>";
    $sample = $conn->query("SELECT * FROM reviews ORDER BY date DESC LIMIT 5");
    if ($sample->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        $first = true;
        while ($row = $sample->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    echo "<th>" . htmlspecialchars($key) . "</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>No reviews found in the database.</p>";
    }
} else {
    echo "<p style='color:red'>✗ Reviews table does not exist!</p>";
}

// Check connection to patients table
echo "<h3>Checking patients table:</h3>";
$tables = $conn->query("SHOW TABLES LIKE 'patients'");
if ($tables->num_rows > 0) {
    echo "<p style='color:green'>✓ Patients table exists</p>";
    
    // Get patient count
    $count = $conn->query("SELECT COUNT(*) as total FROM patients");
    $count_result = $count->fetch_assoc();
    echo "<p>Total patients in database: <b>" . $count_result['total'] . "</b></p>";
} else {
    echo "<p style='color:red'>✗ Patients table does not exist!</p>";
}
