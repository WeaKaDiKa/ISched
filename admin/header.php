<header class="flex items-center justify-between bg-blue-300 px-6 py-3 border-b border-gray-300">
    <!-- Welcome Message Section -->
    <div class="flex items-center space-x-3 text-gray-900 text-sm font-normal">
        <span><?php echo $greeting; ?></span>
        <span class="font-bold text-gray-900 text-base">
            <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>!
        </span>
        <span class="text-gray-900">
            | North Fairview Branch
        </span>
    </div>

    <!-- Action Buttons Section -->
    <div class="flex items-center space-x-4">
        <a href="walk_in_appointment.php"
            class="bg-purple-700 text-white text-xs font-semibold rounded-md px-4 py-1 hover:bg-purple-800">
            Walk-in Appointment Form
        </a>
        <button aria-label="Notifications" class="text-gray-900 hover:text-gray-700 focus:outline-none relative">
            <i class="fas fa-bell fa-lg"></i>
            <?php if ($pendingAppointments > 0): ?>
                <span
                    class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                    <?= $pendingAppointments ?>
                </span>
            <?php endif; ?>
        </button>
        <img alt="Profile photo of <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>"
            class="rounded-full w-10 h-10 object-cover"
            src="<?php echo (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>" />
    </div>
</header>