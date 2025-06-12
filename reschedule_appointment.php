<?php
require_once('session.php');
require_once('db.php');
require_once('includes/profile_functions.php');
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
$query = "SELECT a.*
          FROM appointments a 
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
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} else {
    $success_message = "";
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
} else {
    $error_message = "";
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['appointment_date'] ?? '';
    $new_time_12h = $_POST['appointment_time'] ?? '';

    if (empty($new_date) || empty($new_time_12h)) {
        $error_message = "Please select both date and time for your appointment.";
    } else {

        if (!DateTime::createFromFormat('Y-m-d', $new_date)) {
            $error_message = "Invalid date format. Please use YYYY-MM-DD format.";
        } else {

            $day_of_week = date('w', strtotime($new_date));
            if ($day_of_week == 0 || $day_of_week == 6) {
                $error_message = "Weekend days (Saturday and Sunday) are not available for appointments. Please select a weekday.";
            } else {

                if (!preg_match('/^(1[0-2]|0?[1-9]):[0-5][0-9] (AM|PM)$/i', $new_time_12h)) {
                    $error_message = "Invalid time format. Please use HH:MM AM/PM format (e.g., 10:00 AM).";
                } else {

                    $new_time_24h = date("H:i", strtotime($new_time_12h));

                    $conn->begin_transaction();

                    try {
                        $checkQuery = "SELECT id FROM appointments 
                                      WHERE appointment_date = ? AND appointment_time = ? 
                                      AND status = 'booked'";
                        $checkStmt = $conn->prepare($checkQuery);
                        $checkStmt->bind_param("ss", $new_date, $new_time_24h);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result();

                        if ($checkResult->num_rows > 0) {
                            throw new Exception("The selected time slot is already booked. Please choose another time.");
                        }
                        $insertQuery = "INSERT INTO appointments 
                                        (patient_id, appointment_date, appointment_time, services, 
                                         status, parent_appointment_id, consent) 
                                        VALUES (?, ?, ?, ?, 'rescheduled', ?, ?)";

                        // Prepare values for binding
                        $patient_id = $appointment['patient_id'];
                        $services = $appointment['services'];
                        $parent_id = $appointmentId;
                        $consent = $appointment['consent'] ? 1 : 0;

                        $insertStmt = $conn->prepare($insertQuery);
                        $insertStmt->bind_param(
                            "isssii",
                            $patient_id,
                            $new_date,
                            $new_time_24h,
                            $services,
                            $parent_id,
                            $consent
                        );

                        if (!$insertStmt->execute()) {
                            throw new Exception("Failed to create new appointment. Error: " . $conn->error);
                        }

                        $newAppointmentId = $conn->insert_id;

                        $updateQuery = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
                        $updateStmt = $conn->prepare($updateQuery);
                        $updateStmt->bind_param("i", $appointmentId);

                        if (!$updateStmt->execute()) {
                            throw new Exception("Failed to update original appointment. Error: " . $conn->error);
                        }

                        error_log("Appointment rescheduled - Old ID: $appointmentId, New ID: $newAppointmentId");

                        $tableCheckResult = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
                        if ($tableCheckResult->num_rows == 0) {
                            $createTableSql = "CREATE TABLE admin_notifications (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                message VARCHAR(255) NOT NULL,
                                type VARCHAR(50) NOT NULL,
                                reference_id INT,
                                is_read TINYINT(1) DEFAULT 0,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            )";
                            $conn->query($createTableSql);
                        }

                        $patientQuery = "SELECT CONCAT(first_name, ' ', last_name) AS full_name 
                                        FROM patients WHERE id = ?";
                        $patientStmt = $conn->prepare($patientQuery);
                        $patientStmt->bind_param("i", $patient_id);
                        $patientStmt->execute();
                        $patientResult = $patientStmt->get_result();
                        $patientData = $patientResult->fetch_assoc();
                        $patientName = $patientData['full_name'] ?? 'Unknown Patient';

                        $notificationMessage = "Patient $patientName has rescheduled their appointment to $new_date at $new_time.";

                        $notifStmt = $conn->prepare("INSERT INTO admin_notifications 
                                                    (message, type, reference_id) 
                                                    VALUES (?, 'reschedule', ?)");
                        $notifStmt->bind_param("si", $notificationMessage, $newAppointmentId);
                        $notifStmt->execute();

                        $conn->commit();

                        $_SESSION['success_message'] = "Your appointment has been rescheduled successfully. New appointment ID: #$newAppointmentId";
                        unset($_SESSION['reschedule_appointment_id']);
                        header("Location: mybookings.php");
                        exit();

                    } catch (Exception $e) {
                        $conn->rollback();
                        $_SESSION['error_message'] = $e->getMessage();
                        $error_message = $e->getMessage();
                        echo "<script>console.log('$error_message')</script>";
                        header("Location: reschedule_appointment.php");
                        exit();
                    }
                }
            }
        }
    }
}
// Get available time slots for form
$timeSlots = [
    '10:00 AM',
    '11:00 AM',
    '1:00 PM',
    '2:00 PM',
    '3:00 PM',
    '4:00 PM',
    '5:00 PM'
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - M&A Oida Dental Clinic</title>
    <?php require_once 'includes/head.php' ?>
    <style>
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

        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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
        <?php include_once('includes/navbar.php'); ?>
    </header>

    <div class="container mt-4">
        <h1 class="text-center">Reschedule Appointment</h1>
        <p class="muted text-center">Change your appointment date and time</p>
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
                        <div class="detail-value"><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Current Time:</div>
                        <div class="detail-value"><?php echo $appointment['appointment_time']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Services:</div>
                        <div class="detail-value"><?php echo $appointment['services']; ?></div>
                    </div>

                </div>

                <h2>Select New Date and Time</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="appointment_date">New Date:</label>
                        <input type="date" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo date('Y-m-d'); ?>" required>
                        <small class="form-text text-muted">Note: Weekend days (Saturday and Sunday) are not available for
                            appointments.</small>
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

                    <button type="submit" class="btn btn-primary">Reschedule Appointment</button>
                    <a href="mybookings.php" class="btn btn-secondary">Back</a>

                </form>

                <script>
                    // Function to disable weekend days (Saturday = 6, Sunday = 0)
                    function disableWeekends() {
                        const datePicker = document.getElementById('appointment_date');

                        datePicker.addEventListener('input', function (e) {
                            const selectedDate = new Date(this.value);
                            const day = selectedDate.getUTCDay();

                            // Check if the selected day is a weekend
                            if (day === 0 || day === 6) {
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


</body>

</html>