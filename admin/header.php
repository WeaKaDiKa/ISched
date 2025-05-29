<?php
// Get count of unread admin notifications
$unreadNotificationsCount = 0;
try {
    // Check if the admin_notifications table exists
    $tableCheckResult = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
    if ($tableCheckResult->num_rows > 0) {
        $notifStmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
        $notifStmt->execute();
        $result = $notifStmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $unreadNotificationsCount = $row['count'];
        }
    }
} catch (Exception $e) {
    // Silently handle any errors
}
?>
<header class="flex w-full items-center justify-between bg-blue-300 px-6 py-3 border-b border-gray-300">
    <!-- Welcome Message Section -->
    <div class="flex items-center space-x-3 text-gray-900 text-sm font-normal">
        <button id="sidebarToggle" aria-label="Toggle menu"
            class="text-blue-600 hover:text-blue-700 focus:outline-none md:hidden flex">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <span class="lg:block hidden"><?php echo $greeting; ?></span>
        <span class="font-bold text-gray-900 text-base sm:block hidden">
            <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>!
        </span>

        <span class="text-gray-900 lg:block hidden">
            | North Fairview Branch
        </span>
    </div>

    <!-- Action Buttons Section -->
    <div class="flex items-center space-x-4">
        <a href="walk_in_appointment.php"
            class="bg-purple-700 text-white text-xs font-semibold rounded-md hover:bg-purple-800 p-3 text-center">
            Walk-in Form
        </a>
        <?php include('admin_notification_bell.php'); ?>
        <img alt="Profile photo of <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>"
            class="rounded-full w-10 h-10 object-cover"
            src="<?php echo (!empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo'])) ? htmlspecialchars($_SESSION['profile_photo']) : 'assets/photo/default_avatar.png'; ?>" />
    </div>
</header>