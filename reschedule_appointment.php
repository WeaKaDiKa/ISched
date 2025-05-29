<?php
require_once('session.php');
require_once('db.php');

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if we have an appointment ID to reschedule
if (!isset($_SESSION['reschedule_appointment_id'])) {
    header('Location: mybookings.php');
    exit;
}

$appointmentId = $_SESSION['reschedule_appointment_id'];
$userId = $_SESSION['user_id'];

// Get the appointment details
$query = "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.specialization 
          FROM appointments a 
          LEFT JOIN doctors d ON a.doctor_id = d.id
          WHERE a.id = ? AND a.patient_id = ? AND a.status = 'booked'";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $appointmentId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Invalid appointment or doesn't belong to this user
    unset($_SESSION['reschedule_appointment_id']);
    header('Location: mybookings.php');
    exit;
}

$appointment = $result->fetch_assoc();

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['appointment_date'] ?? '';
    $new_time = $_POST['appointment_time'] ?? '';
    $reschedule_reason = $_POST['reschedule_reason'] ?? '';
    
    if (empty($new_date) || empty($new_time)) {
        $error_message = "Please select both date and time for your appointment.";
    } else if (empty($reschedule_reason)) {
        $error_message = "Please provide a reason for rescheduling your appointment.";
    } else {
        // Validate that the selected date is not a weekend
        $day_of_week = date('w', strtotime($new_date));
        if ($day_of_week == 0 || $day_of_week == 6) {
            $error_message = "Weekend days (Saturday and Sunday) are not available for appointments. Please select a weekday.";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Step 1: Check if the new date/time slot is available
                $checkQuery = "SELECT id FROM appointments 
                               WHERE clinic_branch = ? AND appointment_date = ? AND appointment_time = ? 
                               AND status = 'booked'";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("sss", $appointment['clinic_branch'], $new_date, $new_time);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    throw new Exception("The selected time slot is already booked. Please choose another time.");
                }
                
                // First, check if reschedule_reason column exists
                $columnCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'reschedule_reason'");
                $hasRescheduleReason = $columnCheck->num_rows > 0;
                
                // Step 2: Create a new appointment with the same details but new date/time
                // Prepare the query based on whether reschedule_reason column exists
                if ($hasRescheduleReason) {
                    $insertQuery = "INSERT INTO appointments 
                                   (patient_id, clinic_branch, appointment_date, appointment_time, services, 
                                    health, doctor_id, status, parent_appointment_id,
                                    blood_type, medical_history, allergies, consent, reschedule_reason) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 'rescheduled', ?, ?, ?, ?, ?, ?)";
                } else {
                    $insertQuery = "INSERT INTO appointments 
                                   (patient_id, clinic_branch, appointment_date, appointment_time, services, 
                                    health, doctor_id, status, parent_appointment_id,
                                    blood_type, medical_history, allergies, consent) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 'rescheduled', ?, ?, ?, ?, ?)";
                }
                
                // Create individual variables for binding to prevent issues
                $patient_id = $appointment['patient_id'];
                $clinic_branch = $appointment['clinic_branch'];
                $services = $appointment['services'];
                $health = $appointment['health'];
                $doctor_id = $appointment['doctor_id'] ? $appointment['doctor_id'] : NULL;
                $parent_id = $appointmentId; // Set the original appointment as the parent
                $blood_type = $appointment['blood_type'];
                $medical_history = $appointment['medical_history'];
                $allergies = $appointment['allergies'];
                $consent = $appointment['consent'] ? 1 : 0;
                
                $insertStmt = $conn->prepare($insertQuery);
                
                // Bind parameters based on whether reschedule_reason column exists
                if ($hasRescheduleReason) {
                    $insertStmt->bind_param("isssssiisssis", 
                        $patient_id,
                        $clinic_branch,
                        $new_date,
                        $new_time,
                        $services,
                        $health,
                        $doctor_id,
                        $parent_id,
                        $blood_type,
                        $medical_history,
                        $allergies,
                        $consent,
                        $reschedule_reason
                    );
                } else {
                    $insertStmt->bind_param("isssssiiissi", 
                        $patient_id,
                        $clinic_branch,
                        $new_date,
                        $new_time,
                        $services,
                        $health,
                        $doctor_id,
                        $parent_id,
                        $blood_type,
                        $medical_history,
                        $allergies,
                        $consent
                    );
                }
                
                if (!$insertStmt->execute()) {
                    throw new Exception("Failed to create new appointment. Error: " . $conn->error);
                }
                
                // Get the ID of the newly created appointment
                $newAppointmentId = $conn->insert_id;
                
                // Step 3: Update the status of the original appointment to 'cancelled'
                $updateQuery = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("i", $appointmentId);
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update original appointment. Error: " . $conn->error);
                }
                
                // Log the status change for debugging
                error_log("Updated appointment ID $appointmentId status to 'rescheduled'");
                
                // Create admin notification about the rescheduled appointment
                $tableCheckResult = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
                if ($tableCheckResult->num_rows == 0) {
                    // Create admin_notifications table if it doesn't exist
                    $createTableSql = "CREATE TABLE IF NOT EXISTS admin_notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        message VARCHAR(255) NOT NULL,
                        type VARCHAR(50) NOT NULL,
                        reference_id INT,
                        is_read TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    $conn->query($createTableSql);
                }
                
                // Get user's name for the notification
                $userQuery = "SELECT CONCAT(first_name, ' ', last_name) as patient_name FROM patients WHERE id = ?";
                $userStmt = $conn->prepare($userQuery);
                $userStmt->bind_param("i", $userId);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $userData = $userResult->fetch_assoc();
                $patientName = $userData['patient_name'] ?? 'A patient';
                
                // Create notification message with reason
                $reasonText = !empty($reschedule_reason) ? $reschedule_reason : "No reason provided";
                $notificationMessage = "Patient {$patientName} has rescheduled their appointment to {$new_date} at {$new_time}.\n\nReason: {$reasonText}";
                
                // Insert admin notification
                $notifStmt = $conn->prepare("INSERT INTO admin_notifications (message, type, reference_id) VALUES (?, 'reschedule', ?)");
                $notifStmt->bind_param("si", $notificationMessage, $newAppointmentId);
                $notifStmt->execute();
                
                // If everything is successful, commit the transaction
                $conn->commit();
                
                $success_message = "Your appointment has been rescheduled successfully. Your new appointment ID is #" . $newAppointmentId;
                // Clear the session variable
                unset($_SESSION['reschedule_appointment_id']);
                
            } catch (Exception $e) {
                // Roll back the transaction if any part fails
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        }
    }
}

// Get available time slots for form
$timeSlots = [
    '10:00 AM', '11:00 AM', '1:00 PM', '2:00 PM',
    '3:00 PM', '4:00 PM', '5:00 PM', '6:00 PM',
    '7:00 PM'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - M&A Oida Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Basic reset and common styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        header {
            background: linear-gradient(to right, #1a76d2, #0d47a1);
            color: white;
            padding: 20px 0;
            text-align: center;
            position: relative;
        }
        
        h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
        }
        
        .back-btn:hover {
            text-decoration: underline;
        }
        
        .reschedule-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .appointment-details {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            width: 150px;
            font-weight: bold;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="date"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            background-color: white;
            color: #333;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            background-color: white;
            color: #333;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #1a76d2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: #0d47a1;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <header>
        <a href="mybookings.php" class="back-btn">Back</a>
        <h1>Reschedule Appointment</h1>
        <p>Change your appointment date and time</p>
    </header>
    
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="message success">
                <?php echo $success_message; ?>
                <p><a href="mybookings.php">Return to My Bookings</a></p>
            </div>
        <?php else: ?>
            <?php if (!empty($error_message)): ?>
                <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="reschedule-container">
                <h2>Current Appointment Details</h2>
                
                <div class="appointment-details">
                    <div class="detail-row">
                        <div class="detail-label">Appointment ID:</div>
                        <div class="detail-value">#<?php echo $appointment['id']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Current Date:</div>
                        <div class="detail-value"><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Current Time:</div>
                        <div class="detail-value"><?php echo $appointment['appointment_time']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Services:</div>
                        <div class="detail-value"><?php echo $appointment['services']; ?></div>
                    </div>
                    <?php if ($appointment['doctor_id']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Doctor:</div>
                            <div class="detail-value">
                                Dr. <?php echo $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']; ?> 
                                (<?php echo $appointment['specialization']; ?>)
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h2>Select New Date and Time</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="reschedule_reason">Reason for Rescheduling:</label>
                        <textarea id="reschedule_reason" name="reschedule_reason" rows="3" required placeholder="Please provide a reason for rescheduling your appointment..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_date">New Date:</label>
                        <input type="date" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                        <small class="form-text text-muted">Note: Weekend days (Saturday and Sunday) are not available for appointments.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_time">New Time:</label>
                        <select id="appointment_time" name="appointment_time" required>
                            <option value="">Select a time</option>
                            <?php foreach ($timeSlots as $time): ?>
                                <option value="<?php echo $time; ?>"><?php echo $time; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Reschedule Appointment</button>
                </form>

                <script>
                    // Function to disable weekend days (Saturday = 6, Sunday = 0)
                    function disableWeekends() {
                        const datePicker = document.getElementById('appointment_date');
                        
                        datePicker.addEventListener('input', function(e) {
                            const selectedDate = new Date(this.value);
                            const day = selectedDate.getUTCDay();
                            
                            // Check if the selected day is a weekend
                            if(day === 0 || day === 6) {
                                alert('Weekends are not available for appointments. Please select a weekday (Monday to Friday).');
                                this.value = '';
                            }
                        });
                    }
                    
                    // Run the function when the page loads
                    window.onload = disableWeekends;
                </script>
            </div>
        <?php endif; ?>
    </div>
    
    <footer style="background-color: #333; color: white; padding: 20px 0; text-align: center; margin-top: 30px;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> M&A Oida Dental Clinic. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 