<?php
// Initialize variables
$current_date = date("F j, Y");
$page_title = "Generated PDF Files";

// Set the timezone to Morocco
date_default_timezone_set('Africa/Casablanca');

// Directory where PDF files are stored
$pdf_directory = __DIR__ . "/barcodes/";

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

// Get all PDF files
$pdf_files = getPdfFiles($pdf_directory);

// Handle file download if requested
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $file_to_download = $pdf_directory . basename($_GET['download']);
    
    if (file_exists($file_to_download) && pathinfo($file_to_download, PATHINFO_EXTENSION) === 'pdf') {
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file_to_download) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_to_download));
        readfile($file_to_download);
        exit;
    }
}

// Function to establish database connection
function connectDB() {
    $conn = new mysqli("localhost", "root", "", "jgr2");
    return $conn->connect_error ? false : $conn;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF File Listing</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pdf-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .pdf-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .pdf-icon {
            font-size: 3rem;
            color: #dc3545;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .row-pdf {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        .pdf-col {
            flex: 0 0 20%;
            max-width: 20%;
            padding: 0 15px;
            margin-bottom: 30px;
        }
        @media (max-width: 1200px) {
            .pdf-col {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }
        @media (max-width: 992px) {
            .pdf-col {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }
        @media (max-width: 768px) {
            .pdf-col {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        @media (max-width: 576px) {
            .pdf-col {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $page_title; ?></h1>
            <div class="text-muted"><?php echo $current_date; ?></div>
        </div>

        <div class="back-button">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

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
                                    <a href="?download=<?php echo urlencode($pdf['name']); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <a href="barcodes/<?php echo urlencode($pdf['name']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>