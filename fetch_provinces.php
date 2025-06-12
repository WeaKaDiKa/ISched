<?php
require 'db.php';

$region_id = $_GET['region_id'] ?? null;

$provinces = [];

if ($region_id) {
    $sql = "SELECT province_id, province_name FROM refprovince WHERE region_id = ? ORDER BY province_name";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $region_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Return both ID and name
            $provinces[] = ['province_id' => $row['province_id'], 'province_name' => $row['province_name']];
        }
        $stmt->close();
    }
}

header('Content-Type: application/json; charset=utf-8');

$json = json_encode($provinces, JSON_UNESCAPED_UNICODE);

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
