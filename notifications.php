<?php
require_once('session.php');
require_once('db.php');

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Mark notification as read
    if ($action === 'mark_read') {
        $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

        if ($notificationId > 0) {
            // Mark specific notification as read
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notificationId, $userId);
        } else {
            // Mark all notifications as read
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to mark notification as read']);
        }
        exit;
    }
}

// Get notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get unread count
$unreadStmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadStmt->bind_param("i", $userId);
$unreadStmt->execute();
$unreadResult = $unreadStmt->get_result()->fetch_assoc();
$unreadCount = $unreadResult['count'];

// Return JSON if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
    exit;
}

// Otherwise, render the notifications page
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - M&A Oida Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
        }

        header {
            background-color: #4a89dc;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-size: 16px;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .notification-list {
            margin-top: 20px;
        }

        .notification-item {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        /* Type colors */
        .type-appointment {
            border-left: 4px solid #4a89dc;
        }

        .type-cancellation {
            border-left: 4px solid #dc4a4a;
        }

        .type-reschedule {
            border-left: 4px solid #dc8c4a;
        }

        .type-review {
            border-left: 4px solid #dcce4a;
        }

        .type-reminder {
            border-left: 4px solid #4adc7d;
        }

        .type-message {
            border-left: 4px solid #9c4adc;
        }

        /* Type icon colors */
        .icon-appointment {
            background-color: #e6f0ff;
            color: #4a89dc;
        }

        .icon-cancellation {
            background-color: #ffe6e6;
            color: #dc4a4a;
        }

        .icon-reschedule {
            background-color: #fff0e6;
            color: #dc8c4a;
        }

        .icon-review {
            background-color: #fffde6;
            color: #dcce4a;
        }

        .icon-reminder {
            background-color: #e6ffef;
            color: #4adc7d;
        }

        .icon-message {
            background-color: #f2e6ff;
            color: #9c4adc;
        }

        .notification-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .notification-type-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .notification-type {
            font-weight: 600;
            font-size: 14px;
            text-transform: capitalize;
            flex-grow: 1;
        }

        .notification-date {
            font-size: 12px;
            color: #888;
            flex-shrink: 0;
        }

        .notification-content {
            display: flex;
            align-items: flex-start;
        }

        .sender-photo {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 2px solid #e6e6e6;
            flex-shrink: 0;
        }

        .sender-initial {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .message-bubble {
            background-color: #f5f5f5;
            border-radius: 12px;
            padding: 12px;
            flex-grow: 1;
        }

        .sender-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .message-text {
            font-size: 14px;
            line-height: 1.4;
        }

        .message-time {
            font-size: 12px;
            color: #888;
            margin-top: 8px;
            text-align: right;
        }

        .no-notifications {
            text-align: center;
            padding: 40px 20px;
            color: #757575;
        }

        .no-notifications i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #d0d0d0;
        }

        .mark-all-read {
            background-color: #4a89dc;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            float: right;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .mark-all-read:hover {
            background-color: #3a79cc;
        }

        .mark-all-read i {
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <header>
        <a href="profile.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        <h1>Notifications</h1>
    </header>

    <div class="container">
        <?php if (!empty($notifications)): ?>
            <button class="mark-all-read" id="markAllRead"><i class="fas fa-check-double"></i> Mark All as Read</button>
            <div class="notification-list">
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
                        <div class="notification-header">
                            <div class="notification-type-icon icon-<?php echo $typeColor; ?>">
                                <i class="fas fa-<?php echo $typeIcon; ?>"></i>
                            </div>
                            <div class="notification-type text-<?php echo $typeColor; ?>">
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
            </div>
        <?php else: ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>You don't have any notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mark individual notification as read when clicked
            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', function () {
                    const notificationId = this.getAttribute('data-id');
                    markAsRead(notificationId);
                    this.classList.remove('unread');
                });
            });

            // Mark all notifications as read
            document.getElementById('markAllRead')?.addEventListener('click', function () {
                markAsRead(0); // 0 means mark all as read
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
            });

            function markAsRead(notificationId) {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('notification_id', notificationId);

                fetch('notifications.php', {
                    method: 'POST',
                    body: formData
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
</body>

</html>