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
        foreach ($barcode_ranges as $barcodes) {
            $this->createBarcodeTable($barcodes, $colsPerPage, $rowsPerPage);
            if ($barcodes !== end($barcode_ranges)) {
                $this->AddPage();
            }
        }
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
        $currentPage = 1;
        $itemsOnCurrentPage = 0;
        
        // Calculate how many full columns we need
        $fullColumns = floor($totalItems / $rowsPerPage);
        $remainingItems = $totalItems % $rowsPerPage;
        
        // Process full columns
        for ($col = 0; $col < $fullColumns; $col++) {
            for ($row = 0; $row < $rowsPerPage; $row++) {
                $itemIndex = $col * $rowsPerPage + $row;
                $x = $leftMargin + ($col * $cellWidth);
                $y = $topMargin + ($row * $cellHeight);
                
                $this->Rect($x, $y, $cellWidth, $cellHeight);
                $checkboxSize = 3;
                $checkboxX = $x + $cellWidth - $checkboxSize - 3; // Position checkbox with some padding from right
                $checkboxY = $y + ($cellHeight/2) - ($checkboxSize/2);
                $this->Checkbox($checkboxX, $checkboxY, $checkboxSize);
                $textX = $x + 3; // Position text with some padding from left
                $this->SetXY($textX, $y + ($cellHeight/2) - 1.5);
                $textWidth = $cellWidth - ($checkboxSize + 8);
                $this->Cell($textWidth, 3, $data[$itemIndex], 0, 0, 'C');
                
                $itemsOnCurrentPage++;
                if ($itemsOnCurrentPage >= $itemsPerPage) {
                    $this->AddPage();
                    $currentPage++;
                    $itemsOnCurrentPage = 0;
                }
            }
        }
        
        // Process remaining items in the last partial column
        if ($remainingItems > 0) {
            $lastCol = $fullColumns;
            for ($row = 0; $row < $remainingItems; $row++) {
                $itemIndex = $fullColumns * $rowsPerPage + $row;
                $x = $leftMargin + ($lastCol * $cellWidth);
                $y = $topMargin + ($row * $cellHeight);
                
                $this->Rect($x, $y, $cellWidth, $cellHeight);
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
        $default_end = empty($end_number) ? 225 : $end_number;
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
    
    $filename = "barcodes-$prefix.pdf";
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
</head>
<body >
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content" >
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
                                                            <input type="number" class="form-control" name="size_ranges[' . $index . '][start]" value="' . $start_val . '" min="1">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text">End</span>
                                                            <input type="number" class="form-control" name="size_ranges[' . $index . '][end]" value="' . $end_val . '" min="1">
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
                                                        <input type="number" class="form-control" name="size_ranges[0][start]" value="" min="1">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text">End</span>
                                                        <input type="number" class="form-control" name="size_ranges[0][end]" value="" min="1">
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
                    '<input type="number" class="form-control" name="size_ranges[' + newIndex + '][start]" value="" min="1">' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-6">' +
                    '<div class="input-group">' +
                    '<span class="input-group-text">End</span>' +
                    '<input type="number" class="form-control" name="size_ranges[' + newIndex + '][end]" value="" min="1">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>'
                );
                attachRemoveHandlers();
                updatePreview();
            });
            
            function attachRemoveHandlers() {
                $('.remove-size').off('click').on('click', function() {
                    if ($('.size-container .size-group').length > 1) {
                        $(this).closest('.size-group').remove();
                        reindexSizeRanges();
                        updatePreview();
                    } else {
                        alert('You must have at least one size.');
                    }
                });
            }
            
            function reindexSizeRanges() {
                $('.size-group').each(function(index) {
                    $(this).find('input[name^="size_ranges"]').each(function() {
                        var name = $(this).attr('name');
                        var newName = name.replace(/size_ranges\[\d+\]/, 'size_ranges[' + index + ']');
                        $(this).attr('name', newName);
                    });
                });
            }
            
            $('form input, form select').on('change input', function() {
                updatePreview();
            });
            
            function updatePreview() {
                console.log('Preview would update here in a real application');
            }
            $('#open-path').click(function() {
                var prefix = $('#prefix').val();
                
                // Create a hidden form to handle the path opening request
                var form = $('<form></form>').attr({
                    'method': 'post',
                    'action': 'tracker_open_path.php'
                });
                
                // If prefix is provided, include it as a parameter
                if (prefix) {
                    form.append($('<input>').attr({
                        'type': 'hidden',
                        'name': 'filename',
                        'value': 'barcodes-' + prefix + '.pdf'
                    }));
                }
                
                $('body').append(form);
                form.submit();
            });
            
            attachRemoveHandlers();
        });
    </script>
</body>
</html>