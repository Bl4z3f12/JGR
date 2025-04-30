<?php

$current_view = 'diagramme.php';
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

// Debug query
$debug_query = $query;
$debug_params = implode(", ", $params);

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $stage_counts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

// Debug results
$debug_count = count($stage_counts);
$debug_stages = [];
foreach ($stage_counts as $stage) {
    $debug_stages[] = $stage['stage'] . ": " . $stage['count'];
}

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
    $chart_data[] = (int)$stage['count']; // Ensure values are integers
    
    // Set color based on count
    if ($stage['count'] > 900) {
        $chart_colors[] = '#28a745'; // Green
    } elseif ($stage['count'] >= 700 && $stage['count'] <= 900) {
        $chart_colors[] = '#ffc107'; // Yellow
    } else {
        $chart_colors[] = '#dc3545'; // Red
    }
}

// For empty datasets, add placeholder data
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
    <?php include 'includes/head.php'; ?>
    <!-- Ensure Font Awesome is included -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Explicitly include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <style>
        .main-content {
            overflow-y: auto;
            height: auto;
            padding-bottom: 20px;
        }
        /* Add this to ensure charts have minimum height */
        .chart-container {
            min-height: 350px;
            position: relative;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <!-- Debug Information (hidden in HTML comments) -->
        <!-- SQL Query: <?= $debug_query ?> -->
        <!-- Parameters: <?= $debug_params ?> -->
        <!-- Stages found: <?= $debug_count ?> -->
        <!-- Stage data: <?= implode(", ", $debug_stages) ?> -->
        <!-- Chart labels: <?= implode(", ", $chart_labels) ?> -->
        <!-- Chart data: <?= implode(", ", $chart_data) ?> -->
        
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Filter Form -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
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
                    </div>
                    
                    <!-- Total Card -->
                    <div class="card mb-4 border-0 shadow-sm bg-primary text-white">
                        <div class="card-body py-4">
                            <div class="row align-items-center">
                                <div class="col-lg-3 col-md-6 text-center mb-3 mb-lg-0">
                                    <h5 class="text-white-50 mb-1">Total Production</h5>
                                    <div class="display-6"><?= number_format($total_count) ?></div>
                                    <span class="badge bg-white text-primary rounded-pill px-3 py-2">
                                        <?= htmlspecialchars($of_number ? "OF: $of_number" : "All Production") ?>
                                    </span>
                                </div>
                                
                                <div class="col-lg-6 col-md-6 mb-3 mb-lg-0">
                                    <div class="progress bg-white bg-opacity-25" style="height: 25px;">
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
                                    <div class="mt-2">
                                        <?= getEmoji($total_count) ?>
                                    </div>
                                    <div class="mt-2">
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
                            
                            // Card background color based on stage
                            $bgClass = 'bg-primary';
                            if ($stage_name == 'No Stage') {
                                $bgClass = 'bg-danger';
                            }
                        ?>
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="card border-0 shadow-sm h-100 <?= $bgClass ?> text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">
                                                <span class="me-2"><?= getStageEmoji($stage_name) ?></span>
                                                <?= htmlspecialchars($stage_name) ?>
                                            </h5>
                                            <div>
                                                <?= getEmoji($count) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <div class="display-6"><?= number_format($count) ?></div>
                                            <span class="text-white-50">
                                                <?= round(($count / max(1, $total_count)) * 100) ?>% of total
                                            </span>
                                        </div>
                                        
                                        <div class="progress mt-3 bg-white bg-opacity-25">
                                            <div class="progress-bar bg-white" role="progressbar" 
                                                 style="width: <?= min(100, ($count/1000)*100) ?>%;" 
                                                 aria-valuenow="<?= $count ?>" aria-valuemin="0" aria-valuemax="1000">
                                            </div>
                                        </div>
                                        
                                        <div class="text-center mt-3">
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
                            <div class="card border-0 shadow-sm h-100">
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
                            <div class="card border-0 shadow-sm h-100">
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
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Debug information as JS variables -->
    <script>
        console.log('Chart labels:', <?= json_encode($chart_labels) ?>);
        console.log('Chart data:', <?= json_encode($chart_data) ?>);
        console.log('Chart colors:', <?= json_encode($chart_colors) ?>);
    </script>
    
    <script>
        // Initialize charts when the document is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing charts');
            
            // Chart.js global defaults
            Chart.defaults.font.family = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";
            Chart.defaults.font.size = 13;
            Chart.defaults.color = '#6B7280';
            
            // Get chart contexts - add error handling
            var pieCtx = document.getElementById('pieChart');
            var barCtx = document.getElementById('barChart');
            
            if(!pieCtx || !barCtx) {
                console.error('Could not find chart canvas elements!');
                return;
            }
            
            console.log('Canvas elements found');
            
            // Chart data
            const chartLabels = <?= json_encode($chart_labels) ?>;
            const chartData = <?= json_encode($chart_data) ?>;
            const chartColors = <?= json_encode($chart_colors) ?>;
            
            console.log('Chart data loaded:', {chartLabels, chartData, chartColors});
            
            // Verify we have data
            if(chartLabels.length === 0 || chartData.length === 0) {
                console.warn('No chart data available!');
                // Add placeholder data
                chartLabels.push('No Data');
                chartData.push(0);
                chartColors.push('#6c757d');
            }
            
            try {
                // Pie Chart
                console.log('Creating pie chart');
                var pieChart = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            data: chartData,
                            backgroundColor: chartColors,
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
                console.log('Pie chart created');
                
                // Bar Chart
                console.log('Creating bar chart');
                var barChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Number of Barcodes',
                            data: chartData,
                            backgroundColor: chartColors,
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
                                ticks: {
                                    precision: 0
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
                console.log('Bar chart created');
            } catch (error) {
                console.error('Error creating charts:', error);
            }
        });
    </script>
</body>
</html>