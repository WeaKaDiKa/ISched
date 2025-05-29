<?php
require_once('db.php');
header('Content-Type: application/json');

$date = isset($_POST['date']) ? $_POST['date'] : null;
$branch = isset($_POST['branch']) ? $_POST['branch'] : null;
/* $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : null; */

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
$res = $conn->query("SELECT slot_time FROM time_slots ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    $all_slots[format_ampm($row['slot_time'])] = format_ampm($row['slot_time']);
}
$response['all_slots'] = $all_slots;

if ($date && $branch) {
    $stmt = $conn->prepare(
        "SELECT appointment_time FROM appointments "
        . " WHERE appointment_date = ? AND clinic_branch = ? AND status = 'booked'"
    );
    $stmt->bind_param('ss', $date, $branch);
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
if (!$date || !$branch) {
    $response['available_slots'] = [];
}

echo json_encode($response);
