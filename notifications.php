<?php
require_once __DIR__ . '/barcode_system.php';
$current_view = 'notifications.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" rel="stylesheet">
    <style>
        .list-group-item.unread {
            background-color: #f8f9fa; /* Gray background for unread */
        }
        .list-group-item.read {
            background-color: #d4edda; /* Green background for read */
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card form-card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Notifications</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php
                            $notifications = [];
                            $conn = connectDB();
                            $result = $conn->query("SELECT * FROM notifications ORDER BY date DESC");
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    $notifications[] = [
                                        'id' => $row['id'],
                                        'type' => $row['type'],
                                        'message' => $row['message'],
                                        'date' => date('Y-m-d H:i:s', strtotime($row['date'])), // Added time
                                        'read' => $row['read'],
                                        // Add other fields from the database (e.g., 'name', 'details', etc.)
                                        'name' => $row['name'] ?? '', // Example if 'name' exists
                                        // Include other columns as needed
                                    ];
                                }
                            }
                            
                            foreach ($notifications as $notification) {
                                $readClass = $notification['read'] ? 'read' : 'unread';
                                $message = $notification['message'];
                                // Check if message matches the old format and reformat
                                if (preg_match('/^barcode (.*) created$/', $message, $matches)) {
                                    $formattedMessage = 'new barcode created: <strong>' . htmlspecialchars($matches[1]) . '</strong>';
                                } else {
                                    $formattedMessage = htmlspecialchars($message);
                                }
                                echo '
                                <a href="#" class="list-group-item list-group-item-action ' . $readClass . '" data-id="' . htmlspecialchars($notification['id']) . '">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <h6 class="mb-1">' . $formattedMessage . '</h6>
                                            <!-- Display name -->
                                            '. (!empty($notification['name']) ? '<p class="mb-0">Creator: '.htmlspecialchars($notification['name']).'</p>' : '') .'
                                            <!-- Add other fields here -->
                                        </div>
                                        <small>'.htmlspecialchars($notification['date']).'</small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">'.ucfirst($notification['type']).' notification</small>
                                        <div>
                                            <button class="btn btn-sm btn-outline-danger delete-notification">Delete</button>
                                            <button class="btn btn-sm btn-outline-secondary mark-as-read">Mark as Read</button>
                                            <button class="btn btn-sm btn-outline-secondary mark-as-unread">Mark as Unread</button>
                                        </div>
                                    </div>
                                </a>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update the event listeners
        document.querySelectorAll('.mark-as-read').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const notificationItem = this.closest('.list-group-item');
                const notificationId = notificationItem.dataset.id;
                
                fetch('mark_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: notificationId, read: 1 })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        notificationItem.classList.replace('unread', 'read');
                        updateBellCount();
                    }
                });
            });
        });

        // Similar update for mark-as-unread
        document.querySelectorAll('.mark-as-unread').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const notificationItem = this.closest('.list-group-item');
                const notificationId = notificationItem.dataset.id;
                
                fetch('mark_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: notificationId, read: 0 })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        notificationItem.classList.replace('read', 'unread');
                        updateBellCount();
                    }
                });
            });
        });

        // Function to update the bell count
        function updateBellCount() {
            fetch('get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.fa-bell + .badge');
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                });
        }
        // Add this to the existing script
        document.querySelectorAll('.delete-notification').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const notificationItem = this.closest('.list-group-item');
                const notificationId = notificationItem.dataset.id;

                if (confirm('Are you sure you want to delete this notification?')) {
                    fetch('delete_notification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: notificationId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            notificationItem.remove();
                            updateBellCount(); // Update unread count if needed
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>