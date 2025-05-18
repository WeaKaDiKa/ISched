<?php
// filepath: c:\xampp\htdocs\M_A_Oida_Dental_Clinic\db.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../dbinfo.php';
// Gumawa ng database connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>