<?php
require_once 'productionset.php';
$production_summary = getProductionSummary(
    $pdo,
    $filter_date ?? (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d')),
    $filter_stage ?? (isset($_GET['stage']) ? $_GET['stage'] : null),
    $filter_piece_name ?? (isset($_GET['piece_name']) ? $_GET['piece_name'] : null)
);

$grand_totals = [
    'total_count' => 0,
    'total_stage_quantity' => 0,
    'total_main_quantity' => 0,
    'manque' => 0,
    'suv_plus' => 0,
];
if (!empty($production_summary) && is_array($production_summary)) {
    foreach ($production_summary as $item) {
        $grand_totals['total_count'] += (float)($item['total_count'] ?? 0);
        $grand_totals['total_stage_quantity'] += (float)($item['total_stage_quantity'] ?? 0);
        $grand_totals['total_main_quantity'] += (float)($item['total_main_quantity'] ?? 0);
        $grand_totals['manque'] += (float)($item['manque'] ?? 0);
        $grand_totals['suv_plus'] += (float)($item['suv_plus'] ?? 0);
    }
}

$_SESSION['production_summary'] = $production_summary;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Stage</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>

    .dip{
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-content: flex-start;
        justify-content: space-between;
    }
    </style>
</head>
<body class="bg-light">
<div id="loadingOverlay">
        <div class="d-flex flex-column bg-white align-items-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
            <div class="txt">
                <h5 class="mt-3 mb-2 text-center text-dark" style="font-size: 1.25rem;">
                    Processing Your Request...
                </h5>
                <p class="text-muted text-center" >
                    This may take a moment depending on data size
                </p>
            </div>
        </div>
    </div>

<?php include 'includes/sidebar.php'; ?>
    <div class="main-content position-relative">
        <div class="sticky-top bg-white border-bottom shadow-sm">
            <?php include 'includes/header.php'; ?>
        </div>

        <div class="container-fluid">
        
        <div class="row mb-0">
            <div class="col-12">
                <div class="card-body">
                    <h1 class="mb-0" style="font-size: 18px;">Production Stage</h1>

                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning d-flex align-items-center mt-2 mb-2" role="alert">
                                <i class="bi bi-database-fill-lock dbico"></i>
                                <div>
                                    <strong>Data Retention Notice:</strong> Production history records are automatically archived and permanently deleted 30 days after creation. Once purged, this data cannot be retrieved or reconstructed through any means.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-section">
                        <form method="GET" class="row g-2 align-items-center" id="filterForm">
                            <div class="col-auto">
                                <label for="date" class="form-label mb-0">Select Date:</label>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="date"
                                        name="date"
                                        value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>"
                                    >
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filter</button>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>"  class="btn btn-outline-dark"><i class="fas fa-broom"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!isset($_GET['date']) || empty($_GET['date'])): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i> Please select a date to view production data.</h5>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Production Date: <?php echo htmlspecialchars(date('d/m/Y', strtotime($_GET['date']))); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php
                            $filter_date = $_GET['date'];
                            $has_data = false;

                            if (isset($daily_stage_stats) && is_array($daily_stage_stats)) {
                                foreach ($daily_stage_stats as $stage => $stats) {
                                    if ($stats['current'] > 0 || $stats['in'] > 0 || $stats['out'] > 0) {
                                        $has_data = true;
                                        break;
                                    }
                                }
                            }

                            if (!$has_data):
                            ?>
                                <div class="no-data-message">
                                    <div class="alert alert-danger">
                                        <h5><i class="fa-solid fa-triangle-exclamation"></i> No production data available for <?php echo htmlspecialchars($filter_date); ?></h5>
                                        <p>Try selecting a different date.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="row g-4">
                                    <?php
                                    if (isset($display_stages) && is_array($display_stages)):
                                        foreach ($display_stages as $stage => $properties):
                                            $current = isset($daily_stage_stats[$stage]) ? $daily_stage_stats[$stage]['current'] : 0;
                                            $target = isset($targets[$stage]) ? $targets[$stage] : 100;

                                            $items_in = isset($daily_stage_stats[$stage]) ? $daily_stage_stats[$stage]['in'] : 0;
                                            $items_out = isset($daily_stage_stats[$stage]) ? $daily_stage_stats[$stage]['out'] : 0;

                                            $in_percent = ($total_count ?? 0) > 0 ? round(($items_in / $total_count) * 100) : 0;
                                            $out_percent = ($total_count ?? 0) > 0 ? round(($items_out / $total_count) * 100) : 0;
                                            $current_percent = ($total_count ?? 0) > 0 ? round(($current / $total_count) * 100) : 0;

                                            $from_stages = $daily_stage_stats[$stage]['from_stages'] ?? [];
                                            $to_stages = $daily_stage_stats[$stage]['to_stages'] ?? [];

                                            if ($current > 900) {
                                                $badgeClass = "bg-success";
                                                $moodEmoji = '<i class="fas fa-smile text-success fa-2x"></i>';
                                            } elseif ($current >= 700) {
                                                $badgeClass = "bg-warning";
                                                $moodEmoji = '<i class="fas fa-meh text-warning fa-2x"></i>';
                                            } else {
                                                $badgeClass = "bg-danger";
                                                $moodEmoji = '<i class="fas fa-angry text-danger fa-2x"></i>';
                                            }

                                            $in_percentage = ($target > 0) ? min(100, ($items_in / $target) * 100) : 0;
                                            $out_percentage = ($target > 0) ? min(100, ($items_out / $target) * 100) : 0;
                                            $current_percentage = max(0, $in_percentage - $out_percentage);
                                    ?>
                                    <div class="col-xl-3 col-lg-4 col-md-6">
                                        <div class="card h-100 bg-<?php echo htmlspecialchars($properties['color']); ?> text-white">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h5 class="card-title mb-0">
                                                        <span class="me-2"><?php echo $properties['emoji']; ?></span>
                                                        <?php echo htmlspecialchars($stage); ?>
                                                    </h5>
                                                    <div>
                                                        <span class="fs-5"><?php echo $moodEmoji; ?></span>
                                                    </div>
                                                </div>

                                                <div class="row text-center mb-3">
                                                    <div class="col-4">
                                                        <div class="fw-bold fs-5"><?php echo number_format($items_in); ?></div>
                                                        <div class="small">In</div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="fw-bold fs-5"><?php echo number_format($items_out); ?></div>
                                                        <div class="small">Out <br><span style="font-size: 11px;">PRODUCTION</span></div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="fw-bold fs-5"><?php echo number_format($current); ?></div>
                                                        <div class="small">Current</div>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="progress bg-dark rounded-pill" style="height: 20px;">
                                                        <div class="progress-bar bg-light text-dark rounded-pill" role="progressbar"
                                                            style="width: <?= $current_percentage; ?>%;"
                                                            aria-valuenow="<?= $current_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between text-white-50 small mb-3">
                                                    <span>In: <?php echo $in_percent; ?>%</span>
                                                    <span>Out: <?php echo $out_percent; ?>%</span>
                                                    <span>Current: <?php echo $current_percent; ?>%</span>
                                                </div>

                                                <div class="text-center mt-3">
                                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-2">
                                                        <?php if($current > 900): ?>
                                                            Excellent
                                                        <?php elseif($current >= 700): ?>
                                                            Good
                                                        <?php else: ?>
                                                            Below Target
                                                        <?php endif; ?>
                                                    </span>
                                                </div>

                                                <div class="mt-3">
                                                    <button onclick="togglePanel('panel-in-<?= str_replace(' ', '_', $stage) ?>', this)"
                                                        class="btn btn-sm btn-outline-light w-100 text-start d-flex justify-content-between align-items-center">
                                                        <span>Items In Detail</span>
                                                        <i class="fas fa-chevron-down toggle-icon"></i>
                                                    </button>
                                                    <div id="panel-in-<?= str_replace(' ', '_', $stage) ?>" class="toggle-panel" style="display: none;">
                                                        <div class="pt-2">
                                                            <?php if (empty($from_stages)): ?>
                                                                <p class="text-white-50"><small>No incoming items</small></p>
                                                            <?php else: ?>
                                                                <ul class="list-group list-group-flush">
                                                                    <?php foreach ($from_stages as $from_stage => $count): ?>
                                                                        <li class="list-group-item py-1 bg-transparent text-white border-0">
                                                                            <small>
                                                                                <span class="fw-bold"><?= htmlspecialchars($from_stage) ?>:</span>
                                                                                <?= $count ?> items
                                                                            </small>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-2">
                                                    <button onclick="togglePanel('panel-out-<?= str_replace(' ', '_', $stage) ?>', this)"
                                                        class="btn btn-sm btn-outline-light w-100 text-start d-flex justify-content-between align-items-center">
                                                        <span>Items Out Detail <i>[PRODUCTION]</i></span>
                                                        <i class="fas fa-chevron-down toggle-icon"></i>
                                                    </button>
                                                    <div id="panel-out-<?= str_replace(' ', '_', $stage) ?>" class="toggle-panel" style="display: none;">
                                                        <div class="pt-2">
                                                            <?php if (empty($to_stages)): ?>
                                                                <p class="text-white-50"><small>No outgoing items</small></p>
                                                            <?php else: ?>
                                                                <ul class="list-group list-group-flush">
                                                                    <?php foreach ($to_stages as $to_stage => $count): ?>
                                                                        <li class="list-group-item py-1 bg-transparent text-white border-0">
                                                                            <small>
                                                                                <span class="fw-bold"><?= htmlspecialchars($to_stage) ?>:</span>
                                                                                <?= $count ?> items
                                                                            </small>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($has_data) && $has_data): ?>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chart-pie me-2"></i> Stage Distribution (Current)
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="pieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chart-bar me-2"></i> Stage Flow Analysis
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="barChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Production Summary Table - Only show when date is selected -->
            <div class="row mt-4" id="productionSummaryContainer">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Production Summary - Stage: <?= htmlspecialchars($filter_stage ?? 'All') ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4 dip">
                                
                                <div class="col-md-7">
                                    <form id="stageFilterForm" class="form-inline d-flex flex-row align-items-center" method="get">
                                        <?php if (!empty($filter_date)): ?>
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($filter_date) ?>">
                                        <?php endif; ?>
                                        
                                        <div class="form-group mr-2">
                                            <label for="stageFilter" class="mr-2">Stage:</label>
                                            <select name="stage" id="stageFilter" class="form-select" style="min-width: 200px;">
                                                <option value="0">All Stages</option>
                                                <option value="Coupe">Coupe</option>
                                                <option value="V1">V1</option>
                                                <option value="V2">V2</option>
                                                <option value="V3">V3</option>
                                                <option value="Pantalon">Pantalon</option>
                                                <option value="AMF">AMF</option>
                                                <option value="Repassage">Repassage</option>
                                                <option value="P_ fini">P_ fini</option>
                                                <option value="Exported">Exported</option>
                                                <?php 
                                                foreach ($available_stages as $stage): 
                                                    if (empty($stage) || $stage == 'No Stage') continue;
                                                ?>
                                                    <option value="<?= htmlspecialchars($stage) ?>" <?= ($filter_stage == $stage) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($stage) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group mr-2">
                                            <label for="pieceNameFilter" class="mr-2">Piece Name:</label>
                                            <select name="piece_name" id="pieceNameFilter" class="form-select" style="min-width: 180px;">
                                                <option value="">All Piece Names</option>
                                                <option value="V" <?= (isset($_GET['piece_name']) && $_GET['piece_name'] == 'V') ? 'selected' : '' ?>>V</option>
                                                <option value="P" <?= (isset($_GET['piece_name']) && $_GET['piece_name'] == 'P') ? 'selected' : '' ?>>P</option>
                                                <option value="G" <?= (isset($_GET['piece_name']) && $_GET['piece_name'] == 'G') ? 'selected' : '' ?>>G</option>
                                                <option value="M" <?= (isset($_GET['piece_name']) && $_GET['piece_name'] == 'M') ? 'selected' : '' ?>>M</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" id="applyFilterBtn" class="btn btn-primary mr-2" ><i class="bi bi-funnel-fill"></i> Apply Filter</button>
                                        <a href="?<?= !empty($filter_date) ? 'date=' . htmlspecialchars($filter_date) : '' ?>" class="btn btn-outline-dark" id="resetFilterBtn" ><i class="fas fa-broom"></i> Reset</a>
                                    </form>
                                </div>

                                <div class="col-md-5">
                                    <div class="card bg-light">
                                        <div class="card-body p-2">
                                            <h5 class="card-title">Grand Totals <?= !empty($filter_stage) ? 'for Stage: ' . ($filter_stage == 'No Stage' ? '' : htmlspecialchars($filter_stage)) : '' ?></h5>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Total Barcodes:</strong> <?= number_format($grand_totals['total_count']) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Total Stage Qty:</strong> <?= number_format($grand_totals['total_stage_quantity'], 2) ?></p>
                                                    <p class="mb-1"><strong>Total Main Qty:</strong> <?= number_format($grand_totals['total_main_quantity'], 2) ?></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="mb-1"><strong>Total Manque:</strong> <?= number_format($grand_totals['manque'], 2) ?></p>
                                                    <p class="mb-1"><strong>Total Suv Plus:</strong> <?= number_format($grand_totals['suv_plus'], 2) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="productionSummaryTable">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>OF Number</th>
                                            <th>Size</th>
                                            <th>Category</th>
                                            <th>Piece Name</th>
                                            <th>Chef</th>
                                            <th>Stage</th>
                                            <th>Total Count</th>
                                            <th>Total Stage Quantity</th>
                                            <th>Total Main Quantity</th>
                                            <th>Solped Client</th>
                                            <th>Pedido Client</th>
                                            <th>Color Tissus</th>
                                            <th>Manque</th>
                                            <th>Suv Plus</th>
                                            <th>Latest Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($production_summary) && !empty($production_summary)): ?>
                                            <?php foreach ($production_summary as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['of_number'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['size'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['p_name'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['chef'] ?? '') ?></td>
                                                    <td><span class="badge bg-primary text-white border border-primary rounded-pill px-3 py-2"><?= $item['stage'] == 'No Stage' ? '' : htmlspecialchars($item['stage'] ?? '') ?></span></td>
                                                    <td><span class="badge bg-primary text-white border border-primary rounded-pill px-3 py-2"><?= htmlspecialchars($item['total_count'] ?? 0) ?></span></td>
                                                    <td><?= htmlspecialchars($item['total_stage_quantity'] ?? 0) ?></td>
                                                    <td><?= htmlspecialchars($item['total_main_quantity'] ?? 0) ?></td>
                                                    <td><?= htmlspecialchars($item['solped_client'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['pedido_client'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['color_tissus'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['manque'] ?? 0) ?></td>
                                                    <td><?= htmlspecialchars($item['suv_plus'] ?? 0) ?></td>
                                                    <td><?php 
                                                        if (!empty($item['latest_update'])) {
                                                            echo htmlspecialchars(date('Y-m-d H:i', strtotime($item['latest_update'])));
                                                        } else {
                                                            echo '';
                                                        }
                                                    ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="15" class="text-center">No production summary available for the selected filters.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>            
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        <?php if (isset($has_data) && $has_data): ?>
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($chart_data['labels']); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_data['current']); ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
                        '#e74a3b', '#858796', '#5a5c69', '#3a3b45'
                    ],
                    hoverBorderColor: "rgba(255, 255, 255, 1)",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_data['labels']); ?>,
                datasets: [
                    {
                        label: 'Items In',
                        data: <?php echo json_encode($chart_data['in']); ?>,
                        backgroundColor: '#e74a3b', // Changed to red
                    },
                    {
                        label: 'Items Out {PRODUCTION}',
                        data: <?php echo json_encode($chart_data['out']); ?>,
                        backgroundColor: '#1cc88a', // Changed to green
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: false,
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('stageFilterForm');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const applyFilterBtn = document.getElementById('applyFilterBtn');
        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                loadingOverlay.style.display = 'flex';
            });
        }
        if (applyFilterBtn) {
            applyFilterBtn.addEventListener('click', function() {
                loadingOverlay.style.display = 'flex';
            });
        }
        const resetFilterBtn = document.getElementById('resetFilterBtn');
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', function() {
                loadingOverlay.style.display = 'flex';
            });
        }
    });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filterForm');
            const dateInput = document.getElementById('date');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const resetButton = document.querySelector('a.btn-outline-dark');

            function showLoading() {
                loadingOverlay.style.display = 'flex';
            }

            dateInput.addEventListener('change', function() {
                showLoading();
                filterForm.submit();
            });

            filterForm.addEventListener('submit', showLoading);

            resetButton.addEventListener('click', function(e) {
                showLoading();
            });

            window.addEventListener('load', () => {
                loadingOverlay.style.display = 'none';
            });
            
            const productionNavLink = document.getElementById('productionNavLink');
            if (productionNavLink) {
                productionNavLink.addEventListener('click', showLoading);
            }
        });
    </script>
    <script>
        function togglePanel(panelId, buttonElement) {
            var panel = document.getElementById(panelId);
            var icon = buttonElement.querySelector('.toggle-icon');
            
            if (panel.style.display === 'none') {
                panel.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                panel.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html>