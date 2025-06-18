<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once('db.php');

// Include TCPDF from your specific path
require_once('admin/tcpdf/tcpdf.php');

// Check if reference ID is provided
if (!isset($_GET['ref']) || empty($_GET['ref'])) {
    die('Reference ID is required');
}

$referenceId = $_GET['ref'];

// Remove 'OIDA-' prefix if present and convert to integer for ID comparison
$appointmentId = str_replace('OIDA-', '', $referenceId);
$appointmentId = intval($appointmentId);

// Fetch appointment details from database
$stmt = $conn->prepare("SELECT a.*, p.first_name, p.last_name, p.email, p.phone_number
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

// Generate the reference number
$formattedReferenceNumber = 'OIDA-' . str_pad($appointmentId, 8, '0', STR_PAD_LEFT);

// Format date
$appointmentDate = date('F j, Y', strtotime($appointment['appointment_date']));

// Format services
$services = explode(', ', $appointment['services']);

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Oida Dental Clinic');
$pdf->SetAuthor('Oida Dental Clinic');
$pdf->SetTitle('Appointment Confirmation - ' . $formattedReferenceNumber);
$pdf->SetSubject('Appointment Confirmation');

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Add a page
$pdf->AddPage();

// Add logo
$logoPath = 'path/to/your/logo.png'; // Set your logo path
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 30, 0, 'PNG');
}

// Set font for title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetY(40);
$pdf->Cell(0, 10, 'Appointment Booking Confirmation', 0, 1, 'C');

// Reference number
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(241, 248, 233);
$pdf->Cell(0, 10, 'Reference Number: ' . $formattedReferenceNumber, 0, 1, 'C', 1);

// Patient Information
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Patient Information', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$pdf->Cell(40, 7, 'Name:', 0, 0);
$pdf->Cell(0, 7, $appointment['first_name'] . ' ' . $appointment['last_name'], 0, 1);

$pdf->Cell(40, 7, 'Email:', 0, 0);
$pdf->Cell(0, 7, $appointment['email'], 0, 1);

$pdf->Cell(40, 7, 'Phone Number:', 0, 0);
$pdf->Cell(0, 7, $appointment['phone_number'], 0, 1);

// Appointment Details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Appointment Details', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$pdf->Cell(40, 7, 'Date:', 0, 0);
$pdf->Cell(0, 7, $appointmentDate, 0, 1);

$pdf->Cell(40, 7, 'Time:', 0, 0);
$pdf->Cell(0, 7, $appointment['appointment_time'], 0, 1);

$pdf->Cell(40, 7, 'Clinic Branch:', 0, 0);
$pdf->Cell(0, 7, 'North Fairview Branch', 0, 1);

$pdf->Cell(40, 7, 'Status:', 0, 0);
$pdf->Cell(0, 7, $appointment['status'], 0, 1);

// Services
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Services', 0, 1);
$pdf->SetFont('helvetica', '', 12);

foreach ($services as $service) {
    $pdf->Cell(5, 7, '', 0, 0);
    $pdf->Cell(0, 7, '• ' . trim($service), 0, 1);
}

// Important Notes
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Important Notes', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$notes = [
    'Please arrive 15 minutes before your scheduled appointment time.',
    'Bring any previous dental records or X-rays if available.',
    'If you need to cancel or reschedule, please do so at least 24 hours in advance.',
    'For any questions, please contact us at (02) 8-123-4567 or email at info@oidadental.com'
];

foreach ($notes as $note) {
    $pdf->Cell(5, 7, '', 0, 0);
    $pdf->Cell(0, 7, '• ' . $note, 0, 1);
}

// Footer
$pdf->SetY(-40);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 7, 'Thank you for choosing M&A Oida Dental Clinic for your dental care needs.', 0, 1, 'C');
$pdf->Cell(0, 7, 'This is a computer-generated document and requires no signature.', 0, 1, 'C');

// Output PDF
$pdf->Output('Appointment_' . $formattedReferenceNumber . '.pdf', 'D');
exit;