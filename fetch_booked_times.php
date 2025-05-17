<?php
require_once 'db.php';
header('Content-Type: application/json');

// Validate inputs
if (!isset($_GET['date']) || !isset($_GET['branch'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$date = $_GET['date'];
$branch = $_GET['branch'];
$doctorId = $_GET['doctor'] ?? null; // Optional doctor filter

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

try {
    // Build the query based on whether a doctor is specified
    $query = "
        SELECT appointment_time 
        FROM appointments 
        WHERE appointment_date = ? 
        AND clinic_branch = ? 
        AND status = 'booked'
    ";
    
    $types = "ss"; // string, string
    $params = [$date, $branch];
    
    // Add doctor filter if provided
    if ($doctorId) {
        $query .= " AND doctor_id = ?";
        $types .= "i"; // Add integer type
        $params[] = $doctorId;
    }
    
    // Execute query
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[] = $row['appointment_time'];
    }
    
    // Return just the array of booked times for simpler processing in JavaScript
    echo json_encode($bookedSlots);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>