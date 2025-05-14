<?php
/**
 * export_excel.php - Exports production summary data to Excel XLSX file
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

    // Verify PhpSpreadsheet is installed
    if (!class_exists(Spreadsheet::class)) {
        die('PhpSpreadsheet library is not installed. Please run: composer require phpoffice/phpspreadsheet');
    }

    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Start a session if not already started to access the stored production summary data
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if we have production summary data in the session
    if (!isset($_SESSION['production_summary']) || empty($_SESSION['production_summary'])) {
        // If no data in session, we need to fetch it using the same query as in the main page
        
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
            die("Database connection failed: " . $e->getMessage());
        }

        // Get filter parameters from request
        $of_number = $_GET['of_number'] ?? '';
        $size = $_GET['size'] ?? '';
        $category = $_GET['category'] ?? '';
        $p_name = $_GET['p_name'] ?? '';
        $stage = $_GET['stage'] ?? '';
        $date = $_GET['date'] ?? date("Y-m-d");

        // Build the query - use named parameters for better debugging
        $query = "SELECT
                    b.of_number,
                    b.size,
                    b.category,
                    b.piece_name,
                    b.chef,
                    COUNT(b.id) AS total_stage_qty,
                    GROUP_CONCAT(DISTINCT b.stage ORDER BY b.stage SEPARATOR ', ') AS stages,
                    COUNT(DISTINCT b.full_barcode_name) AS total_count,
                    MAX(b.last_update) AS latest_update
                  FROM
                    barcodes b
                  WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($date)) {
            // Use DATE() function to compare only the date part
            $query .= " AND DATE(b.last_update) = :date";
            $params[':date'] = $date;
        }

        if (!empty($of_number)) {
            $query .= " AND b.of_number LIKE :of_number";
            $params[':of_number'] = "%$of_number%";
        }

        if (!empty($size)) {
            $query .= " AND b.size = :size";
            $params[':size'] = $size;
        }

        if (!empty($category) && $category != 'select') {
            $query .= " AND b.category = :category";
            $params[':category'] = $category;
        }

        if (!empty($p_name) && $p_name != 'select') {
            $query .= " AND b.piece_name = :p_name";
            $params[':p_name'] = $p_name;
        }

        if (!empty($stage) && $stage != 'select') {
            $query .= " AND b.stage = :stage";
            $params[':stage'] = $stage;
        }

        // Group and order results
        $query .= " GROUP BY b.of_number, b.size, b.category, b.piece_name, b.chef";
        $query .= " ORDER BY MAX(b.last_update) DESC";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
        } catch (PDOException $e) {
            die("Query execution failed: " . $e->getMessage());
        }
    } else {
        // Use the data that's already in the session
        $results = $_SESSION['production_summary'];
    }

    // If still no results, just create an empty file with headers
    if (empty($results)) {
        $results = [];
    }

    // Calculate totals
    $total_items = 0;
    $total_stage_qty = 0;

    foreach ($results as $row) {
        $total_items += $row['total_count'];
        $total_stage_qty += $row['total_stage_qty'];
    }

    // Create Excel file
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Production Summary');

    // Set up column headers
    $headers = [
        'QF Number', 'Size', 'Category', 'Piece Name', 'Chef', 
        'Total Stage Qty', 'Stages', 'Total Count', 'Latest Update'
    ];

    // Apply header styles
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

    // Set header values and column widths
    foreach ($headers as $idx => $header) {
        $col = chr(65 + $idx); // A, B, C, ...
        $sheet->setCellValue($col . '1', $header);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Apply header styles
    $lastCol = chr(65 + count($headers) - 1);
    $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

    // Fill data rows
    $row = 2;
    foreach ($results as $data) {
        $sheet->setCellValue('A' . $row, $data['of_number'] ?? '');
        $sheet->setCellValue('B' . $row, $data['size'] ?? '');
        $sheet->setCellValue('C' . $row, $data['category'] ?? '');
        $sheet->setCellValue('D' . $row, $data['piece_name'] ?? '');
        $sheet->setCellValue('E' . $row, $data['chef'] ?? '');
        $sheet->setCellValue('F' . $row, $data['total_stage_qty'] ?? 0);
        $sheet->setCellValue('G' . $row, $data['stages'] ?? '');
        $sheet->setCellValue('H' . $row, $data['total_count'] ?? 0);
        $sheet->setCellValue('I' . $row, $data['latest_update'] ? date('Y-m-d H:i', strtotime($data['latest_update'])) : '');

        // Apply border styling to data rows
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Apply number formatting where appropriate
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');

        $row++;
    }

    // Add totals row if we have data
    if (!empty($results)) {
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'TOTALS');
        $sheet->mergeCells('A' . $totalRow . ':E' . $totalRow);
        $sheet->setCellValue('F' . $totalRow, $total_stage_qty);
        $sheet->setCellValue('H' . $totalRow, $total_items);

        // Style the totals row
        $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->applyFromArray([
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

        // Apply number formatting to totals
        $sheet->getStyle('F' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
    }

    // Generate filename with date/time
    $filename = 'production_summary_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    try {
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
    } catch (Exception $e) {
        die("Failed to create Excel file: " . $e->getMessage());
    }
}
?>