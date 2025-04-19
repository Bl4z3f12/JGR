<?php
require_once __DIR__ . '/vendor/autoload.php'; // For FPDF

// Initialize variables
$current_date = date("Y-m-d");
$filter_of_number = $_GET['of_number'] ?? '';
$filter_stage = $_GET['stage'] ?? '';
$filter_date = $_GET['date'] ?? $current_date;
$view_mode = 'summary'; // Always use summary view
$group_by_of = true; // Always group by OF number

// Establish database connection
function connectDB() {
    $conn = new mysqli("localhost", "root", "", "jgr2");
    return $conn->connect_error ? false : $conn;
}

// Function to get OF summary data with totals
function getOFSummaryData($of_number, $stage, $date) {
    $conn = connectDB();
    if (!$conn) return [];

    // Build WHERE clause based on filters
    $where_clauses = ["DATE(last_update) = ?"];
    $param_types = "s";
    $params = [$date];
    
    if (!empty($of_number)) {
        $where_clauses[] = "of_number LIKE ?";
        $param_types .= "s";
        $params[] = "%$of_number%";
    }
    
    if (!empty($stage)) {
        $where_clauses[] = "stage = ?";
        $param_types .= "s";
        $params[] = $stage;
    }
    
    $where = "WHERE " . implode(" AND ", $where_clauses);
    
    // Query to get individual barcodes grouped by OF number, size, category, and piece_name
    $sql = "SELECT 
                of_number,
                size,
                category,
                piece_name,
                COUNT(*) as count
            FROM barcodes 
            $where 
            GROUP BY of_number, size, category, piece_name
            ORDER BY of_number ASC, size ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $summary_data = [];
    $current_of = '';
    $of_total = 0;
    $grand_total = 0;
    
    while ($row = $result->fetch_assoc()) {
        // If we've moved to a new OF number, add the total for the previous one
        if ($current_of !== '' && $current_of !== $row['of_number']) {
            $summary_data[] = [
                'of_number' => $current_of,
                'is_total' => true,
                'count' => $of_total
            ];
            $of_total = 0;
        }
        
        $current_of = $row['of_number'];
        $of_total += $row['count'];
        $grand_total += $row['count'];
        
        $summary_data[] = [
            'of_number' => $row['of_number'],
            'size' => $row['size'],
            'category' => $row['category'],
            'piece_name' => $row['piece_name'],
            'count' => $row['count'],
            'is_total' => false
        ];
    }
    
    // Add the total for the last OF number
    if ($current_of !== '') {
        $summary_data[] = [
            'of_number' => $current_of,
            'is_total' => true,
            'count' => $of_total
        ];
    }
    
    // Add the grand total
    $summary_data[] = [
        'of_number' => 'GRAND TOTAL',
        'is_total' => true,
        'is_grand_total' => true,
        'count' => $grand_total
    ];
    
    $stmt->close();
    $conn->close();
    return $summary_data;
}

// Function to get list of stages for dropdown
function getStages() {
    // Define static stage options
    $stage_options = [
        "Coupe",
        "V1",
        "V2",
        "V3",
        "Pantalon",
        "Repassage",
        "P_fini"
    ];
    
    // Return the static array of stage options
    return $stage_options;
    
    /* Original database query code - commented out
    $conn = connectDB();
    if (!$conn) return [];
    
    $sql = "SELECT DISTINCT stage FROM barcodes WHERE stage IS NOT NULL ORDER BY stage";
    $result = $conn->query($sql);
    
    $stages = [];
    while ($row = $result->fetch_assoc()) {
        $stages[] = $row['stage'];
    }
    
    $conn->close();
    return $stages;
    */
}

// Export to Excel (Tab-delimited TXT)
if (isset($_GET['export'])) {
    $conn = connectDB();
    if (!$conn) {
        echo "Database connection failed";
        exit;
    }
    
    $summary_data = getOFSummaryData($filter_of_number, $filter_stage, $filter_date);
    
    // Create excel directory if it doesn't exist
    $exelDir = __DIR__ . '/exel';
    if (!file_exists($exelDir)) {
        mkdir($exelDir, 0755, true);
    }
    
    // Generate filename
    $filename = 'barcodes_summary_' . date('Y-m-d_H-i-s') . '.xls';
    $filepath = $exelDir . '/' . $filename;
    
    // Open file for writing
    $file = fopen($filepath, 'w');
    
    // Excel XML header
    fwrite($file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
    fwrite($file, "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n");
    fwrite($file, "<Worksheet ss:Name=\"Barcodes\">\n");
    fwrite($file, "<Table>\n");
    
    // Header row
    fwrite($file, "<Row>\n");
    fwrite($file, "<Cell><Data ss:Type=\"String\">OF</Data></Cell>\n");
    fwrite($file, "<Cell><Data ss:Type=\"String\">Size</Data></Cell>\n");
    fwrite($file, "<Cell><Data ss:Type=\"String\">Category</Data></Cell>\n");
    fwrite($file, "<Cell><Data ss:Type=\"String\">P_Name</Data></Cell>\n");
    fwrite($file, "<Cell><Data ss:Type=\"String\">" . htmlspecialchars("Inside P_fini Sum") . "</Data></Cell>\n");
    fwrite($file, "</Row>\n");
    
    // Data rows
    foreach ($summary_data as $row) {
        fwrite($file, "<Row>\n");
        
        if (isset($row['is_total']) && $row['is_total']) {
            if (isset($row['is_grand_total']) && $row['is_grand_total']) {
                fwrite($file, "<Cell><Data ss:Type=\"String\">GRAND TOTAL</Data></Cell>\n");
            } else {
                fwrite($file, "<Cell><Data ss:Type=\"String\">OF_TOTAL</Data></Cell>\n");
            }
            fwrite($file, "<Cell><Data ss:Type=\"String\"></Data></Cell>\n");
            fwrite($file, "<Cell><Data ss:Type=\"String\"></Data></Cell>\n");
            fwrite($file, "<Cell><Data ss:Type=\"String\"></Data></Cell>\n");
            fwrite($file, "<Cell><Data ss:Type=\"Number\">" . htmlspecialchars($row['count']) . "</Data></Cell>\n");
        } else {
            fwrite($file, "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['of_number']) . "</Data></Cell>\n");
            fwrite($file, "<Cell><Data ss:Type=\"Number\">" . htmlspecialchars($row['size']) . "</Data></Cell>\n");
            fwrite($file, "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['category'] ?: '') . "</Data></Cell>\n");
            fwrite($file, "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($row['piece_name']) . "</Data></Cell>\n");
            fwrite($file, "<Cell><Data ss:Type=\"Number\">" . htmlspecialchars($row['count']) . "</Data></Cell>\n");
        }
        
        fwrite($file, "</Row>\n");
    }
    
    // Close XML tags
    fwrite($file, "</Table>\n");
    fwrite($file, "</Worksheet>\n");
    fwrite($file, "</Workbook>\n");
    
    fclose($file);
    $conn->close();
    
    // Redirect back to the page with success message
    header("Location: scantoday.php?export_success=1&filename=" . urlencode($filename) . 
           "&of_number=" . urlencode($filter_of_number) . 
           "&stage=" . urlencode($filter_stage) . 
           "&date=" . urlencode($filter_date));
    exit;
}

// Get stages for dropdown
$stages = getStages();

// Get OF summary data
$summary_data = getOFSummaryData($filter_of_number, $filter_stage, $filter_date);

// Success messages
$export_success = isset($_GET['export_success']) && $_GET['export_success'] == 1;
$exported_filename = $_GET['filename'] ?? '';

// Get the title based on stage
$title = "Inside P_fini for " . ($filter_stage ?: "All Stages") . " - " . date('d/m/Y', strtotime($filter_date));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OF Summary - Barcode System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-dismissible {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        .table th, .table td {
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }
        .table thead th {
            background-color: #007bff;
            text-align: center;
            color: white;
        }
        .total-row {
            background-color: #d1e7dd !important;
            font-weight: bold;
        }
        .grand-total-row {
            background-color: #a3cfbb !important;
            font-weight: bold;
        }
        .show-more-btn {
            margin-left: 10px;
            background-color: #007bff; /* Bootstrap primary color */
            color: white;
            cursor: pointer; /* Added cursor style for better UX */
        }
        .show-more-btn:hover {
            color: white;

            background-color: #0056b3; /* Darker shade on hover */
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if ($export_success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Data exported to exel/<?php echo htmlspecialchars($exported_filename); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <h1 class="mb-4">
            <i class="fas fa-barcode me-2"></i>
            OF Summary
        </h1>
        
        <div class="filter-section">
            <form action="scantoday.php" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="of_number" class="form-label">OF Number</label>
                    <input type="text" class="form-control" id="of_number" name="of_number" 
                           value="<?php echo htmlspecialchars($filter_of_number); ?>" placeholder="Enter OF Number">
                </div>
                <div class="col-md-3">
                    <label for="stage" class="form-label">Stage</label>
                    <select class="form-select" id="stage" name="stage">
                        <option value="">All Stages</option>
                        <?php foreach ($stages as $stage): ?>
                        <option value="<?php echo htmlspecialchars($stage); ?>" <?php echo $filter_stage === $stage ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($stage); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="text" class="form-control datepicker" id="date" name="date" 
                           value="<?php echo htmlspecialchars($filter_date); ?>" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="scantoday.php" class="btn btn-secondary me-2">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                    <a href="scantoday.php?export=1&of_number=<?php echo urlencode($filter_of_number); ?>&stage=<?php echo urlencode($filter_stage); ?>&date=<?php echo urlencode($filter_date); ?>" 
                       class="btn btn-success">
                        <i class="fas fa-file-excel me-1"></i> Export
                    </a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 text-center">
                    <?php echo htmlspecialchars($title); ?>                    
                    <button id="showMoreBtn" class="btn btn-sm show-more-btn">
                        Show details <i class="fa-solid fa-circle-info"></i>
                    </button>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" >
                        <thead>
                            <tr class="text-center">
                                <th>OF</th>
                                <th>Size</th>
                                <th>Category</th>
                                <th>P_Name</th>
                                <th>P_Sum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($summary_data)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No data found for the selected filters</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($summary_data as $row): ?>
                                <?php if (isset($row['is_total']) && $row['is_total']): ?>
                                    <?php if (isset($row['is_grand_total']) && $row['is_grand_total']): ?>
                                    <tr class="grand-total-row">
                                        <td>GRAND TOTAL</td>
                                        <td colspan="3"></td>
                                        <td class="text-center"><?php echo htmlspecialchars($row['count']); ?></td>
                                    </tr>
                                    <?php else: ?>
                                    <tr class="total-row">
                                        <td>OF_TOTAL</td>
                                        <td colspan="3"></td>
                                        <td class="text-center"><?php echo htmlspecialchars($row['count']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                <?php else: ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['of_number']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['size']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['category'] ?: ''); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['piece_name']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['count']); ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(document).ready(function(){
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
            
            // Auto-hide alert after 5 seconds
            setTimeout(function(){
                $('.alert-dismissible').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>