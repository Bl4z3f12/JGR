<?php
require_once __DIR__ . '/vendor/autoload.php'; // Make sure FPDF and BarcodeGeneratorPNG are installed
$pdf = new FPDF();
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodePDF extends FPDF
{
    protected $generator;
    
    function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->generator = new BarcodeGeneratorPNG();
    }
    function Checkbox($x, $y, $size = 3)
    {
        $this->Rect($x, $y, $size, $size);
    }
    function processBarcodeRanges($barcode_ranges, $colsPerPage = 5, $rowsPerPage = 45)
    {
        // First, create an array with size info and barcodes for sorting
        $size_groups = [];
        foreach ($barcode_ranges as $barcodes) {
            if (!empty($barcodes)) {
                // Extract size from the first barcode in the range
                $firstBarcode = $barcodes[0];
                $lastBarcode = end($barcodes);
                
                // Parse the first barcode to get size info
                $parts = explode('-', $firstBarcode);
                $sizeWithCategory = $parts[1]; // This will be like "1", "2R", "3CC", etc.
                
                // Extract numeric part for sorting
                preg_match('/^(\d+)/', $sizeWithCategory, $matches);
                $numericSize = isset($matches[1]) ? (int)$matches[1] : 0;
                
                // Extract start and end numbers correctly
                $firstParts = explode('-', $firstBarcode);
                $startNumber = (int)end($firstParts); // Get the last part which is the number
                
                $lastParts = explode('-', $lastBarcode);
                $endNumber = (int)end($lastParts); // Get the last part which is the number
                
                $size_groups[] = [
                    'size_display' => $sizeWithCategory,
                    'numeric_size' => $numericSize,
                    'start_number' => $startNumber,
                    'end_number' => $endNumber,
                    'barcodes' => $barcodes,
                    'header' => "SIZE_HEADER:Taille: " . $sizeWithCategory . " | " . $startNumber . " - " . $endNumber
                ];
            }
        }
        
        // Sort by start number first (smallest to largest), then by numeric size
        usort($size_groups, function($a, $b) {
            if ($a['start_number'] == $b['start_number']) {
                return $a['numeric_size'] - $b['numeric_size'];
            }
            return $a['start_number'] - $b['start_number'];
        });
        
        // Flatten sorted groups into a single array with size headers
        $all_barcodes = [];
        foreach ($size_groups as $group) {
            // Add size header with range
            $all_barcodes[] = $group['header'];
            
            // Add all barcodes for this size
            $all_barcodes = array_merge($all_barcodes, $group['barcodes']);
        }
        
        // Create one continuous table with all barcodes
        $this->createBarcodeTable($all_barcodes, $colsPerPage, $rowsPerPage);
    }
    
    function createBarcodeTable($data, $colsPerPage = 5, $rowsPerPage = 45)
    {
        $pageWidth = 210; // A4 width in mm
        $pageHeight = 297; // A4 height in mm
        $leftMargin = 5;
        $rightMargin = 5;
        $topMargin = 5;
        $bottomMargin = 5;
        $usableWidth = $pageWidth - $leftMargin - $rightMargin;
        $usableHeight = $pageHeight - $topMargin - $bottomMargin;
        $cellWidth = $usableWidth / $colsPerPage;
        $cellHeight = $usableHeight / $rowsPerPage;
        $this->SetMargins($leftMargin, $topMargin, $rightMargin);
        $this->SetAutoPageBreak(true, $bottomMargin);
        $this->SetFont('Arial', '', 8);
        $totalItems = count($data);
        $itemsPerPage = $colsPerPage * $rowsPerPage;
        
        // Calculate how many full columns we need
        $fullColumns = floor($totalItems / $rowsPerPage);
        $remainingItems = $totalItems % $rowsPerPage;
        
        // Process full columns
        for ($col = 0; $col < $fullColumns; $col++) {
            $currentColOnPage = $col % $colsPerPage;
            // Add new page if current column is the first column of a new page (except the first column)
            if ($currentColOnPage == 0 && $col != 0) {
                $this->AddPage();
            }
            for ($row = 0; $row < $rowsPerPage; $row++) {
                $itemIndex = $col * $rowsPerPage + $row;
                $x = $leftMargin + ($currentColOnPage * $cellWidth);
                $y = $topMargin + ($row * $cellHeight);
                
                $this->Rect($x, $y, $cellWidth, $cellHeight);
                
                // Check if this is a size header
                if (strpos($data[$itemIndex], 'SIZE_HEADER:') === 0) {
                    $headerText = substr($data[$itemIndex], 12); // Remove "SIZE_HEADER:" prefix
                    $this->SetFillColor(200, 200, 200); // Gray background
                    $this->Rect($x, $y, $cellWidth, $cellHeight, 'F'); // Fill rectangle with gray
                    $this->Rect($x, $y, $cellWidth, $cellHeight); // Draw border
                    $this->SetFont('Arial', 'B', 10); // Bold font for header
                    $textX = $x + 3;
                    $this->SetXY($textX, $y + ($cellHeight/2) - 2);
                    $this->Cell($cellWidth - 6, 4, $headerText, 0, 0, 'C');
                    $this->SetFont('Arial', '', 8); // Reset to normal font
                } else {
                    $checkboxSize = 3;
                    $checkboxX = $x + $cellWidth - $checkboxSize - 3; // Position checkbox with padding from right
                    $checkboxY = $y + ($cellHeight/2) - ($checkboxSize/2);
                    $this->Checkbox($checkboxX, $checkboxY, $checkboxSize);
                    $textX = $x + 3; // Position text with padding from left
                    $this->SetXY($textX, $y + ($cellHeight/2) - 1.5);
                    $textWidth = $cellWidth - ($checkboxSize + 8);
                    $this->Cell($textWidth, 3, $data[$itemIndex], 0, 0, 'C');
                }
            }
        }
        
        // Process remaining items in the last partial column
        if ($remainingItems > 0) {
            $lastCol = $fullColumns;
            $currentColOnPage = $lastCol % $colsPerPage;
            // Add new page if current column is the first column of a new page (except the first column)
            if ($currentColOnPage == 0 && $lastCol != 0) {
                $this->AddPage();
            }
            for ($row = 0; $row < $remainingItems; $row++) {
                $itemIndex = $fullColumns * $rowsPerPage + $row;
                $x = $leftMargin + ($currentColOnPage * $cellWidth);
                $y = $topMargin + ($row * $cellHeight);
                
                $this->Rect($x, $y, $cellWidth, $cellHeight);
                
                // Check if this is a size header
                if (strpos($data[$itemIndex], 'SIZE_HEADER:') === 0) {
                    $headerText = substr($data[$itemIndex], 12); // Remove "SIZE_HEADER:" prefix
                    $this->SetFillColor(200, 200, 200); // Gray background
                    $this->Rect($x, $y, $cellWidth, $cellHeight, 'F'); // Fill rectangle with gray
                    $this->Rect($x, $y, $cellWidth, $cellHeight); // Draw border
                    $this->SetFont('Arial', 'B', 10); // Bold font for header
                    $textX = $x + 3;
                    $this->SetXY($textX, $y + ($cellHeight/2) - 2);
                    $this->Cell($cellWidth - 6, 4, $headerText, 0, 0, 'C');
                    $this->SetFont('Arial', '', 8); // Reset to normal font
                } else {
                    $checkboxSize = 3;
                    $checkboxX = $x + $cellWidth - $checkboxSize - 3;
                    $checkboxY = $y + ($cellHeight/2) - ($checkboxSize/2);
                    $this->Checkbox($checkboxX, $checkboxY, $checkboxSize);
                    $textX = $x + 3;
                    $this->SetXY($textX, $y + ($cellHeight/2) - 1.5);
                    $textWidth = $cellWidth - ($checkboxSize + 8);
                    $this->Cell($textWidth, 3, $data[$itemIndex], 0, 0, 'C');
                }
            }
        }
    }
}

// Function to check for missing numbers in ranges
function checkForMissingNumbers($size_ranges) {
    if (empty($size_ranges) || count($size_ranges) < 2) {
        return null; // No gaps possible with less than 2 ranges
    }
    
    // Collect all ranges with their start and end numbers
    $ranges = [];
    foreach ($size_ranges as $index => $range) {
        if (isset($range['start']) && isset($range['end']) && 
            !empty($range['start']) && !empty($range['end'])) {
            $start = (int)$range['start'];
            $end = (int)$range['end'];
            if ($start <= $end) {
                $ranges[] = ['start' => $start, 'end' => $end, 'index' => $index];
            }
        }
    }
    
    if (count($ranges) < 2) {
        return null; // Need at least 2 valid ranges to check for gaps
    }
    
    // Sort ranges by start number
    usort($ranges, function($a, $b) {
        return $a['start'] - $b['start'];
    });
    
    $missing_ranges = [];
    
    // Check for gaps between consecutive ranges
    for ($i = 0; $i < count($ranges) - 1; $i++) {
        $current_end = $ranges[$i]['end'];
        $next_start = $ranges[$i + 1]['start'];
        
        // If there's a gap (next start is more than current end + 1)
        if ($next_start > $current_end + 1) {
            $gap_start = $current_end + 1;
            $gap_end = $next_start - 1;
            $missing_ranges[] = [
                'start' => $gap_start,
                'end' => $gap_end,
                'after_range' => $i,
                'before_range' => $i + 1
            ];
        }
    }
    
    return $missing_ranges;
}

// Start session for success message functionality
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check for success message from previous PDF generation
$show_success = false;
$pdf_filename = '';
if (isset($_SESSION['pdf_generated']) && $_SESSION['pdf_generated']) {
    $show_success = true;
    $pdf_filename = isset($_SESSION['pdf_filename']) ? $_SESSION['pdf_filename'] : '';
    // Clear the session variables
    $_SESSION['pdf_generated'] = false;
    $_SESSION['pdf_filename'] = '';
}

$prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '';
$size = isset($_POST['size']) ? $_POST['size'] : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';
$piece_type = isset($_POST['piece_type']) ? $_POST['piece_type'] : '';
$start_number = isset($_POST['start_number']) ? (int)$_POST['start_number'] : '';
$end_number = isset($_POST['end_number']) ? (int)$_POST['end_number'] : '';
$generate = isset($_POST['generate']) ? true : false;
$columns = 5;
$rows_per_page = 45;
$category_options = ['','R', 'C', 'L', 'LL', 'CC', 'N'];
$piece_type_options = ['', 'P', 'V', 'G', 'M'];
// Multiple sizes is now enabled by default - no checkbox needed
$sizes = isset($_POST['sizes']) ? $_POST['sizes'] : [];
$size_ranges = isset($_POST['size_ranges']) ? $_POST['size_ranges'] : [];

// Check for missing numbers warning
$missing_numbers_warning = null;
if (!empty($size_ranges)) {
    $missing_ranges = checkForMissingNumbers($size_ranges);
    if ($missing_ranges) {
        $missing_numbers_warning = $missing_ranges;
    }
}

function formatBarcodeString($prefix, $size, $category, $piece_type, $number) {
    $formatted_number = sprintf("%d", $number);
    if (empty($category)) {
        return "$prefix-$size-$piece_type-$formatted_number";
    } else {
        return "$prefix-$size$category-$piece_type-$formatted_number";
    }
}

if ($generate) {
    $barcode_ranges = [];
    
    if (!empty($sizes)) {
        foreach ($sizes as $index => $size_value) {
            if (empty($size_value)) continue;
            $size_start = isset($size_ranges[$index]['start']) ? (int)$size_ranges[$index]['start'] : 1;
            $size_end = isset($size_ranges[$index]['end']) ? (int)$size_ranges[$index]['end'] : 225;
            
            $range_barcodes = [];
            for ($i = $size_start; $i <= $size_end; $i++) {
                $range_barcodes[] = formatBarcodeString($prefix, $size_value, $category, $piece_type, $i);
            }
            
            if (!empty($range_barcodes)) {
                $barcode_ranges[] = $range_barcodes;
            }
        }
    } else {
        // Fallback to single size if no sizes were defined
        $range_barcodes = [];
        
        // Use default values if fields are empty
        $default_start = empty($start_number) ? 1 : $start_number;
        $default_size = empty($size) ? '1' : $size;
        
        for ($i = $default_start; $i <= $default_end; $i++) {
            $range_barcodes[] = formatBarcodeString($prefix, $default_size, $category, $piece_type, $i);
        }
        
        if (!empty($range_barcodes)) {
            $barcode_ranges[] = $range_barcodes;
        }
    }
    
    $pdf = new BarcodePDF();
    $pdf->AddPage();
    $pdf->processBarcodeRanges($barcode_ranges, $columns, $rows_per_page);
    
    // Define the track files directory
    $track_files_dir = __DIR__ . '/track files';
    
    // Create the directory if it doesn't exist
    if (!is_dir($track_files_dir)) {
        mkdir($track_files_dir, 0755, true);
    }
    
    // Generate filename with user data
    $filename_parts = [];
    $filename_parts[] = $prefix; // OF Number

    // Add category if not empty
    if (!empty($category)) {
        $filename_parts[] = $category;
    }

    // Add piece type if not empty  
    if (!empty($piece_type)) {
        $filename_parts[] = $piece_type;
    }

    // Create filename: ofnumber-category-piecetype.pdf (or ofnumber.pdf if no category/piece type)
    $filename = implode('-', $filename_parts) . '.pdf';
    $filepath = $track_files_dir . '/' . $filename;  
      
    // Save the PDF to the track files directory
    $pdf->Output('F', $filepath);
    
    // Set a session variable to indicate success for the next page load
    $_SESSION['pdf_generated'] = true;
    $_SESSION['pdf_filename'] = $filename;
    
    // Redirect back to the tracker page to show the success message
    header("Location: tracker.php?success=true");
    exit;
}

require_once 'auth_functions.php';
requireLogin('login.php');

$allowed_ips = ['127.0.0.1', '192.168.1.130', '::1', '192.168.0.120' ,'NEW_IP_HERE'];
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$client_ip = trim(explode(',', $client_ip)[0]);
$is_localhost = in_array($client_ip, ['127.0.0.1', '::1']) || 
               stripos($_SERVER['HTTP_HOST'], 'localhost') !== false;

if (!$is_localhost && !in_array($client_ip, $allowed_ips)) {
    require_once 'die.php';
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode List Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="assets/tracker.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="content">
            <div class="container-fluid">
                <?php if ($show_success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    PDF file <strong><?php echo htmlspecialchars($pdf_filename); ?></strong> was successfully generated!
                    <div class="mt-2">
                        <a href="track files/<?php echo urlencode($pdf_filename); ?>" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i> View PDF
                        </a>
                        <a href="tracker_open_path.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-folder-open me-1"></i> Browse Files
                        </a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <h4 class="mb-4" style="font-size: 18px;">Barcode List Generator</h4>

                <div class="card-body">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="prefix" class="form-label">OF Number:</label>
                                <input type="number" class="form-control" id="prefix" name="prefix" value="<?php echo htmlspecialchars($prefix); ?>" required>
                            </div>
                            <div class="col-md-3 mt-2">
                                <label for="size" class="form-label mb-0">Size:</label>
                                <div class="size-container">
                                    <?php 
                                    if (!empty($sizes)) {
                                        foreach ($sizes as $index => $size_val) {
                                            $start_val = isset($size_ranges[$index]['start']) ? htmlspecialchars($size_ranges[$index]['start']) : $start_number;
                                            $end_val = isset($size_ranges[$index]['end']) ? htmlspecialchars($size_ranges[$index]['end']) : $end_number;
                                            
                                            echo '<div class="size-group mb-3">
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text">Size</span>
                                                    <input type="text" class="form-control" name="sizes[]" value="' . htmlspecialchars($size_val) . '">
                                                    <button type="button" class="btn btn-outline-danger remove-size"><i class="fas fa-times"></i></button>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text">Start</span>
                                                            <input type="number" class="form-control size-start" name="size_ranges[' . $index . '][start]" value="' . $start_val . '" min="1">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text">End</span>
                                                            <input type="number" class="form-control size-end" name="size_ranges[' . $index . '][end]" value="' . $end_val . '" min="1">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>';
                                        }
                                    } else {
                                        echo '<div class="size-group mb-3">
                                            <div class="input-group mb-2">
                                                <span class="input-group-text">Size</span>
                                                <input type="number" class="form-control" name="sizes[]" value="">
                                                <button type="button" class="btn btn-outline-danger remove-size"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text">Start</span>
                                                        <input type="number" class="form-control size-start" name="size_ranges[0][start]" value="" min="1">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text">End</span>
                                                        <input type="number" class="form-control size-end" name="size_ranges[0][end]" value="" min="1">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';
                                    }
                                    ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-size">
                                    <i class="fas fa-plus"></i> Add Size
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label for="category" class="form-label">Category:</label>
                                <select class="form-select" id="category" name="category">
                                    <?php foreach ($category_options as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo $category === $option ? 'selected' : ''; ?>>
                                            <?php echo empty($option) ? '(None)' : $option; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="piece_type" class="form-label">Piece Type:</label>
                                <select class="form-select" id="piece_type" name="piece_type">
                                    <?php foreach ($piece_type_options as $option): ?>
                                        <option value="<?php echo $option; ?>" <?php echo $piece_type === $option ? 'selected' : ''; ?>>
                                            <?php echo empty($option) ? '(None)' : $option; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex">
                                    <button type="submit" name="generate" value="1" class="btn btn-primary me-2">
                                        <i class="fas fa-file-pdf me-1"></i> Generate PDF
                                    </button>
                                    <button type="button" id="open-path" class="btn btn-secondary">
                                        <i class="fas fa-folder-open me-1"></i> Open Path
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="columns" value="5">
                            <input type="hidden" name="rows_per_page" value="45">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Auto-hide success alert after 10 seconds
            setTimeout(function() {
                $('.alert-success').fadeOut('slow');
            }, 10000);
            
            // Auto-hide warning alert after 15 seconds (but not the dynamic ones)
            setTimeout(function() {
                $('.alert-warning:not(#missing-numbers-warning)').fadeOut('slow');
            }, 15000);
            
            // Global variables to track timeouts
            var currentWarningTimeout = null;
            var currentDuplicateTimeout = null;
            
            // Check for missing numbers in ranges
            function checkForMissingNumbers() {
                var ranges = [];
                var hasValidInput = false;
                
                $('.size-group').each(function() {
                    var start = parseInt($(this).find('.size-start').val());
                    var end = parseInt($(this).find('.size-end').val());
                    var sizeValue = $(this).find('input[name="sizes[]"]').val();
                    
                    if (!isNaN(start) && !isNaN(end) && start <= end && sizeValue && sizeValue.trim() !== '') {
                        ranges.push({start: start, end: end});
                        hasValidInput = true;
                    }
                });
                
                // Clear any existing timeout
                if (currentWarningTimeout) {
                    clearTimeout(currentWarningTimeout);
                    currentWarningTimeout = null;
                }
                
                // Remove existing dynamic warning (but preserve server-side warnings)
                $('#missing-numbers-warning').remove();
                
                // Only check if we have at least 2 valid ranges and some valid input
                if (!hasValidInput || ranges.length < 2) {
                    return;
                }
                
                // Sort ranges by start number
                ranges.sort(function(a, b) {
                    return a.start - b.start;
                });
                
                var missingRanges = [];
                for (var i = 0; i < ranges.length - 1; i++) {
                    var currentEnd = ranges[i].end;
                    var nextStart = ranges[i + 1].start;
                    
                    if (nextStart > currentEnd + 1) {
                        var gapStart = currentEnd + 1;
                        var gapEnd = nextStart - 1;
                        missingRanges.push({start: gapStart, end: gapEnd});
                    }
                }
                
                if (missingRanges.length > 0) {
                    var warningHtml = '<div id="missing-numbers-warning" class="alert alert-warning alert-dismissible fade show mt-3" role="alert">' +
                        '<i class="fas fa-exclamation-triangle me-2"></i>' +
                        '<strong>Warning: Missing Numbers Detected</strong>' +
                        '<div class="mt-2">' +
                        '<p class="mb-2">There are gaps in your number sequence:</p>' +
                        '<ul class="mb-2">';
                    
                    missingRanges.forEach(function(missing) {
                        if (missing.start === missing.end) {
                            warningHtml += '<li>Number <strong class="text-danger">' + missing.start + '</strong> is missing</li>';
                        } else {
                            warningHtml += '<li>Numbers <strong class="text-danger">' + missing.start + '</strong> to <strong class="text-danger">' + missing.end + '</strong> are missing</li>';
                        }
                    });
                    
                    warningHtml += '</ul>' +
                        '<div class="alert alert-info mb-0 py-2">' +
                        '<small>' +
                        '<i class="fas fa-info-circle me-1"></i>' +
                        'This means some barcode numbers will be skipped in your generated list. ' +
                        'Consider adjusting your ranges to avoid gaps.' +
                        '</small>' +
                        '</div>' +
                        '</div>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
                    
                    // Insert the warning after the main content card
                    $('.card-body').after(warningHtml);
                    
                    // Auto-hide after 20 seconds
                    currentWarningTimeout = setTimeout(function() {
                        $('#missing-numbers-warning').fadeOut('slow', function() {
                            $(this).remove();
                        });
                    }, 20000);
                    
                    // Add click handler for manual dismiss
                    $('#missing-numbers-warning .btn-close').on('click', function() {
                        if (currentWarningTimeout) {
                            clearTimeout(currentWarningTimeout);
                            currentWarningTimeout = null;
                        }
                    });
                }
            }
            
            // Check for duplicate numbers across different sizes
            function checkForDuplicateNumbers() {
                var allNumbers = {};
                var duplicates = [];
                var hasValidInput = false;
                
                // Clear any existing timeout for duplicates
                if (currentDuplicateTimeout) {
                    clearTimeout(currentDuplicateTimeout);
                    currentDuplicateTimeout = null;
                }
                
                // Remove existing duplicate warning
                $('#duplicate-numbers-warning').remove();
                
                $('.size-group').each(function(index) {
                    var start = parseInt($(this).find('.size-start').val());
                    var end = parseInt($(this).find('.size-end').val());
                    var sizeValue = $(this).find('input[name="sizes[]"]').val();
                    
                    if (!isNaN(start) && !isNaN(end) && start <= end && sizeValue && sizeValue.trim() !== '') {
                        hasValidInput = true;
                        
                        // Check each number in the range
                        for (var num = start; num <= end; num++) {
                            if (allNumbers[num]) {
                                // This number already exists
                                var existingEntry = allNumbers[num];
                                var currentEntry = {
                                    size: sizeValue,
                                    index: index + 1
                                };
                                
                                // Check if this duplicate combination is already recorded
                                var duplicateExists = duplicates.some(function(dup) {
                                    return dup.number === num && 
                                        ((dup.size1 === existingEntry.size && dup.size2 === currentEntry.size) ||
                                        (dup.size1 === currentEntry.size && dup.size2 === existingEntry.size));
                                });
                                
                                if (!duplicateExists) {
                                    duplicates.push({
                                        number: num,
                                        size1: existingEntry.size,
                                        size2: currentEntry.size,
                                        index1: existingEntry.index,
                                        index2: currentEntry.index
                                    });
                                }
                            } else {
                                allNumbers[num] = {
                                    size: sizeValue,
                                    index: index + 1
                                };
                            }
                        }
                    }
                });
                
                // Only show warning if we have valid input and duplicates
                if (!hasValidInput || duplicates.length === 0) {
                    return;
                }
                
                // Create warning HTML
                var warningHtml = '<div id="duplicate-numbers-warning" class="alert alert-danger alert-dismissible fade show mt-3" role="alert">' +
                    '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    '<strong>Error: Duplicate Numbers Detected!</strong>' +
                    '<div class="mt-2">' +
                    '<p class="mb-2">The following numbers appear in multiple sizes:</p>' +
                    '<ul class="mb-2">';
                
                duplicates.forEach(function(duplicate) {
                    warningHtml += '<li>Number <strong class="text-danger">' + duplicate.number + '</strong> appears in both ' +
                                '<strong>Size ' + duplicate.size1 + '</strong> and <strong>Size ' + duplicate.size2 + '</strong></li>';
                });
                
                warningHtml += '</ul>' +
                    '<div class="alert alert-warning mb-0 py-2">' +
                    '<small>' +
                    '<i class="fas fa-exclamation-circle me-1"></i>' +
                    'Each barcode number should be unique across all sizes. Please adjust your ranges to eliminate duplicates.' +
                    '</small>' +
                    '</div>' +
                    '</div>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';
                
                // Insert the warning after the card body
                $('.card-body').after(warningHtml);
                
                // Auto-hide after 25 seconds
                currentDuplicateTimeout = setTimeout(function() {
                    $('#duplicate-numbers-warning').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 25000);
                
                // Add click handler for manual dismiss
                $('#duplicate-numbers-warning .btn-close').on('click', function() {
                    if (currentDuplicateTimeout) {
                        clearTimeout(currentDuplicateTimeout);
                        currentDuplicateTimeout = null;
                    }
                });
            }
            
            // Debounce function to prevent excessive calls
            function debounce(func, wait) {
                var timeout;
                return function executedFunction() {
                    var context = this;
                    var args = arguments;
                    var later = function() {
                        timeout = null;
                        func.apply(context, args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
            
            // Create debounced versions of check functions
            var debouncedCheck = debounce(checkForMissingNumbers, 500);
            var debouncedDuplicateCheck = debounce(checkForDuplicateNumbers, 500);
            
            // Attach event handlers to existing and new elements
            function attachEventHandlers() {
                $('.size-start, .size-end, input[name="sizes[]"]').off('input.missingNumbers input.duplicateNumbers')
                    .on('input.missingNumbers input.duplicateNumbers', function() {
                        debouncedCheck();
                        debouncedDuplicateCheck();
                    });
            }
            
            // Add size functionality
            $('#add-size').click(function() {
                var newIndex = $('.size-group').length;
                $('.size-container').append(
                    '<div class="size-group mb-3">' +
                    '<div class="input-group mb-2">' +
                    '<span class="input-group-text">Size</span>' +
                    '<input type="text" class="form-control" name="sizes[]" value="">' +
                    '<button type="button" class="btn btn-outline-danger remove-size"><i class="fas fa-times"></i></button>' +
                    '</div>' +
                    '<div class="row">' +
                    '<div class="col-6">' +
                    '<div class="input-group">' +
                    '<span class="input-group-text">Start</span>' +
                    '<input type="number" class="form-control size-start" name="size_ranges[' + newIndex + '][start]" value="" min="1">' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-6">' +
                    '<div class="input-group">' +
                    '<span class="input-group-text">End</span>' +
                    '<input type="number" class="form-control size-end" name="size_ranges[' + newIndex + '][end]" value="" min="1">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>'
                );
                attachEventHandlers();
            });
            
            // Remove size functionality
            $(document).on('click', '.remove-size', function() {
                if ($('.size-group').length > 1) {
                    $(this).closest('.size-group').remove();
                    updateIndexes();
                    debouncedCheck();
                    debouncedDuplicateCheck();
                }
            });
            
            // Update indexes after removing elements
            function updateIndexes() {
                $('.size-group').each(function(index) {
                    $(this).find('.size-start').attr('name', 'size_ranges[' + index + '][start]');
                    $(this).find('.size-end').attr('name', 'size_ranges[' + index + '][end]');
                });
            }
            
            // Initial event handler attachment
            attachEventHandlers();
            
            // Open path functionality
            $('#open-path').click(function() {
                window.open('tracker_open_path.php', '_blank');
            });
            
            // Enhanced form validation with better error messages and duplicate checking
            $('form').on('submit', function(e) {
                var errors = [];
                var prefix = $('#prefix').val();
                var hasValidSize = false;
                var allNumbers = {};
                var hasDuplicates = false;
                
                // Check prefix
                if (!prefix || prefix.trim() === '') {
                    errors.push('Please enter an OF Number.');
                }
                
                // Check sizes and duplicates
                $('.size-group').each(function(index) {
                    var sizeValue = $(this).find('input[name="sizes[]"]').val();
                    if (sizeValue && sizeValue.trim() !== '') {
                        hasValidSize = true;
                        
                        var start = parseInt($(this).find('.size-start').val());
                        var end = parseInt($(this).find('.size-end').val());
                        
                        if (isNaN(start) || start < 1) {
                            errors.push('Size ' + (index + 1) + ': Please enter a valid start number (minimum 1).');
                        }
                        if (isNaN(end) || end < 1) {
                            errors.push('Size ' + (index + 1) + ': Please enter a valid end number (minimum 1).');
                        }
                        if (!isNaN(start) && !isNaN(end) && start > end) {
                            errors.push('Size ' + (index + 1) + ': Start number (' + start + ') cannot be greater than end number (' + end + ').');
                        }
                        
                        // Check for duplicates
                        if (!isNaN(start) && !isNaN(end) && start <= end) {
                            for (var num = start; num <= end; num++) {
                                if (allNumbers[num]) {
                                    errors.push('Number ' + num + ' is duplicated between Size ' + allNumbers[num] + ' and Size ' + sizeValue + '.');
                                    hasDuplicates = true;
                                } else {
                                    allNumbers[num] = sizeValue;
                                }
                            }
                        }
                    }
                });
                
                if (!hasValidSize) {
                    errors.push('Please enter at least one size.');
                }
                
                // Show errors if any
                if (errors.length > 0) {
                    e.preventDefault();
                    
                    // Remove any existing error alert
                    $('#form-errors').remove();
                    
                    var errorHtml = '<div id="form-errors" class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '<i class="fas fa-exclamation-circle me-2"></i>' +
                        '<strong>Please fix the following errors:</strong>' +
                        '<ul class="mb-0 mt-2">';
                    
                    errors.forEach(function(error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    
                    errorHtml += '</ul>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
                    
                    $('.container-fluid').prepend(errorHtml);
                    
                    // Scroll to top to show errors
                    $('html, body').animate({scrollTop: 0}, 500);
                    
                    // Auto-hide after 10 seconds
                    setTimeout(function() {
                        $('#form-errors').fadeOut('slow');
                    }, 10000);
                    
                    return false;
                }
                
                // Clear any existing error alerts on successful validation
                $('#form-errors').remove();
            });
            
            // Validate end number is not less than start with better visual feedback
            $(document).on('input', '.size-end', function() {
                var $startInput = $(this).closest('.size-group').find('.size-start');
                var startVal = parseInt($startInput.val());
                var endVal = parseInt($(this).val());
                
                // Remove previous validation state
                $(this).removeClass('is-invalid is-valid');
                $(this).next('.invalid-feedback').remove();
                $startInput.removeClass('is-invalid is-valid');
                $startInput.next('.invalid-feedback').remove();
                
                if (!isNaN(startVal) && !isNaN(endVal)) {
                    if (endVal < startVal) {
                        $(this).addClass('is-invalid');
                        $startInput.addClass('is-invalid');
                        $(this).after('<div class="invalid-feedback">End number must be greater than or equal to start number (' + startVal + ').</div>');
                    } else {
                        $(this).addClass('is-valid');
                        $startInput.addClass('is-valid');
                    }
                }
                
                debouncedCheck();
                debouncedDuplicateCheck();
            });
            
            // Also validate start input
            $(document).on('input', '.size-start', function() {
                var $endInput = $(this).closest('.size-group').find('.size-end');
                var startVal = parseInt($(this).val());
                var endVal = parseInt($endInput.val());
                
                // Remove previous validation state
                $(this).removeClass('is-invalid is-valid');
                $(this).next('.invalid-feedback').remove();
                $endInput.removeClass('is-invalid is-valid');
                $endInput.next('.invalid-feedback').remove();
                
                if (!isNaN(startVal) && !isNaN(endVal)) {
                    if (startVal > endVal) {
                        $(this).addClass('is-invalid');
                        $endInput.addClass('is-invalid');
                        $(this).after('<div class="invalid-feedback">Start number must be less than or equal to end number (' + endVal + ').</div>');
                    } else {
                        $(this).addClass('is-valid');
                        $endInput.addClass('is-valid');
                    }
                } else if (!isNaN(startVal) && startVal >= 1) {
                    $(this).addClass('is-valid');
                }
                
                debouncedCheck();
                debouncedDuplicateCheck();
            });
            
            // Add real-time validation for size input
            $(document).on('input', 'input[name="sizes[]"]', function() {
                var sizeValue = $(this).val().trim();
                
                // Remove previous validation state
                $(this).removeClass('is-invalid is-valid');
                $(this).next('.invalid-feedback').remove();
                
                if (sizeValue === '') {
                    $(this).addClass('is-invalid');
                    $(this).after('<div class="invalid-feedback">Please enter a size value.</div>');
                } else {
                    $(this).addClass('is-valid');
                }
                
                debouncedCheck();
                debouncedDuplicateCheck();
            });
            
            // Add validation for OF Number
            $('#prefix').on('input', function() {
                var prefixValue = $(this).val().trim();
                
                // Remove previous validation state
                $(this).removeClass('is-invalid is-valid');
                $(this).next('.invalid-feedback').remove();
                
                if (prefixValue === '') {
                    $(this).addClass('is-invalid');
                    $(this).after('<div class="invalid-feedback">Please enter an OF Number.</div>');
                } else if (!/^\d+$/.test(prefixValue)) {
                    $(this).addClass('is-invalid');
                    $(this).after('<div class="invalid-feedback">OF Number must contain only numbers.</div>');
                } else {
                    $(this).addClass('is-valid');
                }
            });
            
            // Prevent form submission on Enter key in input fields (to avoid accidental submissions)
            $('input').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    return false;
                }
            });
            
            // Add confirmation dialog for form submission
            $('form').on('submit', function(e) {
                // Only show confirmation if form is valid (no errors)
                if ($('#form-errors').length === 0) {
                    var totalItems = 0;
                    var sizeInfo = [];
                    
                    $('.size-group').each(function() {
                        var sizeValue = $(this).find('input[name="sizes[]"]').val();
                        var start = parseInt($(this).find('.size-start').val());
                        var end = parseInt($(this).find('.size-end').val());
                        
                        if (sizeValue && !isNaN(start) && !isNaN(end) && start <= end) {
                            var count = end - start + 1;
                            totalItems += count;
                            sizeInfo.push('Size ' + sizeValue + ': ' + count + ' items (' + start + '-' + end + ')');
                        }
                    });
                    
                    if (totalItems > 1000) {
                        var message = 'You are about to generate a large PDF with ' + totalItems + ' barcodes:\n\n' + 
                                    sizeInfo.join('\n') + '\n\n' +
                                    'This may take a while to generate. Continue?';
                        
                        if (!confirm(message)) {
                            e.preventDefault();
                            return false;
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>