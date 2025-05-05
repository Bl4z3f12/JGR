<?php
// barcode_settings.php
require_once __DIR__ . '/vendor/autoload.php';

// Initialize variables
$of_number_search = $_GET['of_number_search'] ?? '';
$full_barcode_search = $_GET['full_barcode_search'] ?? '';
$piece_name_search = $_GET['piece_name_search'] ?? '';
$category_search = $_GET['category_search'] ?? '';
$order_str_search = $_GET['order_str_search'] ?? '';
$size_search = $_GET['size_search'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = $_GET['page'] ?? 1;
$items_per_page = 5000;

// Database connection function
function connectDB() {
    $conn = new mysqli("localhost", "root", "", "jgr");
    return $conn->connect_error ? false : $conn;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();
    
    // Handle delete action
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (isset($_POST['selected_barcodes']) && is_array($_POST['selected_barcodes'])) {
            // Prepare SQL to delete selected barcodes
            $ids = array_map('intval', $_POST['selected_barcodes']);
            $id_list = implode(',', $ids);
            
            if (!empty($id_list)) {
                $delete_sql = "DELETE FROM barcodes WHERE id IN ($id_list)";
                if ($conn->query($delete_sql)) {
                    $success_message = count($ids) . " barcode(s) successfully deleted.";
                } else {
                    $error_message = "Error deleting barcodes: " . $conn->error;
                }
            }
        } else {
            $error_message = "No barcodes selected for deletion.";
        }
    }
    
    // Handle edit action for a single item
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $barcode_id = intval($_POST['barcode_id']);
        $of_number = $_POST['of_number'];
        $size = intval($_POST['size']);
        $category = $_POST['category'];
        $piece_name = $_POST['piece_name'];
        $order_str = $_POST['order_str'];
        $status = $_POST['status'];
        $stage = $_POST['stage'];
        $chef = $_POST['chef'];
        
        // Get the timestamp or use current time if not provided
        $timestamp = !empty($_POST['last_update']) ? $_POST['last_update'] : date('Y-m-d H:i:s');
        
        // Update the full barcode name
        $full_barcode_name = "$of_number-$size";
        if (!empty($category)) {
            $full_barcode_name .= "$category";
        }
        $full_barcode_name .= "-$piece_name-$order_str";
        
        // Use provided timestamp instead of NOW()
        $update_sql = "UPDATE barcodes SET of_number = ?, size = ?, category = ?, piece_name = ?, 
                   order_str = ?, status = ?, stage = ?, chef = ?, full_barcode_name = ?, last_update = ? 
                   WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sissssssssi", $of_number, $size, $category, $piece_name, $order_str, $status, $stage, $chef, $full_barcode_name, $timestamp, $barcode_id);
        
        if ($stmt->execute()) {
            $success_message = "Barcode updated successfully.";
        } else {
            $error_message = "Error updating barcode: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Handle bulk edit action
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_edit') {
        if (isset($_POST['selected_barcodes']) && is_array($_POST['selected_barcodes'])) {
            $ids = array_map('intval', $_POST['selected_barcodes']);
            $id_list = implode(',', $ids);
            
            if (!empty($id_list)) {
                $fields_to_update = [];
                $params = [];
                $types = '';
                
                // Check which fields to update
                if (isset($_POST['update_status']) && !empty($_POST['bulk_status'])) {
                    $fields_to_update[] = "status = ?";
                    $params[] = $_POST['bulk_status'];
                    $types .= 's';
                }
                
                if (isset($_POST['update_stage']) && !empty($_POST['bulk_stage'])) {
                    $fields_to_update[] = "stage = ?";
                    $params[] = $_POST['bulk_stage'];
                    $types .= 's';
                }
                
                if (isset($_POST['update_chef']) && !empty($_POST['bulk_chef'])) {
                    $fields_to_update[] = "chef = ?";
                    $params[] = $_POST['bulk_chef'];
                    $types .= 's';
                }
                
                // Add datetime handling for bulk edit
                if (isset($_POST['update_timestamp']) && !empty($_POST['bulk_timestamp'])) {
                    $fields_to_update[] = "last_update = ?";
                    $params[] = $_POST['bulk_timestamp'];
                    $types .= 's';
                } else {
                    // If not updating timestamp specifically, update to current time
                    if (!empty($fields_to_update)) {
                        $fields_to_update[] = "last_update = NOW()";
                    }
                }
                
                if (!empty($fields_to_update)) {
                    $update_sql = "UPDATE barcodes SET " . implode(", ", $fields_to_update) . " WHERE id IN ($id_list)";
                    $stmt = $conn->prepare($update_sql);
                    
                    if (!empty($types)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    
                    if ($stmt->execute()) {
                        $success_message = count($ids) . " barcode(s) successfully updated.";
                    } else {
                        $error_message = "Error updating barcodes: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error_message = "No fields selected for update.";
                }
            }
        } else {
            $error_message = "No barcodes selected for updating.";
        }
    }
    
    if ($conn) $conn->close();
    
    // Preserve search parameters after POST actions
    $search_params = [];
    if (!empty($of_number_search)) $search_params[] = "of_number_search=" . urlencode($of_number_search);
    if (!empty($full_barcode_search)) $search_params[] = "full_barcode_search=" . urlencode($full_barcode_search);
    if (!empty($piece_name_search)) $search_params[] = "piece_name_search=" . urlencode($piece_name_search);
    if (!empty($category_search)) $search_params[] = "category_search=" . urlencode($category_search);
    if (!empty($order_str_search)) $search_params[] = "order_str_search=" . urlencode($order_str_search);
    if (!empty($size_search)) $search_params[] = "size_search=" . urlencode($size_search);
    if (!empty($date_to)) $search_params[] = "date_to=" . urlencode($date_to);
    if (!empty($page) && $page > 1) $search_params[] = "page=" . $page;
    
    // Redirect to preserve search parameters and prevent form resubmission
    $redirect_url = "barcode_settings.php";
    if (!empty($search_params)) {
        $redirect_url .= "?" . implode("&", $search_params);
    }
    
    header("Location: " . $redirect_url);
    exit;
}

// Function to get searched barcodes with pagination
function getSearchedBarcodes($of_number_search, $full_barcode_search, $piece_name_search, $category_search, $order_str_search, $size_search, $date_to, $page, $items_per_page) {
    $conn = connectDB();
    if (!$conn) return [];

    $offset = ($page - 1) * $items_per_page;
    
    $where_clauses = [];
    $params = [];
    $types = '';
    
    if (!empty($of_number_search)) {
        $where_clauses[] = "of_number LIKE ?";
        $params[] = "%" . $of_number_search . "%";
        $types .= 's';
    }
    
    if (!empty($full_barcode_search)) {
        $where_clauses[] = "full_barcode_name LIKE ?";
        $params[] = "%" . $full_barcode_search . "%";
        $types .= 's';
    }
    
    if (!empty($piece_name_search)) {
        $where_clauses[] = "piece_name LIKE ?";
        $params[] = "%" . $piece_name_search . "%";
        $types .= 's';
    }
    
    if (!empty($category_search)) {
        $where_clauses[] = "category LIKE ?";
        $params[] = "%" . $category_search . "%";
        $types .= 's';
    }
    
    if (!empty($order_str_search)) {
        $where_clauses[] = "order_str LIKE ?";
        $params[] = "%" . $order_str_search . "%";
        $types .= 's';
    }
    
    if (!empty($size_search)) {
        $where_clauses[] = "size = ?";
        $params[] = $size_search;
        $types .= 'i';
    }
    
    // Filter by specific date
    if (!empty($date_to)) {
        // Convert the input date to start and end of the day
        $date_obj = new DateTime($date_to);
        $start_date = $date_obj->format('Y-m-d 00:00:00');
        $end_date = $date_obj->format('Y-m-d 23:59:59');
        
        $where_clauses[] = "last_update BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= 'ss';
    }
    
    $where_clause = '';
    if (!empty($where_clauses)) {
        $where_clause = "WHERE " . implode(" AND ", $where_clauses);
    }
    
    $params[] = $offset;
    $params[] = $items_per_page;
    $types .= 'ii';
    
    $sql = "SELECT * FROM barcodes $where_clause ORDER BY id DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $barcodes = [];
    while ($row = $result->fetch_assoc()) {
        $barcodes[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $barcodes;
}

// Get total count function for pagination
function getTotalSearchedBarcodes($of_number_search, $full_barcode_search, $piece_name_search, $category_search, $order_str_search, $size_search, $date_to) {
    $conn = connectDB();
    if (!$conn) return 0;
    
    $where_clauses = [];
    $params = [];
    $types = '';
    
    if (!empty($of_number_search)) {
        $where_clauses[] = "of_number LIKE ?";
        $params[] = "%" . $of_number_search . "%";
        $types .= 's';
    }
    
    if (!empty($full_barcode_search)) {
        $where_clauses[] = "full_barcode_name LIKE ?";
        $params[] = "%" . $full_barcode_search . "%";
        $types .= 's';
    }
    
    if (!empty($piece_name_search)) {
        $where_clauses[] = "piece_name LIKE ?";
        $params[] = "%" . $piece_name_search . "%";
        $types .= 's';
    }
    
    if (!empty($category_search)) {
        $where_clauses[] = "category LIKE ?";
        $params[] = "%" . $category_search . "%";
        $types .= 's';
    }
    
    if (!empty($order_str_search)) {
        $where_clauses[] = "order_str LIKE ?";
        $params[] = "%" . $order_str_search . "%";
        $types .= 's';
    }
    
    if (!empty($size_search)) {
        $where_clauses[] = "size = ?";
        $params[] = $size_search;
        $types .= 'i';
    }
    
    // Filter by specific date
    if (!empty($date_to)) {
        // Convert the input date to start and end of the day
        $date_obj = new DateTime($date_to);
        $start_date = $date_obj->format('Y-m-d 00:00:00');
        $end_date = $date_obj->format('Y-m-d 23:59:59');
        
        $where_clauses[] = "last_update BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= 'ss';
    }
    
    $where_clause = '';
    if (!empty($where_clauses)) {
        $where_clause = "WHERE " . implode(" AND ", $where_clauses);
    }
    
    $sql = "SELECT COUNT(*) as total FROM barcodes $where_clause";
    $stmt = $conn->prepare($sql);
    
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    
    $stmt->close();
    $conn->close();
    return $total;
}

// Helper function to build URL with search parameters
function buildSearchUrl($params = []) {
    $url_params = [];
    
    // Default parameters
    $defaults = [
        'of_number_search' => $GLOBALS['of_number_search'],
        'full_barcode_search' => $GLOBALS['full_barcode_search'],
        'piece_name_search' => $GLOBALS['piece_name_search'],
        'category_search' => $GLOBALS['category_search'],
        'order_str_search' => $GLOBALS['order_str_search'],
        'size_search' => $GLOBALS['size_search'],
        'date_to' => $GLOBALS['date_to'],
        'page' => $GLOBALS['page']
    ];
    
    // Merge provided params with defaults
    $params = array_merge($defaults, $params);
    
    // Build URL parameters
    foreach ($params as $key => $value) {
        if (!empty($value) || $value === '0') {
            $url_params[] = $key . '=' . urlencode($value);
        }
    }
    
    return 'barcode_settings.php' . (!empty($url_params) ? '?' . implode('&', $url_params) : '');
}

// Get status options for dropdown
function getStatusOptions() {
    $conn = connectDB();
    if (!$conn) return [];
    
    $sql = "SELECT DISTINCT status FROM barcodes WHERE status IS NOT NULL AND status != '' ORDER BY status";
    $result = $conn->query($sql);
    
    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['status'])) {
                $options[] = $row['status'];
            }
        }
    }
    
    $conn->close();
    return $options;
}

// Get stage options for dropdown
function getStageOptions() {
    $conn = connectDB();
    if (!$conn) return [];
    
    $sql = "SELECT DISTINCT stage FROM barcodes WHERE stage IS NOT NULL AND stage != '' ORDER BY stage";
    $result = $conn->query($sql);
    
    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['stage'])) {
                $options[] = $row['stage'];
            }
        }
    }
    
    $conn->close();
    return $options;
}

// Get chef options for dropdown
function getChefOptions() {
    $conn = connectDB();
    if (!$conn) return [];
    
    $sql = "SELECT DISTINCT chef FROM barcodes WHERE chef IS NOT NULL AND chef != '' ORDER BY chef";
    $result = $conn->query($sql);
    
    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['chef'])) {
                $options[] = $row['chef'];
            }
        }
    }
    
    $conn->close();
    return $options;
}

// Format datetime for input fields
function formatDatetimeForInput($datetime) {
    if (empty($datetime)) {
        return date('Y-m-d\TH:i');
    }
    
    $dt = new DateTime($datetime);
    return $dt->format('Y-m-d\TH:i');
}

// Load barcodes based on search criteria
$barcodes = getSearchedBarcodes($of_number_search, $full_barcode_search, $piece_name_search, $category_search, $order_str_search, $size_search, $date_to, $page, $items_per_page);
$total_barcodes = getTotalSearchedBarcodes($of_number_search, $full_barcode_search, $piece_name_search, $category_search, $order_str_search, $size_search, $date_to);
$total_pages = ceil($total_barcodes / $items_per_page);

$status_options = getStatusOptions();
$stage_options = getStageOptions();
$chef_options = getChefOptions();

// Fetch barcode details for editing if edit parameter is set
$edit_barcode = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $conn = connectDB();
    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM barcodes WHERE id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $edit_barcode = $result->fetch_assoc();
        }
        $stmt->close();
        $conn->close();
    }
}

// Include header or necessary HTML template
?>