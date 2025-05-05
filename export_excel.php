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

    // Base query - Only get the specific fields we need for the export
    $query = "SELECT 
                b.of_number, 
                b.size, 
                b.category, 
                b.piece_name AS p_name,
                b.chef,
                b.stage,
                COUNT(b.id) AS total_count,
                -- Get the values from quantity_coupe if they exist
                MAX(qc.quantity_coupe) AS total_stage_quantity,
                MAX(qc.principale_quantity) AS total_main_quantity,
                MAX(qc.solped_client) AS solped_client,
                MAX(qc.pedido_client) AS pedido_client,
                MAX(qc.color_tissus) AS color_tissus,
                MAX(qc.principale_quantity) AS principale_quantity,
                MAX(qc.quantity_coupe) AS quantity_coupe,
                MAX(qc.manque) AS manque,
                MAX(qc.suv_plus) AS suv_plus,
                MAX(IFNULL(qc.lastupdate, b.last_update)) AS latest_update
              FROM barcodes b
              LEFT JOIN quantity_coupe qc ON b.of_number = qc.of_number 
                AND b.size = qc.size 
                AND b.category = qc.category 
                AND b.piece_name = qc.piece_name
              WHERE 1=1";

    $params = [];

    // Add date filter first since it's the most restrictive
    if (!empty($date)) {
        $query .= " AND DATE(IFNULL(qc.lastupdate, b.last_update)) = ?";
        $params[] = $date;
    }

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

    $query .= " GROUP BY b.of_number, b.size, b.category, b.piece_name, b.chef, b.stage";
    $query .= " ORDER BY b.of_number, b.size, b.category, b.piece_name, b.chef, b.stage";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Process the results to match the table view (grouped by OF, size, category, piece_name)
    $grouped_results = [];
    
    foreach ($results as $row) {
        $key = $row['of_number'] . '_' . $row['size'] . '_' . $row['category'] . '_' . $row['p_name'];
        
        if (!isset($grouped_results[$key])) {
            $grouped_results[$key] = [
                'of_number' => $row['of_number'],
                'size' => $row['size'],
                'category' => $row['category'],
                'p_name' => $row['p_name'],
                'chef' => $row['chef'],
                'stage' => $row['stage'],
                'total_count' => $row['total_count'],
                'total_stage_quantity' => $row['total_stage_quantity'] ?? 0,
                'total_main_quantity' => $row['total_main_quantity'] ?? 0,
                'solped_client' => $row['solped_client'] ?? '',
                'pedido_client' => $row['pedido_client'] ?? '',
                'color_tissus' => $row['color_tissus'] ?? '',
                'principale_quantity' => $row['principale_quantity'] ?? 0,
                'quantity_coupe' => $row['quantity_coupe'] ?? 0,
                'manque' => $row['manque'] ?? 0,
                'suv_plus' => $row['suv_plus'] ?? 0,
                'latest_update' => $row['latest_update'] ?? ''
            ];
        } else {
            // Add stage if not already included
            if (!str_contains($grouped_results[$key]['stage'], $row['stage'])) {
                $grouped_results[$key]['stage'] .= ', ' . $row['stage'];
            }
            
            // Increment counts
            $grouped_results[$key]['total_count'] += $row['total_count'];
            
            // Keep latest update date
            if (!empty($row['latest_update'])) {
                if (empty($grouped_results[$key]['latest_update']) || 
                    strtotime($row['latest_update']) > strtotime($grouped_results[$key]['latest_update'])) {
                    $grouped_results[$key]['latest_update'] = $row['latest_update'];
                }
            }
        }
    }
    
    // Convert back to indexed array for Excel export
    $results_for_export = array_values($grouped_results);

    // Totals
    $total_items = 0;
    $total_stage_quantity = 0;
    $total_main_quantity = 0;

    foreach ($results_for_export as $row) {
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

    // Headers for the Excel file - Ensure these match exactly what's shown in the table
    $headers = [
        'OF Number', 'Size', 'Category', 'Piece Name', 'Chef', 'Total Stage Quantity', 
        'Total Main Quantity', 'Stages', 'Total Count', 'Solped Client', 'Pedido Client', 
        'Color Tissus', 'Main Qty', 'Qty Coupe', 'Manque', 'Suv Plus', 'Latest Update'
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
    $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

    $row = 2;
    foreach ($results_for_export as $data) {
        $sheet->setCellValue('A' . $row, $data['of_number'] ?? '');
        $sheet->setCellValue('B' . $row, $data['size'] ?? '');
        $sheet->setCellValue('C' . $row, $data['category'] ?? '');
        $sheet->setCellValue('D' . $row, $data['p_name'] ?? '');
        $sheet->setCellValue('E' . $row, $data['chef'] ?? '');
        $sheet->setCellValue('F' . $row, $data['total_stage_quantity'] ?? 0);
        $sheet->setCellValue('G' . $row, $data['total_main_quantity'] ?? 0);
        $sheet->setCellValue('H' . $row, $data['stage'] ?? '');
        $sheet->setCellValue('I' . $row, $data['total_count'] ?? 0);
        $sheet->setCellValue('J' . $row, $data['solped_client'] ?? '');
        $sheet->setCellValue('K' . $row, $data['pedido_client'] ?? '');
        $sheet->setCellValue('L' . $row, $data['color_tissus'] ?? '');
        $sheet->setCellValue('M' . $row, $data['principale_quantity'] ?? 0);
        $sheet->setCellValue('N' . $row, $data['quantity_coupe'] ?? 0);
        $sheet->setCellValue('O' . $row, $data['manque'] ?? 0);
        $sheet->setCellValue('P' . $row, $data['suv_plus'] ?? 0);
        $sheet->setCellValue('Q' . $row, $data['latest_update'] ? date('Y-m-d H:i', strtotime($data['latest_update'])) : '');

        $sheet->getStyle('A' . $row . ':Q' . $row)->applyFromArray([
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
    $sheet->mergeCells('A' . $totalRow . ':E' . $totalRow);
    $sheet->setCellValue('F' . $totalRow, $total_stage_quantity);
    $sheet->setCellValue('G' . $totalRow, $total_main_quantity);
    $sheet->mergeCells('H' . $totalRow . ':H' . $totalRow);
    $sheet->setCellValue('I' . $totalRow, $total_items);

    $sheet->getStyle('A' . $totalRow . ':Q' . $totalRow)->applyFromArray([
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

    $sheet->getStyle('F' . $totalRow . ':I' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');

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
            <strong>Records:</strong> ' . count($results_for_export) . '
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