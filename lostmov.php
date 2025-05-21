<?php
$current_view = 'lostmovset.php';
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
require_once 'lostmovset.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost Barcodes Tracking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <?php include 'includes/head.php'; ?>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>
        
    <div class="content">
            <div class="container-fluid">
                <h4 class="mb-4" style="font-size: 18px;">Lost Barcodes</h4>
                
                <?php
                    // Check if any filter has been applied (except the default date)
                    $is_filtered = !empty($_GET['of_number']) || 
                                !empty($_GET['size']) || 
                                !empty($_GET['category']) || 
                                !empty($_GET['piece']) || 
                                !empty($_GET['order']) || 
                                !empty($_GET['used_by']) || 
                                (isset($_GET['specific_date']) && $_GET['specific_date'] !== date('Y-m-d'));
                    
                    // If filters are applied, show the count
                    if ($is_filtered): 
                    ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-filter me-2"></i> 
                        <strong>Filtered Results: </strong><?php echo $total_records; ?> record<?php echo $total_records !== 1 ? 's' : ''; ?> found
                        <?php if ($total_records > $items_per_page): ?>
                            <span class="ms-2">(showing <?php echo min($items_per_page, $total_records); ?> per page)</span>
                        <?php endif; ?>
                        <a href="lostmov.php" class="btn btn-sm btn-outline-dark float-end">
                            <i class="fa-solid fa-broom"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>

        <?php if(isset($connection_error)): ?>
        <div>
            <strong>Error:</strong> <?php echo htmlspecialchars($connection_error); ?>
        </div>
        <?php else: ?>
        <!-- Enhanced Filters -->
        <div class="mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    
                    <div class="col">
                        <!-- OF Number Filter -->
                        <label for="of_number" class="form-label">
                            <i class="fas fa-hashtag"></i> OF Number
                        </label>
                        <input type="text" class="form-control" id="of_number" name="of_number" placeholder="OF_ #"
                            value="<?php echo htmlspecialchars($_GET['of_number'] ?? ''); ?>">
                    </div>

                    <div class="col">
                        <!-- OF Number Filter -->
                        <label for="size" class="form-label">
                            <i class="fas fa-ruler"></i> Size
                        </label>
                        <input type="text" class="form-control" id="size" name="size" placeholder="Size"
                            value="<?php echo htmlspecialchars($_GET['size'] ?? ''); ?>">
                    </div>
                    
                    <div class="col">
                        <!-- Category Filter -->
                        <label for="category" class="form-label">
                            <i class="fas fa-tag"></i> Category
                        </label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php 
                            // Get distinct categories
                            $categories = [];
                            foreach ($lost_barcodes as $b) {
                                if (!empty($b['category']) && !in_array($b['category'], $categories)) {
                                    $categories[] = $b['category'];
                                }
                            }
                            sort($categories);
                            
                            foreach ($categories as $category): 
                                $selected = (isset($_GET['category']) && $_GET['category'] === $category) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col">
                        <!-- Piece Filter -->
                        <label for="piece" class="form-label">
                            <i class="fas fa-puzzle-piece"></i> Piece
                        </label>
                        <select class="form-select" id="piece" name="piece">
                            <option value="">All Pieces</option>
                            <?php 
                            // Get distinct pieces
                            $pieces = [];
                            foreach ($lost_barcodes as $b) {
                                if (!empty($b['piece_name']) && !in_array($b['piece_name'], $pieces)) {
                                    $pieces[] = $b['piece_name'];
                                }
                            }
                            sort($pieces);
                            
                            foreach ($pieces as $piece): 
                                $selected = (isset($_GET['piece']) && $_GET['piece'] === $piece) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($piece); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($piece); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col">
                        <!-- Order Filter -->
                        <label for="order" class="form-label">
                            <i class="fas fa-sort"></i> Order
                        </label>
                        <input type="text" class="form-control" id="order" name="order" placeholder="Filter by order"
                            value="<?php echo htmlspecialchars($_GET['order'] ?? ''); ?>">
                    </div>
                    
                    <div class="col">
                        <!-- Used By Filter -->
                        <label for="used_by" class="form-label">
                            <i class="fas fa-user"></i> Used By
                        </label>
                        <select class="form-select" id="used_by" name="used_by">
                            <option value="">All Users</option>
                            <?php 
                            // Get distinct users
                            $users = [];
                            foreach ($lost_barcodes as $b) {
                                if (!empty($b['name']) && !in_array($b['name'], $users)) {
                                    $users[] = $b['name'];
                                }
                            }
                            sort($users);
                            
                            foreach ($users as $user): 
                                $selected = (isset($_GET['used_by']) && $_GET['used_by'] === $user) ? 'selected' : '';
                            ?>
                                <option value="<?php echo htmlspecialchars($user); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($user); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col">
                        <!-- Date Filter -->
                        <label for="specific_date" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Date
                        </label>
                        <input type="date" class="form-control" id="specific_date" name="specific_date" value="<?php echo $filter_specific_date; ?>">
                    </div>
                    
                    <div class="col-auto d-flex align-items-end">
                        <!-- Submit Buttons -->
                        <button type="submit" class="btn btn-primary me-2"><i class="fa-solid fa-filter"></i> Filter</button>
                        <a href="lostmov.php" class="btn btn-outline-dark"><i class="fa-solid fa-broom"></i> Clear</a>
                    </div>
                </form>
            </div>
        </div>

                <!-- Barcode List -->
            <div class="col mb-4">
                <!-- Desktop Table -->
                <div class="d-none d-md-block">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>OF Number</th><th>Size</th><th>Category</th><th>Piece</th>
                                <th>Order</th><th>Status</th><th>Stage</th><th>Chef</th>
                                <th>Used by</th><th>Full Barcode</th><th>Last Update</th><th>Tools</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lost_barcodes)): ?>
                                <tr><td colspan="11" class="text-center">No matching orders</td></tr>
                            <?php else: ?>
                                <?php foreach ($lost_barcodes as $b): ?>
                                    <?php
                                        $st = strtolower($b['status'] ?? '');
                                        switch ($st) {
                                            case 'completed':   $cls='bg-success'; $ico='fa-check'; break;
                                            case 'in progress': $cls='bg-warning'; $ico='fa-clock'; break;
                                            case 'pending':     $cls='bg-secondary'; $ico='fa-hourglass'; break;
                                            case 'error':       $cls='bg-danger'; $ico='fa-exclamation-circle'; break;
                                            default:            $cls='bg-info'; $ico='fa-info-circle';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($b['of_number']); ?></td>
                                        <td><?php echo htmlspecialchars($b['size']); ?></td>
                                        <td><?php echo htmlspecialchars($b['category']); ?></td>
                                        <td><?php echo htmlspecialchars($b['piece_name']); ?></td>
                                        <td><?php echo htmlspecialchars($b['order_str']); ?></td>
                                        <td><span class="badge <?php echo $cls; ?>">
                                              <i class="fas <?php echo $ico; ?> me-1"></i><?php echo htmlspecialchars($b['status'] ?? ''); ?>
                                            </span></td>
                                        <td><?php echo htmlspecialchars($b['current_stage']); ?></td>
                                        <td><?php echo htmlspecialchars($b['chef']); ?></td>
                                        <td><?php echo htmlspecialchars($b['name']); ?></td>
                                        <td><?php echo htmlspecialchars($b['full_barcode_name']); ?></td>
                                        <td><?php echo htmlspecialchars($b['last_seen']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary edit-btn" data-barcode="<?php echo htmlspecialchars($b['full_barcode_name']); ?>" data-user="<?php echo htmlspecialchars($b['name']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-btn" data-barcode="<?php echo htmlspecialchars($b['full_barcode_name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="d-md-none">
                    <?php if (empty($lost_barcodes)): ?>
                        <div class="alert alert-info text-center">No matching orders</div>
                    <?php else: ?>
                        <?php foreach ($lost_barcodes as $b): ?>
                            <?php
                                $st = strtolower($b['status'] ?? '');
                                switch ($st) {
                                    case 'completed':   $cls='bg-success'; $ico='fa-check'; break;
                                    case 'in progress': $cls='bg-warning'; $ico='fa-clock'; break;
                                    case 'pending':     $cls='bg-secondary'; $ico='fa-hourglass'; break;
                                    case 'error':       $cls='bg-danger'; $ico='fa-exclamation-circle'; break;
                                    default:            $cls='bg-info'; $ico='fa-info-circle';
                                }
                            ?>
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between">
                                    <strong><?php echo htmlspecialchars($b['piece_name']); ?></strong>
                                    <span class="badge <?php echo $cls; ?>"><i class="fas <?php echo $ico; ?>"></i></span>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>OF:</span><span><?php echo htmlspecialchars($b['of_number']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Size:</span><span><?php echo htmlspecialchars($b['size']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Order:</span><span><?php echo htmlspecialchars($b['order_str']); ?></span>
                                    </li>
                                    
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Piece:</span><span><?php echo htmlspecialchars($b['piece_name']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Last Update:</span><span><?php echo htmlspecialchars($b['last_seen']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Actions:</span>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary edit-btn" data-barcode="<?php echo htmlspecialchars($b['full_barcode_name']); ?>" data-user="<?php echo htmlspecialchars($b['name']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn" data-barcode="<?php echo htmlspecialchars($b['full_barcode_name']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="editModalLabel">
                                <i class="fas fa-edit"></i> Edit User
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Barcode: </strong><span id="editBarcodeName"></span></p>
                            
                            <div class="mb-3">
                                <label for="editUserSelect" class="form-label">Used by <span class="text-danger">*</span></label>
                                <select class="form-select" id="editUserSelect" name="editUserSelect">
                                    <option value="">Select</option>
                                    <option value="Othmane">Othmane</option>
                                    <option value="Othmane Jebar">Othmane Jebar</option>
                                    <option value="Brahim Akikab">Brahim Akikab</option>
                                    <option value="Mohamed Errhioui">Mohamed Errhioui</option>
                                    <option value="Toujaj Malika">Toujaj Malika</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmEdit">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add this Modal for Delete Confirmation -->
            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="deleteModalLabel">
                                <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this barcode?</p>
                            <p><strong>Barcode: </strong><span id="deleteBarcodeName"></span></p>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle"></i> Warning: This action cannot be undone!
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDelete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

<script src="assets/lostmov.js"></script>
</body>
</html>