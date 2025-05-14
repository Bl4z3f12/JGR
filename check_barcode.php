<?php
require_once __DIR__ . '/barcode_system.php';

$ofNumber = $_POST['of_number'] ?? '';
$size = $_POST['size'] ?? '';
$category = $_POST['category'] ?? '';
$pieceName = $_POST['piece_name'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 0);

$conn = connectDB();
if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Build the SQL query dynamically based on category presence
$sql = "SELECT COUNT(*) as count FROM barcodes WHERE of_number = ? AND size = ? ";
$types = "ss";
$params = [$ofNumber, $size];

if (!empty($category)) {
    $sql .= "AND category = ? ";
    $types .= "s";
    $params[] = $category;
} else {
    $sql .= "AND (category IS NULL OR category = '') ";
}

$sql .= "AND piece_name = ?";
$types .= "s";
$params[] = $pieceName;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode(['error' => 'Prepare failed: ' . $conn->error]));
}

$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    die(json_encode(['error' => 'Execute failed: ' . $stmt->error]));
}

$result = $stmt->get_result();
$row = $result->fetch_assoc();
$exists = $row['count'] > 0;

// Generate a formatted barcode for the message
$formattedBarcode = $ofNumber . '-' . $size;
if (!empty($category)) {
    $formattedBarcode .= $category;
}
$formattedBarcode .= '-' . $pieceName;

// Return more detailed and clear messages with specific guidance
echo json_encode([
    'exists' => $exists,
    'found' => $exists,
    'count' => $row['count'],
    'formattedBarcode' => $formattedBarcode,
    'message' => $exists 
        ? "Barcode {$formattedBarcode} found in database!" 
        : "Barcode {$formattedBarcode} was not found in the database. Please check your information"
]);