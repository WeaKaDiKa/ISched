<?php
session_start();
require 'db.php';

$error = '';

// Check for remember me cookie
if (isset($_COOKIE['admin_token'])) {
    $token = $_COOKIE['admin_token'];
    $sql = "SELECT t.admin_id, a.name 
            FROM remember_me_tokens t 
            JOIN admin_logins a ON t.admin_id = a.id 
            WHERE t.token = ? AND t.expiry > NOW()";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['admin_id'] = $user['admin_id'];
            header('Location: dashboard.php');
            exit;
        }
        $stmt->close();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['admin_id']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['rememberMe']) ? true : false;

    try {
        // Check login attempts and verify password
        $stmt = $conn->prepare("SELECT id, admin_id, type, password, name, attemptleft, profile_photo FROM admin_logins WHERE admin_id = ?");
        $stmt->bind_param("s", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            throw new Exception("Account not found");
        }

        $user = $result->fetch_assoc();
        $attempt = $user['attemptleft'];
        if ($attempt <= 0) {
            throw new Exception("You have 0 attempts left. Recover your account using forget password");
        }

        if (!password_verify($password, $user['password'])) {
            $attempt--;
            $attempt = max($attempt, 0);

            $updateStmt = $conn->prepare("UPDATE admin_logins SET attemptleft = ? WHERE admin_id = ?");
            $updateStmt->bind_param("is", $attempt, $login_id);
            $updateStmt->execute();
            $updateStmt->close();

            if ($attempt === 0) {
                throw new Exception("Invalid password. You have 0 attempts left. Recover your account using forget password");
            } elseif ($attempt === 1) {
                throw new Exception("Invalid password. You have 1 attempt left");
            } else {
                throw new Exception("Invalid password. You have $attempt attempts left");
            }
        } else {
            // Reset attempts on successful login
            $updateStmt = $conn->prepare("UPDATE admin_logins SET attemptleft = 3 WHERE admin_id = ?");
            $updateStmt->bind_param("s", $login_id);
            $updateStmt->execute();
            $updateStmt->close();
        }

        // Set default profile if not set
        if (empty($user['name'])) {
            $default_name = 'Admin User';
            $sql = "UPDATE admin_logins SET 
                    first_name = 'Admin', 
                    last_name = 'User',
                    name = ?,
                    age = 25,
                    mobile = '+639123456789',
                    email = 'admin@example.com',
                    gender = 'Other',
                    profile_photo = 'assets/photo/default_avatar.png'
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('si', $default_name, $user['id']);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Set session variables
        $_SESSION['admin_id'] = $user['admin_id'];
        $_SESSION['admin_type'] = $user['type'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['type'];
        $_SESSION['profile_photo'] = !empty($user['profile_photo']) ? $user['profile_photo'] : 'assets/photo/default_avatar.png';

        // Handle Remember Me functionality
        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (86400 * 30); // 30 days

            // Store token in database
            $sql = "INSERT INTO remember_tokens (admin_id, token, expiry) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE token = ?, expiry = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sssss', $user['id'], $token, $expiry, $token, $expiry);
                $stmt->execute();
                $stmt->close();
            }

            // Set cookie
            setcookie('admin_token', $token, $expiry, '/', '', false, true);
        }

        // Redirect based on user type
        switch ($_SESSION['admin_type']) {
            case 'admin':
                $redirect = 'dashboard.php';
                break;
            case 'dentist':
                $redirect = 'appointments.php';
                break;
            case 'dental_helper':
            case 'helper':
                $redirect = 'patient_record.php';
                break;
            default:
                $redirect = 'dashboard.php';
                break;
        }

        header("Location: $redirect");
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-blue-100 min-h-screen flex items-center justify-center">
    <div class="flex flex-col md:flex-row w-full min-h-screen items-center justify-center">
        <!-- Left Illustration -->
        <div class="relative flex justify-center items-center w-full md:w-1/2 p-8">
            <div
                class="w-full max-w-xl mx-auto bg-white bg-opacity-40 rounded-2xl shadow-lg p-10 flex items-center justify-center">
                <img src="assets/photo/dentist_patient.png" alt="Hospital Illustration"
                    class="w-full h-auto select-none pointer-events-none">
            </div>
        </div>
        <!-- Right Login Card -->
        <div class="flex justify-center items-center w-full md:w-1/2 p-8">
            <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl p-10 border border-blue-300">
                <div class="flex items-center mb-6">
                    <img src="assets/photo/logo.jpg" alt="Logo"
                        class="w-12 h-12 rounded-full mr-3 border-2 border-blue-200">
                    <span class="text-2xl font-bold text-gray-900 tracking-tight">M&A Oida Dental Clinic</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Login</h2>
                <p class="text-gray-500 mb-6">Sign in to your admin account</p>
                <?php if ($error): ?>
                    <p class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm text-center">
                        <?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form action="admin_login.php" method="POST" class="space-y-6">
                    <div class="relative">
                        <input type="text" name="admin_id" id="admin_id"
                            value="<?= htmlspecialchars($_POST['admin_id'] ?? '') ?>" required
                            class="peer block w-full appearance-none border border-gray-300 rounded-lg bg-gray-50 px-4 pt-6 pb-2 pl-12 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder=" " />
                        <label for="admin_id"
                            class="absolute left-12 top-2 text-gray-500 text-sm transition-all duration-200 peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-2 peer-focus:text-sm peer-focus:text-blue-600">Admin
                            ID</label>
                        <span
                            class="absolute left-4 top-0 flex items-center h-full text-gray-400 pointer-events-none"><i
                                class="fa-solid fa-id-card"></i></span>
                    </div>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                            class="peer block w-full appearance-none border border-gray-300 rounded-lg bg-gray-50 px-4 pt-6 pb-2 pl-12 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            placeholder=" " />
                        <label for="password"
                            class="absolute left-12 top-2 text-gray-500 text-sm transition-all duration-200 peer-placeholder-shown:top-4 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:top-2 peer-focus:text-sm peer-focus:text-blue-600">Password</label>
                        <span
                            class="absolute left-4 top-0 flex items-center h-full text-gray-400 pointer-events-none"><i
                                class="fa-solid fa-lock"></i></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center">
                            <input type="checkbox" name="rememberMe" id="rememberMe" class="mr-2">
                            <span class="text-gray-700">Remember me</span>
                        </label>
                        <a href="../forgotpassword.php" class="text-blue-600 hover:underline">Forgot password?</a>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg shadow-sm transition">Login</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const loginButton = document.querySelector(".login-button, button[type='submit']");
            if (loginButton) {
                loginButton.addEventListener("click", (e) => {
                    // Basic front-end validation
                    const adminId = document.querySelector("#admin_id").value.trim();
                    const password = document.querySelector("#password").value.trim();
                    if (!adminId || !password) {
                        e.preventDefault();
                        alert("All fields are required!");
                        return;
                    }
                });
            }
        });
    </script>
</body>

</html>