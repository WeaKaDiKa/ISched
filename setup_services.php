<?php
require_once 'db.php';

// Check if services table exists and has data
$checkQuery = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'services'";
$result = $conn->query($checkQuery);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo "<h2>Setting up services table...</h2>";
    
    // Read SQL file
    $sql = file_get_contents('sql/services_table.sql');
    
    // Execute multi query
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
            // Check for more results
        } while ($conn->more_results() && $conn->next_result());
        
        echo "<div style='color:green'>Services table created and populated successfully!</div>";
    } else {
        echo "<div style='color:red'>Error setting up services table: " . $conn->error . "</div>";
    }
} else {
    echo "<h2>Services table check</h2>";
    
    // Check if services table has data
    $checkDataQuery = "SELECT COUNT(*) as count FROM services";
    $dataResult = $conn->query($checkDataQuery);
    $dataRow = $dataResult->fetch_assoc();
    
    if ($dataRow['count'] > 0) {
        echo "<div style='color:green'>Services table exists and contains " . $dataRow['count'] . " services.</div>";
    } else {
        echo "<div style='color:orange'>Services table exists but is empty. Re-running setup...</div>";
        
        // Read SQL file (just the INSERT part)
        $insertSql = file_get_contents('sql/services_table.sql');
        // Extract the INSERT portion
        if (preg_match('/INSERT INTO `services`.*$/ms', $insertSql, $matches)) {
            $insertSql = $matches[0];
            
            if ($conn->multi_query($insertSql)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
                
                echo "<div style='color:green'>Services data added successfully!</div>";
            } else {
                echo "<div style='color:red'>Error adding services data: " . $conn->error . "</div>";
            }
        } else {
            echo "<div style='color:red'>Error finding INSERT statement in SQL file.</div>";
        }
    }
}

echo "<p><a href='bookings.php'>Return to Booking Form</a></p>";
