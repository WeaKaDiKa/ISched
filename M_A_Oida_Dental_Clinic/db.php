<?php
require_once '../dbinfo.php';

// Gumawa ng database connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// I-check kung may error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>