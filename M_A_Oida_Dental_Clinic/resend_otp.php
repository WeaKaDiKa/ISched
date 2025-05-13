<?php
require 'db.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        throw new Exception('Email is required');
    }

    // Get pending registration
    $stmt = $conn->prepare("SELECT first_name FROM pending_patients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception('No pending registration found for this email');
    }

    $patient = $result->fetch_assoc();

    // Generate new OTP
    $otp = rand(100000, 999999);
    $otp_expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Update OTP in database
    $stmt = $conn->prepare("UPDATE pending_patients SET otp = ?, otp_expires = ? WHERE email = ?");
    $stmt->bind_param("sss", $otp, $otp_expires, $email);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update OTP');
    }

    // Send new OTP email
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
        $mail->Subject = 'Your New OTP Code';
        $mail->Body = "Dear {$patient['first_name']},\n\nYour new OTP code is: $otp\n\nThis code will expire in 10 minutes.\n\nBest regards,\nM&A Oida Dental Clinic";
        
        $mail->send();
        echo json_encode([
            'status' => 'success',
            'message' => 'New OTP sent successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to send new OTP email. Please try again later.');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>