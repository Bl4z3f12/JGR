<?php
require_once __DIR__ . '/vendor/autoload.php';

use TCPDF;

// Create a new PDF document
$pdf = new TCPDF();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

// Directory containing barcode images
$dir = 'barcodes/';
$files = array_filter(scandir($dir), function ($file) {
    return pathinfo($file, PATHINFO_EXTENSION) === 'png';
});

// Sort files numerically by the last part of the filename (after the dash)
usort($files, function ($a, $b) {
    $numA = (int)substr(pathinfo($a, PATHINFO_FILENAME), strrpos($a, '-') + 1);
    $numB = (int)substr(pathinfo($b, PATHINFO_FILENAME), strrpos($b, '-') + 1);
    return $numA - $numB;
});

// Layout configuration
$columns = 3;
$maxRows = 3; 
$boxWidth = 60;
$boxHeight = 50;
$gapX = 5;       
$imageHeight = 18;
$filesPerPage = $columns * $maxRows;

// Split files into pages
$fileChunks = array_chunk($files, $filesPerPage);

foreach ($fileChunks as $chunk) {
    $pdf->AddPage();
    $pageWidth  = $pdf->getPageWidth();
    $pageHeight = $pdf->getPageHeight();

    // Use fixed margins as specified; here we use 10 on top and bottom.
    $topMargin    = 20;
    $bottomMargin = 10;
    $usableHeight = $pageHeight - $topMargin - $bottomMargin;

    // Determine the number of rows this page will have.
    // Even if there are fewer items than $maxRows, we distribute them evenly.
    $currentRows = ceil(count($chunk) / $columns);
    // Ensure that if there's only one row, gap is 0.
    $gapY = ($currentRows > 1) ? (($usableHeight - ($currentRows * $boxHeight)) / ($currentRows - 1)) : 0;

    // For horizontal centering of the grid, calculate total grid width.
    $totalWidth = ($columns * $boxWidth) + (($columns - 1) * $gapX);
    $startX = ($pageWidth - $totalWidth) / 2;
    // Instead of centering vertically, start at the top margin.
    $startY = $topMargin;

    // Draw horizontal lines between rows (if there is more than one row).
    for ($r = 1; $r < $currentRows; $r++) {
        // The Y position is calculated as the bottom of the previous box plus half the gap.
        $lineY = $startY + $r * $boxHeight + ($r - 0.5) * $gapY;
        $pdf->Line($startX, $lineY, $startX + $totalWidth, $lineY);
    }

    // Place each barcode and its label in the grid.
    foreach ($chunk as $i => $file) {
        $col = $i % $columns;
        $row = floor($i / $columns);

        // Calculate the top-left coordinates for the current cell.
        $x = $startX + $col * ($boxWidth + $gapX);
        $y = $startY + $row * ($boxHeight + $gapY);

        // Center the image horizontally within the cell.
        $imageX = $x + ($boxWidth - $imageWidth) / 2;
        $imageY = $y + 4; // A small vertical margin within the cell
        $pdf->Image($dir . $file, $imageX, $imageY, $imageWidth, $imageHeight);

        // Print the barcode number below the image.
        $barcodeText = pathinfo($file, PATHINFO_FILENAME);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY($x, $y + $imageHeight + 12);
        $pdf->Cell($boxWidth, 6, $barcodeText, 0, 0, 'C');
    }
}

$pdf->Output('barcodes.pdf', 'I');
?>
