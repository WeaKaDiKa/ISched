<?php
require_once('db.php');
header('Content-Type: application/json');

$date = isset($_POST['date']) ? $_POST['date'] : null;

$response = ["available_slots" => [], "all_slots" => [], "booked_slots" => []];

// --- TIME FORMATTER FOR UI ---
function format_ampm($time)
{
    $dt = DateTime::createFromFormat('H:i:s', $time);
    if ($dt)
        return strtolower($dt->format('h:i a'));
    return $time;
}

$all_slots = [];
$currentDate = date('Y-m-d');

// Get all possible time slots
if ($date < $currentDate) {
    $response['all_slots'] = [];
} elseif ($date == $currentDate) {
    $res = $conn->query("SELECT slot_time FROM time_slots ORDER BY id ASC");
    while ($row = $res->fetch_assoc()) {
        $formatted_time = format_ampm($row['slot_time']);
        $all_slots[$formatted_time] = $formatted_time;
    }
    $response['all_slots'] = $all_slots;

    // Remove slots that are within 2 hours from current time
    $current_time = date('H:i:s');
    $cutoff_time = strtotime($current_time) + (2 * 60 * 60);
    foreach ($all_slots as $key => $value) {
        if (strtotime($key) < $cutoff_time) {
            unset($all_slots[$key]);
        }
    }
} else {
    $res = $conn->query("SELECT slot_time FROM time_slots ORDER BY id ASC");
    while ($row = $res->fetch_assoc()) {
        $formatted_time = format_ampm($row['slot_time']);
        $all_slots[$formatted_time] = $formatted_time;
    }
    $response['all_slots'] = $all_slots;
}

if ($date) {
    // Count appointments per time slot
    $stmt = $conn->prepare(
        "SELECT appointment_time, COUNT(*) as count 
         FROM appointments 
         WHERE appointment_date = ? AND (status = 'booked' OR status = 'pending')
         GROUP BY appointment_time"
    );
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_counts = [];
    while ($row = $result->fetch_assoc()) {
        $formatted_time = format_ampm($row['appointment_time']);
        $booked_counts[$formatted_time] = $row['count'];
    }
    $stmt->close();

    // Determine available and booked slots
    $available = [];
    $fully_booked = [];

    foreach ($all_slots as $slot => $formatted_time) {
        if (isset($booked_counts[$formatted_time]) && $booked_counts[$formatted_time] >= 2) {
            $fully_booked[] = $formatted_time;
        } else {
            $available[] = $formatted_time;
        }
    }

    $response['available_slots'] = $available;
    $response['booked_slots'] = $fully_booked;
}

// If no date provided, return all slots as unavailable
if (!$date) {
    $response['available_slots'] = [];
}

echo json_encode($response);