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
$dentalhistoryQuery = "SELECT *
               FROM dentalhistory 
               WHERE patientid = ?";
$dentalStmt = $conn->prepare($dentalhistoryQuery);
$dentalStmt->bind_param("i", $patientId);
$dentalStmt->execute();
$dentalResult = $dentalStmt->get_result();

if ($dentalResult && $dentalResult->num_rows > 0) {
    $dental = $dentalResult->fetch_assoc();
    $teethData = json_decode($dental['teeth'], true);

}
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
                                <div
                                    class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if (!empty($patient['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($patient['profile_picture']); ?>"
                                            alt="Profile Picture" class="w-full h-full object-cover">
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
                                <span
                                    class="ml-2 bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
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
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Date of Birth</p>
                                    <p class="font-medium">
                                        <?php echo !empty($patient['date_of_birth']) ? date('F j, Y', strtotime($patient['date_of_birth'])) : 'Not provided'; ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Gender</p>
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($patient['gender'] ?? 'Not provided'); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Phone Number</p>
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($patient['phone_number'] ?? 'Not provided'); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Email</p>
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($patient['email'] ?? 'Not provided'); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 mb-1">Address</p>
                                    <p class="font-medium">
                                        <?php
                                        $address = [];
                                        if (!empty($patient['barangay']))
                                            $address[] = $patient['barangay'];
                                        if (!empty($patient['city']))
                                            $address[] = $patient['city'];
                                        if (!empty($patient['province']))
                                            $address[] = $patient['province'];
                                        if (!empty($patient['region']))
                                            $address[] = $patient['region'];
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
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ref. Number</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Date & Time</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Services</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Doctor</th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status</th>
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
                                                        switch (strtolower($appointment['status'])) {
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
                                <h2 class="text-xl font-semibold">Dental and Medical Records</h2>
                                <a href="edit_patient.php?id=<?php echo $patientId; ?>"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-edit mr-2"></i> Edit Records
                                </a>
                            </div>

                            <?php
                            $profileQuery = "SELECT * FROM patient_profiles WHERE patient_id = ?";
                            $profileStmt = $conn->prepare($profileQuery);
                            $profileStmt->bind_param("i", $patientId);
                            $profileStmt->execute();
                            $profileResult = $profileStmt->get_result();
                            $patient = $profileResult->fetch_assoc();

                            if (!empty($patient)):
                                ?>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <form>
                                        <fieldset disabled>
                                            <h5 class="font-medium text-gray-900 mb-3">Medical History</h5>
                                            <div class="space-y-4" id="medicalInfo">
                                                <div class="space-y-8 bg-white p-6 rounded-lg shadow">
                                                    <!-- Physician Information -->
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-4">Physician Information</h2>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label for="physician_name"
                                                                    class="block text-sm font-medium text-gray-700">Physician
                                                                    Name</label>
                                                                <input type="text" name="physician_name" id="physician_name"
                                                                    value="<?php echo htmlspecialchars($patient['physician_name'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                            <div>
                                                                <label for="physician_specialty"
                                                                    class="block text-sm font-medium text-gray-700">Specialty</label>
                                                                <input type="text" name="physician_specialty"
                                                                    id="physician_specialty"
                                                                    value="<?php echo htmlspecialchars($patient['physician_specialty'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                            <div>
                                                                <label for="physician_office_address"
                                                                    class="block text-sm font-medium text-gray-700">Office
                                                                    Address</label>
                                                                <input type="text" name="physician_office_address"
                                                                    id="physician_office_address"
                                                                    value="<?php echo htmlspecialchars($patient['physician_office_address'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                            <div>
                                                                <label for="physician_office_number"
                                                                    class="block text-sm font-medium text-gray-700">Office
                                                                    Number</label>
                                                                <input type="text" name="physician_office_number"
                                                                    id="physician_office_number"
                                                                    value="<?php echo htmlspecialchars($patient['physician_office_number'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Health Status -->
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-4">Health Status</h2>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700">Are
                                                                    you
                                                                    in good health?</label>
                                                                <div class="mt-2 space-x-4">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="good_health" value="1"
                                                                            <?php echo (isset($patient['good_health']) && $patient['good_health'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">Yes</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="good_health" value="0"
                                                                            <?php echo (isset($patient['good_health']) && $patient['good_health'] == 0) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700">Are
                                                                    you
                                                                    under medical treatment now?</label>
                                                                <div class="mt-2 space-x-4">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="under_treatment" value="1"
                                                                            <?php echo (isset($patient['under_treatment']) && $patient['under_treatment'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">Yes</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="under_treatment" value="0"
                                                                            <?php echo (isset($patient['under_treatment']) && $patient['under_treatment'] == 0) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Medical Conditions -->
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-4">Medical Conditions</h2>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700">Have
                                                                    you
                                                                    had any serious illness or operation?</label>
                                                                <div class="mt-2 space-x-4">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="serious_illness" value="1"
                                                                            <?php echo (isset($patient['serious_illness']) && $patient['serious_illness'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">Yes</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="serious_illness" value="0"
                                                                            <?php echo (isset($patient['serious_illness']) && $patient['serious_illness'] == 0) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700">Have
                                                                    you
                                                                    been hospitalized in the past 5 years?</label>
                                                                <div class="mt-2 space-x-4">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="hospitalized" value="1"
                                                                            <?php echo (isset($patient['hospitalized']) && $patient['hospitalized'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">Yes</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="hospitalized" value="0"
                                                                            <?php echo (isset($patient['hospitalized']) && $patient['hospitalized'] == 0) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Medications and Habits -->
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-4">Medications and Habits</h2>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700">Are
                                                                    you
                                                                    taking any prescription medication?</label>
                                                                <div class="mt-2 space-x-4">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="prescription_medication"
                                                                            value="1" <?php echo (isset($patient['prescription_medication']) && $patient['prescription_medication'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">Yes</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="prescription_medication"
                                                                            value="0" <?php echo (isset($patient['prescription_medication']) && $patient['prescription_medication'] == 0) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700">Do
                                                                    you
                                                                    use tobacco?</label>
                                                                <div class="mt-2 space-x-4">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="tobacco_use" value="1"
                                                                            <?php echo (isset($patient['tobacco_use']) && $patient['tobacco_use'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">Yes</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="tobacco_use" value="0"
                                                                            <?php echo (isset($patient['tobacco_use']) && $patient['tobacco_use'] == 0) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-gray-700">Do
                                                                    you
                                                                    use any other substances?</label>
                                                                <div class="mt-2 space-x-4">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="substance_use" value="1"
                                                                            <?php echo (isset($patient['substance_use']) && $patient['substance_use'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">Yes</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="radio" name="substance_use" value="0"
                                                                            <?php echo (isset($patient['substance_use']) && $patient['substance_use'] == 0) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                        <span class="ml-2">No</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Blood Information -->
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-4">Blood Information</h2>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label for="blood_type"
                                                                    class="block text-sm font-medium text-gray-700">Blood
                                                                    Type</label>
                                                                <select name="blood_type" id="blood_type"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                                    <option value="">Select Blood Type</option>
                                                                    <option value="A+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'A+') ? 'selected' : ''; ?>>
                                                                        A+</option>
                                                                    <option value="A-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'A-') ? 'selected' : ''; ?>>
                                                                        A-</option>
                                                                    <option value="B+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'B+') ? 'selected' : ''; ?>>
                                                                        B+</option>
                                                                    <option value="B-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'B-') ? 'selected' : ''; ?>>
                                                                        B-</option>
                                                                    <option value="AB+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'AB+') ? 'selected' : ''; ?>>AB+
                                                                    </option>
                                                                    <option value="AB-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'AB-') ? 'selected' : ''; ?>>AB-
                                                                    </option>
                                                                    <option value="O+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'O+') ? 'selected' : ''; ?>>
                                                                        O+</option>
                                                                    <option value="O-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'O-') ? 'selected' : ''; ?>>
                                                                        O-</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label for="blood_pressure"
                                                                    class="block text-sm font-medium text-gray-700">Blood
                                                                    Pressure</label>
                                                                <input type="text" name="blood_pressure" id="blood_pressure"
                                                                    value="<?php echo htmlspecialchars($patient['blood_pressure'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Specific Conditions -->
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-4">Specific Conditions</h2>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label
                                                                    class="block text-sm font-medium text-gray-700 mb-2">Check
                                                                    all that apply:</label>
                                                                <div class="space-y-2">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="checkbox" name="heart_attack" value="1"
                                                                            <?php echo (isset($patient['heart_attack']) && $patient['heart_attack'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                        <span class="ml-2">Heart Attack</span>
                                                                    </label>
                                                                    <br>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="checkbox" name="thyroid_problem"
                                                                            value="1" <?php echo (isset($patient['thyroid_problem']) && $patient['thyroid_problem'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                        <span class="ml-2">Thyroid Problem</span>
                                                                    </label>
                                                                    <br>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="checkbox" name="heart_disease"
                                                                            value="1" <?php echo (isset($patient['heart_disease']) && $patient['heart_disease'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                        <span class="ml-2">Heart Disease</span>
                                                                    </label>
                                                                    <br>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="checkbox" name="diabetes" value="1"
                                                                            <?php echo (isset($patient['diabetes']) && $patient['diabetes'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                        <span class="ml-2">Diabetes</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Allergies -->
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-4">Allergies</h2>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label
                                                                    class="block text-sm font-medium text-gray-700 mb-2">Check
                                                                    all that apply:</label>
                                                                <div class="space-y-2">
                                                                    <label class="inline-flex items-center">
                                                                        <input type="checkbox"
                                                                            name="allergy_local_anesthetic" value="1" <?php echo (isset($patient['allergy_local_anesthetic']) && $patient['allergy_local_anesthetic'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                        <span class="ml-2">Local Anesthetic</span>
                                                                    </label>
                                                                    <br>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="checkbox" name="allergy_penicillin"
                                                                            value="1" <?php echo (isset($patient['allergy_penicillin']) && $patient['allergy_penicillin'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                        <span class="ml-2">Penicillin</span>
                                                                    </label>
                                                                    <br>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="checkbox" name="allergy_sulfa_drugs"
                                                                            value="1" <?php echo (isset($patient['allergy_sulfa_drugs']) && $patient['allergy_sulfa_drugs'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                        <span class="ml-2">Sulfa Drugs</span>
                                                                    </label>
                                                                    <br>
                                                                    <label class="inline-flex items-center">
                                                                        <input type="checkbox" name="allergy_aspirin"
                                                                            value="1" <?php echo (isset($patient['allergy_aspirin']) && $patient['allergy_aspirin'] == 1) ? 'checked' : ''; ?>
                                                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                        <span class="ml-2">Aspirin</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>

                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <form>
                                        <fieldset disabled>
                                            <h5 class="font-medium text-gray-900 mb-3">Dental Information</h5>
                                            <div class="space-y-4" id="dentalInfo">
                                                <!-- Dental History Fields -->
                                                <div class="space-y-8 bg-white p-6 rounded-lg shadow">
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-4">Dental History</h2>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div>
                                                                <label for="previous_dentist"
                                                                    class="block text-sm font-medium text-gray-700">Previous
                                                                    Dentist</label>
                                                                <input type="text" name="previous_dentist"
                                                                    id="previous_dentist"
                                                                    value="<?php echo htmlspecialchars($dental['previous_dentist'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                            <div>
                                                                <label for="last_dental_visit"
                                                                    class="block text-sm font-medium text-gray-700">Last
                                                                    Dental
                                                                    Visit</label>
                                                                <input type="date" name="last_dental_visit"
                                                                    id="last_dental_visit"
                                                                    value="<?php echo htmlspecialchars($dental['last_dental_visit'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                            <div>
                                                                <label for="reason_for_consultation"
                                                                    class="block text-sm font-medium text-gray-700">Reason
                                                                    for
                                                                    Dental Consultation</label>
                                                                <input type="text" name="reason_for_consultation"
                                                                    id="reason_for_consultation"
                                                                    value="<?php echo htmlspecialchars($dental['reason_for_consultation'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                            <div>
                                                                <label for="referral"
                                                                    class="block text-sm font-medium text-gray-700">Whom may
                                                                    we
                                                                    thank for referring you?</label>
                                                                <input type="text" name="referral" id="referral"
                                                                    value="<?php echo htmlspecialchars($dental['referral'] ?? ''); ?>"
                                                                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <h3>LAST INTRAORAL EXAMINATION</h3>

                                                <div class="space-y-8 bg-white p-6 rounded-lg shadow">
                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-2">Temporary Teeth (Upper)</h2>
                                                        <div class="grid grid-cols-10 gap-2">

                                                            <?php
                                                            $toothOptions = [
                                                                'D' => 'Decayed',
                                                                'M' => 'Missing due to Caries',
                                                                'F' => 'Filled',
                                                                'I' => 'Caries for Extraction',
                                                                'RF' => 'Root Fragment',
                                                                'MO' => 'Missing Other Causes',
                                                                'Im' => 'Impacted Tooth',
                                                                'J' => 'Jacket Crown',
                                                                'A' => 'Amalgam Filling',
                                                                'A-B' => 'Abutment',
                                                                'P' => 'Pontic',
                                                                'In' => 'Inlay',
                                                                'XO' => 'Extraction due to other causes',
                                                                'X' => 'Extraction due to Caries',
                                                                'Cn' => 'Congenitally Missing',
                                                                'Sp' => 'Supernumerary',
                                                                'FX' => 'Fixed Cure Composite',
                                                                'Rm' => 'Removable Denture',
                                                                '✓' => 'Present Teeth'
                                                            ];

                                                            $toothIds = ['55', '54', '53', '52', '51', '61', '62', '63', '64', '65'];

                                                            foreach ($toothIds as $toothId):
                                                                $selectedVal = $teethData[$toothId] ?? '✓'; // Default to Present Teeth
                                                                ?>
                                                                <div class="flex flex-col items-center m-1">
                                                                    <select name="tooth[<?= $toothId ?>]"
                                                                        class="text-center border rounded p-1 w-24">
                                                                        <?php foreach ($toothOptions as $code => $label): ?>
                                                                            <option value="<?= $code ?>" <?= $selectedVal == $code ? 'selected' : '' ?>>
                                                                                <?= $code ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="text-center text-sm mt-1 font-medium">
                                                                        <?= $toothId ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>


                                                        </div>
                                                    </div>

                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-2">Permanent Teeth (Upper)</h2>
                                                        <div class="grid grid-cols-8 md:grid-cols-16 gap-2">
                                                            <?php $toothIds = [
                                                                '18',
                                                                '17',
                                                                '16',
                                                                '15',
                                                                '14',
                                                                '13',
                                                                '12',
                                                                '11',
                                                                '21',
                                                                '22',
                                                                '23',
                                                                '24',
                                                                '25',
                                                                '26',
                                                                '27',
                                                                '28'
                                                            ];

                                                            foreach ($toothIds as $toothId):
                                                                $selectedVal = $teethData[$toothId] ?? '✓'; // Default ✓ Present Teeth
                                                                ?>
                                                                <div class="flex flex-col items-center m-1">
                                                                    <select name="tooth[<?= $toothId ?>]"
                                                                        class="text-center border rounded p-1 w-24">
                                                                        <?php foreach ($toothOptions as $code => $label): ?>
                                                                            <option value="<?= $code ?>" <?= $selectedVal === $code ? 'selected' : '' ?>>
                                                                                <?= $code ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="text-center text-sm mt-1 font-medium">
                                                                        <?= $toothId ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>

                                                        </div>
                                                    </div>

                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-2">Permanent Teeth (Lower)</h2>
                                                        <div class="grid grid-cols-8 md:grid-cols-16 gap-2">
                                                            <?php

                                                            $toothIds = [
                                                                '48',
                                                                '47',
                                                                '46',
                                                                '45',
                                                                '44',
                                                                '43',
                                                                '42',
                                                                '41',
                                                                '31',
                                                                '32',
                                                                '33',
                                                                '34',
                                                                '35',
                                                                '36',
                                                                '37',
                                                                '38'
                                                            ];

                                                            foreach ($toothIds as $toothId):
                                                                $selectedVal = $teethData[$toothId] ?? '✓'; // default to ✓ Present Teeth
                                                                ?>
                                                                <div class="flex flex-col items-center m-1">
                                                                    <select name="tooth[<?= $toothId ?>]"
                                                                        class="text-center border rounded p-1 w-24">
                                                                        <?php foreach ($toothOptions as $code => $label): ?>
                                                                            <option value="<?= $code ?>" <?= $selectedVal === $code ? 'selected' : '' ?>>
                                                                                <?= $code ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="text-center text-sm mt-1 font-medium">
                                                                        <?= $toothId ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>

                                                        </div>
                                                    </div>

                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-2">Temporary Teeth (Lower)</h2>
                                                        <div class="grid grid-cols-10 gap-2">
                                                            <?php

                                                            $toothIds = ['85', '84', '83', '82', '81', '71', '72', '73', '74', '75'];

                                                            foreach ($toothIds as $toothId):
                                                                $selectedVal = $teethData[$toothId] ?? '✓'; // default to ✓ Present Teeth
                                                                ?>
                                                                <div class="flex flex-col items-center m-1">
                                                                    <select name="tooth[<?= $toothId ?>]"
                                                                        class="text-center border rounded p-1 w-24">
                                                                        <?php foreach ($toothOptions as $code => $label): ?>
                                                                            <option value="<?= $code ?>" <?= $selectedVal === $code ? 'selected' : '' ?>>
                                                                                <?= $code ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="text-center text-sm mt-1 font-medium">
                                                                        <?= $toothId ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>

                                                        </div>
                                                    </div>

                                                    <div>
                                                        <h2 class="text-lg font-semibold mb-2">Other Notes</h2>
                                                        <textarea name="notes" rows="4"
                                                            class="w-full border rounded p-2"><?php echo htmlspecialchars($dental['notes'] ?? ''); ?></textarea>
                                                    </div>

                                                    <div class="section-title">Legend</div>
                                                    <table class="legend-table">
                                                        <tr>
                                                            <td><strong>D</strong> - Decayed</td>
                                                            <td><strong>J</strong> - Jacket Crown</td>
                                                            <td><strong>X</strong> - Extraction due to Caries</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>M</strong> - Missing due to Caries</td>
                                                            <td><strong>A</strong> - Amalgam Filling</td>
                                                            <td><strong>XO</strong> - Extraction due to other causes</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>F</strong> - Filled</td>
                                                            <td><strong>A-B</strong> - Abutment</td>
                                                            <td><strong>✓</strong> - Present Teeth</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>I</strong> - Caries for Extraction</td>
                                                            <td><strong>P</strong> - Pontic</td>
                                                            <td><strong>Cn</strong> - Congenitally Missing</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>RF</strong> - Root Fragment</td>
                                                            <td><strong>In</strong> - Inlay</td>
                                                            <td><strong>Sp</strong> - Supernumerary</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>MO</strong> - Missing Other Causes</td>
                                                            <td><strong>FX</strong> - Fixed Cure Composite</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Im</strong> - Impacted Tooth</td>
                                                            <td><strong>Rm</strong> - Removable Denture</td>
                                                        </tr>
                                                    </table>

                                                    <div class="section-title">Additional Notes</div>
                                                    <ul>
                                                        <li><strong>Periodontal Screening:</strong>
                                                            <ul>
                                                                <li><input type="checkbox" name="periodontal_screening[]"
                                                                        value="Gingivitis" <?php echo (isset($dental['periodontal_screening']) && in_array('Gingivitis', explode(',', $dental['periodontal_screening']))) ? 'checked' : ''; ?>> Gingivitis</li>
                                                                <li><input type="checkbox" name="periodontal_screening[]"
                                                                        value="Early Periodontics" <?php echo (isset($dental['periodontal_screening']) && in_array('Early Periodontics', explode(',', $dental['periodontal_screening']))) ? 'checked' : ''; ?>> Early Periodontics</li>
                                                                <li><input type="checkbox" name="periodontal_screening[]"
                                                                        value="Moderate Periodontics" <?php echo (isset($dental['periodontal_screening']) && in_array('Moderate Periodontics', explode(',', $dental['periodontal_screening']))) ? 'checked' : ''; ?>> Moderate Periodontics</li>
                                                                <li><input type="checkbox" name="periodontal_screening[]"
                                                                        value="Advanced Periodontics" <?php echo (isset($dental['periodontal_screening']) && in_array('Advanced Periodontics', explode(',', $dental['periodontal_screening']))) ? 'checked' : ''; ?>> Advanced Periodontics</li>
                                                            </ul>
                                                        </li>
                                                        <li><strong>Occlusion:</strong>
                                                            <ul>
                                                                <li><input type="checkbox" name="occlusion[]"
                                                                        value="Class (Molar)" <?php echo (isset($dental['occlusion']) && in_array('Class (Molar)', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?>> Class (Molar)</li>
                                                                <li><input type="checkbox" name="occlusion[]"
                                                                        value="Overjet" <?php echo (isset($dental['occlusion']) && in_array('Overjet', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?>> Overjet
                                                                </li>
                                                                <li><input type="checkbox" name="occlusion[]"
                                                                        value="Overbite" <?php echo (isset($dental['occlusion']) && in_array('Overbite', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?>> Overbite
                                                                </li>
                                                                <li><input type="checkbox" name="occlusion[]"
                                                                        value="Midline Deviation" <?php echo (isset($dental['occlusion']) && in_array('Midline Deviation', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?>> Midline Deviation</li>
                                                                <li><input type="checkbox" name="occlusion[]"
                                                                        value="Crossbite" <?php echo (isset($dental['occlusion']) && in_array('Crossbite', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?>>
                                                                    Crossbite
                                                                </li>
                                                            </ul>
                                                        </li>
                                                        <li><strong>Appliances:</strong>
                                                            <ul>
                                                                <li><input type="checkbox" name="appliance[]"
                                                                        value="Orthodontic" <?php echo (isset($dental['appliance']) && in_array('Orthodontic', explode(',', $dental['appliance']))) ? 'checked' : ''; ?>>
                                                                    Orthodontic</li>
                                                                <li><input type="checkbox" name="appliance[]"
                                                                        value="Stayplate" <?php echo (isset($dental['appliance']) && in_array('Stayplate', explode(',', $dental['appliance']))) ? 'checked' : ''; ?>>
                                                                    Stayplate</li>

                                                            </ul>
                                                        </li>
                                                        <li><strong>TMD:</strong>
                                                            <ul>
                                                                <li><input type="checkbox" name="tmd[]" value="Clenching"
                                                                        <?php echo (isset($dental['tmd']) && in_array('Clenching', explode(',', $dental['tmd']))) ? 'checked' : ''; ?>>
                                                                    Clenching</li>
                                                                <li><input type="checkbox" name="tmd[]" value="Clicking"
                                                                        <?php echo (isset($dental['tmd']) && in_array('Clicking', explode(',', $dental['tmd']))) ? 'checked' : ''; ?>>
                                                                    Clicking</li>
                                                                <li><input type="checkbox" name="tmd[]" value="Trismus"
                                                                        <?php echo (isset($dental['tmd']) && in_array('Trismus', explode(',', $dental['tmd']))) ? 'checked' : ''; ?>>
                                                                    Trismus</li>
                                                                <li><input type="checkbox" name="tmd[]" value="Muscle Spasm"
                                                                        <?php echo (isset($dental['tmd']) && in_array('Muscle Spasm', explode(',', $dental['tmd']))) ? 'checked' : ''; ?>> Muscle Spasm
                                                                </li>
                                                            </ul>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </form>

                                </div>

                            <?php else: ?>
                                <div class="flex flex-col items-center justify-center py-12">
                                    <svg class="w-24 h-24 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <p class="text-gray-500 mb-4">No dental or medical records found for this patient.</p>
                                    <p class="text-gray-500 mb-4">Edit the patient profile to add dental and medical
                                        history.</p>
                                    <a href="edit_patient.php?id=<?php echo $patientId; ?>"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-edit mr-2"></i> Edit Patient
                                    </a>
                                </div>
                            <?php endif; ?>
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