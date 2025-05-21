<?php
require_once __DIR__ . '/vendor/autoload.php'; // Make sure FPDF and BarcodeGeneratorPNG are installed

$pdf = new FPDF();
use Picqer\Barcode\BarcodeGeneratorPNG;

// Create PDF class with barcode functionality
class BarcodePDF extends FPDF
{
    protected $generator;
    
    function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->generator = new BarcodeGeneratorPNG();
    }
    
    // Draw checkbox
    function Checkbox($x, $y, $size = 3)
    {
        $this->Rect($x, $y, $size, $size);
    }
    
    // Add this method to process multiple barcode ranges
    function processBarcodeRanges($barcode_ranges, $colsPerPage = 5, $rowsPerPage = 45)
    {
        // Process each range of barcodes separately
        foreach ($barcode_ranges as $barcodes) {
            $this->createBarcodeTable($barcodes, $colsPerPage, $rowsPerPage);
            
            // Add a new page after each range except the last one
            if ($barcodes !== end($barcode_ranges)) {
                $this->AddPage();
            }
        }
    }
    
    function createBarcodeTable($data, $colsPerPage = 5, $rowsPerPage = 45)
    {
        // Page dimensions
        $pageWidth = 210; // A4 width in mm
        $pageHeight = 297; // A4 height in mm
        
        // Margins
        $leftMargin = 5;
        $rightMargin = 5;
        $topMargin = 5;
        $bottomMargin = 5;
        
        // Calculate usable area
        $usableWidth = $pageWidth - $leftMargin - $rightMargin;
        $usableHeight = $pageHeight - $topMargin - $bottomMargin;
        
        // Cell dimensions
        $cellWidth = $usableWidth / $colsPerPage;
        $cellHeight = $usableHeight / $rowsPerPage;
        
        // Set margins
        $this->SetMargins($leftMargin, $topMargin, $rightMargin);
        $this->SetAutoPageBreak(true, $bottomMargin);
        
        // Add first page
        $this->SetFont('Arial', '', 8);
        
        $totalItems = count($data);
        $itemsPerPage = $colsPerPage * $rowsPerPage;
        
        $currentPage = 1;
        $itemsOnCurrentPage = 0;
        
        // Process each barcode
        for ($i = 0; $i < $totalItems; $i++) {
            // Check if we need a new page
            if ($itemsOnCurrentPage >= $itemsPerPage) {
                $this->AddPage();
                $currentPage++;
                $itemsOnCurrentPage = 0;
            }
            
            // Calculate row and column position
            $row = floor($itemsOnCurrentPage / $colsPerPage);
            $col = $itemsOnCurrentPage % $colsPerPage;
            
            // Calculate x and y positions
            $x = $leftMargin + ($col * $cellWidth);
            $y = $topMargin + ($row * $cellHeight);
            
            // Draw cell border
            $this->Rect($x, $y, $cellWidth, $cellHeight);
            
            // Draw checkbox in the right part of the cell
            $checkboxSize = 3;
            $checkboxX = $x + $cellWidth - $checkboxSize - 3; // Position checkbox with some padding from right
            $checkboxY = $y + ($cellHeight/2) - ($checkboxSize/2);
            $this->Checkbox($checkboxX, $checkboxY, $checkboxSize);
            
            // Position for text - moved slightly to the left to accommodate checkbox on right
            $textX = $x + 3; // Position text with some padding from left
            $this->SetXY($textX, $y + ($cellHeight/2) - 1.5);
            
            // Calculate width for text cell (reducing by checkbox width and some padding)
            $textWidth = $cellWidth - ($checkboxSize + 8);
            
            // Add barcode text with adjusted positioning
            $this->Cell($textWidth, 3, $data[$i], 0, 0, 'C');
            
            $itemsOnCurrentPage++;
        }
    }
}

// Initialize variables and get form data
$prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '17406';
$size = isset($_POST['size']) ? $_POST['size'] : '52';
$category = isset($_POST['category']) ? $_POST['category'] : 'R';
$piece_type = isset($_POST['piece_type']) ? $_POST['piece_type'] : 'V';
$start_number = isset($_POST['start_number']) ? (int)$_POST['start_number'] : 1;
$end_number = isset($_POST['end_number']) ? (int)$_POST['end_number'] : 225;
$generate = isset($_POST['generate']) ? true : false;

// Fixed layout parameters - 5 columns and 45 rows (225 items per page)
$columns = 5;
$rows_per_page = 45;

// Define available options for dropdowns
$category_options = ['','R', 'C', 'L', 'LL', 'CC', 'N'];
$piece_type_options = ['P', 'V', 'G', 'M'];

// Multiple piece types
$multiple_pieces = isset($_POST['multiple_pieces']) ? true : false;
$piece_types = isset($_POST['piece_types']) ? $_POST['piece_types'] : ['V'];

// Multiple sizes with independent ranges
$multiple_sizes = isset($_POST['multiple_sizes']) ? true : false;
$sizes = isset($_POST['sizes']) ? $_POST['sizes'] : [];
$size_ranges = isset($_POST['size_ranges']) ? $_POST['size_ranges'] : [];

// Format the barcode string
function formatBarcodeString($prefix, $size, $category, $piece_type, $number) {
    // Format number with leading zeros to ensure consistent width
    $formatted_number = sprintf("%d", $number);
    
    if (empty($category)) {
        return "$prefix-$size-$piece_type-$formatted_number";
    } else {
        return "$prefix-$size$category-$piece_type-$formatted_number";
    }
}

// Generate PDF if form submitted
if ($generate) {
    // Instead of one big array, create an array of barcode ranges
    $barcode_ranges = [];
    
    if ($multiple_sizes && !empty($sizes)) {
        // Generate barcodes for multiple sizes with independent ranges
        foreach ($sizes as $index => $size_value) {
            if (empty($size_value)) continue;
            
            // Get the start and end numbers for this size
            $size_start = isset($size_ranges[$index]['start']) ? (int)$size_ranges[$index]['start'] : $start_number;
            $size_end = isset($size_ranges[$index]['end']) ? (int)$size_ranges[$index]['end'] : $end_number;
            
            if ($multiple_pieces && !empty($piece_types)) {
                // Multiple sizes AND multiple piece types
                foreach ($piece_types as $piece) {
                    if (empty($piece)) continue;
                    
                    // Create a new range for each size and piece type combination
                    $range_barcodes = [];
                    for ($i = $size_start; $i <= $size_end; $i++) {
                        $range_barcodes[] = formatBarcodeString($prefix, $size_value, $category, $piece, $i);
                    }
                    
                    if (!empty($range_barcodes)) {
                        $barcode_ranges[] = $range_barcodes;
                    }
                }
            } else {
                // Multiple sizes but single piece type
                $range_barcodes = [];
                for ($i = $size_start; $i <= $size_end; $i++) {
                    $range_barcodes[] = formatBarcodeString($prefix, $size_value, $category, $piece_type, $i);
                }
                
                if (!empty($range_barcodes)) {
                    $barcode_ranges[] = $range_barcodes;
                }
            }
        }
    } else {
        // Single size
        if ($multiple_pieces && !empty($piece_types)) {
            // Single size but multiple piece types
            foreach ($piece_types as $piece) {
                if (empty($piece)) continue;
                
                $range_barcodes = [];
                for ($i = $start_number; $i <= $end_number; $i++) {
                    $range_barcodes[] = formatBarcodeString($prefix, $size, $category, $piece, $i);
                }
                
                if (!empty($range_barcodes)) {
                    $barcode_ranges[] = $range_barcodes;
                }
            }
        } else {
            // Single size and single piece type
            $range_barcodes = [];
            for ($i = $start_number; $i <= $end_number; $i++) {
                $range_barcodes[] = formatBarcodeString($prefix, $size, $category, $piece_type, $i);
            }
            
            if (!empty($range_barcodes)) {
                $barcode_ranges[] = $range_barcodes;
            }
        }
    }
    
    // Generate PDF with page breaks between ranges
    $pdf = new BarcodePDF();
    
    // Add a page before processing (to ensure there's at least one page)
    $pdf->AddPage();
    
    // Process the barcode ranges
    $pdf->processBarcodeRanges($barcode_ranges, $columns, $rows_per_page);
    
    // Output PDF
    $filename = "barcodes-$prefix.pdf";
    $pdf->Output('D', $filename);
    exit;
}

// The rest of the HTML form remains the same...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Barcode List Generator</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h1 class="h3 mb-0 text-center">Advanced Barcode List Generator</h1>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="prefix" class="form-label">Prefix (e.g., OF Number):</label>
                            <input type="text" class="form-control" id="prefix" name="prefix" value="<?php echo htmlspecialchars($prefix); ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <label for="size" class="form-label mb-0">Size:</label>
                                <div class="form-check ms-2">
                                    <input class="form-check-input" type="checkbox" id="multiple_sizes" name="multiple_sizes" <?php echo $multiple_sizes ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="multiple_sizes">
                                        Multiple sizes
                                    </label>
                                </div>
                            </div>
                            <div id="single-size-input" class="<?php echo $multiple_sizes ? 'd-none' : ''; ?>">
                                <input type="text" class="form-control" id="size" name="size" value="<?php echo htmlspecialchars($size); ?>">
                            </div>
                            <div id="multiple-sizes-input" class="<?php echo !$multiple_sizes ? 'd-none' : ''; ?>">
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
                                                <input type="text" class="form-control" name="sizes[]" value="">
                                                <button type="button" class="btn btn-outline-danger remove-size"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text">Start</span>
                                                        <input type="number" class="form-control" name="size_ranges[0][start]" value="' . $start_number . '" min="1">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="input-group">
                                                        <span class="input-group-text">End</span>
                                                        <input type="number" class="form-control" name="size_ranges[0][end]" value="' . $end_number . '" min="1">
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
                        </div>
                        
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category:</label>
                            <select class="form-select" id="category" name="category">
                                <?php foreach ($category_options as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php echo $category === $option ? 'selected' : ''; ?>>
                                        <?php echo empty($option) ? '(None)' : $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="multiple_pieces" name="multiple_pieces" <?php echo $multiple_pieces ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="multiple_pieces">
                                    Generate for multiple piece types
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div id="single-piece" class="row mb-3 <?php echo $multiple_pieces ? 'd-none' : ''; ?>">
                        <div class="col-md-6">
                            <label for="piece_type" class="form-label">Piece Type:</label>
                            <select class="form-select" id="piece_type" name="piece_type">
                                <?php foreach ($piece_type_options as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php echo $piece_type === $option ? 'selected' : ''; ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="multiple-pieces" class="row mb-3 <?php echo !$multiple_pieces ? 'd-none' : ''; ?>">
                        <div class="col-12">
                            <label class="form-label">Piece Types:</label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($piece_type_options as $option): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="piece_types[]" value="<?php echo $option; ?>" 
                                            id="piece_type_<?php echo $option; ?>"
                                            <?php echo in_array($option, (array)$piece_types) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="piece_type_<?php echo $option; ?>">
                                            <?php echo $option; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div id="global-range" class="row mb-4 <?php echo $multiple_sizes ? 'd-none' : ''; ?>">
                        <div class="col-md-6">
                            <label for="start_number" class="form-label">Start Number:</label>
                            <input type="number" class="form-control" id="start_number" name="start_number" value="<?php echo $start_number; ?>" min="1" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="end_number" class="form-label">End Number:</label>
                            <input type="number" class="form-control" id="end_number" name="end_number" value="<?php echo $end_number; ?>" min="1" required>
                        </div>
                    </div>
                    
                    <!-- Fixed layout - 5 columns and 45 rows (225 items) per page -->
                    <input type="hidden" name="columns" value="5">
                    <input type="hidden" name="rows_per_page" value="45">
                    
                    <div class="d-grid">
                        <button type="submit" name="generate" value="1" class="btn btn-primary btn-lg">
                            <i class="fas fa-file-pdf me-2"></i> Generate PDF
                        </button>
                    </div>
                </form>
            
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (required for some of our custom functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle between single and multiple piece types
            $('#multiple_pieces').change(function() {
                $('#single-piece').toggleClass('d-none');
                $('#multiple-pieces').toggleClass('d-none');
                updatePreview();
            });
            
            // Toggle between single and multiple sizes
            $('#multiple_sizes').change(function() {
                $('#single-size-input').toggleClass('d-none');
                $('#multiple-sizes-input').toggleClass('d-none');
                $('#global-range').toggleClass('d-none');
                updatePreview();
            });
            
            // Add new size input with its own range
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
                    '<input type="number" class="form-control" name="size_ranges[' + newIndex + '][start]" value="1" min="1">' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-6">' +
                    '<div class="input-group">' +
                    '<span class="input-group-text">End</span>' +
                    '<input type="number" class="form-control" name="size_ranges[' + newIndex + '][end]" value="225" min="1">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>'
                );
                attachRemoveHandlers();
                updatePreview();
            });
            
            // Remove size input
            function attachRemoveHandlers() {
                $('.remove-size').off('click').on('click', function() {
                    // Make sure there's always at least one size input
                    if ($('.size-container .size-group').length > 1) {
                        $(this).closest('.size-group').remove();
                        // Renumber the remaining size groups
                        reindexSizeRanges();
                        updatePreview();
                    } else {
                        alert('You must have at least one size.');
                    }
                });
            }
            
            // Reindex the size ranges when a size is removed
            function reindexSizeRanges() {
                $('.size-group').each(function(index) {
                    $(this).find('input[name^="size_ranges"]').each(function() {
                        var name = $(this).attr('name');
                        var newName = name.replace(/size_ranges\[\d+\]/, 'size_ranges[' + index + ']');
                        $(this).attr('name', newName);
                    });
                });
            }
            
            // Update preview when form inputs change
            $('form input, form select').on('change input', function() {
                updatePreview();
            });
            
            // Function to update preview (in a real application, this would use AJAX)
            function updatePreview() {
                // In a real app, this would send the current form data to the server via AJAX
                // and update the preview with the response
                console.log('Preview would update here in a real application');
            }
            
            // Initialize handlers
            attachRemoveHandlers();
        });
    </script>
</body>
</html>