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
                    <a href="pdf.php" class="btn-create" id="open-path-btn">
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
                        <input type="text" class="form-control" id="filter-of" name="filter_of"
                            value="<?php echo htmlspecialchars($filter_of_number); ?>" placeholder="Enter OF number">
                    </div>
                    <!-- Size -->
                    <div class="col-md-2">
                        <label for="filter-size" class="form-label mb-2">Size</label>
                        <input type="number" class="form-control" id="filter-size" name="filter_size"
                            value="<?php echo htmlspecialchars($filter_size); ?>" placeholder="Enter size">
                    </div>
                    <!-- Category -->
                    <div class="col-md-2">
                        <label for="filter-category" class="form-label mb-2">Category</label>
                        <select class="form-control" id="filter-category" name="filter_category">
                            <option value="">Select Category</option>
                            <option value="R" <?php echo ($filter_category ?? '') === 'R' ? 'selected' : ''; ?>>R</option>
                            <option value="C" <?php echo ($filter_category ?? '') === 'C' ? 'selected' : ''; ?>>C</option>
                            <option value="L" <?php echo ($filter_category ?? '') === 'L' ? 'selected' : ''; ?>>L</option>
                            <option value="LL" <?php echo ($filter_category ?? '') === 'LL' ? 'selected' : ''; ?>>LL</option>
                            <option value="CC" <?php echo ($filter_category ?? '') === 'CC' ? 'selected' : ''; ?>>CC</option>
                            <option value="N" <?php echo ($filter_category ?? '') === 'N' ? 'selected' : ''; ?>>N</option>
                        </select>
                    </div>
                    <!-- Piece Name -->
                    <div class="col-md-2">
                        <label for="filter-piece-name" class="form-label mb-2">Piece Name</label>
                        <select class="form-control" id="filter-piece-name" name="filter_piece_name">
                            <option value="">Select Piece Name</option>
                            <option value="P" <?php echo ($filter_piece_name ?? '') === 'P' ? 'selected' : ''; ?>>P</option>
                            <option value="V" <?php echo ($filter_piece_name ?? '') === 'V' ? 'selected' : ''; ?>>V</option>
                            <option value="G" <?php echo ($filter_piece_name ?? '') === 'G' ? 'selected' : ''; ?>>G</option>
                            <option value="M" <?php echo ($filter_piece_name ?? '') === 'M' ? 'selected' : ''; ?>>M</option>
                        </select>
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


            <table class="dashboard-table">
                <thead>
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
                        <td colspan="12" style="text-align: center;">No barcodes found</td>
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
                                <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($barcode['status'])); ?>">    
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
                        <input class="form-control ofinput" type="text" id="barcode-prefix" name="barcode_prefix" required>
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
                        <div class="d-flex flex-wrap gap-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-white border" style="width: 20px; height: 20px;"></div>
                                <span class="ms-2">1 ---> 1000</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-warning" style="width: 20px; height: 20px;"></div>
                                <span class="ms-2">1001 ---> 2000</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-success" style="width: 20px; height: 20px;"></div>
                                <span class="ms-2">2001 ---> 3000</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <p class="small text-muted">1 --> CC / 2 --> C / 3 --> R / 4 --> L / 5 --> LL</p>
                    </div>
                </div>
            </form>
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
        });
    </script>
    

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get references to the modal and input field
        const barcodeModal = document.getElementById('barcode-modal');
        const barcodePrefix = document.getElementById('barcode-prefix');
        
        // Function to set focus when modal is shown
        const setFocusOnModal = function() {
            if (barcodeModal.classList.contains('show')) {
                // Set timeout to ensure DOM is fully rendered
                setTimeout(function() {
                    barcodePrefix.focus();
                }, 100);
            }
        };
        
        // Call when the page loads in case modal is shown by default
        setFocusOnModal();
        
        // Set up a mutation observer to detect when modal gets 'show' class
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (barcodeModal.classList.contains('show')) {
                        // Modal was just shown
                        setTimeout(function() {
                            barcodePrefix.focus();
                        }, 100);
                    }
                }
            });
        });
        
        // Start observing the modal for class changes
        observer.observe(barcodeModal, { attributes: true });
    });

    
    </script>

</body>
</html>