<?php
require_once 'db.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No patient ID provided.']);
    exit;
}

$patientId = $_GET['id'];

// Main patient data
$stmt = $conn->prepare("SELECT p.*, p.gender, p.profile_picture 
                        FROM patients p 
                        LEFT JOIN patient_profiles pp ON p.id = pp.patient_id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Patient not found.']);
    exit;
}
$patient = $result->fetch_assoc();

// Appointments (Approved)
$stmt2 = $conn->prepare("SELECT appointment_date, appointment_time, status, 
                         (SELECT name FROM services WHERE id = a.services) AS service_name 
                         FROM appointments a 
                         WHERE a.patient_id = ? AND a.status = 'approved' 
                         ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$stmt2->bind_param("i", $patientId);
$stmt2->execute();
$appointments = $stmt2->get_result();

$upcoming = [];
$past = [];
$today = date('Y-m-d');
while ($row = $appointments->fetch_assoc()) {
    $label = $row['service_name'] . ' — ' . date('M d, Y', strtotime($row['appointment_date'])) . ' at ' . date('g:i A', strtotime($row['appointment_time']));
    if ($row['appointment_date'] >= $today) {
        $upcoming[] = $label;
    } else {
        $past[] = $label;
    }
}

// Medical History
$query = "SELECT * FROM medical_history WHERE patient_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$medical = $result->fetch_assoc();

echo json_encode([
    'id' => $patient['id'],
    'name' => $patient['first_name'] . ' ' . $patient['last_name'],
    'image' => !empty($patient['profile_picture']) ? $patient['profile_picture'] : '',
    'upcoming' => $upcoming,
    'past' => $past,
    'medical' => $medical ?? []
]);
?>