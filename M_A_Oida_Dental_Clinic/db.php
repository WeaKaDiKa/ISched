<?php

$servername = "localhost";
$username = "root"; // Baguhin kung may ibang username
$password = ""; // Baguhin kung may password
$dbname = "dental_clinic";

// Gumawa ng database connection
//$conn = new mysqli($servername, $username, $password, $dbname, 3318);

$servername = "sql213.infinityfree.com";
$username = "if0_38975384"; // Baguhin kung may ibang username
$password = "2qHptzN96wi4"; // Baguhin kung may password
$dbname = "if0_38975384_dental_clinic";

// Gumawa ng database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// I-check kung may error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
