<?php
require_once('session.php');
require 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get user's profile picture path before deletion
    $stmt = $conn->prepare("SELECT profile_picture FROM patients WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check for and delete any notifications
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Delete appointments
    $stmt = $conn->prepare("DELETE FROM appointments WHERE patient_id = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete appointments: " . $conn->error);
    }

    // Delete any reviews/feedback
    $stmt = $conn->prepare("DELETE FROM reviews WHERE patient_id = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete reviews: " . $conn->error);
    }

    // Delete remember me tokens if they exist
    $stmt = $conn->prepare("DELETE FROM remember_me_tokens WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete remember me tokens: " . $conn->error);
    }

    // Delete patient record
    $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete patient record: " . $conn->error);
    }

    // Delete profile picture file if it exists
    if (!empty($user['profile_picture']) && $user['profile_picture'] != 'default.jpg') {
        $profile_pic_path = 'uploads/profiles/' . $user['profile_picture'];
        if (file_exists($profile_pic_path)) {
            unlink($profile_pic_path);
        }
    }

    // Commit transaction
    $conn->commit();

    // Clear all session data
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    session_destroy();

    // Redirect to homepage with success message
    header('Location: homepage.php?msg=account_deleted');
    exit();

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    // Write detailed error to log
    error_log("Account deletion failed: " . $e->getMessage());
    
    // Show error message or redirect with error
    echo "<div style='color: red; padding: 20px; background: #ffe6e6; border: 1px solid #ff9999; margin: 20px;'>";
    echo "<h2>Error Deleting Account</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Please contact the administrator for assistance.</p>";
    echo "<a href='profile.php' style='display: inline-block; padding: 10px 15px; background: #4a89dc; color: white; text-decoration: none; border-radius: 4px;'>Return to Profile</a>";
    echo "</div>";
    exit();
}