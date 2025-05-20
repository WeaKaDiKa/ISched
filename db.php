<?php
require_once 'dbinfo.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Gumawa ng database connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// I-check kung may error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$greeting = 'Good Morning,';
date_default_timezone_set('Asia/Manila');
$hour = (int) date('G');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning,';
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = 'Good Afternoon,';
} else {
    $greeting = 'Good Evening,';
}
