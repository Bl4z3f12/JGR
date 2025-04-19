<?php
// Import the PHP logic file
require_once 'barcode_system.php';

// Add this line right after including barcode_system.php
// This will override the default items per page (likely defined in barcode_system.php)
$items_per_page = 2000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
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
                    <a href="pdf.php" class="btn-create" id="open-path-btn">
                            <span><i class="fa-solid fa-folder-open"></i></span> Open Path
                    </a>
                </div>
            </div>            
            <form id="filter-form" class="filter-form card p-3 shadow-sm" action="" method="GET">
  <input type="hidden" name="view" value="<?php echo $current_view; ?>">
  
  <h5 class="mb-3"><i class="fa-solid fa-arrow-up-wide-short"></i> Filter Options</h5>
  
  <div class="row mb-3">
    <div class="col-md-6">
      <div class="form-floating mb-3">
        <input type="text" class="form-control" id="filter-of" name="filter_of" value="<?php echo htmlspecialchars($filter_of_number); ?>" placeholder="Enter OF number">
        <label for="filter-of">Filter by OF Number</label>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="form-floating mb-3">
        <input type="number" class="form-control" id="filter-size" name="filter_size" value="<?php echo htmlspecialchars($filter_size); ?>" placeholder="Enter size">
        <label for="filter-size">Filter by Size</label>
      </div>
    </div>
  </div>
  
  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-filter me-1"></i> Apply Filters
    </button>
    <button type="button" id="clear-filters" class="btn btn-outline-secondary">
        <i class="fa-solid fa-broom"></i> Clear
    </button>
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
                            <span class="status-badge status-<?php echo strtolower($barcode['status']); ?>">
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
            <div class="modal-header">
                <h3>Barcode Generator</h3>
                <a href="?view=<?php echo $current_view; ?>" class="close">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            </div>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="create_barcode">
                <input type="hidden" name="view" value="<?php echo $current_view; ?>">
                
                <div class="warning-message">
                    REMEMBER: You will not be able to change, barcode data after you confirm validity of data
                </div>
                
                <div class="form-group">
                    <label for="barcode-prefix">OF_ number *</label>
                    <input class="ofinput" type="text" id="barcode-prefix" name="barcode_prefix" required>
                </div>
                
                <div class="form-group">
                    <label for="barcode-size">Size *</label>
                    <input type="number" id="barcode-size" name="barcode_size" min="1" step="1" class="ofinput" placeholder="Enter size number">
                </div>

                <div class="form-group">
                    <label for="barcode-category">OF_Category</label>
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
                
                <div class="form-group">
                    <label for="barcode-piece-name">Piece Name *</label>
                    <select id="barcode-piece-name" name="barcode_piece_name" class="form-select">
                        <option value="">Select Piece Name</option>
                        <option value="P">P</option>
                        <option value="V">V</option>
                        <option value="G">G</option>
                        <option value="M">M</option>
                    </select>
                </div>
                                
                <div class="checkall">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="lost-barcode" name="lost_barcode" style="margin-right: 5px;"
                            onclick="this.form.lost_barcode_count.disabled = !this.checked;
                                    this.form.generate_costume_2pcs.disabled = this.checked;
                                    this.form.generate_costume_3pcs.disabled = this.checked;
                                    this.form.range_from.disabled = this.checked;
                                    this.form.range_to.disabled = this.checked;">
                        <label for="lost-barcode" style="margin-right: 10px;">Lost Barcode [C prefix]</label>
                        <label style="margin-right: 5px;"></label>
                        <input type="number" id="lost-barcode-count" name="lost_barcode_count"
                            style="margin-right: 20px; width: 60px;"
                            value="1" min="1" max="100" 
                            disabled>
                        <label style="margin-right: 10px;">Random <i class="fa-solid fa-arrows-rotate"></i></label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Piece Order</label>
                    <div class="range-inputs">
                        <div>
                            <label for="range-from">From</label>
                            <input type="number" id="range-from" name="range_from" size="8">
                        </div>
                        <div>
                            <label for="range-to">To</label>
                            <input type="number" id="range-to" name="range_to" size="8">
                        </div>
                    </div>
                </div>

                <div class="checkall2">
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="generate-costume-2pcs" name="generate_costume_2pcs">
                    <label for="generate-costume-2pcs">Generate for P and V (Costume 2pcs)</label>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="generate-costume-3pcs" name="generate_costume_3pcs">
                    <label for="generate-costume-3pcs">Generate for P, V, and G (Costume 3pcs)</label>
                </div>
                    
                <div class="form-group checkbox-group indented">
                    <input type="checkbox" id="generate_pdf_only" name="generate_pdf_only">
                    <label for="generate_pdf_only">Generate only PDF</label>
                </div>
                </div>

                <div class="barcode-actions">
                    <button type="submit" class="btn btn-secondary">Generate Barcodes</button>
                    <a href="?view=<?php echo $current_view; ?>" class="btn btn-danger">Cancel Generating</a>
                </div>
                
                <div class="barcode-range-indicators">
                    <div class="range-indicator">
                        <div class="color-box white-box"></div>
                        <span>1 ---> 1000</span>
                    </div>
                    <div class="range-indicator">
                        <div class="color-box yellow-box"></div>
                        <span>1001 ---> 2000</span>
                    </div>
                    <div class="range-indicator">
                        <div class="color-box green-box"></div>
                        <span>2001 ---> 3000</span>
                    </div>
                </div>
                
                <div class="barcode-legend">
                    <span>1 --> CC / 2 --> C / 3 --> R / 4 --> L / 5 --> LL</span>
                </div>
                
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <?php 
    // Output the random button JavaScript
    echo $random_button_script;
    ?>
</body>
</html>