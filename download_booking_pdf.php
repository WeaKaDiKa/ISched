<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once('db.php');

// Check if reference ID is provided
if (!isset($_GET['ref']) || empty($_GET['ref'])) {
    die('Reference ID is required');
}

$referenceId = $_GET['ref'];

// Remove 'OIDA-' prefix if present and convert to integer for ID comparison
$appointmentId = str_replace('OIDA-', '', $referenceId);
$appointmentId = intval($appointmentId);

// Fetch appointment details from database
$stmt = $conn->prepare("SELECT a.*, p.first_name, p.last_name, p.email, p.phone_number, 
                         FROM appointments a 
                         LEFT JOIN patients p ON a.patient_id = p.id 
                         WHERE a.id = ?");

$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Appointment not found');
}

$appointment = $result->fetch_assoc();

// Generate the reference number in the same format as in bookings.php
$formattedReferenceNumber = 'OIDA-' . str_pad($appointmentId, 8, '0', STR_PAD_LEFT);

// Format date
$appointmentDate = date('F j, Y', strtotime($appointment['appointment_date']));


// Format services
$services = explode(', ', $appointment['services']);
$servicesList = '';
foreach ($services as $service) {
    $servicesList .= '<li>' . htmlspecialchars(trim($service)) . '</li>';
}

// Set headers for download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename=Appointment_' . $formattedReferenceNumber . '.html');
header('Cache-Control: max-age=0');

// Output HTML content
echo '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Appointment Booking - ' . htmlspecialchars($formattedReferenceNumber) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        h1 {
            color: #4CAF50;
            font-size: 24px;
            margin: 10px 0;
        }
        .reference {
            background-color: #f1f8e9;
            border: 1px solid #c5e1a5;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            margin: 15px 0;
            font-size: 16px;
        }
        .section {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .section h2 {
            color: #2196F3;
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .notes {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-top: 15px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
        @media print {
            body {
                padding: 0;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Appointment Booking Confirmation</h1>
    </div>
    
    <div class="reference">
        <strong>Reference Number:</strong> ' . htmlspecialchars($formattedReferenceNumber) . '
    </div>
    
    <div class="section">
        <h2>Patient Information</h2>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span>' . htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span>' . htmlspecialchars($appointment['email']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone Number:</span>
            <span>' . htmlspecialchars($appointment['phone_number']) . '</span>
        </div>
    </div>
    
    <div class="section">
        <h2>Appointment Details</h2>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span>' . htmlspecialchars($appointmentDate) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time:</span>
            <span>' . htmlspecialchars($appointment['appointment_time']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Clinic Branch:</span>
            <span>North Fairview Branch</span>
        </div>

        <div class="info-row">
            <span class="info-label">Status:</span>
            <span>' . htmlspecialchars($appointment['status']) . '</span>
        </div>
    </div>
    
    <div class="section">
        <h2>Services</h2>
        <ul>
            ' . $servicesList . '
        </ul>
    </div>
    
    <div class="notes">
        <h2>Important Notes</h2>
        <ul>
            <li>Please arrive 15 minutes before your scheduled appointment time.</li>
            <li>Bring any previous dental records or X-rays if available.</li>
            <li>If you need to cancel or reschedule, please do so at least 24 hours in advance.</li>
            <li>For any questions, please contact us at (02) 8-123-4567 or email at info@oidadental.com</li>
        </ul>
    </div>
    
    <div class="footer">
        <p>Thank you for choosing M&A Oida Dental Clinic for your dental care needs.</p>
        <p>This is a computer-generated document and requires no signature.</p>
    </div>
</body>
</html>';
