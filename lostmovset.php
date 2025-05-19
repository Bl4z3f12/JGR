<?php
$current_view = 'lost_barcodes_tracking.php';
$host = "localhost";
$dbname = "jgr";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Default filters
    $filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days'));
    $filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    // Get all lost barcodes (containing 'X' in their name)
    function getLostBarcodes($pdo, $date_from, $date_to) {
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
                AND b.last_update BETWEEN :date_from AND :date_to
            ORDER BY
                b.last_update DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':date_from' => $date_from . ' 00:00:00',
            ':date_to' => $date_to . ' 23:59:59'
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get movement history for a specific barcode
    function getBarcodeMovementHistory($pdo, $barcode) {
        $query = "
            SELECT 
                h.full_barcode_name,
                h.stage,
                h.action_type,
                h.last_update,
                h.action_time,
                IFNULL(u.username, 'System') AS modified_by
            FROM 
                jgr_barcodes_history h
            LEFT JOIN 
                users u ON h.user_id = u.id
            WHERE 
                h.full_barcode_name = :barcode
            ORDER BY 
                h.action_time ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':barcode' => $barcode]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all lost barcodes
    $lost_barcodes = getLostBarcodes($pdo, $filter_date_from, $filter_date_to);
    
    // If a specific barcode is selected for viewing its history
    $selected_barcode = isset($_GET['barcode']) ? $_GET['barcode'] : null;
    $barcode_history = [];
    
    if ($selected_barcode) {
        $barcode_history = getBarcodeMovementHistory($pdo, $selected_barcode);
    }
    
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
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
