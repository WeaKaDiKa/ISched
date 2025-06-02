<?php
require_once('db.php');

// This script creates notifications for upcoming appointments
// It should be run via a cron job or when the admin dashboard is loaded

// Check if the admin_notifications table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
if ($tableCheckResult->num_rows == 0) {
    // Create admin_notifications table if it doesn't exist
    $createTableSql = "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT,
        reference_data TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTableSql);
} else {
    // Check if reference_data column exists
    $columnCheckResult = $conn->query("SHOW COLUMNS FROM admin_notifications LIKE 'reference_data'");
    if ($columnCheckResult->num_rows == 0) {
        // Add reference_data column if it doesn't exist
        $alterTableSql = "ALTER TABLE admin_notifications ADD COLUMN reference_data TEXT AFTER reference_id";
        $conn->query($alterTableSql);
    }
}

// Get today's date
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Find upcoming appointments for today and tomorrow that don't already have notifications
// Include both pending and approved appointments
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.services, a.clinic_branch, 
                p.first_name, p.last_name, p.id as patient_id, a.status,
                d.first_name as doctor_first_name, d.last_name as doctor_last_name
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        WHERE (a.appointment_date = ? OR a.appointment_date = ?)
        AND (a.status = 'pending' OR a.status = 'approved')
        AND a.id NOT IN (
            SELECT reference_id FROM admin_notifications 
            WHERE (type = 'upcoming_appointment_today' OR type = 'upcoming_appointment_tomorrow') AND reference_id IS NOT NULL
        )
        ORDER BY a.appointment_date, a.appointment_time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $today, $tomorrow);
$stmt->execute();
$result = $stmt->get_result();

$notificationsCreated = 0;

while ($appointment = $result->fetch_assoc()) {
    // Format the date for display
    $appointmentDate = date('F j, Y', strtotime($appointment['appointment_date']));
    $isToday = ($appointment['appointment_date'] == $today);
    
    // Get doctor name if available
    $doctorName = '';
    if (!empty($appointment['doctor_first_name']) && !empty($appointment['doctor_last_name'])) {
        $doctorName = " with Dr. {$appointment['doctor_first_name']} {$appointment['doctor_last_name']}";
    }
    
    // Create notification message with status info
    $statusInfo = ucfirst($appointment['status']);
    $statusIcon = ($appointment['status'] == 'approved') ? '✅' : '⏳';
    
    if ($isToday) {
        $notificationMessage = "TODAY {$statusIcon}: {$appointment['first_name']} {$appointment['last_name']} has a {$statusInfo} appointment at {$appointment['appointment_time']} in {$appointment['clinic_branch']}{$doctorName}.";        
        $notificationType = "upcoming_appointment_today";
    } else {
        $notificationMessage = "TOMORROW {$statusIcon}: {$appointment['first_name']} {$appointment['last_name']} has a {$statusInfo} appointment at {$appointment['appointment_time']} in {$appointment['clinic_branch']}{$doctorName}.";        
        $notificationType = "upcoming_appointment_tomorrow";
    }
    
    // Create reference data (JSON with appointment_id and patient_id)
    $referenceData = json_encode([
        'appointment_id' => $appointment['id'],
        'patient_id' => $appointment['patient_id']
    ]);
    
    // Insert notification with reference data
    $insertSql = "INSERT INTO admin_notifications (message, type, reference_id, reference_data) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("ssis", $notificationMessage, $notificationType, $appointment['id'], $referenceData);
    
    if ($insertStmt->execute()) {
        $notificationsCreated++;
    }
}

// Return the number of notifications created if this is called via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode(['success' => true, 'notifications_created' => $notificationsCreated]);
}
