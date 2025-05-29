<?php
require_once('db.php');
require_once('session_handler.php');


// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Load admin data
load_admin_data($conn);

// Fetch pending appointments with debug info
$sql = "SELECT a.id, a.services, a.appointment_date, a.appointment_time, a.status,
               CONCAT('APT-', LPAD(a.id, 6, '0')) as reference_number,
               p.first_name, p.middle_name, p.last_name, p.id as patient_id 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.status = 'pending' OR a.status IS NULL 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
echo "<!-- Debug SQL: " . htmlspecialchars($sql) . " -->";
$result = $conn->query($sql);

if (!$result) {
    echo "Error: " . $conn->error;
}

// Debug appointment count
echo "<!-- Total appointments found: " . ($result ? $result->num_rows : 0) . " -->";

// Appointments count
$appointmentCount = 0;
$appointmentThisMonth = 0;
// Total appointments (exclude cancelled)
$sql = "SELECT COUNT(*) as total FROM appointments WHERE status != 'cancelled'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $appointmentCount = $row['total'];
}
// Appointments this month (exclude cancelled)
$sqlMonth = "SELECT COUNT(*) as month_total FROM appointments WHERE status != 'cancelled' AND MONTH(appointment_date) = MONTH(CURRENT_DATE()) AND YEAR(appointment_date) = YEAR(CURRENT_DATE())";
$resultMonth = $conn->query($sqlMonth);
if ($resultMonth && $rowMonth = $resultMonth->fetch_assoc()) {
    $appointmentThisMonth = $rowMonth['month_total'];
}

// Get pending appointments count for badge
$pendingAppointments = 0;
$sqlPending = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$resultPending = $conn->query($sqlPending);
if ($resultPending && $rowPending = $resultPending->fetch_assoc()) {
    $pendingAppointments = $rowPending['total'];
}

// Debug pending appointments
echo "<!-- Pending appointments count: $pendingAppointments -->";


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Appointments - M&amp;A Oida Dental Clinic</title>
    <?php require_once 'head.php' ?>

    <style>
        /* Mobile optimizations */
        @media (max-width: 768px) {

            /* Improve appointment cards */
            .appointment-card {
                margin: 10px 0;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            /* Stack appointment details */
            .appointment-info {
                flex-direction: column;
                gap: 10px;
            }



            /* Improve filter section */
            .filter-section {
                flex-direction: column;
                gap: 10px;
                padding: 15px;
            }

            .filter-group {
                width: 100%;
            }

            /* Better date/time display */
            .appointment-datetime {
                font-size: 1.1rem;
                font-weight: 600;
                margin-bottom: 8px;
            }

            /* Improve status badges */
            .status-badge {
                width: 100%;
                text-align: center;
                padding: 8px;
                margin: 8px 0;
                border-radius: 4px;
            }

            /* Better patient info layout */
            .patient-details {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
            }

            /* Improve modal layout */
            .modal-content {
                width: 95% !important;
                margin: 10px auto;
                border-radius: 8px;
            }

            .modal-body {
                padding: 15px;
                max-height: 70vh;
                overflow-y: auto;
            }


            /* Improve calendar view */
            .calendar-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding: 10px 0;
            }

            /* Loading states */
            .loading-overlay {
                background: rgba(255, 255, 255, 0.9);
            }

            /* Improve scrolling */
            .scroll-container {
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }
        }

        .action-button {
            padding: 5px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Function to show selected section and update button states
            window.showSection = function (sectionName) {
                // Hide all sections first
                const sections = ['pending', 'upcoming', 'rescheduled', 'canceled'];
                sections.forEach(section => {
                    const sectionEl = document.getElementById(section + '-section');
                    const buttonEl = document.getElementById(section + '-btn');
                    if (sectionEl) {
                        sectionEl.style.display = 'none';
                    }
                    if (buttonEl) {
                        buttonEl.classList.remove('active');
                    }
                });

                // Show selected section and update button
                const selectedSection = document.getElementById(sectionName + '-section');
                const selectedButton = document.getElementById(sectionName + '-btn');
                if (selectedSection) {
                    selectedSection.style.display = 'block';
                }
                if (selectedButton) {
                    selectedButton.classList.add('active');
                }
            }

            // Set initial active section
            const urlParams = new URLSearchParams(window.location.search);
            const initialSection = urlParams.get('section') || 'pending';
            showSection(initialSection);
        });
    </script>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen">
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-x-hidden">
            <?php require_once 'header.php' ?>

            <!-- Breadcrumb Navigation -->
            <nav class="flex items-center space-x-2 px-6 py-3 bg-gray-50 border-b border-gray-200">
                <ol class="flex items-center space-x-2">
                    <li>
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    <li>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </li>
                    <li>
                        <span class="text-gray-600">Appointments</span>
                    </li>
                </ol>
            </nav>

            <!-- Appointments Table Section -->
            <section class="mx-5 bg-white rounded-lg border border-gray-300 shadow-md p-4 mt-6">
                <div class="flex justify-between items-center mb-3">
                    <h1 class="text-blue-900 font-bold text-lg select-none">
                        Appointments
                    </h1>
                    <!--    <div class="relative">
                            <input aria-label="Search"
                                class="border border-gray-400 rounded text-sm pl-7 pr-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-600"
                                placeholder="Search appointments..." type="text" />
                            <i aria-hidden="true"
                                class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-600 text-xs"></i>
                        </div> -->
                </div>

                <div class="flex flex-col md:flex-row mb-6">

                    <button type="button" onclick="showSection('pending')" id="pending-btn"
                        class="action-button bg-yellow-400 hover:bg-yellow-500 text-white font-semibold md:mr-4">
                        <i class="fas fa-clock mr-2"></i>Pending
                    </button>
                    <button type="button" onclick="showSection('upcoming')" id="upcoming-btn"
                        class="action-button bg-green-700 hover:bg-green-800 text-white font-semibold md:mr-4">
                        <i class="fas fa-calendar-check mr-2"></i>Upcoming
                    </button>
                    <button type="button" onclick="showSection('rescheduled')" id="rescheduled-btn"
                        class="action-button bg-blue-800 hover:bg-blue-900 text-white font-semibold md:mr-4">
                        <i class="fas fa-calendar-alt mr-2"></i>Rescheduled
                    </button>
                    <button type="button" onclick="showSection('canceled')" id="canceled-btn"
                        class="action-button bg-red-700 hover:bg-red-800 text-white font-semibold">
                        <i class="fas fa-times-circle mr-2"></i>Canceled
                    </button>

                </div>

                <div class="my-4 w-full overflow-x-scroll">
                    <div id="pending-section" class="w-full block">
                        <div class="appointments-table-container p-2">
                            <table id="appointmentsTable1" class="appointments-table display">

                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-300">
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Booking Ref No.</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Patient Name</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Service</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Date</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Time</th>
                                        <th class="font-semibold text-left px-4 py-2 whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch pending appointments with debug info
// Simplified query to get ALL appointments regardless of status for debugging
                                    $sql = "SELECT a.*, CONCAT('APT-', LPAD(a.id, 6, '0')) as reference_number,
                p.first_name, p.middle_name, p.last_name, p.id as patient_id
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        ORDER BY a.id DESC LIMIT 10";

                                    // Debug SQL query
                                    echo "<!-- Debug SQL: " . htmlspecialchars($sql) . " -->";
                                    $result = $conn->query($sql);

                                    if (!$result) {
                                        echo "Error: " . $conn->error;
                                    }

                                    // Debug all appointments in the database
                                    $debug_sql = "SELECT id, patient_id, status FROM appointments ORDER BY id DESC LIMIT 10";
                                    $debug_result = $conn->query($debug_sql);
                                    if ($debug_result) {
                                        echo "<!-- Recent appointments in database: ";
                                        while ($debug_row = $debug_result->fetch_assoc()) {
                                            echo "ID: {$debug_row['id']}, Patient: {$debug_row['patient_id']}, Status: {$debug_row['status']} | ";
                                        }
                                        echo " -->";
                                    }

                                    if ($result && $result->num_rows > 0):
                                        // Debug count
                                        echo "<!-- Found " . $result->num_rows . " pending appointments -->";
                                        while ($row = $result->fetch_assoc()):
                                            // Debug row data
                                            echo "<!-- Appointment data: " . json_encode($row) . " -->";

                                            $patientName = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']);
                                            $ref = $row['reference_number'] ?? ('APP-' . date('Y') . '-' . str_pad($row['id'], 5, '0', STR_PAD_LEFT));
                                            $service = $row['services'] ?? 'General Consultation';
                                            $date = !empty($row['appointment_date']) ? date('F j, Y', strtotime($row['appointment_date'])) : date('F j, Y');
                                            $time = !empty($row['appointment_time']) ? date('g:i A', strtotime($row['appointment_time'])) : '9:00 AM';
                                            ?>
                                            <tr class="border-t border-gray-300 hover:bg-gray-50 transition-colors">
                                                <td class="border-r border-gray-300 font-semibold px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($ref); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($patientName); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($service); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($date); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($time); ?>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap flex items-center space-x-2">
                                                    <button
                                                        class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1"
                                                        type="button" title="Approve"
                                                        onclick="showConfirmModal('approve', '<?= htmlspecialchars($patientName) ?>', '<?= htmlspecialchars($date) ?>', '<?= htmlspecialchars($time) ?>', '<?= htmlspecialchars($ref) ?>', '<?= htmlspecialchars($service) ?>')"><i
                                                            class="fas fa-check"></i></button>
                                                    <button
                                                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1"
                                                        type="button" title="Decline"
                                                        onclick="showConfirmModal('decline', '<?= htmlspecialchars($patientName) ?>', '<?= htmlspecialchars($date) ?>', '<?= htmlspecialchars($time) ?>', '<?= htmlspecialchars($ref) ?>', '<?= htmlspecialchars($service) ?>')"><i
                                                            class="fas fa-times"></i></button>
                                                    <button
                                                        class="bg-blue-700 text-white text-xs font-semibold rounded px-3 py-1"
                                                        type="button" title="Details"
                                                        onclick="showDetailsModal('<?= htmlspecialchars($ref) ?>','<?= htmlspecialchars($patientName) ?>','<?= htmlspecialchars($service) ?>','<?= htmlspecialchars($date) ?>','<?= htmlspecialchars($time) ?>','<?= htmlspecialchars($row['clinic_branch'] ?? 'Maligaya Park Branch') ?>','<?= htmlspecialchars($row['status'] ?? 'pending') ?>')">
                                                        Details
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php
                                        endwhile;
                                    endif; ?>
                                </tbody>
                            </table>
                            <script>
                                $(document).ready(function () {
                                    $('#appointmentsTable1').DataTable({
                                        responsive: true,
                                        pageLength: 10,
                                        order: [[3, 'asc']], // Sort by Date (column index 3)
                                    });
                                });
                            </script>

                        </div>
                    </div>

                    <div id="upcoming-section" class="w-full" style="display:none;">
                        <div class="appointments-table-container">
                            <table id="appointmentsTable" class="appointments-table display">

                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-300">
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Booking Ref No.</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Patient Name</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Service</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Date</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Time</th>
                                        <th class="font-semibold text-left px-4 py-2 whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch upcoming appointments
                                    $sql = "SELECT a.*, p.first_name, p.middle_name, p.last_name 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.status IN ('upcoming', 'booked') 
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
                                    $result = $conn->query($sql);

                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                            $patientName = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']);
                                            $ref = $row['reference_number'] ?? ('APP-' . date('Y') . '-' . str_pad($row['id'], 5, '0', STR_PAD_LEFT));
                                            $service = $row['services'] ?? 'General Consultation';
                                            $date = !empty($row['appointment_date']) ? date('F j, Y', strtotime($row['appointment_date'])) : date('F j, Y');
                                            $time = !empty($row['appointment_time']) ? date('g:i A', strtotime($row['appointment_time'])) : '9:00 AM';
                                            ?>
                                            <tr class="border-t border-gray-300 hover:bg-gray-50 transition-colors">
                                                <td class="border-r border-gray-300 font-semibold px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($ref); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($patientName); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($service); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($date); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($time); ?>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-green-700 font-bold">Approved
                                                </td>
                                            </tr>
                                            <?php
                                        endwhile;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <script>
                        $(document).ready(function () {
                            $('#appointmentsTable').DataTable({
                                responsive: true,
                                pageLength: 10,
                                order: [[3, 'asc']], // Sort by Date (column index 3)
                            });
                        });
                    </script>


                    <div id="rescheduled-section" class="w-full" style="display:none;">
                        <div class="appointments-table-container">
                            <table id="appointmentsTable2" class="appointments-table display">

                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-300">
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Booking Ref No.</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Patient Name</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Service</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Date</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Time</th>
                                        <th class="font-semibold text-left px-4 py-2 whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch rescheduled appointments
                                    $sql = "SELECT a.*, CONCAT('APT-', LPAD(a.id, 6, '0')) as reference_number,
                p.first_name, p.middle_name, p.last_name, p.id as patient_id 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.parent_appointment_id IS NOT NULL AND a.parent_appointment_id > 0 
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
                                    $result = $conn->query($sql);

                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                            $firstName = isset($row['first_name']) ? $row['first_name'] : '';
                                            $middleName = isset($row['middle_name']) ? $row['middle_name'] . ' ' : '';
                                            $lastName = isset($row['last_name']) ? $row['last_name'] : '';
                                            $patientName = trim($firstName . ' ' . $middleName . $lastName);
                                            $patientName = empty($patientName) ? 'N/A' : $patientName;

                                            $ref = isset($row['reference_number']) ? $row['reference_number'] : 'N/A';
                                            $service = isset($row['services']) ? $row['services'] : 'Not specified';

                                            $date = isset($row['appointment_date']) && !empty($row['appointment_date']) ?
                                                date('F j, Y', strtotime($row['appointment_date'])) : 'Not set';

                                            $time = isset($row['appointment_time']) && !empty($row['appointment_time']) ?
                                                date('g:i A', strtotime($row['appointment_time'])) : 'Not set';
                                            ?>
                                            <tr class="border-t border-gray-300 hover:bg-gray-50 transition-colors">
                                                <td class="border-r border-gray-300 font-semibold px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($ref); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($patientName); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($service); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($date); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($time); ?>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap flex items-center space-x-2">
                                                    <button
                                                        class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1"
                                                        type="button" title="Approve"
                                                        onclick="showConfirmModal('approve', '<?= htmlspecialchars($patientName) ?>', '<?= htmlspecialchars($date) ?>', '<?= htmlspecialchars($time) ?>', '<?= htmlspecialchars($ref) ?>', '<?= htmlspecialchars($service) ?>')"><i
                                                            class="fas fa-check"></i></button>
                                                    <button
                                                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1"
                                                        type="button" title="Decline"
                                                        onclick="showConfirmModal('decline', '<?= htmlspecialchars($patientName) ?>', '<?= htmlspecialchars($date) ?>', '<?= htmlspecialchars($time) ?>', '<?= htmlspecialchars($ref) ?>', '<?= htmlspecialchars($service) ?>')"><i
                                                            class="fas fa-times"></i></button>
                                                    <button
                                                        class="bg-blue-700 text-white text-xs font-semibold rounded px-3 py-1"
                                                        type="button" title="Details"
                                                        onclick="showDetailsModal('<?= htmlspecialchars($ref) ?>','<?= htmlspecialchars($patientName) ?>','<?= htmlspecialchars($service) ?>','<?= htmlspecialchars($date) ?>','<?= htmlspecialchars($time) ?>','<?= htmlspecialchars($row['clinic_branch']) ?>','<?= htmlspecialchars($row['status']) ?>')">
                                                        Details
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php
                                        endwhile;
                                    endif; ?>
                                </tbody>
                            </table>
                            <script>
                                $(document).ready(function () {
                                    $('#appointmentsTable2').DataTable({
                                        responsive: true,
                                        pageLength: 10,
                                        order: [[3, 'asc']], // Sort by Date (column index 3)
                                    });
                                });
                            </script>

                        </div>
                    </div>

                    <div id="canceled-section" class="w-full" style="display:none;">
                        <div class="appointments-table-container">
                            <table id="appointmentsTable3" class="appointments-table display">

                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-300">
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Booking Ref No.</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Patient Name</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Service</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Date</th>
                                        <th
                                            class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">
                                            Time</th>
                                        <th class="font-semibold text-left px-4 py-2 whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch canceled appointments
                                    $sql = "SELECT a.id, a.services, a.appointment_date, a.appointment_time, a.status,
               CONCAT('APT-', LPAD(a.id, 6, '0')) as reference_number,
               p.first_name, p.middle_name, p.last_name, p.id as patient_id 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.status = 'cancelled' 
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
                                    $result = $conn->query($sql);

                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                            $firstName = isset($row['first_name']) ? $row['first_name'] : '';
                                            $middleName = isset($row['middle_name']) ? $row['middle_name'] . ' ' : '';
                                            $lastName = isset($row['last_name']) ? $row['last_name'] : '';
                                            $patientName = trim($firstName . ' ' . $middleName . $lastName);
                                            $patientName = empty($patientName) ? 'N/A' : $patientName;

                                            $ref = isset($row['reference_number']) ? $row['reference_number'] : 'N/A';
                                            $service = isset($row['services']) ? $row['services'] : 'Not specified';

                                            $date = isset($row['appointment_date']) && !empty($row['appointment_date']) ?
                                                date('F j, Y', strtotime($row['appointment_date'])) : 'Not set';

                                            $time = isset($row['appointment_time']) && !empty($row['appointment_time']) ?
                                                date('g:i A', strtotime($row['appointment_time'])) : 'Not set';
                                            ?>
                                            <tr class="border-t border-gray-300 hover:bg-gray-50 transition-colors">
                                                <td class="border-r border-gray-300 font-semibold px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($ref); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($patientName); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($service); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($date); ?>
                                                </td>
                                                <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($time); ?>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-red-700 font-bold">Cancelled
                                                </td>
                                            </tr>
                                            <?php
                                        endwhile;
                                    endif; ?>
                                </tbody>
                            </table>
                            <script>
                                $(document).ready(function () {
                                    $('#appointmentsTable3').DataTable({
                                        responsive: true,
                                        pageLength: 10,
                                        order: [[3, 'asc']], // Sort by Date (column index 3)
                                    });
                                });
                            </script>

                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>
    <!-- Appointment Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full relative">
            <button onclick="hideDetailsModal()"
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            <div id="detailsContent">
                <!-- Appointment details will be injected here -->
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h2 id="modalTitle" class="text-xl font-bold mb-4">Confirm Approval of Appointment</h2>
            <p class="text-gray-600 mb-6">
                Are you sure you want to <span id="actionText" class="font-semibold text-blue-600"></span> this
                appointment for
                <span id="patientName" class="font-semibold"></span> on
                <span id="appointmentDate" class="font-semibold"></span> at
                <span id="appointmentTime" class="font-semibold"></span>?
            </p>
            <div class="flex justify-end space-x-4">
                <button onclick="hideConfirmModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button id="submitButton" onclick="handleConfirm()" class="px-4 py-2 rounded transition-colors">
                    Submit
                </button>
            </div>
        </div>
    </div>

    <!-- Reason Modal -->
    <div id="reasonModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h2 class="text-xl font-bold mb-4">Reason:</h2>
            <div class="mb-6">
                <textarea id="reasonText"
                    class="w-full h-32 p-3 border border-gray-300 rounded resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-gray-800"
                    placeholder="State your reason.." maxlength="500"
                    style="background-color: white !important; color: #333 !important;"></textarea>
                <div class="text-right text-sm text-gray-500">
                    <span id="charCount">0</span>/500
                </div>
            </div>
            <div class="flex justify-end space-x-4">
                <button onclick="hideReasonModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button onclick="handleReasonSubmit()"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                    Submit
                </button>
            </div>
        </div>
    </div>

    <!-- Decline Appointment Modal -->
    <div id="declineModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Decline Appointment</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">Please provide a reason for declining this appointment.</p>
                    <textarea id="reasonText"
                        class="mt-2 w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none" rows="4"
                        maxlength="200" placeholder="Enter reason here..."></textarea>
                    <div class="text-right text-xs text-gray-500"><span id="charCount">0</span>/200</div>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="cancelDecline"
                        class="px-4 py-2 bg-gray-200 text-gray-900 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300">Cancel</button>
                    <button id="confirmDecline"
                        class="mt-3 px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">Decline
                        Appointment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="mb-4">
                    <i id="successIcon" class="fas fa-check-circle text-5xl"></i>
                </div>
                <h2 id="successTitle" class="text-xl font-bold mb-2">Approval Successful!</h2>
                <p class="text-gray-600 mb-6">
                    The appointment for <span id="successPatientName" class="font-semibold"></span> on
                    <span id="successDate" class="font-semibold"></span> at
                    <span id="successTime" class="font-semibold"></span> has been
                    <span id="successAction" class="font-semibold"></span>. The patient
                    will be notified via Email/SMS.
                </p>
                <button onclick="hideSuccessModal()" class="px-6 py-2 text-white rounded transition-colors"
                    id="successButton">
                    Ok
                </button>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-xs w-full text-center">
            <h2 class="text-lg font-semibold text-blue-700 mb-2">Confirm logout</h2>
            <hr class="my-2 border-blue-100">
            <p class="text-gray-700 mb-6">Are you sure you want to log out?</p>
            <div class="flex justify-center space-x-4">
                <button id="cancelLogout"
                    class="px-4 py-1 rounded bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200">Cancel</button>
                <button id="confirmLogout"
                    class="px-4 py-1 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">OK</button>
            </div>
        </div>
    </div>

    <script>
        function showSection(section) {
            // Hide all sections
            document.getElementById('pending-section').style.display = 'none';
            document.getElementById('upcoming-section').style.display = 'none';
            document.getElementById('rescheduled-section').style.display = 'none';
            document.getElementById('canceled-section').style.display = 'none';

            // Show selected section
            document.getElementById(section + '-section').style.display = '';

            // Highlight active button
            ['pending', 'upcoming', 'rescheduled', 'canceled'].forEach(function (tab) {
                document.getElementById(tab + '-btn').classList.remove('opacity-60');
            });
            document.getElementById(section + '-btn').classList.add('opacity-60');
        }
        // Default: show pending
        showSection('pending');

        function showDetailsModal(bookingId, patientName, service, date, time, branch, status) {
            document.getElementById('detailsContent').innerHTML = `
            <h2 class="text-lg font-bold mb-2">Appointment Details</h2>
            <div><b>Booking ID:</b> ${bookingId}</div>
            <div><b>Patient Name:</b> ${patientName}</div>
            <div><b>Service:</b> ${service}</div>
            <div><b>Date:</b> ${date}</div>
            <div><b>Time:</b> ${time}</div>
            <div><b>Clinic Branch:</b> ${branch}</div>
            <div><b>Status:</b> ${status}</div>
        `;
            document.getElementById('detailsModal').classList.remove('hidden');
        }
        function hideDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Modal logic and appointment actions (from dashboard.js, adapted for appointments.php)
        let currentAction = '';
        let currentPatient = '';
        let currentDate = '';
        let currentTime = '';
        let currentReason = '';
        let currentBookingRef = '';
        let currentService = '';

        function showConfirmModal(action, patient, date, time, bookingRef, service) {
            currentAction = action;
            currentPatient = patient;
            currentDate = date;
            currentTime = time;
            currentBookingRef = bookingRef;
            currentService = service;

            const modal = document.getElementById('confirmModal');
            const modalTitle = document.getElementById('modalTitle');
            const actionText = document.getElementById('actionText');
            const patientName = document.getElementById('patientName');
            const appointmentDate = document.getElementById('appointmentDate');
            const appointmentTime = document.getElementById('appointmentTime');
            const submitButton = document.getElementById('submitButton');

            if (action === 'approve') {
                modalTitle.textContent = 'Confirm Approval of Appointment';
                actionText.textContent = 'approve';
                actionText.className = 'font-semibold text-green-600';
                submitButton.className = 'px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors';
            } else {
                modalTitle.textContent = 'Confirm Decline of Appointment';
                actionText.textContent = 'decline';
                actionText.className = 'font-semibold text-red-600';
                submitButton.className = 'px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors';
            }
            patientName.textContent = patient;
            appointmentDate.textContent = date;
            appointmentTime.textContent = time;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function hideConfirmModal() {
            const modal = document.getElementById('confirmModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        function showReasonModal() {
            const modal = document.getElementById('reasonModal');
            const reasonText = document.getElementById('reasonText');
            const charCount = document.getElementById('charCount');
            reasonText.value = '';
            charCount.textContent = '0';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function hideReasonModal() {
            const modal = document.getElementById('reasonModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        function handleConfirm() {
            hideConfirmModal();
            if (currentAction === 'decline') {
                setTimeout(() => { showReasonModal(); }, 300);
            } else {
                // Approve: send AJAX to backend
                updateAppointmentStatus('approve');
            }
        }
        function handleReasonSubmit() {
            const reasonText = document.getElementById('reasonText');
            currentReason = reasonText.value.trim();
            if (!currentReason) {
                alert('Please provide a reason for declining the appointment.');
                return;
            }
            hideReasonModal();
            // Decline: send AJAX to backend
            updateAppointmentStatus('decline', currentReason);
        }
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            const successIcon = document.getElementById('successIcon');
            const successTitle = document.getElementById('successTitle');
            const successAction = document.getElementById('successAction');
            const successButton = document.getElementById('successButton');
            const patientName = document.getElementById('successPatientName');
            const appointmentDate = document.getElementById('successDate');
            const appointmentTime = document.getElementById('successTime');
            if (currentAction === 'approve') {
                successIcon.className = 'fas fa-check-circle text-5xl text-green-500';
                successTitle.textContent = 'Approval Successful!';
                successAction.textContent = 'approved';
                successButton.className = 'px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors';
            } else {
                successIcon.className = 'fas fa-times-circle text-5xl text-red-500';
                successTitle.textContent = 'Appointment Declined';
                successAction.textContent = 'declined';
                successButton.className = 'px-6 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors';
            }
            patientName.textContent = currentPatient;
            appointmentDate.textContent = currentDate;
            appointmentTime.textContent = currentTime;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            // Force reload after short delay
            setTimeout(() => { location.reload(); }, 1200);
        }
        function hideSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            // Optionally reload the page to update the table
            location.reload();
        }
        function moveAppointmentRow(bookingRef, action) {
            // Find the row in the pending section
            const pendingTable = document.querySelector('#pending-section table tbody');
            const rows = pendingTable.querySelectorAll('tr');
            let foundRow = null;
            rows.forEach(row => {
                if (row.children[0] && row.children[0].textContent.trim() === bookingRef) {
                    foundRow = row;
                }
            });
            if (foundRow) {
                // Remove from pending
                foundRow.remove();
                // Add to the correct section
                let targetSection = null;
                if (action === 'approve') {
                    targetSection = document.querySelector('#upcoming-section table tbody');
                } else if (action === 'decline') {
                    targetSection = document.querySelector('#canceled-section table tbody');
                }
                if (targetSection) {
                    // Remove any 'No ... appointments found.' row
                    const emptyRows = targetSection.querySelectorAll('tr');
                    emptyRows.forEach(row => {
                        if (row.children.length === 1 && row.children[0].hasAttribute('colspan') && row.textContent.includes('No')) {
                            row.remove();
                        }
                    });
                    // Clone the row and append
                    const newRow = foundRow.cloneNode(true);
                    targetSection.appendChild(newRow);
                }
            } else {
                // Fallback: reload if not found
                setTimeout(() => { location.reload(); }, 1000);
            }
        }
        function updateAppointmentStatus(action, reason = '') {
            // Extract appointment ID from reference number
            let appointmentId = '';
            if (currentBookingRef && currentBookingRef.includes('-')) {
                appointmentId = currentBookingRef.split('-').pop().replace(/^0+/, ''); // Remove leading zeros
            }
            if (!appointmentId) {
                alert('Invalid appointment reference.');
                return;
            }
            const formData = new FormData();
            formData.append('action', action === 'approve' ? 'approve' : 'decline');
            formData.append('appointment_id', appointmentId);
            if (action === 'decline') {
                formData.append('reason', reason);
            }
            fetch('appointment_actions.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessModal();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Request failed.');
                });
        }
        // Character counter for reason textarea
        document.addEventListener('DOMContentLoaded', function () {
            const reasonText = document.getElementById('reasonText');
            const charCount = document.getElementById('charCount');
            if (reasonText && charCount) {
                reasonText.addEventListener('input', function () {
                    charCount.textContent = this.value.length;
                });
            }
        });

        // Check for profile photo updates from other pages
        window.addEventListener('load', function () {
            const newProfilePhoto = sessionStorage.getItem('newProfilePhoto');
            if (newProfilePhoto) {
                document.querySelectorAll('img[alt*="Profile photo"]').forEach(img => {
                    img.src = newProfilePhoto;
                });
            }
        });

        // Table search functionality
        document.querySelector('input[aria-label="Search"]').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            // Hanapin ang kasalukuyang visible na section
            const sections = ['pending', 'upcoming', 'rescheduled', 'canceled'];
            let visibleSection = null;
            for (const sec of sections) {
                const el = document.getElementById(sec + '-section');
                if (el && el.style.display !== 'none') {
                    visibleSection = el;
                    break;
                }
            }
            if (!visibleSection) return;
            const rows = visibleSection.querySelectorAll('tbody tr');
            rows.forEach(row => {
                // Skip 'No ... appointments found.' row
                if (row.children.length === 1 && row.children[0].hasAttribute('colspan')) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

        document.querySelectorAll('a[href="admin_login.php"]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('logoutModal').classList.remove('hidden');
            });
        });
        document.getElementById('cancelLogout').onclick = function () {
            document.getElementById('logoutModal').classList.add('hidden');
        };
        document.getElementById('confirmLogout').onclick = function () {
            window.location.href = 'admin_login.php';
        };
    </script>
</body>

</html>