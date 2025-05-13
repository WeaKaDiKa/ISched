<?php
require_once('db.php');

$admin_id = 'ADM-001';
$password = 'marc147258';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "UPDATE admin_logins SET password = ? WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $hashed_password, $admin_id);

if ($stmt->execute()) {
    echo "Password updated successfully!";
} else {
    echo "Error updating password: " . $stmt->error;
}

$stmt->close();
$conn->close();
?> 