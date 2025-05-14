<?php
// Include settings file for database connection
require "ofsizedetails_settings.php";
require_once 'auth_functions.php';
requireLogin('login.php');

// Check if required parameters are provided
if (!isset($_GET['solped_client']) || empty($_GET['solped_client'])) {
    die("Error: Solped Client parameter is required.");
}

$solped_client = $_GET['solped_client'];
$grouped = isset($_GET['grouped']) ? (int)$_GET['grouped'] : 0;

// Prepare query to get data
$query = "SELECT 
            b.of_number, 
            b.size, 
            b.category, 
            b.piece_name,
            b.chef,
            b.stage,
            qc.solped_client,
            qc.pedido_client,
            qc.color_tissus,
            qc.principale_quantity,
            qc.quantity_coupe,
            qc.manque,
            qc.suv_plus,
            IFNULL(qc.lastupdate, b.last_update) AS latest_update
          FROM quantity_coupe qc
          LEFT JOIN barcodes b ON b.of_number = qc.of_number 
            AND b.size = qc.size 
            AND b.category = qc.category 
            AND b.piece_name = qc.piece_name
          WHERE qc.solped_client LIKE ?
          ORDER BY qc.of_number, qc.size, qc.category, qc.piece_name";

$stmt = $pdo->prepare($query);
$stmt->execute(["%$solped_client%"]);
$results = $stmt->fetchAll();

// Group identical records if requested
$grouped_results = [];
if ($grouped) {
    $grouped_data = [];
    foreach ($results as $row) {
        // Create a unique key based on all record fields
        $key = $row['of_number'] . '|' . 
               $row['size'] . '|' . 
               $row['category'] . '|' . 
               $row['piece_name'] . '|' . 
               $row['chef'] . '|' . 
               $row['stage'] . '|' . 
               $row['solped_client'] . '|' . 
               $row['pedido_client'] . '|' . 
               $row['color_tissus'] . '|' . 
               $row['principale_quantity'] . '|' . 
               $row['quantity_coupe'] . '|' . 
               $row['manque'] . '|' . 
               $row['suv_plus'] . '|' . 
               $row['latest_update'];
        
        if (!isset($grouped_data[$key])) {
            $grouped_data[$key] = [
                'data' => $row,
                'count' => 1
            ];
        } else {
            $grouped_data[$key]['count']++;
        }
    }
    
    $grouped_results = array_values($grouped_data);
}

// Function to calculate total stage quantity
function calculateTotalStages($stage) {
    return isset($stage) && !empty($stage) ? 1 : 0;
}

// Require library
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Create a new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Solped Client Results');

// Define headers
$headers = [
    'OF Number', 'Size', 'Category', 'Piece Name', 'Chef', 
    'Total Stage Quantity', 'Total Main Quantity', 'Stages', 'Total Count',
    'Solped Client', 'Pedido Client', 'Color Tissus', 
    'Main Qty', 'Qty Coupe', 'Manque', 'Suv Plus', 'Latest Update'
];

// Set column headers
foreach ($headers as $colIndex => $header) {
    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
    $sheet->setCellValue($column . '1', $header);
}

// Style the header row
$headerRowStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

$sheet->getStyle("A1:" . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . "1")->applyFromArray($headerRowStyle);

// Set data rows
$row = 2;
if ($grouped) {
    foreach ($grouped_results as $groupedItem) {
        $item = $groupedItem['data'];
        $count = $groupedItem['count'];
        
        $sheet->setCellValue('A' . $row, $item['of_number']);
        $sheet->setCellValue('B' . $row, $item['size']);
        $sheet->setCellValue('C' . $row, $item['category']);
        $sheet->setCellValue('D' . $row, $item['piece_name']);
        $sheet->setCellValue('E' . $row, $item['chef'] ?? '');
        $sheet->setCellValue('F' . $row, calculateTotalStages($item['stage']));
        $sheet->setCellValue('G' . $row, $item['principale_quantity'] ?? 0);
        $sheet->setCellValue('H' . $row, $item['stage'] ?? '');
        $sheet->setCellValue('I' . $row, $count);
        $sheet->setCellValue('J' . $row, $item['solped_client']);
        $sheet->setCellValue('K' . $row, $item['pedido_client'] ?? '');
        $sheet->setCellValue('L' . $row, $item['color_tissus'] ?? '');
        $sheet->setCellValue('M' . $row, $item['principale_quantity'] ?? '');
        $sheet->setCellValue('N' . $row, $item['quantity_coupe'] ?? '');
        $sheet->setCellValue('O' . $row, $item['manque'] ?? '');
        $sheet->setCellValue('P' . $row, $item['suv_plus'] ?? '');
        $sheet->setCellValue('Q' . $row, $item['latest_update'] ? date('Y-m-d H:i', strtotime($item['latest_update'])) : '');
        $row++;
    }
} else {
    foreach ($results as $item) {
        $sheet->setCellValue('A' . $row, $item['of_number']);
        $sheet->setCellValue('B' . $row, $item['size']);
        $sheet->setCellValue('C' . $row, $item['category']);
        $sheet->setCellValue('D' . $row, $item['piece_name']);
        $sheet->setCellValue('E' . $row, $item['chef'] ?? '');
        $sheet->setCellValue('F' . $row, calculateTotalStages($item['stage']));
        $sheet->setCellValue('G' . $row, $item['principale_quantity'] ?? 0);
        $sheet->setCellValue('H' . $row, $item['stage'] ?? '');
        $sheet->setCellValue('I' . $row, 1); // Count is always 1 for non-grouped results
        $sheet->setCellValue('J' . $row, $item['solped_client']);
        $sheet->setCellValue('K' . $row, $item['pedido_client'] ?? '');
        $sheet->setCellValue('L' . $row, $item['color_tissus'] ?? '');
        $sheet->setCellValue('M' . $row, $item['principale_quantity'] ?? '');
        $sheet->setCellValue('N' . $row, $item['quantity_coupe'] ?? '');
        $sheet->setCellValue('O' . $row, $item['manque'] ?? '');
        $sheet->setCellValue('P' . $row, $item['suv_plus'] ?? '');
        $sheet->setCellValue('Q' . $row, $item['latest_update'] ? date('Y-m-d H:i', strtotime($item['latest_update'])) : '');
        $row++;
    }
}

// Auto-size columns
foreach (range('A', \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers))) as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Define table style for data
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];
$sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . ($row - 1))->applyFromArray($styleArray);

// Conditional formatting for alternate rows
$lastRow = $row - 1;
for ($i = 2; $i <= $lastRow; $i++) {
    if ($i % 2 == 0) {
        $sheet->getStyle('A' . $i . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . $i)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('E9ECEF');
    }
}

// Set Content-Type and Content-Disposition headers to force download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="solped_client_' . $solped_client . '_results.xlsx"');
header('Cache-Control: max-age=0');

// Create Excel file and save to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;