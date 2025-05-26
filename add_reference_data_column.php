<?php
// Direct database connection instead of using db.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "isched";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add reference_data column to admin_notifications table if it doesn't exist
$checkColumnSql = "SHOW COLUMNS FROM admin_notifications LIKE 'reference_data'";
$checkResult = $conn->query($checkColumnSql);

if ($checkResult->num_rows == 0) {
    // Column doesn't exist, add it
    $alterTableSql = "ALTER TABLE admin_notifications ADD COLUMN reference_data TEXT AFTER reference_id";
    if ($conn->query($alterTableSql)) {
        echo "Successfully added reference_data column to admin_notifications table.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column reference_data already exists in admin_notifications table.";
}

$conn->close();
?>
