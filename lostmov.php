<?php
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
    
    <div class="container-fluid mt-3">
    <h1><i class="fas fa-search"></i> Lost Barcodes Tracking</h1>
    <p class="text-muted">Track the movement of barcodes with "X" in their names</p>
    
    <?php if(isset($connection_error)): ?>
    <div>
        <strong>Error:</strong> <?php echo htmlspecialchars($connection_error); ?>
    </div>
    <?php else: ?>
    
    <!-- Filters - Changed to specific date -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="m-0"><i class="fas fa-filter"></i> Filter by Specific Date</h5>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="specific_date" class="form-label">Specific Date</label>
                    <input type="date" class="form-control" id="specific_date" name="specific_date" value="<?php echo $filter_specific_date; ?>">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="lostmov.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>
                <!-- Barcode List -->
            <div class="col-lg-9 mb-4">
                <!-- Desktop Table -->
                <div class="d-none d-md-block">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>OF #</th><th>Size</th><th>Category</th><th>Piece</th>
                                <th>Order</th><th>Status</th><th>Stage</th><th>Chef</th>
                                <th>User</th><th>Full Barcode</th><th>Last Update</th>
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
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        
    <div class="row">
        <!-- Stages Overview 
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="m-0"><i class="fas fa-chart-pie"></i> Lost Barcodes by Stage</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($stage_counts as $stage => $count): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($stage); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($stage_counts)): ?>
                            <div class="alert alert-info">No lost barcodes found.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <strong>Total: <?php echo count($lost_barcodes); ?> lost barcodes</strong>
                </div>
            </div>
        </div>
        -->
        <!-- Barcodes List
        <div class="col-md-9 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="m-0"><i class="fas fa-barcode"></i> Lost Barcodes for <?php echo $filter_specific_date; ?></h5>
                </div>
                <div class="card-body" style="max-height: 700px; overflow-y: auto;">
                    <?php if (empty($lost_barcodes)): ?>
                        <div class="alert alert-info">No lost barcodes found for the selected date.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Details</th>
                                        <th>Stage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lost_barcodes as $barcode): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($barcode['full_barcode_name']); ?></strong><br>
                                                <strong>OF:</strong> <?php echo htmlspecialchars($barcode['of_number']); ?>
                                            </td>
                                            <td>
                                                <strong>Piece:</strong> <?php echo htmlspecialchars($barcode['piece_name']); ?><br>
                                                <strong>Category:</strong> <?php echo htmlspecialchars($barcode['category']); ?><br>
                                                <strong>Size:</strong> <?php echo htmlspecialchars($barcode['size']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($barcode['current_stage'] ?: 'Unknown'); ?></span><br>
                                                <small class="text-muted">Last seen: <?php echo date('Y-m-d H:i', strtotime($barcode['last_seen'])); ?></small>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary history-btn" data-barcode="<?php echo htmlspecialchars($barcode['full_barcode_name']); ?>">
                                                    <i class="fas fa-history"></i> View History
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    </div>
</div>
 -->
<!-- Modal for Barcode History 
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="historyModalLabel">
                    <i class="fas fa-history"></i> Movement History
                    <small id="modalBarcodeTitle"></small>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="historyLoader" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div id="historyContent" class="timeline" style="display: none;"></div>
                <div id="noHistoryAlert" class="alert alert-info" style="display: none;">No movement history found for this barcode.</div>
                <div id="errorAlert" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize modal
    var historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    
    // Handle click on barcode history button
    $('.history-btn').on('click', function() {
        var barcode = $(this).data('barcode');
        $('#modalBarcodeTitle').text(barcode);
        
        // Show loader and hide content
        $('#historyLoader').show();
        $('#historyContent').hide();
        $('#noHistoryAlert').hide();
        $('#errorAlert').hide();
        
        // Show the modal
        historyModal.show();
        
        // Fetch barcode history via AJAX
        $.ajax({
            url: window.location.pathname,
            data: {
                ajax: 'getHistory',
                barcode: barcode
            },
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                // Hide loader
                $('#historyLoader').hide();
                
                // Check for error in response
                if (data.error) {
                    $('#errorAlert').text(data.error).show();
                    return;
                }
                
                if (data.length === 0) {
                    // Show no history alert
                    $('#noHistoryAlert').show();
                } else {
                    // Populate and show history content
                    var historyHtml = '<div class="table-responsive">';
                    historyHtml += '<table class="table table-striped">';
                    historyHtml += '<thead><tr><th>Action</th><th>Stage</th><th>Date/Time</th></tr></thead>';
                    historyHtml += '<tbody>';
                    
                    $.each(data, function(index, history) {
                        var actionBadge = '';
                        switch(history.action_type) {
                            case 'INSERT':
                                actionBadge = '<span class="badge bg-success">Created</span>';
                                break;
                            case 'UPDATE':
                                actionBadge = '<span class="badge bg-primary">Updated</span>';
                                break;
                            case 'DELETE':
                                actionBadge = '<span class="badge bg-danger">Deleted</span>';
                                break;
                            default:
                                actionBadge = '<span class="badge bg-secondary">' + history.action_type + '</span>';
                        }
                        
                        historyHtml += '<tr>';
                        historyHtml += '<td>' + actionBadge + '</td>';
                        historyHtml += '<td><span class="badge bg-info text-white">' + (history.stage || 'No Stage') + '</span></td>';
                        historyHtml += '<td><i class="fas fa-calendar-alt"></i> ' + new Date(history.last_update).toLocaleString() + '</td>';
                        historyHtml += '</tr>';
                    });
                    
                    historyHtml += '</tbody></table></div>';
                    $('#historyContent').html(historyHtml).show();
                }
            },
            error: function(xhr, status, error) {
                $('#historyLoader').hide();
                console.error('AJAX Error:', status, error);
                $('#errorAlert').html('<i class="fas fa-exclamation-triangle"></i> Error loading barcode history. Please try again. Error: ' + error).show();
            }
        });
    });
});
</script>
</body>
</html>