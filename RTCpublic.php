<?php
require_once __DIR__ . '/barcode_system.php';
require_once __DIR__ . '/vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

$current_view = 'RTCpublic.php';

// Process barcode creation via AJAX
if (isset($_POST['ajax_create_barcode']) && $_POST['ajax_create_barcode'] == 1) {
    // Get form values
    $of_number = $_POST['barcode_prefix'] ?? '';
    $size = $_POST['barcode_size'] ?? '';
    $category = $_POST['barcode_category'] ?? '';
    $piece_name = $_POST['barcode_piece_name'] ?? '';
    $lost_barcode_count = (int)($_POST['lost_barcode_count'] ?? 1);
    
    $conn = connectDB();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Validate input
    $errors = [];
    if (!$of_number) $errors[] = "OF number is required";
    if (!$piece_name || $piece_name == '0') $errors[] = "Piece name is required";
    if ($lost_barcode_count <= 0 || $lost_barcode_count > 100) $errors[] = "Lost barcode quantity must be between 1 and 100";
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
        exit;
    }
    
    // Create barcode
    try {     
        $generator = new BarcodeGeneratorPNG();
        $pdfFiles = [];
        $successCount = 0;
        $duplicates = [];
        
        // Configuration for barcode PDFs
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
        
        // Create PDF
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        
        $col = 0;
        $row = 0;
        
        $lost_barcode_number = rand(1, 1000);
        
        for ($i = 0; $i < $lost_barcode_count; $i++) {
            $formatted_number = "X" . $lost_barcode_number;
            $full_barcode_name = formatBarcodeString($of_number, $size, $category, $piece_name, $formatted_number);
            
            if (barcodeExists($conn, $full_barcode_name)) {
                $duplicates[] = $full_barcode_name;
                $lost_barcode_number++;
                continue;
            }
            
            $stmt = $conn->prepare("INSERT INTO barcodes (of_number, size, category, piece_name, order_str, full_barcode_name, name, status, stage) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL)");
            $user_name = $_POST['user_name'] ?? '';
            $stmt->bind_param("sssssss", $of_number, $size, $category, $piece_name, $formatted_number, $full_barcode_name, $user_name);
            $stmt->execute();
            $successCount++;

            $result = placeBarcodeInPdf($generator, $pdf, $full_barcode_name, $col, $row, $cellWidth, $cellHeight, $barcodeWidth, $barcodeHeight, $topSpacing, $fontSize, $pageWidth, $colsPerPage, $rowsPerPage);
            $col = $result['col'];
            $row = $result['row'];
            
            if ($row >= $rowsPerPage && $i < $lost_barcode_count - 1) {
                $row = 0;
                $pdf->AddPage();
            }
            
            $lost_barcode_number++;

            // After $stmt->execute(); in the for-loop
            // Modified notification insert
            $notificationMessage = "barcode $full_barcode_name created";
            $notificationDate = date('Y-m-d H:i:s');
            $user_name = $_POST['user_name'] ?? 'unknown';

            $stmtNotif = $conn->prepare("INSERT INTO notifications 
                (type, message, date, `read`, name) 
                VALUES ('new', ?, ?, 0, ?)");
            $stmtNotif->bind_param("sss", $notificationMessage, $notificationDate, $user_name);
            $stmtNotif->execute();
            $stmtNotif->close();

        }
        
        // Save the PDF file
        $pdfFilename = "{$of_number}-{$size}{$category}-RTC.pdf";
        $pdfFilename = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $pdfFilename);
        if (empty($pdfFilename) || $pdfFilename == ".pdf") {
            $randomNumber = rand(10000, 99999);
            $pdfFilename = "A{$randomNumber}.pdf";
        }
        $pdfPath = __DIR__ . "/barcodes/$pdfFilename";
        $pdf->Output('F', $pdfPath);
        
        // Return success response
        $barcode_text = formatBarcodeString($of_number, $size, $category, $piece_name, "X" . ($lost_barcode_number - 1));
        
        // Create response message
        if (!empty($duplicates)) {
            $duplicate_message = " Some barcodes already existed and were skipped.";
            $message = "$successCount barcodes created successfully.$duplicate_message";
        } else {
            $message = "$successCount barcodes created successfully!";
        }
        
        $response = [
            'success' => true,
            'message' => $message,
            'barcode' => $barcode_text,
            'quantity' => $successCount,
            'pdf' => $pdfFilename
        ];
        
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error creating barcode: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost Barcode public edition</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card form-card">
                    <div class="card-header bg-primary text-white" >
                        <h3 class="mb-0">Lost Barcode [Public Edition]</h3>
                    </div>
                    <div class="card-body">
                        <!-- Changed from form submission to AJAX handling -->
                        <form id="barcodeForm">
                            <input type="hidden" name="ajax_create_barcode" value="1">
                            <input type="hidden" name="lost_barcode" value="1">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="barcode_prefix" class="form-label">OF Number <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="barcode_prefix" name="barcode_prefix" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="barcode_size" class="form-label">Size <span class="text-danger">*</span></label>
                                        <!-- Changed to numeric input -->
                                        <input type="number" class="form-control" id="barcode_size" name="barcode_size" min="0" step="1" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="barcode_category" class="form-label">Category </label>
                                        <!-- Updated dropdown options -->
                                        <select class="form-select" id="barcode_category" name="barcode_category">
                                            <option value="">Select</option>
                                            <option value="R">R</option>
                                            <option value="C">C</option>
                                            <option value="L">L</option>
                                            <option value="LL">LL</option>
                                            <option value="CC">CC</option>
                                            <option value="N">N</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="barcode_piece_name" class="form-label">Piece Name <span class="text-danger">*</span></label>
                                        <!-- Changed to dropdown menu -->
                                        <select class="form-select" id="barcode_piece_name" name="barcode_piece_name" required>
                                            <option value="0">Select</option>
                                            <option value="P">P</option>
                                            <option value="V">V</option>
                                            <option value="G">G</option>
                                            <option value="M">M</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Verify button and subsequent fields unchanged -->
                            <div class="mb-3 text-end">
                                <button type="button" id="verifyBtn" class="btn btn-dark btn-verify text-white w-100">
                                    <i class="fas fa-check-circle me-1"></i> Verify
                                </button>
                            </div>
                            <div id="verificationMessage" class="mt-2"></div>

                            <!-- Quantity section moved below the verify button -->
                            <div class="mb-4">
                                <div class="form-group mb-3">
                                    <label for="user_name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <select class="form-select" id="user_name" name="user_name" required>
                                        <option value="">Select Your Name</option>
                                        <option value="Othmane">Othmane</option>
                                        <option value="Othmane Jebar">Othmane Jebar</option>
                                        <option value="Brahim Akikab">Brahim Akikab</option>
                                        <option value="Mohamed Errhioui">Mohamed Errhioui</option>
                                    </select>
                                </div>


                                <div id="lost_barcode_options" class="mt-2">
                                    <div class="input-group">
                                        <span class="input-group-text">Quantity</span>
                                        <input type="number" class="form-control" id="lost_barcode_count" name="lost_barcode_count" value="1" min="1" max="100" required>
                                        <span class="input-group-text"><i class="fas fa-arrows-rotate"></i></span>
                                    </div>
                                    <small class="text-muted">Unique numbers will be generated for replacement barcodes</small>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="button" id="createBtn" class="btn btn-primary w-100" disabled>
                                    <i class="fa-solid fa-qrcode"></i> Create Barcode
                                </button>
                            </div>
                        </form>
                        
                        <!-- Add a success message area -->
                        <div id="successMessage" class="mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    
    
    <script>

        // New function to control UI elements state
        function updateUIState() {
            const isVerified = document.getElementById('verificationMessage').querySelector('.alert-success') !== null;
            const userNameValid = document.getElementById('user_name').value !== '';
            const quantityInput = document.getElementById('lost_barcode_count');
            const createBtn = document.getElementById('createBtn');

            // Enable quantity only after successful verification
            quantityInput.disabled = !isVerified;
            
            // Enable create button only when verified AND name is selected
            createBtn.disabled = !(isVerified && userNameValid);
        }

        // Initial state setup
        document.addEventListener('DOMContentLoaded', function() {
            updateUIState();
        });

        // Name dropdown change handler
        document.getElementById('user_name').addEventListener('change', updateUIState);

        document.getElementById('verifyBtn').addEventListener('click', function() {
            const verificationMessage = document.getElementById('verificationMessage');
            verificationMessage.innerHTML = '';
            
            const ofNumber = document.getElementById('barcode_prefix').value;
            let size = document.getElementById('barcode_size').value;
            let category = document.getElementById('barcode_category').value;
            const pieceName = document.getElementById('barcode_piece_name').value;

            // Only check barcode-related fields for verification
            if (!ofNumber || !size || pieceName === '0') {
                verificationMessage.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Please fill all required barcode fields
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                updateUIState();
                return;
            }

            // Show loading indicator
            verificationMessage.innerHTML = `
                <div class="alert alert-info" role="alert">
                    <div class="d-flex align-items-center">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Checking database...
                    </div>
                </div>
            `;

            fetch('check_barcode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    of_number: ofNumber,
                    size: size,
                    category: category,
                    piece_name: pieceName,
                    quantity: document.getElementById('lost_barcode_count').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    verificationMessage.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-1"></i> ${data.error}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                } else if (data.exists) {
                    verificationMessage.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-1"></i> ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                } else {
                    verificationMessage.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-times-circle me-1"></i> ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                }
                updateUIState();
            })
            .catch(error => {
                console.error('Error:', error);
                verificationMessage.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-times-circle me-1"></i> Error checking barcode availability: ${error.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                updateUIState();
            });
        });

        // Add event listener for the Create Barcode button
        document.getElementById('createBtn').addEventListener('click', function() {
            // Show loading indicator
            document.getElementById('successMessage').style.display = 'block';
            document.getElementById('successMessage').innerHTML = `
                <div class="alert alert-info" role="alert">
                    <div class="d-flex align-items-center">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Creating barcode...
                    </div>
                </div>
            `;

            // Get form data
            const formData = new FormData(document.getElementById('barcodeForm'));
            
            // Send AJAX request
            fetch('RTCpublic.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    document.getElementById('successMessage').innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-1"></i> Barcode created successfully! ${data.barcode} <br>Ready to print
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    
                    // Reset form fields
                    document.getElementById('barcodeForm').reset();
                    document.getElementById('createBtn').disabled = true;
                    document.getElementById('verificationMessage').innerHTML = '';
                } else {
                    // Show error message
                    document.getElementById('successMessage').innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-1"></i> ${data.message || 'Error creating barcode'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                }
                updateNotificationCount(); // Refresh the notification count
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('successMessage').innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-times-circle me-1"></i> Error creating barcode: ${error.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            });
        });
    </script>




</body>
</html>