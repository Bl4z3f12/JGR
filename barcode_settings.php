<?php
// Include PHP logic file
require_once 'settings.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <title>Barcode Settings - Inventory Management System</title>
    <!-- Additional CSS specific to barcode settings -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <div class="container-fluid py-4">
                <h4 class="mb-4">Barcode Settings</h4>
                
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
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="of_number_search" class="form-label">OF Number</label>
                                <input type="text" class="form-control" id="of_number_search" name="of_number_search" value="<?php echo htmlspecialchars($of_number_search); ?>">
                            </div>
                            <div class="col-md-1">
                                <label for="size_search" class="form-label">Size</label>
                                <input type="number" class="form-control" id="size_search" name="size_search" value="<?php echo htmlspecialchars($size_search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="category_search" class="form-label">Category</label>
                                <input type="text" class="form-control" id="category_search" name="category_search" value="<?php echo htmlspecialchars($category_search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="piece_name_search" class="form-label">Piece Name</label>
                                <input type="text" class="form-control" id="piece_name_search" name="piece_name_search" value="<?php echo htmlspecialchars($piece_name_search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="order_str_search" class="form-label">Order</label>
                                <input type="text" class="form-control" id="order_str_search" name="order_str_search" value="<?php echo htmlspecialchars($order_str_search); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <a href="barcode_settings.php" class="btn btn-secondary w-100">Clear</a>
                            </div>
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
                                        <i class="fas fa-edit"></i> Bulk Edit
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
                                <h5 class="card-title">Bulk Edit Selected Barcodes</h5>
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
                                                  $stage_options = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_fini'];
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
                                                $chef_options = ['Habib', 'Youssef' , 'Farah', 'Aziz', 'Abdelkarim', 'Miloud' , 'Abderazaq', 'pfi,i' , 'repa']; 
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
                                <thead>
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
                                    <?php if (empty($barcodes)): ?>
                                        <tr>
                                            <td colspan="11" class="text-center">No barcodes found</td>
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
                                        "P_fini"
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>