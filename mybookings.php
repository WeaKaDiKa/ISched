<?php
require_once('session.php');
require_once('db.php');
require_once('includes/profile_functions.php');
// Check if parent_appointment_id column exists in appointments table and add if not
$columnCheckSql = "SHOW COLUMNS FROM appointments LIKE 'parent_appointment_id'";
$columnCheckResult = $conn->query($columnCheckSql);

if ($columnCheckResult->num_rows == 0) {
    $alterTableSql = "ALTER TABLE appointments ADD COLUMN parent_appointment_id INT NULL DEFAULT NULL AFTER doctor_id";
    $conn->query($alterTableSql);
}

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle reschedule requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reschedule') {
    $appointmentId = intval($_POST['appointment_id']);
    // Store the appointment ID in session and redirect to reschedule page
    $_SESSION['reschedule_appointment_id'] = $appointmentId;
    header('Location: reschedule_appointment.php');
    exit;
}

// Handle accept booking requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept') {
    $appointmentId = intval($_POST['appointment_id']);

    // Verify this appointment belongs to the current user
    $verifyStmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND patient_id = ? AND status = 'pending'");
    $verifyStmt->bind_param("ii", $appointmentId, $_SESSION['user_id']);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();

    if ($result->num_rows === 1) {
        // Update appointment status to booked
        $updateStmt = $conn->prepare("UPDATE appointments SET status = 'booked' WHERE id = ?");
        $updateStmt->bind_param("i", $appointmentId);

        if ($updateStmt->execute()) {
            $acceptMessage = "Your appointment has been accepted successfully.";
        } else {
            $acceptError = "Failed to accept appointment. Please try again.";
        }
    } else {
        $acceptError = "Invalid appointment or appointment is not in pending status.";
    }
}

// Handle cancellation requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $appointmentId = intval($_POST['appointment_id']);

    // Verify this appointment belongs to the current user
    $verifyStmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND patient_id = ?");
    $verifyStmt->bind_param("ii", $appointmentId, $_SESSION['user_id']);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();

    if ($result->num_rows === 1) {
        // Update appointment status to cancelled
        $updateStmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
        $updateStmt->bind_param("i", $appointmentId);

        if ($updateStmt->execute()) {
            $cancelMessage = "Your appointment has been cancelled successfully.";
        } else {
            $cancelError = "Failed to cancel appointment. Please try again.";
        }
    } else {
        $cancelError = "Invalid appointment.";
    }
}

// Process filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$rescheduleFilter = isset($_GET['reschedule']) ? $_GET['reschedule'] : 'all';

// Get all appointments for the current user
$userId = $_SESSION['user_id'];
$query = "SELECT a.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.specialization
          FROM appointments a 
          LEFT JOIN doctors d ON a.doctor_id = d.id
          WHERE a.patient_id = ?";


// Add status filter
if ($statusFilter !== 'all') {
    $query .= " AND a.status = '$statusFilter'";
}

// Add reschedule filter
if ($rescheduleFilter === 'rescheduled_from') {
    $query .= " AND (SELECT COUNT(*) > 0 FROM appointments WHERE parent_appointment_id = a.id)";
} else if ($rescheduleFilter === 'rescheduled_to') {
    $query .= " AND a.parent_appointment_id IS NOT NULL";
} else if ($rescheduleFilter === 'any_reschedule') {
    $query .= " AND ((SELECT COUNT(*) > 0 FROM appointments WHERE parent_appointment_id = a.id) OR a.parent_appointment_id IS NOT NULL)";
}

$query .= " ORDER BY a.appointment_date DESC, STR_TO_DATE(a.appointment_time, '%h:%i %p')";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch user details
$userQuery = "SELECT first_name, last_name, email, phone_number FROM patients WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - M&A Oida Dental Clinic</title>
    <!-- Include your common CSS files -->
    <?php require_once 'includes/head.php' ?>
    <link rel="stylesheet" href="assets/css/appointment-modal.css">
    <style>
        .user-info {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .bookings-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .booking-card {
            border: 1px solid #eaeaea;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fcfcfc;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .booking-id {
            font-weight: bold;
            color: #1a76d2;
        }

        .booking-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-booked {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .status-rescheduled {
            background-color: #fff8e1;
            color: #ff8f00;
        }

        .booking-details {
            margin-bottom: 15px;
        }

        .booking-row {
            display: flex;
            margin-bottom: 5px;
        }

        .booking-label {
            width: 150px;
            font-weight: bold;
        }

        .booking-value {
            flex: 1;
        }

        .booking-actions {
            text-align: right;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-cancel {
            background-color: #f44336;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #d32f2f;
        }

        .btn-accept {
            background-color: #4CAF50;
            color: white;
        }

        .btn-accept:hover {
            background-color: #388E3C;
        }

        .btn-reschedule {
            background-color: #ff9800;
            color: white;
            margin-right: 10px;
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

        .no-bookings {
            text-align: center;
            padding: 30px;
            color: #757575;
        }

        .navigation {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .nav-link {
            display: inline-block;
            margin: 0 10px;
            color: #1a76d2;
            text-decoration: none;
            font-weight: bold;
        }

        .nav-link:hover {
            text-decoration: underline;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .modal-title {
            font-size: 20px;
            margin-bottom: 15px;
            color: #d32f2f;
        }

        .modal-message {
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .modal-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-confirm-cancel {
            background-color: #f44336;
            color: white;
        }

        .btn-go-back {
            background-color: #9e9e9e;
            color: white;
        }

        .filter-controls {
            margin-bottom: 20px;
            background-color: #f9f9f9;
            border-radius: 6px;
            padding: 15px;
            border: 1px solid #eaeaea;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        .filter-group label {
            margin-right: 8px;
            font-weight: bold;
        }

        .filter-group select {
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <header>
        <?php include_once('includes/navbar.php'); ?>
    </header>
    <div class="container mt-4">
        <?php if (isset($acceptMessage)): ?>
            <div class="message success alert alert-success"><?php echo $acceptMessage; ?></div>
        <?php endif; ?>

        <?php if (isset($acceptError)): ?>
            <div class="message error alert alert-danger"><?php echo $acceptError; ?></div>
        <?php endif; ?>

        <?php if (isset($cancelMessage)): ?>
            <div class="message success alert alert-success"><?php echo $cancelMessage; ?></div>
        <?php endif; ?>

        <?php if (isset($cancelError)): ?>
            <div class="message error alert alert-danger"><?php echo $cancelError; ?></div>
        <?php endif; ?>

        <div class="user-info card mb-4">
            <div class="card-body">
                <h2 class="card-title">Patient Information</h2>
                <div class="booking-row row mb-2">
                    <div class="booking-label col-md-2 font-weight-bold">Name:</div>
                    <div class="booking-value col-md-10">
                        <?php echo $userData['first_name'] . ' ' . $userData['last_name']; ?></div>
                </div>
                <div class="booking-row row mb-2">
                    <div class="booking-label col-md-2 font-weight-bold">Email:</div>
                    <div class="booking-value col-md-10"><?php echo $userData['email']; ?></div>
                </div>
                <div class="booking-row row mb-2">
                    <div class="booking-label col-md-2 font-weight-bold">Phone:</div>
                    <div class="booking-value col-md-10"><?php echo $userData['phone_number']; ?></div>
                </div>
            </div>
        </div>

        <div class="bookings-container card">
            <div class="card-body">
                <h2 class="card-title">Your Appointments</h2>

                <div class="filter-controls mb-4">
                    <form action="" method="GET" id="filterForm">
                        <div class="filter-row row">
                            <div class="filter-group col-md-6 mb-3">
                                <label for="status" class="form-label">Status:</label>
                                <select name="status" id="status" class="form-select"
                                    onchange="document.getElementById('filterForm').submit();">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All
                                        Statuses
                                    </option>
                                    <option value="booked" <?php echo $statusFilter === 'booked' ? 'selected' : ''; ?>>
                                        Booked
                                    </option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>
                                        Completed</option>
                                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>
                                        Cancelled</option>
                                </select>
                            </div>

                            <div class="filter-group col-md-6 mb-3">
                                <label for="reschedule" class="form-label">Reschedule Status:</label>
                                <select name="reschedule" id="reschedule" class="form-select"
                                    onchange="document.getElementById('filterForm').submit();">
                                    <option value="all" <?php echo $rescheduleFilter === 'all' ? 'selected' : ''; ?>>All
                                    </option>
                                    <option value="rescheduled_from" <?php echo $rescheduleFilter === 'rescheduled_from' ? 'selected' : ''; ?>>Rescheduled From</option>
                                    <option value="rescheduled_to" <?php echo $rescheduleFilter === 'rescheduled_to' ? 'selected' : ''; ?>>Rescheduled To</option>
                                    <option value="any_reschedule" <?php echo $rescheduleFilter === 'any_reschedule' ? 'selected' : ''; ?>>Any Reschedule</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <?php while ($booking = $result->fetch_assoc()): ?>
                        <?php
                        // Compute reschedule flags
                        $booking['has_been_rescheduled'] = false;
                        $booking['is_result_of_reschedule'] = false;

                        // Check if this appointment has been rescheduled (may anak)
                        $checkRescheduledFrom = $conn->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE parent_appointment_id = ?");
                        $checkRescheduledFrom->bind_param("i", $booking['id']);
                        $checkRescheduledFrom->execute();
                        $resFrom = $checkRescheduledFrom->get_result()->fetch_assoc();
                        if ($resFrom && $resFrom['cnt'] > 0) {
                            $booking['has_been_rescheduled'] = true;
                        }

                        // Check if this appointment is a result of reschedule (may parent)
                        if (!empty($booking['parent_appointment_id'])) {
                            $booking['is_result_of_reschedule'] = true;
                        }
                        ?>
                        <div class="booking-card card mb-3">
                            <div class="booking-header card-header d-flex justify-content-between align-items-center">
                                <span class="booking-id">Appointment #<?php echo $booking['id']; ?></span>
                                <div>
                                    <span class="booking-status badge status-<?php echo strtolower($booking['status']); ?> 
                                    <?php echo $booking['status'] === 'pending' ? 'bg-warning text-dark' : ''; ?>
                                    <?php echo $booking['status'] === 'booked' || $booking['status'] === 'approved' ? 'bg-success' : ''; ?>
                                    <?php echo $booking['status'] === 'cancelled' ? 'bg-danger' : ''; ?>
                                    <?php echo $booking['status'] === 'rescheduled' ? 'bg-info' : ''; ?>">
                                        <?php
                                        if ($booking['status'] === 'pending') {
                                            echo 'Pending';
                                        } elseif ($booking['status'] === 'booked' || $booking['status'] === 'approved') {
                                            echo 'Confirmed';
                                        } elseif ($booking['status'] === 'cancelled') {
                                            echo 'Cancelled';
                                        } elseif ($booking['status'] === 'rescheduled') {
                                            echo 'Rescheduled';
                                        } else {
                                            echo ucfirst($booking['status']);
                                        }
                                        ?>
                                    </span>
                                    <?php if ($booking['has_been_rescheduled']): ?>
                                        <span class="booking-status badge bg-secondary">Rescheduled From</span>
                                    <?php endif; ?>
                                    <?php if ($booking['is_result_of_reschedule']): ?>
                                        <span class="booking-status badge bg-secondary">Rescheduled To</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="booking-details card-body">
                                <div class="booking-row row mb-2">
                                    <div class="booking-label col-md-3 font-weight-bold">Date:</div>
                                    <div class="booking-value col-md-9">
                                        <?php echo date('F j, Y', strtotime($booking['appointment_date'])); ?>
                                    </div>
                                </div>
                                <div class="booking-row row mb-2">
                                    <div class="booking-label col-md-3 font-weight-bold">Time:</div>
                                    <div class="booking-value col-md-9"><?php echo $booking['appointment_time']; ?></div>
                                </div>
                                <div class="booking-row row mb-2">
                                    <div class="booking-label col-md-3 font-weight-bold">Branch:</div>
                                    <div class="booking-value col-md-9"><?php echo $booking['clinic_branch']; ?></div>
                                </div>
                                <div class="booking-row row mb-2">
                                    <div class="booking-label col-md-3 font-weight-bold">Services:</div>
                                    <div class="booking-value col-md-9"><?php echo $booking['services']; ?></div>
                                </div>
                                <?php if ($booking['doctor_id']): ?>
                                    <div class="booking-row row mb-2">
                                        <div class="booking-label col-md-3 font-weight-bold">Doctor:</div>
                                        <div class="booking-value col-md-9">
                                            Dr. <?php echo $booking['doctor_first_name'] . ' ' . $booking['doctor_last_name']; ?>
                                            (<?php echo $booking['specialization']; ?>)
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($booking['status'] === 'pending'): ?>
                                <div class="booking-actions card-footer">
                                    <button class="btn btn-cancel btn-danger"
                                        onclick="openCancelModal(<?php echo $booking['id']; ?>)">Cancel
                                        Appointment</button>
                                </div>
                            <?php elseif ($booking['status'] === 'booked'): ?>
                                <div class="booking-actions card-footer">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="reschedule">
                                        <input type="hidden" name="appointment_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" class="btn btn-reschedule btn-primary">Reschedule</button>
                                    </form>

                                    <button class="btn btn-cancel btn-danger"
                                        onclick="openCancelModal(<?php echo $booking['id']; ?>)">Cancel
                                        Appointment</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-bookings alert alert-info">
                        <p>You don't have any appointments yet.</p>
                        <p>Click <a href="bookings.php" class="text-primary fw-bold">here</a> to book your first
                            appointment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Appointment Details Modal -->

    <div id="appointmentDetailsOverlay" class="modal-overlay" style="display:none;">
        <div class="appointment-modal">
            <button class="modal-close" onclick="closeAppointmentDetails()">&times;</button>
            <h2 class="modal-title">Appointment Details</h2>
            <div class="modal-details" id="appointmentDetailsContent">
                <!-- Details will be injected by JS -->
            </div>
        </div>
    </div>

    <!-- Cancel Appointment Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3 class="modal-title">Cancel Appointment</h3>
            <p class="modal-message">Are you sure you want to cancel this appointment? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button class="modal-btn btn-go-back" onclick="closeCancelModal()">Go Back</button>
                <form id="cancelForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" id="cancel_appointment_id" name="appointment_id" value="">
                    <button type="submit" class="modal-btn btn-confirm-cancel">Yes, Cancel Appointment</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Get the modal
        const modal = document.getElementById('cancelModal');
        const cancelForm = document.getElementById('cancelForm');
        const appointmentIdInput = document.getElementById('cancel_appointment_id');

        // Function to open the cancel modal
        function openCancelModal(appointmentId) {
            appointmentIdInput.value = appointmentId;
            modal.style.display = 'flex';
        }

        // Function to close the cancel modal
        function closeCancelModal() {
            modal.style.display = 'none';
        }

        // Close the modal if clicked outside
        window.onclick = function (event) {
            if (event.target === modal) {
                closeCancelModal();
            }
        }
    </script>
    <script>
        function showAppointmentDetails(bookingId, patientName, services, date, time, branch, status) {
            const overlay = document.getElementById('appointmentDetailsOverlay');
            const content = document.getElementById('appointmentDetailsContent');
            content.innerHTML = `
    <div><span class="label">Booking ID:</span> <span class="value">${bookingId}</span></div>
    <div><span class="label">Patient Name:</span> <span class="value">${patientName}</span></div>
    <div><span class="label">Service:</span> <span class="value">${services}</span></div>
    <div><span class="label">Date:</span> <span class="value">${date}</span></div>
    <div><span class="label">Time:</span> <span class="value">${time}</span></div>
    <div><span class="label">Clinic Branch:</span> <span class="value">${branch}</span></div>
    <div><span class="label">Status:</span> <span class="value status-${status.toLowerCase()}">${status}</span></div>
  `;
            overlay.style.display = 'flex';
        }
        function closeAppointmentDetails() {
            document.getElementById('appointmentDetailsOverlay').style.display = 'none';
        }
    </script>
</body>

</html>