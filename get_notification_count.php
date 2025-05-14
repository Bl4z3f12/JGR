<?php
require_once __DIR__ . '/barcode_system.php';

$conn = connectDB();
$count = 0;

if ($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE type='new' AND `read`=0");
    if ($result) $count = $result->fetch_assoc()['count'];
}

echo json_encode(['count' => $count]); // $count is 0 when empty
