<?php
require_once('session.php');
require_once('db.php');
require_once('includes/profile_functions.php');
// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('location: login.php');
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

    if ($notificationId > 0) {

        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notificationId, $userId);
    } else {

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
    <?php require_once 'includes/head.php' ?>

    <style>
        .bg-purple {
            background-color: #9c4adc;
        }

        .bg-purple-subtle {
            background-color: #f2e6ff;
        }

        .text-purple {
            color: #9c4adc;
        }

        .border-purple {
            border-color: #9c4adc !important;
        }


        .notification-item.unread {
            background-color: rgba(74, 137, 220, 0.05)!important;
        }
    </style>
</head>

<body>
    <header>
        <?php include_once('includes/navbar.php'); ?>
    </header>

    <div class="container mt-4">
        <div class="d-flex justify-content-between">
            <h1>Notifications</h1>
            <?php if (!empty($notifications)): ?>
                <button class="btn btn-primary mb-3" id="markAllRead">
                    <i class="fas fa-check-double me-2"></i>Mark All as Read
                </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($notifications)): ?>


            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $typeColor = 'blue';
                    $typeIcon = 'bell';
                    $typeName = $notification['type'];
                    $bsColorClass = 'primary'; 
            
                    switch ($notification['type']) {
                        case 'appointment':
                            $typeColor = 'appointment';
                            $typeIcon = 'calendar-check';
                            $bsColorClass = 'primary';
                            break;
                        case 'cancellation':
                            $typeColor = 'cancellation';
                            $typeIcon = 'calendar-times';
                            $bsColorClass = 'danger';
                            break;
                        case 'reschedule':
                            $typeColor = 'reschedule';
                            $typeIcon = 'calendar-day';
                            $bsColorClass = 'warning';
                            break;
                        case 'review':
                            $typeColor = 'review';
                            $typeIcon = 'star';
                            $bsColorClass = 'info';
                            break;
                        case 'message':
                            $typeColor = 'message';
                            $typeIcon = 'comment';
                            $bsColorClass = 'purple';
                            break;
                        case 'reminder':
                            $typeColor = 'reminder';
                            $typeIcon = 'clock';
                            $bsColorClass = 'success';
                            break;
                    }
                    ?>

                    <div class="notification-item list-group-item list-group-item-action border-start-4 border-<?php echo $bsColorClass; ?> <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
                        data-id="<?php echo $notification['id']; ?>">

                        <!-- Notification Header -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <span
                                    class="badge bg-<?php echo $bsColorClass; ?>-subtle text-<?php echo $bsColorClass; ?> me-2">
                                    <i class="fas fa-<?php echo $typeIcon; ?> me-1"></i>
                                    <?php echo ucfirst(htmlspecialchars(str_replace('_', ' ', $typeName))); ?>
                                </span>
                            </div>
                            <small
                                class="text-muted"><?php echo date('M d, Y', strtotime($notification['created_at'])); ?></small>
                        </div>

                        <!-- Notification Content -->
                        <div class="d-flex">

                            <?php if (!empty($notification['sender_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($notification['sender_photo']); ?>" alt="Sender"
                                    class="sender-photo rounded-circle me-3">
                            <?php else: ?>
                                <div class="sender-initial rounded-circle me-3 bg-<?php echo $bsColorClass; ?> text-white d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <?php echo !empty($notification['sender_name']) ? strtoupper(substr($notification['sender_name'], 0, 1)) : 'C'; ?>
                                </div>
                            <?php endif; ?>


                            <!-- Message Content -->
                            <div class="message-bubble flex-grow-1 p-3 rounded">
                                <?php if (!empty($notification['sender_name'])): ?>
                                    <div class="sender-name fw-bold mb-2">
                                        <?php echo htmlspecialchars($notification['sender_name']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="message-text mb-2"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <div class="message-time text-muted text-end">
                                    <?php echo date('h:i A', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-notifications text-center py-5">
                <i class="fas fa-bell-slash text-muted mb-3" style="font-size: 3rem;"></i>
                <p class="text-muted">You don't have any notifications yet.</p>
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
                markAsRead(0);
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
            });

            function markAsRead(notificationId) {
                console.log('markallpresed');
                const btn = notificationId === 0 ? document.getElementById('markAllRead') : null;
                if (btn) btn.disabled = true;
                console.log(notificationId);
                const formData = new FormData();
                formData.append('notification_id', notificationId);

                fetch('notifications.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.log('Failed to mark notification as read');

                        }
                        if (data.success) {

                            const unreadCountEl = document.querySelector('.unread-count');
                            if (unreadCountEl) {
                                const current = parseInt(unreadCountEl.textContent) || 0;
                                const newCount = notificationId === 0 ? 0 : Math.max(0, current - 1);
                                unreadCountEl.textContent = newCount > 0 ? newCount : '';
                                unreadCountEl.classList.toggle('d-none', newCount === 0);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    })
                    .finally(() => {
                        if (btn) btn.disabled = false;
                    });
            }
        });
    </script>
</body>

</html>