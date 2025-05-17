<?php
// delete_record.php
// Handle record deletion requests

// Connect to database
$conn = new mysqli('localhost', 'root', '', 'jgr');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get the request data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate request
if (!$data || !isset($data['id']) || !isset($data['action']) || $data['action'] !== 'delete') {
    die(json_encode(['success' => false, 'error' => 'Invalid request']));
}

// Sanitize the ID (ensure it's a number)
$id = intval($data['id']);

// Delete the record
$stmt = $conn->prepare("DELETE FROM ayoub WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>