<?php
require_once 'auth_functions.php';
requireLogin('login.php');
$current_view = 'lostmov.php';
$host = "localhost";
$dbname = "jgr";
$username = "root";
$password = "";

// Function to handle AJAX error responses
function sendJsonError($message) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Default filters - changed to single specific date filter
    $filter_specific_date = isset($_GET['specific_date']) ? $_GET['specific_date'] : date('Y-m-d');
    
    // Get all lost barcodes (containing 'X' in their name)
    function getLostBarcodes($pdo, $specific_date) {
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
                AND DATE(b.last_update) = :specific_date
            ORDER BY
                b.last_update DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':specific_date' => $specific_date
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get movement history for a specific barcode - This function will be used via AJAX
    function getBarcodeMovementHistory($pdo, $barcode) {
        $query = "
          SELECT
              h.full_barcode_name,
              h.stage,
              h.action_type,
              h.last_update,
              h.action_time
          FROM
              jgr_barcodes_history h
          WHERE
              h.full_barcode_name = :barcode
          ORDER BY
              h.action_time ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':barcode' => $barcode]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // If this is an AJAX request for barcode history
    if(isset($_GET['ajax']) && $_GET['ajax'] == 'getHistory' && isset($_GET['barcode'])) {
        try {
            header('Content-Type: application/json');
            $history = getBarcodeMovementHistory($pdo, $_GET['barcode']);
            echo json_encode($history);
            exit;
        } catch(PDOException $e) {
            sendJsonError("Database error: " . $e->getMessage());
        }
    }
    
    // Add error handling for AJAX requests
    if(isset($_GET['ajax'])) {
        sendJsonError('Invalid AJAX request');
    }
    
    // Get all lost barcodes for the specific date
    $lost_barcodes = getLostBarcodes($pdo, $filter_specific_date);
    
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
    // Check if it's an AJAX request
    if(isset($_GET['ajax'])) {
        sendJsonError("Database connection failed: " . $e->getMessage());
    } else {
        // Regular page request - show error on page
        $connection_error = "Connection failed: " . $e->getMessage();
    }
}
?>