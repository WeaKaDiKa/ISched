<?php
// This file contains the common navbar for all pages
// It should be included in all pages that need the navbar

// Check if user is logged in
if (!isset($user) && isset($_SESSION['user_id'])) {
  // If $user is not set but user is logged in, fetch user data
  $userStmt = $conn->prepare("SELECT first_name, profile_picture FROM patients WHERE id = ?");
  $userStmt->bind_param("i", $_SESSION['user_id']);
  $userStmt->execute();
  $user = $userStmt->get_result()->fetch_assoc() ?: null;
}
?>

<?php
// Include the custom modal component
include_once('includes/custom_modal.php');
?>

<nav class="navbar">
  <div class="d-flex align-items-center">
    <a href="index.php" class="logo-link">
      <img src="assets/photos/logo.jpg" alt="Logo" class="logo">
    </a>

    <?php if (isset($user) && $user !== null): ?>
      <div class="welcome-message d-none d-xxl-flex mx-3">
        Welcome, <strong><?= htmlspecialchars($user['first_name']) ?>!</strong>
      </div>
    <?php endif; ?>
  </div>

  <!-- hamburger toggle -->
  <input type="checkbox" id="nav-toggle" class="nav-toggle">
  <label for="nav-toggle" class="nav-toggle-label">
    <span></span>
  </label>


  <div class="nav-menu">
    <ul class="nav-links mb-0">
      <li><a href="index.php" <?php if (basename($_SERVER['PHP_SELF']) == 'index.php')
        echo 'class="active"'; ?>>Home</a>
      </li>
      <li><a href="services.php" <?php if (basename($_SERVER['PHP_SELF']) == 'services.php')
        echo 'class="active"'; ?>>Services</a></li>
      <li><a href="about.php" <?php if (basename($_SERVER['PHP_SELF']) == 'about.php')
        echo 'class="active"'; ?>>About</a>
      </li>
      <li><a href="reviews.php" <?php if (basename($_SERVER['PHP_SELF']) == 'reviews.php')
        echo 'class="active"'; ?>>Reviews</a></li>
      <li><a href="contact.php" <?php if (basename($_SERVER['PHP_SELF']) == 'contact.php')
        echo 'class="active"'; ?>>Contact Us</a></li>
    </ul>

    <div class="nav-right">
      <a class="book-now text-decoration-none"
        href="<?= isset($user) && $user !== null ? 'bookings.php' : 'login.php'; ?>" <?= !isset($user) || $user === null ? "onclick=\"alert('Please login to book an appointment.');\"" : '' ?>>
        Book Now
      </a>
      <?php if (isset($user) && $user !== null): ?>
        <?php include('user_notification_bell.php'); ?>
      <?php endif; ?>
      <a href="<?= isset($user) && $user !== null ? 'profile.php' : 'login.php'; ?>" <?= !isset($user) || $user === null ? "onclick=\"alert('Please login to view profile.');\"" : '' ?>>
        <div class="user-icon">
          <?php if (isset($user) && $user !== null): ?>
            <img src="<?= get_profile_image_url($_SESSION['user_id'] ?? null) ?>?<?= time() ?>" alt="Profile Picture"
              class="profile-pic">
          <?php else: ?>
            <img src="assets/photos/default_avatar.png" alt="Profile Icon" class="profile-pic">
          <?php endif; ?>
        </div>
      </a>
    </div> <!-- .nav-right -->
  </div> <!-- .nav-menu -->
</nav>

<script>
  document.querySelectorAll('.nav-links a, .nav-right a').forEach(link => {
    link.addEventListener('click', () => {
      document.getElementById('nav-toggle').checked = false;
    });
  });
</script>
<?php include_once('includes/custom_modal.php'); ?>
