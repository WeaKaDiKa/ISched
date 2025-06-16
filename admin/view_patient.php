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

$patientIds = intval($_GET['id']);

// Get patient information
$patientQuery = "SELECT p.*, pp.*,
               reg.region_description as region_name,
               prov.province_name as province_name,
               city.municipality_name as city_name,
               brgy.barangay_name as barangay_name
               FROM patients p 
               LEFT JOIN patient_profiles pp ON p.id = pp.patient_id
               LEFT JOIN refregion reg ON p.region = reg.region_id
               LEFT JOIN refprovince prov ON p.province = prov.province_id
               LEFT JOIN refcity city ON p.city = city.municipality_id
               LEFT JOIN refbrgy brgy ON p.barangay = brgy.brgy_id
               WHERE p.id = ?";
$patientStmt = $conn->prepare($patientQuery);
$patientStmt->bind_param("i", $patientIds);
$patientStmt->execute();
$patientResult = $patientStmt->get_result();
$dentalhistoryQuery = "SELECT *
               FROM dentalhistory 
               WHERE patientid = ?";
$dentalStmt = $conn->prepare($dentalhistoryQuery);
$dentalStmt->bind_param("i", $patientIds);
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
                     CONCAT('APP-', LPAD(a.id, 6, '0')) as reference_number
                     FROM appointments a 
                     WHERE a.patient_id = ? 
                     ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$appointmentsStmt = $conn->prepare($appointmentsQuery);
$appointmentsStmt->bind_param("i", $patientIds);
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
                                        $addressParts = [];
                                        if (!empty($patient['barangay_name']))
                                            $addressParts[] = $patient['barangay_name'];
                                        if (!empty($patient['city_name']))
                                            $addressParts[] = $patient['city_name'];
                                        if (!empty($patient['province_name']))
                                            $addressParts[] = $patient['province_name'];
                                        if (!empty($patient['region_name']))
                                            $addressParts[] = $patient['region_name'];
                                        $addressStr = implode(', ', $addressParts);
                                        echo $addressStr ? htmlspecialchars($addressStr) : 'Not provided';
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
                                <a href="edit_patient.php?id=<?php echo $patientIds; ?>"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-edit mr-2"></i> Edit Records
                                </a>
                            </div>

                            <?php
                            $dentalhistoryQuery = "SELECT * FROM dentalhistory WHERE patientid = ?";
                            $dentalStmt = $conn->prepare($dentalhistoryQuery);
                            $dentalStmt->bind_param("i", $patientIds);
                            $dentalStmt->execute();
                            $dentalResult = $dentalStmt->get_result();
                            if ($dentalResult && $dentalResult->num_rows > 0):
                                $dental = $dentalResult->fetch_assoc();
                                ?>
                           <div class="bg-gray-50 p-4 rounded-lg">
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
                                                        <input type="text" name="previous_dentist" id="previous_dentist"
                                                            value="<?php echo htmlspecialchars($dental['previous_dentist'] ?? ''); ?>"
                                                            class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                    <div>
                                                        <label for="last_dental_visit"
                                                            class="block text-sm font-medium text-gray-700">Last Dental
                                                            Visit</label>
                                                        <input type="date" name="last_dental_visit"
                                                            id="last_dental_visit"
                                                            value="<?php echo htmlspecialchars($dental['last_dental_visit'] ?? ''); ?>"
                                                            class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                    <div>
                                                        <label for="reason_for_consultation"
                                                            class="block text-sm font-medium text-gray-700">Reason for
                                                            Dental Consultation</label>
                                                        <input type="text" name="reason_for_consultation"
                                                            id="reason_for_consultation"
                                                            value="<?php echo htmlspecialchars($dental['reason_for_consultation'] ?? ''); ?>"
                                                            class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                    <div>
                                                        <label for="referral"
                                                            class="block text-sm font-medium text-gray-700">Whom may we
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
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <div class="space-y-1">
                                                    <p><strong>D</strong> - Decayed</p>
                                                    <p><strong>M</strong> - Missing due to Caries</p>
                                                    <p><strong>F</strong> - Filled</p>
                                                    <p><strong>I</strong> - Caries for Extraction</p>
                                                    <p><strong>RF</strong> - Root Fragment</p>
                                                    <p><strong>MO</strong> - Missing Other Causes</p>
                                                    <p><strong>Im</strong> - Impacted Tooth</p>
                                                </div>
                                                <div class="space-y-1">
                                                    <p><strong>J</strong> - Jacket Crown</p>
                                                    <p><strong>A</strong> - Amalgam Filling</p>
                                                    <p><strong>A-B</strong> - Abutment</p>
                                                    <p><strong>P</strong> - Pontic</p>
                                                    <p><strong>In</strong> - Inlay</p>
                                                    <p><strong>FX</strong> - Fixed Cure Composite</p>
                                                    <p><strong>Rm</strong> - Removable Denture</p>
                                                </div>
                                                <div class="space-y-1">
                                                    <p><strong>X</strong> - Extraction due to Caries</p>
                                                    <p><strong>XO</strong> - Extraction due to other causes</p>
                                                    <p><strong>✓</strong> - Present Teeth</p>
                                                    <p><strong>Cn</strong> - Congenitally Missing</p>
                                                    <p><strong>Sp</strong> - Supernumerary</p>
                                                </div>
                                            </div>


                                            <div class="section-title">Additional Notes</div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                                <!-- Periodontal Screening -->
                                                <div>
                                                    <strong class="block mb-2">Periodontal Screening:</strong>
                                                    <ul class="space-y-1">
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="periodontal_screening[]"
                                                                    value="Gingivitis" <?php echo (isset($dental['periodontal_screening']) && in_array('Gingivitis', explode(',', $dental['periodontal_screening']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Gingivitis
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="periodontal_screening[]"
                                                                    value="Early Periodontics" <?php echo (isset($dental['periodontal_screening']) && in_array('Early Periodontics', explode(',', $dental['periodontal_screening']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Early Periodontics
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="periodontal_screening[]"
                                                                    value="Moderate Periodontics" <?php echo (isset($dental['periodontal_screening']) && in_array('Moderate Periodontics', explode(',', $dental['periodontal_screening']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Moderate Periodontics
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="periodontal_screening[]"
                                                                    value="Advanced Periodontics" <?php echo (isset($dental['periodontal_screening']) && in_array('Advanced Periodontics', explode(',', $dental['periodontal_screening']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Advanced Periodontics
                                                            </label>
                                                        </li>
                                                    </ul>
                                                </div>

                                                <!-- Occlusion -->
                                                <div>
                                                    <strong class="block mb-2">Occlusion:</strong>
                                                    <ul class="space-y-1">
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="occlusion[]"
                                                                    value="Class (Molar)" <?php echo (isset($dental['occlusion']) && in_array('Class (Molar)', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Class (Molar)
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="occlusion[]"
                                                                    value="Overjet" <?php echo (isset($dental['occlusion']) && in_array('Overjet', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Overjet
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="occlusion[]"
                                                                    value="Overbite" <?php echo (isset($dental['occlusion']) && in_array('Overbite', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Overbite
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="occlusion[]"
                                                                    value="Midline Deviation" <?php echo (isset($dental['occlusion']) && in_array('Midline Deviation', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Midline Deviation
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="occlusion[]"
                                                                    value="Crossbite" <?php echo (isset($dental['occlusion']) && in_array('Crossbite', explode(',', $dental['occlusion']))) ? 'checked' : ''; ?>
                                                                    class="mr-2">
                                                                Crossbite
                                                            </label>
                                                        </li>
                                                    </ul>
                                                </div>

                                                <!-- Appliances -->
                                                <div>
                                                    <strong class="block mb-2">Appliances:</strong>
                                                    <ul class="space-y-1">
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="appliance[]"
                                                                    value="Orthodontic" <?php echo (isset($dental['appliance']) && in_array('Orthodontic', explode(',', $dental['appliance']))) ? 'checked' : ''; ?>
                                                                    class="mr-2">
                                                                Orthodontic
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="appliance[]"
                                                                    value="Stayplate" <?php echo (isset($dental['appliance']) && in_array('Stayplate', explode(',', $dental['appliance']))) ? 'checked' : ''; ?>
                                                                    class="mr-2">
                                                                Stayplate
                                                            </label>
                                                        </li>
                                                    </ul>
                                                </div>

                                                <!-- TMD -->
                                                <div>
                                                    <strong class="block mb-2">TMD:</strong>
                                                    <ul class="space-y-1">
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="tmd[]" value="Clenching"
                                                                    <?php echo (isset($dental['tmd']) && in_array('Clenching', explode(',', $dental['tmd']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Clenching
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="tmd[]" value="Clicking"
                                                                    <?php echo (isset($dental['tmd']) && in_array('Clicking', explode(',', $dental['tmd']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Clicking
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="tmd[]" value="Trismus"
                                                                    <?php echo (isset($dental['tmd']) && in_array('Trismus', explode(',', $dental['tmd']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Trismus
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="tmd[]" value="Muscle Spasm"
                                                                    <?php echo (isset($dental['tmd']) && in_array('Muscle Spasm', explode(',', $dental['tmd']))) ? 'checked' : ''; ?> class="mr-2">
                                                                Muscle Spasm
                                                            </label>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
    <h5 class="font-medium text-gray-900 mb-3">Dental History (Detailed)</h5>
    <div class="space-y-8 bg-white p-6 rounded-lg shadow">

        <!-- Temporary Teeth (Upper) -->
        <div>
            <h2 class="text-lg font-semibold mb-2">Temporary Teeth (Upper)</h2>
            <div class="grid grid-cols-10 gap-2">
                <?php
                $toothIds = ['55', '54', '53', '52', '51', '61', '62', '63', '64', '65'];
                foreach ($toothIds as $toothId):
                    $selectedVal = $teethData[$toothId] ?? '✓';
                    $selectedLabel = $toothOptions[$selectedVal] ?? $selectedVal;
                ?>
                    <div class="flex flex-col items-center m-1">
                        <input type="text" value="<?= htmlspecialchars($selectedLabel) ?>" disabled
                            class="text-center border rounded p-1 w-24 bg-gray-100 cursor-not-allowed">
                        <div class="text-center text-sm mt-1 font-medium">
                            <?= $toothId ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Permanent Teeth (Upper) -->
        <div>
            <h2 class="text-lg font-semibold mb-2">Permanent Teeth (Upper)</h2>
            <div class="grid grid-cols-8 md:grid-cols-16 gap-2">
                <?php
                $toothIds = ['18', '17', '16', '15', '14', '13', '12', '11', '21', '22', '23', '24', '25', '26', '27', '28'];
                foreach ($toothIds as $toothId):
                    $selectedVal = $teethData[$toothId] ?? '✓';
                    $selectedLabel = $toothOptions[$selectedVal] ?? $selectedVal;
                ?>
                    <div class="flex flex-col items-center m-1">
                        <input type="text" value="<?= htmlspecialchars($selectedLabel) ?>" disabled
                            class="text-center border rounded p-1 w-24 bg-gray-100 cursor-not-allowed">
                        <div class="text-center text-sm mt-1 font-medium">
                            <?= $toothId ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Permanent Teeth (Lower) -->
        <div>
            <h2 class="text-lg font-semibold mb-2">Permanent Teeth (Lower)</h2>
            <div class="grid grid-cols-8 md:grid-cols-16 gap-2">
                <?php
                $toothIds = ['48', '47', '46', '45', '44', '43', '42', '41', '31', '32', '33', '34', '35', '36', '37', '38'];
                foreach ($toothIds as $toothId):
                    $selectedVal = $teethData[$toothId] ?? '✓';
                    $selectedLabel = $toothOptions[$selectedVal] ?? $selectedVal;
                ?>
                    <div class="flex flex-col items-center m-1">
                        <input type="text" value="<?= htmlspecialchars($selectedLabel) ?>" disabled
                            class="text-center border rounded p-1 w-24 bg-gray-100 cursor-not-allowed">
                        <div class="text-center text-sm mt-1 font-medium">
                            <?= $toothId ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Temporary Teeth (Lower) -->
        <div>
            <h2 class="text-lg font-semibold mb-2">Temporary Teeth (Lower)</h2>
            <div class="grid grid-cols-10 gap-2">
                <?php
                $toothIds = ['85', '84', '83', '82', '81', '71', '72', '73', '74', '75'];
                foreach ($toothIds as $toothId):
                    $selectedVal = $teethData[$toothId] ?? '✓';
                    $selectedLabel = $toothOptions[$selectedVal] ?? $selectedVal;
                ?>
                    <div class="flex flex-col items-center m-1">
                        <input type="text" value="<?= htmlspecialchars($selectedLabel) ?>" disabled
                            class="text-center border rounded p-1 w-24 bg-gray-100 cursor-not-allowed">
                        <div class="text-center text-sm mt-1 font-medium">
                            <?= $toothId ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Other Notes -->
        <div>
            <h2 class="text-lg font-semibold mb-2">Other Notes</h2>
            <textarea name="notes" rows="4" disabled
                class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed"><?php echo htmlspecialchars($dental['notes'] ?? ''); ?></textarea>
        </div>

        <!-- Legend -->
        <div class="section-title">Legend</div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="space-y-1">
                <p><strong>D</strong> - Decayed</p>
                <p><strong>M</strong> - Missing due to Caries</p>
                <p><strong>F</strong> - Filled</p>
                <p><strong>I</strong> - Caries for Extraction</p>
                <p><strong>RF</strong> - Root Fragment</p>
                <p><strong>MO</strong> - Missing Other Causes</p>
                <p><strong>Im</strong> - Impacted Tooth</p>
            </div>
            <div class="space-y-1">
                <p><strong>J</strong> - Jacket Crown</p>
                <p><strong>A</strong> - Amalgam Filling</p>
                <p><strong>A-B</strong> - Abutment</p>
                <p><strong>P</strong> - Pontic</p>
                <p><strong>In</strong> - Inlay</p>
                <p><strong>FX</strong> - Fixed Cure Composite</p>
                <p><strong>Rm</strong> - Removable Denture</p>
            </div>
            <div class="space-y-1">
                <p><strong>X</strong> - Extraction due to Caries</p>
                <p><strong>XO</strong> - Extraction due to other causes</p>
                <p><strong>✓</strong> - Present Teeth</p>
                <p><strong>Cn</strong> - Congenitally Missing</p>
                <p><strong>Sp</strong> - Supernumerary</p>
            </div>
        </div>

        <!-- Additional Notes -->
        <div class="section-title">Additional Notes</div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Periodontal Screening -->
            <div>
                <strong class="block mb-2">Periodontal Screening:</strong>
                <ul class="space-y-1">
                    <?php
                    $periodontalValues = isset($dental['periodontal_screening']) ? explode(',', $dental['periodontal_screening']) : [];
                    $options = ['Gingivitis', 'Early Periodontics', 'Moderate Periodontics', 'Advanced Periodontics'];
                    foreach ($options as $option):
                    ?>
                        <li>
                            <label class="inline-flex items-center">
                                <input type="checkbox" disabled 
                                    <?= in_array($option, $periodontalValues) ? 'checked' : '' ?>
                                    class="mr-2">
                                <?= $option ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Occlusion -->
            <div>
                <strong class="block mb-2">Occlusion:</strong>
                <ul class="space-y-1">
                    <?php
                    $occlusionValues = isset($dental['occlusion']) ? explode(',', $dental['occlusion']) : [];
                    $options = ['Class (Molar)', 'Overjet', 'Overbite', 'Midline Deviation', 'Crossbite'];
                    foreach ($options as $option):
                    ?>
                        <li>
                            <label class="inline-flex items-center">
                                <input type="checkbox" disabled 
                                    <?= in_array($option, $occlusionValues) ? 'checked' : '' ?>
                                    class="mr-2">
                                <?= $option ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Appliances -->
            <div>
                <strong class="block mb-2">Appliances:</strong>
                <ul class="space-y-1">
                    <?php
                    $applianceValues = isset($dental['appliance']) ? explode(',', $dental['appliance']) : [];
                    $options = ['Orthodontic', 'Stayplate'];
                    foreach ($options as $option):
                    ?>
                        <li>
                            <label class="inline-flex items-center">
                                <input type="checkbox" disabled 
                                    <?= in_array($option, $applianceValues) ? 'checked' : '' ?>
                                    class="mr-2">
                                <?= $option ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- TMD -->
            <div>
                <strong class="block mb-2">TMD:</strong>
                <ul class="space-y-1">
                    <?php
                    $tmdValues = isset($dental['tmd']) ? explode(',', $dental['tmd']) : [];
                    $options = ['Clenching', 'Clicking', 'Trismus', 'Muscle Spasm'];
                    foreach ($options as $option):
                    ?>
                        <li>
                            <label class="inline-flex items-center">
                                <input type="checkbox" disabled 
                                    <?= in_array($option, $tmdValues) ? 'checked' : '' ?>
                                    class="mr-2">
                                <?= $option ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-file-medical text-gray-300 text-5xl mb-4"></i>
                                    <p class="text-gray-500">No dental or medical records found for this patient.</p>
                                    <a href="edit_patient.php?id=<?php echo $patientIds; ?>"
                                        class="btn btn-primary mt-2">Edit Patient</a>
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