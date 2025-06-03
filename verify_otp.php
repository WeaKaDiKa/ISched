<?php

ob_start(); // Start output buffering

require 'db.php';
// ob_clean(); // Clean output buffer (already done by ob_get_clean later)
header('Content-Type: application/json');

// Debug logging (optional, remove in production)
// error_log("Starting OTP verification process");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); // Clean the buffer before sending non-JSON error
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $otp = filter_var($_POST['otp'], FILTER_SANITIZE_STRING);

    // Debug logging (optional, remove in production)
    // error_log("Verifying OTP for email: " . $email);
    // error_log("Submitted OTP: " . $otp);

    if (empty($email) || empty($otp)) {
        throw new Exception('Email and OTP are required');
    }

    // Get pending registration and debug expiry time
    $stmt = $conn->prepare("SELECT *, 
        TIME_TO_SEC(TIMEDIFF(otp_expires, NOW())) as seconds_left 
        FROM pending_patients 
        WHERE email = ? AND otp = ?");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        // error_log("No matching OTP found for email: " . $email); // Debug logging
        throw new Exception('Invalid or expired OTP code');
    }

    $patient = $result->fetch_assoc();
    // error_log("OTP Details - Expires at: " . $patient['otp_expires'] . ", Seconds left: " . $patient['seconds_left']); // Debug logging

    // Check if OTP is expired (negative seconds means expired)
    if ($patient['seconds_left'] <= 0) {
        // error_log("OTP expired. Expiry time was: " . $patient['otp_expires'] . ", Current server time: " . date('Y-m-d H:i:s')); // Debug logging
        throw new Exception('OTP code has expired. Please request a new one.');
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Always set role as 'user' regardless of what's in pending_patients
        $role = 'user';
        
        // Insert into patients table with role
        $stmt = $conn->prepare("INSERT INTO patients (first_name, middle_name, last_name, email, 
                              phone_number, region, province, city, barangay, zip_code, 
                              date_of_birth, password_hash, gender, role, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param("ssssssssssssss", 
            $patient['first_name'],
            $patient['middle_name'],
            $patient['last_name'],
            $patient['email'],
            $patient['phone_number'],
            $patient['region'],
            $patient['province'],
            $patient['city'],
            $patient['barangay'],
            $patient['zip_code'],
            $patient['date_of_birth'],
            $patient['password_hash'],
            $patient['gender'],
            $role
        );

        if (!$stmt->execute()) {
            // error_log("Failed to insert into patients: " . $stmt->error); // Debug logging
            throw new Exception('Failed to create account: ' . $stmt->error);
        }

        // Delete from pending_patients
        $stmt = $conn->prepare("DELETE FROM pending_patients WHERE email = ?");
        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            throw new Exception('Failed to remove pending registration');
        }

        // Commit transaction
        $conn->commit();

        // Discard any buffered output and echo JSON response
        ob_end_clean(); 
        echo json_encode([
            'status' => 'success',
            'message' => 'Account verified successfully!'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        // error_log("Transaction failed: " . $e->getMessage()); // Debug logging
        throw $e;
    }

} catch (Exception $e) {
    // error_log("Error in OTP verification: " . $e->getMessage()); // Debug logging
    
    // Discard any buffered output and echo JSON error response
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
