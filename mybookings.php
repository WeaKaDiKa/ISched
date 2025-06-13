<?php
require_once('session.php');
require_once('db.php');
require_once('includes/profile_functions.php');
// Check if parent_appointment_id column exists in appointments table and add if not
$columnCheckSql = "SHOW COLUMNS FROM appointments LIKE 'parent_appointment_id'";
$columnCheckResult = $conn->query($columnCheckSql);

if ($columnCheckResult->num_rows == 0) {
    $alterTableSql = "ALTER TABLE appointments ADD COLUMN parent_appointment_id INT NULL DEFAULT NULL";
    $conn->query($alterTableSql);
}

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
} else {
    // Get all appointments for the current user
    $userId = $_SESSION['user_id'];
}

// Handle reschedule requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reschedule') {
    $appointmentId = intval($_POST['appointment_id']);
    // Store the appointment ID in session and redirect to reschedule page
    $_SESSION['reschedule_appointment_id'] = $appointmentId;
    header('Location: reschedule_appointment.php');
    exit();
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
    exit();
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
    exit();
}

// Process filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$rescheduleFilter = isset($_GET['reschedule']) ? $_GET['reschedule'] : 'all';


$query = "SELECT a.* FROM appointments a WHERE a.patient_id = ?";

// Use prepared statements for status filter to prevent SQL injection
$statusFilter = $conn->real_escape_string($statusFilter); // Additional safety
if ($statusFilter !== 'all') {
    $query .= " AND a.status = ?";
}

// Improved reschedule filter logic
switch ($rescheduleFilter) {
    case 'rescheduled_from':
        $query .= " AND EXISTS (SELECT 1 FROM appointments WHERE parent_appointment_id = a.id)";
        break;
    case 'rescheduled_to':
        $query .= " AND a.parent_appointment_id IS NOT NULL";
        break;
    case 'any_reschedule':
        $query .= " AND (EXISTS (SELECT 1 FROM appointments WHERE parent_appointment_id = a.id) OR a.parent_appointment_id IS NOT NULL)";
        break;
}

// More reliable time sorting with proper 12-hour format handling and NULL safety
$query .= " ORDER BY a.appointment_date DESC, 
            CASE 
                WHEN a.appointment_time IS NULL THEN '23:59:59'
                ELSE TIME(STR_TO_DATE(a.appointment_time, '%h:%i %p')) 
            END DESC";

// Prepare statement with dynamic parameters
$stmt = $conn->prepare($query);

if ($statusFilter !== 'all') {
    $stmt->bind_param("is", $userId, $statusFilter);
} else {
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$resultappointments = $stmt->get_result();

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
        /* Custom card styles that complement Bootstrap */
        .user-info,
        .bookings-container,
        .navigation {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.625rem rgba(0, 0, 0, 0.1);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        /* Booking card enhancements */
        .booking-card {
            border-left: 0.25rem solid transparent;
            margin-bottom: 1rem;
        }

        /* Status badges - using Bootstrap's badge classes with custom colors */
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

        /* Booking details layout */
        .booking-row {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .booking-label {
            width: 9.375rem;
            font-weight: 600;
        }

        /* Messages - using Bootstrap alert classes with custom tweaks */
        .message {
            text-align: center;
        }

        /* Filter controls */
        .filter-controls {
            background-color: #f9f9f9;
            border: 1px solid #eaeaea;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            align-items: center;
        }


        /* Custom button variants */
        .btn-reschedule {
            background-color: #ff9800;
            border-color: #ff9800;
        }

        .btn-reschedule:hover {
            background-color: #e68a00;
            border-color: #e68a00;
        }

        /* Navigation link styling */
        .nav-link-custom {
            color: #1a76d2;
            font-weight: 600;
        }

        .nav-link-custom:hover {
            text-decoration: underline;
        }

        /* Back button positioning */
        .back-btn-custom {
            position: absolute;
            top: 1.25rem;
            left: 1.25rem;
            color: white;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <header>
        <?php include_once('includes/navbar.php'); ?>
    </header>
    <div class="container mt-4">
        <h1 class="text-center">My Appointments</h1>
        <p class="muted text-center">View all your appointments</p>
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
                        <?php echo $userData['first_name'] . ' ' . $userData['last_name']; ?>
                    </div>
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
                    <form method="GET" id="filterForm">
                        <div class="p-3 row">
                            <div class="col-md-6 mb-3">
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

                            <div class="col-md-6 mb-3">
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

                <?php if ($resultappointments->num_rows > 0): ?>
                    <?php while ($booking = $resultappointments->fetch_assoc()): ?>
                        <?php

                        $rescheduleCheck = $conn->prepare("SELECT 
            EXISTS(SELECT 1 FROM appointments WHERE parent_appointment_id = ?) as has_children,
            ? IS NOT NULL as has_parent");
                        $rescheduleCheck->bind_param("ii", $booking['id'], $booking['parent_appointment_id']);
                        $rescheduleCheck->execute();
                        $rescheduleStatus = $rescheduleCheck->get_result()->fetch_assoc();

                        $booking['has_been_rescheduled'] = (bool) $rescheduleStatus['has_children'];
                        $booking['is_result_of_reschedule'] = (bool) $rescheduleStatus['has_parent'];
                        ?>

                        <div class="booking-card card mb-3 border-start border-4 
            <?php echo match ($booking['status']) {
                'pending' => 'border-warning',
                'booked', 'approved' => 'border-success',
                'cancelled' => 'border-danger',
                'rescheduled' => 'border-info',
                default => ''
            }; ?>">

                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Appointment #<?= htmlspecialchars($booking['id']) ?></span>
                                <div>
                                    <span class="badge <?= match ($booking['status']) {
                                        'pending' => 'bg-warning text-dark',
                                        'booked', 'approved' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        'rescheduled' => 'bg-info',
                                        default => 'bg-secondary'
                                    } ?>">
                                        <?= match ($booking['status']) {
                                            'pending' => 'Pending',
                                            'booked', 'approved' => 'Confirmed',
                                            'cancelled' => 'Cancelled',
                                            'rescheduled' => 'Rescheduled',
                                            default => ucfirst($booking['status'])
                                        } ?>
                                    </span>

                                    <?php if ($booking['has_been_rescheduled']): ?>
                                        <span class="badge bg-secondary ms-1">Original</span>
                                    <?php endif; ?>
                                    <?php if ($booking['is_result_of_reschedule']): ?>
                                        <span class="badge bg-secondary ms-1">Rescheduled</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Date:</dt>
                                    <dd class="col-sm-9"><?= date('F j, Y', strtotime($booking['appointment_date'])) ?></dd>

                                    <dt class="col-sm-3">Time:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($booking['appointment_time']) ?></dd>

                                    <dt class="col-sm-3">Branch:</dt>
                                    <dd class="col-sm-9">North Fairview Branch</dd>

                                    <dt class="col-sm-3">Services:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($booking['services']) ?></dd>


                                </dl>
                            </div>

                            <?php if (in_array($booking['status'], ['pending', 'booked'])): ?>
                                <div class="card-footer d-flex gap-2">
                                    <?php if ($booking['status'] === 'booked'): ?>
                                        <form method="POST" class="mb-0">
                                            <input type="hidden" name="action" value="reschedule">
                                            <input type="hidden" name="appointment_id" value="<?= $booking['id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">Reschedule</button>
                                        </form>
                                    <?php endif; ?>

                                    <button class="btn btn-danger btn-sm" onclick="openCancelModal(<?= $booking['id'] ?>)">
                                        Cancel Appointment
                                    </button>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['status'] === 'completed'): ?>
                                <div class="card-footer d-flex gap-2">
                                    <a href="reviews.php?appointment_id=<?= $booking['id'] ?>" class="btn btn-primary btn-sm">
                                        Write a Review
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-calendar-times fa-2x mb-2 text-muted"></i>
                        <p class="mb-1">You don't have any appointments yet.</p>
                        <a href="bookings.php" class="btn btn-sm btn-outline-primary mt-2">
                            Book Your First Appointment
                        </a>
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

    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this appointment? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Go Back</button>
                    <form id="cancelForm" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" id="cancel_appointment_id" name="appointment_id" value="">
                        <button type="submit" class="btn btn-danger">Yes, Cancel Appointment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize modal with Bootstrap's JavaScript
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
        const cancelForm = document.getElementById('cancelForm');
        const appointmentIdInput = document.getElementById('cancel_appointment_id');

        // Function to open the cancel modal
        function openCancelModal(appointmentId) {
            appointmentIdInput.value = appointmentId;
            cancelModal.show();
        }

        // Optional: Close modal when form submits (if not doing a full page reload)
        cancelForm.addEventListener('submit', function () {
            cancelModal.hide();
        });
  
    </script>
    <script>
        function showAppointmentDetails(bookingId, patientName, services, date, time, status) {
            const overlay = document.getElementById('appointmentDetailsOverlay');
            const content = document.getElementById('appointmentDetailsContent');
            content.innerHTML = `
    <div><span class="label">Booking ID:</span> <span class="value">${bookingId}</span></div>
    <div><span class="label">Patient Name:</span> <span class="value">${patientName}</span></div>
    <div><span class="label">Service:</span> <span class="value">${services}</span></div>
    <div><span class="label">Date:</span> <span class="value">${date}</span></div>
    <div><span class="label">Time:</span> <span class="value">${time}</span></div>
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