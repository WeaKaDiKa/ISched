<?php
/**
 * Functions for handling user profile images
 */

/**
 * Get the profile image URL for a user
 * 
 * @param int $user_id The user ID
 * @param string $size Size of the image (small, medium, large)
 * @return string URL to the profile image
 */
function get_profile_image_url($user_id = null, $size = 'small') {
    // Default image path
    $default_image = '/M_A_Oida_Dental_Clinic/assets/photos/default_avatar.png';
    
    // If no user ID or user is not logged in, return default
    if (empty($user_id)) {
        return $default_image;
    }
    
    // Check if user has a custom profile image
    $image_path = '/M_A_Oida_Dental_Clinic/assets/images/profiles/user_' . $user_id . '.jpg';
    $server_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
    
    // If the file exists, return the custom image path
    if (file_exists($server_path)) {
        return $image_path;
    }
    
    // Otherwise return default
    return $default_image;
}

/**
 * Display a profile icon with optional dropdown menu
 * 
 * @param int $user_id The user ID
 * @param string $size Size of the icon (small, medium, large)
 * @param bool $with_dropdown Whether to include a dropdown menu
 * @return void Outputs the HTML directly
 */
function display_profile_icon($user_id = null, $size = 'small', $with_dropdown = false) {
    $image_url = get_profile_image_url($user_id, $size);
    $size_class = '';
    
    if ($size == 'medium') {
        $size_class = 'medium';
    } elseif ($size == 'large') {
        $size_class = 'large';
    }
    
    echo '<div class="profile-icon-container">';
    echo '<img src="' . $image_url . '" alt="Profile" class="profile-icon ' . $size_class . '" id="profileIcon">';
    
    if ($with_dropdown && isset($_SESSION['user_id'])) {
        echo '<div class="profile-dropdown" id="profileDropdown">
            <a href="profile.php" class="profile-dropdown-item"><i class="fas fa-user"></i> My Profile</a>
            <a href="appointments.php" class="profile-dropdown-item"><i class="fas fa-calendar-check"></i> My Appointments</a>
            <div class="profile-dropdown-divider"></div>
            <a href="logout.php" class="profile-dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>';
    }
    
    echo '</div>';
    
    if ($with_dropdown) {
        echo '<script>
            document.getElementById("profileIcon").addEventListener("click", function() {
                document.getElementById("profileDropdown").classList.toggle("show");
            });
            
            // Close dropdown when clicking outside
            window.addEventListener("click", function(event) {
                if (!event.target.matches("#profileIcon")) {
                    var dropdown = document.getElementById("profileDropdown");
                    if (dropdown.classList.contains("show")) {
                        dropdown.classList.remove("show");
                    }
                }
            });
        </script>';
    }
}

/**
 * Upload and save a profile image
 * 
 * @param int $user_id The user ID
 * @param array $file The $_FILES array element for the uploaded file
 * @return array Status and message
 */
function upload_profile_image($user_id, $file) {
    $result = [
        'success' => false,
        'message' => ''
    ];
    
    // Check if user ID is provided
    if (empty($user_id)) {
        $result['message'] = 'User ID is required';
        return $result;
    }
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        $result['message'] = 'Error uploading file';
        return $result;
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        $result['message'] = 'Only JPG, JPEG, and PNG files are allowed';
        return $result;
    }
    
    // Check file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        $result['message'] = 'File size must be less than 2MB';
        return $result;
    }
    
    // Create directory if it doesn't exist
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/M_A_Oida_Dental_Clinic/assets/images/profiles/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate filename
    $filename = 'user_' . $user_id . '.jpg';
    $target_file = $upload_dir . $filename;
    
    // Simple file move without image processing
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Update the database to use the new profile picture
        global $conn;
        if (isset($conn)) {
            $stmt = $conn->prepare("UPDATE patients SET profile_picture = ? WHERE id = ?");
            $profile_path = 'user_' . $user_id . '.jpg';
            $stmt->bind_param("si", $profile_path, $user_id);
            $stmt->execute();
        }
        
        $result['success'] = true;
        $result['message'] = 'Profile image uploaded successfully';
    } else {
        $result['message'] = 'Error saving image';
    }
    
    return $result;
}
?>
