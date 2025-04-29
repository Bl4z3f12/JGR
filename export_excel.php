<?php
/**
 * export_excel.php - Exports table data to Excel XLSX file
 * 
 * Requirements:
 * - PhpSpreadsheet library (install via Composer)
 * - Command: composer require phpoffice/phpspreadsheet
 */

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Check if this is an export request
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

    if (!class_exists(Spreadsheet::class)) {
        die('PhpSpreadsheet library is not installed. Please run: composer require phpoffice/phpspreadsheet');
    }

    // Database connection
    $host = 'localhost';
    $db_name = 'jgr';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // Parameters
    $of_number = $_GET['of_number'] ?? '';
    $size = $_GET['size'] ?? '';
    $category = $_GET['category'] ?? '';
    $p_name = $_GET['p_name'] ?? '';
    $stage = $_GET['stage'] ?? '';
    $date = $_GET['date'] ?? date("Y-m-d");

    $query = "SELECT 
                b.of_number, 
                b.size, 
                b.category, 
                b.piece_name AS p_name,
                b.chef,
                b.stage,
                COUNT(b.id) AS total_count,
                qc.quantity_coupe AS total_stage_quantity,
                qc.principale_quantity AS total_main_quantity,
                qc.solped_client AS solped_client,
                qc.pedido_client AS pedido_client,
                qc.color_tissus AS color_tissus,
                qc.principale_quantity AS principale_quantity,
                qc.quantity_coupe AS quantity_coupe,
                qc.manque AS manque,
                qc.suv_plus AS suv_plus,
                IFNULL(qc.lastupdate, b.last_update) AS latest_update
              FROM barcodes b
              LEFT JOIN quantity_coupe qc ON b.of_number = qc.of_number 
                AND b.size = qc.size 
                AND b.category = qc.category 
                AND b.piece_name = qc.piece_name
              WHERE 1=1";

    $params = [];

    if (!empty($of_number)) {
        $query .= " AND b.of_number LIKE ?";
        $params[] = "%$of_number%";
    }

    if (!empty($size)) {
        $query .= " AND b.size = ?";
        $params[] = $size;
    }

    if (!empty($category) && $category != 'select') {
        $query .= " AND b.category = ?";
        $params[] = $category;
    }

    if (!empty($p_name) && $p_name != 'select') {
        $query .= " AND b.piece_name = ?";
        $params[] = $p_name;
    }

    if (!empty($stage) && $stage != 'select') {
        $query .= " AND b.stage = ?";
        $params[] = $stage;
    }

    if (!empty($date)) {
        $query .= " AND DATE(
                        (SELECT MAX(IFNULL(qc2.lastupdate, b2.last_update))
                         FROM barcodes b2
                         LEFT JOIN quantity_coupe qc2 ON b2.of_number = qc2.of_number 
                            AND b2.size = qc2.size 
                            AND b2.category = qc2.category 
                            AND b2.piece_name = qc2.piece_name
                         WHERE b2.of_number = b.of_number
                            AND b2.size = b.size
                            AND b2.category = b.category
                            AND b2.piece_name = b.piece_name
                        )
                    ) = ?";
        $params[] = $date;
    }

    $query .= " GROUP BY b.of_number, b.size, b.category, b.piece_name, b.chef, b.stage";
    $query .= " ORDER BY b.of_number, b.size, b.category, b.piece_name, b.chef, b.stage";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Totals
    $total_items = 0;
    $total_stage_quantity = 0;
    $total_main_quantity = 0;

    foreach ($results as $row) {
        $total_items += $row['total_count'];
        $total_stage_quantity += $row['total_stage_quantity'] ?? 0;
        $total_main_quantity += $row['total_main_quantity'] ?? 0;
    }

    // Excel directory
    $excel_dir = __DIR__ . '/excel';
    if (!file_exists($excel_dir)) {
        mkdir($excel_dir, 0777, true);
    }

    $filename = 'export_data_' . date('Y-m-d_H-i-s') . '.xlsx';
    $filepath = $excel_dir . '/' . $filename;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Export Data');

    $headers = [
        'OF Number', 'Size', 'Category', 'Piece Name', 'Chef', 'Stage', 
        'Total Count', 'Quantity Coupe', 'Principale Quantity', 
        'SolPed Client', 'Pedido Client', 'Color Tissus', 
        'Manque', 'Suv Plus', 'Latest Update'
    ];

    $headerStyle = [
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
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    foreach ($headers as $idx => $header) {
        $col = chr(65 + $idx); // A, B, C, ...
        $sheet->setCellValue($col . '1', $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

    $row = 2;
    foreach ($results as $data) {
        $sheet->setCellValue('A' . $row, $data['of_number'] ?? '');
        $sheet->setCellValue('B' . $row, $data['size'] ?? '');
        $sheet->setCellValue('C' . $row, $data['category'] ?? '');
        $sheet->setCellValue('D' . $row, $data['p_name'] ?? '');
        $sheet->setCellValue('E' . $row, $data['chef'] ?? '');
        $sheet->setCellValue('F' . $row, $data['stage'] ?? '');
        $sheet->setCellValue('G' . $row, $data['total_count'] ?? 0);
        $sheet->setCellValue('H' . $row, $data['total_stage_quantity'] ?? 0);
        $sheet->setCellValue('I' . $row, $data['total_main_quantity'] ?? 0);
        $sheet->setCellValue('J' . $row, $data['solped_client'] ?? '');
        $sheet->setCellValue('K' . $row, $data['pedido_client'] ?? '');
        $sheet->setCellValue('L' . $row, $data['color_tissus'] ?? '');
        $sheet->setCellValue('M' . $row, $data['manque'] ?? 0);
        $sheet->setCellValue('N' . $row, $data['suv_plus'] ?? 0);
        $sheet->setCellValue('O' . $row, $data['latest_update'] ?? '');

        $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $row++;
    }

    $totalRow = $row;
    $sheet->setCellValue('A' . $totalRow, 'TOTALS');
    $sheet->mergeCells('A' . $totalRow . ':F' . $totalRow);
    $sheet->setCellValue('G' . $totalRow, $total_items);
    $sheet->setCellValue('H' . $totalRow, $total_stage_quantity);
    $sheet->setCellValue('I' . $totalRow, $total_main_quantity);

    $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)->applyFromArray([
        'font' => [
            'bold' => true,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E2EFDA'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ],
    ]);

    $sheet->getStyle('G' . $totalRow . ':I' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');

    $writer = new Xlsx($spreadsheet);
    $writer->save($filepath);

    // Output HTML
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Export Successful</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 20px;
            }
            .success-box {
                background-color: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .btn {
                display: inline-block;
                padding: 8px 16px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-right: 10px;
            }
            .btn:hover {
                background-color: #0069d9;
            }
            .btn-secondary {
                background-color: #6c757d;
            }
            .btn-secondary:hover {
                background-color: #5a6268;
            }
        </style>
    </head>
    <body>
        <div class="success-box">
            Excel file has been saved successfully in XLSX format.
        </div>
        
        <h3>File details:</h3>
        <p>
            <strong>Filename:</strong> ' . htmlspecialchars($filename) . '<br>
            <strong>Path:</strong> ' . htmlspecialchars($filepath) . '<br>
            <strong>Records:</strong> ' . count($results) . '
        </p>
        
        <p>
            <a href="excel/' . htmlspecialchars($filename) . '" download class="btn">Download Excel File</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </p>
    </body>
    </html>';

    exit;
}
?>
