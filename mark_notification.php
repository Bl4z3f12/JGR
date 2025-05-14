<?php
require_once __DIR__ . '/barcode_system.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json'); // Ensure JSON response

$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die(json_encode(['success' => false, 'error' => 'Invalid JSON input']));
}

$id = $input['id'] ?? null;
$read = $input['read'] ?? 0;

// Validate ID
if (!$id || !is_numeric($id)) {
    die(json_encode(['success' => false, 'error' => 'Invalid notification ID']));
}

$conn = connectDB();
if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// Use backticks for reserved keyword `read`
$stmt = $conn->prepare("UPDATE notifications SET `read` = ? WHERE id = ?");
if (!$stmt) {
    die(json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]));
}

$stmt->bind_param("ii", $read, $id);
$success = $stmt->execute();

if (!$success) {
    die(json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]));
}

echo json_encode(['success' => true]);
?>