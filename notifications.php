<?php
require_once __DIR__ . '/barcode_system.php';
$current_view = 'notifications.php';
require_once 'auth_functions.php';
requireLogin('login.php');

$allowed_ips = ['127.0.0.1', '192.168.1.130', '::1', '192.168.0.120' ,'NEW_IP_HERE'];
$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$client_ip = trim(explode(',', $client_ip)[0]);
$is_localhost = in_array($client_ip, ['127.0.0.1', '::1']) || 
               stripos($_SERVER['HTTP_HOST'], 'localhost') !== false;

if (!$is_localhost && !in_array($client_ip, $allowed_ips)) {
    require_once 'die.php';
    die();
}
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
        tr.read td {
        background-color:#b3efcd !important ;
        }
        tr.unread td {
            background-color: #f8f9fa !important;
        }
        /* Remove any conflicting Bootstrap styles */
        table tr {
            background-color: inherit !important;
        }
        .table-responsive {
            margin: 20px 0;
        }
        .actions-column {
            min-width: 250px;
        }
        .btn-group.gap-2 {
            gap: 0.5rem !important;
        }

    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="row justify-content-center">
            <div class="col-md-100">
                <div class="card form-card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Notifications</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Message</th>
                                        <th>Creator</th>
                                        <th>Type</th>
                                        <th>Created At</th>
                                        <th class="actions-column">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $conn = connectDB();
                                    $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
                                    
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $readClass = $row['read'] ? 'read' : 'unread';
                                            $message = $row['message'];
                                            
                                            if (preg_match('/^barcode (.*) created$/', $message, $matches)) {
                                                $formattedMessage = 'New barcode created: <strong>' . htmlspecialchars($matches[1]) . '</strong>';
                                            } else {
                                                $formattedMessage = htmlspecialchars($message);
                                            }
                                            ?>
                                            <tr class="<?= $readClass ?>" data-id="<?= htmlspecialchars($row['id']) ?>">
                                                <td><?= $formattedMessage ?></td>
                                                <td><?= !empty($row['name']) ? htmlspecialchars($row['name']) : '-' ?></td>
                                                <td><?= ucfirst($row['type']) ?></td>
                                                <td><?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></td>
                                                
                                                <td>
                                                    <div class="d-flex justify-content-center">
                                                        <div class="btn-group gap-2">
                                                            <button class="btn btn-sm btn-danger delete-notification">Delete</button>
                                                            <button class="btn btn-sm btn-success mark-as-read">Mark Read</button>
                                                            <button class="btn btn-sm btn-secondary mark-as-unread">Mark Unread</button>
                                                        </div>
                                                    </div>
                                                </td>


                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No notifications found</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Update event listeners to work with table rows
        
        // Mark as Read
        document.querySelectorAll('.mark-as-read').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const row = this.closest('tr');
                const id = row.dataset.id;
                
                fetch('mark_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, read: 1 })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.classList.remove('unread');
                        row.classList.add('read');
                        updateBellCount();
                    }
                });
            });
        });

        // Mark as Unread
        document.querySelectorAll('.mark-as-unread').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const row = this.closest('tr');
                const id = row.dataset.id;
                
                fetch('mark_notification.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, read: 0 })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.classList.remove('read');
                        row.classList.add('unread');
                        updateBellCount();
                    }
                });
            });
        });

        document.querySelectorAll('.delete-notification').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const notificationRow = this.closest('tr');
                const notificationId = notificationRow.dataset.id;

                if (confirm('Are you sure you want to delete this notification?')) {
                    fetch('delete_notification.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: notificationId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            notificationRow.remove();
                            updateBellCount();
                            if (!document.querySelector('tbody').children.length) {
                                document.querySelector('tbody').innerHTML = 
                                    '<tr><td colspan="5" class="text-center">No notifications found</td></tr>';
                            }
                        }
                    });
                }
            });
        });

        function updateBellCount() {
            fetch('get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.fa-bell + .badge');
                    badge.textContent = data.count > 0 ? data.count : '';
                    badge.style.display = data.count > 0 ? 'block' : 'none';
                });
        }
    </script>
</body>
</html>