<?php
session_start();
$current_view = 'tracking.php';
$host = "localhost";
$dbname = "jgr";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $filter_of = isset($_GET['of_number']) ? trim($_GET['of_number']) : '';
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
        // Get barcode details for the OF with previous stage information (all dates)
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
            prev_h.stage as previous_stage,
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
            )
        ) prev_h ON prev_h.full_barcode_name = b.full_barcode_name AND prev_h.rn = 1
        WHERE b.of_number = :of_number
        AND (h.full_barcode_name IS NOT NULL OR b.full_barcode_name IS NOT NULL)
        AND (h.action_type IN ('INSERT', 'UPDATE') OR h.action_type IS NULL)
        AND (h.last_update = (
            SELECT MAX(h2.last_update)
            FROM jgr_barcodes_history h2
            WHERE h2.full_barcode_name = h.full_barcode_name
        ) OR h.last_update IS NULL)
        ORDER BY b.last_update DESC, b.stage, b.piece_name, b.size, b.category
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':of_number' => $filter_of
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
    <title>OF Number Progress Path</title>
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
    <div class="bg-gradient bg-primary text-white py-2">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <h1 class="text-center mb-4">
                        OF Number Progress Path
                    </h1>
                    <form method="GET" class="row g-3 justify-content-center">
                        <div class="col-md-6">
                            <input 
                                type="text" 
                                name="of_number" 
                                class="form-control form-control-lg" 
                                placeholder="Enter OF Number..." 
                                value="<?php echo htmlspecialchars($filter_of); ?>"
                                required
                            >
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-light btn-lg w-100">
                                <i class="fas fa-search"></i> Analyze
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="clearSearch" class="btn btn-outline-light btn-lg w-100">
                                <i class="fas fa-times"></i> Clear
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
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-calendar-alt"></i> Showing all historical data
                                    </p>
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

                <!-- Filter Tool -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Options</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label for="stageFilter" class="form-label">Filter by Current Stage:</label>
                                <select id="stageFilter" class="form-select">
                                    <option value="">All Stages</option>
                                    <?php foreach ($stage_totals as $stage => $count): ?>
                                        <option value="<?php echo htmlspecialchars($stage); ?>">
                                            <?php echo htmlspecialchars($stage); ?> (<?php echo $count; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="barcodeFilter" class="form-label">Filter by Barcode Type:</label>
                                <select id="barcodeFilter" class="form-select">
                                    <option value="">All Barcodes</option>
                                    <option value="x-ending">Barcodes ending with X</option>
                                    <option value="non-x-ending">Barcodes not ending with X</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="searchBarcode" class="form-label">Search Barcode:</label>
                                <input type="text" id="searchBarcode" class="form-control" placeholder="Search barcode...">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="dateRangeFilter" class="form-label">Date Range Filter:</label>
                                <select id="dateRangeFilter" class="form-select">
                                    <option value="">All Dates</option>
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="last7days">Last 7 Days</option>
                                    <option value="last30days">Last 30 Days</option>
                                    <option value="thismonth">This Month</option>
                                    <option value="lastmonth">Last Month</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label for="specificDate" class="form-label">Specific Date:</label>
                                <input type="date" id="specificDate" class="form-control">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button id="clearFilters" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear All Filters
                                </button>
                                <span id="filterStatus" class="ms-3 text-muted"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Detailed Barcode Information</h5>
                        <span id="visibleCount" class="badge bg-primary">Showing <?php echo $total_barcodes; ?> of <?php echo $total_barcodes; ?> records</span>
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
                                <tbody id="barcodeTableBody">
                                    <?php foreach ($of_data as $row): ?>
                                        <?php 
                                            $current_stage = $row['current_stage'];
                                            $previous_stage = $row['previous_stage'];
                                            $barcode = $row['full_barcode_name'];
                                            $ends_with_x = preg_match('/-X\d+$/', $barcode);
                                            
                                            // Determine previous stage display
                                            $previous_stage_display = '';
                                            if ($previous_stage) {
                                                $previous_stage_display = $previous_stage;
                                            } else if ($current_stage === 'No Stage') {
                                                $previous_stage_display = ''; // Leave blank for "No Stage"
                                            } else {
                                                $previous_stage_display = 'Not present'; // Default for other cases
                                            }
                                            
                                            $current_stage_props = isset($display_stages[$current_stage]) ? $display_stages[$current_stage] : ['color' => 'secondary'];
                                            $previous_stage_props = isset($display_stages[$previous_stage]) ? $display_stages[$previous_stage] : ['color' => 'light'];
                                            $current_badge_color = $current_stage_props['color'];
                                            $previous_badge_color = $previous_stage_props['color'];
                                            if ($previous_stage_display === 'Not present') $previous_badge_color = 'warning';
                                        ?>
                                        <tr class="barcode-row" 
                                            data-current-stage="<?php echo htmlspecialchars($current_stage); ?>"
                                            data-barcode="<?php echo htmlspecialchars($barcode); ?>"
                                            data-last-update="<?php echo $row['last_update'] ? date('Y-m-d', strtotime($row['last_update'])) : ''; ?>">
                                            <td>
                                                <code class="barcode-text">
                                                    <?php echo htmlspecialchars($barcode); ?>
                                                    <?php if ($ends_with_x): ?>
                                                        <i class="fas fa-times-circle text-warning ms-1" title="Ends with X"></i>
                                                    <?php endif; ?>
                                                </code>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $current_badge_color; ?>">
                                                    <?php echo htmlspecialchars($current_stage); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($previous_stage_display): ?>
                                                    <span class="badge bg-<?php echo $previous_badge_color; ?>">
                                                        <?php echo htmlspecialchars($previous_stage_display); ?>
                                                    </span>
                                                    <?php if ($row['previous_stage_time']): ?>
                                                        <br><small class="text-muted">
                                                            <?php echo date('Y-m-d H:i', strtotime($row['previous_stage_time'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <!-- Leave blank -->
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
                            <p class="text-muted">No barcodes found for OF Number: <strong><?php echo htmlspecialchars($filter_of); ?></strong></p>
                            <a href="?" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Search Again
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Welcome Message -->
            <div class="text-center">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-muted">Enter an OF number above to analyze barcode distribution and stages across all historical data.</p>
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
            const totalBarcodes = <?php echo $total_barcodes; ?>;
            
            // Clear search functionality
            $('#clearSearch').click(function() {
                // Clear the OF input field
                $('input[name="of_number"]').val('');
                // Submit the form to reset the page
                window.location.href = 'tracking.php';
            });
            
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
                
                // Update the stage filter dropdown
                $('#stageFilter').val(stageName);
                applyFilters();
            });
            
            // Date helper functions
            function getDateRange(rangeType) {
                const today = new Date();
                const todayStr = today.toISOString().split('T')[0];
                
                switch(rangeType) {
                    case 'today':
                        return { from: todayStr, to: todayStr };
                    case 'yesterday':
                        const yesterday = new Date(today);
                        yesterday.setDate(yesterday.getDate() - 1);
                        const yesterdayStr = yesterday.toISOString().split('T')[0];
                        return { from: yesterdayStr, to: yesterdayStr };
                    case 'last7days':
                        const last7days = new Date(today);
                        last7days.setDate(last7days.getDate() - 7);
                        return { from: last7days.toISOString().split('T')[0], to: todayStr };
                    case 'last30days':
                        const last30days = new Date(today);
                        last30days.setDate(last30days.getDate() - 30);
                        return { from: last30days.toISOString().split('T')[0], to: todayStr };
                    case 'thismonth':
                        const thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                        return { from: thisMonthStart.toISOString().split('T')[0], to: todayStr };
                    case 'lastmonth':
                        const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                        return { 
                            from: lastMonthStart.toISOString().split('T')[0], 
                            to: lastMonthEnd.toISOString().split('T')[0] 
                        };
                    default:
                        return null;
                }
            }
            
            function isDateInRange(dateStr, fromDate, toDate) {
                if (!dateStr) return false;
                const date = new Date(dateStr);
                const from = new Date(fromDate);
                const to = new Date(toDate);
                to.setHours(23, 59, 59, 999); // Include the entire end date
                return date >= from && date <= to;
            }

            // Filter functionality
            function applyFilters() {
                const stageFilter = $('#stageFilter').val();
                const barcodeFilter = $('#barcodeFilter').val();
                const searchText = $('#searchBarcode').val().toLowerCase();
                const dateRangeFilter = $('#dateRangeFilter').val();
                const specificDate = $('#specificDate').val();
                
                let visibleCount = 0;
                let activeFilters = [];
                
                // Get date range
                let dateRange = null;
                if (specificDate) {
                    // If specific date is selected, use it
                    dateRange = { from: specificDate, to: specificDate };
                } else if (dateRangeFilter) {
                    dateRange = getDateRange(dateRangeFilter);
                }
                
                $('.barcode-row').each(function() {
                    const row = $(this);
                    const currentStage = row.data('current-stage');
                    const barcode = row.data('barcode');
                    const barcodeSearch = barcode.toLowerCase();
                    const lastUpdate = row.data('last-update');
                    
                    // Check if barcode contains -X followed by numbers
                    const endsWithX = /-X\d+$/.test(barcode);
                    
                    let showRow = true;
                    
                    // Stage filter
                    if (stageFilter && currentStage !== stageFilter) {
                        showRow = false;
                    }
                    
                    // Barcode type filter
                    if (barcodeFilter === 'x-ending' && !endsWithX) {
                        showRow = false;
                    } else if (barcodeFilter === 'non-x-ending' && endsWithX) {
                        showRow = false;
                    }
                    
                    // Search filter
                    if (searchText && !barcodeSearch.includes(searchText)) {
                        showRow = false;
                    }
                    
                    // Date filter
                    if (dateRange && !isDateInRange(lastUpdate, dateRange.from, dateRange.to)) {
                        showRow = false;
                    }
                    
                    if (showRow) {
                        row.show();
                        visibleCount++;
                    } else {
                        row.hide();
                    }
                });
                
                // Update visible count
                $('#visibleCount').text(`Showing ${visibleCount} of ${totalBarcodes} records`);
                
                // Update filter status
                if (stageFilter) activeFilters.push(`Stage: ${stageFilter}`);
                if (barcodeFilter === 'x-ending') activeFilters.push('Barcodes ending with X');
                if (barcodeFilter === 'non-x-ending') activeFilters.push('Barcodes not ending with X');
                if (searchText) activeFilters.push(`Search: "${searchText}"`);
                if (specificDate) {
                    activeFilters.push(`Specific Date: ${specificDate}`);
                } else if (dateRange) {
                    const rangeLabels = {
                        'today': 'Today',
                        'yesterday': 'Yesterday',
                        'last7days': 'Last 7 Days',
                        'last30days': 'Last 30 Days',
                        'thismonth': 'This Month',
                        'lastmonth': 'Last Month'
                    };
                    activeFilters.push(`Date: ${rangeLabels[dateRangeFilter]}`);
                }
                
                if (activeFilters.length > 0) {
                    $('#filterStatus').html(`<i class="fas fa-filter"></i> Active filters: ${activeFilters.join(', ')}`);
                } else {
                    $('#filterStatus').text('');
                }
                
                // Update stage cards highlighting
                $('.stage-card').removeClass('active-filter bg-secondary text-white').addClass('bg-white');
                if (stageFilter) {
                    $(`.stage-card[data-stage="${stageFilter}"]`).addClass('active-filter bg-secondary text-white').removeClass('bg-white');
                }
            }
            
            // Event listeners for filters
            $('#stageFilter, #barcodeFilter, #dateRangeFilter').change(function() {
                // Clear specific date when date range is selected
                if ($(this).attr('id') === 'dateRangeFilter' && $(this).val()) {
                    $('#specificDate').val('');
                }
                applyFilters();
            });
            
            $('#searchBarcode').on('input', function() {
                applyFilters();
            });
            
            $('#specificDate').change(function() {
                // Clear date range when specific date is selected
                if ($(this).val()) {
                    $('#dateRangeFilter').val('');
                }
                applyFilters();
            });
            
            // Clear all filters
            $('#clearFilters').click(function() {
                $('#stageFilter').val('');
                $('#barcodeFilter').val('');
                $('#searchBarcode').val('');
                $('#dateRangeFilter').val('');
                $('#specificDate').val('');
                applyFilters();
            });
            
            // Event listeners for filters
            $('#stageFilter, #barcodeFilter, #dateRangeFilter').change(function() {
                if ($(this).attr('id') === 'dateRangeFilter') {
                    const selectedValue = $(this).val();
                    if (selectedValue === 'custom') {
                        $('#customDateRow').show();
                    } else {
                        $('#customDateRow').hide();
                        $('#dateFrom').val('');
                        $('#dateTo').val('');
                    }
                }
                applyFilters();
            });
            
            $('#searchBarcode').on('input', function() {
                applyFilters();
            });
            
            $('#dateFrom, #dateTo').change(function() {
                applyFilters();
            });
            
            // Clear all filters
            $('#clearFilters').click(function() {
                $('#stageFilter').val('');
                $('#barcodeFilter').val('');
                $('#searchBarcode').val('');
                $('#dateRangeFilter').val('');
                $('#dateFrom').val('');
                $('#dateTo').val('');
                $('#customDateRow').hide();
                applyFilters();
            });
            
            // Add transition effects
            $('.stage-card').css('transition', 'all 0.2s ease-in-out');
        });
    </script>
</body>
</html>