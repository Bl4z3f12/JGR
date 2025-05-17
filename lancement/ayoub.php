<?php
$current_view = 'lancement/ayoub.php';
$user_name = "AYYOUB EL OUADGIRI";
$conn = new mysqli('localhost', 'root', '', 'jgr');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}
$result = $conn->query("SELECT * FROM ayoub ORDER BY of_number, last_edit DESC");
$records = [];
$currentOF = null;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}

// Calculate totals for each OF number
$ofTotals = [];
$ofQuantities = []; // To store OF quantities
foreach ($records as $record) {
    $ofNumber = $record['of_number'];
    
    if (!isset($ofTotals[$ofNumber])) {
        $ofTotals[$ofNumber] = [
            'dv' => 0,
            'g' => 0,
            'm' => 0,
            'dos' => 0
        ];
        
        // Store OF quantity for this OF number
        $ofQuantities[$ofNumber] = $record['of_quantity'] ?? 0;
    }
    
    $ofTotals[$ofNumber]['dv'] += $record['dv'];
    $ofTotals[$ofNumber]['g'] += $record['g'];
    $ofTotals[$ofNumber]['m'] += $record['m'];
    $ofTotals[$ofNumber]['dos'] += $record['dos'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        die(json_encode(['success' => false, 'error' => 'Invalid JSON or no data received']));
    }
    $required = ['ofNumber','tailles','packNumber','packOrderStart','packOrderEnd','dv','g','m','dos','ofQuantity'];
    foreach ($required as $key) {
        if (!isset($data[$key])) {
            die(json_encode(['success' => false, 'error' => "Missing field: $key"]));
        }
    }
    if (!empty($data['id'])) {
        $stmt = $conn->prepare("UPDATE ayoub SET 
            of_number=?, tailles=?, pack_number=?, pack_order_start=?, 
            pack_order_end=?, dv=?, g=?, m=?, dos=?, of_quantity=?, last_edit=NOW() 
            WHERE id=?");
        $stmt->bind_param("iiiiiiiiiii", 
            $data['ofNumber'],
            $data['tailles'],
            $data['packNumber'],
            $data['packOrderStart'],
            $data['packOrderEnd'],
            $data['dv'],
            $data['g'],
            $data['m'],
            $data['dos'],
            $data['ofQuantity'],
            $data['id']
        );
    } else {
        $stmt = $conn->prepare("INSERT INTO ayoub 
            (of_number, tailles, pack_number, pack_order_start, 
            pack_order_end, dv, g, m, dos, of_quantity, last_edit) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
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
            $data['ofQuantity']
        );
    }
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
    <title><?php echo $user_name; ?> space</title>
    <link rel="stylesheet" href="styling.css">
    <link rel="shortcut icon" href="../assets/user.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="main-content">
        <div class="modal fade" id="addDataModal" tabindex="-1" aria-labelledby="addDataModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addDataModalLabel">Add New Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newDataForm" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                        <label for="ofNumber" class="form-label">OF JGR</label>
                        <input type="number" class="form-control" id="ofNumber" required>
                        <div class="invalid-feedback">
                            Please provide a valid OF number.
                        </div>
                        </div>
                        <div class="col-md-6">
                        <label for="ofQuantity" class="form-label">General OF Qty</label>
                        <input type="number" class="form-control" id="ofQuantity" required>
                        <div class="invalid-feedback">
                            Please provide a valid General OF Qty.
                        </div>
                        </div>
                        <div class="col-md-6">
                        <label for="tailles" class="form-label">Taille</label>
                        <input type="number" class="form-control" id="tailles" required>
                        <div class="invalid-feedback">
                            Please provide a valid Tailles number.
                        </div>
                        </div>
                        <div class="col-md-6">
                        <label for="packNumber" class="form-label">Pack Number</label>
                        <input type="number" class="form-control" id="packNumber" required>
                        <div class="invalid-feedback">
                            Please provide a valid Pack number.
                        </div>
                        </div>
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
                        <div class="col-md-6 col-lg-3">
                        <label for="dv" class="form-label">Devant</label>
                        <input type="number" class="form-control" id="dv" required>
                        <div class="invalid-feedback">
                            Please provide a valid DV value.
                        </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                        <label for="g" class="form-label">Garniture</label>
                        <input type="number" class="form-control" id="g" required>
                        <div class="invalid-feedback">
                            Please provide a valid G value.
                        </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                        <label for="m" class="form-label">Manche</label>
                        <input type="number" class="form-control" id="m" required>
                        <div class="invalid-feedback">
                            Please provide a valid M value.
                        </div>
                        </div>
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
            <button class="menu-toggle d-md-none" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mobileMenu"
                    aria-controls="mobileMenu">
                <i class="bi bi-list"></i>
            </button>
            <div class="user-info">
                <span class="username"><?php echo $user_name; ?></span>
                <span class="separator">|</span>
                <span class="workspace-title">workspace</span>
            </div>
            <div class="search-container d-none d-md-block">
                <div class="input-group mb-3">
                    <input type="search" id="ofSearchInput" class="form-control" placeholder="Search by OF JGR..." required>
                    <button class="btn btn-primary" type="button" id="ofSearchButton">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
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
        <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu"
                aria-labelledby="mobileMenuLabel">
            <div class="offcanvas-header">
                <h5 id="mobileMenuLabel">Menu</h5>
                <button type="button" class="btn-close btn-close-white text-reset"
                        data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="user-info">
                    <span class="username"><?php echo $user_name; ?></span>
                    <span class="separator">|</span>
                    <span class="workspace-title">workspace</span>
                </div>
                <div class="search-container">
                    <div class="input-group mb-3">
                        <input type="search" id="mobileSearchInput" class="form-control" placeholder="Search by OF JGR...">
                        <button class="btn btn-primary" type="button" id="mobileSearchButton">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
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
        </div>
        <div class="container">
            <div id="cardView" class="d-md-none mb-4">
                <?php if (empty($records)): ?>
                    <div class="empty-message">No data found in database</div>
                <?php else: ?>
                    <?php 
                    $lastOF = null;
                    foreach ($records as $record): 
                        $isNewOF = $lastOF !== $record['of_number'];
                        $isLastOfCurrentOF = false;
                        if ($lastOF !== null && $lastOF !== $record['of_number']) { // New OF coming up
                            ?>
                            <div class="of-total-card">
                                <div class="table-card-title">OF #<?= htmlspecialchars($lastOF) ?> Totals</div>
                                <div class="table-card-row">
                                    <span class="table-card-label">General OF Qty:</span>
                                    <span><?= htmlspecialchars($ofQuantities[$lastOF]) ?></span>
                                </div>
                                <div class="table-card-row">
                                    <span class="table-card-label">Devant Total:</span>
                                    <span><?= htmlspecialchars($ofTotals[$lastOF]['dv']) ?></span>
                                </div>
                                <div class="table-card-row">
                                    <span class="table-card-label">Garniture Total:</span>
                                    <span><?= htmlspecialchars($ofTotals[$lastOF]['g']) ?></span>
                                </div>
                                <div class="table-card-row">
                                    <span class="table-card-label">Manche Total:</span>
                                    <span><?= htmlspecialchars($ofTotals[$lastOF]['m']) ?></span>
                                </div>
                                <div class="table-card-row">
                                    <span class="table-card-label">D.O.S Total:</span>
                                    <span><?= htmlspecialchars($ofTotals[$lastOF]['dos']) ?></span>
                                </div>
                            </div>
                            <?php
                        }
                        $lastOF = $record['of_number'];
                    ?>
                        <div class="table-card <?= $isNewOF ? 'new-of' : '' ?>">
                            <div class="table-card-title">
                                OF #<?= htmlspecialchars($record['of_number']) ?>
                            </div>
                            <?php if ($isNewOF): ?>
                            <div class="table-card-row">
                                <span class="table-card-label">General OF Qty:</span>
                                <span><?= htmlspecialchars($record['of_quantity'] ?? 0) ?></span>
                            </div>
                            <?php endif; ?>
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
                                data-of_quantity="<?= htmlspecialchars($record['of_quantity'] ?? 0) ?>"
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
                    <?php if(!empty($records)): ?>
                    <div class="of-total-card">
                        <div class="table-card-title">OF #<?= htmlspecialchars($lastOF) ?> Totals</div>
                        <div class="table-card-row">
                            <span class="table-card-label">General OF Qty:</span>
                            <span><?= htmlspecialchars($ofQuantities[$lastOF]) ?></span>
                        </div>
                        <div class="table-card-row">
                            <span class="table-card-label">Devant Total:</span>
                            <span><?= htmlspecialchars($ofTotals[$lastOF]['dv']) ?></span>
                        </div>
                        <div class="table-card-row">
                            <span class="table-card-label">Garniture Total:</span>
                            <span><?= htmlspecialchars($ofTotals[$lastOF]['g']) ?></span>
                        </div>
                        <div class="table-card-row">
                            <span class="table-card-label">Manche Total:</span>
                            <span><?= htmlspecialchars($ofTotals[$lastOF]['m']) ?></span>
                        </div>
                        <div class="table-card-row">
                            <span class="table-card-label">D.O.S Total:</span>
                            <span><?= htmlspecialchars($ofTotals[$lastOF]['dos']) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="table-wrapper">
                <table class="data-table table-bordered">
                    <thead>
                        <tr>
                            <th>OF JGR</th>
                            <th>General OF Qty</th>
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
                                <td colspan="11" class="empty-message">No data found in database</td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $lastOF = null;
                            foreach ($records as $index => $record): 
                                $isNewOF = $lastOF !== $record['of_number'];
                                $isLastOfCurrentOF = false;
                                if ($lastOF !== null && $lastOF !== $record['of_number']) {
                                    ?>
                                    <tr class="of-total-row">
                                        <td colspan="4">OF #<?= htmlspecialchars($lastOF) ?> Totals</td>
                                        <td></td>
                                        <td><?= htmlspecialchars($ofTotals[$lastOF]['dv']) ?></td>
                                        <td><?= htmlspecialchars($ofTotals[$lastOF]['g']) ?></td>
                                        <td><?= htmlspecialchars($ofTotals[$lastOF]['m']) ?></td>
                                        <td><?= htmlspecialchars($ofTotals[$lastOF]['dos']) ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <?php
                                }
                                $lastOF = $record['of_number'];
                                if ($index === count($records) - 1 || 
                                    (isset($records[$index + 1]) && $records[$index + 1]['of_number'] !== $record['of_number'])) {
                                    $isLastOfCurrentOF = true;
                                }
                            ?>
                                <tr class="<?= $isNewOF ? 'new-of-section' : '' ?>">
                                    <td><?= htmlspecialchars($record['of_number']) ?></td>
                                    <td><?= $isNewOF ? htmlspecialchars($record['of_quantity'] ?? 0) : '' ?></td>
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
                                        data-of_quantity="<?= htmlspecialchars($record['of_quantity'] ?? 0) ?>"
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
                                <?php 
                                if ($isLastOfCurrentOF && $index === count($records) - 1): ?>
                                    <tr class="of-total-row">
                                        <td colspan="4">OF #<?= htmlspecialchars($record['of_number']) ?> Totals</td>
                                        <td></td>
                                        <td><?= htmlspecialchars($ofTotals[$record['of_number']]['dv']) ?></td>
                                        <td><?= htmlspecialchars($ofTotals[$record['of_number']]['g']) ?></td>
                                        <td><?= htmlspecialchars($ofTotals[$record['of_number']]['m']) ?></td>
                                        <td><?= htmlspecialchars($ofTotals[$record['of_number']]['dos']) ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner-container">
                <div class="spinner-face">JGR</div>
                <div class="spinner-face">FORMENS</div>
                <div class="spinner-face">JGR</div>
                <div class="spinner-face">FORMENS</div>
                <div class="spinner-face">JGR</div>
                <div class="spinner-face">FORMENS</div>
            </div>
            <div class="loading-message" data-message="1"></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const formattedDateTime = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            document.querySelectorAll('#currentDateTime').forEach(el => {
                el.textContent = formattedDateTime;
            });
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);
        document.querySelectorAll('.add-new-btn').forEach(button => {
            button.addEventListener('click', function() {
                const addDataModal = new bootstrap.Modal(document.getElementById('addDataModal'));
                addDataModal.show();
            });
        });
        document.querySelectorAll('.logout-btn').forEach(button => {
            button.addEventListener('click', function() {
                window.location.href = '../start.php';
            });
        });
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {

            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    event.preventDefault();
                    const formData = {
                        id: document.getElementById('recordId').value,
                        ofNumber: document.getElementById('ofNumber').value,
                        ofQuantity: document.getElementById('ofQuantity').value,
                        tailles: document.getElementById('tailles').value,
                        packNumber: document.getElementById('packNumber').value,
                        packOrderStart: document.getElementById('packOrderStart').value,
                        packOrderEnd: document.getElementById('packOrderEnd').value,
                        dv: document.getElementById('dv').value,
                        g: document.getElementById('g').value,
                        m: document.getElementById('m').value,
                        dos: document.getElementById('dos').value,
                    };
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
        document.getElementById('packOrderEnd').addEventListener('change', function() {
            const start = parseInt(document.getElementById('packOrderStart').value) || 0;
            const end = parseInt(this.value) || 0;
            if (end < start) {
                alert('End value must be greater than or equal to Start value');
                this.value = start;
            }
        });

        document.getElementById('packOrderStart').addEventListener('change', function() {
            const start = parseInt(this.value) || 0;
            const end = parseInt(document.getElementById('packOrderEnd').value) || 0;
            if (start > end && end !== 0) {
                alert('Start value must be less than or equal to End value');
                this.value = end;
            }
        });
    </script>
    <script>
        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            const messageEl = overlay.querySelector('.loading-message');
            const randomMessageNum = Math.floor(Math.random() * 5) + 1;
            messageEl.setAttribute('data-message', randomMessageNum);
            overlay.classList.add('show');
            return new Promise(resolve => setTimeout(resolve, 800));
        }
        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.remove('show');
        }
    </script>
</body>
</html>