<?php
require_once('db.php');
require_once('mailfunction.php');
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

        if ($action === 'approve') {
            $sql = "UPDATE appointments SET status = 'booked' WHERE id = ?";

            // Add notification for the user if patient ID is valid
            if ($patientId > 0) {
                $appointmentDate = date('F j, Y', strtotime($appointmentData['appointment_date']));
                $appointmentTime = $appointmentData['appointment_time'];
                $message = "Your appointment on {$appointmentDate} at {$appointmentTime} has been approved by the admin.";


                $patientInfoStmt = $conn->prepare("SELECT email, CONCAT(first_name, ' ', last_name) AS name FROM patients WHERE id = ?");
                $patientInfoStmt->bind_param('i', $patientId);
                $patientInfoStmt->execute();
                $patientInfoResult = $patientInfoStmt->get_result();
                $patientInfo = $patientInfoResult->fetch_assoc();

                if ($patientInfo) {
                    $email = $patientInfo['email'];
                    $name = $patientInfo['name'];
                    $subject = 'Appointment Approved';
                    
                    // Send email notification for approved appointment
                    phpmailsend($email, $name, $subject, $message);
                }

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
            }
        } elseif ($action === 'decline') {
            $sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";

            // Add notification for declined appointment
            if ($patientId > 0) {
                $reason = $_POST['reason'] ?? 'No reason provided';
                $appointmentDate = date('F j, Y', strtotime($appointmentData['appointment_date']));
                $appointmentTime = $appointmentData['appointment_time'];
                $message = "Your appointment on {$appointmentDate} at {$appointmentTime} has been declined. Reason: {$reason}";


                $patientInfoStmt = $conn->prepare("SELECT email, CONCAT(first_name, ' ', last_name) AS name FROM patients WHERE id = ?");
                $patientInfoStmt->bind_param('i', $patientId);
                $patientInfoStmt->execute();
                $patientInfoResult = $patientInfoStmt->get_result();
                $patientInfo = $patientInfoResult->fetch_assoc();

                if ($patientInfo) {
                    $email = $patientInfo['email'];
                    $name = $patientInfo['name'];
                    $subject = 'Appointment Declined';
                    
                    // Send email notification for declined appointment
                    phpmailsend($email, $name, $subject, $message);
                }


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
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            exit;
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $appointment_id);
        if ($stmt->execute()) {
            $emailStatus = ' Email sent to patient.';
            // Email is now sent directly when patient info is retrieved
            echo json_encode(['success' => true, 'message' => 'Appointment updated.' . $emailStatus]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }


    }
}
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
