<?php
require 'db.php';

$province_id = $_GET['province_id'] ?? null;

$cities = [];

if ($province_id) {
    $sql = "SELECT municipality_id, municipality_name FROM refcity WHERE province_id = ? ORDER BY municipality_name";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $province_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $cities[] = ['municipality_id' => $row['municipality_id'], 'municipality_name' => $row['municipality_name']];
        }
        $stmt->close();
    }
}

header('Content-Type: application/json');
echo json_encode($cities);
?> 