<?php
require_once('db.php');
require_once('session_handler.php');

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Check if patient ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: patient_record.php');
    exit;
}

$patientId = intval($_GET['id']);

// Get patient information
$patientQuery = "SELECT p.*, pp.* 
               FROM patients p 
               LEFT JOIN patient_profiles pp ON p.id = pp.patient_id
               WHERE p.id = ?";
$patientStmt = $conn->prepare($patientQuery);
$patientStmt->bind_param("i", $patientId);
$patientStmt->execute();
$patientResult = $patientStmt->get_result();

if ($patientResult->num_rows === 0) {
    header('Location: patient_record.php');
    exit;
}

$patient = $patientResult->fetch_assoc();

// Get patient's appointments (both pending and approved)
$appointmentsQuery = "SELECT a.*, 
                     CONCAT('APP-', LPAD(a.id, 6, '0')) as reference_number,
                     d.first_name as doctor_first_name, d.last_name as doctor_last_name
                     FROM appointments a 
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     WHERE a.patient_id = ? 
                     ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$appointmentsStmt = $conn->prepare($appointmentsQuery);
$appointmentsStmt->bind_param("i", $patientId);
$appointmentsStmt->execute();
$appointmentsResult = $appointmentsStmt->get_result();

$appointments = [];
while ($row = $appointmentsResult->fetch_assoc()) {
    $appointments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Patient Details - M&A Oida Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php require_once 'head.php' ?>
    <style>
        .appointment-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .appointment-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .status-approved {
            background-color: #D1FAE5;
            color: #065F46;
        }
        .status-completed {
            background-color: #DBEAFE;
            color: #1E40AF;
        }
        .status-cancelled {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-button {
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-bottom: 2px solid transparent;
        }
        .tab-button.active {
            border-bottom: 2px solid #3B82F6;
            color: #3B82F6;
        }
    </style>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <?php require_once 'header.php' ?>
            <!-- Breadcrumb -->
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
                        <a href="patient_record.php" class="text-gray-600 hover:text-gray-900">Patient Records</a>
                    </li>
                    <li>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </li>
                    <li>
                        <span class="text-gray-600">Patient Details</span>
                    </li>
                </ol>
            </nav>

            <!-- Content area -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-6xl mx-auto">
                    <!-- Patient Info Header -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                                <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if (!empty($patient['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($patient['profile_picture']); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user text-gray-400 text-4xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">
                                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                    </h1>
                                    <p class="text-gray-600">
                                        <i class="fas fa-phone-alt mr-2"></i>
                                        <?php echo htmlspecialchars($patient['phone_number'] ?? 'No phone number'); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <i class="fas fa-envelope mr-2"></i>
                                        <?php echo htmlspecialchars($patient['email'] ?? 'No email'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="edit_patient.php?id=<?php echo $patientId; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-edit mr-2"></i> Edit Profile
                                </a>
                                <a href="create_appointment.php?patient_id=<?php echo $patientId; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <i class="fas fa-calendar-plus mr-2"></i> New Appointment
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8">
                            <button class="tab-button active py-4 px-1 font-medium text-sm" data-tab="profile">
                                <i class="fas fa-user mr-2"></i> Profile
                            </button>
                            <button class="tab-button py-4 px-1 font-medium text-sm" data-tab="appointments">
                                <i class="fas fa-calendar-alt mr-2"></i> Appointments
                                <span class="ml-2 bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                    <?php echo count($appointments); ?>
                                </span>
                            </button>
                            <button class="tab-button py-4 px-1 font-medium text-sm" data-tab="dental-records">
                                <i class="fas fa-tooth mr-2"></i> Dental Records
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Contents -->
                    <div class="tab-content active" id="profile-tab">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold mb-4">Personal Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Full Name</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Date of Birth</p>
                                    <p class="font-medium"><?php echo !empty($patient['date_of_birth']) ? date('F j, Y', strtotime($patient['date_of_birth'])) : 'Not provided'; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Gender</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($patient['gender'] ?? 'Not provided'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Phone Number</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($patient['phone_number'] ?? 'Not provided'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Email</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($patient['email'] ?? 'Not provided'); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Address</p>
                                    <p class="font-medium">
                                        <?php 
                                        $address = [];
                                        if (!empty($patient['barangay'])) $address[] = $patient['barangay'];
                                        if (!empty($patient['city'])) $address[] = $patient['city'];
                                        if (!empty($patient['province'])) $address[] = $patient['province'];
                                        if (!empty($patient['region'])) $address[] = $patient['region'];
                                        echo !empty($address) ? htmlspecialchars(implode(', ', $address)) : 'Not provided';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="appointments-tab">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold">Appointment History</h2>
                                <a href="create_appointment.php?patient_id=<?php echo $patientId; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-1"></i> New Appointment
                                </a>
                            </div>

                            <?php if (empty($appointments)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-calendar-times text-gray-300 text-5xl mb-4"></i>
                                    <p class="text-gray-500">No appointments found for this patient.</p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref. Number</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Services</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($appointments as $appointment): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($appointment['reference_number']); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php 
                                                        $appointmentDate = date('F j, Y', strtotime($appointment['appointment_date']));
                                                        echo htmlspecialchars($appointmentDate . ' at ' . $appointment['appointment_time']); 
                                                        ?>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        <?php 
                                                        $services = explode(',', $appointment['services']);
                                                        foreach ($services as $service) {
                                                            echo '<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">' . htmlspecialchars(trim($service)) . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php 
                                                        if (!empty($appointment['doctor_first_name']) && !empty($appointment['doctor_last_name'])) {
                                                            echo 'Dr. ' . htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']);
                                                        } else {
                                                            echo 'Not assigned';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?php 
                                                        $statusClass = '';
                                                        switch(strtolower($appointment['status'])) {
                                                            case 'pending':
                                                                $statusClass = 'status-pending';
                                                                break;
                                                            case 'approved':
                                                                $statusClass = 'status-approved';
                                                                break;
                                                            case 'completed':
                                                                $statusClass = 'status-completed';
                                                                break;
                                                            case 'cancelled':
                                                                $statusClass = 'status-cancelled';
                                                                break;
                                                            default:
                                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                        }
                                                        ?>
                                                        <span class="status-badge <?php echo $statusClass; ?>">
                                                            <?php echo htmlspecialchars(ucfirst($appointment['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-content" id="dental-records-tab">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-semibold">Dental Records</h2>
                                <a href="#" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-plus mr-1"></i> Add Record
                                </a>
                            </div>

                            <div class="text-center py-8">
                                <i class="fas fa-tooth text-gray-300 text-5xl mb-4"></i>
                                <p class="text-gray-500">No dental records found for this patient.</p>
                                <p class="text-gray-500 mt-2">Add a new record to track the patient's dental history.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all tabs
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Add active class to clicked tab
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>
