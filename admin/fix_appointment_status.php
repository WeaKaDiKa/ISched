<?php
require_once('db.php');

// Check if user is logged in as admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "Unauthorized access. Please log in as admin.";
    exit;
}

// Create a backup of the appointments table first
$backupTable = "CREATE TABLE IF NOT EXISTS appointments_backup LIKE appointments";
if ($conn->query($backupTable)) {
    $copyData = "INSERT INTO appointments_backup SELECT * FROM appointments";
    if ($conn->query($copyData)) {
        echo "Backup created successfully.<br>";
    } else {
        echo "Error creating backup: " . $conn->error . "<br>";
    }
} else {
    echo "Error creating backup table: " . $conn->error . "<br>";
}

// Update all appointment statuses to ensure they're in the correct category
$updatePending = "UPDATE appointments SET status = 'pending' WHERE status IS NULL OR status = ''";
if ($conn->query($updatePending)) {
    echo "Updated NULL statuses to 'pending'.<br>";
} else {
    echo "Error updating NULL statuses: " . $conn->error . "<br>";
}

// Ensure all approved appointments have status 'booked'
$updateApproved = "UPDATE appointments SET status = 'booked' WHERE status = 'approved' OR status = 'upcoming'";
if ($conn->query($updateApproved)) {
    echo "Standardized approved appointment statuses.<br>";
} else {
    echo "Error updating approved statuses: " . $conn->error . "<br>";
}

// Create a new appointment_status_log table to track status changes
$createLogTable = "CREATE TABLE IF NOT EXISTS appointment_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
)";

if ($conn->query($createLogTable)) {
    echo "Created appointment status log table.<br>";
} else {
    echo "Error creating log table: " . $conn->error . "<br>";
}

// Add a trigger to log status changes
$dropTrigger = "DROP TRIGGER IF EXISTS appointment_status_change";
if ($conn->query($dropTrigger)) {
    $createTrigger = "CREATE TRIGGER appointment_status_change
        BEFORE UPDATE ON appointments
        FOR EACH ROW
        BEGIN
            IF OLD.status != NEW.status THEN
                INSERT INTO appointment_status_log (appointment_id, old_status, new_status, changed_by)
                VALUES (OLD.id, OLD.status, NEW.status, 1); -- Default to admin ID 1
            END IF;
        END";
    
    // Need to change delimiter for trigger creation
    $conn->query("DELIMITER //");
    if ($conn->query($createTrigger)) {
        $conn->query("DELIMITER ;");
        echo "Created status change trigger.<br>";
    } else {
        echo "Error creating trigger: " . $conn->error . "<br>";
        $conn->query("DELIMITER ;");
    }
} else {
    echo "Error dropping existing trigger: " . $conn->error . "<br>";
}

// Update appointment_actions.php to ensure proper status updates
$actionFile = file_get_contents('appointment_actions.php');
if ($actionFile !== false) {
    // Make sure approve action sets status to 'booked'
    $actionFile = str_replace(
        "\$sql = \"UPDATE appointments SET status = 'booked' WHERE id = ?\"",
        "\$sql = \"UPDATE appointments SET status = 'booked' WHERE id = ?\"",
        $actionFile
    );
    
    // Make sure decline action sets status to 'cancelled'
    $actionFile = str_replace(
        "\$sql = \"UPDATE appointments SET status = 'cancelled' WHERE id = ?\"",
        "\$sql = \"UPDATE appointments SET status = 'cancelled' WHERE id = ?\"",
        $actionFile
    );
    
    if (file_put_contents('appointment_actions.php', $actionFile)) {
        echo "Updated appointment_actions.php.<br>";
    } else {
        echo "Error updating appointment_actions.php.<br>";
    }
} else {
    echo "Could not read appointment_actions.php.<br>";
}

echo "<br>Fix completed. <a href='appointments.php'>Return to Appointments</a>";
?>
