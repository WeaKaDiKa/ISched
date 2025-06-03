<?php
require_once('session.php');
require 'db.php';

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($new_password !== $confirm_password) {
        header('Location: profile.php?error=mismatch&message=New+password+and+confirm+password+do+not+match!');
        exit;
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password_hash FROM patients WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user['password_hash'])) {
        header('Location: profile.php?error=incorrect&message=Current+Password+is+not+correct');
        exit;
    }
    
    // Update password in database
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update_sql = "UPDATE patients SET password_hash = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_password_hash, $user_id);
    
    if ($update_stmt->execute()) {
        header('Location: profile.php?success=password_updated&message=Your+password+has+been+successfully+updated!');
        exit;
    } else {
        header('Location: profile.php?error=db_error&message=Database+error.+Please+try+again.');
        exit;
    }
} else {
    // If not a POST request, redirect to profile
    header('Location: profile.php');
    exit;
}
?>
