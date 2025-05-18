<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable if using HTTPS
ini_set('session.use_strict_mode', 1);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$allowed_pages = [
    'index.php',
    'homepage.php',
    'login.php',
    'signup.php',
    'clinics.php',
    'about.php',
    'services.php',
    'reviews.php',
    'contact.php'
];
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) && !in_array($current_page, $allowed_pages)) {
    header("Location: index.php");
    exit();
}
