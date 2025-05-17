<?php
$current_view = 'lancement/ayoub.php';
$user_name = "AYYOUB EL OUADGIRI";

// Database connection
$conn = new mysqli('localhost', 'root', '', 'jgr');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

// Fetch all records from the table, first group by OF number, then sort by last_edit
$result = $conn->query("SELECT * FROM ayoub ORDER BY of_number, last_edit DESC");
$records = [];
$currentOF = null;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        die(json_encode(['success' => false, 'error' => 'Invalid JSON or no data received']));
    }
    $required = ['ofNumber','tailles','packNumber','packOrderStart','packOrderEnd','dv','g','m','dos'];
    foreach ($required as $key) {
        if (!isset($data[$key])) {
            die(json_encode(['success' => false, 'error' => "Missing field: $key"]));
        }
    }
    
    // Prepare insert statement
    if (!empty($data['id'])) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE ayoub SET 
            of_number=?, tailles=?, pack_number=?, pack_order_start=?, 
            pack_order_end=?, dv=?, g=?, m=?, dos=?, last_edit=NOW() 
            WHERE id=?");
        $stmt->bind_param("iiiiiiiiii", 
            $data['ofNumber'],
            $data['tailles'],
            $data['packNumber'],
            $data['packOrderStart'],
            $data['packOrderEnd'],
            $data['dv'],
            $data['g'],
            $data['m'],
            $data['dos'],
            $data['id']
        );
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO ayoub 
            (of_number, tailles, pack_number, pack_order_start, 
            pack_order_end, dv, g, m, dos, last_edit) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiiiiiii", 
            $data['ofNumber'],
            $data['tailles'],
            $data['packNumber'],
            $data['packOrderStart'],
            $data['packOrderEnd'],
            $data['dv'],
            $data['g'],
            $data['m'],
            $data['dos']
        );
    }

    // Execute and respond
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user_name; ?> Workspace</title>
    <link rel="stylesheet" href="styling.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-content">
        <div class="modal fade" id="addDataModal" tabindex="-1" aria-labelledby="addDataModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="addDataModalLabel">Add New Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newDataForm" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <!-- OF Number -->
                        <div class="col-md-6">
                        <label for="ofNumber" class="form-label">OF Number</label>
                        <input type="number" class="form-control" id="ofNumber" required>
                        <div class="invalid-feedback">
                            Please provide a valid OF number.
                        </div>
                        </div>
                        
                        <!-- Tailles -->
                        <div class="col-md-6">
                        <label for="tailles" class="form-label">Taille</label>
                        <input type="number" class="form-control" id="tailles" required>
                        <div class="invalid-feedback">
                            Please provide a valid Tailles number.
                        </div>
                        </div>
                        
                        <!-- Pack Number -->
                        <div class="col-md-6">
                        <label for="packNumber" class="form-label">Pack Number</label>
                        <input type="number" class="form-control" id="packNumber" required>
                        <div class="invalid-feedback">
                            Please provide a valid Pack number.
                        </div>
                        </div>
                        
                        <!-- Pack Order - Start From and End In -->
                        <div class="col-12">
                        <label class="form-label">Pack Order</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">Start from</span>
                                <input type="number" class="form-control" id="packOrderStart" required>
                                <div class="invalid-feedback">
                                Please provide a valid start value.
                                </div>
                            </div>
                            </div>
                            <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">End in</span>
                                <input type="number" class="form-control" id="packOrderEnd" required>
                                <div class="invalid-feedback">
                                Please provide a valid end value.
                                </div>
                            </div>
                            </div>
                        </div>
                        </div>
                        
                        <!-- DV -->
                        <div class="col-md-6 col-lg-3">
                        <label for="dv" class="form-label">Devant</label>
                        <input type="number" class="form-control" id="dv" required>
                        <div class="invalid-feedback">
                            Please provide a valid DV value.
                        </div>
                        </div>
                        
                        <!-- G -->
                        <div class="col-md-6 col-lg-3">
                        <label for="g" class="form-label">Garniture</label>
                        <input type="number" class="form-control" id="g" required>
                        <div class="invalid-feedback">
                            Please provide a valid G value.
                        </div>
                        </div>
                        
                        <!-- M -->
                        <div class="col-md-6 col-lg-3">
                        <label for="m" class="form-label">Manche</label>
                        <input type="number" class="form-control" id="m" required>
                        <div class="invalid-feedback">
                            Please provide a valid M value.
                        </div>
                        </div>
                        
                        <!-- D.O.S -->
                        <div class="col-md-6 col-lg-3">
                        <label for="dos" class="form-label">D.O.S</label>
                        <input type="number" class="form-control" id="dos" required>
                        <div class="invalid-feedback">
                            Please provide a valid D.O.S value.
                        </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="recordId">
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary" id="saveDataBtn">
                        <i class="bi bi-save me-1"></i> Save Data
                        </button>
                        <button type="button" class="btn btn-secondary me-md-2" data-bs-dismiss="modal">Cancel</button>
                    </div>
                    </form>
                </div>
                </div>
            </div>
        </div>

        <div class="workspace-header">
            <div class="user-info">
                <span class="username"><?php echo $user_name; ?></span>
                <span class="separator">|</span>
                <span class="workspace-title">workspace</span>
            </div>
            
            <div class="buttons-container">
                <button class="add-new-btn">
                    <i class="bi bi-plus-circle me-1"></i> New OF_
                </button>
                <button class="logout-btn">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </button>
            </div>
            
            <div class="datetime-display">
                <span id="currentDateTime"></span>
            </div>
        </div>
        
        <div class="container">
            <!-- Card view for mobile -->
            <div id="cardView" class="d-md-none mb-4">
                <?php if (empty($records)): ?>
                    <div class="empty-message">No data found in database</div>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <div class="table-card">
                            <div class="table-card-title">
                                OF #<?= htmlspecialchars($record['of_number']) ?>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Taille:</span>
                                <span><?= htmlspecialchars($record['tailles']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Pack:</span>
                                <span>#<?= htmlspecialchars($record['pack_number']) ?> (<?= htmlspecialchars($record['pack_order_start']) ?> - <?= htmlspecialchars($record['pack_order_end']) ?>)</span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Devant:</span>
                                <span><?= htmlspecialchars($record['dv']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Garniture:</span>
                                <span><?= htmlspecialchars($record['g']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Manche:</span>
                                <span><?= htmlspecialchars($record['m']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">D.O.S:</span>
                                <span><?= htmlspecialchars($record['dos']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Last edit:</span>
                                <span><?= date('d/m/Y H:i:s', strtotime($record['last_edit'])) ?></span>
                            </div>
                            <div class="mt-2 d-flex justify-content-between">
                                <a href="#" class="btn btn-sm btn-outline-primary edit-btn" 
                                   data-id="<?= $record['id'] ?>"
                                   data-of_number="<?= htmlspecialchars($record['of_number']) ?>"
                                   data-tailles="<?= htmlspecialchars($record['tailles']) ?>"
                                   data-pack_number="<?= htmlspecialchars($record['pack_number']) ?>"
                                   data-pack_order_start="<?= htmlspecialchars($record['pack_order_start']) ?>"
                                   data-pack_order_end="<?= htmlspecialchars($record['pack_order_end']) ?>"
                                   data-dv="<?= htmlspecialchars($record['dv']) ?>"
                                   data-g="<?= htmlspecialchars($record['g']) ?>"
                                   data-m="<?= htmlspecialchars($record['m']) ?>"
                                   data-dos="<?= htmlspecialchars($record['dos']) ?>"
                                >
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-danger delete-btn">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        
           <!-- Traditional table view with updated row styling -->
            <div class="table-wrapper">
                <table class="data-table table-bordered">
                    <thead>
                        <tr>
                            <th>OF number</th>
                            <th>Taille</th>
                            <th>Pack number</th>
                            <th>Pack order</th>
                            <th>Devant</th>
                            <th>Garniture</th>
                            <th>Manche</th>
                            <th>D.O.S</th>
                            <th>Last edit</th>
                            <th class="tools-cell">Tools</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="10" class="empty-message">No data found in database</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $lastOF = null;
                            foreach ($records as $record): 
                                $isNewOF = $lastOF !== $record['of_number'];
                                $lastOF = $record['of_number'];
                            ?>
                                <tr class="<?= $isNewOF ? 'new-of-section' : '' ?>">
                                    <td><?= htmlspecialchars($record['of_number']) ?></td>
                                    <td><?= htmlspecialchars($record['tailles']) ?></td>
                                    <td><?= htmlspecialchars($record['pack_number']) ?></td>
                                    <td><?= htmlspecialchars($record['pack_order_start']) ?> - <?= htmlspecialchars($record['pack_order_end']) ?></td>
                                    <td><?= htmlspecialchars($record['dv']) ?></td>
                                    <td><?= htmlspecialchars($record['g']) ?></td>
                                    <td><?= htmlspecialchars($record['m']) ?></td>
                                    <td><?= htmlspecialchars($record['dos']) ?></td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($record['last_edit'])) ?></td>
                                    <td class="tools-cell">
                                        <a href="#" class="edit-btn" 
                                        data-id="<?= $record['id'] ?>"
                                        data-of_number="<?= htmlspecialchars($record['of_number']) ?>"
                                        data-tailles="<?= htmlspecialchars($record['tailles']) ?>"
                                        data-pack_number="<?= htmlspecialchars($record['pack_number']) ?>"
                                        data-pack_order_start="<?= htmlspecialchars($record['pack_order_start']) ?>"
                                        data-pack_order_end="<?= htmlspecialchars($record['pack_order_end']) ?>"
                                        data-dv="<?= htmlspecialchars($record['dv']) ?>"
                                        data-g="<?= htmlspecialchars($record['g']) ?>"
                                        data-m="<?= htmlspecialchars($record['m']) ?>"
                                        data-dos="<?= htmlspecialchars($record['dos']) ?>"
                                        ><i class="bi bi-pencil-square"></i></a>
                                        <a href="#" class="delete-btn"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Updated card view for mobile -->
            <div id="cardView" class="d-md-none mb-4">
                <?php if (empty($records)): ?>
                    <div class="empty-message">No data found in database</div>
                <?php else: ?>
                    <?php 
                    $lastOF = null;
                    foreach ($records as $record): 
                        $isNewOF = $lastOF !== $record['of_number'];
                        $lastOF = $record['of_number'];
                    ?>
                        <div class="table-card <?= $isNewOF ? 'new-of' : '' ?>">
                            <div class="table-card-title">
                                OF #<?= htmlspecialchars($record['of_number']) ?>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Taille:</span>
                                <span><?= htmlspecialchars($record['tailles']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Pack:</span>
                                <span>#<?= htmlspecialchars($record['pack_number']) ?> (<?= htmlspecialchars($record['pack_order_start']) ?> - <?= htmlspecialchars($record['pack_order_end']) ?>)</span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Devant:</span>
                                <span><?= htmlspecialchars($record['dv']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Garniture:</span>
                                <span><?= htmlspecialchars($record['g']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Manche:</span>
                                <span><?= htmlspecialchars($record['m']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">D.O.S:</span>
                                <span><?= htmlspecialchars($record['dos']) ?></span>
                            </div>
                            <div class="table-card-row">
                                <span class="table-card-label">Last edit:</span>
                                <span><?= date('d/m/Y H:i:s', strtotime($record['last_edit'])) ?></span>
                            </div>
                            <div class="mt-2 d-flex justify-content-between">
                                <a href="#" class="btn btn-sm btn-outline-primary edit-btn" 
                                data-id="<?= $record['id'] ?>"
                                data-of_number="<?= htmlspecialchars($record['of_number']) ?>"
                                data-tailles="<?= htmlspecialchars($record['tailles']) ?>"
                                data-pack_number="<?= htmlspecialchars($record['pack_number']) ?>"
                                data-pack_order_start="<?= htmlspecialchars($record['pack_order_start']) ?>"
                                data-pack_order_end="<?= htmlspecialchars($record['pack_order_end']) ?>"
                                data-dv="<?= htmlspecialchars($record['dv']) ?>"
                                data-g="<?= htmlspecialchars($record['g']) ?>"
                                data-m="<?= htmlspecialchars($record['m']) ?>"
                                data-dos="<?= htmlspecialchars($record['dos']) ?>"
                                >
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-danger delete-btn">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to update current date and time in dd/mm/yyyy hh:mm:ss format
        function updateDateTime() {
            const now = new Date();
            
            // Format the date as dd/mm/yyyy
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            
            // Format the time as hh:mm:ss
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            // Combine date and time in the requested format
            const formattedDateTime = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            
            document.getElementById('currentDateTime').textContent = formattedDateTime;
        }
        
        // Initial call and set interval to update every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
                
        // Add event listener to the "Add New Data" button to open the modal
        document.querySelector('.add-new-btn').addEventListener('click', function() {
            const addDataModal = new bootstrap.Modal(document.getElementById('addDataModal'));
            addDataModal.show();
        });
        
        // Add event listener to the "Logout" button to redirect to start.php
        document.querySelector('.logout-btn').addEventListener('click', function() {
            window.location.href = '../start.php';
        });
        
        // Form validation
        (function() {
            'use strict';
            
            // Fetch all forms we want to apply validation to
            const forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.from(forms).forEach(form => {

            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    event.preventDefault();
                    
                    // Get form data
                    const formData = {
                        id: document.getElementById('recordId').value,
                        ofNumber: document.getElementById('ofNumber').value,
                        tailles: document.getElementById('tailles').value,
                        packNumber: document.getElementById('packNumber').value,
                        packOrderStart: document.getElementById('packOrderStart').value,
                        packOrderEnd: document.getElementById('packOrderEnd').value,
                        dv: document.getElementById('dv').value,
                        g: document.getElementById('g').value,
                        m: document.getElementById('m').value,
                        dos: document.getElementById('dos').value,
                    };

                    // Send data to server
                    fetch('ayoub.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Data saved successfully!');
                            const addDataModal = bootstrap.Modal.getInstance(document.getElementById('addDataModal'));
                            addDataModal.hide();
                            form.reset();
                            form.classList.remove('was-validated');
                            window.location.reload(); // Refresh to show new data
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving.');
                    });
                }
                form.classList.add('was-validated');
            }, false);
            });
        })();
        
        // Ensure End value is greater than Start value
        document.getElementById('packOrderEnd').addEventListener('change', function() {
            const start = parseInt(document.getElementById('packOrderStart').value);
            const end = parseInt(this.value);
            
            if (end < start) {
            this.setCustomValidity('End value must be greater than Start value');
            } else {
            this.setCustomValidity('');
            }
        });
        
        document.getElementById('packOrderStart').addEventListener('change', function() {
            // Trigger validation on the end field when start changes
            const endField = document.getElementById('packOrderEnd');
            if (endField.value) {
            const event = new Event('change');
            endField.dispatchEvent(event);
            }
        });
        
        // Responsive display handling
        window.addEventListener('DOMContentLoaded', function() {
            // Show card view on small screens
            function handleResponsiveView() {
                const cardView = document.getElementById('cardView');
                const tableCards = document.querySelectorAll('.table-card');
                
                if (window.innerWidth < 768) {
                    // Show cards on mobile
                    tableCards.forEach(card => {
                        card.style.display = 'block';
                    });
                } else {
                    // Hide cards on desktop
                    tableCards.forEach(card => {
                        card.style.display = 'none';
                    });
                }
            }
            
            // Initial call
            handleResponsiveView();
            
            // Update on resize
            window.addEventListener('resize', handleResponsiveView);
        });

        // Edit button click handler
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                // Fill modal fields with data attributes
                document.getElementById('recordId').value = this.getAttribute('data-id');
                document.getElementById('ofNumber').value = this.getAttribute('data-of_number');
                document.getElementById('tailles').value = this.getAttribute('data-tailles');
                document.getElementById('packNumber').value = this.getAttribute('data-pack_number');
                document.getElementById('packOrderStart').value = this.getAttribute('data-pack_order_start');
                document.getElementById('packOrderEnd').value = this.getAttribute('data-pack_order_end');
                document.getElementById('dv').value = this.getAttribute('data-dv');
                document.getElementById('g').value = this.getAttribute('data-g');
                document.getElementById('m').value = this.getAttribute('data-m');
                document.getElementById('dos').value = this.getAttribute('data-dos');
                // Change button text to "Update"
                document.getElementById('saveDataBtn').innerHTML = '<i class="bi bi-save me-1"></i> Update Data';
                // Show modal
                const addDataModal = new bootstrap.Modal(document.getElementById('addDataModal'));
                addDataModal.show();
            });
        });

        // When opening for new data, clear the hidden id and reset button text
        document.querySelector('.add-new-btn').addEventListener('click', function() {
            document.getElementById('recordId').value = '';
            document.getElementById('saveDataBtn').innerHTML = '<i class="bi bi-save me-1"></i> Save Data';
        });
        // Add event listener to all delete buttons
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Find the closest row or card
                let recordElement = this.closest('tr') || this.closest('.table-card');
                let recordId;
                
                // Get the record ID - we need to determine if this is a table row or card view
                if (this.closest('tr')) {
                    // For table rows, get ID from the edit button in the same row
                    recordId = recordElement.querySelector('.edit-btn').getAttribute('data-id');
                } else {
                    // For cards, get ID directly from the edit button in the card
                    recordId = recordElement.querySelector('.edit-btn').getAttribute('data-id');
                }
                
                // Show confirmation dialog
                if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                    // If confirmed, send delete request to server
                    deleteRecord(recordId, recordElement);
                }
            });
        });

        // Function to handle the deletion via AJAX
        function deleteRecord(id, element) {
            // Create deletion data
            const deleteData = {
                action: 'delete',
                id: id
            };
            
            // Send data to server
            fetch('ayoub-delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(deleteData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the element from DOM
                    element.remove();
                    alert('Record deleted successfully!');
                    
                    // If no records left, show "No data found" message
                    const tableBody = document.querySelector('.data-table tbody');
                    const cardView = document.getElementById('cardView');
                    
                    if (tableBody && tableBody.children.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="10" class="empty-message">No data found in database</td></tr>';
                    }
                    
                    if (cardView && cardView.children.length === 0) {
                        cardView.innerHTML = '<div class="empty-message">No data found in database</div>';
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the record.');
            });
        }
    </script>
</body>
</html>