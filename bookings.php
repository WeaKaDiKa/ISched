<?php
// Disable error display for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ini_set('display_errors', 0);
}

require_once('session.php');
require_once('db.php');
require_once('includes/profile_functions.php');

// 1) INITIALIZE
$user = null;

// 2) FETCH LOGGED-IN USER
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
      SELECT first_name, profile_picture 
        FROM patients 
       WHERE id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
}

// Check if it's an AJAX request for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Buffer output to prevent any PHP warnings or notices from breaking JSON
    ob_start();
    
    // Make sure no output has been sent before setting headers
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    // Process the form submission 
    $response = array();
    $errors = array();
    
    // Combine all section data
    $formData = $_SESSION['form_data'] ?? [];
    $allData = [];
    foreach ($formData as $sectionData) {
        if (is_array($sectionData)) {
            $allData = array_merge($allData, $sectionData);
        }
    }
    
    // Add the current POST data
    $allData = array_merge($allData, $_POST);
    
    // Ensure we have a user ID
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $errors['user_id'] = "User must be logged in to book an appointment";
        $response['success'] = false;
        $response['errors'] = $errors;
        echo json_encode($response);
        exit;
    }
    
    // Debug: Log received data
    error_log("POST data: " . print_r($_POST, true));
    error_log("Combined data: " . print_r($allData, true));
    
    // Validate required fields
    $requiredFields = ['clinic_branch', 'appointment_date', 'appointment_time'];
    foreach ($requiredFields as $field) {
        if (empty($allData[$field])) {
            $errors[$field] = "Field {$field} is required";
        }
    }
    
    try {
        if (empty($errors)) {
            // Calculate total
            $servicePrices = [];
            $servicesQuery = "SELECT id, name, price FROM services WHERE is_active = 1";
            $servicesResult = $conn->query($servicesQuery);
            
            if ($servicesResult && $servicesResult->num_rows > 0) {
                while ($row = $servicesResult->fetch_assoc()) {
                    $servicePrices[$row['name']] = $row['price'];
                }
            }
            
            $total = 0;
            if (!empty($allData['services']) && is_array($allData['services'])) {
                // Check if more than 3 services are selected
                if (count($allData['services']) > 3) {
                    $errors['services'] = 'You can only select up to 3 services';
                    $response['success'] = false;
                    $response['errors'] = $errors;
                    echo json_encode($response);
                    exit;
                }
                foreach ($allData['services'] as $service) {
                    $total += $servicePrices[$service] ?? 0;
                }
            }
            
            // Ensure we have all required fields before proceeding
            foreach (['clinic_branch', 'appointment_date', 'appointment_time'] as $requiredField) {
                if (empty($allData[$requiredField])) {
                    throw new Exception("Required field missing: " . $requiredField);
                }
            }

            // Prevent double booking for same doctor, branch, date, time (robust, label-insensitive)
            // Modified to exclude cancelled appointments so users can rebook after cancellation
            $checkStmt = $conn->prepare(
                "SELECT COUNT(*) as cnt FROM appointments a " .
                "JOIN timeslots t1 ON a.appointment_time = t1.slot_label " .
                "JOIN timeslots t2 ON t2.slot_label = ? " .
                "WHERE a.appointment_date = ? AND a.clinic_branch = ? AND a.doctor_id = ? AND t1.slot_time = t2.slot_time " .
                "AND (a.status = 'booked' OR a.status = 'pending') " .
                "AND a.status != 'cancelled'"
            );
            $checkStmt->bind_param('ssss', $allData['appointment_time'], $allData['appointment_date'], $allData['clinic_branch'], $allData['doctor_id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $row = $checkResult->fetch_assoc();
            if ($row && $row['cnt'] > 0) {
                $errors['appointment_time'] = 'This time slot is already booked for the selected doctor and branch.';
                $response['success'] = false;
                $response['errors'] = $errors;
                echo json_encode($response);
                exit;
            }
            $checkStmt->close();

$medical_history = isset($allData['diseases']) && is_array($allData['diseases']) ? implode(', ', $allData['diseases']) : '';
$allergies = isset($allData['allergies']) && is_array($allData['allergies']) ? implode(', ', $allData['allergies']) : '';

$doctorId = !empty($allData['doctor_id']) ? $allData['doctor_id'] : null;
$status = 'pending';
error_log("Setting appointment status to: $status");

$services_list = !empty($allData['services']) && is_array($allData['services']) ? implode(', ', $allData['services']) : '';
$consent = isset($allData['consent']) ? 1 : 0;
$health = !empty($allData['health']) ? $allData['health'] : 'yes';
$blood_type = !empty($allData['blood_type']) ? $allData['blood_type'] : '';
$blood_pressure = !empty($allData['blood_pressure']) ? $allData['blood_pressure'] : '';

$patientId = (int)$_SESSION['user_id'];

error_log("Inserting appointment with data: " . print_r($allData, true));

$sql = "INSERT INTO appointments (
    patient_id, doctor_id, clinic_branch, appointment_date, appointment_time,
    services, status, consent, blood_type, medical_history, allergies
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    throw new Exception("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "iisssssisss",
    $patientId,
    $doctorId,
    $allData['clinic_branch'],
    $allData['appointment_date'],
    $allData['appointment_time'],
    $services_list,
    $status,
    $consent,
    $blood_type,
    $medical_history,
    $allergies
);

            error_log("About to execute SQL: " . $sql);
            error_log("With params - Patient: $patientId, Branch: {$allData['clinic_branch']}, Date: {$allData['appointment_date']}, Time: {$allData['appointment_time']}");
            
            if ($stmt->execute()) {
                // Get the appointment ID for reference number
                $appointmentId = $conn->insert_id;
                $referenceNumber = 'OIDA-' . str_pad($appointmentId, 8, '0', STR_PAD_LEFT);
                
                // Create admin notification about the new appointment
                try {
                    // First, ensure the admin_notifications table exists
                    $createTableSql = "CREATE TABLE IF NOT EXISTS admin_notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        message VARCHAR(255) NOT NULL,
                        type VARCHAR(50) NOT NULL,
                        reference_id INT,
                        is_read TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    $conn->query($createTableSql);
                    
                    // Get patient name for the notification message
                    $patientQuery = "SELECT first_name, last_name FROM patients WHERE id = ?";
                    $patientStmt = $conn->prepare($patientQuery);
                    $patientStmt->bind_param("i", $patientId);
                    $patientStmt->execute();
                    $patientResult = $patientStmt->get_result();
                    $patientData = $patientResult->fetch_assoc();
                    
                    if ($patientData) {
                        $patientName = $patientData['first_name'] . ' ' . $patientData['last_name'];
                        
                        // Create notification message
                        $notificationMessage = "New appointment booked by {$patientName} for {$allData['appointment_date']} at {$allData['appointment_time']}.";
                        
                        // Insert admin notification
                        $notifStmt = $conn->prepare("INSERT INTO admin_notifications (message, type, reference_id) VALUES (?, 'new_appointment', ?)");
                        $notifStmt->bind_param("si", $notificationMessage, $appointmentId);
                        $notifResult = $notifStmt->execute();
                        
                        if (!$notifResult) {
                            error_log("Failed to insert admin notification: " . $conn->error);
                        } else {
                            error_log("Successfully created admin notification for appointment ID: {$appointmentId}");
                        }
                    } else {
                        error_log("Could not find patient data for ID: {$patientId}");
                    }
                } catch (Exception $e) {
                    // Log detailed error but don't stop the process
                    error_log("Error creating admin notification: " . $e->getMessage());
                    error_log("Error trace: " . $e->getTraceAsString());
                }
                
                // Clear form data
                unset($_SESSION['form_data']);
                unset($_SESSION['current_section']);
                
                // Return success response with reference ID
                $response['success'] = true;
                $response['reference_id'] = $referenceNumber;
                error_log("Appointment created successfully with ID: $appointmentId");
            } else {
                $response['success'] = false;
                $response['error'] = "Database error: " . $stmt->error;
                error_log("Database error: " . $stmt->error);
            }
        } else {
            $response['success'] = false;
            $response['errors'] = $errors;
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['error'] = "Exception: " . $e->getMessage();
        error_log("Exception in appointment submission: " . $e->getMessage());
    }
    
    // Output JSON response and exit
    echo json_encode($response);
    
    // Clean the output buffer and end the script
    $debug_output = ob_get_clean();
    if (!empty($debug_output)) {
        error_log("Debug output captured: " . $debug_output);
    }
    exit;
}

// Initialize key variables
$userData = [];
$errors = [];
$total = 0;
$current_section = $_SESSION['current_section'] ?? 'services';

// Initialize postData from $_POST or session data
$postData = $_POST ?? [];
if (empty($postData) && isset($_SESSION['form_data'])) {
    $postData = $_SESSION['form_data'];
}

// Fetch user data if logged in
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("
SELECT 
    p.first_name, 
    p.middle_name, 
    p.last_name, 
    p.email,
    p.phone_number, 
    p.date_of_birth, 
    p.gender, 
    p.region AS region_id, 
    p.province AS province_id, 
    p.city AS city_id, 
    p.barangay AS barangay_id, 
    p.zip_code,
    p.profile_picture,

  
    COALESCE(reg.region_description, 'Unknown Region') AS region_name,
    COALESCE(prov.province_name, 'Unknown Province') AS province_name,
    COALESCE(city.municipality_name, 'Unknown City') AS city_name,
    COALESCE(brgy.barangay_name, 'Unknown Barangay') AS barangay_name

FROM patients p
LEFT JOIN refregion reg ON p.region = reg.region_id
LEFT JOIN refprovince prov ON p.province = prov.province_id
LEFT JOIN refcity city ON p.city = city.municipality_id
LEFT JOIN refbrgy brgy ON p.barangay = brgy.brgy_id
WHERE p.id = ?

  ");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $userData = $result->fetch_assoc() ?? [];
}

// Fetch services from database
$services = [];
$servicePrices = [];
$servicesQuery = "SELECT id, name, price, description FROM services WHERE is_active = 1 ORDER BY name";
$servicesResult = $conn->query($servicesQuery);

if ($servicesResult && $servicesResult->num_rows > 0) {
  while ($row = $servicesResult->fetch_assoc()) {
    $services[] = $row;
    $servicePrices[$row['name']] = $row['price'];
  }
} else {
  // Fallback to hardcoded services if database fetch fails
  $servicePrices = [
    'Dental Check-ups & Consultation' => 500,
    'Dental Crown' => 8000,
    'Intraoral X-ray' => 300,
    'Teeth Cleaning (Oral Prophylaxis)' => 1500,
    'Dental Bridges' => 12000,
    'Panoramic X-ray/Full Mouth X-Ray' => 1500,
    'Tooth Extraction' => 2000,
    'Dentures (Partial & Full)' => 15000,
    'TMJ Treatment' => 5000,
    'Dental Fillings (Composite)' => 1500,
    'Root Canal Treatment' => 8000,
    'Teeth Whitening' => 6000,
    'Orthodontic Braces' => 40000,
    'Dental Implant' => 50000,
    'Gum Surgery' => 10000,
    'Wisdom Tooth Extraction' => 5000,
    'Pediatric Dental Care' => 1000,
    'Dental Veneers' => 10000,
    'Night Guard' => 4500,
    'Dental Sealants' => 800,
    'Full Mouth Rehabilitation' => 250000,
    'Sleep Apnea Treatment' => 20000,
    'Dental Emergency Care' => 1000
  ];
}

// Fetch doctors from database
/* $doctors = [];
$doctorsQuery = "SELECT id, first_name, last_name, specialization, clinic_branch FROM doctors WHERE is_active = 1 ORDER BY clinic_branch, last_name";
$doctorsResult = $conn->query($doctorsQuery);

if ($doctorsResult && $doctorsResult->num_rows > 0) {
    while ($row = $doctorsResult->fetch_assoc()) {
        $branch = $row['clinic_branch'];
        if (!isset($doctors[$branch])) {
            $doctors[$branch] = [];
        }
        $doctors[$branch][] = $row;
    }
}
 */
// Convert doctors to JSON for JavaScript
// $doctorsJson = json_encode($doctors);

// Calculate total price for selected services
function calculateTotal($services, $prices) {
$total = 0;
    if (!empty($services) && is_array($services)) {
        foreach ($services as $service) {
            $total += $prices[$service] ?? 0;
        }
    }
    return $total;
}

// Form processing logic
/* if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['current_section'] ?? 'services';
    $errors = [];
    
    // Validate based on current section
    switch ($section) {
        case 'services':
            // At least one service must be selected
            if (empty($_POST['services']) || !is_array($_POST['services'])) {
                $errors['services'] = 'Please select at least one service';
            }
            break;
            
        case 'appointment':
            // Clinic branch validation
            if (empty($_POST['clinic_branch'])) {
                $errors['clinic_branch'] = 'Please select a clinic branch';
            }
            
            // Appointment date validation
            if (empty($_POST['appointment_date'])) {
                $errors['appointment_date'] = 'Please select an appointment date';
            } elseif (strtotime($_POST['appointment_date']) < strtotime(date('Y-m-d'))) {
                $errors['appointment_date'] = 'Appointment date cannot be in the past';
            }
            
            // Appointment time validation
            if (empty($_POST['appointment_time'])) {
                $errors['appointment_time'] = 'Please select an appointment time';
            } else {
                // Check if the time slot is already booked
                $checkBookingQuery = $conn->prepare("
                    SELECT id FROM appointments 
                    WHERE appointment_date = ? 
                    AND appointment_time = ? 
                    AND clinic_branch = ? 
                    AND status = 'booked' OR status = 'pending')
                ");
                
                $checkBookingQuery->bind_param("sss", 
                    $_POST['appointment_date'], 
                    $_POST['appointment_time'], 
                    $_POST['clinic_branch']
                );
                
                $checkBookingQuery->execute();
                $bookingResult = $checkBookingQuery->get_result();
                
                if ($bookingResult->num_rows > 0) {
                    $errors['appointment_time'] = 'This time slot is already booked. Please select another time.';
                }
            }
            break;
            
        case 'payment':
            // Payment section is informational only
            break;
            
        case 'summary':
            // Final validation before submission
            break;
    }
    
    // Handle errors or advance to next section
    if (!empty($errors)) {
        $_SESSION['validation_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    } else {
        // Store form data for this section
        $_SESSION['form_data'][$section] = $_POST;
        
        // Clear validation errors
        unset($_SESSION['validation_errors']);
        
        // Determine next section
        $nextSection = '';
        switch ($section) {
            case 'services': $nextSection = 'appointment'; break;
            case 'appointment': $nextSection = 'payment'; break;
            case 'payment': $nextSection = 'summary'; break;
        }
        
        // Update current section if advancing
        if ($nextSection) {
            $_SESSION['current_section'] = $nextSection;
            $current_section = $nextSection;
        }
        
        // If submitting from summary section, process the appointment booking
        if ($section === 'summary' && isset($_POST['submit'])) {
            // Combine all section data
            $formData = $_SESSION['form_data'] ?? [];
            $allData = [];
            foreach ($formData as $sectionData) {
                $allData = array_merge($allData, $sectionData);
            }
            
            // Calculate total
            $total = calculateTotal($allData['services'] ?? [], $servicePrices);
            
            // Process diseases and allergies arrays
            $medical_history = isset($allData['diseases']) ? implode(', ', $allData['diseases']) : '';
            $allergies = isset($allData['allergies']) ? implode(', ', $allData['allergies']) : '';
            

$stmt = $conn->prepare("
    INSERT INTO appointments (
        patient_id, clinic_branch, appointment_date, appointment_time, 
        services, status, health, pregnant, nursing, birth_control, 
        blood_pressure, blood_type, medical_history, allergies, consent, 
        religion, nationality, occupation, dental_insurance
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
");

$status = 'pending';
$services_list = !empty($allData['services']) && is_array($allData['services']) ? implode(', ', $allData['services']) : '';
$consent = isset($allData['consent']) ? 1 : 0;

$patient_id = $_SESSION['user_id'];
$clinic_branch = $allData['clinic_branch'];
$appointment_date = $allData['appointment_date'];
$appointment_time = $allData['appointment_time'];
$health = $allData['health'];
$pregnant = isset($allData['pregnant']) ? $allData['pregnant'] : null;
$nursing = isset($allData['nursing']) ? $allData['nursing'] : null;
$birth_control = isset($allData['birth_control']) ? $allData['birth_control'] : null;
$blood_pressure = $allData['blood_pressure'];
$blood_type = $allData['blood_type'];
$religion = $allData['religion'];
$nationality = $allData['nationality'];
$occupation = isset($allData['occupation']) ? $allData['occupation'] : null;
$dental_insurance = isset($allData['dental_insurance']) ? $allData['dental_insurance'] : null;

$typeString = 'issssssssssssiisss';

$stmt->bind_param(
    $typeString,
    $patient_id,
    $clinic_branch,
    $appointment_date,
    $appointment_time,
    $services_list,
    $status,
    $health,
    $pregnant,
    $nursing,
    $birth_control,
    $blood_pressure,
    $blood_type,
    $medical_history,
    $allergies,
    $consent,
    $religion,
    $nationality,
    $occupation,
    $dental_insurance
);

        if ($stmt->execute()) {
                // Get the appointment ID for reference number
                $appointmentId = $conn->insert_id;
                $referenceNumber = 'OIDA-' . str_pad($appointmentId, 8, '0', STR_PAD_LEFT);
                
                // Store appointment data in session for success page
                $_SESSION['appointment_data'] = [
                    'reference' => $referenceNumber,
                    'services' => $allData['services'] ?? [],
                    'total' => $total,
                    'date' => $allData['appointment_date'],
                    'time' => $allData['appointment_time'],
                    'branch' => $allData['clinic_branch']
                ];
                
                // Clear form data
                unset($_SESSION['form_data']);
                unset($_SESSION['current_section']);
                
                // Redirect to success page
                // Commented out to favor the AJAX modal display
                // header('Location: appointment_success.php');
                // exit;
                
                // Set success message in session instead
                $_SESSION['appointment_success'] = true;
                $_SESSION['reference_number'] = $referenceNumber;
        } else {
                $errors['database'] = "Database error: " . $conn->error;
            }
        }
    }
} */

// Get any stored errors
$errors = $_SESSION['validation_errors'] ?? $errors;

// Restore form data from session if available
$formData = $_SESSION['form_data'][$current_section] ?? [];

// Set default data for form from session or database
$postData = [];

// Combine data from POST, session form data, and user data from database
foreach ($_POST as $key => $value) {
    $postData[$key] = $value;
}

foreach ($formData as $key => $value) {
    if (!isset($postData[$key])) {
        $postData[$key] = $value;
    }
}

// Calculate total for selected services
$total = calculateTotal($postData['services'] ?? [], $servicePrices);

// Address for clinic branches
$branchAddresses = [
    'Commonwealth Branch' => '123 Commonwealth Ave, Quezon City',
    'North Fairview Branch' => '456 North Fairview, Quezon City',
    'Maligaya Park Branch' => '789 Maligaya Park, Quezon City',
    'San Isidro Branch' => '101 San Isidro St, Manila',
    'Quiapo Branch' => '202 Quiapo, Manila',
    'Kiko Branch' => '303 Kiko Ave, Quezon City',
    'Bagong Silang Branch' => '404 Bagong Silang, Caloocan City'
];

$branchAddress = $branchAddresses[$postData['clinic_branch'] ?? ''] ?? 'Address not available';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Booking - M&A Oida Dental Clinic</title>
    <link rel="stylesheet" href="assets/css/bookings.css?v=1.4">
  <?php require_once 'includes/head.php' ?>
    <link rel="stylesheet" href="assets/css/selected-services.css">
    <link rel="stylesheet" href="assets/css/calendar.css?v=1.0">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        /* Success Modal Styles */
        .success-modal-content {
            max-width: 500px;
            text-align: center;
            padding: 0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .success-header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
        }
        
        .success-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .success-body {
            padding: 20px;
            font-size: 1.1rem;
        }
        
        .booking-reference {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .success-footer {
            padding: 15px;
            background-color: #f1f1f1;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .ok-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .ok-btn:hover {
            background-color: #45a049;
        }
        
        .download-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .download-btn:hover {
            background-color: #0b7dda;
        }
    </style>
    <!-- Pass PHP data to JavaScript -->
  <script>
  window.servicePrices = <?php echo json_encode($servicePrices); ?>;
       // window.doctorsJson = <?php //echo $doctorsJson; ?>;
</script>
    <script src="assets/js/bookings.js?v=1.1" defer></script>
    <script src="assets/js/appointment_schedule.js?v=1.1" defer></script>

</head>
<body>
<header>
  <?php include_once('includes/navbar.php'); ?>
</header>
<div class="container">
    <h1>Online Appointment Form</h1>
        
        <!-- Progress Bar Section -->
    <div class="progress-container">
      <div class="progress-bar flex-row">
                <div class="step <?php echo $current_section === 'services' ? 'active' : ($current_section === 'appointment' || $current_section === 'payment' || $current_section === 'summary' ? 'completed' : ''); ?>">
          <span>1</span>
          <div class="step-label">Services</div>
        </div>
                <div class="step <?php echo $current_section === 'appointment' ? 'active' : ($current_section === 'payment' || $current_section === 'summary' ? 'completed' : ''); ?>">
          <span>2</span>
          <div class="step-label">Appointment</div>
        </div>
                <div class="step <?php echo $current_section === 'payment' ? 'active' : ($current_section === 'summary' ? 'completed' : ''); ?>">
          <span>3</span>
          <div class="step-label">Payment</div>
        </div>
                <div class="step <?php echo $current_section === 'summary' ? 'active' : ''; ?>">
          <span>4</span>
          <div class="step-label">Summary</div>
        </div>
      </div>
    </div>

        <!-- Main Form -->
    <form method="POST" action="process_appointment.php" id="appointmentForm">
    <input type="hidden" name="current_section" value="<?php echo $current_section; ?>">
            
            <!-- Section 1: Services Selection -->
            <div id="section1" class="form-section <?php echo $current_section === 'services' ? 'active' : ''; ?>">
    <h2>Select Your Services</h2>

    <div class="services-header">
        <div class="services-title">Select Dental Services</div>
        <div class="services-subtitle">Click on any service card below to select it. Select at least one dental service for your appointment.</div>
    </div>

    <?php if (isset($errors['services'])): ?>
        <div class="error services-error"><?php echo $errors['services']; ?></div>
    <?php endif; ?>
    <div class="error-message" id="services-error" style="display: none;"></div>

    <div class="services-container d-block d-lg-flex">

        <div class="services-grid mb-3">
            <?php foreach ($services as $service): ?>
            <div class="service-card <?php echo (isset($postData['services']) && is_array($postData['services']) && in_array($service['name'], $postData['services'])) ? 'selected' : ''; ?>" 
                 data-service-name="<?php echo htmlspecialchars($service['name']); ?>"
                 data-service-price="<?php echo $service['price']; ?>">
<div class="d-flex align-items-center justify-content-between">
                <input type="checkbox" id="service-<?php echo htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '-', $service['name'])); ?>" 
                    name="services[]" 
                    value="<?php echo htmlspecialchars($service['name']); ?>" 
                    <?php echo (isset($postData['services']) && is_array($postData['services']) && in_array($service['name'], $postData['services'])) ? 'checked' : ''; ?>
                    class="service-checkbox me-3">
                                 <div class="service-name fw-bold text-end"><?php echo htmlspecialchars($service['name']); ?></div>

</div><hr class="my-1">
                <div class="service-content">
       
                    <?php if (!empty($service['description'])): ?>
                    <div class="service-description fs-6"><?php echo htmlspecialchars($service['description']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="selected-services-panel">
            <div class="selected-services-header">
                <h3>Selected Services</h3>
            </div>
            <div class="selected-services-list" id="selected-services-list">
                <!-- Selected services will be displayed here -->
            </div>

        </div>



    </div>
    <div class="button-group">
        <button type="button" class="next-btn">Next</button>
    </div>
</div>
            
            <!-- Section 2: Appointment Scheduling -->
            <div id="section2" class="form-section <?php echo $current_section === 'appointment' ? 'active' : ''; ?>">
                <h2>Part 2: Schedule Your Appointment</h2>
    
    <div class="form-group">
                    <label class="required">Clinic Branch:</label>
                    <select id="clinic" name="clinic_branch" required>
                        <option value="North Fairview Branch" selected>North Fairview Branch</option>
        </select>
                    <?php if (isset($errors['clinic_branch'])): ?>
                        <div class="error"><?php echo $errors['clinic_branch']; ?></div>
                    <?php endif; ?>
                    <div class="error-message" id="clinic-error" style="display: none;"></div>
    </div>
                
                <!-- Doctor selection will be populated by JS based on branch -->
        <!--         <div class="form-group" id="doctor-container" style="display:none;">
                    <label>Select Doctor:</label>
                    <select id="doctor" name="doctor_id">
                        <option value="">Select a Doctor</option>

                    </select>
                    <div class="error-message" id="doctor-error" style="display: none;"></div>
                </div>
                 -->
                <!-- Calendar and Time Selection -->
                <div class="schedule-container">
                    <h3>Select Date and Time</h3>

    <div class="calendar-container">
        <div class="calendar-header-container">
            <div class="calendar-nav">
                                <button type="button" class="prev-month">&lt;</button>
                                <span class="month-year">January 2025</span>
                                <button type="button" class="next-month">&gt;</button>
            </div>
        </div>
                        
                        <div class="calendar-grid" id="calendar">
                            <!-- Calendar will be populated by JavaScript -->
        </div>
                        
                        <?php if (isset($errors['appointment_date'])): ?>
                            <div class="error"><?php echo $errors['appointment_date']; ?></div>
                        <?php endif; ?>
                        <div class="error-message" id="appointment-date-error" style="display: none;"></div>
                    </div>
                    
        <div class="time-slots-container">
                        <h3>Available Time Slots</h3>
            <div class="time-slots">
                            <!-- Time slots will be populated by JavaScript -->
            </div>
            
                        <?php if (isset($errors['appointment_time'])): ?>
                            <div class="error"><?php echo $errors['appointment_time']; ?></div>
                        <?php endif; ?>
                </div>
                    
                    <div class="selected-schedule-container">
                        <div id="selected-schedule">
                            <strong>Your Selected Appointment:</strong><br>
                            Date: Not selected<br>
                            Time: Not selected<br>
                            Branch: Not selected<br>
                  <!--           Doctor: Not selected -->
            </div>
        </div>
                </div>
                
                <!-- Hidden fields for appointment data -->
                <input type="hidden" id="appointment-date" name="appointment_date" value="<?php echo htmlspecialchars($postData['appointment_date'] ?? ''); ?>">
                <input type="hidden" id="appointment-time" name="appointment_time" value="<?php echo htmlspecialchars($postData['appointment_time'] ?? ''); ?>">
                <input type="hidden" id="appointment-datetime" name="appointment_datetime" value="<?php echo htmlspecialchars($postData['appointment_datetime'] ?? ''); ?>">
        <!--   <input type="hidden" id="selected-doctor-name" name="selected_doctor_name" value="<?php //echo htmlspecialchars($postData['selected_doctor_name'] ?? ''); ?>">
           -->      
                <div class="info-text">
                    <div class="info-icon">i</div>
                    <div>Select your preferred branch, date, and time for your appointment. Time slots shown are available for booking.</div>
    </div>
    
    <div class="button-group">
                    <button type="button" class="prev-btn">Previous</button>
                    <button type="submit" class="next-btn">Next</button>
</div>
</div>
            
            <!-- Section 3: Payment Details -->
            <section id="section3" class="form-section">
                <h2>Payment Details</h2>
                
                <div class="payment-section">
                    <div class="payment-details">
                        <h3>Payment (Estimated Price)</h3>
                        
                        <div id="payment-services-list" class="selected-services-list">
                            <?php if (!empty($_SESSION['selected_services']) && is_array($_SESSION['selected_services'])): ?>
                                <?php 
                                $total = 0;
                                foreach ($_SESSION['selected_services'] as $service): 
                                    $servicePriceInt = intval($service['price']);
                                    $total += $servicePriceInt;
                                ?>
                <div class="service-item">
                                        <div><?php echo htmlspecialchars($service['name']); ?></div>
                                        <div>₱<?php echo number_format($servicePriceInt); ?></div>
                </div>
            <?php endforeach; ?>
                                <div class="total-row">
                                    <div>TOTAL:</div>
                                    <div>₱<?php echo number_format($total); ?></div>
                                </div>
    <?php else: ?>
        <div class="service-item">
            <div>No services selected</div>
            <div>₱0</div>
        </div>
    <div class="total-row">
        <div>TOTAL:</div>
                                    <div>₱0</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <input type="hidden" name="total_price" value="<?php echo isset($total) ? $total : 0; ?>">
                        
                        <div class="notes-section">
                            <h3>Additional Notes (Optional)</h3>
                            <textarea name="additional_notes" placeholder="Any special requests or information we should know?"></textarea>
                    </div>
    </div>
        
                    <div class="payment-notice">
                        <h3>IMPORTANT INFORMATION</h3>
                        
                        <div class="info-section">
                            <div class="info-icon">!</div>
                            <div>
                                <strong>Payment Methods:</strong>
                                <div class="payment-methods">
                                    <div><i class="fas fa-check-circle"></i> Cash</div>
                                    <div><i class="fas fa-check-circle"></i> GCash</div>
                                    <div><i class="fas fa-check-circle"></i> Maya</div>
                                    <div><i class="fas fa-check-circle"></i> Credit/Debit Cards</div>
                                </div>
                                
                                <div class="payment-icons">
                                    <img src="assets/photos/Cash-App-Logo.png" alt="Cash">
                                    <img src="assets/photos/gcash.png" alt="GCash">
                                    <img src="assets/photos/maya.png" alt="Maya">
                                    <img src="assets/photos/visa.png" alt="Credit/Debit Cards">
                                                 <img src="assets/photos/Cash-App-Logo.png" alt="Credit/Debit Cards">
                                </div>
                            </div>
    </div>
        
    <div class="info-section">
        <div class="info-icon">i</div>
        <div>
                                <strong>Payment Accepted After Procedure</strong>
                                <p>Payment is only accepted after your procedure has been completed.</p>
        </div>
    </div>
  
                        <div class="info-section">
                            <div class="info-icon">i</div>
                            <div>
                                <strong>Cancellation Rules:</strong>
        <div class="cancellation-rules">
                                    <div>
                                        <i class="fas fa-check-circle"></i>
                                        <span>If you need to cancel, please do so at least 24 hours before your appointment.</span>
        </div>
                                    <div>
                                        <i class="fas fa-check-circle"></i>
                                        <span>No-shows or late cancellations may affect future appointment scheduling.</span>
                                    </div>
                                </div>
                            </div>
        </div>
    </div>
      </div>
    <div class="button-group">
                    <button type="button" class="prev-btn" >Previous</button>
                    <button type="button" class="next-btn" onclick="showSummary()">Review Booking</button>
</div>
            </section>
            
            <!-- Section 4: Final Summary -->
            <div id="section4" class="form-section <?php echo $current_section === 'summary' ? 'active' : ''; ?>">
                <h2>Part 4: Appointment Summary</h2>
                
                <div class="summary-section">
                    <div class="summary-title">Personal Information</div>
      
      <div class="summary-row">
        <div class="summary-field">
          <label class="summary-label summary-required">Full Name:</label>
          <div class="summary-box" id="summary-name">
            <?php
            // Get name directly from session user data
            if (isset($userData) && !empty($userData)) {
                if (!empty($userData['first_name']) || !empty($userData['last_name'])) {
                    echo htmlspecialchars(trim($userData['first_name'] . ' ' . $userData['last_name']));
                } else {
                    echo "Name not provided";
                }
            } else {
                echo "Name not provided";
            }
            ?>
          </div>
        </div>
        <div class="summary-field">
          <label class="summary-label summary-required">Date of Birth:</label>
          <div class="summary-box" id="summary-dob">
            <?php
            // Get date of birth directly from session user data
            if (isset($userData) && !empty($userData) && !empty($userData['date_of_birth'])) {
                try {
                    echo date('F j, Y', strtotime($userData['date_of_birth']));
                } catch (Exception $e) {
                    echo "Date of birth not available";
                }
            } else {
                echo "Date of birth not provided";
            }
            ?>
          </div>
        </div>
      </div>
      
      <div class="summary-row">
        <div class="summary-field">
                            <label class="summary-label summary-required">Contact Number:</label>
                            <div class="summary-box" id="summary-contact">
                                <?php echo htmlspecialchars($postData['contact_number'] ?? $userData['phone_number'] ?? ''); ?>
        </div>
        </div>
        <div class="summary-field">
                            <label class="summary-label summary-required">Email:</label>
                            <div class="summary-box" id="summary-email">
                                <?php echo htmlspecialchars($postData['email'] ?? $userData['email'] ?? ''); ?>
        </div>
        </div>
      </div>
      
      <div class="summary-row">
        <div class="summary-field">
                            <label class="summary-label summary-required">Address:</label>
                            <div class="summary-box" id="summary-address">
                                <?php 
                                    $address = [
                                        $postData['barangay'] ?? $userData['barangay_name'] ?? '',
                                        $postData['city'] ?? $userData['city_name'] ?? '',
                                        $postData['province'] ?? $userData['province_name'] ?? '',
                                        $postData['region'] ?? $userData['region_name'] ?? '',
                                        $postData['zip_code'] ?? $userData['zip_code'] ?? ''
                                    ];
                                    echo htmlspecialchars(implode(', ', array_filter($address)));
                                ?>
        </div>
        </div>
        </div>
      </div>
      
                <div class="summary-section">
                    <div class="summary-title">Appointment Details</div>
      
      <div class="summary-row">
        <div class="summary-field">
                            <label class="summary-label">Selected Services:</label>
          <div class="summary-services-list">
                                <?php if (!empty($postData['services']) && is_array($postData['services'])): ?>
                                    <?php foreach ($postData['services'] as $service): ?>
              <div class="summary-service-item">
                                        <div class="service-name"><?php echo htmlspecialchars($service); ?></div>
                                        <div class="service-price">₱<?php echo number_format($servicePrices[$service] ?? 0); ?></div>
              </div>
            <?php endforeach; ?>
            <div class="summary-service-total">
                                        <div>TOTAL:</div>
                                        <div>₱<?php echo number_format($total); ?></div>
          </div>
        <?php else: ?>
                                    <div class="summary-service-item">
                                        <div>No services selected</div>
                                    </div>
        <?php endif; ?>
                            </div>
                        </div>
      </div>
      
                    <div class="summary-title">Appointment Schedule:</div>
                    <p>
      <?php 
              /*               $appointmentDate = $postData['appointment_date'] ?? '';
                            if ($appointmentDate) {
                                $formattedDate = date('F j, Y', strtotime($appointmentDate));
                                echo htmlspecialchars($formattedDate);
                            } else {
                                echo 'Date not selected';
                            } */
                        ?>
                        <span id="selected-date-sched"></span>
                        at 
                         <span id="selected-time-sched"></span>
                        <!-- <?php //echo htmlspecialchars($postData['appointment_time'] ?? 'Time not selected'); ?> -->
                    </p>
                    <p>
                             <span id="selected-branch"></span>
                        <?php //echo htmlspecialchars($postData['clinic_branch'] ?? 'Branch not selected'); ?>
                        <?php //echo htmlspecialchars($branchAddress); ?><span id="selected-address"></span>
                    </p>
                    
               <!--      <div class="summary-title">Selected Doctor:</div>
                    <p> -->
                        <?php 
                            // First try to use the selected_doctor_name if available
             /*                if (!empty($postData['selected_doctor_name'])) {
                                echo htmlspecialchars($postData['selected_doctor_name']);
                            } else {
                            $doctorName = 'No doctor selected';
                                
                                // Use doctor_id from the form if it exists (but we won't store it in DB)
                                $doctorId = $postData['doctor_id'] ?? '';
                            
                            if (!empty($doctorId)) {
                                    // Check if doctors table exists and has this ID
                                $doctorQuery = "SELECT first_name, last_name, specialization FROM doctors WHERE id = ?";
                                $stmt = $conn->prepare($doctorQuery);
                                    if ($stmt) {
                                $stmt->bind_param("i", $doctorId);
                                $stmt->execute();
                                $doctorResult = $stmt->get_result();
                                
                                if ($doctorResult && $doctorResult->num_rows > 0) {
                                    $doctor = $doctorResult->fetch_assoc();
                                    $doctorName = "Dr. {$doctor['first_name']} {$doctor['last_name']} ({$doctor['specialization']})";
                                        }
                                }
                            }
                            
                            echo htmlspecialchars($doctorName); 
                            } */
                        ?>
                   <!--  </p> -->
      
      <div class="summary-row">
        <div class="summary-field">
                            <label class="summary-label">Additional Notes:</label>
                            <div class="summary-textarea" id="summary-notes">
                                <?php echo htmlspecialchars($postData['additional_notes'] ?? 'No additional notes'); ?>
                            </div>
        </div>
      </div>
      
      <div class="summary-pdf-box">
                        Click Submit to confirm your appointment
      </div>
      
      <div class="summary-note">
                        Please review all information carefully before submitting. Your appointment will be confirmed via email after submission.
      </div>
    </div>
    
    <div class="button-group">
                    <button type="button" class="prev-btn">Previous</button>
                    <button type="submit" name="submit" id="submit-appointment-btn" class="submit-btn">Submit Appointment</button>
  </div>
  </div>
</form>
        
        <!-- Consent Modal -->
        <div id="consentModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Informed Consent for Dental Treatment</h2>
                <div class="consent-content">
                    <p>I hereby authorize the dentists at M&A Oida Dental Clinic and their staff to perform the procedures and treatments as discussed. I understand that:</p>
                    <ol>
                        <li>The practice of dentistry is not an exact science, and no guarantees can be made as to results.</li>
                        <li>There are risks associated with dental treatment including swelling, bruising, pain, infection, bleeding, nerve damage, and allergic reactions.</li>
                        <li>I may be given local anesthesia and/or sedative drugs to minimize discomfort.</li>
                        <li>I agree to comply with all post-operative instructions and attend necessary follow-up appointments.</li>
                        <li>I have disclosed my complete medical history, including allergies, medications, and existing conditions.</li>
                        <li>I understand that payment is due at the time of treatment.</li>
                    </ol>
                    <p>By checking the consent box on the form, I acknowledge that I have read and understand this informed consent document, had the opportunity to ask questions, and give my consent to proceed with treatment.</p>
                </div>
            </div>
        </div>

<!-- Success Modal -->
<div id="successModal" class="modal">
  <div class="modal-content success-modal-content">
                <span class="close">&times;</span>
    <div class="success-header">
                    <h3>Appointment Successfully Submitted</h3>
    </div>
    <div class="success-body">
      <p>Your appointment has been successfully submitted. Thank you for choosing M&A Oida Dental Clinic.</p>
                    <div class="booking-reference">
                        <p><strong>Booking Reference ID:</strong> <span id="booking-reference-id">Processing...</span></p>
                        <p>Please save this reference ID for your records. You will need it when visiting the clinic.</p>
                    </div>
    </div>
    <div class="success-footer">
      <button class="download-btn" id="download-booking-btn">Download Booking</button>
      <button class="ok-btn" onclick="window.location.href='index.php'">OK</button>
    </div>
  </div>
  </div>
</div>

    <script>
        // Initialize global variables for doctor data
   /*      window.doctorsJson = <?php echo $doctorsJson ?: '{}'; ?>; */
        
        // Function to force update summary data from the database
        function showSummary() {
            // First make an AJAX call to refresh patient data
            fetch('refresh_patient_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    section: 'summary'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Data refreshed:', data);
                
                // Update the UI elements if data was refreshed successfully
                if (data.success) {
                    if (data.name && document.getElementById('summary-name')) {
                        document.getElementById('summary-name').textContent = data.name;
                    }
                    
                    if (data.dob && document.getElementById('summary-dob')) {
                        document.getElementById('summary-dob').textContent = data.dob;
                    }
                }
                
                // Move to summary section
                currentStep = 4;
                showSection(currentStep);
                
                // Update the form's current_section field
                const currentSectionInput = document.querySelector('input[name="current_section"]');
                if (currentSectionInput) {
                    currentSectionInput.value = 'summary';
                }
                
                // Use prepareSummaryView to fill in any missing information
                // that wasn't provided by the server
                setTimeout(() => {
                    // Check if name and DOB fields have content before calling prepareSummaryView
                    const nameField = document.getElementById('summary-name');
                    const dobField = document.getElementById('summary-dob');
                    
                    const nameEmpty = !nameField || !nameField.textContent || 
                                     nameField.textContent.trim() === '' || 
                                     nameField.textContent === 'Name not provided';
                                     
                    const dobEmpty = !dobField || !dobField.textContent || 
                                    dobField.textContent.trim() === '' || 
                                    dobField.textContent === 'Date of birth not provided';
                    
                    if (nameEmpty || dobEmpty) {
                        // Only call prepareSummaryView if we're missing data
                        prepareSummaryView();
                    }
                }, 100);
            })
            .catch(error => {
                console.error('Error refreshing data:', error);
                // Still show summary even if refresh fails
                currentStep = 4;
                showSection(currentStep);
                // Only call prepareSummaryView if the AJAX request failed
                prepareSummaryView();
            });
        }

        // Function to automatically select service from services page
        function autoSelectService() {
            const selectedService = sessionStorage.getItem('selectedService');
            if (selectedService) {
                // Find the service card with matching name
                const serviceCards = document.querySelectorAll('.service-card');
                serviceCards.forEach(card => {
                    const serviceName = card.getAttribute('data-service-name');
                    if (serviceName === selectedService) {
                        // Click the checkbox
                        const checkbox = card.querySelector('.service-checkbox');
                        if (checkbox) {
                            checkbox.checked = true;
                            card.classList.add('selected');
                            // Add to window.selectedServices if not already present
                            if (!window.selectedServices) window.selectedServices = [];
                            const servicePrice = parseInt(card.getAttribute('data-service-price'), 10) || 0;
                            if (!window.selectedServices.some(s => s.name === serviceName)) {
                                window.selectedServices.push({ name: serviceName, price: servicePrice });
                            }
                            // Trigger change event to update the UI
                            const event = new Event('change');
                            checkbox.dispatchEvent(event);
                        }
                    }
                });
                // Update the Selected Services panel and payment summary
                if (typeof updateSelectedServicesUI === 'function') {
                    updateSelectedServicesUI();
                }
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
                if (typeof updatePaymentSummary === 'function') {
                    updatePaymentSummary();
                }
                // Clear the stored service
                sessionStorage.removeItem('selectedService');
            }
        }

        // Call autoSelectService when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            autoSelectService();
        });
    </script>
    
    <!-- Medical History Yes/No Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Map of radio button names to their detail row selectors
            const medicalDetailsMapping = {
                'treatment': '.treatment-details',
                'operation': '.operation-details',
                'hospitalized': '.hospitalized-details',
                'medication': '.medication-details',
                'tobacco': '.tobacco-details',
                'drugs': '.drugs-details'
            };
            
            // Add event listeners to all yes/no radios
            Object.keys(medicalDetailsMapping).forEach(radioName => {
                const yesRadio = document.querySelector(`input[name="${radioName}"][value="yes"]`);
                const noRadio = document.querySelector(`input[name="${radioName}"][value="no"]`);
                const detailsRow = document.querySelector(medicalDetailsMapping[radioName]);
                
                if (yesRadio && noRadio && detailsRow) {
                    // Set initial state based on current selection
                    if (yesRadio.checked) {
                        detailsRow.style.display = 'table-row';
                    } else {
                        detailsRow.style.display = 'none';
                    }
                    
                    // Add event listeners for changes
                    yesRadio.addEventListener('change', function() {
                        if (this.checked) {
                            detailsRow.style.display = 'table-row';
                            // Focus on the input field for better UX
                            const inputField = detailsRow.querySelector('input[type="text"]');
                            if (inputField) {
                                setTimeout(() => inputField.focus(), 50);
                            }
                        }
                    });
                    
                    noRadio.addEventListener('change', function() {
                        if (this.checked) {
                            detailsRow.style.display = 'none';
                            // Clear the text input when "No" is selected
                            const inputField = detailsRow.querySelector('input[type="text"]');
                            if (inputField) {
                                inputField.value = '';
                            }
                        }
                    });
                }
            });
        });
    </script>
    
    <!-- Form Submission and Success Modal Handler -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the appointment form and the success modal
            const appointmentForm = document.getElementById('appointmentForm');
            const successModal = document.getElementById('successModal');
            const referenceIdSpan = document.getElementById('booking-reference-id');
            
            if (appointmentForm) {
                appointmentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Show a loading indicator or disable submit button
                    const submitBtn = document.getElementById('submit-appointment-btn');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Processing...';
                    }
                    
                    // Create FormData object to send the entire form
                    const formData = new FormData(this);
                    
                    // Add a flag to identify AJAX requests
                    formData.append('is_ajax', '1');
                    
                    console.log("Submitting form to: " + appointmentForm.action);
                    
                    // Submit form via fetch API with proper headers
                    fetch(appointmentForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        // Always convert to text first
                        return response.text();
                    })
                    .then(text => {
                        console.log("Raw response:", text);
                        
                        // Try to parse as JSON
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            console.error("Failed to parse response as JSON:", e);
                            console.error("Response content:", text);
                            throw new Error("Could not parse server response as JSON. Please try again later.");
                        }
                        
                        // Re-enable the submit button
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit Appointment';
                        }
                        
                        if (data.success) {
                            // Update the reference ID in the success modal
                            if (referenceIdSpan && data.reference_id) {
                                referenceIdSpan.textContent = data.reference_id;
                            }
                            
                            // Show the success modal
                            if (successModal) {
                                successModal.style.display = 'block';
                                
                                // Add event listener to the modal's close button
                                const closeBtn = successModal.querySelector('.close');
                                if (closeBtn) {
                                    closeBtn.addEventListener('click', function() {
                                        successModal.style.display = 'none';
                                        window.location.href = 'index.php';
                                    });
                                }
                            } else {
                                // Fallback if modal not found
                                alert('Appointment booked successfully! Reference: ' + (data.reference_id || 'Generated'));
                                window.location.href = 'index.php';
                            }
                        } else {
                            // Show error message
                            alert('Error: ' + (data.error || 'Failed to submit appointment. Please try again.'));
                        }
                    })
                    .catch(error => {
                        console.error('Submission error:', error);
                        
                        // Re-enable the submit button
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit Appointment';
                        }
                        
                        // Show error message
                        alert('Error: ' + error.message);
                    });
                });
            }
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target === successModal) {
                    successModal.style.display = 'none';
                    window.location.href = 'index.php';
                }
            });
            
            // Make the OK button redirect to homepage
            const okBtn = document.querySelector('.success-modal-content .ok-btn');
            if (okBtn) {
                okBtn.addEventListener('click', function() {
                    window.location.href = 'index.php';
                });
            }
            
            // Setup download booking button
            const downloadBtn = document.querySelector('.success-modal-content .download-btn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function() {
                    const referenceId = document.getElementById('booking-reference-id').textContent;
                    if (referenceId && referenceId !== 'Processing...') {
                        window.open('download_booking_pdf.php?ref=' + encodeURIComponent(referenceId), '_blank');
                    } else {
                        alert('Please wait for the booking reference to be generated.');
                    }
                });
            }
        });
    </script>
    
    <!-- Service Card Selection Script -->
    <script>
        // Wait for the DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded - initializing service card selection');
            
            // Direct approach to handle service card selection
            function setupServiceCardSelection() {
                // Get all service cards and the selected services list container
                const serviceCards = document.querySelectorAll('.service-card');
                const selectedServicesList = document.getElementById('selected-services-list');
                
                console.log('Found ' + serviceCards.length + ' service cards');
                console.log('Selected services list element:', selectedServicesList);
                
                if (!selectedServicesList) {
                    console.error('Selected services list element not found!');
                    return;
                }
                
                // Function to update the selected services panel
                function updateSelectedServices() {
                    // Clear the current list
                    selectedServicesList.innerHTML = '';
                    
                    // Get all checked checkboxes
                    const checkedServices = document.querySelectorAll('.service-checkbox:checked');
                    console.log('Found ' + checkedServices.length + ' checked services');
                    
                    if (checkedServices.length === 0) {
                        selectedServicesList.innerHTML = '<div class="no-services-selected">No services selected</div>';
                        return;
                    }
                    
                    // Add each selected service to the list
                    checkedServices.forEach(checkbox => {
                        const card = checkbox.closest('.service-card');
                        if (!card) return;
                        
                        const serviceName = card.getAttribute('data-service-name');
                        const servicePrice = card.getAttribute('data-service-price');
                        
                        console.log('Adding selected service:', serviceName, servicePrice);
                        
                        const serviceItem = document.createElement('div');
                        serviceItem.className = 'selected-service-item d-flex justify-content-between';
                        serviceItem.innerHTML = `
                            <div class="selected-service-name">${serviceName}</div>
                            <div class="selected-service-price">₱${servicePrice}</div>
                        `;
                        selectedServicesList.appendChild(serviceItem);
                    });
                }
                
                // Add click event to each service card
                serviceCards.forEach(card => {
                    card.onclick = function(e) {
                        // Find the checkbox inside this card
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        if (!checkbox) {
                            console.error('Checkbox not found in card');
                            return;
                        }
                        
                        // If the click was directly on the checkbox, don't do anything
                        if (e.target === checkbox) {
                            return;
                        }
                        
                        // Toggle the checkbox state
                        checkbox.checked = !checkbox.checked;
                        console.log('Toggled checkbox to:', checkbox.checked);
                        
                        // Toggle the selected class on the card
                        this.classList.toggle('selected', checkbox.checked);
                        
                        // Update the selected services list
                        updateSelectedServices();
                    };
                });
                
                // Also handle checkbox changes directly
                document.querySelectorAll('.service-checkbox').forEach(checkbox => {
                    checkbox.onchange = function() {
                        const card = this.closest('.service-card');
                        if (card) {
                            card.classList.toggle('selected', this.checked);
                            updateSelectedServices();
                        }
                    };
                });
                
                // Initialize the selected services list
                updateSelectedServices();
                
                console.log('Service card selection setup complete');
            }
            
            // Run the setup
            setupServiceCardSelection();
        });
    </script>
</body>
</html>
