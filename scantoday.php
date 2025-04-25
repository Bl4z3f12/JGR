<?php
require "scantoday_settings.php";
// Import any additional PHP logic files here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <!-- Bootstrap for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tab-content {
            padding: 20px 0;
        }
        .card {
            margin-bottom: 20px;
        }
        .alert {
            margin-top: 15px;
        }
        .stage-badge {
            margin-right: 5px;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <h1 class="mb-4" style="font-size: 18px;">Scanned Today</h1>
            
            <!-- Display success/error messages if any -->
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- FIX: Initialize $active_tab at the beginning of the PHP file if not already set -->
            <?php 
            // Make sure $active_tab is initialized
            $active_tab = $_GET['tab'] ?? 'summary';
            ?>
            
            <!-- Tab navigation -->
            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $active_tab == 'summary' ? 'active' : ''; ?>" 
                       href="?tab=summary" role="tab">Summary</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $active_tab == 'quantity_coupe' ? 'active' : ''; ?>" 
                       href="?tab=quantity_coupe" role="tab">Quantity Coupe</a>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Summary Tab -->
                <?php if($active_tab == 'summary'): ?>
    <div class="card">
        <div class="card-header">
            <h5>Search</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="tab" value="summary">
                
                <div class="col-md-2">
                    <label for="of_number" class="form-label">OF Number</label>
                    <input type="text" class="form-control" id="of_number" name="of_number" 
                           value="<?php echo htmlspecialchars($of_number); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="size" class="form-label">Size</label>
                    <input type="text" class="form-control" id="size" name="size" value="<?php echo htmlspecialchars($size); ?>">        
                </div>
                
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="select" <?php echo ($category === 'select') ? 'selected' : ''; ?>>All Categories</option>
                        <?php
                        $category_options = ['R', 'C', 'L', 'LL', 'CC', 'N'];
                        foreach ($category_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($category === $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="p_name" class="form-label">Piece Name</label>
                    <select class="form-select" id="p_name" name="p_name">
                        <option value="select" <?php echo ($p_name === 'select') ? 'selected' : ''; ?>>All Pieces</option>
                        <?php
                        $p_name_options = ['P', 'V', 'G', 'M'];
                        foreach ($p_name_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($p_name === $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="stage" class="form-label">Stage</label>
                    <select class="form-select" id="stage" name="stage">
                        <option value="select" <?php echo ($stage === 'select') ? 'selected' : ''; ?>>All Stages</option>
                        <?php
                        $stage_options = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_fini'];
                        foreach ($stage_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($stage === $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" 
                           value="<?php echo htmlspecialchars($date); ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" name="search" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="?tab=summary" class="btn btn-secondary"><i class="fa-solid fa-broom"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stage Summary -->
    <div class="card">
        <div class="card-header">
            <h5>Stage Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach($stage_options as $s): ?>
                    <div class="col-md-2 mb-2">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5><?php echo htmlspecialchars($s); ?></h5>
                                <span class="badge bg-primary fs-5">
                                    <?php echo $stage_summary[$s] ?? ""; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-primary">
                <tr>
                    <th>OF Number</th>
                    <th>Size</th>
                    <th>Category</th>
                    <th>Piece Name</th>
                    <th>Total Count</th>
                    <th>Chef</th>
                    <th>Total Stage Quantity</th>
                    <th>Total Main Quantity</th>
                    <th>Stages</th>
                    <th>Solped Client</th>
                    <th>Pedido Client</th>
                    <th>Color Tissus</th>
                    <th>Main Qty</th>
                    <th>Qty Coupe</th>
                    <th>Manque</th>
                    <th>Suv Plus</th>
                    <th>Latest Update</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($grouped_results)): ?>
                    <tr>
                        <td colspan="17" class="text-center">No records found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($grouped_results as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['of_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['size']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo htmlspecialchars($row['p_name']); ?></td>
                            <td><?php echo $row['total_count']; ?></td>
                            <td><?php echo htmlspecialchars($row['chef'] ?? ''); ?></td>
                            <td><?php echo $row['total_stage_quantity']; ?></td>
                            <td><?php echo $row['total_main_quantity']; ?></td>
                            <td>
                                <?php 
                                $stages = explode(', ', $row['stage']);
                                foreach($stages as $s): ?>
                                    <span class="badge bg-secondary stage-badge">
                                        <?php echo htmlspecialchars($s); ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['solped_client'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['pedido_client'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['color_tissus'] ?? ''); ?></td>
                            <td><?php echo $row['principale_quantity']; ?></td>
                            <td><?php echo $row['quantity_coupe']; ?></td>
                            <td><?php echo $row['manque']; ?></td>
                            <td><?php echo $row['suv_plus']; ?></td>
                            <td><?php echo $row['latest_update'] ? date('Y-m-d H:i', strtotime($row['latest_update'])) : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
                
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
                                            <input type="text" class="form-control" id="of_number" name="of_number" 
                                                   value="<?php echo htmlspecialchars($barcode_data['of_number']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="size" class="form-label">Size</label>
                                            <input type="text" class="form-control" id="size" name="size" 
                                                   value="<?php echo htmlspecialchars($barcode_data['size']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="category" class="form-label">Category</label>
                                            <input type="text" class="form-control" id="category" name="category" 
                                                   value="<?php echo htmlspecialchars($barcode_data['category']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="piece_name" class="form-label">Piece Name</label>
                                            <input type="text" class="form-control" id="piece_name" name="piece_name" 
                                                   value="<?php echo htmlspecialchars($barcode_data['piece_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-12">
                                            <button type="submit" name="check_barcode" class="btn btn-primary">Check Barcode</button>
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
                    
                    <!-- Search Form for Quantity Coupe -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Search Quantity Coupe Records</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="row g-3">
                                <input type="hidden" name="tab" value="quantity_coupe">
                                
                                <div class="col-md-4">
                                    <label for="of_number" class="form-label">OF Number</label>
                                    <input type="text" class="form-control" id="of_number" name="of_number" 
                                           value="<?php echo htmlspecialchars($of_number); ?>">
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <a href="?tab=quantity_coupe" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Quantity Coupe Results Table -->
                    <div class="table-responsive mt-4">
                        <table class="table table-striped table-hover">
                            <thead>
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>