<?php
require_once('session.php');
require_once('db.php');
require_once('includes/profile_functions.php');

// 1) INITIALIZE
$user = null;

// 2) FETCH LOGGED-IN USER
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("
      SELECT first_name, profile_picture 
        FROM patients 
       WHERE id = ?
    ");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contact Us - ISched of M&A Oida Dental Clinic</title>
  <link rel="stylesheet" href="assets/css/contact.css">
  <?php require_once 'includes/head.php' ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script src="assets/js/contact.js" defer></script>
</head>

<body>
  <header>
    <?php include_once('includes/navbar.php'); ?>
  </header>

  <main class="contact-container">
    <!-- branch photo -->
    <img src="assets/photos/regalado_branch.png" alt="Regalado Branch" class="contact-image">

    <div class="contact-info">
      <h2>Contact Us</h2>
      <p>We would love to hear from you! Whether you have questions about our services, our friendly staff is here to
        help.</p>
      <div class="contact-details">
        <strong>Clinic Address:</strong> Unit A - Lot 30 Blk 9 Regalado Hi-way North Fairview<br>
        <strong>Contact Number:</strong> 0918 578 2346<br>
        <strong>Email:</strong> <a href="mailto:naioby_2007@yahoo.ph">naioby_2007@yahoo.ph</a><br>
        <strong>Social Media:</strong>
        <a href="https://www.facebook.com/mandaoidadental?mibextid=ZbWKwL" target="_blank" rel="noopener">
          <img src="assets/photos/fb-logo.png" alt="Facebook" class="social-logo">
        </a>
      </div>
    </div>
  </main>

  <h2 style="color: #124085; font-weight: bold; text-align: center; margin-top: 2em;">
    Map of North Fairview Branch (Regalado)
  </h2>

  <div id="map-container" class="map-container">
    <iframe id="googleMap"
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3668.0860597628844!2d121.0596292799073!3d14.711035491402756!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b10d18e2494d%3A0x7ad9da87e3339a6d!2sM%20and%20A%20Oida%20Dental%20Clinic!5e1!3m2!1sen!2sph!4v1746116763981!5m2!1sen!2sph"
      width="90%" height="450" style="border:0;" allowfullscreen="" loading="lazy">
    </iframe>
  </div>

  <footer>
    <p class="text-center">© 2025 ISched of M&A Oida Dental Clinic. All Rights Reserved.</p>
  </footer>
</body>

</html>