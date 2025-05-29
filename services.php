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
  <link rel="stylesheet" href="assets/css/homepage.css">
  <?php require_once 'includes/head.php' ?>
  <script src="assets/js/services.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

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
          ['Kids’ Braces & Orthodontic Care', 'kidsbrace.png'],
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
          ?>
          <div class="service-item">
            <p><?= htmlspecialchars($svc[0]) ?></p>
            <img src="assets/photos/clinics/<?= $svc[1] ?>" alt="<?= htmlspecialchars($svc[0]) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <!-- Modal -->
  <div id="serviceModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <img id="modalImage" src="" alt="Service Image" class="modal-image">
      <div class="modal-description">
        <h2 id="modalTitle"></h2>
        <p id="modalDescription"></p>
        <button class="modal-book-btn">Book Now</button>
      </div>
    </div>
  </div>

  <footer>
    <p>&copy; 2025 ISched of M&A Oida Dental Clinic. All Rights Reserved.</p>
  </footer>

  <script>
    // pass PHP login state
    const isLoggedIn = <?= $user ? 'true' : 'false' ?>;
  </script>

  <!-- Notification functionality is now handled by notifications.js -->
</body>

</html>