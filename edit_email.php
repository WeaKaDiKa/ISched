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
$current_email = $user['email'];

// Define the steps in the email change process
// 1. Request OTP for current email verification
// 2. Verify current email with OTP
// 3. Enter new email and request OTP for new email
// 4. Verify new email with OTP

// Initialize or get the current step
if (!isset($_SESSION['email_change_step'])) {
    $_SESSION['email_change_step'] = 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Generate and send OTP to current email
    if (isset($_POST['request_current_otp']) && $_SESSION['email_change_step'] == 1) {
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
                $mail->addAddress($current_email);
                $mail->Subject = 'Email Change Verification';
                $mail->Body = "Your OTP code to verify your current email is: $otp\n\n".
                              "This code will expire in 10 minutes.\n\n".
                              "Best regards,\nM&A Oida Dental Clinic";
                
                $mail->send();
                $_SESSION['email_change_step'] = 2;
            } catch (Exception $e) {
                $error = "Failed to send OTP email. Please try again.";
            }
        } else {
            $error = "Failed to generate OTP.";
        }
    }
    // Step 2: Verify OTP for current email
    elseif (isset($_POST['verify_current_otp']) && $_SESSION['email_change_step'] == 2) {
        $otp = $_POST['otp'];
        $stmt = $conn->prepare("SELECT otp, otp_expires FROM patients WHERE id = ? AND otp = ?");
        $stmt->bind_param("is", $user_id, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (strtotime($row['otp_expires']) > time()) {
                $_SESSION['email_change_step'] = 3;
            } else {
                $error = "OTP has expired. Please request a new one.";
                $_SESSION['email_change_step'] = 1;
            }
        } else {
            $error = "Invalid OTP code.";
        }
    }
    // Step 3: Enter new email and send OTP
    elseif (isset($_POST['request_new_otp']) && $_SESSION['email_change_step'] == 3) {
        $new_email = filter_var($_POST['new_email'], FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if email already exists for another user
            $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $new_email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "This email is already registered with another account.";
            } else {
                // Store new email in session for later use
                $_SESSION['new_email'] = $new_email;
                
                // Generate OTP for new email verification
                $otp = rand(100000, 999999);
                $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                $stmt = $conn->prepare("UPDATE patients SET otp = ?, otp_expires = ? WHERE id = ?");
                $stmt->bind_param("ssi", $otp, $otp_expires, $user_id);
                
                if ($stmt->execute()) {
                    // Send OTP to new email
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
                        $mail->addAddress($new_email);
                        $mail->Subject = 'New Email Verification';
                        $mail->Body = "Your OTP code to verify your new email is: $otp\n\n".
                                      "This code will expire in 10 minutes.\n\n".
                                      "Best regards,\nM&A Oida Dental Clinic";
                        
                        $mail->send();
                        $_SESSION['email_change_step'] = 4;
                    } catch (Exception $e) {
                        $error = "Failed to send OTP email. Please try again.";
                    }
                } else {
                    $error = "Failed to generate OTP.";
                }
            }
        }
    }
    // Step 4: Verify OTP for new email and complete the change
    elseif (isset($_POST['verify_new_otp']) && $_SESSION['email_change_step'] == 4) {
        $otp = $_POST['otp'];
        $stmt = $conn->prepare("SELECT otp, otp_expires FROM patients WHERE id = ? AND otp = ?");
        $stmt->bind_param("is", $user_id, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (strtotime($row['otp_expires']) > time()) {
                // Update email
                $new_email = $_SESSION['new_email'];
                $stmt = $conn->prepare("UPDATE patients SET email = ? WHERE id = ?");
                $stmt->bind_param("si", $new_email, $user_id);
                
                if ($stmt->execute()) {
                    // Clear session variables
                    unset($_SESSION['email_change_step']);
                    unset($_SESSION['new_email']);
                    
                    $success = "Email changed successfully!";
                    header("Location: profile.php?msg=email_changed");
                    exit();
                } else {
                    $error = "Failed to update email.";
                }
            } else {
                $error = "OTP has expired. Please start over.";
                $_SESSION['email_change_step'] = 1;
            }
        } else {
            $error = "Invalid OTP code.";
        }
    }
    // Go back to previous step
    elseif (isset($_POST['back'])) {
        if ($_SESSION['email_change_step'] > 1) {
            $_SESSION['email_change_step']--;
        } else {
            // Go back to profile
            header("Location: profile.php");
            exit();
        }
    }
    // Cancel the process
    elseif (isset($_POST['cancel'])) {
        unset($_SESSION['email_change_step']);
        unset($_SESSION['new_email']);
        header("Location: profile.php");
        exit();
    }
    // Resend OTP
    elseif (isset($_POST['resend_otp'])) {
        // Keep the current step but resend OTP
        if ($_SESSION['email_change_step'] == 2) {
            // Resend to current email
            header("Location: edit_email.php");
            $_SESSION['email_change_step'] = 1;
        } elseif ($_SESSION['email_change_step'] == 4) {
            // Resend to new email
            header("Location: edit_email.php");
            $_SESSION['email_change_step'] = 3;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Email - M&A Oida Dental Clinic</title>
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
            background: #e5e7eb;
            z-index: 1;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        .step.active {
            background: #4F46E5;
            color: white;
        }
        .step.completed {
            background: #10B981;
            color: white;
        }
        .step-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6B7280;
            white-space: nowrap;
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
        .input-group input[readonly] {
            background-color: #F3F4F6;
            cursor: not-allowed;
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
            background: #4F46E5;
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
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        .btn-danger:hover {
            background: #DC2626;
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Change Email Address</h2>
        
        <div class="step-indicator">
            <div class="step <?php echo $_SESSION['email_change_step'] >= 1 ? 'active' : ''; ?>">
                1
                <span class="step-label">Verify Current</span>
            </div>
            <div class="step <?php echo $_SESSION['email_change_step'] >= 2 ? 'active' : ''; ?>">
                2
                <span class="step-label">Confirm</span>
            </div>
            <div class="step <?php echo $_SESSION['email_change_step'] >= 3 ? 'active' : ''; ?>">
                3
                <span class="step-label">New Email</span>
            </div>
            <div class="step <?php echo $_SESSION['email_change_step'] >= 4 ? 'active' : ''; ?>">
                4
                <span class="step-label">Verify New</span>
            </div>
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
        
        <?php if ($_SESSION['email_change_step'] == 1): ?>
            <!-- Step 1: Send OTP to current email -->
            <div class="text-center mb-6">
                <p class="text-gray-600">To change your email, we need to verify your current email address first.</p>
            </div>
            <div class="input-group">
                <label>Current Email:</label>
                <input type="email" value="<?php echo htmlspecialchars($current_email); ?>" readonly>
            </div>
            <form method="POST" class="space-y-4">
                <button type="submit" name="request_current_otp" class="btn btn-primary w-full">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Send Verification Code
                </button>
                <div class="flex justify-between">
                    <button type="submit" name="cancel" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                </div>
            </form>
            
        <?php elseif ($_SESSION['email_change_step'] == 2): ?>
            <!-- Step 2: Verify current email OTP -->
            <div class="text-center mb-6">
                <p class="text-gray-600">A verification code has been sent to your current email address.</p>
                <p class="text-sm text-gray-500 mt-2">Please check your inbox and enter the code below:</p>
            </div>
            <form method="POST" class="space-y-4">
                <div class="input-group">
                    <label for="otp">Verification Code:</label>
                    <input type="text" id="otp" name="otp" required maxlength="6" pattern="[0-9]{6}" 
                           class="otp-input" placeholder="Enter 6-digit code">
                </div>
                <button type="submit" name="verify_current_otp" class="btn btn-primary w-full">
                    <i class="fas fa-check mr-2"></i>
                    Verify Code
                </button>
                <div class="flex justify-between">
                    <button type="submit" name="back" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back
                    </button>
                    <button type="submit" name="resend_otp" class="btn btn-secondary">
                        <i class="fas fa-redo mr-2"></i>
                        Resend Code
                    </button>
                </div>
            </form>
            
        <?php elseif ($_SESSION['email_change_step'] == 3): ?>
            <!-- Step 3: Enter new email -->
            <div class="text-center mb-6">
                <p class="text-gray-600">Current email verified successfully. Please enter your new email address:</p>
            </div>
            <form method="POST" class="space-y-4">
                <div class="input-group">
                    <label for="current_email">Current Email:</label>
                    <input type="email" id="current_email" value="<?php echo htmlspecialchars($current_email); ?>" readonly>
                </div>
                <div class="input-group">
                    <label for="new_email">New Email:</label>
                    <input type="email" id="new_email" name="new_email" required 
                           value="<?php echo isset($_SESSION['new_email']) ? htmlspecialchars($_SESSION['new_email']) : ''; ?>"
                           placeholder="Enter your new email address">
                </div>
                <button type="submit" name="request_new_otp" class="btn btn-primary w-full">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Send Verification Code
                </button>
                <div class="flex justify-between">
                    <button type="submit" name="back" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back
                    </button>
                    <button type="submit" name="cancel" class="btn btn-danger">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </button>
                </div>
            </form>
            
        <?php elseif ($_SESSION['email_change_step'] == 4): ?>
            <!-- Step 4: Verify new email OTP -->
            <div class="text-center mb-6">
                <p class="text-gray-600">A verification code has been sent to your new email address.</p>
                <p class="text-sm text-gray-500 mt-2">Please check your inbox and enter the code below:</p>
            </div>
            <div class="input-group">
                <label>New Email:</label>
                <input type="email" value="<?php echo htmlspecialchars($_SESSION['new_email']); ?>" readonly>
            </div>
            <form method="POST" class="space-y-4">
                <div class="input-group">
                    <label for="otp">Verification Code:</label>
                    <input type="text" id="otp" name="otp" required maxlength="6" pattern="[0-9]{6}" 
                           class="otp-input" placeholder="Enter 6-digit code">
                </div>
                <button type="submit" name="verify_new_otp" class="btn btn-primary w-full">
                    <i class="fas fa-check mr-2"></i>
                    Complete Email Change
                </button>
                <div class="flex justify-between">
                    <button type="submit" name="back" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back
                    </button>
                    <button type="submit" name="resend_otp" class="btn btn-secondary">
                        <i class="fas fa-redo mr-2"></i>
                        Resend Code
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if (!isset($_SESSION['email_change_step']) || $_SESSION['email_change_step'] == 1): ?>
            <a href="profile.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Profile
            </a>
        <?php endif; ?>
    </div>
</body>
</html>