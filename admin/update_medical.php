<?php
require_once 'db.php';

$patient_id = $_POST['patient_id'];
$blood_type = $_POST['blood_type'];
$allergies = $_POST['allergies'];
$blood_pressure = $_POST['blood_pressure'];
$heart_disease = $_POST['heart_disease'];
$diabetes = $_POST['diabetes'];
$current_medications = $_POST['current_medications'];
$medical_conditions = $_POST['medical_conditions'];
$last_physical_exam = $_POST['last_physical_exam'];
$updated_at = date('Y-m-d H:i:s');

// Check if record exists
$check = $conn->prepare("SELECT 1 FROM medical_history WHERE patient_id = ?");
$check->bind_param("i", $patient_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // UPDATE
    $stmt = $conn->prepare("UPDATE medical_history SET 
        blood_type=?, allergies=?, blood_pressure=?, heart_disease=?, diabetes=?,
        current_medications=?, medical_conditions=?, last_physical_exam=?, updated_at=? 
        WHERE patient_id=?");

    $stmt->bind_param(
        "sssssssssi",
        $blood_type,
        $allergies,
        $blood_pressure,
        $heart_disease,
        $diabetes,
        $current_medications,
        $medical_conditions,
        $last_physical_exam,
        $updated_at,
        $patient_id
    );
} else {
    // INSERT
    $stmt = $conn->prepare("INSERT INTO medical_history 
        (patient_id, blood_type, allergies, blood_pressure, heart_disease, diabetes,
        current_medications, medical_conditions, last_physical_exam, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "isssssssss",
        $patient_id,
        $blood_type,
        $allergies,
        $blood_pressure,
        $heart_disease,
        $diabetes,
        $current_medications,
        $medical_conditions,
        $last_physical_exam,
        $updated_at
    );
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}