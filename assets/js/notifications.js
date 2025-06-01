document.addEventListener('DOMContentLoaded', function() {
    console.log('Notification script loaded');
    const notificationToggle = document.getElementById('notificationBellBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationBadge = document.querySelector('.notification-badge');
    
    console.log('Notification elements:', { notificationToggle, notificationDropdown, notificationBadge });
    
    if (!notificationToggle || !notificationDropdown) {
        console.log('Notification elements not found');
        return;
    }
    
    // Toggle notification dropdown
    notificationToggle.addEventListener('click', function(e) {
        console.log('Notification bell clicked');
        e.preventDefault();
        e.stopPropagation();
        notificationDropdown.classList.toggle('show');
        
        // If opening the dropdown, fetch notifications
        if (notificationDropdown.classList.contains('show')) {
            fetchNotifications();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationDropdown.classList.contains('show') && 
            !notificationDropdown.contains(e.target) && 
            e.target !== notificationToggle) {
            notificationDropdown.classList.remove('show');
        }
    });
    
    // Prevent dropdown from closing when clicking inside it
    notificationDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Function to fetch notifications
    function fetchNotifications() {
        fetch('notifications.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            updateNotificationUI(data.notifications, data.unread_count);
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
    }
    
    // Function to update notification UI
    function updateNotificationUI(notifications, unreadCount) {
        // Update badge count
        if (notificationBadge) {
            if (unreadCount > 0) {
                notificationBadge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                notificationBadge.style.display = 'flex';
            } else {
                notificationBadge.style.display = 'none';
            }
        }
        
        // Clear existing content
        notificationDropdown.innerHTML = '';
        
        // Add header
        const header = document.createElement('div');
        header.className = 'notification-header';
        header.innerHTML = `
            <div class="notification-title">Notifications</div>
            ${unreadCount > 0 ? '<div class="mark-all-read">Mark all as read</div>' : ''}
        `;
        notificationDropdown.appendChild(header);
        
        // Add notifications or empty message
        if (notifications.length === 0) {
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'empty-message';
            emptyMessage.textContent = 'No notifications yet';
            notificationDropdown.appendChild(emptyMessage);
        } else {
            notifications.forEach(notification => {
                const item = document.createElement('div');
                item.className = `notification-item ${notification.is_read == 0 ? 'unread' : ''}`;
                item.setAttribute('data-id', notification.id);
                
                const content = document.createElement('div');
                content.className = 'notification-content';
                content.textContent = notification.message;
                
                const time = document.createElement('div');
                time.className = 'notification-time';
                time.textContent = formatDate(notification.created_at);
                
                item.appendChild(content);
                item.appendChild(time);
                
                // Add click event to mark as read
                item.addEventListener('click', function() {
                    markAsRead(notification.id);
                    this.classList.remove('unread');
                });
                
                notificationDropdown.appendChild(item);
            });
            
            // Add view all link
            const viewAll = document.createElement('div');
            viewAll.className = 'view-all';
            viewAll.innerHTML = '<a href="notifications.php">View all notifications</a>';
            notificationDropdown.appendChild(viewAll);
        }
        
        // Add event listener to mark all as read button
        const markAllReadBtn = notificationDropdown.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                markAsRead(0); // 0 means mark all as read
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
                if (notificationBadge) {
                    notificationBadge.style.display = 'none';
                }
                this.style.display = 'none';
            });
        }
    }
    
    // Function to mark notification as read
    function markAsRead(notificationId) {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('notification_id', notificationId);
        
        fetch('notifications.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
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
    
    // Format date function
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);
        
        if (diffSec < 60) {
            return 'Just now';
        } else if (diffMin < 60) {
            return `${diffMin} minute${diffMin > 1 ? 's' : ''} ago`;
        } else if (diffHour < 24) {
            return `${diffHour} hour${diffHour > 1 ? 's' : ''} ago`;
        } else if (diffDay < 7) {
            return `${diffDay} day${diffDay > 1 ? 's' : ''} ago`;
        } else {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
    
    // Check for new notifications periodically (every 30 seconds)
    setInterval(function() {
        if (!notificationDropdown.classList.contains('show')) {
            // Only fetch if dropdown is closed to avoid disrupting user interaction
            fetch('notifications.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Just update the badge count
                if (notificationBadge && data.unread_count > 0) {
                    notificationBadge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    notificationBadge.style.display = 'flex';
                } else if (notificationBadge) {
                    notificationBadge.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
        }
    }, 30000); // 30 seconds
    
    // Initial fetch to set up notification badge
    fetch('notifications.php', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Just update the badge count initially
        if (notificationBadge && data.unread_count > 0) {
            notificationBadge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
            notificationBadge.style.display = 'flex';
        } else if (notificationBadge) {
            notificationBadge.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error checking notifications:', error);
    });
});
