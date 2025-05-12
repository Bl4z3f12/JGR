<?php
$current_view = 'dashboard';
require_once 'auth_functions.php';

requireLogin('login.php');

$allowed_ips = ['127.0.0.1', '192.168.1.130', '::1', '192.168.0.120' ,'NEW_IP_HERE'];
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$client_ip = trim(explode(',', $client_ip)[0]);
$is_localhost = in_array($client_ip, ['127.0.0.1', '::1']) || 
               stripos($_SERVER['HTTP_HOST'], 'localhost') !== false;

if (!$is_localhost && !in_array($client_ip, $allowed_ips)) {
    require_once 'die.php';
    die();
}
require_once 'barcode_system.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
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
                    <a href="#" 
                        class="btn btn-outline-primary d-inline-flex align-items-center" 
                        id="open-path-btn" 
                        role="button">
                        <i class="fa-solid fa-folder-open me-2"></i> 
                        Open Path
                    </a>

                </div>
            </div>  

            <form id="filter-form" class="filter-form card p-3 shadow-sm" action="" method="GET">
                <input type="hidden" name="view" value="<?php echo $current_view; ?>">
                <h5 class="mb-3"><i class="fa-solid fa-arrow-up-wide-short"></i> Filter Options</h5>
                <div class="row mb-3 align-items-end">
                    <div class="col-md-2">
                        <label for="filter-of" class="form-label mb-2">OF Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                            <input type="number" class="form-control" id="filter-of" name="filter_of"
                                value="<?php echo htmlspecialchars($filter_of_number); ?>" placeholder="Enter OF number">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label for="filter-size" class="form-label mb-2">Size</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-ruler"></i></span>
                            <input type="text" class="form-control" id="filter-size" name="filter_size"
                                value="<?php echo htmlspecialchars($filter_size); ?>" placeholder="Enter size">
                        </div>
                    </div>
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
                    <div class="col-md-2">
                        <label for="filter-date" class="form-label mb-2">Date</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
                            <input type="date" class="form-control" id="filter-date" name="filter_date"
                                value="<?php echo htmlspecialchars($filter_date ?? ''); ?>" placeholder="Select date">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-filter me-1"></i> Filter
                            </button>
                            <button type="button" id="clear-filters" class="btn btn-outline-dark">
                                <i class="fa-solid fa-broom"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        <div class="container-fluid py-3">

        <div class="d-none d-md-block">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>OF_Number</th>
                        <th>Size</th>
                        <th>Category</th>
                        <th>Piece Name</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Stage</th>
                        <th>Chef</th>
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

                            <td><?php echo htmlspecialchars($barcode['stage']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['chef']); ?></td>

                            <td><?php echo htmlspecialchars($barcode['full_barcode_name']); ?></td>
                            <td><?php echo htmlspecialchars($barcode['last_update']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
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
    
    <div class="modal <?php echo $show_modal ? 'show' : ''; ?>" id="barcode-modal">
        <div class="modal-content">
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
                    <input
                        class="form-control ofinput"
                        type="number"
                        id="barcode-prefix"
                        name="barcode_prefix"
                        placeholder="Enter OF number"
                        required
                        autofocus
                        >
                    </div>
                </div>
                
                <div class="row mb-3">
                    <label for="barcode-size" class="col-sm-3 col-form-label">Size</label>
                    <div class="col-sm-9">
                        <input type="text" id="barcode-size" name="barcode_size" class="form-control ofinput" placeholder="Enter size number" required>
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
                        <button type="submit" class="btn btn-primary" id="generate-button">
                            <i class="fa-solid fa-qrcode"></i> Generate Barcodes
                            <span class="spinner-border spinner-border-sm ms-2 d-none" id="generate-spinner" role="status" aria-hidden="true"></span>
                        </button>
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
    echo $random_button_script;
    ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lostBarcodeCheckbox = document.getElementById('lost-barcode');
            const lostBarcodeCount = document.getElementById('lost-barcode-count');
            const checkbox2pcs = document.getElementById('generate-costume-2pcs');
            const checkbox3pcs = document.getElementById('generate-costume-3pcs');
            const rangeFrom = document.getElementById('range-from');
            const rangeTo = document.getElementById('range-to');
            
            if (lostBarcodeCheckbox) {
                lostBarcodeCheckbox.addEventListener('change', function() {
                    lostBarcodeCount.disabled = !this.checked;
                    checkbox2pcs.disabled = this.checked;
                    checkbox3pcs.disabled = this.checked;
                    rangeFrom.disabled = this.checked;
                    rangeTo.disabled = this.checked;
                });
            }
            
            if (checkbox2pcs && checkbox3pcs) {
                checkbox2pcs.addEventListener('change', function() {
                    checkbox3pcs.disabled = this.checked;
                });
                
                checkbox3pcs.addEventListener('change', function() {
                    checkbox2pcs.disabled = this.checked;
                });
            }
            
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
            
            const barcodeForm = document.querySelector('#barcode-modal form');
            const generateButton = document.getElementById('generate-button');
            const generateSpinner = document.getElementById('generate-spinner');
            
            if (barcodeForm) {
                barcodeForm.addEventListener('submit', function(e) {
                    // Show the spinner when form is submitted
                    if (generateSpinner) {
                        generateSpinner.classList.remove('d-none');
                    }
                    
                    // Disable the button to prevent multiple submissions
                    if (generateButton) {
                        generateButton.setAttribute('disabled', 'disabled');
                    }
                    
                    const formInputs = this.querySelectorAll('input:not([type="hidden"]), select');
                    formInputs.forEach(input => {
                        sessionStorage.setItem(input.name, input.value);
                    });
                });
                
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

            const openPathBtn = document.getElementById('open-path-btn');
            const pdfModal = document.getElementById('pdf-modal');
            
            if (openPathBtn && pdfModal) {
                openPathBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    pdfModal.style.display = 'block';
                    
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
            
            window.addEventListener('click', function(event) {
                if (event.target === pdfModal) {
                    pdfModal.style.display = 'none';
                }
            });
        });
        
        function initPdfModalEvents() {
            const searchForm = document.getElementById('pdf-search-form');
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const searchParams = new URLSearchParams(formData);
                    
                    document.getElementById('pdf-modal-content').innerHTML = `
                        <div id="pdf-modal-loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Searching...</p>
                        </div>
                    `;
                    
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
            const clearFilterBtn = document.getElementById('clear-pdf-filters');
            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function() {
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