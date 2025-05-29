<?php
require_once('email_functions.php');

// Set up test parameters
$recipient_email = 'clinicoidadental@gmail.com'; // Replace with your test email
$patient_name = 'Test Patient';
$appointment_date = 'May 30, 2025';
$appointment_time = '10:00 AM';

// Test sending an approval email
$result = send_appointment_approval_email(
    $recipient_email,
    $patient_name,
    $appointment_date,
    $appointment_time
);

// Output the result
if ($result) {
    echo "<h2>Success!</h2>";
    echo "<p>Test email was sent successfully to {$recipient_email}.</p>";
    echo "<p>Please check your inbox (and spam folder) to verify the email was received.</p>";
} else {
    echo "<h2>Error!</h2>";
    echo "<p>Failed to send test email. Please check the error log for more details.</p>";
}
?>
