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
    $default_image = 'assets/images/profiles/default.png';
    
    // If no user ID or user is not logged in, return default
    if (empty($user_id)) {
        return $default_image;
    }
    
    // Check if user has a custom profile image
    $image_path = 'assets/images/profiles/user_' . $user_id . '.jpg';
    $server_path = $image_path;
    
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
            <a href="#" class="profile-dropdown-item" id="dropdownLogoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>';
    }
    
    echo '</div>';
    
    if ($with_dropdown) {
        echo '<script>
            document.getElementById("profileIcon").addEventListener("click", function() {
                document.getElementById("profileDropdown").classList.toggle("show");
            });
            
            // Add event listener for dropdown logout button
            if (document.getElementById("dropdownLogoutBtn")) {
                document.getElementById("dropdownLogoutBtn").addEventListener("click", function(e) {
                    e.preventDefault();
                    showLogoutConfirmation();
                });
            }
            
            // Create hidden logout form if it doesn\'t exist
            if (!document.getElementById("logoutForm")) {
                const logoutForm = document.createElement("form");
                logoutForm.id = "logoutForm";
                logoutForm.action = "logout.php";
                logoutForm.method = "POST";
                logoutForm.style.display = "none";
                document.body.appendChild(logoutForm);
            }
            
            // Logout confirmation modal function
            function showLogoutConfirmation() {
                // Create modal elements
                const logoutModal = document.createElement("div");
                logoutModal.style.position = "fixed";
                logoutModal.style.zIndex = "2000";
                logoutModal.style.left = "0";
                logoutModal.style.top = "0";
                logoutModal.style.width = "100%";
                logoutModal.style.height = "100%";
                logoutModal.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
                logoutModal.style.display = "flex";
                logoutModal.style.alignItems = "center";
                logoutModal.style.justifyContent = "center";
                
                const modalContent = document.createElement("div");
                modalContent.style.backgroundColor = "white";
                modalContent.style.color = "#333";
                modalContent.style.padding = "20px";
                modalContent.style.borderRadius = "8px";
                modalContent.style.width = "300px";
                modalContent.style.boxShadow = "0 4px 8px rgba(0, 0, 0, 0.2)";
                
                const title = document.createElement("h3");
                title.textContent = "Logout Account";
                title.style.marginTop = "0";
                title.style.marginBottom = "15px";
                title.style.fontSize = "18px";
                title.style.fontWeight = "bold";
                title.style.textAlign = "center";
                
                const messageText = document.createElement("p");
                messageText.textContent = "Are you sure you want to logout? Once you logout you need to login again. Are you OK?";
                messageText.style.marginBottom = "20px";
                messageText.style.color = "#666";
                messageText.style.fontSize = "14px";
                messageText.style.textAlign = "center";
                messageText.style.lineHeight = "1.5";
                
                const buttonContainer = document.createElement("div");
                buttonContainer.style.display = "flex";
                buttonContainer.style.justifyContent = "space-between";
                
                const cancelButton = document.createElement("button");
                cancelButton.textContent = "Cancel";
                cancelButton.style.flex = "1";
                cancelButton.style.padding = "10px";
                cancelButton.style.backgroundColor = "#E0F7E6";
                cancelButton.style.color = "#333";
                cancelButton.style.border = "none";
                cancelButton.style.borderRadius = "4px";
                cancelButton.style.marginRight = "5px";
                cancelButton.style.cursor = "pointer";
                cancelButton.style.fontWeight = "bold";
                
                const confirmButton = document.createElement("button");
                confirmButton.textContent = "Yes, Logout!";
                confirmButton.style.flex = "1";
                confirmButton.style.padding = "10px";
                confirmButton.style.backgroundColor = "#F44336";
                confirmButton.style.color = "white";
                confirmButton.style.border = "none";
                confirmButton.style.borderRadius = "4px";
                confirmButton.style.marginLeft = "5px";
                confirmButton.style.cursor = "pointer";
                confirmButton.style.fontWeight = "bold";
                
                cancelButton.onclick = function() {
                    document.body.removeChild(logoutModal);
                };
                
                confirmButton.onclick = function() {
                    // Submit the logout form
                    document.getElementById("logoutForm").submit();
                };
                
                buttonContainer.appendChild(cancelButton);
                buttonContainer.appendChild(confirmButton);
                
                modalContent.appendChild(title);
                modalContent.appendChild(messageText);
                modalContent.appendChild(buttonContainer);
                logoutModal.appendChild(modalContent);
                
                document.body.appendChild(logoutModal);
            }
            
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
