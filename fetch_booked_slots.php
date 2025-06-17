<?php
require_once('db.php');
header('Content-Type: application/json');

$date = isset($_POST['date']) ? $_POST['date'] : null;

$response = ["available_slots" => [], "all_slots" => [], "booked_slots" => []];

// --- TIME FORMATTER FOR UI ---
function format_ampm($time)
{
    // Accepts '10:00:00' or '13:00:00', returns '01:00 pm'
    $dt = DateTime::createFromFormat('H:i:s', $time);
    if ($dt)
        return strtolower($dt->format('h:i a'));
    return $time;
}
// ----------------------------------------

// Fetch all slots from the time_slots table (using 10:00 am format)
$all_slots = [];
$currentDate = date('Y-m-d');

if ($date < $currentDate) {

    $response['all_slots'] = [];
} elseif ($date == $currentDate) {

    $res = $conn->query("SELECT slot_time FROM time_slots ORDER BY id ASC");
    while ($row = $res->fetch_assoc()) {
        $formatted_time = format_ampm($row['slot_time']);
        $all_slots[$formatted_time] = $formatted_time;
    }
    $response['all_slots'] = $all_slots;
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
    $stmt = $conn->prepare(
        "SELECT appointment_time FROM appointments WHERE appointment_date = ? AND (status = 'booked'||status = 'pending')"
    );
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $booked = [];
    while ($row = $result->fetch_assoc()) {
        $booked[] = format_ampm($row['appointment_time']);
    }
    $stmt->close();
    // Compute available by excluding booked keys
    $available = array_diff(array_keys($all_slots), $booked);
    $response['available_slots'] = array_values($available);
    $response['booked_slots'] = array_values($booked);
}

// If not enough info, return all slots as unavailable
if (!$date) {
    $response['available_slots'] = [];
}

echo json_encode($response);
