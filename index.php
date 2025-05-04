<?php
$current_view = 'dashboard'; // Ensure this line exists
require_once 'auth_functions.php';

// Redirect to login page if not logged in
requireLogin('login.php');

// Import the PHP logic file
require_once 'barcode_system.php';

// Add this line right after including barcode_system.php

$items_per_page = 5000;



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <!-- Add Bootstrap CSS if not already included in head.php -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    #costume-options {
        display: flex;
        flex: 0 0 auto;
        width: 100%;
        flex-direction: column;
        align-items: center;
    }
    .status-in-progress {
        color: black;
        background-color: #f1c40f;
        padding: 0px 1px;
        font-size: 14px;
        border-radius: 4px;
    }
    .status-badge.status-completed {
        background-color: #4CAF50; /* Green */
        color: white;
        padding: 0px 1px;
        font-size: 14px;
        border-radius: 4px;
    }
    .status-badge.status-pending {
        background-color: #f44336; /* Red */
        color: white;
        padding: 0px 1px;
        font-size: 14px;
        border-radius: 4px;
    }
    
    /* PDF Modal Styles */
    #pdf-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    #pdf-modal .modal-content {
        background-color: #fefefe;
        margin: 2% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 90%;
        max-width: 1200px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 8px;
    }
    
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
    
    .filter-buttons {
        display: flex;
        gap: 10px;
    }
    
    .filter-buttons .btn {
        flex: 1;
    }
    
    #pdf-modal-loader {
        text-align: center;
        padding: 40px;
    }
</style>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
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
                    <a href="#" class="btn-create" id="open-path-btn">
                        <span><i class="fa-solid fa-folder-open"></i></span> Open Path
                    </a>
                </div>
            </div>            


            <form id="filter-form" class="filter-form card p-3 shadow-sm" action="" method="GET">
                <input type="hidden" name="view" value="<?php echo $current_view; ?>">
                <h5 class="mb-3"><i class="fa-solid fa-arrow-up-wide-short"></i> Filter Options</h5>
                <div class="row mb-3 align-items-end">
                    <!-- OF Number -->
                    <div class="col-md-2">
                        <label for="filter-of" class="form-label mb-2">OF Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                            <input type="number" class="form-control" id="filter-of" name="filter_of"
                                value="<?php echo htmlspecialchars($filter_of_number); ?>" placeholder="Enter OF number">
                        </div>
                    </div>
                    <!-- Size -->
                    <div class="col-md-2">
                        <label for="filter-size" class="form-label mb-2">Size</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-ruler"></i></span>
                            <input type="number" class="form-control" id="filter-size" name="filter_size"
                                value="<?php echo htmlspecialchars($filter_size); ?>" placeholder="Enter size">
                        </div>
                    </div>
                    <!-- Category -->
                    <div class="col-md-2">
                        <label for="filter-category" class="form-label mb-2">Category</label>

                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-tags"></i></span>
                            <select class="form-select" id="filter-category" name="filter_category">
                                <option value="">Category</option>
                                <option value="R" <?php echo ($filter_category ?? '') === 'R' ? 'selected' : ''; ?>>R</option>
                                <option value="C" <?php echo ($filter_category ?? '') === 'C' ? 'selected' : ''; ?>>C</option>
                                <option value="L" <?php echo ($filter_category ?? '') === 'L' ? 'selected' : ''; ?>>L</option>
                                <option value="LL" <?php echo ($filter_category ?? '') === 'LL' ? 'selected' : ''; ?>>LL</option>
                                <option value="CC" <?php echo ($filter_category ?? '') === 'CC' ? 'selected' : ''; ?>>CC</option>
                                <option value="N" <?php echo ($filter_category ?? '') === 'N' ? 'selected' : ''; ?>>N</option>
                            </select>
                        </div>
                    </div>
                    <!-- Piece Name -->
                    <div class="col-md-2">
                        <label for="filter-piece-name" class="form-label mb-2">Piece Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-puzzle-piece"></i></span>
                            <select class="form-select" id="filter-piece-name" name="filter_piece_name">
                                <option value="">Piece Name</option>
                                <option value="P" <?php echo ($filter_piece_name ?? '') === 'P' ? 'selected' : ''; ?>>P</option>
                                <option value="V" <?php echo ($filter_piece_name ?? '') === 'V' ? 'selected' : ''; ?>>V</option>
                                <option value="G" <?php echo ($filter_piece_name ?? '') === 'G' ? 'selected' : ''; ?>>G</option>
                                <option value="M" <?php echo ($filter_piece_name ?? '') === 'M' ? 'selected' : ''; ?>>M</option>
                            </select>
                        </div>
                    </div>
                    <!-- Buttons -->
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-filter me-1"></i> Apply Filters
                            </button>
                            <button type="button" id="clear-filters" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-broom"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </form>


            <div class="container-fluid py-3">
        <!-- Desktop version (visible only on md screens and up) -->
        <div class="d-none d-md-block">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>OF_Number</th>
                        <th>Size</th>
                        <th>Category</th>
                        <th>Piece Name</th>
                        <th>Order</th>
                        <th>Stage</th>
                        <th>Chef</th>
                        <th>Status</th>
                        <th>Full Barcode Name</th>
                        <th>Last Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($barcodes)): ?>
                    <tr>
                        <td colspan="10" class="text-center">No barcodes found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($barcodes as $barcode): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($barcode['of_number']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['size']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['category']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['piece_name']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['order_str']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['stage']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['chef']); ?></td>
                            <td>
                                <?php 
                                $statusClass = '';
                                $icon = '';
                                switch(strtolower($barcode['status'])) {
                                    case 'completed':
                                        $statusClass = 'bg-success';
                                        $icon = 'fa-check';
                                        break;
                                    case 'in progress':
                                        $statusClass = 'bg-warning';
                                        $icon = 'fa-clock';
                                        break;
                                    case 'pending':
                                        $statusClass = 'bg-secondary';
                                        $icon = 'fa-hourglass';
                                        break;
                                    case 'error':
                                        $statusClass = 'bg-danger';
                                        $icon = 'fa-exclamation-circle';
                                        break;
                                    default:
                                        $statusClass = 'bg-info';
                                        $icon = 'fa-info-circle';
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $icon; ?> me-1"></i>
                                    <?php echo htmlspecialchars($barcode['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($barcode['full_barcode_name']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['last_update']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile version (visible only on screens smaller than md) -->
        <div class="d-md-none">
            <?php if (empty($barcodes)): ?>
                <div class="alert alert-info text-center">No barcodes found</div>
            <?php else: ?>
                <?php foreach ($barcodes as $barcode): ?>
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong><?php echo htmlspecialchars($barcode['piece_name']); ?></strong>
                            <?php 
                            $statusClass = '';
                            $icon = '';
                            switch(strtolower($barcode['status'])) {
                                case 'completed':
                                    $statusClass = 'bg-success';
                                    $icon = 'fa-check';
                                    break;
                                case 'in progress':
                                    $statusClass = 'bg-warning';
                                    $icon = 'fa-clock';
                                    break;
                                case 'pending':
                                    $statusClass = 'bg-secondary';
                                    $icon = 'fa-hourglass';
                                    break;
                                case 'error':
                                    $statusClass = 'bg-danger';
                                    $icon = 'fa-exclamation-circle';
                                    break;
                                default:
                                    $statusClass = 'bg-info';
                                    $icon = 'fa-info-circle';
                            }
                            ?>
                            <span class="badge <?php echo $statusClass; ?>">
                                <i class="fas <?php echo $icon; ?> me-1"></i>
                                <?php echo htmlspecialchars($barcode['status']); ?>
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold"><i class="fas fa-hashtag me-2"></i>OF Number:</span>
                                    <span><?php echo htmlspecialchars($barcode['of_number']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold"><i class="fas fa-expand me-2"></i>Size:</span>
                                    <span><?php echo htmlspecialchars($barcode['size']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold"><i class="fas fa-tag me-2"></i>Category:</span>
                                    <span><?php echo htmlspecialchars($barcode['category']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold"><i class="fas fa-sort-numeric-up me-2"></i>Order:</span>
                                    <span><?php echo htmlspecialchars($barcode['order_str']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold"><i class="fas fa-tasks me-2"></i>Stage:</span>
                                    <span><?php echo htmlspecialchars($barcode['stage']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold"><i class="fas fa-user-chef me-2"></i>Chef:</span>
                                    <span><?php echo htmlspecialchars($barcode['chef']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold"><i class="fas fa-barcode me-2"></i>Full Barcode:</span>
                                    <span class="text-truncate ms-2" style="max-width: 180px;"><?php echo htmlspecialchars($barcode['full_barcode_name']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="fw-bold"><i class="fas fa-clock me-2"></i>Last Update:</span>
                                    <span><?php echo htmlspecialchars($barcode['last_update']); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?view=<?php echo $current_view; ?>&page=<?php echo ($page - 1); ?><?php echo !empty($filter_of_number) ? '&filter_of=' . urlencode($filter_of_number) : ''; ?><?php echo !empty($filter_size) ? '&filter_size=' . urlencode($filter_size) : ''; ?>" class="pagination-btn">
                    <i class="fa-solid fa-angle-left"></i>
                </a>
                <?php endif; ?>
                
                <?php
                // Display pagination buttons
                $start_page = max(1, min($page - 1, $total_pages - 2));
                $end_page = min($total_pages, max(3, $page + 1));
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?view=<?php echo $current_view; ?>&page=<?php echo $i; ?><?php echo !empty($filter_of_number) ? '&filter_of=' . urlencode($filter_of_number) : ''; ?><?php echo !empty($filter_size) ? '&filter_size=' . urlencode($filter_size) : ''; ?>" 
                   class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <a href="?view=<?php echo $current_view; ?>&page=<?php echo ($page + 1); ?><?php echo !empty($filter_of_number) ? '&filter_of=' . urlencode($filter_of_number) : ''; ?><?php echo !empty($filter_size) ? '&filter_size=' . urlencode($filter_size) : ''; ?>" class="pagination-btn">
                    <i class="fa-solid fa-angle-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Barcode Modal -->
    <div class="modal <?php echo $show_modal ? 'show' : ''; ?>" id="barcode-modal">
        <div class="modal-content">
            <!-- Add modal header with title -->
            <div class="modal-header">
                <h5 class="modal-title">Create New Barcodes</h5>
                <button type="button" class="btn-close" onclick="document.getElementById('barcode-modal').classList.remove('show')"></button>
            </div>
            
            <form action="index.php" method="POST" class="container-fluid">
                <input type="hidden" name="action" value="create_barcode">
                <input type="hidden" name="view" value="<?php echo $current_view; ?>">
                
                <div class="row mb-3">
                    <label for="barcode-prefix" class="col-sm-3 col-form-label">OF_ number</label>
                    <div class="col-sm-9">
                        <input class="form-control ofinput" type="number" id="barcode-prefix" name="barcode_prefix" placeholder="Enter OF number" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <label for="barcode-size" class="col-sm-3 col-form-label">Size</label>
                    <div class="col-sm-9">
                        <input type="number" id="barcode-size" name="barcode_size" min="1" step="1" class="form-control ofinput" placeholder="Enter size number" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="barcode-category" class="col-sm-3 col-form-label">OF_Category</label>
                    <div class="col-sm-9">
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
                </div>
                
                <div class="row mb-3">
                    <label for="barcode-piece-name" class="col-sm-3 col-form-label">Piece Name</label>
                    <div class="col-sm-9">
                        <select id="barcode-piece-name" name="barcode_piece_name" class="form-select" required>
                            <option value="">Select Piece Name</option>
                            <option value="P">P</option>
                            <option value="V">V</option>
                            <option value="G">G</option>
                            <option value="M">M</option>
                        </select>
                    </div>
                </div>
                                
                <div class="row mb-3">
                    <div class="col-12" id="costume-options">
                        <div class="form-check d-flex align-items-center">
                            <input class="form-check-input me-2" type="checkbox" id="lost-barcode" name="lost_barcode">
                            <label class="form-check-label me-3" for="lost-barcode">Lost Barcode</label>
                            <input type="number" id="lost-barcode-count" name="lost_barcode_count"
                                class="form-control form-control-sm me-3" 
                                style="width: 60px;"
                                value="1" min="1" max="100" 
                                disabled>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Piece Order</label>
                    <div class="col-sm-9">
                        <div class="row">
                            <div class="col-6">
                                <div class="input-group">
                                    <span class="input-group-text">From</span>
                                    <input type="number" id="range-from" name="range_from" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group">
                                    <span class="input-group-text">To</span>
                                    <input type="number" id="range-to" name="range_to" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12" id="costume-options">
                        <div class="form-check mb-2 d-flex align-items-center">
                            <input class="form-check-input me-2" type="checkbox" id="generate-costume-2pcs" name="generate_costume_2pcs">
                            <label class="form-check-label d-flex align-items-center" for="generate-costume-2pcs">
                                Generate for P and V (Costume 2pcs)
                            </label>
                        </div>

                        <div class="form-check mb-2 d-flex align-items-center">
                            <input class="form-check-input me-2" type="checkbox" id="generate-costume-3pcs" name="generate_costume_3pcs">
                            <label class="form-check-label d-flex align-items-center" for="generate-costume-3pcs">
                                Generate for P, V, and G (Costume 3pcs)
                            </label>
                        </div>
                            
                        <div class="form-check mb-2 ms-4 d-flex align-items-center">
                            <input class="form-check-input me-2" type="checkbox" id="generate_pdf_only" name="generate_pdf_only">
                            <label class="form-check-label d-flex align-items-center" for="generate_pdf_only">
                                Generate only PDF
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 d-flex justify-content-center gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-qrcode"></i> Generate Barcodes</button>
                        <button type="button" class="btn btn-outline-danger" onclick="document.getElementById('barcode-modal').classList.remove('show')">Cancel</button>
                    </div>
                </div>    

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-3 justify-content-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-white border" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">1 → 1000</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-warning" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">1001 → 2000</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-success" style="width: 20px; height: 20px;"></div>
                            <span class="ms-2">2001 → 3000</span>
                        </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 text-center">
                        <p class="small text-muted">
                        1 → CC &nbsp;/&nbsp; 2 → C &nbsp;/&nbsp; 3 → R &nbsp;/&nbsp; 4 → L &nbsp;/&nbsp; 5 → LL
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- PDF Modal -->
    <div id="pdf-modal">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title">Generated PDF Files</h5>
                <button type="button" class="btn-close" onclick="document.getElementById('pdf-modal').style.display='none'"></button>
            </div>
            <div id="pdf-modal-content">
                <div id="pdf-modal-loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading PDF files...</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <?php 
    // Output the random button JavaScript
    echo $random_button_script;
    ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lost barcode checkbox functionality
            const lostBarcodeCheckbox = document.getElementById('lost-barcode');
            const lostBarcodeCount = document.getElementById('lost-barcode-count');
            const checkbox2pcs = document.getElementById('generate-costume-2pcs');
            const checkbox3pcs = document.getElementById('generate-costume-3pcs');
            const rangeFrom = document.getElementById('range-from');
            const rangeTo = document.getElementById('range-to');
            
            // Handle lost barcode checkbox
            if (lostBarcodeCheckbox) {
                lostBarcodeCheckbox.addEventListener('change', function() {
                    lostBarcodeCount.disabled = !this.checked;
                    checkbox2pcs.disabled = this.checked;
                    checkbox3pcs.disabled = this.checked;
                    rangeFrom.disabled = this.checked;
                    rangeTo.disabled = this.checked;
                });
            }
            
            // Handle costume checkbox interactions
            if (checkbox2pcs && checkbox3pcs) {
                checkbox2pcs.addEventListener('change', function() {
                    checkbox3pcs.disabled = this.checked;
                });
                
                checkbox3pcs.addEventListener('change', function() {
                    checkbox2pcs.disabled = this.checked;
                });
            }
            
            // Handle clear filters button
            const clearFiltersBtn = document.getElementById('clear-filters');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function() {
                    const filterForm = document.getElementById('filter-form');
                    const inputs = filterForm.querySelectorAll('input[type="text"], input[type="number"]');
                    inputs.forEach(input => {
                        input.value = '';
                    });
                    filterForm.submit();
                });
            }
            
            // Get the form element
            const barcodeForm = document.querySelector('#barcode-modal form');
            
            // Modify form submission to prevent default form clearing
            if (barcodeForm) {
                barcodeForm.addEventListener('submit', function(e) {
                    // Store the current form values in session storage
                    const formInputs = this.querySelectorAll('input:not([type="hidden"]), select');
                    formInputs.forEach(input => {
                        sessionStorage.setItem(input.name, input.value);
                    });
                    
                    // The form will continue with normal submission
                });
                
                // Check if there are stored values to restore
                if (window.sessionStorage) {
                    const formInputs = barcodeForm.querySelectorAll('input:not([type="hidden"]), select');
                    formInputs.forEach(input => {
                        const savedValue = sessionStorage.getItem(input.name);
                        if (savedValue !== null) {
                            input.value = savedValue;
                        }
                    });
                }
            }
            
            // PDF Modal functionality
            const openPathBtn = document.getElementById('open-path-btn');
            const pdfModal = document.getElementById('pdf-modal');
            
            if (openPathBtn && pdfModal) {
                openPathBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Show the modal
                    pdfModal.style.display = 'block';
                    
                    // Load PDF content via AJAX
                    fetch('pdf.php')
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('pdf-modal-content').innerHTML = data;
                            
                            // Initialize any events in the loaded content
                            initPdfModalEvents();
                        })
                        .catch(error => {
                            document.getElementById('pdf-modal-content').innerHTML = 
                                '<div class="alert alert-danger">Error loading PDF content: ' + error.message + '</div>';
                        });
                });
            }
            
            // Close the modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target === pdfModal) {
                    pdfModal.style.display = 'none';
                }
            });
        });
        
        // Function to initialize events within the PDF modal
        function initPdfModalEvents() {
            // Handle search form submission in the PDF modal
            const searchForm = document.getElementById('pdf-search-form');
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const searchParams = new URLSearchParams(formData);
                    
                    // Show loader
                    document.getElementById('pdf-modal-content').innerHTML = `
                        <div id="pdf-modal-loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Searching...</p>
                        </div>
                    `;
                    
                    // Fetch filtered results
                    fetch('pdf.php?' + searchParams.toString())
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('pdf-modal-content').innerHTML = data;
                            initPdfModalEvents(); // Re-initialize events
                        })
                        .catch(error => {
                            document.getElementById('pdf-modal-content').innerHTML = 
                                '<div class="alert alert-danger">Error loading PDF content: ' + error.message + '</div>';
                        });
                });
            }
            
            // Handle clear filter button
            const clearFilterBtn = document.getElementById('clear-pdf-filters');
            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function() {
                    // Reload the PDF content without filters
                    document.getElementById('pdf-modal-content').innerHTML = `
                        <div id="pdf-modal-loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading PDF files...</p>
                        </div>
                    `;
                    
                    fetch('pdf.php')
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('pdf-modal-content').innerHTML = data;
                            initPdfModalEvents();
                        })
                        .catch(error => {
                            document.getElementById('pdf-modal-content').innerHTML = 
                                '<div class="alert alert-danger">Error loading PDF content: ' + error.message + '</div>';
                        });
                });
            }
        }
    </script>
    
</body>
</html>