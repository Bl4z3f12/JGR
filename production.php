<?php
session_start();
require_once 'productionset.php';
$production_summary = getProductionSummary(
    $pdo, 
    $filter_date, 
    $filter_stage, 
    $filter_piece_name, 
    isset($_GET['of_number']) ? $_GET['of_number'] : null
);

// Get available OF numbers
$available_of_numbers = getAvailableOFNumbers($pdo, $filter_date);

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
    .dip {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-start;
    }

    .filter-section {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-width: 200px;
        flex: 1;
    }

    .filter-group label {
        font-weight: 500;
        margin-bottom: 0.25rem;
        color: #495057;
    }

    .filter-actions {
        display: flex;
        gap: 0.5rem;
        align-items: flex-end;
    }

    .stats-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    @media (max-width: 768px) {
        .dip {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }

        .filter-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .stats-card {
            width: 100%;
            margin-top: 1rem;
        }
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
                            <div class="alert alert-danger d-flex align-items-center mt-2 mb-2" role="alert">
                                <i class="bi bi-database-fill-lock dbico"></i>
                                <div>
                                    <strong>Data Retention:</strong> Production history records are automatically archived and permanently deleted 30 days after creation. Once purged, this data cannot be retrieved or reconstructed through any means.
                                </div>
                            </div>
                            <div class="alert alert-primary d-flex align-items-center mt-2 mb-2" role="alert">
                                <i class="fa-solid fa-arrows-left-right-to-line dbico"></i>
                                <div>
                                    <strong>System limitations:</strong> The system does not track <strong style="text-decoration: underline; text-transform: capitalize; font-style: italic;">sur mesure</strong> items, and they are not included in the production statistics.
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
                                        <h5><i class="fa-solid fa-triangle-exclamation"></i>Oops! No production data available for <?php echo htmlspecialchars($filter_date); ?></h5>
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
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Production Summary</h4>
                            <div class="d-flex align-items-center gap-3">
                                <?php if (!empty($filter_stage)): ?>
                                    <span class="badge bg-light text-dark">
                                        Stage: <?= htmlspecialchars($filter_stage) ?>
                                    </span>
                                <?php endif; ?>
                                <?php
                                $total_current = 0;
                                if (isset($daily_stage_stats) && is_array($daily_stage_stats)) {
                                    foreach ($daily_stage_stats as $stats) {
                                        $total_current += $stats['current'];
                                    }
                                }
                                ?>
                                <span class="badge bg-light text-dark">
                                    Total Stage Qty: <?= number_format($total_current) ?>
                                </span>
                                <?php if (isset($production_summary) && !empty($production_summary)): ?>
                                    <a href="export_excel.php?export=excel<?= 
                                        '&date=' . urlencode($_GET['date'] ?? '') . 
                                        '&of_number=' . urlencode($_GET['of_number'] ?? '') . 
                                        '&stage=' . urlencode($_GET['stage'] ?? '') . 
                                        '&piece_name=' . urlencode($_GET['piece_name'] ?? '') .
                                        '&current_data=1'
                                    ?>" class="btn btn-outline-success text-white btn-sm d-flex align-items-center gap-2">
                                        <i class="fas fa-file-excel"></i> Export Data
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-primary d-flex align-items-center mt-2 mb-2" role="alert">
                                        <i class="fa-solid fa-arrows-left-right-to-line dbico"></i>
                                        <div>
                                            <strong>System limitations:</strong> The system does not track <strong style="text-decoration: underline; text-transform: capitalize; font-style: italic;">sur mesure</strong> items, and they are not included in the production statistics.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <div class="card-body">
                            <div class="row mb-1 dip">
                                <div class="col-12">
                                    <form id="stageFilterForm" class="d-flex flex-wrap gap-3" method="get">
                                        <?php if (!empty($filter_date)): ?>
                                            <input type="hidden" name="date" value="<?= htmlspecialchars($filter_date) ?>">
                                        <?php endif; ?>

                                        <div class="filter-group">
                                            <label for="ofFilter">OF Number</label>
                                            <select name="of_number" id="ofFilter" class="form-select">
                                                <option value="">All OF Numbers</option>
                                                <?php foreach ($available_of_numbers as $of): ?>
                                                    <option value="<?= htmlspecialchars($of) ?>" <?= (isset($_GET['of_number']) && $_GET['of_number'] == $of) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($of) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="filter-group">
                                            <label for="stageFilter">Stage</label>
                                            <select name="stage" id="stageFilter" class="form-select">
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
                                        
                                        <div class="filter-group">
                                            <label for="pieceNameFilter">Piece Name</label>
                                            <select name="piece_name" id="pieceNameFilter" class="form-select">
                                                <option value="">All Piece Names</option>
                                                <option value="V" <?= (isset($_GET['piece_name']) && $_GET['piece_name'] == 'V') ? 'selected' : '' ?>>Vest</option>
                                                <option value="P" <?= (isset($_GET['piece_name']) && $_GET['piece_name'] == 'P') ? 'selected' : '' ?>>Pantalon</option>
                                                <option value="G" <?= (isset($_GET['piece_name']) && $_GET['piece_name'] == 'G') ? 'selected' : '' ?>>Gilet</option>
                                                <option value="M" <?= (isset($_GET['piece_name']) && $_GET['piece_name'] == 'M') ? 'selected' : '' ?>>Manteau</option>
                                            </select>
                                        </div>
                                        
                                        <div class="filter-actions">
                                            <button type="submit" id="applyFilterBtn" class="btn btn-primary">
                                                <i class="bi bi-funnel-fill"></i> Apply Filter
                                            </button>
                                            
                                            <button type="button" class="btn btn-outline-dark" id="resetFilterBtn">
                                                <i class="fas fa-broom"></i> Reset
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="productionSummaryTable">
                                    <thead class="table-primary text-center">
                                        <tr>
                                            <th>OF Number</th>
                                            <th>Size</th>
                                            <th>Category</th>
                                            <th>Piece Name</th>
                                            <th>Chef</th>
                                            <th>Stage</th>
                                            <th>Total Count</th>
                                            <th>Stage Qty</th>
                                            <th>Main Qty</th>
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
                                                <td colspan="15" class="text-center">Ooh! No production summary available for the selected filters.</td>
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
        const resetFilterBtn = document.getElementById('resetFilterBtn');

        function updateTableContent(html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Update table body
            const newTableBody = tempDiv.querySelector('#productionSummaryTable tbody');
            const currentTableBody = document.querySelector('#productionSummaryTable tbody');
            if (newTableBody && currentTableBody) {
                currentTableBody.innerHTML = newTableBody.innerHTML;
            }

            // Update header stats
            const newHeaderStats = tempDiv.querySelector('.card-header .d-flex');
            const currentHeaderStats = document.querySelector('.card-header .d-flex');
            if (newHeaderStats && currentHeaderStats) {
                currentHeaderStats.innerHTML = newHeaderStats.innerHTML;
            }

            loadingOverlay.style.display = 'none';
        }

        function calculateFilteredTotals() {
            const tableBody = document.querySelector('#productionSummaryTable tbody');
            const rows = tableBody.querySelectorAll('tr');
            let totalCount = 0;

            rows.forEach(row => {
                const countCell = row.querySelector('td:nth-child(7) .badge');
                if (countCell) {
                    totalCount += parseInt(countCell.textContent.replace(/,/g, '')) || 0;
                }
            });

            // Update the total in the header
            const totalBadge = document.querySelector('.card-header .badge:last-child');
            if (totalBadge) {
                totalBadge.innerHTML = `Total Stage Qty: ${totalCount.toLocaleString()}`;
            }
        }

        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                loadingOverlay.style.display = 'flex';
                
                // Get form data
                const formData = new FormData(filterForm);
                
                // Send AJAX request
                fetch('get_production_summary.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    updateTableContent(html);
                    calculateFilteredTotals();
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingOverlay.style.display = 'none';
                });
            });
        }

        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', function() {
                loadingOverlay.style.display = 'flex';
                
                // Reset form fields
                document.getElementById('ofFilter').value = '';
                document.getElementById('stageFilter').value = '0';
                document.getElementById('pieceNameFilter').value = '';
                
                // Get current date from the date input
                const dateInput = document.getElementById('date');
                const currentDate = dateInput.value;
                
                // Send AJAX request to reset
                fetch('get_production_summary.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        'date': currentDate,
                        'reset': 'true'
                    })
                })
                .then(response => response.text())
                .then(html => {
                    updateTableContent(html);
                    calculateFilteredTotals();
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingOverlay.style.display = 'none';
                });
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