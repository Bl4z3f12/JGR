<?php
require_once __DIR__ . '/vendor/autoload.php'; // For FPDF and Barcode Generator

use Picqer\Barcode\BarcodeGeneratorPNG;
$pdf = new FPDF();

// Initialize variables
$current_view = $_GET['view'] ?? 'dashboard';
$current_date = date("F j, Y");
$page = $_GET['page'] ?? 1;
$items_per_page = 10;

// Establish database connection
function connectDB() {
    $conn = new mysqli("localhost", "root", "", "jgr2");
    return $conn->connect_error ? false : $conn;
}

function getBarcodes($view, $page, $items_per_page) {
    $conn = connectDB();
    if (!$conn) return [];

    $offset = ($page - 1) * $items_per_page;
    $where = match ($view) {
        'today' => "WHERE DATE(last_update) = CURDATE()",
        'manufactured' => "WHERE status = 'Completed'",
        default => "",
    };

    $sql = "SELECT * FROM barcodes $where ORDER BY id DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $items_per_page);
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

function getTotalBarcodes($view) {
    $conn = connectDB();
    if (!$conn) return 100;

    $where = match ($view) {
        'today' => "WHERE DATE(last_update) = CURDATE()",
        'manufactured' => "WHERE status = 'Completed'",
        default => "",
    };

    $sql = "SELECT COUNT(*) as total FROM barcodes $where";
    $result = $conn->query($sql);
    $total = $result ? $result->fetch_assoc()['total'] : 0;
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
        return "$of_number-$size-$category-$piece_name-$number";
    }
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
            $topSpacing = 15;
            $fontSize = 14;
            
            $col = 0;
            $row = 0;
            
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
                        $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                        $stmt->bind_param("sissss", $of_number, $size, $category, $piece_name, $formatted_number, $full_barcode_name);
                        $stmt->execute();
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
                            $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                            $stmt->bind_param("sissss", $of_number, $size, $category, $current_piece, $i, $full_barcode_name);
                            $stmt->execute();
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
                        $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, status, stage) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
                        $stmt->bind_param("sissss", $of_number, $size, $category, $piece_name, $i, $full_barcode_name);
                        $stmt->execute();
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
            
            $randomNumber = rand(10000, 99999);
            $pdfFilename = "A$randomNumber.pdf";
            $pdf->Output('F', __DIR__ . "/barcodes/$pdfFilename");
            
            $conn->close();
        }
        
        header("Location: index.php?view=$form_view&success=1");
        exit;
    } else {
        header("Location: index.php?view=$form_view&modal=create&error=" . urlencode(implode(", ", $errors)));
        exit;
    }
}

// Load barcodes for current view
$barcodes = getBarcodes($current_view, $page, $items_per_page);
$total_barcodes = getTotalBarcodes($current_view);
$total_pages = ceil($total_barcodes / $items_per_page);
$show_success = isset($_GET['success']) && $_GET['success'] == 1;
$error_message = $_GET['error'] ?? '';
$show_modal = isset($_GET['modal']) && $_GET['modal'] === 'create';

// Add the random button script to be included in the page
$random_button_script = getRandomButtonScript();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarcodeHub</title>
    <link rel="stylesheet" href="assets/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>
    <div class="sidebar">
        <a href="?view=dashboard" class="sidebar-item <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>">
            <div class="sidebar-item-icon"><i class="fa-solid fa-qrcode"></i></div>
            Dashboard
        </a>
        <a href="?view=today" class="sidebar-item <?php echo $current_view === 'today' ? 'active' : ''; ?>">
            <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
            Scanned Today
        </a>
        <a href="?view=production" class="sidebar-item <?php echo $current_view === 'production' ? 'active' : ''; ?>">
            <div class="sidebar-item-icon"><i class="fa-solid fa-chart-line"></i></div>
            Production
        </a>
        <a href="?view=export" class="sidebar-item <?php echo $current_view === 'export' ? 'active' : ''; ?>">
            <div class="sidebar-item-icon"><i class="fa-solid fa-file-export"></i></div>
            Export
        </a>
        <a href="?view=Settings" class="sidebar-item <?php echo $current_view === 'Settings' ? 'active' : ''; ?>">
            <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
            Barcodes Settings
        </a>
    </div>
    

    <div class="main-content">
        <div class="header">
            <div class="logo">
                <div class="logo-icon"><i class="fa-solid fa-qrcode"></i></div>
                BarcodeHub
            </div>
            <div class="date" id="current-date">
                <?php echo $current_date; ?>
            </div>
        </div>
        
        <div class="content">
            <?php if ($show_success): ?>
            <div class="success-message">
                Barcodes successfully generated!
                <a href="?view=<?php echo $current_view; ?>" class="close-message">&times;</a>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="error-message">
                Error: <?php echo htmlspecialchars($error_message); ?>
                <a href="?view=<?php echo $current_view; ?>" class="close-message">&times;</a>
            </div>
            <?php endif; ?>
            
            <div class="content-header">
                <h2 class="content-title"><?php echo getViewTitle($current_view); ?></h2>
                <div class="button-group">
                    <a href="?view=<?php echo $current_view; ?>&modal=create" class="btn-create">
                        <span><i class="fa-solid fa-gears"></i></span> Create New Barcodes
                    </a>
                    <a href="pdf.php" class="btn-create" id="open-path-btn">
                            <span><i class="fa-solid fa-folder-open"></i></span> Open Path
                    </a>
                </div>
            </div>            
            <table class="dashboard-table">
    <thead>
        <tr>
            <th>OF_Number</th>
            <th>Size</th>
            <th>Category</th>
            <th>Piece Name</th>
            <th>Order</th>
            <th>Status</th>
            <th>Stage</th>
            <th>Full Barcode Name</th>
            <th>Last Update</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($barcodes)): ?>
        <tr>
            <td colspan="11" style="text-align: center;">No barcodes found</td>
        </tr>
        <?php else: ?>
            <?php foreach ($barcodes as $barcode): ?>
            <tr>
                <td><?php echo htmlspecialchars($barcode['of_number']); ?></td>
                <td><?php echo htmlspecialchars($barcode['size']); ?></td>
                <td><?php echo htmlspecialchars($barcode['category']); ?></td>
                <td><?php echo htmlspecialchars($barcode['piece_name']); ?></td>
                <td><?php echo htmlspecialchars($barcode['order_str']); ?></td>
                <td>
                    <span class="status-badge status-<?php echo strtolower($barcode['status']); ?>">
                        <?php echo htmlspecialchars($barcode['status']); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($barcode['stage']); ?></td>
                <td><?php echo htmlspecialchars($barcode['full_barcode_name']); ?></td>
                <td><?php echo htmlspecialchars($barcode['last_update']); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?view=<?php echo $current_view; ?>&page=<?php echo ($page - 1); ?>" class="pagination-btn">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
                <?php endif; ?>
                
                <?php
                // Display pagination buttons
                $start_page = max(1, min($page - 1, $total_pages - 2));
                $end_page = min($total_pages, max(3, $page + 1));
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?view=<?php echo $current_view; ?>&page=<?php echo $i; ?>" 
                   class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?view=<?php echo $current_view; ?>&page=<?php echo ($page + 1); ?>" class="pagination-btn">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Barcode Modal -->
    <div class="modal <?php echo $show_modal ? 'show' : ''; ?>" id="barcode-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Barcode Generator</h3>
                <a href="?view=<?php echo $current_view; ?>" class="close">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            </div>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="create_barcode">
                <input type="hidden" name="view" value="<?php echo $current_view; ?>">
                
                <div class="warning-message">
                    REMEMBER: You will not be able to change, barcode data after you confirm validity of data
                </div>
                
                <div class="form-group">
                    <label for="barcode-prefix">OF_ number *</label>
                    <input class="ofinput" type="text" id="barcode-prefix" name="barcode_prefix" required>
                </div>
                
                <div class="form-group">
                    <label for="barcode-size">Size *</label>
                    <input type="number" id="barcode-size" name="barcode_size" min="1" step="1" class="ofinput" placeholder="Enter size number">
                </div>

                <div class="form-group">
                    <label for="barcode-category">OF_Category</label>
                    <select id="barcode-category" name="barcode_category" class="form-select">
                        <option value="">Select Category</option>
                        <option value="R">R</option>
                        <option value="C">C</option>
                        <option value="L">L</option>
                        <option value="LL">LL</option>
                        <option value="CC">CC</option>
                        <option value="N">N</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="barcode-piece-name">Piece Name *</label>
                    <select id="barcode-piece-name" name="barcode_piece_name" class="form-select">
                        <option value="">Select Piece Name</option>
                        <option value="P">P</option>
                        <option value="V">V</option>
                        <option value="G">G</option>
                        <option value="M">M</option>
                    </select>
                </div>
                                
                <div class="checkall">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="lost-barcode" name="lost_barcode" style="margin-right: 5px;"
                            onclick="this.form.lost_barcode_count.disabled = !this.checked;
                                    this.form.generate_costume_2pcs.disabled = this.checked;
                                    this.form.generate_costume_3pcs.disabled = this.checked;
                                    this.form.range_from.disabled = this.checked;
                                    this.form.range_to.disabled = this.checked;">
                        <label for="lost-barcode" style="margin-right: 10px;">Lost Barcode [C prefix]</label>
                        <label style="margin-right: 5px;"></label>
                        <input type="number" id="lost-barcode-count" name="lost_barcode_count"
                            style="margin-right: 20px; width: 60px;"
                            value="1" min="1" max="100"
                            disabled>
                        <label style="margin-right: 10px;">Random <i class="fa-solid fa-arrows-rotate"></i></label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Piece Order</label>
                    <div class="range-inputs">
                        <div>
                            <label for="range-from">From</label>
                            <input type="number" id="range-from" name="range_from" size="8">
                        </div>
                        <div>
                            <label for="range-to">To</label>
                            <input type="number" id="range-to" name="range_to" size="8">
                        </div>
                    </div>
                </div>

                <div class="checkall2">
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="generate-costume-2pcs" name="generate_costume_2pcs">
                    <label for="generate-costume-2pcs">Generate for P and V (Costume 2pcs)</label>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="generate-costume-3pcs" name="generate_costume_3pcs">
                    <label for="generate-costume-3pcs">Generate for P, V, and G (Costume 3pcs)</label>
                </div>
                    
                <div class="form-group checkbox-group indented">
                    <input type="checkbox" id="generate_pdf_only" name="generate_pdf_only">
                    <label for="generate_pdf_only">Generate only PDF</label>
                </div>
                </div>

                <div class="barcode-actions">
                    <button type="submit" class="btn btn-secondary">Generate Barcodes</button>
                    <a href="?view=<?php echo $current_view; ?>" class="btn btn-danger">Cancel Generating</a>
                </div>
                
                <div class="barcode-range-indicators">
                    <div class="range-indicator">
                        <div class="color-box white-box"></div>
                        <span>1 ---> 1000</span>
                    </div>
                    <div class="range-indicator">
                        <div class="color-box yellow-box"></div>
                        <span>1001 ---> 2000</span>
                    </div>
                    <div class="range-indicator">
                        <div class="color-box green-box"></div>
                        <span>2001 ---> 3000</span>
                    </div>
                </div>
                
                <div class="barcode-legend">
                    <span>1 --> CC / 2 --> C / 3 --> R / 4 --> L / 5 --> LL</span>
                </div>
                
            </form>
        </div>
    </div>
        
    <!-- Network Status Indicator -->
    <div class="network-status <?php echo function_exists('fsockopen') && @fsockopen('www.google.com', 80) ? 'online' : 'offline'; ?>" id="network-status">
        <?php echo function_exists('fsockopen') && @fsockopen('www.google.com', 80) ? 'Online' : 'Offline'; ?>
    </div>

<?php
// Function to generate a simple barcode (this is a mock function)
function generateBarcode($code) {
    // In a real app, you would use a barcode generation library
    // This is just a placeholder that creates a simple image
    $image = imagecreate(200, 80);
    $background = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 0);
    
    // Draw some lines to represent a barcode
    for ($i = 0; $i < 15; $i++) {
        $x = 20 + ($i * 10);
        $h = rand(20, 60);
        imagefilledrectangle($image, $x, 10, $x + 5, 10 + $h, $text_color);
    }
    
    // Add the text
    imagestring($image, 5, 50, 65, $code, $text_color);
    
    // Capture the image data
    ob_start();
    imagepng($image);
    $data = ob_get_clean();
    
    imagedestroy($image);
    
    return $data;
}
?>