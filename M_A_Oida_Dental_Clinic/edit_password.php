<?php
require_once('session.php');
require 'db.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user email
$stmt = $conn->prepare("SELECT email FROM patients WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $otp = $_POST['otp'];
        $stmt = $conn->prepare("SELECT otp, otp_expires FROM patients WHERE id = ? AND otp = ?");
        $stmt->bind_param("is", $user_id, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (strtotime($row['otp_expires']) > time()) {
                $_SESSION['password_verified'] = true;
                header("Location: edit_password.php");
                exit();
            } else {
                $error = "OTP has expired. Please request a new one.";
            }
        } else {
            $error = "Invalid OTP code.";
        }
    } elseif (isset($_POST['change_password']) && isset($_SESSION['password_verified'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM patients WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (password_verify($current_password, $user['password_hash'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) < 8) {
                    $error = "New password must be at least 8 characters long.";
                } else {
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE patients SET password_hash = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_password_hash, $user_id);
                    
                    if ($stmt->execute()) {
                        unset($_SESSION['password_verified']);
                        $success = "Password changed successfully!";
                        header("Location: profile.php?msg=password_changed");
                        exit();
                    } else {
                        $error = "Failed to update password.";
                    }
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } else {
        // Generate and send OTP
        $otp = rand(100000, 999999);
        $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $conn->prepare("UPDATE patients SET otp = ?, otp_expires = ? WHERE id = ?");
        $stmt->bind_param("ssi", $otp, $otp_expires, $user_id);
        
        if ($stmt->execute()) {
            // Send OTP email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'oidaclinic1@gmail.com';
                $mail->Password = 'lkys fezt vzam bzof';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('oidaclinic1@gmail.com', 'M&A Oida Dental Clinic');
                $mail->addAddress($email);
                $mail->Subject = 'Password Change OTP';
                $mail->Body = "Your OTP code for password change is: $otp\n\nThis code will expire in 10 minutes.\n\nBest regards,\nM&A Oida Dental Clinic";
                
                $mail->send();
            } catch (Exception $e) {
                $error = "Failed to send OTP email. Please try again.";
            }
        } else {
            $error = "Failed to generate OTP.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - M&A Oida Dental Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #8fbaf3;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-container {
            max-width: 500px;
            width: 90%;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .input-group {
            margin-bottom: 1.5rem;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
        }
        .input-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out;
        }
        .input-group input:focus {
            outline: none;
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
        }
        .btn-primary {
            background:rgb(94, 86, 240);
            color: white;
        }
        .btn-primary:hover {
            background: #4338CA;
        }
        .btn-secondary {
            background: #E5E7EB;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #D1D5DB;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        .otp-input {
            letter-spacing: 0.5rem;
            font-size: 1.25rem;
            text-align: center;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #6B7280;
            text-decoration: none;
            margin-top: 1rem;
        }
        .back-link:hover {
            color: #374151;
        }
        .back-link i {
            margin-right: 0.5rem;
        }
        .password-requirements {
            font-size: 0.875rem;
            color: #6B7280;
            margin-top: 0.5rem;
        }
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6B7280;
        }
        .password-field {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Change Password</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!isset($_SESSION['password_verified'])): ?>
            <!-- OTP Verification Form -->
            <div class="text-center mb-6">
                <p class="text-gray-600">For security reasons, please verify your email address first.</p>
                <p class="text-sm text-gray-500 mt-2">A verification code has been sent to:</p>
                <p class="font-medium text-gray-900 mt-1"><?php echo htmlspecialchars($email); ?></p>
            </div>
            
            <form method="POST" class="space-y-4">
                <div class="input-group">
                    <label for="otp">Verification Code:</label>
                    <input type="text" id="otp" name="otp" required maxlength="6" pattern="[0-9]{6}" 
                           class="otp-input" placeholder="Enter 6-digit code">
                </div>
                <button type="submit" name="verify_otp" class="btn btn-primary w-full">
                    <i class="fas fa-check mr-2"></i>
                    Verify Code
                </button>
                <form method="POST" class="mt-4">
                    <button type="submit" class="btn btn-secondary w-full">
                        <i class="fas fa-redo mr-2"></i>
                        Resend Code
                    </button>
                </form>
            </form>
        <?php else: ?>
            <!-- Password Change Form -->
            <form method="POST" class="space-y-4">
                <div class="input-group password-field">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password')"></i>
                </div>
                
                <div class="input-group password-field">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                    <div class="password-requirements">
                        Password must be at least 8 characters long
                    </div>
                </div>
                
                <div class="input-group password-field">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary w-full">
                    <i class="fas fa-key mr-2"></i>
                    Change Password
                </button>
            </form>
        <?php endif; ?>
        
        <a href="profile.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Profile
        </a>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>