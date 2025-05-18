<?php
require_once('db.php');
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}
header('Content-Type: application/json');

// Get POST data
$patient_id = $_POST['patient_id'] ?? null;
$services = $_POST['services'] ?? '';
$appointment_date = $_POST['appointment_date'] ?? '';
$appointment_time = $_POST['appointment_time'] ?? '';
$clinic_branch = $_POST['clinic_branch'] ?? 'Maligaya Park Branch';
$status = $_POST['status'] ?? 'pending';

// Validate required fields
if (!$patient_id || !$services || !$appointment_date || !$appointment_time) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert the appointment first to get the ID
    $sql = "INSERT INTO appointments (
        patient_id, 
        services, 
        appointment_date, 
        appointment_time, 
        clinic_branch, 
        status
    ) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'isssss',
        $patient_id,
        $services,
        $appointment_date,
        $appointment_time,
        $clinic_branch,
        $status
    );

    if (!$stmt->execute()) {
        throw new Exception("Error creating appointment: " . $stmt->error);
    }

    $appointment_id = $conn->insert_id;

    // Generate reference number: APP-YYYY-XXXXX where XXXXX is the ID padded with zeros
    $reference_number = sprintf("APP-%d-%05d", date('Y'), $appointment_id);

    // Update the appointment with the reference number
    $sql = "UPDATE appointments SET reference_number = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $reference_number, $appointment_id);

    if (!$stmt->execute()) {
        throw new Exception("Error updating reference number: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Appointment created successfully',
        'appointment_id' => $appointment_id,
        'reference_number' => $reference_number
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>