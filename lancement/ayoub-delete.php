<?php
// File: ayoub-delete.php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'jgr');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

// Handle DELETE request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action']) || $data['action'] !== 'delete' || !isset($data['id'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid request or missing ID']));
}

// Prepare delete statement with parameterized query for security
$stmt = $conn->prepare("DELETE FROM ayoub WHERE id = ?");
$stmt->bind_param("i", $data['id']);

// Execute and respond
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>