<?php
require 'db.php';

$region_id = $_GET['region_id'] ?? null;

$provinces = [];

if ($region_id) {
    $sql = "SELECT province_id, province_name FROM refprovince WHERE region_id = ? ORDER BY province_name";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $region_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Return both ID and name
            $provinces[] = ['province_id' => $row['province_id'], 'province_name' => $row['province_name']];
        }
        $stmt->close();
    }
}

header('Content-Type: application/json');
echo json_encode($provinces);
?> 