<?php
require_once('db.php');

$admin_id = 'ADM-001';
$new_name = 'Marc Germine Ganan'; // Change this to your desired name
$new_first_name = 'Marc'; // Change this to your desired first name
$new_last_name = 'Ganan'; // Change this to your desired last name

$sql = "UPDATE admin_logins SET name = ?, first_name = ?, last_name = ? WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $new_name, $new_first_name, $new_last_name, $admin_id);

if ($stmt->execute()) {
    echo "Name updated successfully!<br>";
    echo "Your new name is: " . htmlspecialchars($new_name) . "<br>";
    echo "First Name: " . htmlspecialchars($new_first_name) . "<br>";
    echo "Last Name: " . htmlspecialchars($new_last_name);
} else {
    echo "Error updating name: " . $stmt->error;
}

$stmt->close();
$conn->close();
?> 