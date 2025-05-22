<?php
session_start();
$current_view = 'of_analyzer.php';
$host = "localhost";
$dbname = "jgr";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $filter_of = isset($_GET['of_number']) ? trim($_GET['of_number']) : '';
    $filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $search_performed = !empty($filter_of);
    
    // Display stages configuration
    $display_stages = [
        'Coupe' => [
            'icon' => '<i class="fas fa-cut"></i>',
            'color' => 'primary',
        ],
        'V1' => [
            'icon' => '<i class="fas fa-tshirt"></i>',
            'color' => 'info',
        ],
        'V2' => [
            'icon' => '<i class="fas fa-tshirt"></i>',
            'color' => 'info',
        ],
        'V3' => [
            'icon' => '<i class="fas fa-vest"></i>',
            'color' => 'success',
        ],
        'Pantalon' => [
            'icon' => '<i class="fas fa-socks"></i>',
            'color' => 'warning',
        ],
        'AMF' => [
            'icon' => '<i class="fas fa-cut"></i>',
            'color' => 'secondary',
        ],
        'Repassage' => [
            'icon' => '<i class="fas fa-iron"></i>',
            'color' => 'dark',
        ],
        'P_ fini' => [
            'icon' => '<i class="fas fa-box"></i>',
            'color' => 'success',
        ],
        'Exported' => [
            'icon' => '<i class="fas fa-truck"></i>',
            'color' => 'danger',
        ]
    ];
    
    $of_data = [];
    $stage_totals = [];
    $total_barcodes = 0;
    
    if ($search_performed) {
        // Get barcode details for the OF with previous stage information and date filter
        $query = "
        SELECT 
            b.full_barcode_name,
            b.of_number,
            b.size,
            b.category,
            b.piece_name,
            b.chef,
            IFNULL(b.stage, 'No Stage') as current_stage,
            b.last_update,
            IFNULL(prev_h.stage, 'Initial') as previous_stage,
            prev_h.action_time as previous_stage_time
        FROM barcodes b 
        LEFT JOIN jgr_barcodes_history h ON h.full_barcode_name = b.full_barcode_name
        LEFT JOIN (
            SELECT 
                h1.full_barcode_name,
                h1.stage,
                h1.action_time,
                ROW_NUMBER() OVER (
                    PARTITION BY h1.full_barcode_name 
                    ORDER BY h1.action_time DESC
                ) as rn
            FROM jgr_barcodes_history h1
            WHERE h1.full_barcode_name IN (
                SELECT full_barcode_name 
                FROM barcodes 
                WHERE of_number = :of_number
            )
            AND h1.action_time < (
                SELECT MAX(h2.action_time)
                FROM jgr_barcodes_history h2
                WHERE h2.full_barcode_name = h1.full_barcode_name
                AND DATE(h2.last_update) <= :date
            )
        ) prev_h ON prev_h.full_barcode_name = b.full_barcode_name AND prev_h.rn = 1
        WHERE b.of_number = :of_number
        AND h.full_barcode_name IS NOT NULL
        AND DATE(h.last_update) = :date
        AND h.action_type IN ('INSERT', 'UPDATE')
        AND h.last_update = (
            SELECT MAX(h2.last_update)
            FROM jgr_barcodes_history h2
            WHERE h2.full_barcode_name = h.full_barcode_name
            AND DATE(h2.last_update) <= :date
        )
        ORDER BY b.stage, b.piece_name, b.size, b.category
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':of_number' => $filter_of,
            ':date' => $filter_date
        ]);
        $of_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate stage totals
        foreach ($of_data as $row) {
            $stage = $row['current_stage'];
            if (!isset($stage_totals[$stage])) {
                $stage_totals[$stage] = 0;
            }
            $stage_totals[$stage]++;
            $total_barcodes++;
        }
        
        // Sort stage totals by predefined order
        $ordered_stage_totals = [];
        foreach ($display_stages as $stage_name => $stage_props) {
            if (isset($stage_totals[$stage_name])) {
                $ordered_stage_totals[$stage_name] = $stage_totals[$stage_name];
            }
        }
        // Add any other stages not in predefined list
        foreach ($stage_totals as $stage => $count) {
            if (!isset($ordered_stage_totals[$stage])) {
                $ordered_stage_totals[$stage] = $count;
            }
        }
        $stage_totals = $ordered_stage_totals;
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OF Barcode Analyzer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="content">
            <div class="container-fluid">
        
    <!-- Search Section -->
    <div class="bg-gradient bg-primary text-white py-5 mb-4 mt-5">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <h1 class="text-center mb-4">
                        <i class="fas fa-barcode"></i> OF Barcode Analyzer
                    </h1>
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <input 
                                type="text" 
                                name="of_number" 
                                class="form-control form-control-lg" 
                                placeholder="Enter OF Number..." 
                                value="<?php echo htmlspecialchars($filter_of); ?>"
                                required
                            >
                        </div>
                        <div class="col-md-4">
                            <input 
                                type="date" 
                                name="date" 
                                class="form-control form-control-lg" 
                                value="<?php echo htmlspecialchars($filter_date); ?>"
                                required
                            >
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-light btn-lg w-100">
                                <i class="fas fa-search"></i> Analyze
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <?php if ($search_performed): ?>
            <?php if (!empty($of_data)): ?>
                <!-- Summary Cards -->
                <div class="mb-4">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <h3 class="text-primary">OF Number: <?php echo htmlspecialchars($filter_of); ?></h3>
                                    <h4 class="text-muted">Total Barcodes: <span class="badge bg-primary fs-5"><?php echo $total_barcodes; ?></span></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stage Totals Cards -->
                    <h4 class="mb-3"><i class="fas fa-chart-bar"></i> Stage Distribution</h4>
                    <div class="row g-3 mb-4">
                        <?php foreach ($stage_totals as $stage => $count): ?>
                            <?php 
                                $stage_props = isset($display_stages[$stage]) ? $display_stages[$stage] : ['icon' => '<i class="fas fa-question"></i>', 'color' => 'secondary'];
                                $color = $stage_props['color'];
                                $icon = $stage_props['icon'];
                            ?>
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="card border-0 shadow-sm h-100 stage-card" data-stage="<?php echo htmlspecialchars($stage); ?>">
                                    <div class="card-body text-center">
                                        <div class="fs-2 text-<?php echo $color; ?> mb-2">
                                            <?php echo $icon; ?>
                                        </div>
                                        <h6 class="card-title text-<?php echo $color; ?>"><?php echo htmlspecialchars($stage); ?></h6>
                                        <h3 class="text-<?php echo $color; ?> mb-0"><?php echo $count; ?></h3>
                                        <small class="text-muted">
                                            <?php echo round(($count / $total_barcodes) * 100, 1); ?>%
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Detailed Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Detailed Barcode Information</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Current Stage</th>
                                        <th>Previous Stage</th>
                                        <th>Piece Name</th>
                                        <th>Size</th>
                                        <th>Category</th>
                                        <th>Chef</th>
                                        <th>Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($of_data as $row): ?>
                                        <?php 
                                            $current_stage = $row['current_stage'];
                                            $previous_stage = $row['previous_stage'];
                                            
                                            // Fix the "No Stage" issue - show null for previous stage when current is "No Stage"
                                            if ($current_stage === 'No Stage') {
                                                $previous_stage = null;
                                            }
                                            
                                            $current_stage_props = isset($display_stages[$current_stage]) ? $display_stages[$current_stage] : ['color' => 'secondary'];
                                            $previous_stage_props = isset($display_stages[$previous_stage]) ? $display_stages[$previous_stage] : ['color' => 'light'];
                                            $current_badge_color = $current_stage_props['color'];
                                            $previous_badge_color = $previous_stage_props['color'];
                                            if ($previous_stage === 'Initial') $previous_badge_color = 'success';
                                        ?>
                                        <tr class="barcode-row" data-current-stage="<?php echo htmlspecialchars($current_stage); ?>">
                                            <td>
                                                <code><?php echo htmlspecialchars($row['full_barcode_name']); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $current_badge_color; ?>">
                                                    <?php echo htmlspecialchars($current_stage); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($previous_stage): ?>
                                                    <span class="badge bg-<?php echo $previous_badge_color; ?>">
                                                        <?php echo htmlspecialchars($previous_stage); ?>
                                                    </span>
                                                    <?php if ($row['previous_stage_time']): ?>
                                                        <br><small class="text-muted">
                                                            <?php echo date('Y-m-d H:i', strtotime($row['previous_stage_time'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">null</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['piece_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['size'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['category'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['chef'] ?? '-'); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo $row['last_update'] ? date('Y-m-d H:i', strtotime($row['last_update'])) : '-'; ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- No Results -->
                <div class="text-center py-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Results Found</h4>
                            <p class="text-muted">No barcodes found for OF Number: <strong><?php echo htmlspecialchars($filter_of); ?></strong> on date: <strong><?php echo htmlspecialchars($filter_date); ?></strong></p>
                            <a href="?" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Search Again
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Welcome Message -->
            <div class="text-center py-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-barcode fa-4x text-primary mb-3"></i>
                        <h4>Welcome to OF Barcode Analyzer</h4>
                        <p class="text-muted">Enter an OF number and select a date above to analyze barcode distribution and stages for that specific day.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add hover effects using jQuery
            $('.stage-card').hover(
                function() {
                    $(this).addClass('shadow-lg').css('transform', 'translateY(-5px)');
                },
                function() {
                    $(this).removeClass('shadow-lg').css('transform', 'translateY(0)');
                }
            );
            
            // Add click functionality to stage cards for filtering
            $('.stage-card').click(function() {
                var stageName = $(this).data('stage');
                
                // Toggle visibility of table rows based on stage
                if ($(this).hasClass('active-filter')) {
                    // Remove filter - show all rows
                    $('.barcode-row').show();
                    $('.stage-card').removeClass('active-filter bg-secondary').addClass('bg-white');
                } else {
                    // Apply filter - show only rows with matching stage
                    $('.stage-card').removeClass('active-filter bg-secondary').addClass('bg-white');
                    $(this).addClass('active-filter bg-secondary').removeClass('bg-white');
                    
                    $('.barcode-row').hide();
                    $('.barcode-row[data-current-stage="' + stageName + '"]').show();
                }
            });
            
            // Add transition effects
            $('.stage-card').css('transition', 'all 0.2s ease-in-out');
        });
    </script>
</body>
</html>