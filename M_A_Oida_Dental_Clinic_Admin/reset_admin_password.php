<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_clinic";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_id = 'ADM-001';
$new_password = 'marc147258';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$sql = "UPDATE admin_logins SET password = ? WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $hashed_password, $admin_id);

if ($stmt->execute()) {
    echo "Password updated successfully! You can now log in with:<br>";
    echo "Admin ID: " . htmlspecialchars($admin_id) . "<br>";
    echo "Password: " . htmlspecialchars($new_password);
} else {
    echo "Error updating password: " . $stmt->error;
}

$stmt->close();
$conn->close();
?> 