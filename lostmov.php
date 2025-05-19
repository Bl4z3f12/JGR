<?php
$current_view = 'lost_barcodes_tracking.php';
$host = "localhost";
$dbname = "jgr";
$username = "root";
$password = "";

// Function to handle AJAX error responses
function sendJsonError($message) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Default filters - changed to single specific date filter
    $filter_specific_date = isset($_GET['specific_date']) ? $_GET['specific_date'] : date('Y-m-d');
    
    // Get all lost barcodes (containing 'X' in their name)
    function getLostBarcodes($pdo, $specific_date) {
        $query = "
            SELECT DISTINCT 
                b.full_barcode_name,
                b.of_number,
                b.stage AS current_stage,
                b.piece_name,
                b.category,
                b.size,
                b.last_update AS last_seen
            FROM 
                barcodes b
            WHERE 
                (b.full_barcode_name LIKE '%-X%' OR b.of_number LIKE 'X%')
                AND DATE(b.last_update) = :specific_date
            ORDER BY
                b.last_update DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':specific_date' => $specific_date
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get movement history for a specific barcode - This function will be used via AJAX
    function getBarcodeMovementHistory($pdo, $barcode) {
        $query = "
          SELECT
              h.full_barcode_name,
              h.stage,
              h.action_type,
              h.last_update,
              h.action_time
          FROM
              jgr_barcodes_history h
          WHERE
              h.full_barcode_name = :barcode
          ORDER BY
              h.action_time ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':barcode' => $barcode]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // If this is an AJAX request for barcode history
    if(isset($_GET['ajax']) && $_GET['ajax'] == 'getHistory' && isset($_GET['barcode'])) {
        try {
            header('Content-Type: application/json');
            $history = getBarcodeMovementHistory($pdo, $_GET['barcode']);
            echo json_encode($history);
            exit;
        } catch(PDOException $e) {
            sendJsonError("Database error: " . $e->getMessage());
        }
    }
    
    // Add error handling for AJAX requests
    if(isset($_GET['ajax'])) {
        sendJsonError('Invalid AJAX request');
    }
    
    // Get all lost barcodes for the specific date
    $lost_barcodes = getLostBarcodes($pdo, $filter_specific_date);
    
    // Get counts of lost barcodes by stage
    $stage_counts = [];
    foreach ($lost_barcodes as $barcode) {
        $stage = $barcode['current_stage'] ?: 'Unknown';
        if (!isset($stage_counts[$stage])) {
            $stage_counts[$stage] = 0;
        }
        $stage_counts[$stage]++;
    }
    arsort($stage_counts); // Sort by count in descending order

} catch(PDOException $e) {
    // Check if it's an AJAX request
    if(isset($_GET['ajax'])) {
        sendJsonError("Database connection failed: " . $e->getMessage());
    } else {
        // Regular page request - show error on page
        $connection_error = "Connection failed: " . $e->getMessage();
    }
}
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
                    <a href="lost_barcodes_tracking.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        <!-- Stages Overview -->
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
        
        <!-- Barcodes List -->
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

<!-- Modal for Barcode History -->
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