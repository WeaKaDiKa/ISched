<?php
require_once('session.php');
require 'db.php';
require_once('includes/profile_functions.php');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  header('Location: login.php');
  exit;
}

$sql = "
    SELECT 
        p.first_name, p.middle_name, p.last_name, p.email,
        p.phone_number, p.date_of_birth, p.gender, 
        p.region as region_id, p.province as province_id, p.city as city_id, p.barangay as barangay_id, p.zip_code,
        p.profile_picture,
        COALESCE(reg.region_description, 'Unknown Region') as region_name,
        COALESCE(prov.province_name, 'Unknown Province') as province_name,
        COALESCE(city.municipality_name, 'Unknown City') as city_name,
        COALESCE(brgy.barangay_name, 'Unknown Barangay') as barangay_name
    FROM patients p
    LEFT JOIN refregion reg ON p.region = reg.region_id
    LEFT JOIN refprovince prov ON p.province = prov.province_id
    LEFT JOIN refcity city ON p.city = city.municipality_id
    LEFT JOIN refbrgy brgy ON p.barangay = brgy.brgy_id
    WHERE p.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
  die("User not found.");
}

$formatted_dob = date('d-m-Y', strtotime($user['date_of_birth']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Profile</title>
  <link rel="stylesheet" href="assets/css/profiles.css">
  <?php require_once 'includes/head.php' ?>
  <style>
    /* Password Change Modal Styles */
    /*     .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background-color: white;
      border-radius: 8px;
      width: 350px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
      text-align: center;
      margin-bottom: 20px;
    }

    .modal-header h2 {
      color: #333;
      font-size: 20px;
      margin: 0;
    }
 */
    .error-message {
      color: #d9534f;
      font-size: 14px;
      text-align: center;
      margin-bottom: 15px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #555;
    }

    .form-group input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      background-color: #ffffff;
      box-shadow: none;
      outline: none;
    }

    .submit-btn {
      background-color: #7B68EE;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 10px 0;
      width: 100%;
      font-weight: bold;
      cursor: pointer;
      margin-top: 10px;
    }

    .submit-btn:hover {
      background-color: #6A5ACD;
    }

    .close {
      position: absolute;
      right: 15px;
      top: 10px;
      font-size: 20px;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <header>
    <?php include_once('includes/navbar.php'); ?>
  </header>
  <div class="container my-4">
    <div class="card">
      <div class="card-body p-5">
        <h1 class="profile-title mb-4">My Profile</h1>

        <div class="profile-content">

          <div class="profile-pic-section d-flex flex-column flex-md-row align-items-center mb-4">
            <div class="profile-pic-container me-md-4 mb-3 mb-md-0">
              <img src="<?= get_profile_image_url($user_id) ?>" alt="Profile Picture" class="profile-pic rounded-circle"
                style="width: 150px; height: 150px; object-fit: cover;">
            </div>
            <div class="profile-name-header text-center text-md-start">
              <h2 class="mb-2">
                <?= htmlspecialchars(trim("{$user['first_name']} {$user['middle_name']} {$user['last_name']}")) ?>
              </h2>
              <a href="edit_profile.php" class="edit-profile-btn btn btn-outline-primary">Edit Profile</a>
            </div>
          </div>

          <div class="profile-info row">
            <div class="info-column col-md-6">
              <div class="form-group mb-3">
                <label class="form-label">First Name:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Middle Name:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['middle_name'] ?: 'N/A') ?>"
                  readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Last Name:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Email Address:</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Phone Number:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone_number']) ?>" readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Date of Birth:</label>
                <input type="text" class="form-control" value="<?= $formatted_dob ?>" readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Gender:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['gender']) ?>" readonly>
              </div>
            </div>

            <div class="info-column col-md-6">
              <div class="form-group mb-3">
                <label class="form-label">Region:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['region_name'] ?? '') ?>"
                  readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Province:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['province_name'] ?? '') ?>"
                  readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">City/Municipality:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['city_name'] ?? '') ?>"
                  readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Barangay:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['barangay_name'] ?? '') ?>"
                  readonly>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Zip Code:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($user['zip_code']) ?>" readonly>
              </div>
            </div>
          </div>

          <div class="action-buttons d-flex flex-wrap gap-2 mt-4">
            <button class="edit-password-btn btn btn-warning" id="editPasswordBtn">Edit Password</button>
            <!--              <button class="logout-btn btn btn-danger" id="logoutBtn">Logout</button> -->
            <form action="logout.php" method="POST" id="logoutForm" class="d-none">
              <!-- Hidden form that will be submitted when confirmed -->
            </form>
          </div>

        </div>
      </div>
    </div>

  </div>
  <!-- Bootstrap 5 Modal -->
  <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="passwordModalLabel">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <?php if (isset($_GET['error']) && $_GET['error'] == 'incorrect'): ?>
            <div class="alert alert-danger">Current Password is not correct</div>
          <?php endif; ?>

          <form id="passwordForm" action="update_password.php" method="POST">
            <div class="mb-3">
              <label for="current_password" class="form-label">Current Password</label>
              <input type="password" class="form-control bg-warning bg-opacity-10" id="current_password"
                name="current_password" required>
            </div>

            <div class="mb-3">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" class="form-control bg-warning bg-opacity-10" id="new_password" name="new_password"
                required>
            </div>

            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <input type="password" class="form-control bg-warning bg-opacity-10" id="confirm_password"
                name="confirm_password" required>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Initialize modal with Bootstrap's JavaScript
    const passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));

    // Trigger modal when edit button is clicked
    document.getElementById('editPasswordBtn').addEventListener('click', function () {
      passwordModal.show();
    });

    // Check for URL parameters on page load
    window.addEventListener('DOMContentLoaded', function () {
      <?php if (isset($_GET['error']) && isset($_GET['message'])): ?>
        showErrorModal('<?php echo htmlspecialchars($_GET['message']); ?>');
      <?php endif; ?>

      <?php if (isset($_GET['success']) && $_GET['success'] == 'password_updated'): ?>
        showSuccessModal('Password updated successfully!');
        // Optionally show the password modal if you want
        // passwordModal.show();
      <?php endif; ?>

      // Auto-show modal if there's a password error
      <?php if (isset($_GET['error']) && $_GET['error'] == 'incorrect'): ?>
        passwordModal.show();
      <?php endif; ?>
    });

    // Form validation
    document.getElementById('passwordForm').addEventListener('submit', function (e) {
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      if (newPassword !== confirmPassword) {
        e.preventDefault();
        showErrorModal('New password and confirm password do not match!');
      }
    });

    // Check for URL parameters on page load
    window.onload = function () {
      <?php if (isset($_GET['error']) && isset($_GET['message'])): ?>
        showErrorModal('<?php echo htmlspecialchars($_GET['message']); ?>');
      <?php endif; ?>
    }
    // Error Modal Function
    function showErrorModal(message) {
      // Create modal elements
      const errorModal = document.createElement('div');
      errorModal.style.position = 'fixed';
      errorModal.style.zIndex = '2000';
      errorModal.style.left = '0';
      errorModal.style.top = '0';
      errorModal.style.width = '100%';
      errorModal.style.height = '100%';
      errorModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
      errorModal.style.display = 'flex';
      errorModal.style.alignItems = 'center';
      errorModal.style.justifyContent = 'center';

      const modalContent = document.createElement('div');
      modalContent.style.backgroundColor = 'white';
      modalContent.style.color = '#333';
      modalContent.style.padding = '30px 20px';
      modalContent.style.borderRadius = '8px';
      modalContent.style.width = '300px';
      modalContent.style.textAlign = 'center';

      // Create warning icon (triangle with exclamation mark)
      const warningIcon = document.createElement('div');
      warningIcon.innerHTML = `<svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#FF6B6B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M12 8V12" stroke="#FF6B6B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M12 16H12.01" stroke="#FF6B6B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>`;
      warningIcon.style.marginBottom = '15px';

      const title = document.createElement('h3');
      title.textContent = 'Something went wrong';
      title.style.marginTop = '0';
      title.style.marginBottom = '10px';
      title.style.fontSize = '18px';
      title.style.color = '#333';

      const messageText = document.createElement('p');
      messageText.textContent = message;
      messageText.style.marginBottom = '20px';
      messageText.style.color = '#666';
      messageText.style.fontSize = '14px';

      const okButton = document.createElement('button');
      okButton.textContent = 'Go Back';
      okButton.style.padding = '8px 0';
      okButton.style.backgroundColor = '#FFC107';
      okButton.style.color = '#333';
      okButton.style.border = 'none';
      okButton.style.borderRadius = '4px';
      okButton.style.cursor = 'pointer';
      okButton.style.width = '100%';
      okButton.style.fontWeight = 'bold';

      okButton.onclick = function () {
        document.body.removeChild(errorModal);
      };

      modalContent.appendChild(warningIcon);
      modalContent.appendChild(title);
      modalContent.appendChild(messageText);
      modalContent.appendChild(okButton);
      errorModal.appendChild(modalContent);

      document.body.appendChild(errorModal);
    }

    // Success Modal Function
    function showSuccessModal(message) {
      // Create modal elements
      const successModal = document.createElement('div');
      successModal.style.position = 'fixed';
      successModal.style.zIndex = '2000';
      successModal.style.left = '0';
      successModal.style.top = '0';
      successModal.style.width = '100%';
      successModal.style.height = '100%';
      successModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
      successModal.style.display = 'flex';
      successModal.style.alignItems = 'center';
      successModal.style.justifyContent = 'center';

      const modalContent = document.createElement('div');
      modalContent.style.backgroundColor = 'white';
      modalContent.style.color = '#333';
      modalContent.style.padding = '30px 20px';
      modalContent.style.borderRadius = '8px';
      modalContent.style.width = '300px';
      modalContent.style.textAlign = 'center';

      // Create success icon (green circle with checkmark)
      const successIcon = document.createElement('div');
      successIcon.innerHTML = `<div style="width: 60px; height: 60px; border-radius: 50%; background-color: #00C851; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M5 12L10 17L20 7" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>`;

      const title = document.createElement('h3');
      title.textContent = 'Success';
      title.style.marginTop = '0';
      title.style.marginBottom = '10px';
      title.style.fontSize = '24px';
      title.style.fontWeight = 'bold';
      title.style.color = '#333';

      const messageText = document.createElement('p');
      messageText.innerHTML = 'Your password has been successfully updated.<br>You can now use your new password to login.';
      messageText.style.marginBottom = '20px';
      messageText.style.color = '#666';
      messageText.style.fontSize = '14px';
      messageText.style.lineHeight = '1.5';

      const okButton = document.createElement('button');
      okButton.textContent = 'OK';
      okButton.style.padding = '8px 0';
      okButton.style.backgroundColor = '#00C851';
      okButton.style.color = 'white';
      okButton.style.border = 'none';
      okButton.style.borderRadius = '4px';
      okButton.style.cursor = 'pointer';
      okButton.style.width = '100%';
      okButton.style.fontWeight = 'bold';

      okButton.onclick = function () {
        document.body.removeChild(successModal);
      };

      modalContent.appendChild(successIcon);
      modalContent.appendChild(title);
      modalContent.appendChild(messageText);
      modalContent.appendChild(okButton);
      successModal.appendChild(modalContent);

      document.body.appendChild(successModal);
    }

    // Logout confirmation popup
    logoutBtn.addEventListener('click', function () {
      showLogoutConfirmation();
    });

    function showLogoutConfirmation() {
      // Create modal elements
      const logoutModal = document.createElement('div');
      logoutModal.style.position = 'fixed';
      logoutModal.style.zIndex = '2000';
      logoutModal.style.left = '0';
      logoutModal.style.top = '0';
      logoutModal.style.width = '100%';
      logoutModal.style.height = '100%';
      logoutModal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
      logoutModal.style.display = 'flex';
      logoutModal.style.alignItems = 'center';
      logoutModal.style.justifyContent = 'center';

      const modalContent = document.createElement('div');
      modalContent.style.backgroundColor = 'white';
      modalContent.style.color = '#333';
      modalContent.style.padding = '20px';
      modalContent.style.borderRadius = '8px';
      modalContent.style.width = '300px';
      modalContent.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';

      const title = document.createElement('h3');
      title.textContent = 'Logout Account';
      title.style.marginTop = '0';
      title.style.marginBottom = '15px';
      title.style.fontSize = '18px';
      title.style.fontWeight = 'bold';
      title.style.textAlign = 'center';

      const messageText = document.createElement('p');
      messageText.textContent = 'Are you sure you want to logout? Once you logout you need to login again. Are you OK?';
      messageText.style.marginBottom = '20px';
      messageText.style.color = '#666';
      messageText.style.fontSize = '14px';
      messageText.style.textAlign = 'center';
      messageText.style.lineHeight = '1.5';

      const buttonContainer = document.createElement('div');
      buttonContainer.style.display = 'flex';
      buttonContainer.style.justifyContent = 'space-between';

      const cancelButton = document.createElement('button');
      cancelButton.textContent = 'Cancel';
      cancelButton.style.flex = '1';
      cancelButton.style.padding = '10px';
      cancelButton.style.backgroundColor = '#E0F7E6';
      cancelButton.style.color = '#333';
      cancelButton.style.border = 'none';
      cancelButton.style.borderRadius = '4px';
      cancelButton.style.marginRight = '5px';
      cancelButton.style.cursor = 'pointer';
      cancelButton.style.fontWeight = 'bold';

      const confirmButton = document.createElement('button');
      confirmButton.textContent = 'Yes, Logout!';
      confirmButton.style.flex = '1';
      confirmButton.style.padding = '10px';
      confirmButton.style.backgroundColor = '#F44336';
      confirmButton.style.color = 'white';
      confirmButton.style.border = 'none';
      confirmButton.style.borderRadius = '4px';
      confirmButton.style.marginLeft = '5px';
      confirmButton.style.cursor = 'pointer';
      confirmButton.style.fontWeight = 'bold';

      cancelButton.onclick = function () {
        document.body.removeChild(logoutModal);
      };

      confirmButton.onclick = function () {
        // Submit the logout form
        logoutForm.submit();
      };

      buttonContainer.appendChild(cancelButton);
      buttonContainer.appendChild(confirmButton);

      modalContent.appendChild(title);
      modalContent.appendChild(messageText);
      modalContent.appendChild(buttonContainer);
      logoutModal.appendChild(modalContent);

      document.body.appendChild(logoutModal);
    }
  </script>
</body>

</html>