<?php
require_once('db.php');

// Check if the notifications table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($tableCheckResult->num_rows == 0) {
    // Create notifications table if it doesn't exist
    $createTableSql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        sender_id INT,
        sender_name VARCHAR(100),
        sender_photo VARCHAR(255),
        message VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        reference_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES patients(id) ON DELETE CASCADE
    )";
    $conn->query($createTableSql);
}

// Get unread notifications count
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $unreadStmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $unreadStmt->bind_param("i", $userId);
    $unreadStmt->execute();
    $unreadResult = $unreadStmt->get_result()->fetch_assoc();
    $unreadCount = $unreadResult['count'];

    // Get recent notifications
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
?>

<!-- Notification Bell -->
<div class="notification-wrapper">
    <button class="notification-toggle" id="notificationBellBtn">
        <i class="fa-solid fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-badge"><?= $unreadCount ?></span>
        <?php endif; ?>
    </button>

    <!-- Notification Dropdown -->
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <span>Notifications</span>
            <?php if ($unreadCount > 0): ?>
                <button id="markAllReadBtn" class="mark-all-read"><i class="fas fa-check-double"></i> Mark all as
                    read</button>
            <?php endif; ?>
        </div>

        <div class="notification-list">
            <?php if (empty($notifications)): ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    // Determine notification type color and icon
                    $typeColor = 'blue';
                    $typeIcon = 'bell';
                    $typeName = $notification['type'];

                    switch ($notification['type']) {
                        case 'appointment':
                            $typeColor = 'appointment';
                            $typeIcon = 'calendar-check';
                            break;
                        case 'cancellation':
                            $typeColor = 'cancellation';
                            $typeIcon = 'calendar-times';
                            break;
                        case 'reschedule':
                            $typeColor = 'reschedule';
                            $typeIcon = 'calendar-day';
                            break;
                        case 'review':
                            $typeColor = 'review';
                            $typeIcon = 'star';
                            break;
                        case 'message':
                            $typeColor = 'message';
                            $typeIcon = 'comment';
                            break;
                        case 'reminder':
                            $typeColor = 'reminder';
                            $typeIcon = 'clock';
                            break;
                    }
                    ?>

                    <div class="notification-item type-<?php echo $typeColor; ?> <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
                        data-id="<?php echo $notification['id']; ?>">
                        <!-- Notification Header with Type -->
                        <div class="notification-item-header">
                            <div class="notification-type-icon icon-<?php echo $typeColor; ?>">
                                <i class="fas fa-<?php echo $typeIcon; ?>"></i>
                            </div>
                            <div class="notification-type">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $typeName)); ?>
                            </div>
                            <div class="notification-date">
                                <?php echo date('M d, Y', strtotime($notification['created_at'])); ?>
                            </div>
                        </div>

                        <!-- Notification Content -->
                        <div class="notification-content">
                            <!-- Sender Photo -->
                            <?php if (!empty($notification['sender_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($notification['sender_photo']); ?>" alt="Sender"
                                    class="sender-photo">
                            <?php else: ?>
                                <div class="sender-initial"
                                    style="background-color: var(--<?php echo $typeColor; ?>-color, #4a89dc);">
                                    <?php echo !empty($notification['sender_name']) ? strtoupper(substr($notification['sender_name'], 0, 1)) : 'C'; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Message Bubble -->
                            <div class="message-bubble">
                                <?php if (!empty($notification['sender_name'])): ?>
                                    <div class="sender-name"><?php echo htmlspecialchars($notification['sender_name']); ?></div>
                                <?php endif; ?>
                                <div class="message-text"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <div class="message-time"><?php echo date('h:i A', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const bellBtn = document.getElementById('notificationBellBtn');
        const dropdown = document.querySelector('.notification-dropdown');
        const wrapper = document.querySelector('.notification-wrapper');
        const markAllReadBtn = document.getElementById('markAllReadBtn');

        // Toggle dropdown when bell is clicked
        bellBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            dropdown.classList.toggle("show");
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", function (e) {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.remove("show");
            }
        });

        // Mark individual notification as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function () {
                const notificationId = this.getAttribute('data-id');
                markAsRead(notificationId);
                this.classList.remove('unread');
            });
        });

        // Mark all notifications as read
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                markAsRead(0); // 0 means mark all as read
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
                document.querySelector('.notification-badge')?.remove();
            });
        }

        // Function to mark notification as read
        function markAsRead(notificationId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_read&notification_id=' + notificationId
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to mark notification as read');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    });
</script>