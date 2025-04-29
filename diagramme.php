<?php
// Initialize database connection with the same parameters as your other files
$host = 'localhost';
$db_name = 'jgr';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

try {
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize date and search parameters
$current_date = date("Y-m-d");

// Common search parameters
$of_number = $_GET['of_number'] ?? '';
$size = $_GET['size'] ?? '';
$category = $_GET['category'] ?? '';
$p_name = $_GET['piece_name'] ?? '';
$date = $_GET['date'] ?? $current_date;

// Define stage options - add 'No Stage' for NULL values
$stage_options = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_fini', 'Exported', 'No Stage'];

// Query to get barcode counts by stage - modified to handle NULL values
$query = "SELECT 
            COALESCE(stage, 'No Stage') as stage, 
            COUNT(*) as count
          FROM barcodes 
          WHERE 1=1";

$params = [];

if (!empty($of_number)) {
    $query .= " AND of_number LIKE ?";
    $params[] = "%$of_number%";
}

if (!empty($size)) {
    $query .= " AND size = ?";
    $params[] = $size;
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if (!empty($p_name)) {
    $query .= " AND piece_name = ?";
    $params[] = $p_name;
}

if (!empty($date)) {
    $query .= " AND DATE(last_update) = ?";
    $params[] = $date;
}

$query .= " GROUP BY COALESCE(stage, 'No Stage') ORDER BY FIELD(COALESCE(stage, 'No Stage'), 'Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_fini', 'Exported', 'No Stage')";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$stage_counts = $stmt->fetchAll();

// Get total count
$total_query = "SELECT COUNT(*) as total FROM barcodes WHERE 1=1";
$total_params = [];

if (!empty($of_number)) {
    $total_query .= " AND of_number LIKE ?";
    $total_params[] = "%$of_number%";
}

if (!empty($size)) {
    $total_query .= " AND size = ?";
    $total_params[] = $size;
}

if (!empty($category)) {
    $total_query .= " AND category = ?";
    $total_params[] = $category;
}

if (!empty($p_name)) {
    $total_query .= " AND piece_name = ?";
    $total_params[] = $p_name;
}

if (!empty($date)) {
    $total_query .= " AND DATE(last_update) = ?";
    $total_params[] = $date;
}

$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($total_params);
$total_row = $total_stmt->fetch();
$total_count = $total_row['total'];

// Get distinct categories for dropdown
$cat_query = "SELECT DISTINCT category FROM barcodes ORDER BY category";
$cat_stmt = $pdo->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();

// Get distinct piece names for dropdown
$piece_query = "SELECT DISTINCT piece_name FROM barcodes ORDER BY piece_name";
$piece_stmt = $pdo->prepare($piece_query);
$piece_stmt->execute();
$piece_names = $piece_stmt->fetchAll();

// Get distinct sizes for dropdown
$size_query = "SELECT DISTINCT size FROM barcodes ORDER BY size";
$size_stmt = $pdo->prepare($size_query);
$size_stmt->execute();
$sizes = $size_stmt->fetchAll();

// Function to determine which emoji to show based on count
function getEmoji($count) {
    if ($count > 900) {
        return '<i class="fas fa-smile text-success fa-2x"></i>'; // Happy green face for > 900
    } elseif ($count >= 700 && $count <= 900) {
        return '<i class="fas fa-meh text-warning fa-2x"></i>'; // Neutral face for 700-900
    } else {
        return '<i class="fas fa-angry text-danger fa-2x"></i>'; // Angry face for < 700
    }
}

// Function to get badge color based on count
function getBadgeColor($count) {
    if ($count > 900) {
        return 'bg-success';
    } elseif ($count >= 700 && $count <= 900) {
        return 'bg-warning text-dark';
    } else {
        return 'bg-danger';
    }
}

// Get colors for the chart
function getChartColors($stage_counts) {
    $colors = [];
    foreach ($stage_counts as $stage) {
        $count = $stage['count'];
        if ($count > 900) {
            $colors[] = '#28a745'; // Green for > 900
        } elseif ($count >= 700 && $count <= 900) {
            $colors[] = '#ffc107'; // Yellow for 700-900
        } else {
            $colors[] = '#dc3545'; // Red for < 700
        }
    }
    return $colors;
}

// Get stage emoji for display
function getStageEmoji($stage) {
    $emojis = [
        'Coupe' => '✂️',
        'V1' => '🧵',
        'V2' => '🧶',
        'V3' => '👕',
        'Pantalon' => '👖',
        'Repassage' => '🔥',
        'P_fini' => '✅',
        'Exported' => '📦',
        'No Stage' => '❓'  // Added emoji for missing stage
    ];
    
    return $emojis[$stage] ?? '📊';
}

// Convert stage data to chart format
$chart_labels = [];
$chart_data = [];
$chart_colors = [];

foreach ($stage_counts as $stage) {
    $chart_labels[] = $stage['stage'];
    $chart_data[] = $stage['count'];
    
    // Set color based on count
    if ($stage['count'] > 900) {
        $chart_colors[] = '#28a745'; // Green
    } elseif ($stage['count'] >= 700 && $stage['count'] <= 900) {
        $chart_colors[] = '#ffc107'; // Yellow
    } else {
        $chart_colors[] = '#dc3545'; // Red
    }
}

// For empty datasets
if (empty($chart_labels)) {
    $chart_labels = ['No Data'];
    $chart_data = [0];
    $chart_colors = ['#6c757d']; // Grey
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Status Dashboard</title>
    <link rel="stylesheet" href="assets/app.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php include 'includes/head.php'; ?>
   
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
    <div class="container-fluid py-4">
    <?php include 'includes/header.php'; ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm dashboard-header">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="fw-bold mb-0 text-primary">
                                <i class="fas fa-chart-line me-2"></i> Production Status Dashboard
                            </h3>
                            <span class="badge bg-primary rounded-pill py-2 px-3">Date: <?= htmlspecialchars($date) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Form -->
        <div class="filter-form shadow-sm mb-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label for="of_number" class="form-label text-muted">OF Number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-hashtag"></i></span>
                        <input type="text" class="form-control" id="of_number" name="of_number" 
                               value="<?php echo htmlspecialchars($of_number); ?>" placeholder="Enter OF Number">
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label for="size" class="form-label text-muted">Size</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-ruler"></i></span>
                        <input type="text" class="form-control" id="size" name="size" 
                               value="<?php echo htmlspecialchars($size); ?>" placeholder="Size">
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label for="category" class="form-label text-muted">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php $category_options = ['R', 'C', 'L', 'LL', 'CC', 'N']; ?>
                        <?php foreach($category_options as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo ($category === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label for="piece_name" class="form-label text-muted">Piece</label>
                    <select class="form-select" id="piece_name" name="piece_name">
                        <option value="">All Pieces</option>
                        <?php $piece_name_options = ['P', 'V', 'G', 'M']; ?>
                        <?php foreach($piece_name_options as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo ($p_name === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                     
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label for="date" class="form-label text-muted">Date</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-calendar"></i></span>
                        <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($date) ?>">
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-2"></i> Search
                        </button>
                        <a href="diagramme.php" class="btn btn-outline-secondary flex-fill">
                            <i class="fas fa-redo me-2"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Total Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card total-card shadow">
                    <div class="card-body py-4">
                        <div class="row align-items-center">
                            <div class="col-lg-3 col-md-6 text-center mb-3 mb-lg-0">
                                <h5 class="text-white-50 mb-1">Total Production</h5>
                                <div class="stats-value"><?= number_format($total_count) ?></div>
                                <span class="badge bg-white text-primary rounded-pill px-3 py-2">
                                    <?= htmlspecialchars($of_number ? "OF: $of_number" : "All Production") ?>
                                </span>
                            </div>
                            
                            <div class="col-lg-6 col-md-6 mb-3 mb-lg-0">
                                <div class="progress rounded-pill bg-white bg-opacity-25" style="height: 25px;">
                                    <div class="progress-bar bg-white" role="progressbar" 
                                         style="width: <?= min(100, ($total_count/1000)*100) ?>%;" 
                                         aria-valuenow="<?= $total_count ?>" aria-valuemin="0" aria-valuemax="1000">
                                        <?= round(($total_count/1000)*100) ?>%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-white-50">0</small>
                                    <small class="text-white-50">500</small>
                                    <small class="text-white-50">1000</small>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 text-center">
                                <div class="emoji-container">
                                    <?= getEmoji($total_count) ?>
                                </div>
                                <div class="mt-2 text-white-50">
                                    <?php if($total_count > 900): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-2">Excellent</span>
                                    <?php elseif($total_count >= 700): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Good</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-2">Below Target</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stage Cards -->
        <div class="row g-4 mb-4">
            <?php 
            // Convert to associative array for easier access
            $stage_data = [];
            foreach ($stage_counts as $stage) {
                $stage_data[$stage['stage']] = $stage['count'];
            }
            
            // Iterate through all possible stages
            foreach ($stage_options as $stage_name): 
                $count = $stage_data[$stage_name] ?? 0;
                
                // Special class for 'No Stage' card
                $specialClass = ($stage_name == 'No Stage') ? 'no-stage-card' : '';
            ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="stage-card <?= $specialClass ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">
                                    <span class="stage-icon"><?= getStageEmoji($stage_name) ?></span>
                                    <?= htmlspecialchars($stage_name) ?>
                                </h5>
                                <div class="emoji-container">
                                    <?= getEmoji($count) ?>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <div class="stats-value"><?= number_format($count) ?></div>
                                <span class="stage-percentage">
                                    <?= round(($count / max(1, $total_count)) * 100) ?>% of total
                                </span>
                            </div>
                            
                            <div class="progress mt-3 bg-white bg-opacity-25">
                                <div class="progress-bar bg-white" role="progressbar" 
                                     style="width: <?= min(100, ($count/1000)*100) ?>%;" 
                                     aria-valuenow="<?= $count ?>" aria-valuemin="0" aria-valuemax="1000">
                                </div>
                            </div>
                            
                            <div class="text-center mt-2">
                                <span class="badge <?= getBadgeColor($count) ?> rounded-pill px-3 py-2">
                                    <?php if($count > 900): ?>
                                        Excellent
                                    <?php elseif($count >= 700): ?>
                                        Good
                                    <?php else: ?>
                                        Below Target
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card chart-card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-chart-pie me-2"></i> Stage Distribution
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card chart-card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-chart-bar me-2"></i> Stage Comparison
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize charts when the document is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js global defaults
            Chart.defaults.font.family = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";
            Chart.defaults.font.size = 13;
            Chart.defaults.color = '#6B7280';
            Chart.defaults.plugins.tooltip.padding = 10;
            Chart.defaults.plugins.tooltip.titleColor = '#374151';
            Chart.defaults.plugins.tooltip.bodyColor = '#374151';
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(255, 255, 255, 0.9)';
            Chart.defaults.plugins.tooltip.borderColor = 'rgba(219, 234, 254, 1)';
            Chart.defaults.plugins.tooltip.borderWidth = 1;
            Chart.defaults.plugins.tooltip.displayColors = true;
            Chart.defaults.plugins.tooltip.boxPadding = 3;
            
            // Pie Chart with better styling
            var pieCtx = document.getElementById('pieChart').getContext('2d');
            var pieChart = new Chart(pieCtx, {
                type: 'doughnut', // Changed to doughnut for modern look
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($chart_data) ?>,
                        backgroundColor: <?= json_encode($chart_colors) ?>,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.raw || 0;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Bar Chart with better styling
            var barCtx = document.getElementById('barChart').getContext('2d');
            var barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        label: 'Number of Barcodes',
                        data: <?= json_encode($chart_data) ?>,
                        backgroundColor: <?= json_encode($chart_colors) ?>,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        borderRadius: 8,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(226, 232, 240, 0.6)'
                            },
                            ticks: {
                                precision: 0,
                                font: {
                                    weight: '500'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    weight: '500'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    var value = context.raw || 0;
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>