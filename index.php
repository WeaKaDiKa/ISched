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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home - ISched of M&A Oida Dental Clinic</title>
  <link rel="stylesheet" href="assets/css/homepage.css">

  <?php require_once 'includes/head.php' ?>
  <script src="assets/js/homepage.js" defer></script>
</head>

<body>
  <header>
    <?php include_once('includes/navbar.php'); ?>
  </header>

  <section id="home" class="hero">
    <div class="hero-text">
      <h1>Welcome to <span class="highlight">ISched of M&A Oida Dental Clinic</span></h1>
      <p><em>Where Every <span class="italic">Smile</span> Matters.</em></p>
      <p class="intro-text">
        With just a few clicks, you can organize your dental appointments using our
        advanced online booking system. iSched will assist you with check-ups,
        treatments, and follow-up appointments with high-level organization specific
        to your needs.
      </p>
      <ul class="features">
        <li>Clean &amp; Safe Environment</li>
        <li>Friendly Staff</li>
        <li>Professional Dentists</li>
      </ul>
    </div>
    <div class="image-container">
      <img src="assets/photos/clinic.jpg" alt="Clinic Front">
      <img src="assets/photos/clinic1.jpg" alt="Dental Chair">
      <img src="assets/photos/regalado_branch.png" alt="Dentists at Work">
      <img src="assets/photos/clinics/veneers.png" alt="Dental Veneers">
    </div>
    <!-- Notification bell is already included in the navbar -->
  </section>

  <footer>
    <p>&copy; 2025 ISched of M&A Oida Dental Clinic. All Rights Reserved.</p>
  </footer>

  <script>
    document.querySelectorAll('.nav-links a, .nav-right a').forEach(link => {
      link.addEventListener('click', () => {
        document.getElementById('nav-toggle').checked = false;
      });
    });
  </script>

</body>

</html>