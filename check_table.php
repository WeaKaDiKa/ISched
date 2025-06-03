<?php
require 'db.php';

// Check if the patients table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'patients'");
if ($tableCheckResult->num_rows == 0) {
    echo "The patients table does not exist.";
    exit;
}

// Get the columns of the patients table
$columnsResult = $conn->query("SHOW COLUMNS FROM patients");
echo "<h2>Current columns in patients table:</h2>";
echo "<ul>";
while ($column = $columnsResult->fetch_assoc()) {
    echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
}
echo "</ul>";

// Check if otp and otp_expires columns exist
$otpColumnExists = false;
$otpExpiresColumnExists = false;
$columnsResult = $conn->query("SHOW COLUMNS FROM patients");
while ($column = $columnsResult->fetch_assoc()) {
    if ($column['Field'] == 'otp') {
        $otpColumnExists = true;
    }
    if ($column['Field'] == 'otp_expires') {
        $otpExpiresColumnExists = true;
    }
}

// Add missing columns if needed
echo "<h2>Adding missing columns:</h2>";
if (!$otpColumnExists) {
    $conn->query("ALTER TABLE patients ADD COLUMN otp VARCHAR(6) NULL");
    echo "Added otp column<br>";
}
if (!$otpExpiresColumnExists) {
    $conn->query("ALTER TABLE patients ADD COLUMN otp_expires DATETIME NULL");
    echo "Added otp_expires column<br>";
}

echo "<h2>Table structure after updates:</h2>";
$columnsResult = $conn->query("SHOW COLUMNS FROM patients");
echo "<ul>";
while ($column = $columnsResult->fetch_assoc()) {
    echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
}
echo "</ul>";

echo "<p>You can now return to the forgotpassword.php page and try again.</p>";
echo "<p><a href='forgotpassword.php'>Go to Forgot Password page</a></p>";
?>
