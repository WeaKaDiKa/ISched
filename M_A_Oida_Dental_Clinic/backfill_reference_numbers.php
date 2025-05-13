<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('db.php');

$sql = "SELECT id FROM appointments WHERE reference_number IS NULL OR reference_number = ''";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$count = 0;
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $reference = 'OIDA-' . str_pad($id, 8, '0', STR_PAD_LEFT);
    $update = $conn->prepare("UPDATE appointments SET reference_number = ? WHERE id = ?");
    if (!$update) {
        echo "Prepare failed for ID $id: " . $conn->error . "<br>";
        continue;
    }
    $update->bind_param("si", $reference, $id);
    if ($update->execute()) {
        echo "Updated appointment ID $id with reference $reference<br>";
        $count++;
    } else {
        echo "Update failed for ID $id: " . $update->error . "<br>";
    }
    $update->close();
}
echo "<br>Updated $count appointments with reference numbers.";
$conn->close(); 