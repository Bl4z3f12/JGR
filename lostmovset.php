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

    // Filters
    $filter_specific_date = $_GET['specific_date'] ?? date('Y-m-d');
    $filter_of_number = $_GET['of_number'] ?? '';
    $filter_size = $_GET['size'] ?? '';
    $filter_category = $_GET['category'] ?? '';
    $filter_piece = $_GET['piece'] ?? '';
    $filter_order = $_GET['order'] ?? '';
    $filter_used_by = $_GET['used_by'] ?? '';

    // Pagination settings
    $items_per_page = 250; // Maximum rows per page
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    function getLostBarcodes($pdo, $specific_date, $size, $category, $piece, $order, $used_by, $of_number, $limit = null, $offset = null) {
        $params = [':specific_date' => $specific_date];
        
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
                b.last_update  AS last_seen,
                b.status
            FROM barcodes b
            WHERE 
                b.order_str LIKE '%X%'
                AND DATE(b.last_update) = :specific_date
        ";
        
        // Add additional filters if provided
        if (!empty($of_number)) {
            $sql .= " AND b.of_number LIKE :of_number";
            $params[':of_number'] = '%' . $of_number . '%';  // Allow partial matches
        }
        
        if (!empty($size)) {
            $sql .= " AND b.size = :size";
            $params[':size'] = $size;
        }
        
        if (!empty($category)) {
            $sql .= " AND b.category = :category";
            $params[':category'] = $category;
        }
        
        if (!empty($piece)) {
            $sql .= " AND b.piece_name = :piece";
            $params[':piece'] = $piece;
        }
        
        if (!empty($order)) {
            $sql .= " AND b.order_str LIKE :order";
            $params[':order'] = '%' . $order . '%';  // Allow partial matches
        }
        
        if (!empty($used_by)) {
            $sql .= " AND b.name = :used_by";
            $params[':used_by'] = $used_by;
        }
        
        $sql .= " ORDER BY b.last_update DESC";
        
        // Add pagination if limit is provided
        if ($limit !== null) {
            // FIX: Directly add limit and offset to SQL rather than using named parameters
            $sql .= " LIMIT " . intval($limit);
            
            if ($offset !== null) {
                $sql .= " OFFSET " . intval($offset);
            }
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Count total records for pagination
    function countLostBarcodes($pdo, $specific_date, $size, $category, $piece, $order, $used_by, $of_number) {
        $params = [':specific_date' => $specific_date];
        
        $sql = "
            SELECT COUNT(DISTINCT b.full_barcode_name) AS total
            FROM barcodes b
            WHERE 
                b.order_str LIKE '%X%'
                AND DATE(b.last_update) = :specific_date
        ";
        
        // Add additional filters if provided
        if (!empty($of_number)) {
            $sql .= " AND b.of_number LIKE :of_number";
            $params[':of_number'] = '%' . $of_number . '%';
        }
        
        if (!empty($size)) {
            $sql .= " AND b.size = :size";
            $params[':size'] = $size;
        }
        
        if (!empty($category)) {
            $sql .= " AND b.category = :category";
            $params[':category'] = $category;
        }
        
        if (!empty($piece)) {
            $sql .= " AND b.piece_name = :piece";
            $params[':piece'] = $piece;
        }
        
        if (!empty($order)) {
            $sql .= " AND b.order_str LIKE :order";
            $params[':order'] = '%' . $order . '%';
        }
        
        if (!empty($used_by)) {
            $sql .= " AND b.name = :used_by";
            $params[':used_by'] = $used_by;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
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

    // Get total count for pagination
    $total_records = countLostBarcodes(
        $pdo, 
        $filter_specific_date, 
        $filter_size, 
        $filter_category, 
        $filter_piece, 
        $filter_order, 
        $filter_used_by,
        $filter_of_number
    );
    
    // Calculate pagination values
    $total_pages = ceil($total_records / $items_per_page);
    $current_page = min($current_page, max(1, $total_pages)); // Ensure page is valid
    $offset = ($current_page - 1) * $items_per_page;
    
    // Load data with all filters applied and pagination
    $lost_barcodes = getLostBarcodes(
        $pdo, 
        $filter_specific_date, 
        $filter_size, 
        $filter_category, 
        $filter_piece, 
        $filter_order, 
        $filter_used_by,
        $filter_of_number,
        $items_per_page,
        $offset
    );

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

// AJAX: Delete Barcode
if (isset($_POST['ajax'], $_POST['barcode']) && $_POST['ajax'] === 'deleteBarcode') {
    header('Content-Type: application/json');
    
    if (empty($_POST['barcode'])) {
        echo json_encode(['error' => 'Barcode not provided']);
        exit;
    }
    
    try {
        // Delete the barcode from the database
        $sql = "DELETE FROM barcodes WHERE full_barcode_name = :barcode";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':barcode' => $_POST['barcode']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Barcode successfully deleted']);
        } else {
            echo json_encode(['error' => 'Barcode not found or already deleted']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// AJAX: Update User
if (isset($_POST['ajax'], $_POST['barcode'], $_POST['user']) && $_POST['ajax'] === 'updateUser') {
    header('Content-Type: application/json');
    
    if (empty($_POST['barcode'])) {
        echo json_encode(['error' => 'Barcode not provided']);
        exit;
    }
    
    if (empty($_POST['user'])) {
        echo json_encode(['error' => 'User not provided']);
        exit;
    }
    
    try {
        // Update the user name for the barcode
        $sql = "UPDATE barcodes SET name = :user WHERE full_barcode_name = :barcode";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user' => $_POST['user'],
            ':barcode' => $_POST['barcode']
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User successfully updated']);
        } else {
            echo json_encode(['error' => 'Barcode not found or no changes made']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>