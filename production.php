<?php
require_once 'productionset.php';
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
    <!-- Moved Chart.js script after Bootstrap to avoid potential conflicts -->
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

                                        <!-- Items In Detail -->
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

                                        <!-- Items Out Detail -->
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
<?php endif; ?>

        
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
        <?php endif; ?>
        
        <!-- New Production Details Table -->
            <div class="row mt-4 mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-table me-2"></i> Production Details</h5>
                            <button class="btn btn-success btn-sm" id="exportToExcel">
                                <i class="fas fa-file-excel me-1"></i> Export to Excel
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="productionTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>QF Number</th>
                                            <th>Size</th>
                                            <th>Category</th>
                                            <th>Piece Name</th>
                                            <th>Chef</th>
                                            <th>Total Stage Quantity</th>
                                            <th>Total Main Quantity</th>
                                            <th>Stages</th>
                                            <th>Total Count</th>
                                            <th>Solped Client</th>
                                            <th>Pedido Client</th>
                                            <th>Color Tissus</th>
                                            <th>Main Qty</th>
                                            <th>Coupe</th>
                                            <th>Mangue</th>
                                            <th>Suv Plus</th>
                                            <th>Latest Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($production_details) && !empty($production_details)): ?>
                                            <?php foreach ($production_details as $item): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['qf_number'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['size'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['piece_name'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['chef'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['total_stage_qty'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['total_main_qty'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['stages'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['total_count'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['solped_client'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['pedido_client'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['color_tissus'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['main_qty'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['coupe'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['mangue'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['suv_plus'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['latest_update'] ?? '') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="17" class="text-center">No production details available for the selected date.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- Added missing closing div for main-content -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <!-- Add SheetJS library for Excel export -->
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
            const filterForm = document.getElementById('filterForm');
            const dateInput = document.getElementById('date');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const resetButton = document.querySelector('a.btn-outline-dark');

            function showLoading() {
                loadingOverlay.classList.remove('d-none');
                loadingOverlay.classList.add('d-flex');
            }

            dateInput.addEventListener('change', function() {
                showLoading();
                filterForm.submit();
            });

            filterForm.addEventListener('submit', showLoading);

            resetButton.addEventListener('click', function(e) {
                showLoading();
                setTimeout(() => true, 50);
            });

            window.addEventListener('load', () => {
                loadingOverlay.classList.add('d-none');
                loadingOverlay.classList.remove('d-flex');
            });
            const productionNavLink = document.getElementById('productionNavLink');
            if (productionNavLink) {
                productionNavLink.addEventListener('click', showLoading);
            }
            function showLoading() {
                document.getElementById('loadingOverlay').classList.remove('d-none');
                document.getElementById('loadingOverlay').classList.add('d-flex');
            }
            
            // Excel Export Functionality
            document.getElementById('exportToExcel').addEventListener('click', function() {
                const table = document.getElementById('productionTable');
                const wb = XLSX.utils.table_to_book(table, {sheet: "Production Data"});
                const date = document.getElementById('date').value || 'all_dates';
                XLSX.writeFile(wb, `production_report_${date}.xlsx`);
            });
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