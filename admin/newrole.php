<?php
require_once('db.php');
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {

    // Sanitize inputs
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $name = $first_name . ' ' . $last_name;
    $email = $conn->real_escape_string($_POST['email']);
    $age = (int) $_POST['age'];
    $mobile = $conn->real_escape_string($_POST['mobile']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $type = $conn->real_escape_string($_POST['type']);
    $new_hash = password_hash("password", PASSWORD_DEFAULT);

    // Handle profile photo
    $photo_path = '';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "assets/photo/";
        $filename = time() . '_' . basename($_FILES["profile_photo"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
            $photo_path = $filename;
        }
    }

    // Insert dummy to get AUTO_INCREMENT ID
    $conn->query("INSERT INTO admin_logins (profile_photo, email, name, first_name, last_name, age, mobile, gender, type, password) VALUES ('', '', '', '', '', 0, '', '', '', '')");
    $last_id = $conn->insert_id;
    $admin_id = 'ADM-' . str_pad($last_id, 3, '0', STR_PAD_LEFT);

    // Update with real values
    $stmt = $conn->prepare("UPDATE admin_logins SET admin_id=?, profile_photo=?, email=?, name=?, first_name=?, last_name=?, age=?, mobile=?, gender=?, type=?, password=? WHERE id=?");
    $stmt->bind_param("ssssssissssi", $admin_id, $target_file, $email, $name, $first_name, $last_name, $age, $mobile, $gender, $type, $new_hash, $last_id);

    if ($stmt->execute()) {
        header("Location: roles.php");
        exit();
    } else {

    }

    $stmt->close();
    $conn->close();
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Account Role</title>
    <?php require_once 'head.php' ?>
</head>

<body class="bg-white text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php require_once 'nav.php' ?>
        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <?php require_once 'header.php' ?>
            <!-- Breadcrumb Navigation -->
            <?php
            $breadcrumbLabel = 'New Account Role';
            include 'breadcrumb.php';
            ?>
            <!-- End Breadcrumb Navigation -->

            <!-- Content area -->
            <div class="flex-1 flex justify-center p-4  bg-gray-50">
                <div class="max-w-5xl mx-auto bg-white rounded-xl shadow-md p-6">

                    <form id="profileForm" method="post" enctype="multipart/form-data"
                        class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- First Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="first_name"
                                    class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter First Name" required>
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="last_name"
                                    class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter Last Name" required>
                            </div>
                        </div>

                        <!-- Account Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Type <span
                                    class="text-red-500">*</span></label>
                            <select name="type" required
                                class="block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-blue-500 focus:border-blue-500">
                                <option value="admin">Admin</option>
                                <option value="dentist">Dentist</option>
                                <option value="helper">Helper</option>
                            </select>
                        </div>

                        <!-- Age -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Age <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <i class="fas fa-birthday-cake"></i>
                                </span>
                                <input type="number" name="age"
                                    class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter Age" required>
                            </div>
                        </div>

                        <!-- Mobile -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="text" name="mobile"
                                    class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter Mobile Number" required>
                            </div>
                        </div>
                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email ID <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" name="email"
                                    class="pl-10 block w-full border border-gray-300 rounded-md py-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter Email ID" required>
                            </div>
                        </div>

                        <!-- Profile Photo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo <span
                                    class="text-red-500">*</span></label>
                            <input type="file" name="profile_photo" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>


                        <!-- Gender -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span
                                    class="text-red-500">*</span></label>
                            <div class="flex items-center space-x-4 mt-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="gender" value="Male" class="form-radio text-blue-600"
                                        required>
                                    <span class="ml-2">Male</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="gender" value="Female" class="form-radio text-blue-600"
                                        required>
                                    <span class="ml-2">Female</span>
                                </label>
                            </div>
                        </div>

                        <!-- Submit / Cancel -->
                        <div class="md:col-span-2 flex justify-end mt-6 space-x-2">
                            <button type="button" onclick="window.location.href='roles.php'"
                                class="px-4 py-2 rounded bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" name="save_profile"
                                class="px-4 py-2 rounded bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors duration-200">
                                Save
                            </button>
                        </div>

                    </form>

                </div>

            </div>
        </main>
    </div>
    <!-- Success Modal -->
    <div id="successModal"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden transition-opacity duration-300">
        <div id="successModalContent"
            class="bg-white rounded-xl shadow-lg p-8 max-w-sm w-full text-center relative transform transition-all duration-300 scale-90 opacity-0">
            <div class="flex justify-center -mt-14 mb-2">
                <div class="bg-green-400 rounded-full w-20 h-20 flex items-center justify-center">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" stroke-width="3"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Awesome!</h2>
            <p class="text-gray-600 mb-6">Your account settings have been saved successfully.</p>
            <button onclick="closeSuccessModal()"
                class="w-full bg-green-400 text-white font-semibold py-2 rounded hover:bg-green-500 transition">OK</button>
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
                <button onclick="closeCropModal()"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
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
                <button id="cancelLogout"
                    class="px-4 py-1 rounded bg-blue-100 text-blue-700 font-semibold hover:bg-blue-200">Cancel</button>
                <button id="confirmLogout"
                    class="px-4 py-1 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">OK</button>
            </div>
        </div>
    </div>
    <script>
        let cropper = null;

        function showCropModal(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
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
            canvas.toBlob(function (blob) {
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
        window.addEventListener('load', function () {
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


        document.querySelectorAll('a.logout-btn[href="admin_login.php"]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('logoutModal').classList.remove('hidden');
            });
        });
        // Use querySelector to get the buttons inside the modal
        const cancelLogoutBtn = document.querySelector('#logoutModal #cancelLogout');
        const confirmLogoutBtn = document.querySelector('#logoutModal #confirmLogout');
        if (cancelLogoutBtn) {
            cancelLogoutBtn.onclick = function () {
                document.getElementById('logoutModal').classList.add('hidden');
            };
        }
        if (confirmLogoutBtn) {
            confirmLogoutBtn.onclick = function () {
                window.location.href = 'admin_login.php';
            };
        }
    </script>
</body>

</html>