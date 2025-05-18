<?php
// Only start session if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Load admin data including profile photo if logged in
function load_admin_data($conn) {
    if (isset($_SESSION['admin_id'])) {
        $admin_id = $_SESSION['admin_id'];
        $sql = "SELECT * FROM admin_logins WHERE admin_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['profile_photo'] = $row['profile_photo'];
        }
        $stmt->close();
    }

    // If no profile photo is set, use default
    if (empty($_SESSION['profile_photo'])) {
        $_SESSION['profile_photo'] = 'assets/photo/default_avatar.png';
    }
}
