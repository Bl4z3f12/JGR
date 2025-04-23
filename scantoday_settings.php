<?php
// Database connection configuration
$host = 'localhost';
$db_name = 'jgr';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// PDO connection options
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
$p_name = $_GET['p_name'] ?? '';
$stage = $_GET['stage'] ?? ''; 
$date = $_GET['date'] ?? $current_date;

// Default active tab
$active_tab = $_GET['tab'] ?? 'summary';

// Initialize arrays to store results
$grouped_results = [];
$stage_summary = [];

// Function to check if barcode exists
function checkBarcodeExists($pdo, $of_number, $size, $category, $piece_name) {
    $query = "SELECT COUNT(*) as count FROM barcodes 
              WHERE of_number = ? AND size = ? AND category = ? AND piece_name = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$of_number, $size, $category, $piece_name]);
    $row = $stmt->fetch();
    return $row['count'] > 0;
}

// Barcode validation variables
$barcode_exists = false;
$barcode_checked = false;
$barcode_data = [
    'of_number' => '',
    'size' => '',
    'category' => '',
    'piece_name' => ''
];

// Check barcode existence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_barcode'])) {
    $of_number = trim($_POST['of_number'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $piece_name = trim($_POST['piece_name'] ?? '');
    
    $barcode_data = [
        'of_number' => $of_number,
        'size' => $size,
        'category' => $category,
        'piece_name' => $piece_name
    ];
    
    $barcode_exists = checkBarcodeExists($pdo, $of_number, $size, $category, $piece_name);
    $barcode_checked = true;
}

// Handle quantity coupe form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quantity'])) {
    // Extract and sanitize form data
    $of_number = $_POST['of_number'];
    $size = $_POST['size'];
    $category = $_POST['category'];
    $piece_name = $_POST['piece_name'];
    
    // Check if barcode exists
    $barcode_exists = checkBarcodeExists($pdo, $of_number, $size, $category, $piece_name);
    
    if (!$barcode_exists) {
        $error_message = "Error: The specified barcode combination does not exist. Please try again.";
    } else {
        // Updated field list to match database schema
        $fields = [
            'solped_client', 'pedido_client', 'color_tissus', 'principale_quantity',
            'quantity_coupe', 'manque', 'suv_plus'
        ];
        
        $data = [];
        foreach ($fields as $field) {
            if (in_array($field, ['principale_quantity', 'quantity_coupe', 'manque', 'suv_plus'])) {
                $data[$field] = intval($_POST[$field] ?? 0);
            } else {
                $data[$field] = $_POST[$field] ?? '';
            }
        }
        
        // Check if record exists
        $check_sql = "SELECT id FROM quantity_coupe WHERE of_number = ? AND size = ? AND category = ? AND piece_name = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$of_number, $size, $category, $piece_name]);
        $existing_record = $check_stmt->fetch();
        
        try {
            if ($existing_record) {
                // Update existing record
                $id = $existing_record['id'];
                $update_fields = [];
                foreach ($fields as $field) {
                    $update_fields[] = "$field = :$field";
                }
                $update_sql = "UPDATE quantity_coupe SET " . implode(", ", $update_fields) . ", lastupdate = NOW() WHERE id = :id";
                $update_stmt = $pdo->prepare($update_sql);
                foreach ($data as $field => $value) {
                    $update_stmt->bindValue(":$field", $value);
                }
                $update_stmt->bindValue(":id", $id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Quantity coupe data updated successfully";
                } else {
                    $error_message = "Error updating record";
                }
            } else {
                // Insert new record with proper fields
                $insert_fields = ['of_number', 'size', 'category', 'piece_name'];
                $insert_values = [$of_number, $size, $category, $piece_name];
                
                // Add each field and value
                foreach ($fields as $field) {
                    $insert_fields[] = $field;
                    $insert_values[] = $data[$field];
                }
                
                // Add lastupdate field
                $insert_fields[] = 'lastupdate';
                $insert_values[] = date('Y-m-d H:i:s');
                
                // Create the correct number of placeholders
                $placeholders = rtrim(str_repeat("?,", count($insert_fields)), ",");
                $insert_sql = "INSERT INTO quantity_coupe (" . implode(", ", $insert_fields) . 
                              ") VALUES (" . $placeholders . ")";
                $insert_stmt = $pdo->prepare($insert_sql);
                
                if ($insert_stmt->execute($insert_values)) {
                    $success_message = "Quantity coupe data added successfully";
                } else {
                    $error_message = "Error adding record";
                }
            }
        } catch (PDOException $e) {
            // Handle foreign key constraint violations
            if ($e->getCode() == '23000') { // Integrity constraint violation
                $error_message = "Error: Cannot add or update record due to database constraints. Make sure barcode exists first.";
            } else {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Delete quantity coupe record
if (isset($_GET['delete_qc']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete_sql = "DELETE FROM quantity_coupe WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    
    try {
        if ($delete_stmt->execute([$id])) {
            $success_message = "Record deleted successfully";
        } else {
            $error_message = "Error deleting record";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle search action for Summary view
if (isset($_GET['search']) || $active_tab == 'summary') {
    // MODIFIED: Changed query to include chef and stage in GROUP BY clause
    $query = "SELECT 
                b.of_number, 
                b.size, 
                b.category, 
                b.piece_name AS p_name,
                b.chef,
                b.stage,
                COUNT(b.id) AS total_count,
                SUM(IFNULL(qc.quantity_coupe, 0)) AS total_stage_quantity,
                SUM(IFNULL(qc.principale_quantity, 0)) AS total_main_quantity,
                MAX(qc.solped_client) AS solped_client,
                MAX(qc.pedido_client) AS pedido_client,
                MAX(qc.color_tissus) AS color_tissus,
                MAX(qc.principale_quantity) AS principale_quantity,
                MAX(qc.quantity_coupe) AS quantity_coupe,
                MAX(qc.manque) AS manque,
                MAX(qc.suv_plus) AS suv_plus,
                MAX(IFNULL(qc.lastupdate, b.last_update)) AS latest_update
              FROM barcodes b
              LEFT JOIN quantity_coupe qc ON b.of_number = qc.of_number 
                AND b.size = qc.size 
                AND b.category = qc.category 
                AND b.piece_name = qc.piece_name
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($of_number)) {
        $query .= " AND b.of_number LIKE ?";
        $params[] = "%$of_number%";
    }
    
    if (!empty($size)) {
        $query .= " AND b.size = ?";
        $params[] = $size;
    }
    
    if (!empty($category) && $category != 'select') {
        $query .= " AND b.category = ?";
        $params[] = $category;
    }
    
    if (!empty($p_name) && $p_name != 'select') {
        $query .= " AND b.piece_name = ?";
        $params[] = $p_name;
    }
    
    if (!empty($stage) && $stage != 'select') {
        $query .= " AND b.stage = ?";
        $params[] = $stage;
    }
    
    // FIXED DATE FILTER
    if (!empty($date)) {
        $query .= " AND DATE(
                        (SELECT MAX(IFNULL(qc2.lastupdate, b2.last_update))
                         FROM barcodes b2
                         LEFT JOIN quantity_coupe qc2 ON b2.of_number = qc2.of_number 
                            AND b2.size = qc2.size 
                            AND b2.category = qc2.category 
                            AND b2.piece_name = qc2.piece_name
                         WHERE b2.of_number = b.of_number
                            AND b2.size = b.size
                            AND b2.category = b.category
                            AND b2.piece_name = b.piece_name
                        )
                    ) = ?";
        $params[] = $date;
    }
    
    // MODIFIED: Group by core identifiers AND chef AND stage to separate by chef and stage
    $query .= " GROUP BY b.of_number, b.size, b.category, b.piece_name, b.chef, b.stage";
    $query .= " ORDER BY b.of_number, b.size, b.category, b.piece_name, b.chef, b.stage";
    $query .= " LIMIT 100";  // Limit for performance
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $grouped_results = $stmt->fetchAll();
    
    // Second query to get stage breakdown counts - FIXED DATE FILTER HERE TOO
    $stage_query = "SELECT 
                    b.stage,
                    COUNT(*) as stage_count
                  FROM barcodes b
                  LEFT JOIN quantity_coupe qc ON b.of_number = qc.of_number 
                    AND b.size = qc.size 
                    AND b.category = qc.category 
                    AND b.piece_name = qc.piece_name
                  WHERE 1=1";
    
    // Create new params array specifically for the stage query
    $stage_params = [];
    
    if (!empty($of_number)) {
        $stage_query .= " AND b.of_number LIKE ?";
        $stage_params[] = "%$of_number%";
    }
    
    if (!empty($size)) {
        $stage_query .= " AND b.size = ?";
        $stage_params[] = $size;
    }
    
    if (!empty($category) && $category != 'select') {
        $stage_query .= " AND b.category = ?";
        $stage_params[] = $category;
    }
    
    if (!empty($p_name) && $p_name != 'select') {
        $stage_query .= " AND b.piece_name = ?";
        $stage_params[] = $p_name;
    }
    
    if (!empty($stage) && $stage != 'select') {
        $stage_query .= " AND b.stage = ?";
        $stage_params[] = $stage;
    }
    
    // FIXED DATE FILTER for stage query too
    if (!empty($date)) {
        $stage_query .= " AND DATE(
                            (SELECT MAX(IFNULL(qc2.lastupdate, b2.last_update))
                             FROM barcodes b2
                             LEFT JOIN quantity_coupe qc2 ON b2.of_number = qc2.of_number 
                                AND b2.size = qc2.size 
                                AND b2.category = qc2.category 
                                AND b2.piece_name = qc2.piece_name
                             WHERE b2.of_number = b.of_number
                                AND b2.size = b.size
                                AND b2.category = b.category
                                AND b2.piece_name = b.piece_name
                            )
                        ) = ?";
        $stage_params[] = $date;
    }
    
    $stage_query .= " GROUP BY b.stage";
    $stage_stmt = $pdo->prepare($stage_query);
    $stage_stmt->execute($stage_params);
    $stage_counts = $stage_stmt->fetchAll();
    
    // Create an associative array of stage counts
    $stage_summary = [];
    foreach ($stage_counts as $sc) {
        $stage_summary[$sc['stage']] = $sc['stage_count'];
    }
}

// Function to get stages
function getStages() {
    return ["Coupe", "V1", "V2", "V3", "Pantalon", "Repassage", "P_fini"];
}

// Get quantity coupe data if needed
if ($active_tab == 'quantity_coupe') {
    $quantity_coupe_data = getQuantityCoupeData($pdo, $of_number);
}

// Calculate overall totals for summary view
$total_items = 0;
$total_stage_quantity = 0;
$total_main_quantity = 0;

if (!empty($grouped_results)) {
    foreach ($grouped_results as $row) {
        $total_items += $row['total_count'];
        $total_stage_quantity += $row['total_stage_quantity'];
        $total_main_quantity += $row['total_main_quantity'];
    }
}

// Function to get quantity coupe data
function getQuantityCoupeData($pdo, $of_number = '') {
    $where = '';
    $params = [];
    
    if (!empty($of_number)) {
        $where = "WHERE of_number LIKE ?";
        $params[] = "%$of_number%";
    }
    
    $sql = "SELECT * FROM quantity_coupe $where ORDER BY of_number, size, category, piece_name, lastupdate DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

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
?>