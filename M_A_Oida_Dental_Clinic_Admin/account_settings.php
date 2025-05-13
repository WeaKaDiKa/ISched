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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"/>
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
    </style>
</head>
<body class="bg-white text-gray-900">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="flex flex-col bg-white border-r border-gray-200 w-64 min-w-[256px] py-6 px-4 transition-all duration-300">
        <div class="flex items-center justify-between mb-10">
            <div class="flex items-center space-x-2">
                <img alt="M&A Oida Dental Clinic logo" class="w-8 h-8" src="assets/photo/logo.jpg"/>
                <span class="sidebar-label text-sm font-semibold text-gray-900 whitespace-nowrap">
                    M&A Oida Dental Clinic
                </span>
            </div>
            <button id="sidebarToggle" aria-label="Toggle menu" class="text-blue-600 hover:text-blue-700 focus:outline-none">
                <i class="fas fa-bars fa-lg"></i>
            </button>
        </div>
        <!-- Profile Section -->
        <div class="flex flex-col items-center mb-8">
            <form id="profilePhotoForm" method="post" enctype="multipart/form-data" class="relative group">
                <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="hidden" onchange="showCropModal(this)">
                <label for="profile_photo" class="cursor-pointer">
                    <img alt="Profile photo" class="rounded-full w-24 h-24 object-cover mb-2 border-2 border-gray-200" src="<?php echo !empty($admin['profile_photo']) && file_exists($admin['profile_photo']) ? htmlspecialchars($admin['profile_photo']) : 'assets/photo/default_avatar.png'; ?>"/>
                    <span class="absolute bottom-2 right-2 bg-gray-800 bg-opacity-80 rounded-full p-2 flex items-center justify-center text-white border-2 border-white group-hover:bg-blue-600 transition">
                        <i class="fas fa-camera"></i>
                    </span>
                </label>
            </form>
            <h3 class="text-center text-sm font-semibold text-gray-900 leading-tight">
                <?php echo htmlspecialchars($admin['name'] ?? ''); ?>
            </h3>
            <p class="text-center text-xs text-gray-500 mt-1">
                Professional Dentist
            </p>
        </div>
        <nav class="flex flex-col space-y-2 text-gray-700 text-sm font-medium">
            <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'border-l-4 border-blue-600 bg-blue-100 text-blue-900 font-bold' : ''; ?>" href="dashboard.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-home"></i>
                </div>
                <span>Dashboard</span>
            </a>
            <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'border-l-4 border-blue-600 bg-blue-100 text-blue-900 font-bold' : ''; ?>" href="appointments.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700 relative">
                    <i class="fas fa-calendar-alt"></i>
                    <?php if ($pendingAppointments > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                        <?= $pendingAppointments ?>
                    </span>
                    <?php endif; ?>
                </div>
                <span>Appointments</span>
            </a>
            <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'patient_record.php' ? 'border-l-4 border-blue-600 bg-blue-100 text-blue-900 font-bold' : ''; ?>" href="patient_record.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-user-injured"></i>
                </div>
                <span>Patient Records</span>
            </a>
            <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'patient_feedback.php' ? 'border-l-4 border-blue-600 bg-blue-100 text-blue-900 font-bold' : ''; ?>" href="patient_feedback.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700 relative">
                    <i class="fas fa-comment-alt"></i>
                    <?php if ($unseenFeedback > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                        <?= $unseenFeedback ?>
                    </span>
                    <?php endif; ?>
                </div>
                <span>Patient Feedback</span>
            </a>
            <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo basename($_SERVER['PHP_SELF']) == 'account_settings.php' ? 'border-l-4 border-blue-600 bg-blue-100 text-blue-900 font-bold' : ''; ?>" href="account_settings.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-cog"></i>
                </div>
                <span>Account Settings</span>
            </a>
            <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="request_access.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-lock"></i>
                </div>
                <span>Request for Access</span>
            </a>
            <a class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100" href="#help-section">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-question-circle"></i>
                </div>
                <span>Help & Support</span>
            </a>
        </nav>
        <a href="admin_login.php" class="mt-auto flex justify-center items-center space-x-2 text-red-600 hover:text-red-700 font-semibold text-sm">
            <i class="fas fa-sign-out-alt fa-lg"></i>
            <span>Logout</span>
        </a>
    </aside>
    <!-- Main content -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Top bar -->
        <header class="flex items-center justify-between bg-blue-300 px-6 py-3 border-b border-gray-300">
            <div class="text-gray-900 text-sm font-normal">
                <span class="font-semibold">North Fairview Branch</span>
            </div>
            <div class="flex items-center space-x-4 ml-auto">
                <button class="bg-purple-700 text-white text-xs font-semibold rounded-md px-4 py-1 hover:bg-purple-800">
                    Walk-in Appointment Form
                </button>
                <button aria-label="Notifications" class="text-gray-900 hover:text-gray-700 focus:outline-none relative">
                    <i class="fas fa-bell fa-lg"></i>
                    <?php if ($pendingAppointments > 0 || $unseenFeedback > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                        <?= $pendingAppointments + $unseenFeedback ?>
                    </span>
                    <?php endif; ?>
                </button>
                <img alt="Profile photo of <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" 
                     class="rounded-full w-10 h-10 object-cover" 
                     src="<?php echo !empty($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>" />
            </div>
        </header>

        <!-- Breadcrumb Navigation -->
        <?php 
            $breadcrumbLabel = 'Account Settings';
            include 'breadcrumb.php'; 
        ?>
        <!-- End Breadcrumb Navigation -->

        <!-- Content area -->
        <div class="flex-1 flex items-center justify-center p-4 overflow-y-auto min-h-screen bg-gray-50">
            <div class="max-w-5xl mx-auto bg-white rounded-xl shadow-md p-6">
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex space-x-8" aria-label="Tabs">
                        <a href="#" id="tab-personal" onclick="showTab('personal-details-tab'); return false;" class="text-blue-600 border-b-2 border-blue-600 px-1 pb-2 font-medium">Personal Details</a>
                        <a href="#" id="tab-reset" onclick="showTab('reset-password-tab'); return false;" class="text-gray-500 px-1 pb-2 font-medium">Reset Password</a>
                    </nav>
                </div>
                <div id="personal-details-tab">
                    <form id="profileForm" method="post" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-user"></i></span>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ($admin['first_name'] ?? '')); ?>" class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter First Name" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-user"></i></span>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ($admin['last_name'] ?? '')); ?>" class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Last Name" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-user"></i></span>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ($admin['name'] ?? '')); ?>" class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Full Name">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Age <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-birthday-cake"></i></span>
                                <input type="number" name="age" value="<?php echo htmlspecialchars($_POST['age'] ?? ($admin['age'] ?? '')); ?>" class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Select Age" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-phone"></i></span>
                                <input type="text" name="mobile" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ($admin['mobile'] ?? '')); ?>" class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Mobile Number" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Create ID <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-id-badge"></i></span>
                                <input type="text" name="create_id" value="<?php echo htmlspecialchars($admin['admin_id'] ?? ''); ?>" class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Create Unique ID" required readonly>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email ID <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ($admin['email'] ?? '')); ?>" class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Email ID" required>
                            </div>
                        </div>
                        <?php $gender = $_POST['gender'] ?? ($admin['gender'] ?? ''); ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                            <div class="flex items-center space-x-4 mt-2">
                                <label class="inline-flex items-center cursor-pointer transition-colors duration-200 group-hover:text-blue-500 group-focus-within:text-blue-500">
                                    <input type="radio" name="gender" value="Male" class="form-radio text-blue-600" required <?php if ($gender === 'Male') echo 'checked'; ?>>
                                    <span class="ml-2">Male</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer transition-colors duration-200 group-hover:text-blue-500 group-focus-within:text-blue-500">
                                    <input type="radio" name="gender" value="Female" class="form-radio text-blue-600" required <?php if ($gender === 'Female') echo 'checked'; ?>>
                                    <span class="ml-2">Female</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end mt-6 space-x-2 md:col-span-4">
                            <button type="button" onclick="window.location.href='dashboard.php'" class="px-4 py-2 rounded bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition-colors duration-200">Cancel</button>
                            <button type="submit" name="update_profile" class="px-4 py-2 rounded bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors duration-200">Update</button>
                        </div>
                    </form>
                </div>
                <div id="reset-password-tab" class="hidden">
                    <div class="flex justify-center">
                        <form method="post" class="bg-white rounded-xl shadow-md p-8 w-full max-w-md">
                            <?php if (!empty($success)) { echo '<div class="text-green-600 mb-4">'.$success.'</div>'; } ?>
                            <?php if (!empty($error)) { echo '<div class="text-red-600 mb-4">'.$error.'</div>'; } ?>
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium mb-1">Current password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="password" name="current_password" id="current_password" class="block w-full border border-gray-300 rounded-md py-2 pr-10 pl-3 focus:ring-blue-500 focus:border-blue-500 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.25)] group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.15)]" placeholder="Enter Current password" required>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" onclick="togglePassword('current_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium mb-1">New password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="password" name="new_password" id="new_password" class="block w-full border border-gray-300 rounded-md py-2 pr-10 pl-3 focus:ring-blue-500 focus:border-blue-500 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.25)] group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.15)]" placeholder="Your password must be 8-20 characters long." required>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" onclick="togglePassword('new_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label class="block text-gray-700 font-medium mb-1">Confirm new password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" id="confirm_password" class="block w-full border border-gray-300 rounded-md py-2 pr-10 pl-3 focus:ring-blue-500 focus:border-blue-500 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.25)] group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.15)]" placeholder="Confirm new password" required>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer" onclick="togglePassword('confirm_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="button" onclick="showTab('personal-details-tab')" class="px-4 py-2 rounded bg-gray-200 text-gray-700 font-medium hover:bg-gray-300">
                                    Cancel
                                </button>
                                <button type="submit" name="update_password" class="px-4 py-2 rounded bg-blue-600 text-white font-medium hover:bg-blue-700">
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

function showSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    const messageElement = content.querySelector('p');
    
    // Update success message
    messageElement.textContent = message;
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-90', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Check for profile photo updates from other pages
window.addEventListener('load', function() {
    const newProfilePhoto = sessionStorage.getItem('newProfilePhoto');
    if (newProfilePhoto) {
        updateAllProfilePhotos(newProfilePhoto);
        sessionStorage.removeItem('newProfilePhoto'); // Clear after using
    }
});

function closeCropModal() {
    const cropModal = document.getElementById('cropModal');
    cropModal.classList.add('hidden');
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    // Reset file input
    document.getElementById('profile_photo').value = '';
}

function showTab(tab) {
    document.getElementById('personal-details-tab').classList.add('hidden');
    document.getElementById('reset-password-tab').classList.add('hidden');
    document.getElementById(tab).classList.remove('hidden');
    // Update tab styles
    document.getElementById('tab-personal').classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
    document.getElementById('tab-reset').classList.remove('text-blue-600', 'border-blue-600', 'border-b-2');
    document.getElementById('tab-personal').classList.add('text-gray-500');
    document.getElementById('tab-reset').classList.add('text-gray-500');
    if(tab === 'personal-details-tab') {
        document.getElementById('tab-personal').classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
        document.getElementById('tab-personal').classList.remove('text-gray-500');
    } else {
        document.getElementById('tab-reset').classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
        document.getElementById('tab-reset').classList.remove('text-gray-500');
    }
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
        console.log(data); // Debug: log the full response
        if (data.success) {
            document.querySelectorAll('.user-name').forEach(element => {
                element.textContent = data.name;
            });
            showSuccessModal();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating your profile.');
    });
});
function showSuccessModal() {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    
    // Update success message to mention profile photo
    document.querySelector('#successModalContent p').textContent = 'Your profile photo has been updated successfully.';
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-90', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}
function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-90', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
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
document.querySelectorAll('a.logout-btn[href="admin_login.php"]').forEach(btn => {
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
</script>
</body>
</html>