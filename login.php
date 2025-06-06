<?php
// Clean output buffer before starting
while (ob_get_level() > 0)
    ob_end_clean();


require 'db.php';

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = htmlspecialchars($_COOKIE['remember_me'], ENT_QUOTES, 'UTF-8');
    $stmt = $conn->prepare("SELECT user_id FROM remember_me_tokens WHERE token = ? AND expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['user_id'];
        // Regenerate session ID before any output
        if (headers_sent() === false) {
            session_regenerate_id(true);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Invalid request method");
        }

        $login_id = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) || isset($_POST['rememberMe']);

        if (empty($login_id) || empty($password)) {
            throw new Exception("Email and password are required");
        }

        $is_email = filter_var($login_id, FILTER_VALIDATE_EMAIL);
        $redirect = 'index.php';

        if ($is_email) {
            // Patient login
            $stmt = $conn->prepare("SELECT id, first_name, last_name, password_hash, role, attemptleft FROM patients WHERE email = ?");

            $stmt->bind_param("s", $login_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            if ($result->num_rows !== 1) {
                throw new Exception("Email is not registered");
            } else {
                $user = $result->fetch_assoc();
                $attempt = $user['attemptleft'];

                if ($attempt <= 0) {
                    throw new Exception("You have 0 attempts left. Recover your account using forget password");
                }

                if (!password_verify($password, $user['password_hash'])) {
                    $attempt--;

           
                    $attempt = max($attempt, 0);

        
                    $updateStmt = $conn->prepare("UPDATE patients SET attemptleft = ? WHERE email = ?");
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
       
                    $updateStmt = $conn->prepare("UPDATE patients SET attemptleft = 3 WHERE email = ?");
                    $updateStmt->bind_param("s", $login_id);
                    $updateStmt->execute();
                    $updateStmt->close();
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];

                $redirect = 'index.php';
            }


        } else {

            // Admin login
            $stmt = $conn->prepare("SELECT id, admin_id, type, password, name, attemptleft FROM admin_logins WHERE admin_id = ?");
            $stmt->bind_param("s", $login_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows !== 1) {
                throw new Exception("Invalid email");
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
                $updateStmt = $conn->prepare("UPDATE admin_logins SET attemptleft = 3 WHERE admin_id = ?");
                $updateStmt->bind_param("s", $login_id);
                $updateStmt->execute();
                $updateStmt->close();
            }


            $_SESSION['admin_id'] = $user['admin_id'];
            $_SESSION['admin_type'] = $user['type'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['type'];

            switch ($_SESSION['admin_type']) {
                case 'admin':
                    $redirect = 'admin/dashboard.php';
                    break;
                case 'dentist':
                    $redirect = 'admin/appointments.php';
                    break;
                case 'dental_helper':
                case 'helper':
                    $redirect = 'admin/patient_record.php';
                    break;
                default:
                    $redirect = 'admin/dashboard.php';
                    break;
            }


        }

        // Regenerate session ID before sending any output
        if (headers_sent() === false) {
            session_regenerate_id(true);
        }

        // Handle Remember Me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Remove old tokens
            $stmt = $conn->prepare("DELETE FROM remember_me_tokens WHERE user_id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $stmt->close();
            // Insert new token
            $stmt = $conn->prepare("INSERT INTO remember_me_tokens (user_id, token, expires) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user['id'], $token, $expires);
            $stmt->execute();
            $stmt->close();
            setcookie('remember_me', $token, time() + (86400 * 30), '/', '', true, true);
        }

        echo json_encode([
            "status" => "success",
            "message" => "Login successful!",
            "redirect" => $redirect
        ]);
        exit();

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
        exit();
    }
}

// Non-POST requests show login page
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - ISched of M&A Oida Dental Clinic</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="assets/js/script.js"></script>
</head>

<body>
    <div class="login-container">
        <!-- Back Arrow -->
        <a href="index.php" class="back-arrow">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="login-box">
            <!-- HEADER: logo + title -->
            <div class="login-header">
                <img src="assets/photos/logo-2.png" alt="Clinic Logo" class="login-logo">
                <h2>Login</h2>
            </div>

            <form id="login-form" action="login.php" method="POST">
                <label for="email">Email:</label>
                <input type="text" id="email" name="email" placeholder="Enter your email" required>

                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <span class="toggle-password" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-eye"></i>
                    </span>
                </div>

                <!-- REMEMBER ME -->
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>

                <a href="forgotpassword.php" class="forgot-password">Forgot Password?</a>

                <button type="submit" class="login-btn">Login</button>

                <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
            </form>
        </div>
    </div>
</body>

</html>