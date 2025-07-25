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
  <title>Services - ISched of M&A Oida Dental Clinic</title>
  <link rel="stylesheet" href="assets/css/services.css">
  <?php require_once 'includes/head.php' ?>
  <script src="assets/js/services.js" defer></script>
</head>

<body>
  <header>
    <?php include_once('includes/navbar.php'); ?>
  </header>

  <main>
    <section class="services-section">
      <h2>Dental Services</h2>
      <div class="services-grid">
        <?php
        // list of services with image filename
        $services = [
          ['Dental Check-ups & Consultation', 'checkup.png'],
          ['Teeth Cleaning', 'cleaning.png'],
          ['Tooth Extraction', 'extraction.png'],
          ['Dental Fillings/Dental Bonding', 'fillings.png'],
          ['Gum Treatment and Gingivectomy', 'gum-treatment.png'],
          ['Teeth Whitening', 'whitening.png'],
          ['Dental Veneers', 'veneers.png'],
          ['Metal Braces/Ceramic', 'braces.png'],
          ['Clear Aligners/Retainers', 'retainer.png'],
          ['Dental Crown', 'crowns.png'],
          ['Dental Bridges', 'bridges.png'],
          ['Dentures (Partial & Full)', 'dentures.png'],
          ['Dental Implants', 'implants.png'],
          ['Fluoride Treatment', 'flouride.png'],
          ['Dental Sealants', 'sealants.png'],
          ["Kids' Braces & Orthodontic Care", 'kidsbrace.png'],
          ['Wisdom Tooth Extraction (Odontectomy)', 'wisdomtooth.png'],
          ['Root Canal Treatment', 'rootcanal.png'],
          ['TMJ Treatment', 'tmjtreat.png'],
          ['Intraoral X-ray', 'intraoral.png'],
          ['Panoramic X-ray / Full Mouth X-ray', 'panoramic.png'],
          ['Lateral Cephalometric X-ray', 'cephalometric.png'],
          ['Periapical X-ray / Single Tooth X-ray', 'periapical.png'],
          ['TMJ Transcranial X-ray', 'tmjxray.png'],
        ];
        foreach ($services as $svc):
          // Map display name to exact booking name if needed
          $exactName = $svc[0];
          if ($svc[0] === 'Teeth Cleaning') {
            $exactName = 'Teeth Cleaning (Oral Prophylaxis)';
          } elseif ($svc[0] === 'Dental Fillings/Dental Bonding') {
            $exactName = 'Dental Fillings (Composite)';
          } elseif ($svc[0] === 'Gum Treatment and Gingivectomy') {
            $exactName = 'Gum Treatment and Gingivectomy';
          } elseif ($svc[0] === 'Metal Braces/Ceramic') {
            $exactName = 'Orthodontic Braces';
          } elseif ($svc[0] === 'Clear Aligners/Retainers') {
            $exactName = 'Retainers';
          } elseif ($svc[0] === 'Dental Implants') {
            $exactName = 'Dental Implant';
          } elseif ($svc[0] === 'Fluoride Treatment') {
            $exactName = 'Fluoride Treatment';
          } elseif ($svc[0] === "Kids' Braces & Orthodontic Care") {
            $exactName = "Kid's Braces & Orthodontic Care";
          } elseif ($svc[0] === 'Wisdom Tooth Extraction (Odontectomy)') {
            $exactName = 'Wisdom Tooth Extraction';
          } elseif ($svc[0] === 'TMJ Treatment') {
            $exactName = 'TMJ Treatment';
          } elseif ($svc[0] === 'Panoramic X-ray / Full Mouth X-ray') {
            $exactName = 'Panoramic X-ray/Full Mouth X-Ray';
          } elseif ($svc[0] === 'Lateral Cephalometric X-ray') {
            $exactName = 'Lateral Cephalometric X-ray';
          } elseif ($svc[0] === 'Periapical X-ray / Single Tooth X-ray') {
            $exactName = 'Periapical X-ray';
          }
          ?>
          <div class="service-item" data-exact-service-name="<?= htmlspecialchars($exactName) ?>" data-bs-toggle="modal"
            data-bs-target="#serviceModal">
            <p><?= htmlspecialchars($svc[0]) ?></p>
            <img src="assets/photos/clinics/<?= $svc[1] ?>" alt="<?= htmlspecialchars($svc[0]) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>


  <!-- Bootstrap 5 Service Modal -->
  <div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">

      <div class="modal-content  p-2">
        <div class="justify-content-end d-flex w-100 pb-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-header justify-content-between d-flex m-0 p-0">
          <h3 class="modal-title" id="serviceModalLabel">Service Details</h3>

        </div>
        <div class="modal-body text-center">
          <img id="modalImage" src="" alt="Service Image" class="img-fluid rounded mb-4" style="max-height: 200px;">
          <div class="modal-description">
            <h3 id="modalTitle" class="fs-4"></h3>
            <p id="modalDescription" class="text-muted"></p>
            <button class="btn btn-primary btn-lg w-100" id="bookNowBtn">Book Now</button>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Login Notification Modal -->
  <div id="loginModal" class="modal">
    <div class="modal-content login-modal-content">
      <span class="close" onclick="closeLoginModal()">&times;</span>
      <div class="login-modal-body">
        <h2>Login Required</h2>
        <p>Please login to book an appointment for our dental services.</p>
        <div class="login-modal-buttons">
          <button class="modal-login-btn" onclick="window.location.href='login.php'">Login Now</button>
          <button class="modal-cancel-btn" onclick="closeLoginModal()">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <p>&copy; 2025 ISched of M&A Oida Dental Clinic. All Rights Reserved.</p>
  </footer>

  <script>
    // pass PHP login state
    const isLoggedIn = <?= $user ? 'true' : 'false' ?>;
    const serviceModal = document.getElementById('serviceModal');
    const loginModal = document.getElementById('loginModal');

    // Add event listener to the Book Now button
    document.addEventListener('DOMContentLoaded', function () {
      const bookNowBtn = document.getElementById('bookNowBtn');
      if (bookNowBtn) {
        bookNowBtn.addEventListener('click', bookSelectedService);
      }
    });

    // Function to handle booking the selected service
    function bookSelectedService() {
      // Check if user is logged in
      if (!isLoggedIn) {
        // Hide service modal
        serviceModal.style.display = 'none';
        // Show login modal
        loginModal.style.display = 'flex';
        return;
      }

      // Use the exact service name for matching
      const exactServiceName = serviceModal.getAttribute('data-exact-service-name');
      if (exactServiceName) {
        sessionStorage.setItem('selectedService', exactServiceName);
        window.location.href = 'bookings.php';
      }
    }


  </script>

  <!-- Notification functionality is now handled by notifications.js -->
</body>

</html>