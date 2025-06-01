<?php
require_once 'db.php';
require_once 'mailfunction.php';

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

    $subject = 'Resend OTP Code';
    $message = "Your new OTP code is: $otp\n\nThis code will expire in 10 minutes at: $otp_expires\n\nBest regards,\nM&A Oida Dental Clinic";

    // Try sending the email
    $emailSent = phpmailsend($email, $email, $subject, $message);

    if ($emailSent) {
        echo json_encode([
            "status" => "success",
            "message" => "OTP resend successful. Please check your email for the OTP code."
        ]);
    } else {
        throw new Exception("Failed to send verification email. Please try again later.");
    }


} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>