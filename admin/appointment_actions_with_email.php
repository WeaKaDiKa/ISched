<?php
require_once('db.php');
require_once('email_functions.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointment_id = $_POST['appointment_id'] ?? '';
    if ($action && $appointment_id) {
        // Get patient ID for the appointment
        $patientStmt = $conn->prepare("SELECT patient_id, appointment_date, appointment_time FROM appointments WHERE id = ?");
        $patientStmt->bind_param('i', $appointment_id);
        $patientStmt->execute();
        $patientResult = $patientStmt->get_result();
        $appointmentData = $patientResult->fetch_assoc();
        $patientId = $appointmentData['patient_id'] ?? 0;
        
        // Get patient email and name for sending notifications
        $patientEmail = '';
        $patientName = '';
        if ($patientId > 0) {
            $patientDataStmt = $conn->prepare("SELECT email, CONCAT(first_name, ' ', last_name) AS full_name FROM patients WHERE id = ?");
            $patientDataStmt->bind_param('i', $patientId);
            $patientDataStmt->execute();
            $patientDataResult = $patientDataStmt->get_result();
            $patientData = $patientDataResult->fetch_assoc();
            if ($patientData) {
                $patientEmail = $patientData['email'] ?? '';
                $patientName = $patientData['full_name'] ?? '';
            }
        }
        
        if ($action === 'approve') {
            // First check if this appointment already exists with a different status
            $checkDuplicateStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE id != ? AND reference_number = (SELECT reference_number FROM appointments WHERE id = ?)");
            $checkDuplicateStmt->bind_param('ii', $appointment_id, $appointment_id);
            $checkDuplicateStmt->execute();
            $duplicateResult = $checkDuplicateStmt->get_result();
            $duplicateData = $duplicateResult->fetch_assoc();
            
            if ($duplicateData && $duplicateData['count'] > 0) {
                // Delete any duplicates with the same reference number
                $deleteDuplicatesStmt = $conn->prepare("DELETE FROM appointments WHERE id != ? AND reference_number = (SELECT reference_number FROM appointments WHERE id = ?)");
                $deleteDuplicatesStmt->bind_param('ii', $appointment_id, $appointment_id);
                $deleteDuplicatesStmt->execute();
            }
            
            $sql = "UPDATE appointments SET status = 'booked' WHERE id = ?";
            
            // Add notification for the user if patient ID is valid
            if ($patientId > 0) {
                $appointmentDate = date('F j, Y', strtotime($appointmentData['appointment_date']));
                $appointmentTime = $appointmentData['appointment_time'];
                $message = "Your appointment on {$appointmentDate} at {$appointmentTime} has been approved by the admin."; 
                
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
                }
                
                // Insert notification
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'appointment', ?)");
                $notifStmt->bind_param('isi', $patientId, $message, $appointment_id);
                $notifStmt->execute();
                
                // Send email notification if patient email is available
                if (!empty($patientEmail)) {
                    $formattedDate = date('F j, Y', strtotime($appointmentData['appointment_date']));
                    send_appointment_approval_email($patientEmail, $patientName, $formattedDate, $appointmentData['appointment_time']);
                }
            }
        } elseif ($action === 'decline') {
            // First check if this appointment already exists with a different status
            $checkDuplicateStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE id != ? AND reference_number = (SELECT reference_number FROM appointments WHERE id = ?)");
            $checkDuplicateStmt->bind_param('ii', $appointment_id, $appointment_id);
            $checkDuplicateStmt->execute();
            $duplicateResult = $checkDuplicateStmt->get_result();
            $duplicateData = $duplicateResult->fetch_assoc();
            
            if ($duplicateData && $duplicateData['count'] > 0) {
                // Delete any duplicates with the same reference number
                $deleteDuplicatesStmt = $conn->prepare("DELETE FROM appointments WHERE id != ? AND reference_number = (SELECT reference_number FROM appointments WHERE id = ?)");
                $deleteDuplicatesStmt->bind_param('ii', $appointment_id, $appointment_id);
                $deleteDuplicatesStmt->execute();
            }
            
            $sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
            
            // Add notification for declined appointment
            if ($patientId > 0) {
                $reason = $_POST['reason'] ?? 'No reason provided';
                $appointmentDate = date('F j, Y', strtotime($appointmentData['appointment_date']));
                $appointmentTime = $appointmentData['appointment_time'];
                $message = "Your appointment on {$appointmentDate} at {$appointmentTime} has been declined. Reason: {$reason}"; 
                
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
                }
                
                // Insert notification
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, reference_id) VALUES (?, ?, 'appointment', ?)");
                $notifStmt->bind_param('isi', $patientId, $message, $appointment_id);
                $notifStmt->execute();
                
                // Send email notification if patient email is available
                if (!empty($patientEmail)) {
                    $formattedDate = date('F j, Y', strtotime($appointmentData['appointment_date']));
                    send_appointment_cancellation_email($patientEmail, $patientName, $formattedDate, $appointmentData['appointment_time'], $reason);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            exit;
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $appointment_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit;
    }
}
echo json_encode(['success' => false, 'message' => 'Invalid request.']); 
?>
