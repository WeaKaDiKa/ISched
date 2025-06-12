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

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthdate = $_POST['dob'] ?? '';

    // Update patient data
    $updateQuery = "UPDATE patients SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone_number = ?, 
                    gender = ?, 
                    date_of_birth = ? 
                    WHERE id = ?";

    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ssssssi", $firstName, $lastName, $email, $phoneNumber, $gender, $birthdate, $patientId);

    if ($updateStmt->execute()) {
        // Check if patient profile exists
        $checkProfileQuery = "SELECT * FROM patient_profiles WHERE patient_id = ?";
        $checkProfileStmt = $conn->prepare($checkProfileQuery);
        $checkProfileStmt->bind_param("i", $patientId);
        $checkProfileStmt->execute();
        $profileResult = $checkProfileStmt->get_result();

        // Medical history fields
        $physicianName = trim($_POST['physician_name'] ?? '');
        $physicianSpecialty = trim($_POST['physician_specialty'] ?? '');
        $physicianOfficeAddress = trim($_POST['physician_office_address'] ?? '');
        $physicianOfficeNumber = trim($_POST['physician_office_number'] ?? '');
        $goodHealth = $_POST['good_health'] ? 1 : 0;
        $underTreatment = $_POST['under_treatment'] ? 1 : 0;
        $seriousIllness = $_POST['serious_illness'] ? 1 : 0;
        $hospitalized = $_POST['hospitalized'] ? 1 : 0;
        $prescriptionMedication = $_POST['prescription_medication'] ? 1 : 0;
        $tobaccoUse = $_POST['tobacco_use'] ? 1 : 0;
        $substanceUse = $_POST['substance_use'] ? 1 : 0;

        // Medical details fields
        $bloodType = trim($_POST['blood_type'] ?? '');
        $bloodPressure = trim($_POST['blood_pressure'] ?? '');

        // Disease checkboxes (convert to 0/1)
        $heartAttack = isset($_POST['heart_attack']) ? 1 : 0;
        $thyroidProblem = isset($_POST['thyroid_problem']) ? 1 : 0;
        $heartDisease = isset($_POST['heart_disease']) ? 1 : 0;
        $diabetes = isset($_POST['diabetes']) ? 1 : 0;

        // Allergy checkboxes (convert to 0/1)
        $allergyLocalAnesthetic = isset($_POST['allergy_local_anesthetic']) ? 1 : 0;
        $allergyPenicillin = isset($_POST['allergy_penicillin']) ? 1 : 0;
        $allergySulfaDrugs = isset($_POST['allergy_sulfa_drugs']) ? 1 : 0;
        $allergyAspirin = isset($_POST['allergy_aspirin']) ? 1 : 0;

        $previousDentist = trim($_POST['previous_dentist'] ?? '');
        $lastDentalVisit = trim($_POST['last_dental_visit'] ?? '');
        $reasonForConsultation = trim($_POST['reason_for_consultation'] ?? '');
        $referral = trim($_POST['referral'] ?? '');


        if ($profileResult->num_rows > 0) {
            // Update existing profile with medical and dental info
            $updateProfileQuery = "UPDATE patient_profiles SET 
    physician_name = ?, 
    physician_specialty = ?, 
    physician_office_address = ?, 
    physician_office_number = ?, 
    good_health = ?, 
    under_treatment = ?, 
    serious_illness = ?, 
    hospitalized = ?, 
    prescription_medication = ?, 
    tobacco_use = ?, 
    substance_use = ?, 
    blood_type = ?, 
    blood_pressure = ?, 
    heart_attack = ?, 
    thyroid_problem = ?, 
    heart_disease = ?, 
    diabetes = ?, 
    allergy_local_anesthetic = ?, 
    allergy_penicillin = ?, 
    allergy_sulfa_drugs = ?, 
    allergy_aspirin = ? 
    WHERE patient_id = ?";

            $updateProfileStmt = $conn->prepare($updateProfileQuery);
            $updateProfileStmt->bind_param(
                "ssssiiiiiiissiiiiiiiii",
                $physicianName,
                $physicianSpecialty,
                $physicianOfficeAddress,
                $physicianOfficeNumber,
                $goodHealth,
                $underTreatment,
                $seriousIllness,
                $hospitalized,
                $prescriptionMedication,
                $tobaccoUse,
                $substanceUse,
                $bloodType,
                $bloodPressure,
                $heartAttack,
                $thyroidProblem,
                $heartDisease,
                $diabetes,
                $allergyLocalAnesthetic,
                $allergyPenicillin,
                $allergySulfaDrugs,
                $allergyAspirin,
                $patientId
            );
            $updateProfileStmt->execute();

        } else {
            $createProfileQuery = "INSERT INTO patient_profiles (
    patient_id, physician_name, physician_specialty, physician_office_address, physician_office_number,
    good_health, under_treatment, serious_illness, hospitalized, prescription_medication, 
    tobacco_use, substance_use, blood_type, blood_pressure,
    heart_attack, thyroid_problem, heart_disease, diabetes, 
    allergy_local_anesthetic, allergy_penicillin, allergy_sulfa_drugs, allergy_aspirin
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $createProfileStmt = $conn->prepare($createProfileQuery);
            $createProfileStmt->bind_param(
                "issssiiiiiiissiiiiiii",
                $patientId,
                $physicianName,
                $physicianSpecialty,
                $physicianOfficeAddress,
                $physicianOfficeNumber,
                $goodHealth,
                $underTreatment,
                $seriousIllness,
                $hospitalized,
                $prescriptionMedication,
                $tobaccoUse,
                $substanceUse,
                $bloodType,
                $bloodPressure,
                $heartAttack,
                $thyroidProblem,
                $heartDisease,
                $diabetes,
                $allergyLocalAnesthetic,
                $allergyPenicillin,
                $allergySulfaDrugs,
                $allergyAspirin
            );
            $createProfileStmt->execute();

        }


        $successMessage = "Patient information updated successfully!";

        // Refresh patient data
        $patientStmt->execute();
        $patientResult = $patientStmt->get_result();
        $patient = $patientResult->fetch_assoc();
    } else {
        $errorMessage = "Error updating patient information. Please try again.";
    }

    $toothIds = array_merge(
        ['55', '54', '53', '52', '51', '61', '62', '63', '64', '65'],
        ['18', '17', '16', '15', '14', '13', '12', '11', '21', '22', '23', '24', '25', '26', '27', '28'],
        ['48', '47', '46', '45', '44', '43', '42', '41', '31', '32', '33', '34', '35', '36', '37', '38'],
        ['85', '84', '83', '82', '81', '71', '72', '73', '74', '75']
    );

    $submittedTeeth = $_POST['tooth'] ?? [];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    $teethData = [];
    foreach ($toothIds as $id) {
        $teethData[$id] = isset($submittedTeeth[$id]) ? trim($submittedTeeth[$id]) : "";
    }

    $periodontalScreening = isset($_POST['periodontal_screening']) && is_array($_POST['periodontal_screening'])
        ? implode(',', array_map('trim', $_POST['periodontal_screening']))
        : '';

    $occlusion = isset($_POST['occlusion']) && is_array($_POST['occlusion'])
        ? implode(',', array_map('trim', $_POST['occlusion']))
        : '';

    $appliance = isset($_POST['appliance']) && is_array($_POST['appliance'])
        ? implode(',', array_map('trim', $_POST['appliance']))
        : '';

    $tmd = isset($_POST['tmd']) && is_array($_POST['tmd'])
        ? implode(',', array_map('trim', $_POST['tmd']))
        : '';

    $teethJson = json_encode($teethData);
    $check = $conn->prepare("SELECT 1 FROM dentalhistory WHERE patientid = ?");
    $check->bind_param("i", $patientId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE dentalhistory SET 
        teeth = ?, 
        periodontal_screening = ?, 
        occlusion = ?, 
        appliance = ?, 
        tmd = ?, 
        notes = ?,
        previous_dentist = ?,
        last_dental_visit = ?,
        reason_for_consultation = ?,
        referral = ?
        WHERE patientid = ?");

        $stmt->bind_param(
            "ssssssssssi",
            $teethJson,
            $periodontalScreening,
            $occlusion,
            $appliance,
            $tmd,
            $notes,
            $previousDentist,
            $lastDentalVisit,
            $reasonForConsultation,
            $referral,
            $patientId
        );
    } else {
        $stmt = $conn->prepare("INSERT INTO dentalhistory 
        (patientid, teeth, periodontal_screening, occlusion, appliance, tmd, notes, previous_dentist, last_dental_visit, reason_for_consultation, referral)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "issssssssss",
            $patientId,
            $teethJson,
            $periodontalScreening,
            $occlusion,
            $appliance,
            $tmd,
            $notes,
            $previousDentist,
            $lastDentalVisit,
            $reasonForConsultation,
            $referral
        );
    }

    $stmt->execute();
    header('location: edit_patient.php?id=' . $patientId);
    exit();


}
// Get patient's appointments (both pending and approved)
$appointmentsQuery = "SELECT a.*, 
                     CONCAT('APP-', LPAD(a.id, 6, '0')) as reference_number,
                     FROM appointments a 
                  
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
    <title>Edit Patient - M&A Oida Dental Clinic</title>
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
                        <span class="text-gray-600">Edit Patient</span>
                    </li>
                </ol>
            </nav>

            <!-- Content area -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="max-w-6xl mx-auto">
                    <!-- Success/Error Messages -->
                    <?php if (!empty($successMessage)): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                            <p><?php echo $successMessage; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                            <p><?php echo $errorMessage; ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Patient Info Header -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                                <div
                                    class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if (isset($patient['profile_image']) && !empty($patient['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($patient['profile_image']); ?>"
                                            alt="Patient Profile" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user text-gray-400 text-4xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">
                                        Edit Patient:
                                        <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                    </h1>
                                    <p class="text-gray-600">
                                        Patient ID: <?php echo htmlspecialchars($patient['id']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="view_patient.php?id=<?php echo $patientId; ?>"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-eye mr-2"></i> View Mode
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Edit Form -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold mb-4">Edit Patient Information</h2>

                        <?php if (!empty($successMessage)): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                                role="alert">
                                <span class="block sm:inline"><?php echo $successMessage; ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errorMessage)): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                                role="alert">
                                <span class="block sm:inline"><?php echo $errorMessage; ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Tab Navigation -->
                        <div class="border-b border-gray-200 mb-6">
                            <div class="flex space-x-8">
                                <button type="button"
                                    class="tab-button py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium"
                                    data-tab="info">Patient Info</button>
                                <button type="button"
                                    class="tab-button py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium"
                                    data-tab="medical">Medical History</button>
                                <button type="button"
                                    class="tab-button py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium"
                                    data-tab="dentals">Dental Chart</button>
                            </div>
                        </div>

                        <form method="POST">
                            <!-- Tab Content -->
                            <div id="info-tab" class="tab-content">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Personal Details Column -->
                                    <div>
                                        <h2 class="text-xl font-semibold mb-4">Personal Details</h2>

                                        <!-- First Name -->
                                        <div class="mb-4">
                                            <label for="first_name"
                                                class="block text-sm font-medium text-gray-700">First Name <span
                                                    class="text-red-500">*</span></label>
                                            <input type="text" name="first_name" id="first_name"
                                                value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>"
                                                required readonly
                                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>

                                        <!-- Last Name -->
                                        <div class="mb-4">
                                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last
                                                Name <span class="text-red-500">*</span></label>
                                            <input type="text" name="last_name" id="last_name"
                                                value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>"
                                                required readonly
                                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>

                                        <!-- Email -->
                                        <div class="mb-4">
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email
                                                <span class="text-red-500">*</span></label>
                                            <input type="email" name="email" id="email"
                                                value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>"
                                                required readonly
                                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>

                                        <!-- Phone -->
                                        <div class="mb-4">
                                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone
                                                Number <span class="text-red-500">*</span></label>
                                            <input type="text" name="phone" id="phone"
                                                value="<?php echo htmlspecialchars($patient['phone_number'] ?? ''); ?>"
                                                required readonly
                                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>

                                        <!-- Gender -->
                                        <div class="mb-4">
                                            <label for="gender" class="block text-sm font-medium text-gray-700">Gender
                                                <span class="text-red-500">*</span></label>
                                            <select name="gender" id="gender" required disabled
                                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>

                                        <!-- Date of Birth -->
                                        <div class="mb-4">
                                            <label for="dob" class="block text-sm font-medium text-gray-700">Date of
                                                Birth <span class="text-red-500">*</span></label>
                                            <input type="date" name="dob" id="dob"
                                                value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>"
                                                required readonly
                                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Medical History Tab -->
                            <div id="medical-tab" class="tab-content">
                                <div class="bg-gray-50 p-4 rounded-lg">
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
                                                        <label class="block text-sm font-medium text-gray-700">Are you
                                                            in good health?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="good_health" value="1" <?php echo (isset($patient['good_health']) && $patient['good_health'] == 1) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="good_health" value="0" <?php echo (isset($patient['good_health']) && $patient['good_health'] == 0) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">No</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Are you
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
                                                        <label class="block text-sm font-medium text-gray-700">Have you
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
                                                        <label class="block text-sm font-medium text-gray-700">Have you
                                                            been hospitalized in the past 5 years?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="hospitalized" value="1" <?php echo (isset($patient['hospitalized']) && $patient['hospitalized'] == 1) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="hospitalized" value="0" <?php echo (isset($patient['hospitalized']) && $patient['hospitalized'] == 0) ? 'checked' : ''; ?>
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
                                                        <label class="block text-sm font-medium text-gray-700">Are you
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
                                                        <label class="block text-sm font-medium text-gray-700">Do you
                                                            use tobacco?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="tobacco_use" value="1" <?php echo (isset($patient['tobacco_use']) && $patient['tobacco_use'] == 1) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="tobacco_use" value="0" <?php echo (isset($patient['tobacco_use']) && $patient['tobacco_use'] == 0) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">No</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Do you
                                                            use any other substances?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="substance_use" value="1" <?php echo (isset($patient['substance_use']) && $patient['substance_use'] == 1) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="substance_use" value="0" <?php echo (isset($patient['substance_use']) && $patient['substance_use'] == 0) ? 'checked' : ''; ?>
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
                                                                <input type="checkbox" name="thyroid_problem" value="1"
                                                                    <?php echo (isset($patient['thyroid_problem']) && $patient['thyroid_problem'] == 1) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Thyroid Problem</span>
                                                            </label>
                                                            <br>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="heart_disease" value="1"
                                                                    <?php echo (isset($patient['heart_disease']) && $patient['heart_disease'] == 1) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Heart Disease</span>
                                                            </label>
                                                            <br>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="diabetes" value="1" <?php echo (isset($patient['diabetes']) && $patient['diabetes'] == 1) ? 'checked' : ''; ?>
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
                                                                <input type="checkbox" name="allergy_local_anesthetic"
                                                                    value="1" <?php echo (isset($patient['allergy_local_anesthetic']) && $patient['allergy_local_anesthetic'] == 1) ? 'checked' : ''; ?>
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
                                                                <input type="checkbox" name="allergy_aspirin" value="1"
                                                                    <?php echo (isset($patient['allergy_aspirin']) && $patient['allergy_aspirin'] == 1) ? 'checked' : ''; ?>
                                                                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Aspirin</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dental Chart Tab -->
                            <div id="dentals-tab" class="tab-content">
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
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <a href="patient_record.php"
                                    class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-3">
                                    Cancel
                                </a>
                                <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Save Changes
                                </button>
                            </div>
                        </form>

                        <script>
                            // Tab functionality
                            document.addEventListener('DOMContentLoaded', function () {
                                const tabButtons = document.querySelectorAll('.tab-button');
                                const tabContents = document.querySelectorAll('.tab-content');

                                // Function to handle tab switching
                                function switchTab(tabId) {
                                    // Remove active class from all buttons and contents
                                    tabButtons.forEach(btn => btn.classList.remove('active'));
                                    tabContents.forEach(content => content.classList.remove('active'));

                                    // Add active class to clicked button and corresponding content
                                    const selectedButton = document.querySelector(`.tab-button[data-tab="${tabId}"]`);
                                    const selectedContent = document.getElementById(`${tabId}-tab`);

                                    if (selectedButton) {
                                        selectedButton.classList.add('active');
                                    }
                                    if (selectedContent) {
                                        selectedContent.classList.add('active');
                                    }
                                }

                                // Add event listeners to tab buttons
                                tabButtons.forEach(button => {
                                    button.addEventListener('click', () => {
                                        const tabId = button.getAttribute('data-tab');
                                        switchTab(tabId);
                                    });
                                });

                                // Set initial active tab on page load
                                // Ensure all tabs are inactive before setting the initial active tab
                                tabButtons.forEach(btn => btn.classList.remove('active'));
                                tabContents.forEach(content => content.classList.remove('active'));
                                // Activate the first tab by default
                                const initialTabButton = document.querySelector('.tab-button');
                                if (initialTabButton) {
                                    const initialTabId = initialTabButton.getAttribute('data-tab');
                                    switchTab(initialTabId);
                                }

                            });
                        </script>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>