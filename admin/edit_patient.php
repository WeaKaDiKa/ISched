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
        
        // Additional profile fields
        $occupation = trim($_POST['occupation'] ?? '');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');
        $emergencyPhone = trim($_POST['emergency_phone'] ?? '');
        $medicalHistory = trim($_POST['medical_history'] ?? '');
        
        // Dental history fields
        $nickname = trim($_POST['nickname'] ?? '');
        $homeNumber = trim($_POST['home_number'] ?? '');
        $officeNumber = trim($_POST['office_number'] ?? '');
        $faxNumber = trim($_POST['fax_number'] ?? '');
        $previousDentist = trim($_POST['previous_dentist'] ?? '');
        $lastDentalVisit = $_POST['last_dental_visit'] ?? null;
        $reasonForConsultation = trim($_POST['reason_for_consultation'] ?? '');
        $referral = trim($_POST['referral'] ?? '');
        
        // Medical history fields
        $physicianName = trim($_POST['physician_name'] ?? '');
        $physicianSpecialty = trim($_POST['physician_specialty'] ?? '');
        $physicianOfficeAddress = trim($_POST['physician_office_address'] ?? '');
        $physicianOfficeNumber = trim($_POST['physician_office_number'] ?? '');
        $goodHealth = $_POST['good_health'] ?? null;
        $underTreatment = $_POST['under_treatment'] ?? null;
        $seriousIllness = $_POST['serious_illness'] ?? null;
        $hospitalized = $_POST['hospitalized'] ?? null;
        $prescriptionMedication = $_POST['prescription_medication'] ?? null;
        $tobaccoUse = $_POST['tobacco_use'] ?? null;
        $substanceUse = $_POST['substance_use'] ?? null;
        
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
        
        // Get insurance information
        $insuranceInfo = trim($_POST['insurance_info'] ?? '');
        
        if ($profileResult->num_rows > 0) {
            // Update existing profile with medical history and insurance info
            $updateProfileQuery = "UPDATE patient_profiles SET 
                                  medical_history = ?,
                                  insurance_info = ?
                                  WHERE patient_id = ?";
            
            $updateProfileStmt = $conn->prepare($updateProfileQuery);
            $updateProfileStmt->bind_param("ssi", 
                $medicalHistory,
                $insuranceInfo,
                $patientId
            );
            $updateProfileStmt->execute();
        } else {
            // Create new profile with medical history and insurance info
            $createProfileQuery = "INSERT INTO patient_profiles 
                                 (patient_id, medical_history, insurance_info) 
                                 VALUES (?, ?, ?)";
            
            $createProfileStmt = $conn->prepare($createProfileQuery);
            $createProfileStmt->bind_param("iss", 
                $patientId, 
                $medicalHistory,
                $insuranceInfo
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
}

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
    <title>Edit Patient - M&A Oida Dental Clinic</title>
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
                                <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if (isset($patient['profile_image']) && !empty($patient['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($patient['profile_image']); ?>" alt="Patient Profile" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user text-gray-400 text-4xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">
                                        Edit Patient: <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                    </h1>
                                    <p class="text-gray-600">
                                        Patient ID: <?php echo htmlspecialchars($patient['id']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="view_patient.php?id=<?php echo $patientId; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-eye mr-2"></i> View Mode
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Edit Form -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold mb-4">Edit Patient Information</h2>
                        
                        <?php if (!empty($successMessage)): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline"><?php echo $successMessage; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errorMessage)): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline"><?php echo $errorMessage; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Tab Navigation -->
                        <div class="border-b border-gray-200 mb-6">
                            <div class="flex space-x-8">
                                <button type="button" class="tab-button py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-tab="info">Patient Info</button>
                                <button type="button" class="tab-button py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-tab="medical">Medical History</button>
                                <button type="button" class="tab-button py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-tab="dentals">Dental Chart</button>
                            </div>
                        </div>
                        
                        <form method="POST" action="">
                            <!-- Tab Content -->
                            <div id="info-tab" class="tab-content">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Personal Details Column -->
                                    <div>
                                        <h2 class="text-xl font-semibold mb-4">Personal Details</h2>
                                        
                                        <!-- First Name -->
                                        <div class="mb-4">
                                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                                            <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>" required readonly class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>
                                        
                                        <!-- Last Name -->
                                        <div class="mb-4">
                                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                                            <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>" required readonly class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>
                                        
                                        <!-- Email -->
                                        <div class="mb-4">
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>" required readonly class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>
                                        
                                        <!-- Phone -->
                                        <div class="mb-4">
                                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                                            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($patient['phone_number'] ?? ''); ?>" required readonly class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                        </div>
                                        
                                        <!-- Gender -->
                                        <div class="mb-4">
                                            <label for="gender" class="block text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                                            <select name="gender" id="gender" required disabled class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                <option value="">Select Gender</option>
                                                <option value="Male" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?php echo (isset($patient['gender']) && $patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Date of Birth -->
                                        <div class="mb-4">
                                            <label for="dob" class="block text-sm font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                                            <input type="date" name="dob" id="dob" value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>" required readonly class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
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
                                                        <label for="physician_name" class="block text-sm font-medium text-gray-700">Physician Name</label>
                                                        <input type="text" name="physician_name" id="physician_name" value="<?php echo htmlspecialchars($patient['physician_name'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                    <div>
                                                        <label for="physician_specialty" class="block text-sm font-medium text-gray-700">Specialty</label>
                                                        <input type="text" name="physician_specialty" id="physician_specialty" value="<?php echo htmlspecialchars($patient['physician_specialty'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                    <div>
                                                        <label for="physician_office_address" class="block text-sm font-medium text-gray-700">Office Address</label>
                                                        <input type="text" name="physician_office_address" id="physician_office_address" value="<?php echo htmlspecialchars($patient['physician_office_address'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                    <div>
                                                        <label for="physician_office_number" class="block text-sm font-medium text-gray-700">Office Number</label>
                                                        <input type="text" name="physician_office_number" id="physician_office_number" value="<?php echo htmlspecialchars($patient['physician_office_number'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Health Status -->
                                            <div>
                                                <h2 class="text-lg font-semibold mb-4">Health Status</h2>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Are you in good health?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="good_health" value="1" <?php echo (isset($patient['good_health']) && $patient['good_health'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="good_health" value="0" <?php echo (isset($patient['good_health']) && $patient['good_health'] == 0) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">No</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Are you under medical treatment now?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="under_treatment" value="1" <?php echo (isset($patient['under_treatment']) && $patient['under_treatment'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="under_treatment" value="0" <?php echo (isset($patient['under_treatment']) && $patient['under_treatment'] == 0) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
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
                                                        <label class="block text-sm font-medium text-gray-700">Have you had any serious illness or operation?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="serious_illness" value="1" <?php echo (isset($patient['serious_illness']) && $patient['serious_illness'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="serious_illness" value="0" <?php echo (isset($patient['serious_illness']) && $patient['serious_illness'] == 0) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">No</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Have you been hospitalized in the past 5 years?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="hospitalized" value="1" <?php echo (isset($patient['hospitalized']) && $patient['hospitalized'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="hospitalized" value="0" <?php echo (isset($patient['hospitalized']) && $patient['hospitalized'] == 0) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
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
                                                        <label class="block text-sm font-medium text-gray-700">Are you taking any prescription medication?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="prescription_medication" value="1" <?php echo (isset($patient['prescription_medication']) && $patient['prescription_medication'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="prescription_medication" value="0" <?php echo (isset($patient['prescription_medication']) && $patient['prescription_medication'] == 0) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">No</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Do you use tobacco?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="tobacco_use" value="1" <?php echo (isset($patient['tobacco_use']) && $patient['tobacco_use'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="tobacco_use" value="0" <?php echo (isset($patient['tobacco_use']) && $patient['tobacco_use'] == 0) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">No</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Do you use any other substances?</label>
                                                        <div class="mt-2 space-x-4">
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="substance_use" value="1" <?php echo (isset($patient['substance_use']) && $patient['substance_use'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                                                <span class="ml-2">Yes</span>
                                                            </label>
                                                            <label class="inline-flex items-center">
                                                                <input type="radio" name="substance_use" value="0" <?php echo (isset($patient['substance_use']) && $patient['substance_use'] == 0) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
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
                                                        <label for="blood_type" class="block text-sm font-medium text-gray-700">Blood Type</label>
                                                        <select name="blood_type" id="blood_type" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                            <option value="">Select Blood Type</option>
                                                            <option value="A+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                                            <option value="A-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                                            <option value="B+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                                            <option value="B-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                                            <option value="AB+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                                            <option value="AB-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                                            <option value="O+" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                                            <option value="O-" <?php echo (isset($patient['blood_type']) && $patient['blood_type'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label for="blood_pressure" class="block text-sm font-medium text-gray-700">Blood Pressure</label>
                                                        <input type="text" name="blood_pressure" id="blood_pressure" value="<?php echo htmlspecialchars($patient['blood_pressure'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Specific Conditions -->
                                            <div>
                                                <h2 class="text-lg font-semibold mb-4">Specific Conditions</h2>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Check all that apply:</label>
                                                        <div class="space-y-2">
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="heart_attack" value="1" <?php echo (isset($patient['heart_attack']) && $patient['heart_attack'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Heart Attack</span>
                                                            </label>
                                                            <br>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="thyroid_problem" value="1" <?php echo (isset($patient['thyroid_problem']) && $patient['thyroid_problem'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Thyroid Problem</span>
                                                            </label>
                                                            <br>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="heart_disease" value="1" <?php echo (isset($patient['heart_disease']) && $patient['heart_disease'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Heart Disease</span>
                                                            </label>
                                                            <br>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="diabetes" value="1" <?php echo (isset($patient['diabetes']) && $patient['diabetes'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
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
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Check all that apply:</label>
                                                        <div class="space-y-2">
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="allergy_local_anesthetic" value="1" <?php echo (isset($patient['allergy_local_anesthetic']) && $patient['allergy_local_anesthetic'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Local Anesthetic</span>
                                                            </label>
                                                            <br>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="allergy_penicillin" value="1" <?php echo (isset($patient['allergy_penicillin']) && $patient['allergy_penicillin'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Penicillin</span>
                                                            </label>
                                                            <br>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="allergy_sulfa_drugs" value="1" <?php echo (isset($patient['allergy_sulfa_drugs']) && $patient['allergy_sulfa_drugs'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                                <span class="ml-2">Sulfa Drugs</span>
                                                            </label>
                                                            <br>
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="allergy_aspirin" value="1" <?php echo (isset($patient['allergy_aspirin']) && $patient['allergy_aspirin'] == 1) ? 'checked' : ''; ?> class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
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
                                                         <label for="previous_dentist" class="block text-sm font-medium text-gray-700">Previous Dentist</label>
                                                         <input type="text" name="previous_dentist" id="previous_dentist" value="<?php echo htmlspecialchars($patient['previous_dentist'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                     </div>
                                                     <div>
                                                         <label for="last_dental_visit" class="block text-sm font-medium text-gray-700">Last Dental Visit</label>
                                                         <input type="date" name="last_dental_visit" id="last_dental_visit" value="<?php echo htmlspecialchars($patient['last_dental_visit'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                     </div>
                                                     <div>
                                                         <label for="reason_for_consultation" class="block text-sm font-medium text-gray-700">Reason for Dental Consultation</label>
                                                         <input type="text" name="reason_for_consultation" id="reason_for_consultation" value="<?php echo htmlspecialchars($patient['reason_for_consultation'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                     </div>
                                                     <div>
                                                         <label for="referral" class="block text-sm font-medium text-gray-700">Whom may we thank for referring you?</label>
                                                         <input type="text" name="referral" id="referral" value="<?php echo htmlspecialchars($patient['referral'] ?? ''); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                                     </div>
                                                </div>
                                            </div>
                                        </div>

                                        <h3>LAST INTRAORAL EXAMINATION</h3>

                                        <div class="space-y-8 bg-white p-6 rounded-lg shadow">
                                            <div>
                                                <h2 class="text-lg font-semibold mb-2">Temporary Teeth (Upper)</h2>
                                                <div class="grid grid-cols-10 gap-2">
                                                    <input type="text" name="tooth[55]" placeholder="55" value="<?php echo htmlspecialchars($patient['tooth_55'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[54]" placeholder="54" value="<?php echo htmlspecialchars($patient['tooth_54'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[53]" placeholder="53" value="<?php echo htmlspecialchars($patient['tooth_53'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[52]" placeholder="52" value="<?php echo htmlspecialchars($patient['tooth_52'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[51]" placeholder="51" value="<?php echo htmlspecialchars($patient['tooth_51'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[61]" placeholder="61" value="<?php echo htmlspecialchars($patient['tooth_61'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[62]" placeholder="62" value="<?php echo htmlspecialchars($patient['tooth_62'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[63]" placeholder="63" value="<?php echo htmlspecialchars($patient['tooth_63'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[64]" placeholder="64" value="<?php echo htmlspecialchars($patient['tooth_64'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[65]" placeholder="65" value="<?php echo htmlspecialchars($patient['tooth_65'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                </div>
                                            </div>

                                            <div>
                                                <h2 class="text-lg font-semibold mb-2">Permanent Teeth (Upper)</h2>
                                                <div class="grid grid-cols-8 md:grid-cols-16 gap-2">
                                                    <input type="text" name="tooth[18]" placeholder="18" value="<?php echo htmlspecialchars($patient['tooth_18'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[17]" placeholder="17" value="<?php echo htmlspecialchars($patient['tooth_17'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[16]" placeholder="16" value="<?php echo htmlspecialchars($patient['tooth_16'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[15]" placeholder="15" value="<?php echo htmlspecialchars($patient['tooth_15'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[14]" placeholder="14" value="<?php echo htmlspecialchars($patient['tooth_14'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[13]" placeholder="13" value="<?php echo htmlspecialchars($patient['tooth_13'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[12]" placeholder="12" value="<?php echo htmlspecialchars($patient['tooth_12'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[11]" placeholder="11" value="<?php echo htmlspecialchars($patient['tooth_11'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[21]" placeholder="21" value="<?php echo htmlspecialchars($patient['tooth_21'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[22]" placeholder="22" value="<?php echo htmlspecialchars($patient['tooth_22'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[23]" placeholder="23" value="<?php echo htmlspecialchars($patient['tooth_23'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[24]" placeholder="24" value="<?php echo htmlspecialchars($patient['tooth_24'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[25]" placeholder="25" value="<?php echo htmlspecialchars($patient['tooth_25'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[26]" placeholder="26" value="<?php echo htmlspecialchars($patient['tooth_26'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[27]" placeholder="27" value="<?php echo htmlspecialchars($patient['tooth_27'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[28]" placeholder="28" value="<?php echo htmlspecialchars($patient['tooth_28'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                </div>
                                            </div>

                                            <div>
                                                <h2 class="text-lg font-semibold mb-2">Permanent Teeth (Lower)</h2>
                                                <div class="grid grid-cols-8 md:grid-cols-16 gap-2">
                                                    <input type="text" name="tooth[48]" placeholder="48" value="<?php echo htmlspecialchars($patient['tooth_48'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[47]" placeholder="47" value="<?php echo htmlspecialchars($patient['tooth_47'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[46]" placeholder="46" value="<?php echo htmlspecialchars($patient['tooth_46'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[45]" placeholder="45" value="<?php echo htmlspecialchars($patient['tooth_45'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[44]" placeholder="44" value="<?php echo htmlspecialchars($patient['tooth_44'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[43]" placeholder="43" value="<?php echo htmlspecialchars($patient['tooth_43'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[42]" placeholder="42" value="<?php echo htmlspecialchars($patient['tooth_42'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[41]" placeholder="41" value="<?php echo htmlspecialchars($patient['tooth_41'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[31]" placeholder="31" value="<?php echo htmlspecialchars($patient['tooth_31'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[32]" placeholder="32" value="<?php echo htmlspecialchars($patient['tooth_32'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[33]" placeholder="33" value="<?php echo htmlspecialchars($patient['tooth_33'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[34]" placeholder="34" value="<?php echo htmlspecialchars($patient['tooth_34'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[35]" placeholder="35" value="<?php echo htmlspecialchars($patient['tooth_35'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[36]" placeholder="36" value="<?php echo htmlspecialchars($patient['tooth_36'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[37]" placeholder="37" value="<?php echo htmlspecialchars($patient['tooth_37'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[38]" placeholder="38" value="<?php echo htmlspecialchars($patient['tooth_38'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                </div>
                                            </div>

                                            <div>
                                                <h2 class="text-lg font-semibold mb-2">Temporary Teeth (Lower)</h2>
                                                <div class="grid grid-cols-10 gap-2">
                                                    <input type="text" name="tooth[85]" placeholder="85" value="<?php echo htmlspecialchars($patient['tooth_85'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[84]" placeholder="84" value="<?php echo htmlspecialchars($patient['tooth_84'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[83]" placeholder="83" value="<?php echo htmlspecialchars($patient['tooth_83'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[82]" placeholder="82" value="<?php echo htmlspecialchars($patient['tooth_82'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[81]" placeholder="81" value="<?php echo htmlspecialchars($patient['tooth_81'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[71]" placeholder="71" value="<?php echo htmlspecialchars($patient['tooth_71'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[72]" placeholder="72" value="<?php echo htmlspecialchars($patient['tooth_72'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[73]" placeholder="73" value="<?php echo htmlspecialchars($patient['tooth_73'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[74]" placeholder="74" value="<?php echo htmlspecialchars($patient['tooth_74'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                    <input type="text" name="tooth[75]" placeholder="75" value="<?php echo htmlspecialchars($patient['tooth_75'] ?? ''); ?>" class="text-center border rounded p-1" />
                                                </div>
                                            </div>

                                            <div>
                                                <h2 class="text-lg font-semibold mb-2">Other Notes</h2>
                                                <textarea name="notes" rows="4" class="w-full border rounded p-2"><?php echo htmlspecialchars($patient['notes'] ?? ''); ?></textarea>
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
                                                        <li><input type="checkbox" name="periodontal_screening[]" value="Gingivitis" <?php echo (isset($patient['periodontal_screening']) && in_array('Gingivitis', explode(',', $patient['periodontal_screening']))) ? 'checked' : ''; ?>> Gingivitis</li>
                                                        <li><input type="checkbox" name="periodontal_screening[]" value="Early Periodontics" <?php echo (isset($patient['periodontal_screening']) && in_array('Early Periodontics', explode(',', $patient['periodontal_screening']))) ? 'checked' : ''; ?>> Early Periodontics</li>
                                                        <li><input type="checkbox" name="periodontal_screening[]" value="Moderate Periodontics" <?php echo (isset($patient['periodontal_screening']) && in_array('Moderate Periodontics', explode(',', $patient['periodontal_screening']))) ? 'checked' : ''; ?>> Moderate Periodontics</li>
                                                        <li><input type="checkbox" name="periodontal_screening[]" value="Advanced Periodontics" <?php echo (isset($patient['periodontal_screening']) && in_array('Advanced Periodontics', explode(',', $patient['periodontal_screening']))) ? 'checked' : ''; ?>> Advanced Periodontics</li>
                                                    </ul>
                                                </li>
                                                <li><strong>Occlusion:</strong>
                                                    <ul>
                                                        <li><input type="checkbox" name="occlusion[]" value="Class (Molar)" <?php echo (isset($patient['occlusion']) && in_array('Class (Molar)', explode(',', $patient['occlusion']))) ? 'checked' : ''; ?>> Class (Molar)</li>
                                                        <li><input type="checkbox" name="occlusion[]" value="Overjet" <?php echo (isset($patient['occlusion']) && in_array('Overjet', explode(',', $patient['occlusion']))) ? 'checked' : ''; ?>> Overjet</li>
                                                        <li><input type="checkbox" name="occlusion[]" value="Overbite" <?php echo (isset($patient['occlusion']) && in_array('Overbite', explode(',', $patient['occlusion']))) ? 'checked' : ''; ?>> Overbite</li>
                                                        <li><input type="checkbox" name="occlusion[]" value="Midline Deviation" <?php echo (isset($patient['occlusion']) && in_array('Midline Deviation', explode(',', $patient['occlusion']))) ? 'checked' : ''; ?>> Midline Deviation</li>
                                                        <li><input type="checkbox" name="occlusion[]" value="Crossbite" <?php echo (isset($patient['occlusion']) && in_array('Crossbite', explode(',', $patient['occlusion']))) ? 'checked' : ''; ?>> Crossbite</li>
                                                    </ul>
                                                </li>
                                                <li><strong>Appliances:</strong>
                                                    <ul>
                                                        <li><input type="checkbox" name="appliances[]" value="Orthodontic" <?php echo (isset($patient['appliances']) && in_array('Orthodontic', explode(',', $patient['appliances']))) ? 'checked' : ''; ?>> Orthodontic</li>
                                                        <li><input type="checkbox" name="appliances[]" value="Stayplate" <?php echo (isset($patient['appliances']) && in_array('Stayplate', explode(',', $patient['appliances']))) ? 'checked' : ''; ?>> Stayplate</li>
                                                        <li><input type="checkbox" name="appliances[]" value="Others" <?php echo (isset($patient['appliances']) && in_array('Others', explode(',', $patient['appliances']))) ? 'checked' : ''; ?>> Others</li>
                                                    </ul>
                                                </li>
                                                <li><strong>TMD:</strong>
                                                    <ul>
                                                        <li><input type="checkbox" name="tmd[]" value="Clenching" <?php echo (isset($patient['tmd']) && in_array('Clenching', explode(',', $patient['tmd']))) ? 'checked' : ''; ?>> Clenching</li>
                                                        <li><input type="checkbox" name="tmd[]" value="Clicking" <?php echo (isset($patient['tmd']) && in_array('Clicking', explode(',', $patient['tmd']))) ? 'checked' : ''; ?>> Clicking</li>
                                                        <li><input type="checkbox" name="tmd[]" value="Trismus" <?php echo (isset($patient['tmd']) && in_array('Trismus', explode(',', $patient['tmd']))) ? 'checked' : ''; ?>> Trismus</li>
                                                        <li><input type="checkbox" name="tmd[]" value="Muscle Spasm" <?php echo (isset($patient['tmd']) && in_array('Muscle Spasm', explode(',', $patient['tmd']))) ? 'checked' : ''; ?>> Muscle Spasm</li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end">
                                <a href="patient_record.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-3">
                                    Cancel
                                </a>
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                        
                        <script>
                            // Tab functionality
                            document.addEventListener('DOMContentLoaded', function() {
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
