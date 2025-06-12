<?php
require 'db.php';

$province_id = $_GET['province_id'] ?? null;
$cities = [];

if ($province_id) {
    $sql = "SELECT municipality_id, municipality_name FROM refcity WHERE province_id = ? ORDER BY municipality_name";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $province_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $cities[] = [
                'municipality_id' => $row['municipality_id'],
                'municipality_name' => $row['municipality_name']
            ];
        }
        $stmt->close();
    }
}

header('Content-Type: application/json; charset=utf-8');

$json = json_encode($cities, JSON_UNESCAPED_UNICODE);

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
