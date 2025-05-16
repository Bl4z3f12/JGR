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

    // If current_data is set, use the production summary from session
    if (isset($_GET['current_data']) && $_GET['current_data'] == 1) {
        session_start();
        if (!isset($_SESSION['production_summary']) || empty($_SESSION['production_summary'])) {
            die('No data available for export');
        }
        $results_for_export = $_SESSION['production_summary'];
    } else {
        // Original database query logic for full data export
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
    }

    // Excel file preparation
    $filename = 'production_summary_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Production Summary');

    // Headers for the Excel file
    $headers = [
        'OF Number', 'Size', 'Category', 'Piece Name', 'Chef', 'Stage', 
        'Total Count', 'Stage Qty', 'Main Qty', 'Solped Client', 'Pedido Client', 
        'Color Tissus', 'Manque', 'Suv Plus', 'Latest Update'
    ];

    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '0D47A1'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
    ];

    // Set headers
    foreach ($headers as $idx => $header) {
        $col = chr(65 + $idx); // A, B, C, ...
        $sheet->setCellValue($col . '1', $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

    // Add data rows
    $row = 2;
    $totalCount = 0;
    foreach ($results_for_export as $data) {
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
        $sheet->setCellValue('O' . $row, $data['latest_update'] ? date('Y-m-d H:i', strtotime($data['latest_update'])) : '');

        $totalCount += (int)($data['total_count'] ?? 0);

        // Apply data row styling
        $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
        ]);

        $row++;
    }

    // Add total row
    $totalRow = $row;
    $sheet->setCellValue('A' . $totalRow, 'TOTAL');
    $sheet->mergeCells('A' . $totalRow . ':F' . $totalRow);
    $sheet->setCellValue('G' . $totalRow, $totalCount);

    // Style total row
    $sheet->getStyle('A' . $totalRow . ':O' . $totalRow)->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'BBDEFB'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['rgb' => '000000'],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
    ]);

    // Format numbers
    $sheet->getStyle('G2:G' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('H2:I' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('M2:N' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');

    // Create a temporary file
    $temp_file = tempnam(sys_get_temp_dir(), 'excel_');
    
    // Save the spreadsheet to the temporary file
    $writer = new Xlsx($spreadsheet);
    $writer->save($temp_file);
    
    // Output headers for direct download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($temp_file));
    header('Cache-Control: max-age=0');
    
    // Clear the output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output the file
    readfile($temp_file);
    
    // Delete the temporary file
    unlink($temp_file);
    
    // Stop script execution
    exit;
}
?>