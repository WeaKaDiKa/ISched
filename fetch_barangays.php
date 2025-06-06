<?php
require 'db.php';

$city_id = $_GET['city_id'] ?? null;

$barangays = [];

if ($city_id) {
    $sql = "SELECT brgy_id, barangay_name FROM refbrgy WHERE municipality_id = ? ORDER BY barangay_name";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $city_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $barangays[] = ['brgy_id' => $row['brgy_id'], 'barangay_name' => $row['barangay_name']];
        }
        $stmt->close();
    }
}

header('Content-Type: application/json');
echo json_encode($barangays);
?> 