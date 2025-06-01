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
    <div class="flex flex-col items-center mb-8">
        <img alt="Profile photo" class="rounded-full w-24 h-24 object-cover mb-2"
            src="<?php echo (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>" />
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

        <!-- Appointments: Admin & Dentist -->
        <?php if (in_array($role, ['admin', 'dentist'])): ?>
            <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'appointments.php' ? 'active-sidebar-link' : ''; ?>"
                href="appointments.php">
                <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <span>Appointments</span>
            </a>
        <?php endif; ?>

        <!-- Patient Records: All -->
        <?php if (in_array($role, ['admin', 'dentist', 'helper'])): ?>
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
        <!--     <a class="relative flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 <?php echo $currentPage == 'help_support.php' ? 'active-sidebar-link' : ''; ?>"
            href="help_support.php">
            <div class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-lg text-gray-700">
                <i class="fas fa-question-circle"></i>
            </div>
            <span>Help & Support</span>
        </a> -->
    </nav>


    <a href="../login.php"
        class="mt-auto flex justify-center items-center space-x-2 text-red-600 hover:text-red-700 font-semibold text-sm">
        <i class="fas fa-sign-out-alt fa-lg"></i>
        <span>Logout</span>
    </a>
</aside>

<script>
    /*     // Sidebar toggle logic
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
     */

    /*   const currentPage = window.location.pathname.split('/').pop();
      const navMap = {
          'dashboard.php': 'nav-dashboard',
          'appointments.php': 'nav-appointments',
          'patient_records.php': 'nav-patient-records',
          'patient_feedback.php': 'nav-patient-feedback',
          'account_settings.php': 'nav-account-settings'
      };
      if (navMap[currentPage]) {
          document.getElementById(navMap[currentPage]).classList.add('active');
      } */

    document.addEventListener('DOMContentLoaded', function () {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function (event) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnToggle = sidebarToggle.contains(event.target);

                if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        }

        // Handle window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        });

        /*     // Add touch swipe support for mobile
            let touchStartX = 0;
            let touchEndX = 0;
    
            document.addEventListener('touchstart', function (event) {
                touchStartX = event.changedTouches[0].screenX;
            }, false);
    
            document.addEventListener('touchend', function (event) {
                touchEndX = event.changedTouches[0].screenX;
                handleSwipe();
            }, false);
    
            function handleSwipe() {
                const swipeThreshold = 50;
                const swipeDistance = touchEndX - touchStartX;
    
                if (Math.abs(swipeDistance) > swipeThreshold) {
                    if (swipeDistance > 0) {
                        // Swipe right - open sidebar
                        sidebar.classList.add('active');
                    } else {
                        // Swipe left - close sidebar
                        sidebar.classList.remove('active');
                    }
                }
            }
     */
        // Add loading indicator for better UX
       /*  document.addEventListener('click', function (event) {
            const link = event.target.closest('a');
            if (link && !link.classList.contains('no-loader')) {
                const loader = document.createElement('div');
                loader.className = 'page-loader';
                loader.innerHTML = '<div class="spinner"></div>';
                document.body.appendChild(loader);
            }
        }); */
    }); 
</script>