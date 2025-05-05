<?php
require_once __DIR__ . '/vendor/autoload.php'; // For FPDF and Barcode Generator

use Picqer\Barcode\BarcodeGeneratorPNG;
$pdf = new FPDF();

// Initialize variables
$current_view = $_GET['view'] ?? 'dashboard';
$current_date = date("F j, Y");
$page = $_GET['page'] ?? 1;
$items_per_page = 2000;
$date = $_GET['date'] ?? $current_date;

// Initialize filter variables
$filter_of_number = $_GET['filter_of'] ?? '';
$filter_size = $_GET['filter_size'] ?? '';
$filter_category = $_GET['filter_category'] ?? '';
$filter_piece_name = $_GET['filter_piece_name'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

// Establish database connection
function connectDB() {
    $conn = new mysqli("localhost", "root", "", "jgr");
    return $conn->connect_error ? false : $conn;
}

function getBarcodes($view, $page, $items_per_page, $filter_of = '', $filter_size = '', $filter_category = '', $filter_piece_name = '', $filter_date = '') {
    $conn = connectDB();
    if (!$conn) return [];

    $offset = ($page - 1) * $items_per_page;
    
    // Build WHERE clause based on view and filters
    $conditions = [];
    
    // View conditions
    if ($view === 'today') {
        $conditions[] = "DATE(last_update) = CURDATE()";
    } elseif ($view === 'manufactured') {
        $conditions[] = "status = 'Completed'";
    }
    
    // Filter conditions
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
    
    // Add date filter condition
    if (!empty($filter_date)) {
        $conditions[] = "DATE(last_update) = ?";
    }
    
    // Combine conditions
    $where = '';
    if (!empty($conditions)) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }

    $sql = "SELECT * FROM barcodes $where ORDER BY id DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters based on filters
    $types = "";
    $params = [];
    
    if (!empty($filter_of)) {
        $filter_of_param = "%$filter_of%"; // For LIKE search
        $types .= "s";
        $params[] = $filter_of_param;
    }
    
    if (!empty($filter_size)) {
        $types .= "i";
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
    
    // Add date parameter
    if (!empty($filter_date)) {
        $types .= "s";
        $params[] = $filter_date;
    }
    
    // Add limit parameters
    $types .= "ii";
    $params[] = $offset;
    $params[] = $items_per_page;
    
    // Dynamically bind parameters
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

    // Build WHERE clause based on view and filters
    $conditions = [];
    
    // View conditions
    if ($view === 'today') {
        $conditions[] = "DATE(last_update) = CURDATE()";
    } elseif ($view === 'manufactured') {
        $conditions[] = "status = 'Completed'";
    }
    
    // Filter conditions
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
    
    // Add date filter condition
    if (!empty($filter_date)) {
        $conditions[] = "DATE(last_update) = ?";
    }
    
    // Combine conditions
    $where = '';
    if (!empty($conditions)) {
        $where = "WHERE " . implode(" AND ", $conditions);
    }

    $sql = "SELECT COUNT(*) as total FROM barcodes $where";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters based on filters
    $types = "";
    $params = [];
    
    if (!empty($filter_of)) {
        $filter_of_param = "%$filter_of%"; // For LIKE search
        $types .= "s";
        $params[] = $filter_of_param;
    }
    
    if (!empty($filter_size)) {
        $types .= "i";
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
    
    // Add date parameter
    if (!empty($filter_date)) {
        $types .= "s";
        $params[] = $filter_date;
    }
    
    // Dynamically bind parameters
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

// Helper function to place a barcode in the PDF
function placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, 
                         $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage) {
    // Generate barcode image
    $barcodeImage = $generator->getBarcode($full_barcode_name, $generator::TYPE_CODE_128);
    $barcodePath = __DIR__ . "/barcodes/tmp_$full_barcode_name.png";
    file_put_contents($barcodePath, $barcodeImage);
    
    // Calculate positions
    $x = $col * $cellWidth;
    $y = $row * $cellHeight;
    
    // Center the barcode in the cell and add the top spacing
    $barcodeX = $x + (($cellWidth - $barcodeWidth) / 2);
    $barcodeY = $y + $topSpacing + ($cellHeight / 2) - ($barcodeHeight / 2) - 5;
    
    // Add barcode image
    $pdf->Image($barcodePath, $barcodeX, $barcodeY, $barcodeWidth, $barcodeHeight);
    
    // Add barcode text below the image
    $pdf->SetFont('Arial', '', $fontSize);
    $textY = $barcodeY + $barcodeHeight + 2;
    $pdf->SetXY($x, $textY);
    $pdf->Cell($cellWidth, 5, $full_barcode_name, 0, 0, 'C');
    
    // Draw vertical lines (except after the last column)
    if ($col < $colsPerPage - 1) { 
        $lineX = $x + $cellWidth;
        $pdf->Line($lineX, $y, $lineX, $y + $cellHeight);
    }
    
    // Draw horizontal lines (except after the last row)
    if ($row < $rowsPerPage - 1) {
        $lineY = $y + $cellHeight;
        $pdf->Line(0, $lineY, $pageWidth, $lineY);
    }
    
    // Clean up temporary file
    unlink($barcodePath);
    
    // Move to next position and return updated column
    $col++;
    if ($col >= $colsPerPage) {
        $col = 0;
    }
    
    return $col;
}

// Add JavaScript for handling the "Random" functionality
function getRandomButtonScript() {
    return <<<SCRIPT
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Find the Random icon and make it clickable
        const randomIcon = document.querySelector('.fa-arrows-rotate');
        if (randomIcon) {
            randomIcon.parentElement.style.cursor = 'pointer';
            randomIcon.parentElement.addEventListener('click', function() {
                // Since we removed the field, just show a small notification that a random number will be generated
                alert('A random number will be generated for the lost barcode when you submit the form.');
            });
        }
        
        // Clear filters button functionality
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

// Helper function to format barcode name with optional category
function formatBarcodeString($of_number, $size, $category, $piece_name, $number) {
    // If category is empty, don't include it in the barcode string
    if (empty($category)) {
        return "$of_number-$size-$piece_name-$number";
    } else {
        return "$of_number-$size$category-$piece_name-$number";
    }
}

// NEW FUNCTION: Check if a barcode already exists in the database
function barcodeExists($conn, $full_barcode_name) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM barcodes WHERE full_barcode_name = ?");
    $stmt->bind_param("s", $full_barcode_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

// Handle barcode creation and PDF generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_barcode') {
    $of_number = $_POST['barcode_prefix'] ?? '';
    $size = (int)($_POST['barcode_size'] ?? 0);
    $category = $_POST['barcode_category'] ?? '';
    $piece_name = $_POST['barcode_piece_name'] ?? '';
    $form_view = $_POST['view'] ?? 'dashboard';
    $is_lost_barcode = isset($_POST['lost_barcode']);
    $generate_costume_2pcs = isset($_POST['generate_costume_2pcs']);
    $generate_costume_3pcs = isset($_POST['generate_costume_3pcs']);
    $generate_pdf_only = isset($_POST['generate_pdf_only']);

    $errors = [];
    $duplicates = []; // NEW: Array to track duplicate barcodes

    if (!$of_number) $errors[] = "OF number is required";
    if ($size <= 0) $errors[] = "Size must be positive";
    
    // Only validate piece name if not generating multiple pieces
    if (!$generate_costume_2pcs && !$generate_costume_3pcs && !$piece_name) {
        $errors[] = "Piece name is required";
    }
    
    // Different validation based on modes
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

            // Layout settings
            $colsPerPage = 3;
            $rowsPerPage = 5;
            $pageWidth = 210;  // A4 width in mm
            $pageHeight = 297; // A4 height in mm
            
            // Calculate cell dimensions
            $cellWidth = $pageWidth / $colsPerPage;
            $cellHeight = $pageHeight / $rowsPerPage;
            
            // Barcode settings
            $barcodeWidth = 50;
            $barcodeHeight = 20;
            $topSpacing = 12;
            $fontSize = 14;
            
            $col = 0;
            $row = 0;
            $successCount = 0; // NEW: Counter for successfully created barcodes
            
            // Process differently based on mode
            if ($is_lost_barcode) {
                // Generate user-specified quantity of lost barcodes with 'X' prefix
                $lost_barcode_count = (int)($_POST['lost_barcode_count'] ?? 1);
                
                // Generate a random starting number between 1 and 1000
                $lost_barcode_number = rand(1, 1000);
                
                // Generate the requested number of lost barcodes
                for ($i = 0; $i < $lost_barcode_count; $i++) {
                    // Add "X" before the number for lost barcodes
                    $formatted_number = "X" . $lost_barcode_number;
                    $full_barcode_name = formatBarcodeString($of_number, $size, $category, $piece_name, $formatted_number);
                    
                    // Insert into database
                    if (!$generate_pdf_only) {
                        // Check if barcode already exists
                        if (barcodeExists($conn, $full_barcode_name)) {
                            $duplicates[] = $full_barcode_name;
                            $lost_barcode_number++; // Move to next number even if duplicate
                            continue;
                        }
                        
                        $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                        $stmt->bind_param("sissss", $of_number, $size, $category, $piece_name, $formatted_number, $full_barcode_name);
                        $stmt->execute();
                        $successCount++;
                    }
                    
                    // Generate and place barcode in PDF
                    $col = placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, 
                                            $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage);
                    
                    // Update row and page if needed
                    if ($col == 0) {
                        $row++;
                        if ($row >= $rowsPerPage) {
                            $row = 0;
                            $pdf->AddPage();
                        }
                    }
                    
                    // Increment the lost barcode number for each barcode
                    $lost_barcode_number++;
                }
            } else if ($generate_costume_2pcs || $generate_costume_3pcs) {
                // Define which piece types to generate
                $pieces = $generate_costume_2pcs ? ['P', 'V'] : ['P', 'V', 'G'];
                $range_from = (int)$_POST['range_from'];
                $range_to = (int)$_POST['range_to'];
                
                // For each piece type
                foreach ($pieces as $current_piece) {
                    // For each number in the range
                    for ($i = $range_from; $i <= $range_to; $i++) {
                        $full_barcode_name = formatBarcodeString($of_number, $size, $category, $current_piece, $i);
                        
                        // Insert into database only if not PDF-only mode
                        if (!$generate_pdf_only) {
                            // Check if barcode already exists
                            if (barcodeExists($conn, $full_barcode_name)) {
                                $duplicates[] = $full_barcode_name;
                                continue;
                            }
                            
                            $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                            $stmt->bind_param("sissss", $of_number, $size, $category, $current_piece, $i, $full_barcode_name);
                            $stmt->execute();
                            $successCount++;
                        }
                        
                        // Generate and place barcode in PDF
                        $col = placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, 
                                                $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage);
                        
                        // Update row and page if needed
                        if ($col == 0) {
                            $row++;
                            if ($row >= $rowsPerPage) {
                                $row = 0;
                                $pdf->AddPage();
                            }
                        }
                    }
                }
            } else {
                // Original code for processing a range of barcodes for a single piece type
                $range_from = (int)$_POST['range_from'];
                $range_to = (int)$_POST['range_to'];
                
                for ($i = $range_from; $i <= $range_to; $i++) {
                    $full_barcode_name = formatBarcodeString($of_number, $size, $category, $piece_name, $i);
                    
                    // Insert into database only if not PDF-only mode
                    if (!$generate_pdf_only) {
                        // Check if barcode already exists
                        if (barcodeExists($conn, $full_barcode_name)) {
                            $duplicates[] = $full_barcode_name;
                            continue;
                        }
                        
                        $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                        $stmt->bind_param("sissss", $of_number, $size, $category, $piece_name, $i, $full_barcode_name);
                        $stmt->execute();
                        $successCount++;
                    }
                    
                    // Generate and place barcode in PDF
                    $col = placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, 
                                            $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage);
                    
                    // Update row and page if needed
                    if ($col == 0) {
                        $row++;
                        if ($row >= $rowsPerPage) {
                            $row = 0;
                            $pdf->AddPage();
                        }
                    }
                }
            }
            
            // Create a filename using barcode information
            if ($generate_pdf_only) {
                // For PDF-only mode
                $pdfFilename = "{$of_number}-{$size}{$category}-pdfonly.pdf";
            } elseif ($is_lost_barcode) {
                // For lost barcodes
                $pdfFilename = "{$of_number}-{$size}{$category}-RTC.pdf";
            } elseif ($generate_costume_2pcs) {
                // For 2-piece costume (P and V)
                $pdfFilename = "{$of_number}-{$size}{$category}-PV.pdf";
            } elseif ($generate_costume_3pcs) {
                // For 3-piece costume (P, V, and G)
                $pdfFilename = "{$of_number}-{$size}{$category}-PVG.pdf";
            } else {
                // Regular barcode with single piece
                $pdfFilename = "{$of_number}-{$size}{$category}-{$piece_name}.pdf";
            }

            // Sanitize filename to remove any invalid characters
            $pdfFilename = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $pdfFilename);

            // Make sure we have a valid filename
            if (empty($pdfFilename) || $pdfFilename == ".pdf") {
                $randomNumber = rand(10000, 99999);
                $pdfFilename = "A{$randomNumber}.pdf";
            }

            $pdf->Output('F', __DIR__ . "/barcodes/$pdfFilename");  
                      
            $conn->close();
            
            // Handle redirects based on results
            if (!empty($duplicates)) {
                $duplicate_message = "The following barcodes already exist: " . implode(", ", array_slice($duplicates, 0, 5));
                if (count($duplicates) > 5) {
                    $duplicate_message .= " and " . (count($duplicates) - 5) . " more";
                }
                
                if ($successCount > 0) {
                    // Some succeeded, some failed
                    $message = "$successCount barcodes created successfully. $duplicate_message";
                    header("Location: index.php?view=dashboard&modal=create&warning=" . urlencode($message) . "&pdf=$pdfFilename");
                } else {
                    // All failed
                    header("Location: index.php?view=$form_view&modal=create&error=" . urlencode($duplicate_message));
                }
                exit;
            } else {
                // All succeeded
                header("Location: index.php?view=dashboard&modal=create&success=1&pdf=$pdfFilename");
                exit;
            }
        }
    } else {
        header("Location: index.php?view=$form_view&modal=create&error=" . urlencode(implode(", ", $errors)));
        exit;
    }
}

// Load barcodes for current view
$barcodes = getBarcodes($current_view, $page, $items_per_page, $filter_of_number, $filter_size, $filter_category, $filter_piece_name, $filter_date);
$total_barcodes = getTotalBarcodes($current_view, $filter_of_number, $filter_size, $filter_category, $filter_piece_name, $filter_date);
$total_pages = ceil($total_barcodes / $items_per_page);
$show_success = isset($_GET['success']) && $_GET['success'] == 1;
$error_message = $_GET['error'] ?? '';
$warning_message = $_GET['warning'] ?? ''; // NEW: Add support for warning messages
$show_modal = isset($_GET['modal']) && $_GET['modal'] === 'create';
//made by Akram Fouzi
// Add the random button script to be included in the page
$random_button_script = getRandomButtonScript();
?>