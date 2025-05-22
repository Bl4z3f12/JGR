<?php
require_once __DIR__ . '/vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;
$pdf = new FPDF();
$current_view = $_GET['view'] ?? 'dashboard';
$current_date = date("F j, Y");
$page = $_GET['page'] ?? 1;
$items_per_page = 250;
$date = $_GET['date'] ?? $current_date;
$filter_of_number = $_GET['filter_of'] ?? '';
$filter_size = $_GET['filter_size'] ?? '';
$filter_category = $_GET['filter_category'] ?? '';
$filter_piece_name = $_GET['filter_piece_name'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

function connectDB() {
    $conn = new mysqli("localhost", "root", "", "jgr");
    return $conn->connect_error ? false : $conn;
}

function getBarcodes($view, $page, $items_per_page, $filter_of = '', $filter_size = '', $filter_category = '', $filter_piece_name = '', $filter_date = '') {
    $conn = connectDB();
    if (!$conn) return [];
    $offset = ($page - 1) * $items_per_page;
    $conditions = [];
    if ($view === 'today') {
        $conditions[] = "DATE(last_update) = CURDATE()";
    } elseif ($view === 'manufactured') {
        $conditions[] = "status = 'Completed'";
    }
    if (!empty($filter_of)) {
        $conditions[] = "of_number LIKE ?";
    }
    if (!empty($filter_size)) {
        $conditions[] = "size = ?";
    }
    if (!empty($filter_category)) {
        $conditions[] = "category = ?";
    }
    if (!empty($filter_piece_name)) {
        $conditions[] = "piece_name = ?";
    }
    if (!empty($filter_date)) {
        $conditions[] = "DATE(last_update) = ?";
    }
    $where = '';
    if (!empty($conditions)) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }
    $sql = "SELECT * FROM barcodes $where ORDER BY id DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $types = "";
    $params = [];
    if (!empty($filter_of)) {
        $filter_of_param = "%$filter_of%";
        $types .= "s";
        $params[] = $filter_of_param;
    }
    if (!empty($filter_size)) {
        $types .= "s"; // Changed from 'i' to 's' to handle string sizes
        $params[] = $filter_size;
    }
    if (!empty($filter_category)) {
        $types .= "s";
        $params[] = $filter_category;
    }
    if (!empty($filter_piece_name)) {
        $types .= "s";
        $params[] = $filter_piece_name;
    }
    if (!empty($filter_date)) {
        $types .= "s";
        $params[] = $filter_date;
    }
    $types .= "ii";
    $params[] = $offset;
    $params[] = $items_per_page;
    if (!empty($params)) {
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $barcodes = [];
    while ($row = $result->fetch_assoc()) {
        $barcodes[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $barcodes;
}

function getTotalBarcodes($view, $filter_of = '', $filter_size = '', $filter_category = '', $filter_piece_name = '', $filter_date = '') {
    $conn = connectDB();
    if (!$conn) return 100;
    $conditions = [];
    if ($view === 'today') {
        $conditions[] = "DATE(last_update) = CURDATE()";
    } elseif ($view === 'manufactured') {
        $conditions[] = "status = 'Completed'";
    }
    if (!empty($filter_of)) {
        $conditions[] = "of_number LIKE ?";
    }
    if (!empty($filter_size)) {
        $conditions[] = "size = ?";
    }
    if (!empty($filter_category)) {
        $conditions[] = "category = ?";
    }
    if (!empty($filter_piece_name)) {
        $conditions[] = "piece_name = ?";
    }
    if (!empty($filter_date)) {
        $conditions[] = "DATE(last_update) = ?";
    }
    $where = '';
    if (!empty($conditions)) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }
    $sql = "SELECT COUNT(*) as total FROM barcodes $where";
    $stmt = $conn->prepare($sql);
    $types = "";
    $params = [];
    if (!empty($filter_of)) {
        $filter_of_param = "%$filter_of%";
        $types .= "s";
        $params[] = $filter_of_param;
    }
    if (!empty($filter_size)) {
        $types .= "s"; // Changed from 'i' to 's' to handle string sizes
        $params[] = $filter_size;
    }
    if (!empty($filter_category)) {
        $types .= "s";
        $params[] = $filter_category;
    }
    if (!empty($filter_piece_name)) {
        $types .= "s";
        $params[] = $filter_piece_name;
    }
    if (!empty($filter_date)) {
        $types .= "s";
        $params[] = $filter_date;
    }
    if (!empty($params)) {
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result ? $result->fetch_assoc()['total'] : 0;
    $stmt->close();
    $conn->close();
    return $total ?: 100;
}

function getViewTitle($view) {
    return match ($view) {
        'dashboard' => 'Home Page',
        'today' => 'Scanned Today',
        'manufactured' => 'Manufactured Barcodes',
        'history' => 'Barcode History',
        'export' => 'Export Barcodes',
        default => 'Barcodes Overview',
    };
}

function placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage) {
    $barcodeImage = $generator->getBarcode($full_barcode_name, $generator::TYPE_CODE_128);
    $barcodePath = __DIR__ . "/barcodes/tmp_$full_barcode_name.png";
    file_put_contents($barcodePath, $barcodeImage);
    $x = $col * $cellWidth;
    $y = $row * $cellHeight;
    $barcodeX = $x + (($cellWidth - $barcodeWidth) / 2);
    $barcodeY = $y + $topSpacing + ($cellHeight / 2) - ($barcodeHeight / 2) - 5;
    $pdf->Image($barcodePath, $barcodeX, $barcodeY, $barcodeWidth, $barcodeHeight);
    $pdf->SetFont('Arial', '', $fontSize);
    $textY = $barcodeY + $barcodeHeight + 2;
    $pdf->SetXY($x, $textY);
    $pdf->Cell($cellWidth, 5, $full_barcode_name, 0, 0, 'C');
    if ($col < $colsPerPage - 1) { 
        $lineX = $x + $cellWidth;
        $pdf->Line($lineX, $y, $lineX, $y + $cellHeight);
    }
    if ($row < $rowsPerPage - 1) {
        $lineY = $y + $cellHeight;
        $pdf->Line(0, $lineY, $pageWidth, $lineY);
    }
    unlink($barcodePath);
    $col++;
    if ($col >= $colsPerPage) {
        $col = 0;
        $row++;
    }
    return ['col' => $col, 'row' => $row];
}

function createNewPdf() {
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);
    return $pdf;
}

function removeEmptyPages($filePath) {
    $fileSize = filesize($filePath);
    $estimatedPageCount = ceil($fileSize / 40000);
    $pageCount = max(1, $estimatedPageCount);
    return $pageCount;
}

function getRandomButtonScript() {
    return <<<SCRIPT
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const lostBarcodeCheckbox = document.getElementById('lost-barcode');
        const lostBarcodeCount = document.getElementById('lost-barcode-count');
        const lostBarcodeOrderNumber = document.getElementById('lost-barcode-order-number');
        const nameSelect = document.getElementById('name');
        const pieceNameSelect = document.getElementById('barcode-piece-name');
        const rangeFromInput = document.getElementById('range-from');
        const rangeToInput = document.getElementById('range-to');
        
        // Handle lost barcode checkbox
        if (lostBarcodeCheckbox) {
            lostBarcodeCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                
                // Enable/disable lost barcode specific fields
                lostBarcodeCount.disabled = !isChecked;
                lostBarcodeOrderNumber.disabled = !isChecked;
                nameSelect.disabled = !isChecked;
                
                // Handle piece name and range requirements
                if (isChecked) {
                    pieceNameSelect.required = true;
                    rangeFromInput.required = false;
                    rangeToInput.required = false;
                    lostBarcodeOrderNumber.required = true;
                } else {
                    pieceNameSelect.required = true;
                    rangeFromInput.required = true;
                    rangeToInput.required = true;
                    lostBarcodeOrderNumber.required = false;
                }
            });
        }
        
        const clearFiltersBtn = document.getElementById('clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('filter-of').value = '';
                document.getElementById('filter-size').value = '';
                document.getElementById('filter-category').value = '';
                document.getElementById('filter-piece-name').value = '';
                document.getElementById('filter-date').value = '';
                document.getElementById('filter-form').submit();
            });
        }
    });
    </script>
    SCRIPT;
}

function formatBarcodeString($of_number, $size, $category, $piece_name, $number) {
    if (empty($category)) {
        return "$of_number-$size-$piece_name-$number";
    } else {
        return "$of_number-$size$category-$piece_name-$number";
    }
}

function barcodeExists($conn, $full_barcode_name) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM barcodes WHERE full_barcode_name = ?");
    $stmt->bind_param("s", $full_barcode_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_barcode') {
    $of_number = $_POST['barcode_prefix'] ?? '';
    $size = $_POST['barcode_size'] ?? '';
    $category = $_POST['barcode_category'] ?? '';
    $piece_name = $_POST['barcode_piece_name'] ?? '';
    $form_view = $_POST['view'] ?? 'dashboard';
    $is_lost_barcode = isset($_POST['lost_barcode']);
    $generate_costume_2pcs = isset($_POST['generate_costume_2pcs']);
    $generate_costume_3pcs = isset($_POST['generate_costume_3pcs']);
    $generate_pdf_only = isset($_POST['generate_pdf_only']);
    // Get the user_name for lost barcodes
    $user_name = $is_lost_barcode ? ($_POST['name'] ?? '') : '';
    
    $errors = [];
    $duplicates = [];
    
    if (!$of_number) $errors[] = "OF number is required";
    if (!$generate_costume_2pcs && !$generate_costume_3pcs && !$piece_name) {
        $errors[] = "Piece name is required";
    }
    
    // Add validation for user_name field for lost barcodes
    if ($is_lost_barcode && empty($user_name)) {
        $errors[] = "Name is required for lost barcodes";
    }
    
    if ($is_lost_barcode) {
        $lost_barcode_count = (int)($_POST['lost_barcode_count'] ?? 1);
        $lost_barcode_order_number = $_POST['lost_barcode_order_number'] ?? ''; // New field for order number
        
        if ($lost_barcode_count <= 0 || $lost_barcode_count > 100) {
            $errors[] = "Lost barcode quantity must be between 1 and 100";
        }
        
        // Validate that order number is provided for lost barcodes
        if (empty($lost_barcode_order_number)) {
            $errors[] = "Order number is required for lost barcodes";
        } elseif (!is_numeric($lost_barcode_order_number) || (int)$lost_barcode_order_number <= 0) {
            $errors[] = "Order number must be a positive number";
        }
    } else {
        $range_from = (int)($_POST['range_from'] ?? 0);
        $range_to = (int)($_POST['range_to'] ?? 0);
        if ($range_from <= 0 || $range_to <= 0 || $range_from > $range_to) $errors[] = "Invalid range";
    }
    
    if (empty($errors)) {
        $conn = connectDB();
        if ($conn) {
            $generator = new BarcodeGeneratorPNG();
            $successCount = 0;
            $pdfFiles = [];
            
            // Configuration for all barcode PDFs
            $colsPerPage = 3;
            $rowsPerPage = 5;
            $pageWidth = 210;
            $pageHeight = 297;
            $cellWidth = $pageWidth / $colsPerPage;
            $cellHeight = $pageHeight / $rowsPerPage;
            $barcodeWidth = 50;
            $barcodeHeight = 20;
            $topSpacing = 12;
            $fontSize = 14;

            if ($is_lost_barcode) {
                // Lost barcode handling - use specified order number instead of random
                $pdf = createNewPdf();
                $col = 0;
                $row = 0;
                
                $lost_barcode_count = (int)($_POST['lost_barcode_count'] ?? 1);
                $base_order_number = (int)$lost_barcode_order_number; // Use the specified order number
                
                for ($i = 0; $i < $lost_barcode_count; $i++) {
                    $current_order_number = $base_order_number + $i; // Increment for multiple lost barcodes
                    $formatted_number = "X" . $current_order_number;
                    $full_barcode_name = formatBarcodeString($of_number, $size, $category, $piece_name, $formatted_number);
                    
                    if (!$generate_pdf_only) {
                        if (barcodeExists($conn, $full_barcode_name)) {
                            $duplicates[] = $full_barcode_name;
                            continue;
                        }
                        
                        // Include user_name in the insert for lost barcodes
                        $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage, name) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?)");
                        $stmt->bind_param("sssssss", $of_number, $size, $category, $piece_name, $formatted_number, $full_barcode_name, $user_name);
                        $stmt->execute();
                        $successCount++;
                    }
                    
                    $result = placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage);
                    $col = $result['col'];
                    $row = $result['row'];
                    if ($row >= $rowsPerPage && $i < $lost_barcode_count - 1) {
                        $row = 0;
                        $pdf->AddPage();
                    }
                }
                
                $pdfFilename = "{$of_number}-{$size}{$category}-RTC.pdf";
                $pdfFilename = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $pdfFilename);
                if (empty($pdfFilename) || $pdfFilename == ".pdf") {
                    $randomNumber = rand(10000, 99999);
                    $pdfFilename = "A{$randomNumber}.pdf";
                }
                $pdfPath = __DIR__ . "/barcodes/$pdfFilename";
                $pdf->Output('F', $pdfPath);
                $actualPages = removeEmptyPages($pdfPath);
                $pdfFiles[] = [
                    'filename' => $pdfFilename,
                    'pages' => $actualPages,
                    'piece' => 'RTC'
                ];
                
            } else if ($generate_costume_2pcs || $generate_costume_3pcs) {
                $pieces = $generate_costume_2pcs ? ['P', 'V'] : ['P', 'V', 'G'];
                $range_from = (int)$_POST['range_from'];
                $range_to = (int)$_POST['range_to'];
                
                // Create a single PDF with each piece starting on a new page
                $pdf = createNewPdf();
                $piecesPageCount = [];
                
                foreach ($pieces as $index => $current_piece) {
                    // Start on a new page for each piece except the first one
                    if ($index > 0) {
                        $pdf->AddPage();
                    }
                    
                    $col = 0;
                    $row = 0;
                    $currentPieceStartPage = $pdf->PageNo();
                    
                    for ($i = $range_from; $i <= $range_to; $i++) {
                        $full_barcode_name = formatBarcodeString($of_number, $size, $category, $current_piece, $i);
                        if (!$generate_pdf_only) {
                            if (barcodeExists($conn, $full_barcode_name)) {
                                $duplicates[] = $full_barcode_name;
                                continue;
                            }
                            $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                            $stmt->bind_param("ssssss", $of_number, $size, $category, $current_piece, $i, $full_barcode_name);
                            $stmt->execute();
                            $successCount++;
                        }
                        $result = placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage);
                        $col = $result['col'];
                        $row = $result['row'];
                        
                        if ($row >= $rowsPerPage && $i < $range_to) {
                            $row = 0;
                            $pdf->AddPage();
                        }
                    }
                    
                    // Calculate how many pages this piece took
                    $piecePages = $pdf->PageNo() - $currentPieceStartPage + 1;
                    $piecesPageCount[$current_piece] = $piecePages;
                }
                
                // Save the combined PDF
                $piecesStr = implode('', $pieces);
                $pdfFilename = "{$of_number}-{$size}{$category}-{$piecesStr}.pdf";
                $pdfFilename = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $pdfFilename);
                if (empty($pdfFilename) || $pdfFilename == ".pdf") {
                    $randomNumber = rand(10000, 99999);
                    $pdfFilename = "A{$randomNumber}.pdf";
                }
                $pdfPath = __DIR__ . "/barcodes/$pdfFilename";
                $pdf->Output('F', $pdfPath);
                $actualPages = removeEmptyPages($pdfPath);
                
                // Record each piece's information for the message
                foreach ($pieces as $piece) {
                    $pdfFiles[] = [
                        'filename' => $pdfFilename,
                        'pages' => $piecesPageCount[$piece] ?? 0,
                        'piece' => $piece
                    ];
                }
                
            } else {
                // Single piece type - single PDF as before
                $pdf = createNewPdf();
                $col = 0;
                $row = 0;
                
                $range_from = (int)$_POST['range_from'];
                $range_to = (int)$_POST['range_to'];
                $total_barcodes = $range_to - $range_from + 1;
                $barcode_counter = 0;
                
                for ($i = $range_from; $i <= $range_to; $i++) {
                    $full_barcode_name = formatBarcodeString($of_number, $size, $category, $piece_name, $i);
                    if (!$generate_pdf_only) {
                        if (barcodeExists($conn, $full_barcode_name)) {
                            $duplicates[] = $full_barcode_name;
                            continue;
                        }
                        $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                        $stmt->bind_param("ssssss", $of_number, $size, $category, $piece_name, $i, $full_barcode_name);
                        $stmt->execute();
                        $successCount++;
                    }
                    $result = placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage);
                    $col = $result['col'];
                    $row = $result['row'];
                    $barcode_counter++;
                    
                    if ($row >= $rowsPerPage && $barcode_counter < $total_barcodes) {
                        $row = 0;
                        $pdf->AddPage();
                    }
                }
                
                if ($generate_pdf_only) {
                    $pdfFilename = "{$of_number}-{$size}{$category}-pdfonly.pdf";
                } else {
                    $pdfFilename = "{$of_number}-{$size}{$category}-{$piece_name}.pdf";
                }
                $pdfFilename = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $pdfFilename);
                if (empty($pdfFilename) || $pdfFilename == ".pdf") {
                    $randomNumber = rand(10000, 99999);
                    $pdfFilename = "A{$randomNumber}.pdf";
                }
                $pdfPath = __DIR__ . "/barcodes/$pdfFilename";
                $pdf->Output('F', $pdfPath);
                $actualPages = removeEmptyPages($pdfPath);
                $pdfFiles[] = [
                    'filename' => $pdfFilename,
                    'pages' => $actualPages,
                    'piece' => $piece_name
                ];
            }
            
            $conn->close();
            
            // Create PDF file list for the success message
            $pdf_list = '';
            foreach ($pdfFiles as $pdfFile) {
                $pdf_list .= "{$pdfFile['filename']} ({$pdfFile['pages']} pages) for {$pdfFile['piece']}, ";
            }
            $pdf_list = rtrim($pdf_list, ', ');
            
            // Use the first PDF filename for the redirect
            $first_pdf = $pdfFiles[0]['filename'] ?? '';
            
            if (!empty($duplicates)) {
                $duplicate_message = "The following barcodes already exist: " . implode(", ", array_slice($duplicates, 0, 5));
                if (count($duplicates) > 5) {
                    $duplicate_message .= " and " . (count($duplicates) - 5) . " more";
                }
                if ($successCount > 0) {
                    $message = "$successCount barcodes created successfully. Generated PDFs: $pdf_list. $duplicate_message";
                    header("Location: index.php?view=dashboard&modal=create&warning=" . urlencode($message) . "&pdf=$first_pdf");
                } else {
                    header("Location: index.php?view=$form_view&modal=create&error=" . urlencode($duplicate_message));
                }
                exit;
            } else {
                $success_message = "Created PDFs: $pdf_list";
                header("Location: index.php?view=dashboard&modal=create&success=1&pdf=$first_pdf&info=" . urlencode($success_message));
                exit;
            }
        }
    } else {
        header("Location: index.php?view=$form_view&modal=create&error=" . urlencode(implode(", ", $errors)));
        exit;
    }
}

$barcodes = getBarcodes($current_view, $page, $items_per_page, $filter_of_number, $filter_size, $filter_category, $filter_piece_name, $filter_date);
$total_barcodes = getTotalBarcodes($current_view, $filter_of_number, $filter_size, $filter_category, $filter_piece_name, $filter_date);
$total_pages = ceil($total_barcodes / $items_per_page);
$show_success = isset($_GET['success']) && $_GET['success'] == 1;
$error_message = $_GET['error'] ?? '';
$warning_message = $_GET['warning'] ?? '';
$info_message = $_GET['info'] ?? '';
$show_modal = isset($_GET['modal']) && $_GET['modal'] === 'create';
$random_button_script = getRandomButtonScript();
?>