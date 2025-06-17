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

      <?php if (isset($_SESSION['user_id'])): ?>
        <li><a href="myreviews.php" <?php if (basename($_SERVER['PHP_SELF']) == 'myreviews.php')
          echo 'class="active"'; ?>>My
            Reviews</a></li>
        <li><a href="mybookings.php" <?php if (basename($_SERVER['PHP_SELF']) == 'mybookings.php')
          echo 'class="active"'; ?>>My
            Booking</a></li>
      <?php endif; ?>

    </ul>

    <div class="nav-right">
      <a class="book-now text-decoration-none"
        href="<?= isset($user) && $user !== null ? 'bookings.php' : 'login.php'; ?>">
        Book Now
      </a>
      <?php if (isset($user) && $user !== null): ?>
        <?php include('user_notification_bell.php'); ?>
      <?php endif; ?>

      <!-- For larger screens (dropdown without arrow) -->
      <div class="d-none d-xl-block">
        <div class="dropdown">
          <button class="btn p-0 border-0 bg-transparent" type="button" id="profileDropdown" data-bs-toggle="dropdown"
            aria-expanded="false">
            <div class="user-icon">
              <?php if (isset($user) && $user !== null): ?>
                <img src="<?= get_profile_image_url($_SESSION['user_id'] ?? null) ?>?<?= time() ?>" alt="Profile Picture"
                  class="profile-pic">
              <?php else: ?>
                <img src="assets/photos/default_avatar.png" alt="Profile Icon" class="profile-pic">
              <?php endif; ?>
            </div>
          </button>

          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
            <?php if (isset($user) && $user !== null): ?>
              <li><a class="dropdown-item" href="profile.php">Profile</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="#"
                  onclick="customModal.show('Login Required', 'Please login to view your profile.', { showCancel: true, okText: 'Go to Login', redirectUrl: 'login.php' })">Login</a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- For medium screens (modal trigger) - unchanged -->
      <div class="d-xl-none">
        <a href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
          <div class="user-icon">
            <?php if (isset($user) && $user !== null): ?>
              <img src="<?= get_profile_image_url($_SESSION['user_id'] ?? null) ?>?<?= time() ?>" alt="Profile Picture"
                class="profile-pic">
            <?php else: ?>
              <img src="assets/photos/default_avatar.png" alt="Profile Icon" class="profile-pic">
            <?php endif; ?>
          </div>
        </a>
      </div>


      <!-- Modal for medium screens -->
      <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="profileModalLabel">Account</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="list-group">
                <?php if (isset($user) && $user !== null): ?>
                  <a href="profile.php" class="list-group-item list-group-item-action">Profile</a>
                  <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                <?php else: ?>
                  <a href="#" class="list-group-item list-group-item-action"
                    onclick="customModal.show('Login Required', 'Please login to view your profile.', { showCancel: true, okText: 'Go to Login', redirectUrl: 'login.php' }); $('#profileModal').modal('hide');">Login</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <script>
        // Close dropdown when switching to mobile view
        window.addEventListener('resize', function () {
          if (window.innerWidth < 1200) {
            var dropdown = document.querySelector('.dropdown');
            if (dropdown) {
              var bsDropdown = bootstrap.Dropdown.getInstance(dropdown);
              if (bsDropdown) {
                bsDropdown.hide();
              }
            }
          }
        });
      </script>
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