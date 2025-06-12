<?php
require 'db.php';

$city_id = $_GET['city_id'] ?? null;

$barangays = [];

if ($city_id) {
    $sql = "SELECT brgy_id, barangay_name FROM refbrgy WHERE municipality_id = ? ORDER BY barangay_name";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $city_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $barangays[] = ['brgy_id' => $row['brgy_id'], 'barangay_name' => $row['barangay_name']];
        }
        $stmt->close();
    }
}

header('Content-Type: application/json; charset=utf-8');

$json = json_encode($barangays, JSON_UNESCAPED_UNICODE);

// ✅ Check if encoding was successful
if ($json === false) {
    http_response_code(500);
    echo json_encode([
        'error' => 'JSON encoding failed',
        'message' => json_last_error_msg(),
        'code' => json_last_error()
    ]);
} else {
    echo $json;
}
exit;
