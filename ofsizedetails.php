<?php
$current_view = 'ofsizedetails.php'; // Add this line
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

require "ofsizedetails_settings.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>OF_ Size Details</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <h1 class="mb-4" style="font-size: 18px;">OF_ Size Details</h1>
            
            <!-- Display success/error messages if any -->
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php 
            $active_tab = $_GET['tab'] ?? 'quantity_coupe';
            ?>

            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $active_tab == 'quantity_coupe' ? 'active' : ''; ?>" 
                       href="?tab=quantity_coupe" role="tab">Quantity Coupe</a>
                </li>
            </ul>
                    
                <!-- Quantity Coupe Tab -->
                <?php if($active_tab == 'quantity_coupe'): ?>
                    <div class="row">
                        <!-- Barcode Check Form -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Check Barcode</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="?tab=quantity_coupe" class="row g-3">
                                        <div class="col-md-6">
                                            <label for="of_number" class="form-label">OF Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                                                <input type="number" class="form-control" id="of_number" name="of_number" placeholder="Enter Of number"
                                                    value="<?php echo htmlspecialchars($barcode_data['of_number']); ?>" required>
                                            </div>
                                        </div>

                                        
                                        <div class="col-md-6">
                                            <label for="size" class="form-label">Size</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-ruler"></i></span>
                                                <input type="text" class="form-control" id="size" name="size" placeholder="Enter Size"
                                                    value="<?php echo htmlspecialchars($barcode_data['size']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="category" class="form-label">Category</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-tags"></i></span>
                                                <?php $category_options = ['R', 'C', 'L', 'LL', 'CC', 'N']; ?>
                                                <select class="form-select" id="category" name="category">
                                                    <option value="">Select Category</option>
                                                    <?php foreach($category_options as $option): ?>
                                                        <option value="<?php echo $option; ?>" <?php echo (isset($barcode_data['category']) && $barcode_data['category'] === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="piece_name" class="form-label">Piece Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fa-solid fa-puzzle-piece"></i></span>
                                                <?php $piece_name_options = ['P', 'V', 'G', 'M']; ?>
                                                <select class="form-select" id="piece_name" name="piece_name" required>
                                                    <option value="">Select Piece Name</option>
                                                    <?php foreach($piece_name_options as $option): ?>
                                                        <option value="<?php echo $option; ?>" <?php echo (isset($barcode_data['piece_name']) && $barcode_data['piece_name'] === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" name="check_barcode" class="btn btn-primary">Check Barcode <i class="fa-solid fa-microscope"></i></button>
                                        </div>
                                        
                                        <?php if($barcode_checked): ?>
                                            <div class="col-12">
                                                <?php if($barcode_exists): ?>
                                                    <div class="alert alert-success">
                                                        Barcode exists! You can now enter quantity data.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-danger">
                                                        Barcode does not exist. Please check your input.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quantity Coupe Form -->
                        <div class="col-md-6">
                            <?php if($barcode_checked && $barcode_exists): ?>
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Enter Quantity Data</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="?tab=quantity_coupe" class="row g-3" id="quantityForm">
                                            <input type="hidden" name="of_number" value="<?php echo htmlspecialchars($barcode_data['of_number']); ?>">
                                            <input type="hidden" name="size" value="<?php echo htmlspecialchars($barcode_data['size']); ?>">
                                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($barcode_data['category']); ?>">
                                            <input type="hidden" name="piece_name" value="<?php echo htmlspecialchars($barcode_data['piece_name']); ?>">
                                            
                                            <div class="col-md-6">
                                                <label for="solped_client" class="form-label">Solped Client</label>
                                                <input type="text" class="form-control" id="solped_client" name="solped_client">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="pedido_client" class="form-label">Pedido Client</label>
                                                <input type="text" class="form-control" id="pedido_client" name="pedido_client">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="color_tissus" class="form-label">Color Tissus</label>
                                                <input type="text" class="form-control" id="color_tissus" name="color_tissus">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="principale_quantity" class="form-label">Main Quantity</label>
                                                <input type="number" class="form-control" id="principale_quantity" name="principale_quantity" value="0" min="0">
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="quantity_coupe" class="form-label">Quantity Coupe</label>
                                                <input type="number" class="form-control" id="quantity_coupe" name="quantity_coupe" value="0" min="0">
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="manque" class="form-label">Manque</label>
                                                <input type="number" class="form-control" id="manque" name="manque" value="0" min="0" readonly>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="suv_plus" class="form-label">Suv Plus</label>
                                                <input type="number" class="form-control" id="suv_plus" name="suv_plus" value="0" min="0" readonly>
                                            </div>
                                            
                                            <div class="col-12">
                                                <button type="submit" name="save_quantity" class="btn btn-success">Save Quantity Data</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Search Quantity Coupe Records</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <input type="hidden" name="tab" value="quantity_coupe">
                                
                                <div class="search-inline-container">
                                    <div class="search-field">
                                        <label for="of_number" class="form-label">OF Number</label>
                                        <input type="number" class="form-control" id="of_number" name="of_number" placeholder="Enter OF number" value="">
                                    </div>
                                    
                                    <div class="button-group">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                                        <a href="?tab=quantity_coupe" class="btn btn-outline-dark"><i class="fa-solid fa-broom"></i> Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>     
                                   
                    <!-- Quantity Coupe Results Table -->
                    <div class="table-responsive mt-4">
                        <table class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>OF Number</th>
                                    <th>Size</th>
                                    <th>Category</th>
                                    <th>Piece Name</th>
                                    <th>Solped Client</th>
                                    <th>Pedido Client</th>
                                    <th>Color Tissus</th>
                                    <th>Main Qty</th>
                                    <th>Qty Coupe</th>
                                    <th>Manque</th>
                                    <th>Suv Plus</th>
                                    <th>Last Update</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($quantity_coupe_data) && !empty($quantity_coupe_data)): ?>
                                    <?php foreach($quantity_coupe_data as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['of_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['size']); ?></td>
                                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                                            <td><?php echo htmlspecialchars($row['piece_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['solped_client'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['pedido_client'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['color_tissus'] ?? ''); ?></td>
                                            <td><?php echo $row['principale_quantity']; ?></td>
                                            <td><?php echo $row['quantity_coupe']; ?></td>
                                            <td><?php echo $row['manque']; ?></td>
                                            <td><?php echo $row['suv_plus']; ?></td>
                                            <td><?php echo $row['lastupdate'] ? date('Y-m-d H:i', strtotime($row['lastupdate'])) : ''; ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?tab=quantity_coupe&delete_qc=1&id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="13" class="text-center">No records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="d-flex flex-column bg-white align-items-center">
            <div class="spinner-border text-primary" ></div>
            <h5 class="mt-3 mb-2 text-center text-dark">
                Processing Your Request...
            </h5>
            <p class="text-muted text-center" >
                This may take a moment depending on data size
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Loading Screen Script -->
        
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show loading overlay on page load
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Hide when page is fully loaded
            window.addEventListener('load', function() {
                document.getElementById('loadingOverlay').style.display = 'none';
            });

            // Existing code for forms and reset buttons
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Check if it's the Excel export form
                    if (form.action && form.action.includes('export_excel.php')) {
                        // For Excel export, show the overlay but hide it after a short delay
                        document.getElementById('loadingOverlay').style.display = 'flex';
                        setTimeout(function() {
                            document.getElementById('loadingOverlay').style.display = 'none';
                        }, 3000); // Hide after 3 seconds, assuming download has started
                    } else {
                        // For regular forms
                        document.getElementById('loadingOverlay').style.display = 'flex';
                    }
                });
            });
            
            // Add specific handling for export to Excel button
            document.querySelectorAll('button[type="submit"][name="export"]').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('loadingOverlay').style.display = 'flex';
                    setTimeout(function() {
                        document.getElementById('loadingOverlay').style.display = 'none';
                    }, 3000); // Hide after 3 seconds, assuming download has started
                });
            });
            
            // Add specific handling for Export to Excel link in the form
            document.querySelectorAll('form[action="export_excel.php"]').forEach(form => {
                form.addEventListener('submit', function() {
                    document.getElementById('loadingOverlay').style.display = 'flex';
                    setTimeout(function() {
                        document.getElementById('loadingOverlay').style.display = 'none';
                    }, 3000); // Hide after 3 seconds, assuming download has started
                });
            });
            
            document.querySelectorAll('a.btn-secondary').forEach(link => {
                link.addEventListener('click', () => document.getElementById('loadingOverlay').style.display = 'flex');
            });
        });

    </script>

    <!-- Auto-Calculate Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get references to the form elements
            const form = document.getElementById('quantityForm');
            if (!form) return; // Exit if the form doesn't exist on this page
            
            const mainQuantityInput = document.getElementById('principale_quantity');
            const qtyCouperInput = document.getElementById('quantity_coupe');
            const manqueInput = document.getElementById('manque');
            const suvPlusInput = document.getElementById('suv_plus');
            
            // Function to calculate manque and suv_plus
            function calculateDifferences() {
                const mainQty = parseInt(mainQuantityInput.value) || 0;
                const coupeQty = parseInt(qtyCouperInput.value) || 0;
                
                // If quantity_coupe is less than principale_quantity, there's a shortage (manque)
                // If quantity_coupe is more than principale_quantity, there's an excess (suv_plus)
                if (coupeQty < mainQty) {
                    manqueInput.value = mainQty - coupeQty;
                    suvPlusInput.value = 0;
                } else if (coupeQty > mainQty) {
                    suvPlusInput.value = coupeQty - mainQty;
                    manqueInput.value = 0;
                } else {
                    // If they're equal, both are zero
                    manqueInput.value = 0;
                    suvPlusInput.value = 0;
                }
            }
            
            // Add event listeners to recalculate when values change
            mainQuantityInput.addEventListener('input', calculateDifferences);
            qtyCouperInput.addEventListener('input', calculateDifferences);
            
            // Calculate initial values if the form is loaded with existing data
            calculateDifferences();
            
        });
    </script>
    
        
    <!-- Initialize tooltips - add to your script section -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>