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

// Fetch appointment details from database
// The reference number is not stored in the database, it's generated as 'OIDA-' + padded appointment ID
// So we'll just query by the appointment ID

// Remove 'OIDA-' prefix if present and convert to integer for ID comparison
$appointmentId = str_replace('OIDA-', '', $referenceId);
$appointmentId = intval($appointmentId);

$stmt = $conn->prepare("SELECT a.*, p.first_name, p.last_name, p.email, p.phone_number, 
                         d.first_name as doctor_first_name, d.last_name as doctor_last_name 
                         FROM appointments a 
                         LEFT JOIN patients p ON a.patient_id = p.id 
                         LEFT JOIN doctors d ON a.doctor_id = d.id 
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

// Doctor name
$doctorName = 'Not assigned';
if (!empty($appointment['doctor_first_name']) && !empty($appointment['doctor_last_name'])) {
    $doctorName = 'Dr. ' . $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name'];
}

// Format services
$services = explode(', ', $appointment['services']);
$servicesList = '';
foreach ($services as $service) {
    $servicesList .= '<li>' . htmlspecialchars(trim($service)) . '</li>';
}

// Output HTML instead of PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Booking - <?php echo htmlspecialchars($formattedReferenceNumber); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .booking-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
        h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 24px;
        }
        .reference {
            background-color: #f1f8e9;
            border: 1px solid #c5e1a5;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
        }
        .section {
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .section h2 {
            color: #2196F3;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            flex-shrink: 0;
        }
        .info-value {
            flex-grow: 1;
        }
        ul {
            margin: 0;
            padding-left: 20px;
        }
        .notes {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #777;
        }
        .download-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            display: block;
            margin: 20px auto;
            transition: background-color 0.3s;
        }
        .download-btn:hover {
            background-color: #0b7dda;
        }
        @media print {
            body {
                background-color: #fff;
                padding: 0;
            }
            .booking-container {
                box-shadow: none;
                padding: 0;
            }
            .download-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="header">
            <?php if (file_exists('assets/img/oida-logo.png')): ?>
                <img src="assets/img/oida-logo.png" alt="M&A Oida Dental Clinic Logo" class="logo">
            <?php endif; ?>
            <h1>Appointment Booking Confirmation</h1>
        </div>
        
        <div class="reference">
            <strong>Reference Number:</strong> <?php echo htmlspecialchars($formattedReferenceNumber); ?>
        </div>
        
        <div class="section">
            <h2>Patient Information</h2>
            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['email']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone Number:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['phone_number']); ?></div>
            </div>
        </div>
        
        <div class="section">
            <h2>Appointment Details</h2>
            <div class="info-row">
                <div class="info-label">Date:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointmentDate); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Time:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['appointment_time']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Clinic Branch:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['clinic_branch']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Doctor:</div>
                <div class="info-value"><?php echo htmlspecialchars($doctorName); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value"><?php echo htmlspecialchars($appointment['status']); ?></div>
            </div>
        </div>
        
        <div class="section">
            <h2>Services</h2>
            <ul>
                <?php echo $servicesList; ?>
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
        
        <button class="download-btn" id="downloadPdf">Download PDF</button>
        
        <div class="footer">
            <p>Thank you for choosing M&A Oida Dental Clinic for your dental care needs.</p>
            <p>This is a computer-generated document and requires no signature.</p>
        </div>
    </div>
    
    <script>
        // PDF download functionality
        document.getElementById('downloadPdf').addEventListener('click', function() {
            // Most browsers support printing to PDF
            window.print();
            
            // Show instructions for saving as PDF
            setTimeout(function() {
                alert('To save as PDF: In the print dialog, select \'Save as PDF\' or \'Microsoft Print to PDF\' as your printer.');
            }, 500);
        });
    </script>
</body>
</html>
