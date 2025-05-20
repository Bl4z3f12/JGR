<?php
require_once 'auth_functions.php';
requireLogin('login.php');
$current_view = 'lostmov.php';

$host     = "localhost";
$dbname   = "jgr";
$username = "root";
$password = "";

// AJAX error helper
function sendJsonError($msg) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $msg]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // date filter
    $filter_specific_date = $_GET['specific_date'] ?? date('Y-m-d');

    // Fetch only those with 'X' in the order_str
    function getLostBarcodes($pdo, $specific_date) {
        $sql = "
            SELECT DISTINCT 
                b.full_barcode_name,
                b.of_number,
                b.stage        AS current_stage,
                b.piece_name,
                b.category,
                b.size,
                b.order_str,
                b.chef,
                b.name,
                b.last_update  AS last_seen
            FROM barcodes b
            WHERE 
                b.order_str LIKE '%X%'
                AND DATE(b.last_update) = :specific_date
            ORDER BY b.last_update DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':specific_date' => $specific_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // History via AJAX
    function getBarcodeMovementHistory($pdo, $barcode) {
        $sql = "
            SELECT full_barcode_name, stage, action_type, last_update, action_time
            FROM jgr_barcodes_history
            WHERE full_barcode_name = :barcode
            ORDER BY action_time ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':barcode' => $barcode]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // AJAX: getHistory
    if (isset($_GET['ajax'], $_GET['barcode']) && $_GET['ajax']==='getHistory') {
        header('Content-Type: application/json');
        echo json_encode(getBarcodeMovementHistory($pdo, $_GET['barcode']));
        exit;
    }
    if (isset($_GET['ajax'])) {
        sendJsonError('Invalid AJAX request');
    }

    // Load data
    $lost_barcodes = getLostBarcodes($pdo, $filter_specific_date);

    // Stage counts (optional)
    $stage_counts = [];
    foreach ($lost_barcodes as $b) {
        $s = $b['current_stage'] ?: 'Unknown';
        $stage_counts[$s] = ($stage_counts[$s] ?? 0) + 1;
    }
    arsort($stage_counts);

} catch (PDOException $e) {
    if (isset($_GET['ajax'])) {
        sendJsonError($e->getMessage());
    }
    $connection_error = $e->getMessage();
}
?>
