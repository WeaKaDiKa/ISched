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
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>About Us - ISched of M&A Oida Dental Clinic</title>
  <link rel="stylesheet" href="assets/css/about.css">
  <?php require_once 'includes/head.php' ?>
  <?php include_once('includes/custom_modal.php'); ?>
</head>

<body>
  <header>
    <?php include_once('includes/navbar.php'); ?>
  </header>


  <main>
    <!-- Lead Dentists -->
    <section class="about-section">
      <!-- Title & Description Wrapper -->
      <div class="about-header">
        <h2><span class="highlight">Meet Our</span> <strong>Dental Experts</strong></h2>
        <div class="about-content">
          <p>
            Welcome to <strong style="color: #124085">ISched of M&A Oida Dental Clinic</strong>, where excellence in
            dental care has been a tradition for
            <strong style="color: #124085">over 24 years</strong>. At the heart of our clinic are
            <strong style="color: #124085">Dr. Marcial Oida</strong> and <strong style="color: #124085">Dr. Ardeen
              Dofiles Oida</strong>, a dedicated married couple who
            lead with both expertise and compassion. Their combined experience and commitment to staying at the
            forefront of dental innovation ensure that every
            patient receives personalized, high-quality treatment. Together, they create a warm and welcoming
            environment
            that has made our clinic a trusted name in the community.
          </p>
        </div>
      </div>

      <!-- Image Grid -->
      <div class="experts-grid">
        <div class="expert">
          <div class="blob blob-left"></div>
          <img src="assets/photos/Dr._Marcial_Oida.png" alt="Dr. Marcial Oida" class="expert-img">
          <div class="expert-label">Dr. Marcial Oida<br><em>Professional Dentist</em></div>
        </div>
        <div class="expert">
          <div class="blob blob-right"></div>
          <img src="assets/photos/Dr._Ardeen_Dofiles_Oida.png" alt="Dr. Ardeen Oida" class="expert-img">
          <div class="expert-label">Dr. Ardeen D. Oida<br><em>Professional Dentist</em></div>
        </div>
        <div class="expert">
          <div class="blob blob-left"></div>
          <img src="assets/photos/Dr._Maribel_Adajar.png" alt="Dr. Maribel Adajar" class="expert-img">
          <div class="expert-label">Dr. Maribel Adajar<br><em>Professional Dentist</em></div>
        </div>
        <div class="expert">
          <div class="blob blob-right"></div>
          <img src="assets/photos/Dr._Joan_Gajeto_Flores.png" alt="Dr. Joan Flores" class="expert-img">
          <div class="expert-label">Dr. Joan G. Flores<br><em>Professional Dentist</em></div>
        </div>
        <div class="expert">
          <div class="blob blob-right"></div>
          <img src="assets\photos\Regalado Branch.png" alt="Lynneth Matuan" class="expert-img">
          <div class="expert-label">Lynneth Matuan<br><em>Dental Helper - North Fairview Branch</em></div>
        </div>
      </div>
    </section>





    <footer>
      <p>&copy; 2025 ISched of M&A Oida Dental Clinic. All Rights Reserved.</p>
    </footer>

    <script>
      // pass PHP login state
      const isLoggedIn = <?= $user ? 'true' : 'false' ?>;
    </script>

    <script>  document.addEventListener("DOMContentLoaded", function () {
        const bellToggle = document.querySelector(".notification-toggle");
        const wrapper = document.querySelector(".notification-wrapper");

        bellToggle.addEventListener("click", function (e) {
          e.stopPropagation();
          wrapper.classList.toggle("show");
        });

        document.addEventListener("click", function (e) {
          if (!wrapper.contains(e.target)) {
            wrapper.classList.remove("show");
          }
        });
      });
    </script>

</body>

</html>