<?php
ob_start();
require_once('db.php');
require_once('tcpdf/tcpdf.php');

$type = isset($_GET['type']) ? strtolower($_GET['type']) : 'pending';
$valid_types = ['pending', 'upcoming', 'rescheduled', 'completed', 'cancelled'];

if (!in_array($type, $valid_types)) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Invalid report type']));
}

ob_clean();

try {
    // Create A4 landscape PDF
    $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

    // Document setup
    $pdf->SetCreator('Dental Clinic System');
    $pdf->SetAuthor('Dental Clinic System');
    $pdf->SetTitle(ucfirst($type) . ' Appointments Report');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Tight margins to maximize space (left, top, right)
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();

    // Get data
    $status_map = [
        'pending' => 'pending',
        'upcoming' => 'booked',
        'rescheduled' => 'rescheduled',
        'completed' => 'completed',
        'cancelled' => 'cancelled'
    ];

    $status = $status_map[$type];
    $order = ($type === 'completed') ? 'DESC' : 'ASC';

    $sql = "SELECT a.*, 
            CONCAT('APT-', LPAD(a.id, 6, '0')) as reference_number,
            CONCAT(p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', p.last_name) as patient_name,
            CONCAT(al.first_name, ' ', al.last_name) as dentist_name
            FROM appointments a 
            LEFT JOIN patients p ON a.patient_id = p.id 
            LEFT JOIN admin_logins al ON a.dental_id = al.id
            WHERE a.status = ?
            ORDER BY a.appointment_date $order, a.appointment_time $order";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $status);
    $stmt->execute();
    $result = $stmt->get_result();

    // Title (smaller font to save space)
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, ucfirst($type) . ' Appointments Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y, g:i a'), 0, 1, 'C');
    $pdf->Ln(8);

    // Calculate optimal column widths (sum ~270mm for A4 landscape)
    $col_widths = [
        'reference' => 25,    // Ref No.
        'patient' => 55,      // Patient Name
        'service' => 100,     // Service (widest column)
        'date' => 25,         // Date
        'time' => 20,         // Time
        'dentist' => 45       // Dentist
    ];

    // Adjust columns based on report type
    $headers = ['Ref No.', 'Patient Name', 'Service', 'Date', 'Time'];
    $widths = [
        $col_widths['reference'],
        $col_widths['patient'],
        $col_widths['service'],
        $col_widths['date'],
        $col_widths['time']
    ];

    if (in_array($type, ['upcoming', 'rescheduled'])) {
        $headers[] = 'Dentist';
        $widths[] = $col_widths['dentist'];
    }

    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(220, 220, 220);
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', 1);
    }
    $pdf->Ln();

    // Table rows with dynamic height
    $pdf->SetFont('helvetica', '', 9);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $date = !empty($row['appointment_date']) ? date('M j, Y', strtotime($row['appointment_date'])) : 'N/A';
            $time = !empty($row['appointment_time']) ? date('g:i A', strtotime($row['appointment_time'])) : 'N/A';
            $service = $row['services'] ?? 'General';

            // Calculate required height for this row
            $service_width = $col_widths['service'] - 2; // Account for borders
            $service_lines = ceil($pdf->GetStringWidth($service) / $service_width);
            $row_height = max(6, $service_lines * 4); // Minimum 6mm

            // Output each cell
            $cells = [
                $row['reference_number'],
                $row['patient_name'],
                $service,
                $date,
                $time
            ];

            if (in_array($type, ['upcoming', 'rescheduled'])) {
                $cells[] = $row['dentist_name'] ?? 'N/A';
            }

            foreach ($cells as $i => $content) {
                if ($i == 2) { // Service column
                    $pdf->MultiCell(
                        $widths[$i],
                        $row_height,
                        $content,
                        1, // Border
                        'L', // Align left
                        false, // No fill
                        0 // Don't move position
                    );
                } else {
                    $pdf->Cell(
                        $widths[$i],
                        $row_height,
                        $content,
                        1, // Border
                        0, // No line break
                        'L' // Align left
                    );
                }
            }
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(array_sum($widths), 8, 'No ' . $type . ' appointments found', 1, 1, 'C');
    }

    ob_end_clean();
    $pdf->Output(ucfirst($type) . '_Appointments_Report.pdf', 'D');

} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    die(json_encode(['error' => 'PDF generation failed: ' . $e->getMessage()]));
}