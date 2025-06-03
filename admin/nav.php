<!-- Sidebar -->

<style>
    #sidebar {
        left: 0;
        transition: left 0.3s ease;
        /* Fix: animate 'left' instead of 'right' */
    }

    @media (max-width: 768px) {
        #sidebar {
            position: fixed;
            left: -256px;
            top: 0;
            bottom: 0;
            z-index: 50;
            transition: left 0.3s ease;
        }
    }

    #sidebar.active {
        left: 0;
        transition: left 0.3s ease;
        /* Fix: match transition property */
    }

    #sidebarToggle {

        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        padding: 0 !important;
        margin: 0;
        border-radius: 8px;
        -webkit-tap-highlight-color: transparent;
    }

    #sidebarToggle:active {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .sidebar-icon {
        @apply transition-all duration-200 ease-in-out;
    }

    .sidebar-icon.active .icon-circle,
    .sidebar-icon:focus .icon-circle,
    .sidebar-icon:hover .icon-circle {
        @apply bg-blue-100 text-blue-700 shadow-lg scale-110;
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
    #sidebar.collapsed .flex.flex-col.items-center.mb-8>h3,
    #sidebar.collapsed .flex.flex-col.items-center.mb-8>p {
        display: none !important;
    }

    #sidebar.collapsed .flex.flex-col.items-center.mb-8 {
        align-items: flex-start !important;
    }

    #sidebar.collapsed img.w-24 {
        margin-bottom: 0 !important;
    }

    .active-sidebar-link {
        background-color: #f4f6f8;
        /* light gray */
        position: relative;
    }

    .active-sidebar-link::before {
        content: "";
        position: absolute;
        left: 0;
        top: 8px;
        bottom: 8px;
        width: 4px;
        background: #2563eb;
        /* blue-600 */
        border-radius: 8px;
    }

    .active-sidebar-link,
    .active-sidebar-link span,
    .active-sidebar-link i {
        color: #1e3a8a !important;
        /* blue-900 */
        font-weight: bold;
    }
</style>
<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<?php
$role = $_SESSION['admin_type'] ?? '';
?>
<aside id="sidebar"
    class="flex flex-col bg-white border-r border-gray-200 w-64 min-w-[256px] py-6 px-4 transition-all duration-300">
    <div class="flex items-center justify-between mb-10">
        <div class="flex items-center space-x-2">
            <img alt="M&amp;A Oida Dental Clinic logo" class="w-8 h-8" src="assets/photo/logo.jpg" />
            <span class="sidebar-label text-sm font-semibold text-gray-900 whitespace-nowrap">
                M&amp;A Oida Dental Clinic
            </span>
        </div>

    </div>

    <!-- Profile Section -->
    <div class="flex flex-col items-center mb-8 relative">
        <img id="currentProfilePhotoSidebar" alt="Profile photo" class="rounded-full w-24 h-24 object-cover mb-2" src="<?php echo (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>" />
        <!-- Choose Profile Photo Button (visible only on Account Settings page) -->
        <label id="chooseProfilePhotoLabelSidebar" for="profilePhotoInputSidebar" class="cursor-pointer bg-blue-600 text-white text-xs px-3 py-1 rounded-md hover:bg-blue-700 transition-colors duration-200 mt-2 hidden">
            Choose Profile Photo
        </label>
        <input type="file" id="profilePhotoInputSidebar" accept="image/*" class="hidden" onchange="showCropModal(this)">
        <!-- End Choose Profile Photo Button -->
        <h3 class="text-center text-sm font-semibold text-gray-900 leading-tight">
            <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
        </h3>
        <p class="text-center text-xs text-gray-500 mt-1">
            <?= ucfirst($role); ?>
        </p>
    </div>

    <!-- Navigation -->
    <nav class="flex flex-col space-y-2 text-gray-700 text-sm font-medium">
        <!-- Dashboard: Admin only -->
        <?php if ($role === 'admin'): ?>
            <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'dashboard.php' ? 'active-sidebar-link' : ''; ?>"
                href="dashboard.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-home"></i>
                </div>
                <span>Dashboard</span>
            </a>
        <?php endif; ?>

        <!-- Appointments: Admin, Dentist and Helper -->
        <?php if (in_array($role, ['admin', 'dentist', 'helper'])): ?>
            <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'appointments.php' ? 'active-sidebar-link' : ''; ?>"
                href="appointments.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <span>Appointments</span>
            </a>
        <?php endif; ?>

        <!-- Patient Records: Admin and Dentist only -->
        <?php if (in_array($role, ['admin', 'dentist'])): ?>
            <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'patient_record.php' ? 'active-sidebar-link' : ''; ?>"
                href="patient_record.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-user-injured"></i>
                </div>
                <span>Patient Records</span>
            </a>
        <?php endif; ?>

        <!-- Patient Feedback: Admin & Dentist -->
        <?php if (in_array($role, ['admin', 'dentist'])): ?>
            <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'patient_feedback.php' ? 'active-sidebar-link' : ''; ?>"
                href="patient_feedback.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-comment-alt"></i>
                </div>
                <span>Patient Feedback</span>
            </a>
        <?php endif; ?>

        <!-- Account Settings: All -->
        <?php if (in_array($role, ['admin', 'dentist', 'helper'])): ?>
            <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'account_settings.php' ? 'active-sidebar-link' : ''; ?>"
                href="account_settings.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-cog"></i>
                </div>
                <span>Account Settings</span>
            </a>
        <?php endif; ?>

        <!-- Roles: Admin only -->
        <?php if ($role === 'admin'): ?>
            <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'roles.php' ? 'active-sidebar-link' : ''; ?>"
                href="roles.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-lock"></i>
                </div>
                <span>Add Account</span>
            </a>
        <?php endif; ?>

        <!-- Help & Support: All -->
        <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'help_support.php' ? 'active-sidebar-link' : ''; ?>"
            href="help_support.php">
            <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                <i class="fas fa-info"></i>
            </div>
            <span>Help & Support</span>
        </a>
    </nav>


    <a href="../login.php"
        class="mt-auto flex justify-center items-center space-x-2 text-red-600 hover:text-red-700 font-semibold text-sm">
        <i class="fas fa-sign-out-alt fa-lg"></i>
        <span>Logout</span>
    </a>
</aside>

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
        <p class="text-gray-600 mb-6">Your profile photo has been updated successfully.</p>
        <button onclick="closeSuccessModal()"
            class="w-full bg-green-400 text-white font-semibold py-2 rounded hover:bg-green-500 transition">OK</button>
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
        // Update the profile photo in the sidebar
        const sidebarProfilePhoto = document.getElementById('currentProfilePhotoSidebar');
        if (sidebarProfilePhoto) {
            sidebarProfilePhoto.src = imageUrl;
        }

         // Update the profile photo on the account settings page if it exists
         const accountProfilePhoto = document.getElementById('currentProfilePhoto');
         if(accountProfilePhoto) {
             accountProfilePhoto.src = imageUrl;
         }
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

            // Upload using fetch to account_settings.php (where the handling logic is)
            fetch('account_settings.php', { // Target account_settings.php for processing
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

                    } else {
                        alert('Failed to update profile photo: ' + data.message);
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

    function closeCropModal() {
        const cropModal = document.getElementById('cropModal');
        cropModal.classList.add('hidden');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        // Reset file input
        const profilePhotoInput = document.getElementById('profilePhotoInputSidebar'); // Target the sidebar input
        if (profilePhotoInput) {
             profilePhotoInput.value = '';
        }
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

    // Sidebar toggle logic

    document.addEventListener('DOMContentLoaded', function () {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function (event) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            });
        }

        // Show 'Choose Profile Photo' button only on account_settings.php
        const currentPage = window.location.pathname.split('/').pop();
        const chooseButtonLabel = document.getElementById('chooseProfilePhotoLabelSidebar');
        if (currentPage === 'account_settings.php' && chooseButtonLabel) {
            chooseButtonLabel.classList.remove('hidden');
        }

        // Logout modal logic
        document.querySelectorAll('a.logout-btn[href="admin_login.php"]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('logoutModal').classList.remove('hidden');
            });
        });

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

    });

</script>
</body>

</html>
</script>
</script>
</script>