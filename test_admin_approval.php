<?php
// This is a test script to simulate an admin approving a booking
// It will create a notification for the user to test the notification bell functionality

require_once('session.php');
require_once('db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>You must be logged in to use this feature.</p>";
    echo "<p><a href='login.php'>Click here to login</a></p>";
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Check if notifications table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($tableCheckResult->num_rows == 0) {
    // Create notifications table if it doesn't exist
    $createTableSql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES patients(id) ON DELETE CASCADE
    )";
    $conn->query($createTableSql);
    echo "<p>Notifications table created.</p>";
} else {
    echo "<p>Notifications table already exists.</p>";
}

// Check if there's a specific appointment to test with
$appointmentId = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : null;

// If no appointment ID is provided, find the most recent appointment for this user
if (!$appointmentId) {
    $findAppointmentSql = "SELECT id, appointment_date, appointment_time FROM appointments WHERE patient_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($findAppointmentSql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
        $appointmentId = $appointment['id'];
        $appointmentDate = $appointment['appointment_date'];
        $appointmentTime = $appointment['appointment_time'];
    } else {
        echo "<p>No appointments found for this user. Please book an appointment first.</p>";
        echo "<p><a href='bookings.php'>Click here to book an appointment</a></p>";
        exit;
    }
} else {
    // Get appointment details
    $findAppointmentSql = "SELECT appointment_date, appointment_time FROM appointments WHERE id = ? AND patient_id = ?";
    $stmt = $conn->prepare($findAppointmentSql);
    $stmt->bind_param('ii', $appointmentId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
        $appointmentDate = $appointment['appointment_date'];
        $appointmentTime = $appointment['appointment_time'];
    } else {
        echo "<p>Appointment not found or does not belong to you.</p>";
        exit;
    }
}

// Format date and time for display
$formattedDate = date('F j, Y', strtotime($appointmentDate));

// Create a test notification
$message = "Your appointment on {$formattedDate} at {$appointmentTime} has been approved by the admin.";
$notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'appointment', ?)");
$notifStmt->bind_param('isi', $userId, $message, $appointmentId);

if ($notifStmt->execute()) {
    echo "<p>Test notification created successfully!</p>";
    echo "<p>Message: {$message}</p>";
    echo "<p>Go back to the <a href='index.php'>main page</a> and check the notification bell.</p>";
    
    // Also update the appointment status to simulate admin approval
    $updateStmt = $conn->prepare("UPDATE appointments SET status = 'booked' WHERE id = ?");
    $updateStmt->bind_param('i', $appointmentId);
    if ($updateStmt->execute()) {
        echo "<p>Appointment status updated to 'booked'.</p>";
    } else {
        echo "<p>Error updating appointment status: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Error creating test notification: " . $conn->error . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Admin Approval</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #4a89dc;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        a {
            color: #4a89dc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Test Admin Approval</h1>
    <p>This page simulates an admin approving your booking and creates a notification.</p>
    <p>You should now see a notification in the notification bell on the main pages.</p>
    <p><a href="index.php">Return to Home Page</a></p>
</body>
</html>
