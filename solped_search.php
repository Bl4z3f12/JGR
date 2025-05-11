<?php
$current_view = 'solped_search.php'; // Add this line to track current page
require_once 'auth_functions.php';
requireLogin('login.php');

// Include settings file for database connection and common functions
require "scantoday_settings.php";

// Initialize search parameters
$solped_client = $_GET['solped_client'] ?? '';
$search_performed = isset($_GET['search']);
$results = [];
$grouped_results = [];

// Handle search
if ($search_performed && !empty($solped_client)) {
    $query = "SELECT 
                b.of_number, 
                b.size, 
                b.category, 
                b.piece_name,
                b.chef,
                b.stage,
                qc.solped_client,
                qc.pedido_client,
                qc.color_tissus,
                qc.principale_quantity,
                qc.quantity_coupe,
                qc.manque,
                qc.suv_plus,
                IFNULL(qc.lastupdate, b.last_update) AS latest_update
              FROM quantity_coupe qc
              LEFT JOIN barcodes b ON b.of_number = qc.of_number 
                AND b.size = qc.size 
                AND b.category = qc.category 
                AND b.piece_name = qc.piece_name
              WHERE qc.solped_client LIKE ?
              ORDER BY qc.of_number, qc.size, qc.category, qc.piece_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(["%$solped_client%"]);
    $results = $stmt->fetchAll();
    
    // Group identical records
    $grouped_data = [];
    foreach ($results as $row) {
        // Create a unique key based on all record fields
        $key = $row['of_number'] . '|' . 
               $row['size'] . '|' . 
               $row['category'] . '|' . 
               $row['piece_name'] . '|' . 
               $row['chef'] . '|' . 
               $row['stage'] . '|' . 
               $row['solped_client'] . '|' . 
               $row['pedido_client'] . '|' . 
               $row['color_tissus'] . '|' . 
               $row['principale_quantity'] . '|' . 
               $row['quantity_coupe'] . '|' . 
               $row['manque'] . '|' . 
               $row['suv_plus'] . '|' . 
               $row['latest_update'];
        
        if (!isset($grouped_data[$key])) {
            $grouped_data[$key] = [
                'data' => $row,
                'count' => 1
            ];
        } else {
            $grouped_data[$key]['count']++;
        }
    }
    
    $grouped_results = array_values($grouped_data);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Solped Client Search</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .search-container {
            max-width: 800px;
            margin: 0 auto;
        }
        /* Loading Overlay Styles */
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(11.1px);
            -webkit-backdrop-filter: blur(11.1px);    
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        #loadingOverlay .d-flex.flex-column {
            padding: 1.5rem 1rem;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(11.1px);
            -webkit-backdrop-filter: blur(11.1px);
            border-radius: 8px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.55),
                        0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin: 0 1rem;
        }

        #loadingOverlay h5 {
            font-size: 1.125rem;
            margin: 0.75rem 0 0.5rem;
            color: var(--bs-dark);
        }

        #loadingOverlay p {
            font-size: 0.875rem;
            line-height: 1.4;
            color: var(--bs-secondary-color);
        }

        .stage-badge {
            margin-right: 3px;
            display: inline-block;
        }
        .results-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .results-count {
            background-color: #f8f9fa;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            border: 1px solid #dee2e6;
            color: #495057;
        }
        .count-badge {
            font-size: 0.85rem;
            padding: 3px 8px;
            margin-left: 8px;
            vertical-align: middle;
        }
        /* Enhanced button styles */
        .search-btn {
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 15px;
            min-width: 100px;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        .search-btn:hover {
            background-color: #0b5ed7;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .reset-btn {
            text-decoration: none;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 15px;
            min-width: 100px;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        .reset-btn:hover {
            background-color: #5c636a;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .input-group-text {
            background-color: #f8f9fa;
        }
        /* More modern input field */
        .search-input {
            height: 45px;
            border: 1px solid #ced4da;
            box-shadow: inset 0 1px 2px rgba(0,0,0,.075);
        }
        .search-input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        /* Search form container */
        .search-form-container {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        .input-container {
            flex-grow: 1;
            position: relative;
        }
        .btn-container {
            display: flex;
            gap: 10px;
        }
        
        /* Card icon styling */
        .card-icon {
            font-size: 18px;
            margin-right: 5px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <h1 class="mb-4" style="font-size: 18px;">Solped Client Search</h1>
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- Enhanced Search Form -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-search card-icon"></i>Search by Solped Client</h5>
                </div>
                <div class="card-body search-container">
                    <form method="GET" action="" id="searchForm">
                        <div class="search-form-container">
                            <div class="input-container">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control search-input" id="solped_client" name="solped_client" 
                                           placeholder="Enter Solped Client ID" value="<?php echo htmlspecialchars($solped_client); ?>" required>
                                </div>
                            </div>
                            <div class="btn-container">
                                <button type="submit" name="search" class="search-btn">
                                    <i class="fas fa-search me-2"></i> Search
                                </button>
                                <a href="solped_search.php" class="reset-btn">
                                    <i class="fa-solid fa-broom me-2"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Search Results -->
            <?php if($search_performed): ?>
                <?php if(empty($results)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-info-circle me-2"></i> No OF number found for "<?php echo htmlspecialchars($solped_client); ?>".
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-dark bg-gradient text-white">
                            <div class="results-heading">
                                <h5 class="mb-0"><i class="fas fa-table me-2"></i> Search Results (Grouped)</h5>
                                <span class="results-count"><?php echo count($results); ?> Total Records / <?php echo count($grouped_results); ?> Unique Records</span>
                            </div>
                        </div>
                        <div class="card-body">

                            
                            <!-- Export Button -->
                            <div class="mt-3">
                                <form method="GET" action="export_solped_results.php">
                                    <input type="hidden" name="solped_client" value="<?php echo htmlspecialchars($solped_client); ?>">
                                    <input type="hidden" name="grouped" value="1">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-file-excel me-2"></i> Export to Excel
                                    </button>
                                </form>
                            </div>
                            
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> 
                                <?php echo count($results); ?> barcodes found successfully. 
                                Identical records have been grouped together (<span class="fw-bold"><?php echo count($grouped_results); ?> unique entries</span>). 
                                The "Count" column shows duplicate quantities.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Count</th>
                                            <th>OF Number</th>
                                            <th>Size</th>
                                            <th>Category</th>
                                            <th>Piece Name</th>
                                            <th>Chef</th>
                                            <th>Stage</th>
                                            <th>Solped Client</th>
                                            <th>Pedido Client</th>
                                            <th>Color Tissus</th>
                                            <th>Main Qty</th>
                                            <th>Qty Coupe</th>
                                            <th>Manque</th>
                                            <th>Suv Plus</th>
                                            <th>Last Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($grouped_results as $group): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary count-badge">
                                                        <?php echo $group['count']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($group['data']['of_number']); ?></td>
                                                <td><?php echo htmlspecialchars($group['data']['size']); ?></td>
                                                <td><?php echo htmlspecialchars($group['data']['category']); ?></td>
                                                <td><?php echo htmlspecialchars($group['data']['piece_name']); ?></td>
                                                <td><?php echo htmlspecialchars($group['data']['chef'] ?? ''); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary stage-badge">
                                                        <?php echo htmlspecialchars($group['data']['stage'] ?? ''); ?>
                                                    </span>
                                                </td>
                                                <td class="bg-light fw-bold"><?php echo htmlspecialchars($group['data']['solped_client']); ?></td>
                                                <td><?php echo htmlspecialchars($group['data']['pedido_client'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($group['data']['color_tissus'] ?? ''); ?></td>
                                                <td><?php echo $group['data']['principale_quantity']; ?></td>
                                                <td><?php echo $group['data']['quantity_coupe']; ?></td>
                                                <td><?php echo $group['data']['manque']; ?></td>
                                                <td><?php echo $group['data']['suv_plus']; ?></td>
                                                <td><?php echo $group['data']['latest_update'] ? date('Y-m-d H:i', strtotime($group['data']['latest_update'])) : ''; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-light border text-center p-4">
                    <i class="fas fa-search fa-2x mb-3 text-primary"></i>
                    <p>Enter a Solped Client ID to search for related records.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="d-flex flex-column bg-white align-items-center p-4 rounded shadow">
            <div class="spinner-border text-primary"></div>
            <h5 class="mt-3 mb-2 text-center text-dark">
                Processing Your Request...
            </h5>
            <p class="text-muted text-center">
                This may take a moment depending on data size
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Loading Screen Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show loading overlay on page load
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Hide when page is fully loaded
            window.addEventListener('load', function() {
                document.getElementById('loadingOverlay').style.display = 'none';
            });

            // Show loading overlay when form is submitted
            document.getElementById('searchForm').addEventListener('submit', function() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            });

            // Show loading when reset button is clicked
            document.querySelectorAll('.reset-btn').forEach(link => {
                link.addEventListener('click', () => document.getElementById('loadingOverlay').style.display = 'flex');
            });
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show loading overlay on page load
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Hide when page is fully loaded
            window.addEventListener('load', function() {
                document.getElementById('loadingOverlay').style.display = 'none';
            });

            // Show loading overlay when form is submitted
            document.getElementById('searchForm').addEventListener('submit', function() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            });

            // Excel export button handling
            document.querySelectorAll('form[action="export_solped_results.php"]').forEach(form => {
                form.addEventListener('submit', function() {
                    // Show loading overlay when export is initiated
                    document.getElementById('loadingOverlay').style.display = 'flex';
                    
                    // Create and track the download
                    const downloadTimer = setTimeout(function() {
                        // Hide the overlay after a reasonable timeout (5 seconds)
                        document.getElementById('loadingOverlay').style.display = 'none';
                    }, 5000); // 5 seconds should be enough for most downloads to start
                });
            });

            // Show loading when reset button is clicked
            document.querySelectorAll('.reset-btn').forEach(link => {
                link.addEventListener('click', () => document.getElementById('loadingOverlay').style.display = 'flex');
            });
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>