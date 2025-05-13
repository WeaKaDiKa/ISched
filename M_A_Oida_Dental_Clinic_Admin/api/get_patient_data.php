<?php
header('Content-Type: application/json');
require_once '../models/Patient.php';

$patient = new Patient();
$response = ['success' => false, 'data' => null, 'message' => ''];

if (isset($_GET['patient_id'])) {
    $patientId = $_GET['patient_id'];
    
    // Get all patient information
    $patientDetails = $patient->getPatientDetails($patientId);
    $appointments = $patient->getPatientAppointments($patientId);
    $treatments = $patient->getPatientTreatments($patientId);
    $medicalHistory = $patient->getMedicalHistory($patientId);

    if ($patientDetails) {
        $response['success'] = true;
        $response['data'] = [
            'details' => $patientDetails,
            'appointments' => $appointments,
            'treatments' => $treatments,
            'medical_history' => $medicalHistory
        ];
    } else {
        $response['message'] = 'Patient not found';
    }
} else {
    $response['message'] = 'Patient ID is required';
}

echo json_encode($response);
?> 