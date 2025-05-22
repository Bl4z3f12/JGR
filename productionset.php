<?php
$current_view = 'production.php';
$host = "localhost";
$dbname = "jgr";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $filter_stage = isset($_GET['stage']) ? $_GET['stage'] : null;
    $filter_piece_name = isset($_GET['piece_name']) ? $_GET['piece_name'] : null;

    $display_stages = [
        'Coupe' => [
            'icon' => '<i class="fas fa-cut"></i>',
            'color' => 'primary',
        ],
        'V1' => [
            'icon' => '<i class="fas fa-tshirt"></i>',
            'color' => 'primary',
        ],
        'V2' => [
            'icon' => '<i class="fas fa-tshirt"></i>',
            'color' => 'primary',
        ],
        'V3' => [
            'icon' => '<i class="fas fa-vest"></i>',
            'color' => 'primary',
        ],
        'Pantalon' => [
            'icon' => '<i class="fas fa-socks"></i>',
            'color' => 'primary',
        ],
        'AMF' => [
            'icon' => '<i class="fas fa-cut"></i>',
            'color' => 'primary',
        ],
        'Repassage' => [
            'icon' => '<i class="fas fa-iron"></i>',
            'color' => 'primary',
        ],
        'P_ fini' => [
            'icon' => '<i class="fas fa-box"></i>',
            'color' => 'primary',
        ],
        'Exported' => [
            'icon' => '<i class="fas fa-truck"></i>',
            'color' => 'primary',
        ]
    ];

    $targets = [
        'Coupe' => 100,
        'V1' => 100,
        'V2' => 100,
        'V3' => 100,
        'Pantalon' => 100,
        'Repassage' => 1000,
        'P_ fini' => 1000,
        'Exported' => 100
    ];
    $daily_stage_stats = [];
    foreach ($display_stages as $stage => $props) {
        $daily_stage_stats[$stage] = [
            'current' => 0, // This will hold the count for the specific day
            'in' => 0,
            'out' => 0,
            'from_stages' => [],
            'to_stages' => []
        ];
    }
$daily_items_query = "
SELECT 
    b.stage,
    COUNT(DISTINCT b.full_barcode_name) as count
FROM barcodes b
LEFT JOIN jgr_barcodes_history h ON h.full_barcode_name = b.full_barcode_name
WHERE h.full_barcode_name IS NOT NULL
    AND DATE(h.last_update) = :date
    AND h.action_type IN ('INSERT', 'UPDATE')
    AND h.last_update = (
        SELECT MAX(h2.last_update)
        FROM jgr_barcodes_history h2
        WHERE h2.full_barcode_name = h.full_barcode_name
        AND DATE(h2.last_update) <= :date
    )
GROUP BY b.stage";

$daily_params = [':date' => $filter_date];
$daily_items_stmt = $pdo->prepare($daily_items_query);
$daily_items_stmt->execute($daily_params);
$daily_items = $daily_items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($daily_items as $row) {
        $stage = $row['stage'];
        if (isset($daily_stage_stats[$stage])) {
            $daily_stage_stats[$stage]['current'] = (int)$row['count'];
        }
    }
    
    $daily_transitions_query = "
        SELECT 
            h1.stage AS from_stage,
            h2.stage AS to_stage,
            COUNT(DISTINCT h1.full_barcode_name) as transition_count
        FROM 
            jgr_barcodes_history h1
        JOIN 
            jgr_barcodes_history h2 ON h1.full_barcode_name = h2.full_barcode_name
                                   AND h2.action_time = (
                                       SELECT MIN(h3.action_time)
                                       FROM jgr_barcodes_history h3
                                       WHERE h3.full_barcode_name = h1.full_barcode_name
                                       AND h3.action_time > h1.action_time
                                       AND DATE(h3.last_update) = :date
                                   )
        JOIN
            barcodes b ON h1.full_barcode_name = b.full_barcode_name
        WHERE 
            h1.stage != h2.stage
            AND h1.action_type IN ('INSERT', 'UPDATE')
            AND h2.action_type = 'UPDATE'
            AND DATE(h2.last_update) = :date
        GROUP BY 
            h1.stage, h2.stage
        ORDER BY 
            from_stage, to_stage
    ";
    
    $daily_transitions_stmt = $pdo->prepare($daily_transitions_query);
    $daily_transitions_stmt->execute($daily_params);
    $daily_transitions = $daily_transitions_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($daily_transitions as $transition) {
        $from_stage = $transition['from_stage'];
        $to_stage = $transition['to_stage'];
        $count = (int)$transition['transition_count'];
        
        if (isset($daily_stage_stats[$from_stage])) {
            $daily_stage_stats[$from_stage]['out'] += $count;
            
            if (!isset($daily_stage_stats[$from_stage]['to_stages'][$to_stage])) {
                $daily_stage_stats[$from_stage]['to_stages'][$to_stage] = $count;
            } else {
                $daily_stage_stats[$from_stage]['to_stages'][$to_stage] += $count;
            }
        }
        
        if (isset($daily_stage_stats[$to_stage])) {
            $daily_stage_stats[$to_stage]['in'] += $count;
            
            if (!isset($daily_stage_stats[$to_stage]['from_stages'][$from_stage])) {
                $daily_stage_stats[$to_stage]['from_stages'][$from_stage] = $count;
            } else {
                $daily_stage_stats[$to_stage]['from_stages'][$from_stage] += $count;
            }
        }
    }
    
    $first_stage_query = "
    SELECT 
        h.stage,
        COUNT(DISTINCT h.full_barcode_name) as count
    FROM 
        jgr_barcodes_history h
    JOIN
        barcodes b ON h.full_barcode_name = b.full_barcode_name
    WHERE 
        h.action_type = 'INSERT'
        AND DATE(h.last_update) = :date
        AND NOT EXISTS (
            SELECT 1
            FROM jgr_barcodes_history h_prev
            WHERE h_prev.full_barcode_name = h.full_barcode_name
            AND h_prev.action_time < h.action_time
        )
    GROUP BY 
        h.stage
    ORDER BY 
        stage
    ";
    
    $first_stage_stmt = $pdo->prepare($first_stage_query);
    $first_stage_stmt->execute($daily_params);
    $first_stages = $first_stage_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($first_stages as $first) {
        $stage = $first['stage'];
        $count = (int)$first['count'];
        
        if (isset($daily_stage_stats[$stage])) {
            $daily_stage_stats[$stage]['in'] += $count;
            
            if (!isset($daily_stage_stats[$stage]['from_stages']['Initial'])) {
                $daily_stage_stats[$stage]['from_stages']['Initial'] = $count;
            } else {
                $daily_stage_stats[$stage]['from_stages']['Initial'] += $count;
            }
        }
    }
    
    $total_count = 0;
    $chart_data = [
        'labels' => [],
        'current' => [],
        'in' => [],
        'out' => []
    ];
    
    foreach ($daily_stage_stats as $stage => $stats) {
        $total_count += $stats['current'];
        $chart_data['labels'][] = $stage;
        $chart_data['current'][] = $stats['current'];
        $chart_data['in'][] = $stats['in'];
        $chart_data['out'][] = $stats['out'];
    }
    
    function getEmoji($count) {
        if ($count > 900) return '🔥';
        if ($count >= 700) return '👍';
        return '⚠️';
    }
    function getBadgeColor($count) {
        if ($count > 900) return 'bg-success';
        if ($count >= 700) return 'bg-warning';
        return 'bg-danger';
    }
    function getFaceEmoji($count) {
        if ($count > 900) {
            return '<i class="fas fa-smile text-success fa-2x"></i>'; // Happy green face for > 900
        } elseif ($count >= 700 && $count <= 900) {
            return '<i class="fas fa-meh text-warning fa-2x"></i>'; // Neutral face for 700-900
        } else {
            return '<i class="fas fa-angry text-danger fa-2x"></i>'; // Angry face for < 700
        }
    }
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

function getAvailableStages($pdo, $filter_date) {
    $predefined_stages = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'AMF', 'Repassage', 'P_ fini', 'Exported'];
    
    if (!$pdo) {
        error_log("Invalid database connection in getAvailableStages");
        return $predefined_stages;
    }
    
    try {
        $query = "
        SELECT DISTINCT IFNULL(b.stage, 'No Stage') as stage 
        FROM barcodes b
        WHERE 1=1";
        
        if (!empty($filter_date)) {
            $query .= " AND DATE(b.last_update) = ?";
        }
        
        $stmt = $pdo->prepare($query);
        
        if (!empty($filter_date)) {
            $stmt->execute([$filter_date]);
        } else {
            $stmt->execute();
        }
        
        $db_stages = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $available_stages = array_filter($predefined_stages, function($stage) use ($db_stages) {
            return in_array($stage, $db_stages);
        });
        
        foreach ($db_stages as $stage) {
            if (!in_array($stage, $predefined_stages) && !empty($stage)) {
                $available_stages[] = $stage;
            }
        }
        return $available_stages;
    } catch(PDOException $e) {
        error_log("Get Available Stages Query Error: " . $e->getMessage());
        return $predefined_stages;
    }
}

function getProductionSummary($pdo, $filter_date, $filter_stage = null, $filter_piece_name = null, $filter_of = null) {
    if (!$pdo) {
        error_log("Invalid database connection in getProductionSummary");
        return [];
    }
    try {
        // Prepare base parameters first
        $params = [':date' => $filter_date]; // Add the date parameter for the history table

        $query = "
        SELECT 
            b.of_number,
            b.size,
            b.category,
            b.piece_name AS p_name,
            b.chef,
            IFNULL(b.stage, 'No Stage') as stage,
            COUNT(b.id) AS total_count, 
            SUM(qc.quantity_coupe) AS total_stage_quantity,
            SUM(qc.principale_quantity) AS total_main_quantity,
            MAX(qc.solped_client) AS solped_client,
            MAX(qc.pedido_client) AS pedido_client,
            MAX(qc.color_tissus) AS color_tissus,
            SUM(qc.manque) AS manque,
            SUM(qc.suv_plus) AS suv_plus,
            MAX(IFNULL(qc.lastupdate, b.last_update)) AS latest_update 
        FROM barcodes b 
        LEFT JOIN quantity_coupe qc ON b.of_number = qc.of_number
            AND b.size = qc.size
            AND b.category = qc.category
            AND b.piece_name = qc.piece_name 
        LEFT JOIN jgr_barcodes_history h ON h.full_barcode_name = b.full_barcode_name 
        WHERE 
            h.full_barcode_name IS NOT NULL
            AND DATE(h.last_update) = :date
            AND h.action_type IN ('INSERT', 'UPDATE')
            AND h.last_update = (
                SELECT MAX(h2.last_update)
                FROM jgr_barcodes_history h2
                WHERE h2.full_barcode_name = h.full_barcode_name
                AND DATE(h2.last_update) <= :date
            )
            AND b.stage IS NOT NULL 
            AND b.stage != ''";

        // Add filter conditions
        if (!empty($filter_stage)) {
            if ($filter_stage == 'No Stage') {
                $query .= " AND (b.stage IS NULL OR b.stage = '')";
            } else {
                $query .= " AND b.stage = :filter_stage";
                $params[':filter_stage'] = $filter_stage;
            }
        }
        
        if (!empty($filter_piece_name)) {
            $query .= " AND b.piece_name = :filter_piece_name";
            $params[':filter_piece_name'] = $filter_piece_name;
        }
        
        if (!empty($filter_of)) {
            $query .= " AND b.of_number = :filter_of";
            $params[':filter_of'] = $filter_of;
        }
        
        // GROUP BY clause should be after all WHERE conditions
        $query .= " GROUP BY 
            b.of_number, 
            b.size, 
            b.category, 
            b.piece_name, 
            b.chef, 
            IFNULL(b.stage, 'No Stage')
        ORDER BY b.of_number, b.size, b.category, b.piece_name";
                
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Store the results in session for export
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['production_summary'] = $results;
        
        return $results;
    } catch(PDOException $e) {
        error_log("Production Summary Query Error: " . $e->getMessage());
        return [];
    }
}

function getAvailableOFNumbers($pdo, $filter_date) {
    if (!$pdo) {
        error_log("Invalid database connection in getAvailableOFNumbers");
        return [];
    }
    
    try {
        $query = "
        SELECT DISTINCT b.of_number 
        FROM barcodes b
        LEFT JOIN jgr_barcodes_history h ON h.full_barcode_name = b.full_barcode_name
        WHERE b.of_number IS NOT NULL AND b.of_number != ''";
        
        $params = [];
        
        if (!empty($filter_date)) {
            $query .= " AND DATE(h.last_update) = :date 
                        AND h.action_type IN ('INSERT', 'UPDATE')
                        AND h.last_update = (
                            SELECT MAX(h2.last_update)
                            FROM jgr_barcodes_history h2
                            WHERE h2.full_barcode_name = h.full_barcode_name
                            AND DATE(h2.last_update) <= :date
                        )";
            $params[':date'] = $filter_date;
        }
        
        $query .= " ORDER BY b.of_number";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {
        error_log("Get Available OF Numbers Query Error: " . $e->getMessage());
        return [];
    }
}