<?php
require_once __DIR__ . '/barcode_system.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid request']));
}

$id = $input['id'];
$conn = connectDB();

if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();

if ($success && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Delete failed']);
}
?>