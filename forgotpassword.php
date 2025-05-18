<?php
require 'db.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

// Initialize or get the current step
if (!isset($_SESSION['password_reset_step'])) {
    $_SESSION['password_reset_step'] = 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Request OTP for email verification
    if (isset($_POST['request_otp']) && $_SESSION['password_reset_step'] == 1) {
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_email'] = $email;
                
                // Generate and send OTP
                $otp = rand(100000, 999999);
                $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                $stmt = $conn->prepare("UPDATE patients SET otp = ?, otp_expires = ? WHERE id = ?");
                $stmt->bind_param("ssi", $otp, $otp_expires, $user['id']);
                
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
                        $mail->Subject = 'Password Reset OTP';
                        $mail->Body = "Your OTP code for password reset is: $otp\n\n".
                                     "This code will expire in 10 minutes.\n\n".
                                     "Best regards,\nM&A Oida Dental Clinic";
                        
                        $mail->send();
                        $_SESSION['password_reset_step'] = 2;
                        $success = "OTP has been sent to your email.";
                    } catch (Exception $e) {
                        $error = "Failed to send OTP email. Please try again.";
                    }
                } else {
                    $error = "Failed to generate OTP.";
                }
            } else {
                $error = "Email not found in our records.";
            }
        }
    }
    // Step 2: Verify OTP
    elseif (isset($_POST['verify_otp']) && $_SESSION['password_reset_step'] == 2) {
        $otp = $_POST['otp'];
        $user_id = $_SESSION['reset_user_id'];
        
        $stmt = $conn->prepare("SELECT otp, otp_expires FROM patients WHERE id = ? AND otp = ?");
        $stmt->bind_param("is", $user_id, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (strtotime($row['otp_expires']) > time()) {
                $_SESSION['password_reset_step'] = 3;
                $success = "OTP verified successfully. Please set your new password.";
            } else {
                $error = "OTP has expired. Please request a new one.";
                $_SESSION['password_reset_step'] = 1;
            }
        } else {
            $error = "Invalid OTP code.";
        }
    }
    // Step 3: Reset Password
    elseif (isset($_POST['reset_password']) && $_SESSION['password_reset_step'] == 3) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $user_id = $_SESSION['reset_user_id'];
        
        if ($new_password === $confirm_password) {
            if (strlen($new_password) < 8) {
                $error = "New password must be at least 8 characters long.";
            } else {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE patients SET password_hash = ?, otp = NULL, otp_expires = NULL WHERE id = ?");
                $stmt->bind_param("si", $new_password_hash, $user_id);
                
                if ($stmt->execute()) {
                    // Clear session variables
                    unset($_SESSION['password_reset_step']);
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_email']);
                    
                    $success = "Password has been reset successfully!";
                    header("Location: login.php?msg=password_reset");
                    exit();
                } else {
                    $error = "Failed to reset password.";
                }
            }
        } else {
            $error = "New passwords do not match.";
        }
    }
    // Go back to previous step
    elseif (isset($_POST['back'])) {
        if ($_SESSION['password_reset_step'] > 1) {
            $_SESSION['password_reset_step']--;
        } else {
            // Go back to login
            header("Location: login.php");
            exit();
        }
    }
    // Cancel the process
    elseif (isset($_POST['cancel'])) {
        unset($_SESSION['password_reset_step']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_email']);
        header("Location: login.php");
        exit();
    }
    // Resend OTP
    elseif (isset($_POST['resend_otp'])) {
        $_SESSION['password_reset_step'] = 1;
        header("Location: forgotpassword.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - M&A Oida Dental Clinic</title>
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
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #E5E7EB;
            z-index: 1;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 2px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6B7280;
            position: relative;
            z-index: 2;
        }
        .step.active {
            border-color: rgb(94, 86, 240);
            color: rgb(94, 86, 240);
        }
        .step.completed {
            background: rgb(94, 86, 240);
            border-color: rgb(94, 86, 240);
            color: white;
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
            width: 100%;
            margin-bottom: 0.5rem;
        }
        .btn-primary {
            background: rgb(94, 86, 240);
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
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Forgot Password</h2>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo $_SESSION['password_reset_step'] >= 1 ? 'active' : ''; ?> <?php echo $_SESSION['password_reset_step'] > 1 ? 'completed' : ''; ?>">1</div>
            <div class="step <?php echo $_SESSION['password_reset_step'] >= 2 ? 'active' : ''; ?> <?php echo $_SESSION['password_reset_step'] > 2 ? 'completed' : ''; ?>">2</div>
            <div class="step <?php echo $_SESSION['password_reset_step'] >= 3 ? 'active' : ''; ?>">3</div>
        </div>
        
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

        <?php if ($_SESSION['password_reset_step'] == 1): ?>
            <!-- Step 1: Request OTP -->
            <form method="POST" action="">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your registered email address">
                </div>
                <button type="submit" name="request_otp" class="btn btn-primary">
                    Send Reset Code
                </button>
                <button type="submit" name="cancel" class="btn btn-secondary">
                    Cancel
                </button>
            </form>
        <?php elseif ($_SESSION['password_reset_step'] == 2): ?>
            <!-- Step 2: Verify OTP -->
            <form method="POST" action="">
                <div class="input-group">
                    <label for="otp">Enter OTP Code</label>
                    <input type="text" id="otp" name="otp" required 
                           placeholder="Enter the 6-digit code sent to your email"
                           pattern="[0-9]{6}" maxlength="6">
                </div>
                <button type="submit" name="verify_otp" class="btn btn-primary">
                    Verify OTP
                </button>
                <button type="submit" name="resend_otp" class="btn btn-secondary">
                    Resend OTP
                </button>
                <button type="submit" name="back" class="btn btn-secondary">
                    Back
                </button>
            </form>
        <?php elseif ($_SESSION['password_reset_step'] == 3): ?>
            <!-- Step 3: Reset Password -->
            <form method="POST" action="">
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <div class="password-field">
                        <input type="password" id="new_password" name="new_password" required
                               placeholder="Enter new password">
                    </div>
                    <div class="password-requirements">
                        Password must be at least 8 characters long
                    </div>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               placeholder="Confirm new password">
                    </div>
                </div>
                <button type="submit" name="reset_password" class="btn btn-primary">
                    Reset Password
                </button>
                <button type="submit" name="back" class="btn btn-secondary">
                    Back
                </button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Login
        </a>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
        }
    </script>
</body>
</html> 