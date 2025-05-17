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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
  <div class="profile-container">
    <div class="header">
      <a href="index.php" class="back-btn">Back</a>
      <h1 class="profile-title">My Profile</h1>

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
          <img
            src="<?= get_profile_image_url($user_id) ?>"
            alt="Profile Picture"
            class="profile-pic">
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
        <button class="edit-password-btn">Edit Password</button>
        <form action="logout.php" method="POST">
          <button type="submit" class="logout-btn">Logout</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
