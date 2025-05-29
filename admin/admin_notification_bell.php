<?php
require_once('db.php');

// Check if the admin_notifications table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
if ($tableCheckResult->num_rows == 0) {
    // Create admin_notifications table if it doesn't exist
    $createTableSql = "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTableSql);
}

// Get unread notifications count
$unreadStmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result()->fetch_assoc();
$unreadCount = $unreadResult['count'];

// Get recent notifications
$stmt = $conn->prepare("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!-- Notification Bell -->
<div class="relative inline-block">
    <button id="notificationBellBtn" class="text-gray-900 hover:text-gray-700 focus:outline-none relative">
        <i class="fas fa-bell fa-lg"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
            </span>
        <?php endif; ?>
    </button>
    
    <!-- Notification Dropdown -->
    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl overflow-hidden z-50" style="max-height: 500px; overflow-y: auto;">
        <div class="py-3 px-4 bg-blue-600 text-white flex justify-between items-center">
            <span class="font-bold text-white text-base">Notifications</span>
            <?php if ($unreadCount > 0): ?>
                <button id="markAllReadBtn" class="text-xs text-white hover:text-blue-100">Mark all as read</button>
            <?php endif; ?>
        </div>
        
        <div class="divide-y divide-gray-200">
            <?php if (empty($notifications)): ?>
                <div class="py-4 px-4 text-center text-gray-500">No notifications</div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    // Determine notification type color and icon
                    $typeColor = 'blue';
                    $typeIcon = 'bell';
                    
                    // Check if the message contains approved appointment indicator
                    $isApproved = strpos($notification['message'], '✅') !== false;
                    
                    switch($notification['type']) {
                        case 'appointment':
                        case 'new_appointment':
                            $typeColor = 'blue';
                            $typeIcon = 'calendar-check';
                            break;
                        case 'upcoming_appointment_today':
                            $typeColor = $isApproved ? 'green' : 'orange';
                            $typeIcon = $isApproved ? 'calendar-check' : 'calendar-day';
                            break;
                        case 'upcoming_appointment_tomorrow':
                            $typeColor = $isApproved ? 'teal' : 'blue';
                            $typeIcon = $isApproved ? 'calendar-check' : 'calendar-alt';
                            break;
                        case 'cancellation':
                            $typeColor = 'red';
                            $typeIcon = 'calendar-times';
                            break;
                        case 'reschedule':
                            $typeColor = 'orange';
                            $typeIcon = 'calendar-day';
                            break;
                        case 'review':
                            $typeColor = 'yellow';
                            $typeIcon = 'star';
                            break;
                        case 'reaction':
                            $typeColor = 'purple';
                            $typeIcon = 'thumbs-up';
                            break;
                        case 'reminder':
                            $typeColor = 'green';
                            $typeIcon = 'clock';
                            break;
                    }
                    ?>
                    
                    <div class="notification-item py-4 px-4 hover:bg-gray-50 transition-colors <?= $notification['is_read'] ? '' : 'border-l-4 border-<?=$typeColor?>-500' ?>" data-id="<?= $notification['id'] ?>">
                        <!-- Notification Header with Type -->
                        <div class="flex items-center mb-2">
                            <div class="w-8 h-8 rounded-full bg-<?=$typeColor?>-100 text-<?=$typeColor?>-600 flex items-center justify-center mr-2">
                                <i class="fas fa-<?=$typeIcon?> text-sm"></i>
                            </div>
                            <div class="text-sm font-semibold text-<?=$typeColor?>-600 capitalize">
                                <?= htmlspecialchars(str_replace('_', ' ', $notification['type'])) ?>
                            </div>
                            <div class="ml-auto text-xs text-gray-500">
                                <?= date('M d, Y', strtotime($notification['created_at'])) ?>
                            </div>
                        </div>
                        
                        <!-- Notification Content -->
                        <div class="flex items-start mt-2">
                            <!-- User Photo -->
                            <div class="flex-shrink-0 mr-3">
                                <?php if (!empty($notification['user_photo'])): ?>
                                    <img src="<?= htmlspecialchars($notification['user_photo']) ?>" alt="User" class="w-12 h-12 rounded-full object-cover border-2 border-<?=$typeColor?>-200">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-full bg-<?=$typeColor?>-500 flex items-center justify-center text-white font-bold border-2 border-<?=$typeColor?>-200">
                                        <?= !empty($notification['user_name']) ? strtoupper(substr($notification['user_name'], 0, 1)) : '?' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Notification Message -->
                            <div class="flex-1 bg-gray-50 rounded-lg p-3">
                                <?php if (!empty($notification['user_name'])): ?>
                                    <div class="font-semibold text-sm text-gray-800"><?= htmlspecialchars($notification['user_name']) ?></div>
                                <?php endif; ?>
                                <div class="text-sm text-gray-700 mt-1"><?= htmlspecialchars($notification['message']) ?></div>
                                
                                <?php
                                // Extract patient name from notification message for upcoming appointments
                                if (strpos($notification['type'], 'upcoming_appointment') !== false) {
                                    // Try to extract patient name from the message
                                    preg_match('/: ([^\s]+) ([^\s]+) has a/', $notification['message'], $matches);
                                    if (count($matches) >= 3) {
                                        $firstName = $matches[1];
                                        $lastName = $matches[2];
                                        
                                        // Get patient ID from database
                                        $patientQuery = "SELECT id FROM patients WHERE first_name = ? AND last_name = ? LIMIT 1";
                                        $patientStmt = $conn->prepare($patientQuery);
                                        $patientStmt->bind_param("ss", $firstName, $lastName);
                                        $patientStmt->execute();
                                        $patientResult = $patientStmt->get_result();
                                        
                                        if ($patientResult && $patientResult->num_rows > 0) {
                                            $patientData = $patientResult->fetch_assoc();
                                            $patientId = $patientData['id'];
                                            ?>
                                            <div class="mt-2 flex justify-between items-center">
                                                <a href="view_patient.php?id=<?= $patientId ?>" class="text-<?=$typeColor?>-600 hover:text-<?=$typeColor?>-800 text-xs font-medium flex items-center">
                                                    <i class="fas fa-user-circle mr-1"></i> View Patient Record
                                                </a>
                                                <a href="appointments.php?patient_id=<?= $patientId ?>" class="text-blue-600 hover:text-blue-800 text-xs font-medium flex items-center">
                                                    <i class="fas fa-calendar-alt mr-1"></i> View Appointments
                                                </a>
                                            </div>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                                
                                <div class="text-xs text-gray-500 mt-2 text-right"><?= date('h:i A', strtotime($notification['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bellBtn = document.getElementById('notificationBellBtn');
        const dropdown = document.getElementById('notificationDropdown');
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        
        // Toggle dropdown
        bellBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== bellBtn) {
                dropdown.classList.add('hidden');
            }
        });
        
        // Prevent dropdown from closing when clicking inside it
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Mark individual notification as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                markAsRead(id);
                this.classList.remove('bg-blue-50');
            });
        });
        
        // Mark all as read
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function() {
                markAsRead(0); // 0 means mark all as read
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('bg-blue-50');
                });
            });
        }
        
        // Function to mark notification as read
        function markAsRead(id) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            });
        }
    });
</script>
