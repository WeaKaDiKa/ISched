<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'admin/vendor/phpmailer/src/Exception.php';
require 'admin/vendor/phpmailer/src/PHPMailer.php';
require 'admin/vendor/phpmailer/src/SMTP.php';
function phpmailsend($email, $name, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'clinicoidadental@gmail.com';
        $mail->Password = 'zufxwtvbjjxvhblg';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('clinicoidadental@gmail.com', 'Oida Dental ISched');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "
            $subject

            $message
        ";

        $mail->send();

        return true;
    } catch (Exception $e) {

        return false;
    }
}
