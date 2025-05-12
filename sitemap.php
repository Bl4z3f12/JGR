please complete this code

<?php
// Set the current page for navigation highlighting
$current_view = 'barcode_manager.php';

// Include authentication functions
require_once 'auth_functions.php';

// Set timezone
date_default_timezone_set('Africa/Casablanca');

// Require login, redirect to login page if not authenticated
requireLogin('login.php');

// IP address restriction for additional security
$allowed_ips = ['127.0.0.1', '192.168.1.130', '::1', 'YOUR_IP_HERE'];
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$client_ip = trim(explode(',', $client_ip)[0]);
$is_localhost = in_array($client_ip, ['127.0.0.1', '::1']) || 
               stripos($_SERVER['HTTP_HOST'], 'localhost') !== false;

// If not authorized IP, show access denied page
if (!$is_localhost && !in_array($client_ip, $allowed_ips)) {
    include 'access_denied.php';
    exit;
}

// Include database connection and settings
require_once 'settings.php';

// Initialize variables for search parameters
$of_number_search = $_GET['of_number_search'] ?? '';
$size_search = $_GET['size_search'] ?? '';
$category_search = $_GET['category_search'] ?? '';
$piece_name_search = $_GET['piece_name_search'] ?? '';
$order_str_search = $_GET['order_str_search'] ?? '';
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 20;

// Handle barcode editing
$edit_barcode = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    // Fetch the barcode to edit
    // Replace with your actual database code
    $edit_id = intval($_GET['edit']);
    // Example query:
    // $stmt = $pdo->prepare("SELECT * FROM barcodes WHERE id = ?");
    // $stmt->execute([$edit_id]);
    // $edit_barcode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Mock data for example
    $edit_barcode = [
        'id' => $edit_id,
        'of_number' => '12345',
        'size' => '42',
        'category' => 'R',
        'piece_name' => 'P',
        'order_str' => '001',
        'stage' => 'V1',
        'chef' => 'Abdelkarim',
        'status' => 'In Progress',
        'last_update' => date('Y-m-d')
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit') {
        // Handle edit form submission
        $barcode_id = $_POST['barcode_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $stage = $_POST['stage'] ?? '';
        $chef = $_POST['chef'] ?? '';
        $last_update = $_POST['last_update'] ?? date('Y-m-d');
        
        // Update the barcode in the database
        // Example query:
        // $stmt = $pdo->prepare("UPDATE barcodes SET status = ?, stage = ?, chef = ?, last_update = ? WHERE id = ?");
        // $stmt->execute([$status, $stage, $chef, $last_update, $barcode_id]);
        
        $success_message = "Barcode updated successfully!";
        
        // Redirect to avoid form resubmission
        header('Location: ' . buildSearchUrl(['success' => 1]));
        exit;
    } elseif ($action === 'bulk_edit') {
        // Handle bulk edit submission
        $barcode_ids = $_POST['barcode_ids'] ?? [];
        $bulk_status = $_POST['bulk_status'] ?? '';
        $bulk_stage = $_POST['bulk_stage'] ?? '';
        $bulk_chef = $_POST['bulk_chef'] ?? '';
        $bulk_timestamp = $_POST['bulk_timestamp'] ?? date('Y-m-d');
        
        if (!empty($barcode_ids)) {
            // Update multiple barcodes
            // Example query using a prepared statement with multiple executions
            
            $success_message = count($barcode_ids) . " barcodes updated successfully!";
        }
        
        // Redirect to avoid form resubmission
        header('Location: ' . buildSearchUrl(['success' => 1]));
        exit;
    } elseif ($action === 'delete') {
        // Handle delete submission
        $barcode_ids = $_POST['barcode_ids'] ?? [];
        
        if (!empty($barcode_ids)) {
            // Delete multiple barcodes
            // Example query using a prepared statement with multiple executions
            
            $success_message = count($barcode_ids) . " barcodes deleted successfully!";
        }
        
        // Redirect to avoid form resubmission
        header('Location: ' . buildSearchUrl(['success' => 1]));
        exit;
    }
}

// Show success message if redirected after successful operation
if (isset($_GET['success'])) {
    $success_message = "Operation completed successfully!";
}

// Function to build search URL with pagination
function buildSearchUrl($params = []) {
    $search_params = [
        'of_number_search' => $_GET['of_number_search'] ?? '',
        'size_search' => $_GET['size_search'] ?? '',
        'category_search' => $_GET['category_search'] ?? '',
        'piece_name_search' => $_GET['piece_name_search'] ?? '',
        'order_str_search' => $_GET['order_str_search'] ?? '',
        'date_to' => $_GET['date_to'] ?? date('Y-m-d')
    ];
    
    $merged_params = array_merge($search_params, $params);
    return 'barcode_manager.php?' . http_build_query(array_filter($merged_params));
}

// Format date for input fields
function formatDateForInput($date) {
    return date('Y-m-d', strtotime($date));
}

// Format datetime for input fields
function formatDatetimeForInput($datetime) {
    return date('Y-m-d', strtotime($datetime));
}

// Mock data for example - Replace with actual database query
$barcodes = [];
$total_barcodes = 0;

// Only run the query if search parameters are provided
if (isset($_GET['of_number_search']) || isset($_GET['size_search']) || 
    isset($_GET['category_search']) || isset($_GET['piece_name_search']) || 
    isset($_GET['order_str_search'])) {
    
    // Example query building
    /*
    $sql = "SELECT * FROM barcodes WHERE 1=1";
    $params = [];
    
    if (!empty($of_number_search)) {
        $sql .= " AND of_number LIKE ?";
        $params[] = "%$of_number_search%";
    }
    // Add other search conditions...
    
    // Count total for pagination
    $count_sql = str_replace("SELECT *", "SELECT COUNT(*)", $sql);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_barcodes = $count_stmt->fetchColumn();
    
    // Add pagination
    $sql .= " ORDER BY id DESC LIMIT " . (($page - 1) * $items_per_page) . ", " . $items_per_page;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $barcodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    */
    
    // Mock data for example
    $total_barcodes = 125;
    
    // Generate mock data
    for ($i = 1; $i <= 20; $i++) {
        $status_options = ['Completed', 'In Progress', 'Pending'];
        $chef_options = ['Abdelkarim', 'Mohamed', 'Hana Hajouji', 'Saaid Kahlaoui'];
        $stage_options = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_ fini'];
        
        $barcodes[] = [
            'id' => $i,
            'of_number' => $of_number_search ?: rand(10000, 99999),
            'size' => $size_search ?: (40 + ($i % 10)),
            'category' => $category_search ?: ['R', 'C', 'L', 'CC', 'N'][$i % 5],
            'piece_name' => $piece_name_search ?: ['P', 'V', 'G', 'M'][$i % 4],
            'order_str' => $order_str_search ?: sprintf('%03d', $i),
            'stage' => $stage_options[$i % count($stage_options)],
            'chef' => $chef_options[$i % count($chef_options)],
            'status' => $status_options[$i % 3],
            'last_update' => date('Y-m-d', strtotime("-" . ($i % 30) . " days"))
        ];
    }
}

// Calculate total pages for pagination
$total_pages = ceil($total_barcodes / $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Barcode Manager</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #004AAD;
            --secondary-color: #007BFF;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        
        .sidebar-nav {
            padding: 0;
            list-style: none;
        }
        
        .sidebar-nav li {
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav .nav-link {
            padding: 15px 20px;
            color: white;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .sidebar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }
        
        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            border-left-color: #fff;
            font-weight: 500;
        }
        
        .sidebar-nav .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 15px;
            transition: all 0.3s;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .navbar-nav .nav-item {
            margin-left: 10px;
        }
        
        .navbar-brand {
            font-weight: 600;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-control, .form-select {
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            padding: 8px 12px;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            border-color: var(--secondary-color);
        }
        
        .btn {
            border-radius: 5px;
            padding: 8px 16px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #003a87;
            border-color: #003a87;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            border-bottom: 2px solid #e0e0e0;
            font-weight: 600;
            color: #444;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .pagination {
            margin-bottom: 0;
        }
        
        .page-link {
            color: var(--primary-color);
            border: 1px solid #dee2e6;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .alert {
            border: none;
            border-radius: 5px;
        }
        
        /* Custom styles for the edit modal */
        .modal-content {
            border-radius: 10px;
            border: none;
        }
        
        .modal-header {
            border-bottom: 1px solid #f0f0f0;
            background-color: white;
        }
        
        .modal-footer {
            border-top: 1px solid #f0f0f0;
        }
        
        /* Responsive styles */
        @media (max-width: 991px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-nav .nav-link span {
                display: none;
            }
            
            .sidebar-header .sidebar-brand {
                font-size: 1.2rem;
                text-align: center;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .sidebar-nav .nav-link {
                text-align: center;
                padding: 15px 5px;
            }
            
            .sidebar-nav .nav-link i {
                margin-right: 0;
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 767px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .sidebar {
                left: -250px;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .mobile-toggle {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="fas fa-barcode me-2"></i>
                <span>Barcode System</span>
            </a>
        </div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo $current_view == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="scantoday.php" class="nav-link <?php echo $current_view == 'scantoday.php' ? 'active' : ''; ?>">
                    <i class="fas fa-qrcode"></i>
                    <span>Scanned Today</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="production.php" class="nav-link <?php echo $current_view == 'production.php' ? 'active' : ''; ?>">
                    <i class="fas fa-industry"></i>
                    <span>Production</span>
                    <small class="text-warning ms-1">[May Display Slowly]</small>
                </a>
            </li>
            <li class="nav-item">
                <a href="search_client.php" class="nav-link <?php echo $current_view == 'search_client.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i>
                    <span>Search by Solped Client</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="barcode_manager.php" class="nav-link <?php echo $current_view == 'barcode_manager.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Barcodes Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="history.php" class="nav-link <?php echo $current_view == 'history.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>History (logs)</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="scanner_system_download.php" class="nav-link <?php echo $current_view == 'scanner_system_download.php' ? 'active' : ''; ?>">
                    <i class="fas fa-download"></i>
                    <span>Scanner System Download</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid px-0">
                <button class="btn btn-light mobile-toggle d-lg-none me-3" type="button" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <h4 class="mb-0">Barcode Manager</h4>
                
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            Administrator
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Content Container -->
        <div class="container-fluid px-0">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Search Form Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-search me-2"></i> Search Barcodes
                </div>
                <div class="card-body">
                    <form method="GET" action="barcode_manager.php" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-hashtag"></i>
                                    </span>
                                    <input type="number" class="form-control" id="of_number_search" name="of_number_search" 
                                        placeholder="OF Number" value="<?php echo htmlspecialchars($of_number_search); ?>">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-ruler"></i>
                                    </span>
                                    <input type="text" class="form-control" id="size_search" name="size_search" 
                                        placeholder="Size" value="<?php echo htmlspecialchars($size_search); ?>">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-tags"></i>
                                    </span>
                                    <select class="form-select" id="category_search" name="category_search">
                                        <option value="">Category</option>
                                        <option value="R" <?php echo $category_search === 'R' ? 'selected' : ''; ?>>R</option>
                                        <option value="C" <?php echo $category_search === 'C' ? 'selected' : ''; ?>>C</option>
                                        <option value="L" <?php echo $category_search === 'L' ? 'selected' : ''; ?>>L</option>
                                        <option value="LL" <?php echo $category_search === 'LL' ? 'selected' : ''; ?>>LL</option>
                                        <option value="CC" <?php echo $category_search === 'CC' ? 'selected' : ''; ?>>CC</option>
                                        <option value="N" <?php echo $category_search === 'N' ? 'selected' : ''; ?>>N</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-puzzle-piece"></i>
                                    </span>
                                    <select class="form-select" id="piece_name_search" name="piece_name_search">
                                        <option value="">Piece Name</option>
                                        <option value="P" <?php echo $piece_name_search === 'P' ? 'selected' : ''; ?>>P</option>
                                        <option value="V" <?php echo $piece_name_search === 'V' ? 'selected' : ''; ?>>V</option>
                                        <option value="G" <?php echo $piece_name_search === 'G' ? 'selected' : ''; ?>>G</option>
                                        <option value="M" <?php echo $piece_name_search === 'M' ? 'selected' : ''; ?>>M</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-1">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-sort"></i>
                                    </span>
                                    <input type="text" class="form-control" id="order_str_search" name="order_str_search" 
                                        placeholder="Order" value="<?php echo htmlspecialchars($order_str_search); ?>">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <input type="date" class="form-control" id="date_to" name="date_to" 
                                        value="<?php echo htmlspecialchars($date_to); ?>">
                                </div>
                            </div>

                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>

                            <div class="col-md-auto">
                                <a href="barcode_manager.php" class="btn btn-secondary">
                                    <i class="fas fa-broom"></i> Clear
                                </a>
                            </div>
                        </div>

                        <?php if ($total_barcodes > 0): ?>
                        <div class="mt-2">
                            <p class="mb-0 small text-muted">Showing <?php echo min(($page - 1) * $items_per_page + 1, $total_barcodes); ?> to <?php echo min($page * $items_per_page, $total_barcodes); ?> of <?php echo $total_barcodes; ?> records</p>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Results Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-list me-2"></i> Barcode List
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addBarcodeModal">
                            <i class="fas fa-plus"></i> Add New Barcode
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" id="selectAllBtn" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-check-square"></i> Select All
                                </button>
                                <button type="button" id="deselectAllBtn" class="btn btn-outline-secondary btn-sm ms-2">
                                    <i class="fas fa-square"></i> Deselect All
                                </button>
                            </div>
                            <div>
                                <button type="button" id="showBulkEditBtn" class="btn btn-primary btn-sm me-2">
                                    <i class="fas fa-edit"></i> Edit Selected
                                </button>
                                <button type="button" id="showDeleteBtn" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i> Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Edit Form -->
                    <!-- Bulk Edit Form -->
                    <div id="bulkEditForm" class="card mb-3" style="display: none;">
                        <div class="card-body">
                            <h5 class="card-title">Edit Selected Barcodes</h5>
                            <form action="barcode_manager.php" method="POST">
                                <input type="hidden" name="action" value="bulk_edit">
                                <div id="selected_barcodes_container"></div>
                                
                                <div class="row g-3 align-items-center">
                                    <!-- Status Field -->
                                    <div class="col-md-3">
                                        <label class="form-label">Status</label>
                                        <select name="bulk_status" class="form-select">
                                            <option value="">Select Status</option>
                                            <option value="Completed">Completed</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Pending">Pending</option>
                                        </select>
                                    </div>

                                    <!-- Stage Field -->
                                    <div class="col-md-3">
                                        <label class="form-label">Stage</label>
                                        <select name="bulk_stage" class="form-select">
                                            <option value="">Select Stage</option>
                                            <option value="Coupe">Coupe</option>
                                            <option value="V1">V1</option>
                                            <option value="V2">V2</option>
                                            <option value="V3">V3</option>
                                            <option value="Pantalon">Pantalon</option>
                                            <option value="Repassage">Repassage</option>
                                            <option value="P_ fini">P_ fini</option>
                                        </select>
                                    </div>

                                    <!-- Chef Field -->
                                    <div class="col-md-3">
                                        <label class="form-label">Chef</label>
                                        <select name="bulk_chef" class="form-select">
                                            <option value="">Select Chef</option>
                                            <option value="Abdelkarim">Abdelkarim</option>
                                            <option value="Mohamed">Mohamed</option>
                                            <option value="Hana Hajouji">Hana Hajouji</option>
                                            <option value="Saaid Kahlaoui">Saaid Kahlaoui</option>
                                        </select>
                                    </div>

                                    <!-- Timestamp Field -->
                                    <div class="col-md-3">
                                        <label class="form-label">Timestamp</label>
                                        <input type="date" name="bulk_timestamp" class="form-control" 
                                            value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Changes
                                        </button>
                                        <button type="button" class="btn btn-secondary" 
                                                onclick="document.getElementById('bulkEditForm').style.display='none'">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    