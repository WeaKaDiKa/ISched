<?php require_once('db.php');
require_once('tcpdf/tcpdf.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) { {
    $content = $_POST['content']; // create new PDF document
    $pdf = new TCPDF('P'); // set document information 
    $pdf->SetCreator(PDF_CREATOR);

    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); // set image scale factor 
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); // add a page 
    $pdf->AddPage(); // output the HTML content
    $pdf->writeHTML($content, true, false, true, false, ''); // close and output PDF document 
    $pdf->Output('document.pdf', 'D');
  }
}
?>
<!DOCTYPE html>
<html lang="en-US">

<head>
  <?php require_once 'head.php'; ?>
  <title>ISched</title>
  <style>

  </style>
</head>


</html>