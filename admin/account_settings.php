<?php
require_once('db.php');
require_once('session_handler.php');

$admin_id = $_SESSION['admin_id'];

// Get pending appointments count for badge
$pendingAppointments = 0;
$sqlPending = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
$resultPending = $conn->query($sqlPending);
if ($resultPending && $rowPending = $resultPending->fetch_assoc()) {
    $pendingAppointments = $rowPending['total'];
}

// Get unseen feedback count
$unseenFeedback = 0;
$sqlFeedback = "SELECT COUNT(*) as total FROM reviews WHERE is_seen = 0";
$resultFeedback = $conn->query($sqlFeedback);
if ($resultFeedback && $rowFeedback = $resultFeedback->fetch_assoc()) {
    $unseenFeedback = $rowFeedback['total'];
}

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'assets/photo/';
    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $newFileName = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
    $uploadFile = $uploadDir . $newFileName;
    
    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadFile)) {
        // Get the old profile photo path
        $oldPhoto = null;
        $stmt = $conn->prepare("SELECT profile_photo FROM admin_logins WHERE admin_id=?");
        $stmt->bind_param('s', $admin_id);
        $stmt->execute();
        $stmt->bind_result($oldPhoto);
        $stmt->fetch();
        $stmt->close();
        
        // Update DB with new photo
        $stmt = $conn->prepare("UPDATE admin_logins SET profile_photo=? WHERE admin_id=?");
        $stmt->bind_param('ss', $uploadFile, $admin_id);
        if ($stmt->execute()) {
            // Update session
            $_SESSION['profile_photo'] = $uploadFile;
            
            // Delete old photo if it exists and is not the default
            if ($oldPhoto && $oldPhoto !== 'assets/photo/default_avatar.png' && file_exists($oldPhoto)) {
                unlink($oldPhoto);
            }
            
            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'photo_url' => $uploadFile
            ]);
            exit;
        }
        $stmt->close();
    }
}

// Handle Personal Details update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $age = $_POST['age'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $email = $_POST['email'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    $stmt = $conn->prepare("UPDATE admin_logins SET first_name=?, last_name=?, name=?, age=?, mobile=?, email=?, gender=? WHERE admin_id=?");
    $stmt->bind_param('ssssssss', $first_name, $last_name, $full_name, $age, $mobile, $email, $gender, $admin_id);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $full_name;
        $success = "Account settings updated successfully!";
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $success, 'name' => $full_name]);
            exit;
        }
    } else {
        $error = "Failed to update account settings. " . $stmt->error;
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    }
    $stmt->close();
}

// Handle password update
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_password'], $_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])
) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password from database
    $sql = "SELECT password FROM admin_logins WHERE admin_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row) {
        // Verify current password
        if (password_verify($current_password, $row['password'])) {
            if ($new_password === $confirm_password && strlen($new_password) >= 8 && strlen($new_password) <= 20) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin_logins SET password = ? WHERE admin_id = ?");
                $stmt->bind_param('ss', $new_hash, $admin_id);
                if ($stmt->execute()) {
                    $success = "Password updated successfully!";
                } else {
                    $error = "Failed to update password.";
                }
            } else {
                $error = "New passwords do not match or do not meet length requirements (8-20 characters).";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } else {
        $error = "User not found.";
    }
}

// Load admin data including profile photo
if ($admin_id) {
    $sql = "SELECT * FROM admin_logins WHERE admin_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin = $row;
        $_SESSION['profile_photo'] = $row['profile_photo']; // Store profile photo in session
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <?php require_once 'head.php' ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <style>
      body, html, .text-sm, .text-base, .text-lg, .text-xl, .text-2xl, .text-3xl, .text-gray-700, .text-gray-900, .text-blue-900 {
        font-family: 'Inter', sans-serif !important;
      }
      .icon-circle {
        @apply w-12 h-12 flex items-center justify-center rounded-full bg-blue-50 text-blue-600 text-xl transition-all duration-200 ease-in-out shadow-sm;
      }
      .sidebar-icon {
        @apply transition-all duration-200 ease-in-out;
      }
      .sidebar-icon.active .icon-circle,
      .sidebar-icon:focus .icon-circle,
      .sidebar-icon:hover .icon-circle {
        @apply bg-blue-100 text-blue-700 shadow-lg scale-110;
      }
      #sidebar {
        transition: width 0.3s, min-width 0.3s, padding 0.3s;
      }
      #sidebar.collapsed {
        width: 4.5rem !important;
        min-width: 0 !important;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
      }
      #sidebar.collapsed .sidebar-label,
      #sidebar.collapsed .text-center,
      #sidebar.collapsed .text-xs,
      #sidebar.collapsed nav span,
      #sidebar.collapsed .mt-auto,
      #sidebar.collapsed .flex.flex-col.items-center.mb-8 > h3,
      #sidebar.collapsed .flex.flex-col.items-center.mb-8 > p {
          display: none !important;
      }
      #sidebar.collapsed .flex.flex-col.items-center.mb-8 {
          align-items: flex-start !important;
      }
      #sidebar.collapsed img.w-24 {
          margin-bottom: 0 !important;
      }
      .scrollbar-hide {
          -ms-overflow-style: none;
          scrollbar-width: none;
      }
      .scrollbar-hide::-webkit-scrollbar {
          display: none;
      }
    </style>
    <script>
    // Define showTab function in the head so it's available before DOM elements are created
    function showTab(tab) {
        // Get all tabs
        const allTabs = document.querySelectorAll('[id$="-tab"]');
        
        // Hide all tabs immediately
        allTabs.forEach(element => {
            if (element.id !== tab) {
                element.classList.add('hidden');
            }
        });
        
        // Show the selected tab
        document.getElementById(tab).classList.remove('hidden');
        
        // Update tab button styles
        const personalTab = document.getElementById('tab-personal');
        const resetTab = document.getElementById('tab-reset');
        
        // Reset all tab styles
        personalTab.classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
        resetTab.classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
        personalTab.classList.add('text-gray-500');
        resetTab.classList.add('text-gray-500');
        
        // Set active tab style
        if (tab === 'personal-details-tab') {
            personalTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
            personalTab.classList.remove('text-gray-500');
        } else if (tab === 'reset-password-tab') {
            resetTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
            resetTab.classList.remove('text-gray-500');
        }
        
        // For mobile: scroll the active tab button into view
        if (window.innerWidth < 768) {
            if (tab === 'personal-details-tab') {
                personalTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else if (tab === 'reset-password-tab') {
                resetTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }
        
        // Save active tab to sessionStorage for persistence
        sessionStorage.setItem('activeAccountTab', tab);
    }
    </script>
</head>
<body class="bg-white text-gray-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-x-hidden">
            <!-- Top bar -->
            <?php require_once 'header.php' ?>
            <!-- Breadcrumb Navigation -->

            <!-- Breadcrumb Navigation -->
            <?php 
                $breadcrumbLabel = 'Account Settings';
                include 'breadcrumb.php'; 
            ?>
            <!-- End Breadcrumb Navigation -->

            <!-- Content area -->
            <div class="flex-1 p-4 overflow-y-auto bg-gray-50">
                <div class="max-w-5xl mx-auto bg-white rounded-xl shadow-md p-4 md:p-6">
                <div class="border-b border-gray-200 mb-4 md:mb-6">
                    <div class="flex space-x-4 md:space-x-8 overflow-x-auto scrollbar-hide" aria-label="Tabs">
                        <button type="button" id="tab-personal" onclick="showTab('personal-details-tab')" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 font-medium whitespace-nowrap focus:outline-none transition duration-200">Personal Details</button>
                        <button type="button" id="tab-reset" onclick="showTab('reset-password-tab')" class="text-gray-500 hover:text-gray-700 px-3 py-2 font-medium whitespace-nowrap focus:outline-none transition duration-200">Reset Password</button>
                    </div>
                </div>
                <div id="personal-details-tab">
                    <form id="profileForm" method="post" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="first_name" id="first_name" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($admin['first_name'] ?? ''); ?>" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="last_name" id="last_name" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($admin['last_name'] ?? ''); ?>" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <div class="relative">
                                <input type="text" name="full_name" id="full_name" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ($admin['name'] ?? '')); ?>" placeholder="Full Name">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Age <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="age" id="age" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($_POST['age'] ?? ($admin['age'] ?? '')); ?>" placeholder="Select Age" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-birthday-cake text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="mobile" id="mobile" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ($admin['mobile'] ?? '')); ?>" placeholder="Enter Mobile Number" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Create ID <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="create_id" id="create_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50" value="<?php echo htmlspecialchars($admin['admin_id'] ?? ''); ?>" placeholder="Create Unique ID" required readonly>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-id-badge text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email ID <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="email" name="email" id="email" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($_POST['email'] ?? ($admin['email'] ?? '')); ?>" placeholder="Enter Email ID" required>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <?php $gender = $_POST['gender'] ?? ($admin['gender'] ?? ''); ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                            <div class="flex items-center space-x-4 mt-1">
                                <label class="inline-flex items-center cursor-pointer hover:text-blue-600 transition-colors">
                                    <input type="radio" name="gender" value="Male" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" required <?php if ($gender === 'Male') echo 'checked'; ?>>
                                    <span class="ml-2 text-sm">Male</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer hover:text-blue-600 transition-colors">
                                    <input type="radio" name="gender" value="Female" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" required <?php if ($gender === 'Female') echo 'checked'; ?>>
                                    <span class="ml-2 text-sm">Female</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end mt-4 md:mt-6 space-x-2 md:col-span-4">
                            <button type="button" onclick="window.location.href='dashboard.php'" class="px-3 py-2 md:px-4 rounded text-sm md:text-base bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition-colors duration-200">Cancel</button>
                            <button type="submit" name="update_profile" class="px-3 py-2 md:px-4 rounded text-sm md:text-base bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors duration-200">Update</button>
                        </div>
                    </form>
                </div>
                <div id="reset-password-tab" class="hidden">
                    <div class="flex justify-center">
                        <form method="post" class="w-full max-w-md">
                            <?php if (!empty($success)) { echo '<div class="text-green-600 mb-4 text-sm">'.$success.'</div>'; } ?>
                            <?php if (!empty($error)) { echo '<div class="text-red-600 mb-4 text-sm">'.$error.'</div>'; } ?>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="password" name="current_password" id="current_password" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter Current password" required>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" onclick="togglePassword('current_password', this)">
                                        <i class="fas fa-eye text-gray-400"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">New password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="password" name="new_password" id="new_password" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Your password must be 8-20 characters long." required>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" onclick="togglePassword('new_password', this)">
                                        <i class="fas fa-eye text-gray-400"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm new password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" id="confirm_password" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Confirm new password" required>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" onclick="togglePassword('confirm_password', this)">
                                        <i class="fas fa-eye text-gray-400"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="button" onclick="showTab('personal-details-tab')" class="px-3 py-2 md:px-4 rounded text-sm md:text-base bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition-colors duration-200">
                                    Cancel
                                </button>
                                <button type="submit" name="update_password" class="px-3 py-2 md:px-4 rounded text-sm md:text-base bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors duration-200">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
let cropper = null;

function showCropModal(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const cropModal = document.getElementById('cropModal');
            const cropImage = document.getElementById('cropImage');
            
            // Set image source
            cropImage.src = e.target.result;
            
            // Show modal
            cropModal.classList.remove('hidden');
            
            // Initialize cropper
            if (cropper) {
                cropper.destroy();
            }
            cropper = new Cropper(cropImage, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: false,
                cropBoxResizable: false,
                toggleDragModeOnDblclick: false,
            });
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateAllProfilePhotos(imageUrl) {
    // Update all profile photos in the current page
    const profilePhotos = document.querySelectorAll('img[alt*="Profile photo"]');
    profilePhotos.forEach(img => {
        img.src = imageUrl;
    });
}

function saveCroppedImage() {
    if (!cropper) return;
    
    // Get cropped canvas
    const canvas = cropper.getCroppedCanvas({
        width: 300,
        height: 300
    });
    
    // Convert to blob
    canvas.toBlob(function(blob) {
        // Create form data
        const formData = new FormData();
        formData.append('profile_photo', blob, 'profile.jpg');
        
        // Show loading state
        document.body.style.cursor = 'wait';
        
        // Upload using fetch
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create object URL for the blob
                const imageUrl = URL.createObjectURL(blob);
                
                // Update all profile photos
                updateAllProfilePhotos(imageUrl);
                
                // Show success message
                showSuccessModal('Profile photo updated successfully');
                
                // Store the URL in sessionStorage for other pages
                sessionStorage.setItem('newProfilePhoto', data.photo_url);
            } else {
                alert('Failed to update profile photo');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating your profile photo');
        })
        .finally(() => {
            closeCropModal();
            document.body.style.cursor = 'default';
        });
    }, 'image/jpeg', 0.95);
}

function closeCropModal() {
    const cropModal = document.getElementById('cropModal');
    
    // Add fade-out animation
    cropModal.classList.add('opacity-0');
    cropModal.style.transition = 'opacity 0.3s ease';
    
    // Hide modal after animation
    setTimeout(() => {
        cropModal.classList.add('hidden');
        cropModal.classList.remove('opacity-0');
        
        // Clean up cropper instance
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        
        // Reset file input
        document.getElementById('profile_photo').value = '';
    }, 300);
}

function showTab(tab) {
    // Get all tabs
    const allTabs = document.querySelectorAll('[id$="-tab"]');
    
    // Hide all tabs immediately
    allTabs.forEach(element => {
        if (element.id !== tab) {
            element.classList.add('hidden');
        }
    });
    
    // Show the selected tab
    document.getElementById(tab).classList.remove('hidden');
    
    // Update tab button styles
    const personalTab = document.getElementById('tab-personal');
    const resetTab = document.getElementById('tab-reset');
    
    // Reset all tab styles
    personalTab.classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
    resetTab.classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
    personalTab.classList.add('text-gray-500');
    resetTab.classList.add('text-gray-500');
    
    // Set active tab style
    if (tab === 'personal-details-tab') {
        personalTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
        personalTab.classList.remove('text-gray-500');
    } else if (tab === 'reset-password-tab') {
        resetTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
        resetTab.classList.remove('text-gray-500');
    }
    
    // For mobile: scroll the active tab button into view
    if (window.innerWidth < 768) {
        if (tab === 'personal-details-tab') {
            personalTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else if (tab === 'reset-password-tab') {
            resetTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }
    
    // Save active tab to sessionStorage for persistence
    sessionStorage.setItem('activeAccountTab', tab);
}

function togglePassword(fieldId, iconSpan) {
    const input = document.getElementById(fieldId);
    const icon = iconSpan.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Handle form submission with AJAX
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('ajax', 'true');
    formData.append('update_profile', '1'); // Ensure PHP sees this as a profile update
    
    fetch('account_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.user-name').forEach(element => {
                element.textContent = data.name;
            });
            showSuccessModal('Your account settings have been updated successfully.');
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating your profile.');
    });
});

// Handle password form submission
const passwordForm = document.querySelector('#reset-password-tab form');
if (passwordForm) {
    passwordForm.addEventListener('submit', function(e) {
        // Form validation
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New password and confirm password do not match');
            return false;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long');
            return false;
        }
    });
}

function showSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    const messageElement = content.querySelector('p');
    
    // Update success message if provided
    if (message) {
        messageElement.textContent = message;
    }
    
    // Show modal with improved animation
    modal.classList.remove('hidden');
    modal.classList.add('opacity-0');
    modal.classList.remove('opacity-0');
    modal.classList.add('opacity-100');
    
    // Animate content with smoother transition
    setTimeout(() => {
        content.classList.remove('scale-90', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Add touch event for mobile users to dismiss on swipe down
    let touchStartY = 0;
    let touchEndY = 0;
    
    const handleTouchStart = (e) => {
        touchStartY = e.touches[0].clientY;
    };
    
    const handleTouchMove = (e) => {
        touchEndY = e.touches[0].clientY;
    };
    
    const handleTouchEnd = () => {
        if (touchEndY - touchStartY > 70) { // Swipe down detected
            closeSuccessModal();
        }
        touchStartY = 0;
        touchEndY = 0;
    };
    
    modal.addEventListener('touchstart', handleTouchStart, {once: true});
    modal.addEventListener('touchmove', handleTouchMove, {once: true});
    modal.addEventListener('touchend', handleTouchEnd, {once: true});
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    
    // Animate out with improved transition
    content.classList.add('scale-90', 'opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    
    // Fade out modal
    setTimeout(() => {
        modal.classList.add('opacity-0');
        modal.classList.remove('opacity-100');
    }, 100);
    
    // Hide modal after animation completes
    setTimeout(() => {
        modal.classList.add('hidden');
        // Reset classes for next time
        content.classList.remove('scale-90', 'opacity-0');
    }, 300);
}

// Check for profile photo updates from other pages
window.addEventListener('load', function() {
    const newProfilePhoto = sessionStorage.getItem('newProfilePhoto');
    if (newProfilePhoto) {
        updateAllProfilePhotos(newProfilePhoto);
        sessionStorage.removeItem('newProfilePhoto'); // Clear after using
    }
});

// Show Personal Details tab by default
showTab('personal-details-tab');

// Sidebar toggle logic
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
});

// Active state logic
const currentPage = window.location.pathname.split('/').pop();
const navMap = {
    'dashboard.php': 'nav-dashboard',
    'appointments.php': 'nav-appointments',
    'patient_records.php': 'nav-patient-records',
    'patient_feedback.php': 'nav-patient-feedback',
    'account_settings.php': 'nav-account-settings'
};
if (navMap[currentPage]) {
    document.getElementById(navMap[currentPage]).classList.add('active');
}

// Logout modal handling
document.querySelectorAll('a.logout-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('logoutModal').classList.remove('hidden');
    });
});

// Use querySelector to get the buttons inside the modal
const cancelLogoutBtn = document.querySelector('#logoutModal #cancelLogout');
const confirmLogoutBtn = document.querySelector('#logoutModal #confirmLogout');
if (cancelLogoutBtn) {
    cancelLogoutBtn.onclick = function() {
        document.getElementById('logoutModal').classList.add('hidden');
    };
}
if (confirmLogoutBtn) {
    confirmLogoutBtn.onclick = function() {
        window.location.href = 'admin_login.php';
    };
}

// Check for saved active tab and restore it
document.addEventListener('DOMContentLoaded', function() {
    const savedTab = sessionStorage.getItem('activeAccountTab');
    if (savedTab) {
        showTab(savedTab);
    }
});
</script>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden transition-opacity duration-300">
  <div id="successModalContent" class="bg-white rounded-xl shadow-lg p-8 max-w-sm w-full text-center relative transform transition-all duration-300 scale-90 opacity-0">
    <div class="flex justify-center -mt-14 mb-2">
      <div class="bg-green-400 rounded-full w-20 h-20 flex items-center justify-center">
        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
    </div>
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Awesome!</h2>
    <p class="text-gray-600 mb-6">Your account settings have been updated successfully.</p>
    <button onclick="closeSuccessModal()" class="w-full bg-green-400 text-white font-semibold py-2 rounded hover:bg-green-500 transition">OK</button>
  </div>
</div>
<!-- Profile Photo Crop Modal -->
<div id="cropModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg p-6 max-w-2xl w-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Choose profile picture</h3>
            <button onclick="closeCropModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="relative w-full" style="height: 400px;">
            <img id="cropImage" src="" alt="Image to crop" class="max-h-full mx-auto">
        </div>
        <div class="flex justify-end space-x-2 mt-4">
            <button onclick="closeCropModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                Cancel
            </button>
            <button onclick="saveCroppedImage()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Save
            </button>
        </div>
    </div>
</div>
<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-30 z-50 hidden">
  <div class="bg-white rounded-xl shadow-lg p-8 max-w-xs w-full text-center">
    <h2 class="text-lg font-semibold text-blue-700 mb-2">Confirm logout</h2>
    <hr class="my-2 border-blue-100">
    <p class="text-gray-700 mb-6">Are you sure you want to log out?</p>
    <div class="flex justify-center space-x-4">
      <button id="cancelLogout" class="px-4 py-1 rounded bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200">Cancel</button>
      <button id="confirmLogout" class="px-4 py-1 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">OK</button>
    </div>
  </div>
</div>
