<?php
require_once __DIR__ . '/vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;
$pdf = new FPDF();
$current_view = $_GET['view'] ?? 'dashboard';
$current_date = date("F j, Y");
$page = $_GET['page'] ?? 1;
$items_per_page = 2000;
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
        'dashboard' => 'Barcodes Overview',
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
        const randomIcon = document.querySelector('.fa-arrows-rotate');
        if (randomIcon) {
            randomIcon.parentElement.style.cursor = 'pointer';
            randomIcon.parentElement.addEventListener('click', function() {
                alert('A random number will be generated for the lost barcode when you submit the form.');
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
    $errors = [];
    $duplicates = [];
    if (!$of_number) $errors[] = "OF number is required";
    if (!$generate_costume_2pcs && !$generate_costume_3pcs && !$piece_name) {
        $errors[] = "Piece name is required";
    }
    if ($is_lost_barcode) {
        $lost_barcode_count = (int)($_POST['lost_barcode_count'] ?? 1);
        if ($lost_barcode_count <= 0 || $lost_barcode_count > 100) $errors[] = "Lost barcode quantity must be between 1 and 100";
    } else {
        $range_from = (int)($_POST['range_from'] ?? 0);
        $range_to = (int)($_POST['range_to'] ?? 0);
        if ($range_from <= 0 || $range_to <= 0 || $range_from > $range_to) $errors[] = "Invalid range";
    }
    if (empty($errors)) {
        $conn = connectDB();
        if ($conn) {
            $generator = new BarcodeGeneratorPNG();
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(false);
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
            $col = 0;
            $row = 0;
            $successCount = 0;
            if ($is_lost_barcode) {
                $lost_barcode_count = (int)($_POST['lost_barcode_count'] ?? 1);
                $lost_barcode_number = rand(1, 1000);
                for ($i = 0; $i < $lost_barcode_count; $i++) {
                    $formatted_number = "X" . $lost_barcode_number;
                    $full_barcode_name = formatBarcodeString($of_number, $size, $category, $piece_name, $formatted_number);
                    if (!$generate_pdf_only) {
                        if (barcodeExists($conn, $full_barcode_name)) {
                            $duplicates[] = $full_barcode_name;
                            $lost_barcode_number++;
                            continue;
                        }
                        $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                        $stmt->bind_param("ssssss", $of_number, $size, $category, $piece_name, $formatted_number, $full_barcode_name); // Changed type 'i' to 's' for size parameter
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
                    $lost_barcode_number++;
                }
            } else if ($generate_costume_2pcs || $generate_costume_3pcs) {
                $pieces = $generate_costume_2pcs ? ['P', 'V'] : ['P', 'V', 'G'];
                $range_from = (int)$_POST['range_from'];
                $range_to = (int)$_POST['range_to'];
                $total_barcodes = count($pieces) * ($range_to - $range_from + 1);
                $barcode_counter = 0;
                foreach ($pieces as $current_piece) {
                    for ($i = $range_from; $i <= $range_to; $i++) {
                        $full_barcode_name = formatBarcodeString($of_number, $size, $category, $current_piece, $i);
                        if (!$generate_pdf_only) {
                            if (barcodeExists($conn, $full_barcode_name)) {
                                $duplicates[] = $full_barcode_name;
                                continue;
                            }
                            $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                            $stmt->bind_param("ssssss", $of_number, $size, $category, $current_piece, $i, $full_barcode_name); // Changed type 'i' to 's' for size parameter
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
                }
            } else {
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
                        $stmt->bind_param("ssssss", $of_number, $size, $category, $piece_name, $i, $full_barcode_name); // Changed type 'i' to 's' for size parameter
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
            }
            if ($generate_pdf_only) {
                $pdfFilename = "{$of_number}-{$size}{$category}-pdfonly.pdf";
            } elseif ($is_lost_barcode) {
                $pdfFilename = "{$of_number}-{$size}{$category}-RTC.pdf";
            } elseif ($generate_costume_2pcs) {
                $pdfFilename = "{$of_number}-{$size}{$category}-PV.pdf";
            } elseif ($generate_costume_3pcs) {
                $pdfFilename = "{$of_number}-{$size}{$category}-PVG.pdf";
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
            $conn->close();
            if (!empty($duplicates)) {
                $duplicate_message = "The following barcodes already exist: " . implode(", ", array_slice($duplicates, 0, 5));
                if (count($duplicates) > 5) {
                    $duplicate_message .= " and " . (count($duplicates) - 5) . " more";
                }
                if ($successCount > 0) {
                    $message = "$successCount barcodes created successfully ($actualPages pages). $duplicate_message";
                    header("Location: index.php?view=dashboard&modal=create&warning=" . urlencode($message) . "&pdf=$pdfFilename");
                } else {
                    header("Location: index.php?view=$form_view&modal=create&error=" . urlencode($duplicate_message));
                }
                exit;
            } else {
                $success_message = "PDF created with $actualPages pages";
                header("Location: index.php?view=dashboard&modal=create&success=1&pdf=$pdfFilename&info=" . urlencode($success_message));
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