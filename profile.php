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
        first_name, middle_name, last_name, email,
        phone_number, date_of_birth, gender, 
        region, province, city, barangay, zip_code,
        profile_picture
    FROM patients  
    WHERE id = ?
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
  <link rel="stylesheet" href="assets/css/profile-icon.css">
  <link rel="stylesheet" href="assets/css/notification.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    /* Password Change Modal Styles */
    .modal {
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
  <div class="profile-container">
    <div class="header">
      <a href="index.php" class="back-btn">Back</a>
      <h1 class="profile-title">My Profile</h1>

      <!-- Notification bell removed as requested -->

      <div class="bookings-btn">
        <!-- LINKED to mybookings.php now -->
        <a href="mybookings.php">
          <button>MY BOOKINGS</button>
        </a>
        <p class="booking-note">Check your bookings here!</p>
      </div>
    </div>

    <div class="profile-content">
      <div class="profile-pic-section">
        <div class="profile-pic-container">
          <img src="<?= get_profile_image_url($user_id) ?>" alt="Profile Picture" class="profile-pic">
        </div>
        <div class="profile-name-header">
          <h2>
            <?= htmlspecialchars(trim("{$user['first_name']} {$user['middle_name']} {$user['last_name']}")) ?>
          </h2>
          <a href="edit_profile.php" class="edit-profile-btn">Edit Profile</a>
        </div>
      </div>

      <div class="profile-info">
        <div class="info-column">
          <div class="form-group">
            <label>First Name:</label>
            <input type="text" value="<?= htmlspecialchars($user['first_name']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Middle Name:</label>
            <input type="text" value="<?= htmlspecialchars($user['middle_name'] ?: 'N/A') ?>" readonly>
          </div>
          <div class="form-group">
            <label>Last Name:</label>
            <input type="text" value="<?= htmlspecialchars($user['last_name']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Email Address:</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" value="<?= htmlspecialchars($user['phone_number']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Date of Birth:</label>
            <input type="text" value="<?= $formatted_dob ?>" readonly>
          </div>
          <div class="form-group">
            <label>Gender:</label>
            <input type="text" value="<?= htmlspecialchars($user['gender']) ?>" readonly>
          </div>
        </div>

        <div class="info-column">
          <div class="form-group">
            <label>Region:</label>
            <input type="text" value="<?= htmlspecialchars($user['region']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Province:</label>
            <input type="text" value="<?= htmlspecialchars($user['province']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>City/Municipality:</label>
            <input type="text" value="<?= htmlspecialchars($user['city']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Barangay:</label>
            <input type="text" value="<?= htmlspecialchars($user['barangay']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Zip Code:</label>
            <input type="text" value="<?= htmlspecialchars($user['zip_code']) ?>" readonly>
          </div>
        </div>
      </div>

      <div class="action-buttons">
        <a href="myreviews.php" class="notifications-btn" style="text-decoration: none;">
          <button
            style="background-color: #4a89dc; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
            My Reviews
          </button>
        </a>
        <button class="edit-password-btn" id="editPasswordBtn">Edit Password</button>
        <form action="logout.php" method="POST">
          <button type="submit" class="logout-btn">Logout</button>
        </form>
      </div>
    </div>
  </div>
  <!-- Password Change Modal -->
  <div id="passwordModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Change Password</h2>
      </div>
      
      <?php if(isset($_GET['error']) && $_GET['error'] == 'incorrect'): ?>
      <div class="error-message">Current Password is not correct</div>
      <?php endif; ?>
      
      <form id="passwordForm" action="update_password.php" method="POST">
        <div class="form-group">
          <label for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" required style="background-color: #FFF9E6; border: 1px solid #ccc;">
        </div>
        
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" required style="background-color: #FFF9E6; border: 1px solid #ccc;">
        </div>
        
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required style="background-color: #FFF9E6; border: 1px solid #ccc;">
        </div>
        
        <button type="submit" class="submit-btn">Submit</button>
      </form>
    </div>
  </div>

  <script>
    // Get the modal
    const modal = document.getElementById('passwordModal');
    const editPasswordBtn = document.getElementById('editPasswordBtn');
    
    // When the user clicks the button, open the modal
    editPasswordBtn.onclick = function() {
      modal.style.display = 'flex';
    }
    
    // When the user clicks anywhere outside of the modal content, close it
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    }
    
    // Check for URL parameters on page load
    window.onload = function() {
      <?php if(isset($_GET['error']) && isset($_GET['message'])): ?>
        showErrorModal('<?php echo htmlspecialchars($_GET['message']); ?>');
      <?php endif; ?>
      
      <?php if(isset($_GET['success']) && $_GET['success'] == 'password_updated'): ?>
        showSuccessModal('Password updated successfully!');
      <?php endif; ?>
    }
    
    // Form validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      
      if (newPassword !== confirmPassword) {
        e.preventDefault();
        showErrorModal('New password and confirm password do not match!');
      }
    });
    
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
      
      okButton.onclick = function() {
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
      
      okButton.onclick = function() {
        document.body.removeChild(successModal);
      };
      
      modalContent.appendChild(successIcon);
      modalContent.appendChild(title);
      modalContent.appendChild(messageText);
      modalContent.appendChild(okButton);
      successModal.appendChild(modalContent);
      
      document.body.appendChild(successModal);
    }
  </script>
</body>

</html>