*<?php
/**
 * export_excel.php - Exports table data to Excel file
 * 
 * This file handles both the export functionality and the button display.
 * Place this file in the same directory as your main PHP file.
 */

// Check if this is an export request
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Include database connection and run export code
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

    // Get parameters from the form
    $of_number = $_GET['of_number'] ?? '';
    $size = $_GET['size'] ?? '';
    $category = $_GET['category'] ?? '';
    $p_name = $_GET['p_name'] ?? '';
    $stage = $_GET['stage'] ?? '';
    $date = $_GET['date'] ?? date("Y-m-d");

    // Build the same query as in the main file
    $query = "SELECT 
                b.of_number, 
                b.size, 
                b.category, 
                b.piece_name AS p_name,
                b.chef,
                b.stage,
                COUNT(b.id) AS total_count,
                qc.quantity_coupe AS total_stage_quantity,
                qc.principale_quantity AS total_main_quantity,
                qc.solped_client AS solped_client,
                qc.pedido_client AS pedido_client,
                qc.color_tissus AS color_tissus,
                qc.principale_quantity AS principale_quantity,
                qc.quantity_coupe AS quantity_coupe,
                qc.manque AS manque,
                qc.suv_plus AS suv_plus,
                IFNULL(qc.lastupdate, b.last_update) AS latest_update
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

    // Date filter
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

    $query .= " GROUP BY b.of_number, b.size, b.category, b.piece_name, b.chef, b.stage";
    $query .= " ORDER BY b.of_number, b.size, b.category, b.piece_name, b.chef, b.stage";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Calculate totals
    $total_items = 0;
    $total_stage_quantity = 0;
    $total_main_quantity = 0;

    foreach ($results as $row) {
        $total_items += $row['total_count'];
        $total_stage_quantity += $row['total_stage_quantity'] ?? 0;
        $total_main_quantity += $row['total_main_quantity'] ?? 0;
    }

    // Create excel directory if it doesn't exist
    $excel_dir = __DIR__ . '/excel';
    if (!file_exists($excel_dir)) {
        mkdir($excel_dir, 0777, true);
    }

    // Generate a unique filename
    $filename = 'export_data_' . date('Y-m-d_H-i-s') . '.xls';
    $filepath = $excel_dir . '/' . $filename;

    // Start output buffering to capture HTML
    ob_start();

    // Create the Excel content
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>OF Number</th>';
    echo '<th>Size</th>';
    echo '<th>Category</th>';
    echo '<th>Piece Name</th>';
    echo '<th>Chef</th>';
    echo '<th>Stage</th>';
    echo '<th>Total Count</th>';
    echo '<th>Quantity Coupe</th>';
    echo '<th>Principale Quantity</th>';
    echo '<th>SolPed Client</th>';
    echo '<th>Pedido Client</th>';
    echo '<th>Color Tissus</th>';
    echo '<th>Manque</th>';
    echo '<th>Suv Plus</th>';
    echo '<th>Latest Update</th>';
    echo '</tr>';

    // Fill in the data
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['of_number'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['size'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['category'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['p_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['chef'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['stage'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['total_count'] ?? '0') . '</td>';
        echo '<td>' . htmlspecialchars($row['total_stage_quantity'] ?? '0') . '</td>';
        echo '<td>' . htmlspecialchars($row['total_main_quantity'] ?? '0') . '</td>';
        echo '<td>' . htmlspecialchars($row['solped_client'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['pedido_client'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['color_tissus'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['manque'] ?? '0') . '</td>';
        echo '<td>' . htmlspecialchars($row['suv_plus'] ?? '0') . '</td>';
        echo '<td>' . htmlspecialchars($row['latest_update'] ?? '') . '</td>';
        echo '</tr>';
    }

    // Add summary row for totals
    echo '<tr>';
    echo '<td colspan="6"><strong>TOTALS</strong></td>';
    echo '<td><strong>' . $total_items . '</strong></td>';
    echo '<td><strong>' . $total_stage_quantity . '</strong></td>';
    echo '<td><strong>' . $total_main_quantity . '</strong></td>';
    echo '<td colspan="6"></td>';
    echo '</tr>';

    echo '</table>';

    // Get the content from the buffer
    $excel_content = ob_get_clean();
    
    // Save the file
    if (file_put_contents($filepath, $excel_content)) {
        // Inform the user that the file was saved successfully
        echo "Excel file has been saved to: <strong>" . htmlspecialchars($filepath) . "</strong>";
        echo "<br><br>";
        echo "<a href='excel/" . htmlspecialchars($filename) . "' download>Download Excel File</a>";
        echo " | <a href='javascript:history.back()'>Go Back</a>";
    } else {
        echo "Failed to save the Excel file. Please check permissions for the excel directory.";
    }
    exit;
}
?>