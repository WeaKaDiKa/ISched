<?php
// Include database connection
require_once 'db.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_POST['date']) || !isset($_POST['branch'])) {
    echo json_encode([
        'error' => 'Missing required parameters',
        'available_slots' => []
    ]);
    exit;
}

// Get and sanitize parameters
$date = $conn->real_escape_string($_POST['date']);
$branch = $conn->real_escape_string($_POST['branch']);

// Define all possible time slots (10:00 AM to 7:00 PM, excluding 12:00 PM lunch)
$all_time_slots = ["10:00", "11:00", "13:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00"];

try {
    // Query to get all appointments for this date and branch
    $query = "SELECT appointment_time FROM appointments 
              WHERE appointment_date = ? 
              AND clinic_branch = ? 
              AND status != 'cancelled'";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("ss", $date, $branch);

    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    $result = $stmt->get_result();

    // Collect all booked times
    $booked_times = [];
    while ($row = $result->fetch_assoc()) {
        // Extract the hour:minute part of the appointment time
        if (preg_match('/(\d{1,2}:\d{2})/', $row['appointment_time'], $matches)) {
            $time = $matches[1];
            $booked_times[] = $time;
        }
    }

    // Determine available slots (all slots minus booked slots)
    $available_slots = array_diff($all_time_slots, $booked_times);

    // Return the available slots
    echo json_encode([
        'success' => true,
        'date' => $date,
        'branch' => $branch,
        'available_slots' => array_values($available_slots) // Reset array keys
    ]);

} catch (Exception $e) {
    // Log the error and return an empty result
    error_log("Error in check_availability.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Server error occurred',
        'message' => $e->getMessage(),
        'available_slots' => $all_time_slots // Fallback to all slots available
    ]);
}