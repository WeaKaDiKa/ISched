<?php
require_once('db.php');
if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized access");
}

// Check if the script has already been run
$sql = "SHOW COLUMNS FROM appointments LIKE 'reference_number'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, so add it
    $sql = "ALTER TABLE appointments ADD COLUMN reference_number VARCHAR(20) AFTER id";
    if ($conn->query($sql)) {
        echo "Added reference_number column successfully<br>";
    } else {
        echo "Error adding reference_number column: " . $conn->error . "<br>";
        exit;
    }

    // Update existing appointments that don't have a reference number
    $sql = "SELECT id FROM appointments WHERE reference_number IS NULL";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Generate reference number: APP-YYYY-XXXXX where XXXXX is the ID padded with zeros
            $referenceNumber = sprintf("APP-%d-%05d", date('Y'), $row['id']);
            
            // Update the appointment
            $updateSql = "UPDATE appointments SET reference_number = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $referenceNumber, $row['id']);
            
            if ($stmt->execute()) {
                echo "Updated appointment ID {$row['id']} with reference number {$referenceNumber}<br>";
            } else {
                echo "Error updating appointment ID {$row['id']}: " . $stmt->error . "<br>";
            }
            $stmt->close();
        }
        echo "Finished updating appointments<br>";
    } else {
        echo "No appointments need updating<br>";
    }
} else {
    echo "Reference number column already exists<br>";
}

// Add a unique index to prevent duplicate reference numbers
$sql = "SHOW INDEX FROM appointments WHERE Key_name = 'reference_number'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE appointments ADD UNIQUE INDEX reference_number (reference_number)";
    if ($conn->query($sql)) {
        echo "Added unique index on reference_number column<br>";
    } else {
        echo "Error adding unique index: " . $conn->error . "<br>";
    }
}

$conn->close();

// Add a link to go back to appointments page
echo '<br><a href="appointments.php">Go back to appointments</a>';
?> 