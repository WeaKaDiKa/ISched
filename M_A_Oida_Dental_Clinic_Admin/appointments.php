<?php
require_once('session_handler.php');
require_once('db.php');

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Load admin data
load_admin_data($conn);

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Dynamic greeting based on time of day (Asia/Manila)
date_default_timezone_set('Asia/Manila');
$hour = (int)date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning,';
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = 'Good Afternoon,';
} else {
    $greeting = 'Good Evening,';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Appointments - M&amp;A Oida Dental Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/mobile-modules.css">
    <script src="assets/js/mobile.js"></script>
    <style>
    /* Collapsed sidebar styles */
    #sidebar.collapsed {
        width: 4.5rem !important;
        min-width: 0 !important;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    #sidebar.collapsed .sidebar-label,
    #sidebar.collapsed .text-center,
    #sidebar.collapsed .text-xs,
    #sidebar.collapsed nav span,
    #sidebar.collapsed .mt-auto,
    #sidebar.collapsed .flex.flex-col.items-center.mb-8 > h3,
    #sidebar.collapsed .flex.flex-col.items-center.mb-8 > p {
        display: none !important;
    }
    #sidebar.collapsed .flex.flex-col.items-center.mb-8 {
        align-items: flex-start !important;
    }
    #sidebar.collapsed img.w-24 {
        margin-bottom: 0 !important;
    }
    .active-sidebar-link {
        background-color: #f4f6f8;
        color: #1e3a8a !important;
        font-weight: bold;
        position: relative;
    }
    .active-sidebar-link::before {
        content: "";
        position: absolute;
        left: 0;
        top: 8px;
        bottom: 8px;
        width: 4px;
        background: #2563eb;
        border-radius: 8px;
    }
    
    /* Mobile optimizations */
    @media (max-width: 768px) {
        /* Improve appointment cards */
        .appointment-card {
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Stack appointment details */
        .appointment-info {
            flex-direction: column;
            gap: 10px;
        }
        
        /* Make buttons more touch-friendly */
        .action-button {
            min-height: 44px;
            width: 100%;
            margin: 5px 0;
            justify-content: center;
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
        
        /* Better form inputs */
        input[type="text"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        /* Improve calendar view */
        .calendar-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 10px 0;
        }
        
        /* Better navigation */
        .nav-tabs {
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            padding: 10px 0;
        }
        
        .nav-item {
            display: inline-block;
            margin-right: 10px;
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
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to show selected section and update button states
        window.showSection = function(sectionName) {
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
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="flex flex-col bg-white border-r border-gray-200 w-64 min-w-[256px] py-6 px-4 transition-all duration-300">
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center space-x-2">
                    <img alt="M&amp;A Oida Dental Clinic logo" class="w-8 h-8" src="assets/photo/logo.jpg"/>
                    <span class="sidebar-label text-sm font-semibold text-gray-900 whitespace-nowrap">
                        M&amp;A Oida Dental Clinic
                    </span>
                </div>
                <button id="sidebarToggle" aria-label="Toggle menu" class="text-blue-600 hover:text-blue-700 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
            
            <!-- Profile Section -->
            <div class="flex flex-col items-center mb-8">
                <img alt="Profile photo" class="rounded-full w-24 h-24 object-cover mb-2" src="<?php echo (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>"/>
                <h3 class="text-center text-sm font-semibold text-gray-900 leading-tight">
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
                </h3>
                <p class="text-center text-xs text-gray-500 mt-1">
                    Professional Dentist
                </p>
            </div>

            <!-- Navigation -->
            <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
            <nav class="flex flex-col space-y-2 text-gray-700 text-sm font-medium">
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'dashboard.php' ? 'active-sidebar-link' : ''; ?>" href="dashboard.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-home"></i>
                    </div>
                    <span>Dashboard</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'appointments.php' ? 'active-sidebar-link' : ''; ?>" href="appointments.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span>Appointments</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'patient_record.php' ? 'active-sidebar-link' : ''; ?>" href="patient_record.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <span>Patient Records</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'patient_feedback.php' ? 'active-sidebar-link' : ''; ?>" href="patient_feedback.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-comment-alt"></i>
                    </div>
                    <span>Patient Feedback</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'account_settings.php' ? 'active-sidebar-link' : ''; ?>" href="account_settings.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-cog"></i>
                    </div>
                    <span>Account Settings</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'request_access.php' ? 'active-sidebar-link' : ''; ?>" href="request_access.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-lock"></i>
                    </div>
                    <span>Request for Access</span>
                </a>
                <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'help_support.php' ? 'active-sidebar-link' : ''; ?>" href="help_support.php">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <span>Help & Support</span>
                </a>
            </nav>

            <a href="admin_login.php" class="mt-auto flex justify-center items-center space-x-2 text-red-600 hover:text-red-700 font-semibold text-sm">
                <i class="fas fa-sign-out-alt fa-lg"></i>
                <span>Logout</span>
            </a>
        </aside>

        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="flex items-center justify-between bg-blue-300 px-6 py-3 border-b border-gray-300">
                <!-- Welcome Message Section -->
                <div class="flex items-center space-x-3 text-gray-900 text-sm font-normal">
                    <span><?php echo $greeting; ?></span>
                    <span class="font-bold text-gray-900 text-base">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>!
                    </span>
                    <span class="text-gray-900">
                        | Maligaya Park Branch
                    </span>
                </div>

                <!-- Action Buttons Section -->
                <div class="flex items-center space-x-4">
                    <a href="walk_in_appointment.php" class="bg-purple-700 text-white text-xs font-semibold rounded-md px-4 py-1 hover:bg-purple-800">
                        Walk-in Appointment Form
                    </a>
                    <button aria-label="Notifications" class="text-gray-900 hover:text-gray-700 focus:outline-none relative">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if ($pendingAppointments > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                            <?= $pendingAppointments ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <img alt="Profile photo of <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" 
                         class="rounded-full w-10 h-10 object-cover" 
                         src="<?php echo (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>" />
                </div>
            </header>

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


            <!-- Content area -->
            <div class="flex-1 overflow-hidden bg-gray-100">
                <!-- Appointments Table Section -->
                <section class="w-full max-w-5xl mx-auto bg-white rounded-lg border border-gray-300 shadow-md p-4 mt-6">
                    <div class="flex justify-between items-center mb-3">
                        <h1 class="text-blue-900 font-bold text-lg select-none">
                            Appointments
                        </h1>
                        <div class="relative">
                            <input aria-label="Search" class="border border-gray-400 rounded text-sm pl-7 pr-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-600" placeholder="Search appointments..." type="text"/>
                            <i aria-hidden="true" class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-600 text-xs"></i>
                        </div>
                    </div>
                    
                    <div class="appointments-tabs mb-6">
                        <button type="button" onclick="showSection('pending')" id="pending-btn" class="action-button bg-yellow-400 hover:bg-yellow-500 text-white font-semibold">
                            <i class="fas fa-clock mr-2"></i>Pending
                        </button>
                        <button type="button" onclick="showSection('upcoming')" id="upcoming-btn" class="action-button bg-green-700 hover:bg-green-800 text-white font-semibold">
                            <i class="fas fa-calendar-check mr-2"></i>Upcoming
                        </button>
                        <button type="button" onclick="showSection('rescheduled')" id="rescheduled-btn" class="action-button bg-blue-800 hover:bg-blue-900 text-white font-semibold">
                            <i class="fas fa-calendar-alt mr-2"></i>Rescheduled
                        </button>
                        <button type="button" onclick="showSection('canceled')" id="canceled-btn" class="action-button bg-red-700 hover:bg-red-800 text-white font-semibold">
                            <i class="fas fa-times-circle mr-2"></i>Canceled
                        </button>
                    </div>
                    
                    <div class="appointments-sections relative w-full">
                        <div id="pending-section" class="w-full" style="display:block;">
                            <div class="appointments-table-container" style="max-height: 500px; overflow-y: auto;">
                                <table class="appointments-table">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-300">
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Booking Ref No.</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Patient Name</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Service</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Date</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Time</th>
                                            <th class="font-semibold text-left px-4 py-2 whitespace-nowrap">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
// Fetch pending appointments
$sql = "SELECT a.id, a.services, a.appointment_date, a.appointment_time, a.status,
               CONCAT('APT-', LPAD(a.id, 6, '0')) as reference_number,
               p.first_name, p.middle_name, p.last_name, p.id as patient_id 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.status = 'pending' OR a.status IS NULL 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = $conn->query($sql);

if (!$result) {
    echo "Error: " . $conn->error;
}

if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
        $patientName = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']);
        $ref = $row['reference_number'] ?? ('APP-' . date('Y') . '-' . str_pad($row['id'], 5, '0', STR_PAD_LEFT));
        $service = $row['services'] ?? 'General Consultation';
        $date = !empty($row['appointment_date']) ? date('F j, Y', strtotime($row['appointment_date'])) : date('F j, Y');
        $time = !empty($row['appointment_time']) ? date('g:i A', strtotime($row['appointment_time'])) : '9:00 AM';
?>
<tr class="border-t border-gray-300 hover:bg-gray-50 transition-colors">
    <td class="border-r border-gray-300 font-semibold px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($ref); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($patientName); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($service); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($date); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($time); ?></td>
    <td class="px-4 py-2 whitespace-nowrap flex items-center space-x-2">
        <button class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1" type="button" title="Approve" onclick="showConfirmModal('approve', '<?= htmlspecialchars($patientName) ?>', '<?= htmlspecialchars($date) ?>', '<?= htmlspecialchars($time) ?>', '<?= htmlspecialchars($ref) ?>', '<?= htmlspecialchars($service) ?>')"><i class="fas fa-check"></i></button>
        <button class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1" type="button" title="Decline" onclick="showConfirmModal('decline', '<?= htmlspecialchars($patientName) ?>', '<?= htmlspecialchars($date) ?>', '<?= htmlspecialchars($time) ?>', '<?= htmlspecialchars($ref) ?>', '<?= htmlspecialchars($service) ?>')"><i class="fas fa-times"></i></button>
        <button class="bg-blue-700 text-white text-xs font-semibold rounded px-3 py-1" type="button" title="Details"
            onclick="showDetailsModal('<?= htmlspecialchars($ref) ?>','<?= htmlspecialchars($patientName) ?>','<?= htmlspecialchars($service) ?>','<?= htmlspecialchars($date) ?>','<?= htmlspecialchars($time) ?>','<?= htmlspecialchars($row['clinic_branch'] ?? 'Maligaya Park Branch') ?>','<?= htmlspecialchars($row['status'] ?? 'pending') ?>')">
            Details
        </button>
    </td>
</tr>
<?php
    endwhile;
    
    // If no pending appointments were found after checking all rows
    if ($result->num_rows == 0):
?>
<tr>
    <td colspan="6" class="text-center py-2">No pending appointments found.</td>
</tr>
<?php 
    endif;
else:
?>
<tr>
    <td colspan="6" class="text-center py-2">No appointments found.</td>
</tr>
<?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="upcoming-section" class="w-full" style="display:none;">
                            <div class="appointments-table-container" style="max-height: 500px; overflow-y: auto;">
                                <table class="appointments-table">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-300">
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Booking Ref No.</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Patient Name</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Service</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Date</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Time</th>
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
    <td class="border-r border-gray-300 font-semibold px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($ref); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($patientName); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($service); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($date); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($time); ?></td>
    <td class="px-4 py-2 whitespace-nowrap text-green-700 font-bold">Approved</td>
</tr>
<?php
    endwhile;
else:
?>
<tr>
    <td colspan="6" class="text-center py-2">No upcoming appointments found.</td>
</tr>
<?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="rescheduled-section" class="w-full" style="display:none;">
                            <div class="appointments-table-container" style="max-height: 500px; overflow-y: auto;">
                                <table class="appointments-table">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-300">
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Booking Ref No.</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Patient Name</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Service</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Date</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Time</th>
                                            <th class="font-semibold text-left px-4 py-2 whitespace-nowrap">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
// Fetch rescheduled appointments
$sql = "SELECT a.*, p.first_name, p.middle_name, p.last_name, p.id as patient_id 
        FROM appointments a 
        LEFT JOIN patients p ON a.patient_id = p.id 
        WHERE a.status = 'rescheduled' 
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
    <td class="border-r border-gray-300 font-semibold px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($ref); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($patientName); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($service); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($date); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($time); ?></td>
    <td class="px-4 py-2 whitespace-nowrap flex items-center space-x-2">
        <button class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1" type="button" title="Approve" onclick="showConfirmModal('approve', '<?= htmlspecialchars($patientName) ?>', '<?= htmlspecialchars($date) ?>', '<?= htmlspecialchars($time) ?>', '<?= htmlspecialchars($ref) ?>', '<?= htmlspecialchars($service) ?>')"><i class="fas fa-check"></i></button>
        <button class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded px-2 py-1 mr-1" type="button" title="Decline" onclick="showConfirmModal('decline', '<?= htmlspecialchars($patientName) ?>', '<?= htmlspecialchars($date) ?>', '<?= htmlspecialchars($time) ?>', '<?= htmlspecialchars($ref) ?>', '<?= htmlspecialchars($service) ?>')"><i class="fas fa-times"></i></button>
        <button class="bg-blue-700 text-white text-xs font-semibold rounded px-3 py-1" type="button" title="Details"
            onclick="showDetailsModal('<?= htmlspecialchars($ref) ?>','<?= htmlspecialchars($patientName) ?>','<?= htmlspecialchars($service) ?>','<?= htmlspecialchars($date) ?>','<?= htmlspecialchars($time) ?>','<?= htmlspecialchars($row['clinic_branch']) ?>','<?= htmlspecialchars($row['status']) ?>')">
            Details
        </button>
    </td>
</tr>
<?php
    endwhile;
else:
?>
<tr>
    <td colspan="6" class="text-center py-2">No rescheduled appointments found.</td>
</tr>
<?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="canceled-section" class="w-full" style="display:none;">
                            <div class="appointments-table-container" style="max-height: 500px; overflow-y: auto;">
                                <table class="appointments-table">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-300">
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Booking Ref No.</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Patient Name</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Service</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Date</th>
                                            <th class="border-r border-gray-300 font-semibold text-left px-4 py-2 whitespace-nowrap">Time</th>
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
    <td class="border-r border-gray-300 font-semibold px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($ref); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($patientName); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($service); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($date); ?></td>
    <td class="border-r border-gray-300 px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($time); ?></td>
    <td class="px-4 py-2 whitespace-nowrap text-red-700 font-bold">Cancelled</td>
</tr>
<?php
    endwhile;
else:
?>
<tr>
    <td colspan="6" class="text-center py-2">No canceled appointments found.</td>
</tr>
<?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
    <!-- Appointment Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full relative">
            <button onclick="hideDetailsModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-xl">&times;</button>
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
                Are you sure you want to <span id="actionText" class="font-semibold text-blue-600"></span> this appointment for
                <span id="patientName" class="font-semibold"></span> on 
                <span id="appointmentDate" class="font-semibold"></span> at
                <span id="appointmentTime" class="font-semibold"></span>?
            </p>
            <div class="flex justify-end space-x-4">
                <button onclick="hideConfirmModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
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
                <textarea 
                    id="reasonText" 
                    class="w-full h-32 p-3 border border-gray-300 rounded resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="State your reason.."
                    maxlength="500"
                ></textarea>
                <div class="text-right text-sm text-gray-500">
                    <span id="charCount">0</span>/500
                </div>
            </div>
            <div class="flex justify-end space-x-4">
                <button onclick="hideReasonModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button onclick="handleReasonSubmit()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                    Submit
                </button>
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
                <button onclick="hideSuccessModal()" class="px-6 py-2 text-white rounded transition-colors" id="successButton">
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
          <button id="cancelLogout" class="px-4 py-1 rounded bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200">Cancel</button>
          <button id="confirmLogout" class="px-4 py-1 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">OK</button>
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
        ['pending','upcoming','rescheduled','canceled'].forEach(function(tab){
            document.getElementById(tab+'-btn').classList.remove('opacity-60');
        });
        document.getElementById(section+'-btn').classList.add('opacity-60');
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
    document.addEventListener('DOMContentLoaded', function() {
        const reasonText = document.getElementById('reasonText');
        const charCount = document.getElementById('charCount');
        if (reasonText && charCount) {
            reasonText.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
        }
    });

    // Check for profile photo updates from other pages
    window.addEventListener('load', function() {
        const newProfilePhoto = sessionStorage.getItem('newProfilePhoto');
        if (newProfilePhoto) {
            document.querySelectorAll('img[alt*="Profile photo"]').forEach(img => {
                img.src = newProfilePhoto;
            });
        }
    });

    // Table search functionality
    document.querySelector('input[aria-label="Search"]').addEventListener('input', function() {
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

    // Sidebar toggle logic
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
    });

    document.querySelectorAll('a[href="admin_login.php"]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('logoutModal').classList.remove('hidden');
      });
    });
    document.getElementById('cancelLogout').onclick = function() {
      document.getElementById('logoutModal').classList.add('hidden');
    };
    document.getElementById('confirmLogout').onclick = function() {
      window.location.href = 'admin_login.php';
    };
    </script>
</body>
</html> 