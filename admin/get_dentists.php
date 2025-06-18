<?php
require_once 'db.php';


$query = "SELECT id, first_name as fname, last_name as lname FROM admin_logins WHERE type = 'dentist'";
$result = $conn->query($query);

$dentists = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dentists[] = $row;
    }
}

echo json_encode($dentists);
$conn->close();
