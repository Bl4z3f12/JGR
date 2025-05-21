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
            <table class="table table-bordered table-hover">
                <thead class="table-primary">
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
                    <label for="name" class="col-sm-3 col-form-label">Used by <span class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <select class="form-select" id="name" name="name" disabled>
                            <option value="">Select</option>
                            <option value="Othmane">Othmane</option>
                            <option value="Othmane Jebar">Othmane Jebar</option>
                            <option value="Brahim Akikab">Brahim Akikab</option>
                            <option value="Mohamed Errhioui">Mohamed Errhioui</option>
                            <option value="Toujaj Malika">Toujaj Malika</option>
                        </select>
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
            // Get elements
            const els = {
                lostBarcode: document.getElementById('lost-barcode'),
                lostBarcodeCount: document.getElementById('lost-barcode-count'),
                userName: document.getElementById('name'),
                checkbox2pcs: document.getElementById('generate-costume-2pcs'),
                checkbox3pcs: document.getElementById('generate-costume-3pcs'),
                rangeFrom: document.getElementById('range-from'),
                rangeTo: document.getElementById('range-to'),
                clearFiltersBtn: document.getElementById('clear-filters'),
                barcodeForm: document.querySelector('#barcode-modal form'),
                generateButton: document.getElementById('generate-button'),
                generateSpinner: document.getElementById('generate-spinner'),
                openPathBtn: document.getElementById('open-path-btn'),
                pdfModal: document.getElementById('pdf-modal')
            };

            // Lost barcode handler
            function handleLostBarcodeChange() {
                const isChecked = els.lostBarcode.checked;
                els.lostBarcodeCount.disabled = !isChecked;
                els.userName.disabled = !isChecked;
                els.userName.required = isChecked;
                
                // Disable other fields when lost barcode is checked
                els.rangeFrom.disabled = isChecked;
                els.rangeTo.disabled = isChecked;
                els.checkbox2pcs.disabled = isChecked;
                els.checkbox3pcs.disabled = isChecked;
            }

            // Costume checkboxes handler
            function handleCostumeCheckboxes() {
                if (!els.lostBarcode.checked) {
                    if (els.checkbox2pcs.checked) {
                        els.checkbox3pcs.disabled = true;
                    } else if (els.checkbox3pcs.checked) {
                        els.checkbox2pcs.disabled = true;
                    } else {
                        els.checkbox2pcs.disabled = false;
                        els.checkbox3pcs.disabled = false;
                    }
                }
            }

            // Set up event listeners
            if (els.lostBarcode) {
                els.lostBarcode.addEventListener('change', handleLostBarcodeChange);
                // Initialize form state
                handleLostBarcodeChange();
            }

            if (els.checkbox2pcs && els.checkbox3pcs) {
                els.checkbox2pcs.addEventListener('change', handleCostumeCheckboxes);
                els.checkbox3pcs.addEventListener('change', handleCostumeCheckboxes);
            }

            // Clear filters button
            if (els.clearFiltersBtn) {
                els.clearFiltersBtn.addEventListener('click', function() {
                    const filterForm = document.getElementById('filter-form');
                    filterForm.querySelectorAll('input[type="text"], input[type="number"]')
                        .forEach(input => { input.value = ''; });
                    filterForm.submit();
                });
            }

            // Form submission handler
            if (els.barcodeForm) {
                els.barcodeForm.addEventListener('submit', function() {
                    // Show spinner, disable button
                    if (els.generateSpinner) els.generateSpinner.classList.remove('d-none');
                    if (els.generateButton) els.generateButton.setAttribute('disabled', 'disabled');
                    
                    // Save form inputs to session storage
                    this.querySelectorAll('input:not([type="hidden"]), select').forEach(input => {
                        sessionStorage.setItem(input.name, input.value);
                    });
                });

                // Restore form values from session storage
                if (window.sessionStorage) {
                    els.barcodeForm.querySelectorAll('input:not([type="hidden"]), select').forEach(input => {
                        const savedValue = sessionStorage.getItem(input.name);
                        if (savedValue !== null) input.value = savedValue;
                    });
                }
            }

            // PDF modal handling
            if (els.openPathBtn && els.pdfModal) {
                els.openPathBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    els.pdfModal.style.display = 'block';
                    
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

                // Close modal when clicking outside
                window.addEventListener('click', function(event) {
                    if (event.target === els.pdfModal) {
                        els.pdfModal.style.display = 'none';
                    }
                });
            }
        });

        // PDF modal events initialization
        function initPdfModalEvents() {
            const searchForm = document.getElementById('pdf-search-form');
            const clearFilterBtn = document.getElementById('clear-pdf-filters');
            const modalContent = document.getElementById('pdf-modal-content');
            
            function showLoader(message) {
                modalContent.innerHTML = `
                    <div id="pdf-modal-loader">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">${message}</p>
                    </div>
                `;
            }
            
            function loadPdfContent(url = 'pdf.php') {
                fetch(url)
                    .then(response => response.text())
                    .then(data => {
                        modalContent.innerHTML = data;
                        initPdfModalEvents();
                    })
                    .catch(error => {
                        modalContent.innerHTML = 
                            '<div class="alert alert-danger">Error loading PDF content: ' + error.message + '</div>';
                    });
            }
            
            // Search form handler
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const searchParams = new URLSearchParams(formData);
                    
                    showLoader('Searching...');
                    loadPdfContent('pdf.php?' + searchParams.toString());
                });
            }
            
            // Clear filters button
            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function() {
                    showLoader('Loading PDF files...');
                    loadPdfContent();
                });
            }
        }
    </script>
    
</body>
</html>