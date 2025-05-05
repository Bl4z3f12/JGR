<?php

$current_view = 'barcode_settings.php';
require_once 'auth_functions.php';

// Redirect to login page if not logged in
requireLogin('login.php');

// Enhanced IP authorization check
$allowed_ips = ['127.0.0.1', '192.168.1.130', '::1', '192.168.1.14' ,'NEW_IP_HERE'];
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$client_ip = trim(explode(',', $client_ip)[0]);
$is_localhost = in_array($client_ip, ['127.0.0.1', '::1']) || 
               stripos($_SERVER['HTTP_HOST'], 'localhost') !== false;

               // Check authorization
if (!$is_localhost && !in_array($client_ip, $allowed_ips)) {
    // Show authorization message and stop execution
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


// Include PHP logic file
require_once 'settings.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <div class="container-fluid">
                <h4 class="mb-4" style="font-size: 18px;">Barcode Settings</h4>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search Form -->
                <div class="card-body">
    <form method="GET" action="barcode_settings.php" class="mb-4">
        <div class="row g-4">
            <!-- OF Number Input with # symbol -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label for="of_number_search" class="form-label">OF Number</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fa-solid fa-hashtag"></i>
                    </span>
                    <input type="text" class="form-control" id="of_number_search" name="of_number_search" 
                           placeholder="Enter OF number" value="<?php echo htmlspecialchars($of_number_search); ?>">
                </div>
            </div>

            <!-- Full Barcode Search -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label for="full_barcode_search" class="form-label">Full Barcode</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fa-solid fa-barcode"></i>
                    </span>
                    <input type="text" class="form-control" id="full_barcode_search" name="full_barcode_search" 
                           placeholder="Enter barcode" value="<?php echo htmlspecialchars($full_barcode_search); ?>">
                </div>
            </div>

            <!-- Size Input with ruler symbol -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label for="size_search" class="form-label">Size</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fa-solid fa-ruler"></i>
                    </span>
                    <input type="number" class="form-control" id="size_search" name="size_search" 
                           placeholder="Enter size" value="<?php echo htmlspecialchars($size_search); ?>">
                </div>
            </div>

            <!-- Category with tag symbol -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label for="category_search" class="form-label">Category</label>
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

            <!-- Piece Name with puzzle piece symbol -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label for="piece_name_search" class="form-label">Piece Name</label>
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

            <!-- Order Input -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label for="order_str_search" class="form-label">Order</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-sort"></i>
                    </span>
                    <input type="text" class="form-control" id="order_str_search" name="order_str_search" 
                           placeholder="Enter order" value="<?php echo htmlspecialchars($order_str_search); ?>">
                </div>
            </div>

            <!-- Date Filter -->
            <div class="col-lg-3 col-md-6 col-sm-6">
                <label for="date_to" class="form-label">Date</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
            </div>

            <!-- Buttons -->
            <div class="col-lg-3 col-md-6 col-sm-12 d-flex align-items-end">
                <div class="d-flex gap-2 w-100 justify-content-start">
                    <button type="submit" class="btn btn-primary" style="min-width: 150px;">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="barcode_settings.php" class="btn btn-secondary" style="min-width: 150px;">
                        <i class="fas fa-broom"></i> Clear
                    </a>
                </div>
            </div>

            <!-- Pagination Info -->
            <?php if ($total_barcodes > 0): ?>
            <div class="col-12 mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="mb-0">Showing <?php echo min(($page - 1) * $items_per_page + 1, $total_barcodes); ?> to <?php echo min($page * $items_per_page, $total_barcodes); ?> of <?php echo $total_barcodes; ?> records</p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </form>
</div>


                         <!-- Barcodes Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="button" id="selectAllBtn" class="btn btn-outline-primary">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                    <button type="button" id="deselectAllBtn" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-square"></i> Deselect All
                                    </button>
                                </div>
                                <div>
                                    <button type="button" id="showBulkEditBtn" class="btn btn-primary me-2">
                                        <i class="fas fa-edit"></i> Edit Selected
                                    </button>
                                    <button type="button" id="showDeleteBtn" class="btn btn-danger">
                                        <i class="fas fa-trash-alt"></i> Delete Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bulk Edit Form (Initially Hidden) -->
                        <div id="bulkEditForm" class="card mb-3" style="display: none;">
                            <div class="card-body">
                                <h5 class="card-title">Edit Selected Barcodes</h5>
                                <form action="barcode_settings.php" method="POST">
                                    <input type="hidden" name="action" value="bulk_edit">
                                    <div id="selected_barcodes_container"></div>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="update_status" name="update_status">
                                                <label class="form-check-label" for="update_status">Update Status</label>
                                            </div>
                                            <select class="form-select" id="bulk_status" name="bulk_status" disabled>
                                                <option value="">Select Status</option>
                                                <?php 
                                                // Updated status options array with new values
                                                $status_options = ['Completed', 'In Progress', 'Pending',]; 
                                                foreach ($status_options as $option): 
                                                ?>
                                                    <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="update_stage" name="update_stage">
                                                <label class="form-check-label" for="update_stage">Update Stage</label>
                                            </div>
                                            <select class="form-select" id="bulk_stage" name="bulk_stage" disabled>
                                                <option value="">Select Stage</option>
                                                <?php
                                                  $stage_options = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_ fini','Exported'];
                                                 foreach ($stage_options as $option): ?>
                                                    <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="update_chef" name="update_chef">
                                                <label class="form-check-label" for="update_chef">Update Chef</label>
                                            </div>
                                            <select class="form-select" id="bulk_chef" name="bulk_chef" disabled>
                                                <option value="">Select Chef</option>
                                                <?php 
                                                // Updated chef options array with new values
                                                $chef_options = ['Abdelkarim', 'Abderazaq', 'Aziz Berdigue', 'Bouchra',
                                                                    'Driss Khairani', 'Fadwa', 'Farah', 'Fouad',
                                                                    'Fouad Laakawi', 'Habib Douiba', 'Hada', 'Hana Hajouji',
                                                                    'Hanan Khomassi', 'Hassan Nassiri', 'Khadija', 'Miloud',
                                                                    'Mohamed', 'Naaima Elakiwi', 'Rahma Belmokhtar', 'Rakiya',
                                                                    'Saaid Kahlaoui', 'Saadi Zhiliga', 'Souad', 'Yassin',
                                                                    'Youssef', 'Ztouti', 'Zouhair'
                                                                ]; 
                                                foreach ($chef_options as $option): 
                                                ?>
                                                    <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="update_timestamp" name="update_timestamp">
                                                <label class="form-check-label" for="update_timestamp">Update Timestamp</label>
                                            </div>
                                            <input type="datetime-local" class="form-control" id="bulk_timestamp" name="bulk_timestamp" 
                                                   value="<?php echo formatDatetimeForInput(date('Y-m-d H:i:s')); ?>" disabled>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="button" id="cancelBulkEditBtn" class="btn btn-secondary me-2">Cancel</button>
                                        <button type="submit" id="saveBulkEditBtn" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Delete Confirmation Form (Initially Hidden) -->
                        <div id="deleteForm" class="card mb-3" style="display: none;">
                            <div class="card-body">
                                <h5 class="card-title text-danger">Delete Selected Barcodes</h5>
                                <p>Are you sure you want to delete the selected barcodes? This action cannot be undone.</p>
                                <form action="barcode_settings.php" method="POST">
                                    <input type="hidden" name="action" value="delete">
                                    <div id="delete_barcodes_container"></div>
                                    
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="button" id="cancelDeleteBtn" class="btn btn-secondary me-2">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Main Table -->
                        <div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-primary">
            <tr>
                <th width="40px"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                <th>OF Number</th>
                <th>Size</th>
                <th>Category</th>
                <th>Piece</th>
                <th>Order</th>
                <th>Stage</th>
                <th>Chef</th>
                <th>Status</th>
                <th>Last Update</th>
                <th width="80px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!isset($_GET['of_number_search']) && !isset($_GET['size_search']) && !isset($_GET['category_search']) && !isset($_GET['piece_name_search']) && !isset($_GET['order_str_search'])): ?>
                <tr>
                    <td colspan="11" class="text-center">Please use the search form above to display results</td>
                </tr>
            <?php elseif (empty($barcodes)): ?>
                <tr>
                    <td colspan="11" class="text-center">No barcodes found for your search criteria</td>
                </tr>
            <?php else: ?>
                <?php foreach ($barcodes as $barcode): ?>
                    <tr>
                        <td>
                            <input type="checkbox" value="<?php echo $barcode['id']; ?>" class="form-check-input barcode-checkbox" 
                                   data-of-number="<?php echo htmlspecialchars($barcode['of_number']); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($barcode['of_number']); ?></td>
                        <td><?php echo htmlspecialchars($barcode['size']); ?></td>
                        <td><?php echo htmlspecialchars($barcode['category']); ?></td>
                        <td><?php echo htmlspecialchars($barcode['piece_name']); ?></td>
                        <td><?php echo htmlspecialchars($barcode['order_str']); ?></td>
                        <td><?php echo htmlspecialchars($barcode['stage']); ?></td>
                        <td><?php echo htmlspecialchars($barcode['chef']); ?></td>
                        <td>
                            <?php if (!empty($barcode['status'])): ?>
                                <span class="badge bg-<?php echo strtolower($barcode['status']) === 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo htmlspecialchars($barcode['status']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($barcode['last_update']); ?></td>
                        <td>
                            <a href="<?php echo buildSearchUrl(['edit' => $barcode['id']]); ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
    

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildSearchUrl(['page' => $page - 1]); ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="' . buildSearchUrl(['page' => 1]) . '">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $active = $i == $page ? ' active' : '';
                                        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . buildSearchUrl(['page' => $i]) . '">' . $i . '</a></li>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="' . buildSearchUrl(['page' => $total_pages]) . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo buildSearchUrl(['page' => $page + 1]); ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Barcode Modal -->
    <?php if ($edit_barcode): ?>
    <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1" aria-labelledby="editModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Barcode</h5>
                    <a href="<?php echo buildSearchUrl(); ?>" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <form action="<?php echo buildSearchUrl(); ?>" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="barcode_id" value="<?php echo $edit_barcode['id']; ?>">
                        
                        <!-- Barcode Identification Fields (Read-only) -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_of_number" class="form-label">OF Number</label>
                                <input type="text" class="form-control" id="edit_of_number" value="<?php echo htmlspecialchars($edit_barcode['of_number']); ?>" readonly>
                                <input type="hidden" name="of_number" value="<?php echo htmlspecialchars($edit_barcode['of_number']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_size" class="form-label">Size</label>
                                <input type="number" class="form-control" id="edit_size" value="<?php echo htmlspecialchars($edit_barcode['size']); ?>" readonly>
                                <input type="hidden" name="size" value="<?php echo htmlspecialchars($edit_barcode['size']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="edit_category" value="<?php echo htmlspecialchars($edit_barcode['category']); ?>" readonly>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($edit_barcode['category']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_piece_name" class="form-label">Piece Name</label>
                                <input type="text" class="form-control" id="edit_piece_name" value="<?php echo htmlspecialchars($edit_barcode['piece_name']); ?>" readonly>
                                <input type="hidden" name="piece_name" value="<?php echo htmlspecialchars($edit_barcode['piece_name']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_order_str" class="form-label">Order</label>
                                <input type="text" class="form-control" id="edit_order_str" value="<?php echo htmlspecialchars($edit_barcode['order_str']); ?>" readonly>
                                <input type="hidden" name="order_str" value="<?php echo htmlspecialchars($edit_barcode['order_str']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Full Barcode Preview</label>
                                <div class="form-control" style="background-color: #f8f9fa;">
                                    <span id="barcode_preview">
                                    <?php 
                                    // Updated format to match requested format: "19200-40R-P-10"
                                    $preview = $edit_barcode['of_number'] . '-' . $edit_barcode['size'];
                                    if (!empty($edit_barcode['category'])) {
                                        $preview .= $edit_barcode['category'];
                                    }
                                    $preview .= '-' . $edit_barcode['piece_name'] . '-' . $edit_barcode['order_str'];
                                    echo htmlspecialchars($preview);
                                    ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Separation Line -->
                        <hr class="my-4 border-2">
                        <h6 class="mb-3 text-secondary">Editable Fields</h6>
                        
                        <!-- Editable Fields -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="">None</option>
                                    <?php 
                                    // Updated status options array with new values
                                    $status_options = ['Completed', 'In Progress', 'Pending',]; 
                                    foreach ($status_options as $option): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $edit_barcode['status'] === $option ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_stage" class="form-label">Stage</label>
                                <select class="form-select" id="edit_stage" name="stage">
                                    <option value="">None</option>
                                    <?php 
                                    $stage_options = 
                                    [
                                        "Coupe",
                                        "V1",
                                        "V2",
                                        "V3",
                                        "Pantalon",
                                        "Repassage",
                                        "P_ fini"
                                    ];
                                    foreach ($stage_options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $edit_barcode['stage'] === $option ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit_chef" class="form-label">Chef</label>
                                <select class="form-select" id="edit_chef" name="chef">
                                    <option value="">None</option>
                                    <?php 
                                    // Updated chef options array with new values
                                    $chef_options = 
                                    [
                                        "Abdelkarim",
                                        "Abderazaq",
                                        "Aziz Berdigue",
                                        "Bouchra",
                                        "Driss Khairani",
                                        "Fadwa",
                                        "Farah",
                                        "Fouad",
                                        "Fouad Laakawi",
                                        "Habib Douiba",
                                        "Hada",
                                        "Hana Hajouji",
                                        "Hanan Khomassi",
                                        "Hassan Nassiri",
                                        "Miloud",
                                        "Mohamed",
                                        "Naaima Elakiwi",
                                        "Rahma Belmokhtar",
                                        "Rakiya",
                                        "Saaid Kahlaoui",
                                        "Saadi Zhiliga",
                                        "Souad",
                                        "Yassin",
                                        "Youssef",
                                        "Ztouti"
                                    ];
                                    foreach ($chef_options as $option): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $edit_barcode['chef'] === $option ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="last_update" class="form-label">Last Update</label>
                                <input type="datetime-local" class="form-control" id="last_update" name="last_update" 
                                    value="<?php echo formatDatetimeForInput($edit_barcode['last_update']); ?>">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo buildSearchUrl(); ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes <i class="fa-solid fa-floppy-disk"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/index.js"></script>
    <script src="assets/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>