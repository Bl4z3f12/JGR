<?php
$current_view = 'history.php';
require_once 'auth_functions.php';

// Redirect to login page if not logged in
requireLogin('login.php');

// Enhanced IP authorization check
$allowed_ips = ['127.0.0.1', '192.168.1.130', '::1', '192.168.1.14' ,'NEW_IP_HERE'];
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$client_ip = trim(explode(',', $client_ip)[0]);
$is_localhost = in_array($client_ip, ['127.0.0.1', '::1']) || 
               stripos($_SERVER['HTTP_HOST'], 'localhost') !== false;

if (!$is_localhost && !in_array($client_ip, $allowed_ips)) {
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <link rel="icon" href="assets\stop.ico" type="image/png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
        <style>
              
            * {
                position: relative;
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                background: linear-gradient(to bottom right, #eee, #aaa);
            }
            h1 {
                text-align: center;
                margin: 20px 0 20px;
            }
            .linkss a{
            }
            .lock {
                border-radius: 5px;
                width: 55px;
                height: 45px;
                background-color: #333;
                animation: dip 1s;
                animation-delay: 1.5s;
            }
            .lock::before, .lock::after {
                content: "";
                position: absolute;
                border-left: 5px solid #333;
                height: 20px;
                width: 15px;
                left: calc(50% - 12.5px);
            }
            .lock::before {
                top: -30px;
                border: 5px solid #333;
                border-bottom-color: transparent;
                border-radius: 15px 15px 0 0;
                height: 30px;
                animation: lock 2s, spin 2s;
            }
            .lock::after {
                top: -10px;
                border-right: 5px solid transparent;
                animation: spin 2s;
            }
            @keyframes lock {
                0% {
                    top: -45px;
                }
                65% {
                    top: -45px;
                }
                100% {
                    top: -30px;
                }
            }
            @keyframes spin {
                0% {
                    transform: scaleX(-1);
                    left: calc(50% - 30px);
                }
                65% {
                    transform: scaleX(1);
                    left: calc(50% - 12.5px);
                }
            }
            @keyframes dip {
                0% {
                    transform: translateY(0px);
                }
                50% {
                    transform: translateY(10px);
                }
                100% {
                    transform: translateY(0px);
                }
            }
            

        </style>
    </head>
    <body>
        <div class="lock"><i class="fa-solid fa-lock"></i></div>
        
        <div class="message">
            <h1 >Oops! Access to this page is restricted</h1>
            <p>You are not authorized to access this page. If you want full access to all services, contact the developer.
            <br>
            <br>
            You are authorized to access the following pages: <br>

            <div class="linkss">
                <a href="scantoday.php">scanned today</a> <br>
                <a href="production.php">production</a> <br>
                <a href="scanner_system_download.php">scanner system download</a>
            </div>

        </div>
    </body>
    </html>
    ');
}


// Database connection details
$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "jgr";

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Default values for form inputs
$of_number = isset($_GET['of_number']) ? $_GET['of_number'] : '';
$size = isset($_GET['size']) ? $_GET['size'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'select';
$p_name = isset($_GET['p_name']) ? $_GET['p_name'] : 'select';
$stage = isset($_GET['stage']) ? $_GET['stage'] : 'select';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'summary';
// Handle search form submission
$where_conditions = [];
$params = [];
$types = '';
// Build query conditions based on search parameters
if (!empty($of_number)) {
    $where_conditions[] = "of_number LIKE ?";
    $params[] = "%$of_number%";
    $types .= 's';
}
if (!empty($size)) {
    $where_conditions[] = "size = ?";
    $params[] = $size;
    $types .= 's';
}
if ($category !== 'select') {
    $where_conditions[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($p_name !== 'select') {
    $where_conditions[] = "piece_name LIKE ?";
    $params[] = "%$p_name%";
    $types .= 's';
}
// Add date filter - convert to both date start and end to capture full day
if (!empty($date)) {
    $date_start = $date . ' 00:00:00';
    $date_end = $date . ' 23:59:59';
    $where_conditions[] = "action_time BETWEEN ? AND ?";
    $params[] = $date_start;
    $params[] = $date_end;
    $types .= 'ss';
}
// Handle stage filter specifically for barcodes table
$barcode_specific_conditions = [];
if ($stage !== 'select') {
    $barcode_specific_conditions[] = "stage = ?";
    // We'll add this parameter separately when querying the barcode table
}
// Function to display aggregated barcode data
function displayBarcodeSummary($conn, $where_conditions, $params, $types, $stage) {
    $barcode_query = "SELECT 
                        of_number, 
                        size, 
                        category, 
                        piece_name, 
                        stage,
                        order_str,
                        COUNT(*) as barcode_count,
                        GROUP_CONCAT(DISTINCT status) as statuses,
                        MAX(action_time) as latest_update
                    FROM jgr_barcodes_history 
                    WHERE 1=1";
    
    if (!empty($where_conditions)) {
        $barcode_query .= " AND " . implode(" AND ", $where_conditions);
    }
    
    if ($stage !== 'select') {
        $barcode_query .= " AND stage = ?";
        $barcode_params = $params;
        $barcode_params[] = $stage;
        $barcode_types = $types . 's';
    } else {
        $barcode_params = $params;
        $barcode_types = $types;
    }
    
    $barcode_query .= " GROUP BY of_number, size, category, piece_name, stage 
                        ORDER BY latest_update DESC LIMIT 200";
    
    $stmt = $conn->prepare($barcode_query);
    
    if (!empty($barcode_params)) {
        $stmt->bind_param($barcode_types, ...$barcode_params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped table-hover'>";
    echo "<thead class='table-dark'>";
    echo "<tr>";
    echo "<th>Last Update</th>";
    echo "<th>OF Number</th>";
    echo "<th>Size</th>";
    echo "<th>Category</th>";
    echo "<th>P Name</th>";
    echo "<th>Stage</th>";
    echo "<th>Barcode Count</th>";
    echo "<th>Statuses</th>";
    echo "<th>Details</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['latest_update']) . "</td>";
            echo "<td>" . htmlspecialchars($row['of_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['size']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category']) . "</td>";
            echo "<td>" . htmlspecialchars($row['piece_name']) . "</td>";
            
            echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($row['stage']) . "</span></td>";
            echo "<td><span class='badge bg-primary'>" . htmlspecialchars($row['barcode_count']) . "</span></td>";
            echo "<td>" . htmlspecialchars($row['statuses']) . "</td>";
            
            // Button to view detailed history for this specific combination
            $detailsId = md5($row['of_number'] . $row['size'] . $row['category'] . $row['piece_name'] . $row['stage']);
            echo "<td>";
            echo "<button type='button' class='btn btn-sm btn-outline-info' data-bs-toggle='modal' data-bs-target='#detailsModal{$detailsId}'>";
            echo "<i class='fa-solid fa-eye me-1'></i>"; // Changed to eye icon with margin
            echo "</button>";
            echo "</td>";
            echo "</tr>";            
        }
    } else {
        echo "<tr><td colspan='9' class='text-center'>No records found</td></tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
    // Now create all modals OUTSIDE the table structure
    if ($result->num_rows > 0) {
        // Reset result pointer
        $result->data_seek(0);
        
        while ($row = $result->fetch_assoc()) {
            $detailsId = md5($row['of_number'] . $row['size'] . $row['category'] . $row['piece_name'] . $row['stage']);
            // Create modal for detailed history
            echo "<div class='modal fade' id='detailsModal{$detailsId}' tabindex='-1' aria-hidden='true'>";
            echo "<div class='modal-dialog modal-xl'>";
            echo "<div class='modal-content'>";
            echo "<div class='modal-header'>";
            echo "<h5 class='modal-title'>Individual Barcode Details</h5>";
            echo "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>";
            echo "</div>";
            echo "<div class='modal-body'>";
            
            // Modified query to get individual barcode data
            // Using a combination of fields to identify unique barcodes
            $detail_query = "SELECT 
                                id, 
                                action_time, 
                                action_type, 
                                status, 
                                chef,
                                order_str 
                            FROM jgr_barcodes_history 
                            WHERE of_number = ? 
                            AND size = ? 
                            AND category = ? 
                            AND piece_name = ? 
                            AND stage = ?
                            ORDER BY order_str, action_time DESC";
                            
            $detail_stmt = $conn->prepare($detail_query);
            $detail_stmt->bind_param("sssss", 
                $row['of_number'], 
                $row['size'], 
                $row['category'], 
                $row['piece_name'], 
                $row['stage']
            );
            $detail_stmt->execute();
            $detail_result = $detail_stmt->get_result();
            
            echo "<div class='table-responsive'>";
            echo "<table class='table table-sm'>";
            echo "<thead class='table-dark'>"; // Added the table-dark class for dark background with white text
            echo "<tr>";
            echo "<th>OF</th>";
            echo "<th>Size</th>";
            echo "<th>Category</th>";
            echo "<th>P Name</th>";
            echo "<th>Order</th>";
            echo "<th>Stage</th>";
            echo "<th>Status</th>";
            echo "<th>Chef</th>";
            echo "<th>Date/Time</th>";
            echo "<th>Action</th>";
            echo "</tr>";
            echo "</thead>";
                        
            $current_order = null;
            $row_class = '';
            
            while ($detail = $detail_result->fetch_assoc()) {
                // Add alternating background for different orders for better readability
                if ($current_order !== $detail['order_str']) {
                    $current_order = $detail['order_str'];
                    $row_class = ($row_class === 'table-light') ? '' : 'table-light';
                }
                
                $action_class = '';
                switch ($detail['action_type']) {
                    case 'INSERT': $action_class = 'text-success'; break;
                    case 'UPDATE': $action_class = 'text-primary'; break;
                    case 'DELETE': $action_class = 'text-danger'; break;
                }
                
                echo "<tr class='{$row_class}'>";
                echo "<td>" . htmlspecialchars($row['of_number']) . "</td>";
                echo "<td>" . htmlspecialchars($row['size']) . "</td>";
                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                echo "<td>" . htmlspecialchars($row['piece_name']) . "</td>";
                echo "<td><strong>" . htmlspecialchars($detail['order_str']) . "</strong></td>";
                echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($row['stage']) . "</span></td>";
                echo "<td>" . htmlspecialchars($detail['status']) . "</td>";
                echo "<td>" . htmlspecialchars($detail['chef']) . "</td>";
                echo "<td>" . htmlspecialchars($detail['action_time']) . "</td>";
                echo "<td><span class='{$action_class}'>" . htmlspecialchars($detail['action_type']) . "</span></td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
            $detail_stmt->close();
            
            echo "</div>";
            echo "<div class='modal-footer'>";
            echo "<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
    }
    
    $stmt->close();
}

// Function to display quantity data
function displayQuantityData($result) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped table-hover'>";
    
    echo "<thead class='table-dark'>";
    echo "<tr>";
    echo "<th>Date/Time</th>";
    echo "<th>Action</th>";
    echo "<th>OF Number</th>";
    echo "<th>Size</th>";
    echo "<th>Category</th>";
    echo "<th>P Name</th>";
    echo "<th>Main Quan</th>";
    echo "<th>Cut Quan</th>";
    echo "<th>Missing</th>";
    echo "<th>Surplus</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['action_time']) . "</td>";
            
            // Format action type with color coding
            $action_class = '';
            switch ($row['action_type']) {
                case 'INSERT': $action_class = 'text-success'; break;
                case 'UPDATE': $action_class = 'text-primary'; break;
                case 'DELETE': $action_class = 'text-danger'; break;
            }
            echo "<td><span class='{$action_class}'>" . htmlspecialchars($row['action_type']) . "</span></td>";
            
            echo "<td>" . htmlspecialchars($row['of_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['size']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category']) . "</td>";
            echo "<td>" . htmlspecialchars($row['piece_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['principale_quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($row['quantity_coupe']) . "</td>";
            echo "<td>" . htmlspecialchars($row['manque']) . "</td>";
            echo "<td>" . htmlspecialchars($row['suv_plus']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='10' class='text-center'>No records found</td></tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
}

// Function to display traditional barcode data (for the original barcode tab)
function displayBarcodeData($result) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped table-hover'>";
    
    echo "<thead class='table-dark'>";
    echo "<tr>";
    echo "<th>Date/Time</th>";
    echo "<th>Action</th>";
    echo "<th>OF Number</th>";
    echo "<th>Size</th>";
    echo "<th>Category</th>";
    echo "<th>P Name</th>";
    echo "<th>Status</th>";
    echo "<th>Stage</th>";
    echo "<th>Chef</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['action_time']) . "</td>";
            
            // Format action type with color coding
            $action_class = '';
            switch ($row['action_type']) {
                case 'INSERT': $action_class = 'text-success'; break;
                case 'UPDATE': $action_class = 'text-primary'; break;
                case 'DELETE': $action_class = 'text-danger'; break;
            }
            echo "<td><span class='{$action_class}'>" . htmlspecialchars($row['action_type']) . "</span></td>";
            
            echo "<td>" . htmlspecialchars($row['of_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['size']) . "</td>";
            echo "<td>" . htmlspecialchars($row['category']) . "</td>";
            echo "<td>" . htmlspecialchars($row['piece_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['stage']) . "</td>";
            echo "<td>" . htmlspecialchars($row['chef']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='9' class='text-center'>No records found</td></tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Records</title>
    <link rel="stylesheet" href="assets/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Layout styles for fixed header and sidebar with scrollable content */
        html, body {
            height: 100%;
            overflow: hidden;
        }
        
        .main-content {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        
        /* Responsive table styles */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
            
        /* Enhanced modals for mobile */
        .modal-dialog {
            max-width: 95%;
            margin: 10px auto;
        }
        
        @media (min-width: 768px) {
            .modal-dialog {
                max-width: 700px;
            }
        }
        
        /* Responsive styling for different screen sizes */
        @media (max-width: 768px) {
            .card-body {
                padding: 0.75rem;
            }
            
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
            
            .form-label {
                font-size: 0.875rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            
            /* Ensure horizontal scrolling works nicely on mobile */
            .table-responsive {
                margin-bottom: 1rem;
            }
        }
        
        /* Fix for sticky table headers */
        .table-responsive thead th {
            position: sticky;
            top: 0;
            background-color: #212529;
            z-index: 10;
        }
    </style>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <!-- Scrollable content area -->
        <div class="content-area">
            <div class="container-fluid">
                <h2 class="mb-4" style="font-size: 18px;">History Records</h2>
                
                <div class="alert alert-warning text-left m-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i> <span style="font-weight: bold;">Please Note:</span> History records (logs) are removed from our server after 3 months.
                </div>
                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Search History</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                            
                            <div class="col-md-2 col-sm-6">
                                <label for="of_number" class="form-label">OF Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                                    <input type="number" class="form-control" id="of_number" name="of_number" placeholder="Enter OF Number"
                                        value="<?php echo htmlspecialchars($of_number); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-2 col-sm-6">
                                <label for="size" class="form-label">Size</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-ruler"></i></span>
                                    <input type="number" class="form-control" id="size" name="size" placeholder="Enter Size" 
                                        value="<?php echo htmlspecialchars($size); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-2 col-sm-6">
                                <label for="category" class="form-label">Category</label>

                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-tags"></i></span>
                                    <select class="form-select" id="category" name="category">
                                        <option value="select" <?php echo ($category === 'select') ? 'selected' : ''; ?>>All Categories</option>
                                        <?php
                                        $category_options = ['R', 'C', 'L', 'LL', 'CC', 'N'];
                                        foreach ($category_options as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>" 
                                                    <?php echo ($category === $option) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2 col-sm-6">
                                <label for="p_name" class="form-label">P Name</label>

                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-puzzle-piece"></i></span>
                                    <select class="form-select" id="p_name" name="p_name">
                                        <option value="select" <?php echo ($p_name === 'select') ? 'selected' : ''; ?>>All Pieces</option>
                                        <?php
                                        $p_name_options = ['P', 'V', 'G', 'M'];
                                        foreach ($p_name_options as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>" 
                                                    <?php echo ($p_name === $option) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2 col-sm-6">
                                <label for="stage" class="form-label">Stage</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-layer-group"></i></span>
                                    <select class="form-select" id="stage" name="stage">
                                        <option value="select" <?php echo ($stage === 'select') ? 'selected' : ''; ?>>All Stages</option>
                                        <?php
                                        $stage_options = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_ fini'];
                                        foreach ($stage_options as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>" 
                                                    <?php echo ($stage === $option) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2 col-sm-6">
                                <label for="date" class="form-label">Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
                                    <!-- Use date input type for better date selection -->
                                    <input type="date" class="form-control" id="date" name="date" 
                                        value="<?php echo htmlspecialchars($date); ?>">
                                </div>
                            
                            <div class="col-12 mt-3">
                                <button type="submit" name="search" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                                <a href="?tab=<?php echo $tab; ?>" class="btn btn-secondary"><i class="fa-solid fa-broom"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Nav tabs for different history tables -->
                <ul class="nav nav-tabs mb-3" id="historyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo ($tab === 'summary') ? 'active' : ''; ?>" 
                                id="summary-tab" href="?tab=summary<?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . preg_replace('/(&)?tab=[^&]*(&)?/', '$1$2', $_SERVER['QUERY_STRING']) : ''; ?>" 
                                role="tab">
                            Barcode Summary
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo ($tab === 'quantity') ? 'active' : ''; ?>" 
                                id="quantity-tab" href="?tab=quantity<?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . preg_replace('/(&)?tab=[^&]*(&)?/', '$1$2', $_SERVER['QUERY_STRING']) : ''; ?>" 
                                role="tab">
                            Quantity History
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo ($tab === 'barcode') ? 'active' : ''; ?>" 
                                id="barcode-tab" href="?tab=barcode<?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . preg_replace('/(&)?tab=[^&]*(&)?/', '$1$2', $_SERVER['QUERY_STRING']) : ''; ?>" 
                                role="tab">
                            Detailed Barcode History
                        </a>
                    </li>
                </ul>
                
                <!-- Tab content -->
                <div class="tab-content" id="historyTabsContent">
                    <!-- Summary tab (Aggregated Barcode counts) -->
                    <div class="tab-pane fade <?php echo ($tab === 'summary') ? 'show active' : ''; ?>" 
                        id="summary" role="tabpanel" aria-labelledby="summary-tab">
                        <?php
                        if (isset($_GET['search']) || isset($_GET['tab'])) {
                            echo "<h4>Barcode Summary by OF Number, Size, Category, P Name and Stage</h4>";
                            echo "<p>Showing aggregated barcode counts and latest updates</p>";
                            
                            // Display aggregated barcode data
                            displayBarcodeSummary($conn, $where_conditions, $params, $types, $stage);
                        } else {
                            echo "<div class='alert alert-info'>Please use the search form above to find records.</div>";
                        }
                        ?>
                    </div>
                    
                    <!-- Quantity Coupe History tab -->
                    <div class="tab-pane fade <?php echo ($tab === 'quantity') ? 'show active' : ''; ?>" 
                        id="quantity" role="tabpanel" aria-labelledby="quantity-tab">
                        <?php
                        if (isset($_GET['search']) || isset($_GET['tab'])) {
                            $sql_quantity = "SELECT * FROM jgr_quantity_coupe_history WHERE 1=1";
                            
                            if (!empty($where_conditions)) {
                                $sql_quantity .= " AND " . implode(" AND ", $where_conditions);
                            }
                            
                            $sql_quantity .= " ORDER BY action_time DESC LIMIT 200";
                            
                            $stmt_quantity = $conn->prepare($sql_quantity);
                            
                            if (!empty($params)) {
                                $stmt_quantity->bind_param($types, ...$params);
                            }
                            
                            $stmt_quantity->execute();
                            $result_quantity = $stmt_quantity->get_result();
                            
                            echo "<h4>Quantity Coupe History Records</h4>";
                            displayQuantityData($result_quantity);
                            
                            $stmt_quantity->close();
                        } else {
                            echo "<div class='alert alert-info'>Please use the search form above to find records.</div>";
                        }
                        ?>
                    </div>
                    
                    <!-- Detailed Barcodes History tab -->
                    <div class="tab-pane fade <?php echo ($tab === 'barcode') ? 'show active' : ''; ?>" 
                        id="barcode" role="tabpanel" aria-labelledby="barcode-tab">
                        <?php
                        if (isset($_GET['search']) || isset($_GET['tab'])) {
                            $sql_barcode = "SELECT * FROM jgr_barcodes_history WHERE 1=1";
                            
                            if (!empty($where_conditions)) {
                                $sql_barcode .= " AND " . implode(" AND ", $where_conditions);
                            }
                            
                            if ($stage !== 'select') {
                                $sql_barcode .= " AND stage = ?";
                                $barcode_params = $params;
                                $barcode_params[] = $stage;
                                $barcode_types = $types . 's';
                            } else {
                                $barcode_params = $params;
                                $barcode_types = $types;
                            }
                            
                            $sql_barcode .= " ORDER BY action_time DESC LIMIT 200";
                            
                            $stmt_barcode = $conn->prepare($sql_barcode);
                            
                            if (!empty($barcode_params)) {
                                $stmt_barcode->bind_param($barcode_types, ...$barcode_params);
                            }
                            
                            $stmt_barcode->execute();
                            $result_barcode = $stmt_barcode->get_result();
                            
                            echo "<h4>Detailed Barcode History Records</h4>";
                            displayBarcodeData($result_barcode);
                            
                            $stmt_barcode->close();
                        } else {
                            echo "<div class='alert alert-info'>Please use the search form above to find records.</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>