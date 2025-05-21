<?php
// Initialize variables
$current_date = date("F j, Y");

// Set the timezone to Morocco (matching pdf.php)
date_default_timezone_set('Africa/Casablanca');

// Directory where PDF files are stored
$pdf_directory = __DIR__ . "/track files/";

// Create the directory if it doesn't exist
if (!is_dir($pdf_directory)) {
    mkdir($pdf_directory, 0755, true);
}

// Function to get all PDF files from the directory
function getPdfFiles($directory) {
    $pdf_files = [];
    
    // Check if directory exists
    if (!is_dir($directory)) {
        return ["error" => "Directory not found"];
    }
    
    // Get all files with .pdf extension
    $files = glob($directory . "*.pdf");
    
    if (empty($files)) {
        return ["error" => "No PDF files found"];
    }
    
    // Collect file information for each PDF
    foreach ($files as $file) {
        $file_name = basename($file);
        $file_size = filesize($file);
        $mod_time = filemtime($file);
        
        $pdf_files[] = [
            'name' => $file_name,
            'path' => $file,
            'size' => $file_size,
            'modified' => $mod_time,
            'formatted_size' => formatFileSize($file_size),
            'formatted_date' => date("Y-m-d H:i:s", $mod_time)
        ];
    }
    
    // Sort files by modification time (newest first)
    usort($pdf_files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $pdf_files;
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Handle file download if requested
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $file_name = $_GET['download'];
    // Security check - only allow PDF files with specific format
    if (preg_match('/^barcodes-\d+\.pdf$/', $file_name)) {
        $file_path = $pdf_directory . $file_name;
        if (file_exists($file_path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
}

// Get all PDF files
$pdf_files = getPdfFiles($pdf_directory);

// Get search and date filter parameters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : '';

// Apply filters if no error
if (!isset($pdf_files['error'])) {
    // Search filter
    if (!empty($search_term)) {
        $pdf_files = array_filter($pdf_files, function($pdf) use ($search_term) {
            return stripos($pdf['name'], $search_term) !== false;
        });
        $pdf_files = array_values($pdf_files);
    }

    // Date filter
    if (!empty($date_filter)) {
        $pdf_files = array_filter($pdf_files, function($pdf) use ($date_filter) {
            return date('Y-m-d', $pdf['modified']) === $date_filter;
        });
        $pdf_files = array_values($pdf_files);
    }

    // Check if no results after filtering
    if (empty($pdf_files)) {
        $pdf_files = ['error' => 'No PDF files found matching your criteria.'];
    }
}

// If this was opened via the "Open Path" button, display the file explorer
$open_explorer = isset($_POST['filename']) ? true : false;
if ($open_explorer) {
    $filename = $_POST['filename'];
    
    // Security check for filename format
    if (!empty($filename) && preg_match('/^barcodes-\d+\.pdf$/', $filename)) {
        // If on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Open Explorer window to the directory
            exec('explorer.exe "' . str_replace('/', '\\', $pdf_directory) . '"');
        } 
        // If on macOS
        else if (PHP_OS === 'Darwin') {
            exec('open "' . $pdf_directory . '"');
        } 
        // If on Linux
        else {
            exec('xdg-open "' . $pdf_directory . '"');
        }
    }
}

// Include the necessary styling elements
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode PDF Files</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="content">
            <div class="container-fluid">
                <h4 class="mb-4" style="font-size: 18px;">Barcode PDF Files</h4>
                
                <!-- Search and Filter Form -->
                <form id="pdf-search-form" method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search PDF name..." value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" name="date_filter" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Apply Filters
                                </button>
                                <button type="button" id="clear-pdf-filters" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-broom me-1"></i> Clear
                                </button>
                                <a href="tracker.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (isset($pdf_files['error'])): ?>
                    <div class="alert alert-warning">
                        <?php echo $pdf_files['error']; ?>
                    </div>
                <?php else: ?>
                    <div class="row-pdf">
                        <?php foreach ($pdf_files as $pdf): ?>
                            <div class="pdf-col">
                                <div class="card pdf-card">
                                    <div class="card-body text-center">
                                        <i class="far fa-file-pdf pdf-icon mb-3"></i>
                                        <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($pdf['name']); ?>">
                                            <?php echo htmlspecialchars($pdf['name']); ?>
                                        </h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                Size: <?php echo $pdf['formatted_size']; ?><br>
                                                Modified: <?php echo date("Y-m-d", $pdf['modified']); ?><br>
                                                Time: <?php echo date("H:i", $pdf['modified']); ?>
                                            </small>
                                        </p>
                                        <div class="btn-group">
                                            <a href="track files/<?php echo urlencode($pdf['name']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($pdf_files) > 15): ?>
                    <div class="text-center mt-3 mb-3">
                        <p class="text-muted">Showing <?php echo count($pdf_files); ?> PDF files</p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Clear filter functionality
            $('#clear-pdf-filters').click(function() {
                window.location.href = 'tracker_open_path.php';
            });
        });
    </script>
</body>
</html>
?>