<?php
/**
 * Email functions for sending notifications to patients using PHPMailer
 */

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Load PHPMailer autoloader if not already loaded
require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';

// Configuration - Set to true to enable email sending
define('ENABLE_EMAIL_NOTIFICATIONS', true);

/**
 * Send an email notification to a patient using PHPMailer
 * 
 * @param string $to_email The recipient's email address
 * @param string $subject The email subject
 * @param string $message The email message body
 * @return bool True if email was sent successfully, false otherwise
 */
function send_email($to_email, $subject, $message) {
    // Check if email notifications are enabled
    if (!ENABLE_EMAIL_NOTIFICATIONS) {
        // Return true to prevent errors in the calling code
        return true;
    }
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();                                      // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                // SMTP server
        $mail->SMTPAuth   = true;                            // Enable SMTP authentication
        $mail->Username   = 'clinicoidadental@gmail.com';    // SMTP username
        $mail->Password   = 'oidadental2024';                // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
        $mail->Port       = 587;                             // TCP port to connect to
        
        // For debugging
        $mail->SMTPDebug = 0;                               // Enable verbose debug output (0 = off, 1 = client, 2 = client and server)
        
        // Sender
        $mail->setFrom('clinicoidadental@gmail.com', 'M&A Oida Dental Clinic');
        $mail->addReplyTo('clinicoidadental@gmail.com', 'M&A Oida Dental Clinic');
        
        // Recipients
        $mail->addAddress($to_email);                        // Add a recipient
        
        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $message)); // Plain text version
        
        // Send the email
        return $mail->send();
    } catch (Exception $e) {
        // Log the error
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}
/**
 * Send appointment approval notification
 * 
 * @param string $to_email The patient's email address
 * @param string $patient_name The patient's name
 * @param string $appointment_date The appointment date (formatted)
 * @param string $appointment_time The appointment time
 * @return bool True if email was sent successfully, false otherwise
 */
function send_appointment_approval_email($to_email, $patient_name, $appointment_date, $appointment_time) {
    $subject = "Appointment Approved - M&A Oida Dental Clinic";
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #3b82f6;
                color: white;
                padding: 15px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #777;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>M&A Oida Dental Clinic</h2>
            </div>
            <div class="content">
                <h3>Appointment Approved</h3>
                <p>Dear ' . htmlspecialchars($patient_name) . ',</p>
                <p>We are pleased to inform you that your appointment has been approved.</p>
                <p><strong>Appointment Details:</strong></p>
                <ul>
                    <li><strong>Date:</strong> ' . htmlspecialchars($appointment_date) . '</li>
                    <li><strong>Time:</strong> ' . htmlspecialchars($appointment_time) . '</li>
                </ul>
                <p>Please arrive 15 minutes before your scheduled appointment time.</p>
                <p>If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance.</p>
                <p>Thank you for choosing M&A Oida Dental Clinic for your dental care needs.</p>
                <p>If you have any questions, please contact us at:</p>
                <p>Phone: 0918 578 2346</p>
                <p>Email: clinicoidadental@gmail.com</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' M&A Oida Dental Clinic. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return send_email($to_email, $subject, $message);
}

/**
 * Send appointment cancellation notification
 * 
 * @param string $to_email The patient's email address
 * @param string $patient_name The patient's name
 * @param string $appointment_date The appointment date (formatted)
 * @param string $appointment_time The appointment time
 * @param string $reason The reason for cancellation
 * @return bool True if email was sent successfully, false otherwise
 */
function send_appointment_cancellation_email($to_email, $patient_name, $appointment_date, $appointment_time, $reason) {
    $subject = "Appointment Cancelled - M&A Oida Dental Clinic";
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #3b82f6;
                color: white;
                padding: 15px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #777;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>M&A Oida Dental Clinic</h2>
            </div>
            <div class="content">
                <h3>Appointment Cancelled</h3>
                <p>Dear ' . htmlspecialchars($patient_name) . ',</p>
                <p>We regret to inform you that your appointment has been cancelled.</p>
                <p><strong>Appointment Details:</strong></p>
                <ul>
                    <li><strong>Date:</strong> ' . htmlspecialchars($appointment_date) . '</li>
                    <li><strong>Time:</strong> ' . htmlspecialchars($appointment_time) . '</li>
                </ul>
                <p><strong>Reason for cancellation:</strong> ' . htmlspecialchars($reason) . '</p>
                <p>If you would like to reschedule your appointment, please visit our website or contact us directly.</p>
                <p>We apologize for any inconvenience this may have caused.</p>
                <p>Thank you for your understanding.</p>
                <p>If you have any questions, please contact us at:</p>
                <p>Phone: 0918 578 2346</p>
                <p>Email: clinicoidadental@gmail.com</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' M&A Oida Dental Clinic. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return send_email($to_email, $subject, $message);
}
?>
