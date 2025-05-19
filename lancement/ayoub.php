<?php
$current_view = 'lancement/ayoub.php';
$user_name = "AYYOUB EL OUADGIRI";
$conn = new mysqli('localhost', 'root', '', 'lancement');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}
$ofTypeLabels = [
    1 => 'VEST ISOLEE',
    3 => 'GILET',
    4 => 'MANTEAU',
    5 => 'COSTUME 2PC',
    6 => 'COSTUME 3PC'
];
$ofCategoryLabels = [
    1 => 'R',
    2 => 'C',
    3 => 'L',
    4 => 'LL',
    5 => 'CC',
    6 => 'N'
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        die(json_encode(['success' => false, 'error' => 'Invalid ID provided']));
    }
    
    $stmt = $conn->prepare("DELETE FROM ayoub WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit();
}
$result = $conn->query("SELECT * FROM ayoub ORDER BY last_edit DESC, of_number, id DESC");
$records = [];
$currentOF = null;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
}
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
        
        $ofQuantities[$ofNumber] = $record['of_quantity'] ?? 0;
    }
    
    $ofTotals[$ofNumber]['dv'] += $record['dv'];
    $ofTotals[$ofNumber]['g'] += $record['g'];
    $ofTotals[$ofNumber]['m'] += $record['m'];
    $ofTotals[$ofNumber]['dos'] += $record['dos'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['_method'])) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        die(json_encode(['success' => false, 'error' => 'Invalid JSON or no data received']));
    }
    $required = ['ofNumber','ofType','ofCategory','tailles','packNumber','packOrderStart','packOrderEnd','dv','g','m','dos','ofQuantity'];
    foreach ($required as $key) {
        if (!isset($data[$key])) {
            die(json_encode(['success' => false, 'error' => "Missing field: $key"]));
        }
    }
    if (!empty($data['id'])) {
        $stmt = $conn->prepare("UPDATE ayoub SET 
            of_number=?, of_quantity=?, of_type=?, of_category=?, tailles=?, pack_number=?, pack_order_start=?, 
            pack_order_end=?, dv=?, g=?, m=?, dos=?, last_edit=NOW() 
            WHERE id=?");
        $stmt->bind_param("iissiiiiiiiii", 
            $data['ofNumber'],
            $data['ofQuantity'],
            $data['ofType'],
            $data['ofCategory'],
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
        $stmt = $conn->prepare("INSERT INTO ayoub 
            (of_number, of_quantity, of_type, of_category, tailles, pack_number, pack_order_start, 
            pack_order_end, dv, g, m, dos, last_edit) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iissiiiiiiii", 
            $data['ofNumber'],
            $data['ofQuantity'],
            $data['ofType'],
            $data['ofCategory'],
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
                        <label for="ofType" class="form-label">OF Type</label>
                        <select class="form-select" id="ofType" required>
                            <option value="">Choose...</option>
                            <option value="VEST ISOLEE">VEST ISOLEE</option>
                            <option value="GILET">GILET</option>
                            <option value="MANTEAU">MANTEAU</option>
                            <option value="COSTUME 2PC">COSTUME 2PC</option>
                            <option value="COSTUME 3PC">COSTUME 3PC</option>
                        </select>
                        <div class="invalid-feedback">
                            Please provide a valid OF Type.
                        </div>
                        </div>
                        <div class="col-md-6">
                        <label for="ofCategory" class="form-label">OF Category</label>
                        <select class="form-select" id="ofCategory">
                            <option value="">Choose...</option>
                            <option value="R">R</option>
                            <option value="C">C</option>
                            <option value="L">L</option>
                            <option value="LL">LL</option>
                            <option value="CC">CC</option>
                            <option value="N">N</option>
                        </select>
                        <div class="invalid-feedback">
                            Please provide a valid OF category.
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
                    <img width="45" height="45" src="https://img.icons8.com/external-flatart-icons-solid-flatarticons/64/external-menu-ux-and-ui-flatart-icons-solid-flatarticons"/>
            </button>
            <div class="userdate">
                <div class="user-info">
                    <span class="username"><?php echo $user_name; ?></span>
                    <span class="separator">|</span>
                    <span class="workspace-title">workspace</span><br>
                </div>
                <div class="datetime-display">
                    <span id="currentDateTime"></span>
                </div>
            </div>
            <div class="search-container d-none d-md-block">
                <div class="input-group mb-3">
                    <input type="search" id="offilter" class="form-control" placeholder="Filter by OF JGR..." required>
                    <input type="date" id="datefilter" class="form-control" placeholder="Filter by date">
                    <button class="btn btn-primary" type="button" id="offilter">
                    <i class="bi bi-funnel-fill"></i> Filter
                    </button>
                    <button class="btn btn-outline-dark" type="button" id="resetoffilter">
                    <i class="fa-solid fa-broom"></i> Reset
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
        </div>
        <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu"
                aria-labelledby="mobileMenuLabel">
            <div class="offcanvas-header">
                <h5 id="mobileMenuLabel">Menu</h5>
                <button type="button" class="btn-close btn-close-white text-reset"
                        data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="userdate">
                    <div class="user-info">
                        <span class="username"><?php echo $user_name; ?></span>
                        <span class="separator">|</span>
                        <span class="workspace-title">workspace</span><br>
                    </div>
                    <div class="datetime-display">
                        <span id="currentDateTime"></span>
                    </div>
                </div>
                <div class="search-container">
                    <div class="input-group mb-3">
                        <input type="search" id="offilter" class="form-control" placeholder="Filter by OF JGR...">
                        <button class="btn btn-primary" type="button" id="offilter">
                        <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                        <button class="btn btn-outline-dark" type="button" id="resetoffilter">
                        <i class="fa-solid fa-broom"></i> Reset
                        </button>
                    </div>
                    <div class="input-group mb-3">
                        <input type="date" id="datefilter" class="form-control" placeholder="Filter by date">
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
            </div>
        </div>
        <ul class="nav nav-tabs mt-3 mb-3" id="ofTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="dynamic-tab" data-bs-toggle="tab" data-bs-target="#dynamic" type="button" role="tab" aria-controls="dynamic" aria-selected="true">Dynamic</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="static-tab" data-bs-toggle="tab" data-bs-target="#static" type="button" role="tab" aria-controls="static" aria-selected="false">Static</button>
          </li>
        </ul>
        <div class="tab-content" id="ofTabsContent">
          <div class="tab-pane fade show active" id="dynamic" role="tabpanel" aria-labelledby="dynamic-tab">
            <div class="table-responsive d-none d-md-block">
                <div class="table-wrapper">
                    <table class="data-table table-bordered">
                        <thead>
                            <tr>
                                <th>OF JGR</th>
                                <th>General OF Qty</th>
                                <th>OF Type</th>
                                <th>OF Category</th>
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
                                    <td colspan="13" class="empty-message">No data found in database</td>
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
                                            <td colspan="6">OF #<?= htmlspecialchars($lastOF) ?> Totals</td>
                                            <td></td>
                                            <td<?= $ofTotals[$lastOF]['dv'] == $ofQuantities[$lastOF] ? ' class="bg-success text-white"' : '' ?>>
                                                <?= htmlspecialchars($ofTotals[$lastOF]['dv']) ?>
                                            </td>
                                            <td<?= $ofTotals[$lastOF]['g'] == $ofQuantities[$lastOF] ? ' class="bg-success text-white"' : '' ?>>
                                                <?= htmlspecialchars($ofTotals[$lastOF]['g']) ?>
                                            </td>
                                            <td<?= $ofTotals[$lastOF]['m'] == $ofQuantities[$lastOF] ? ' class="bg-success text-white"' : '' ?>>
                                                <?= htmlspecialchars($ofTotals[$lastOF]['m']) ?>
                                            </td>
                                            <td<?= $ofTotals[$lastOF]['dos'] == $ofQuantities[$lastOF] ? ' class="bg-success text-white"' : '' ?>>
                                                <?= htmlspecialchars($ofTotals[$lastOF]['dos']) ?>
                                            </td>
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
                                        <td><?= $isNewOF ? htmlspecialchars($record['of_quantity']) : '' ?></td>
                                        <td><?= htmlspecialchars($record['of_type']) ?></td>
                                        <td><?= htmlspecialchars($record['of_category']) ?></td>
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
                                            data-of_quantity="<?= htmlspecialchars($record['of_quantity']) ?>"
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
                                            <td colspan="6">OF #<?= htmlspecialchars($record['of_number']) ?> Totals</td>
                                            <td></td>
                                            <td<?= $ofTotals[$record['of_number']]['dv'] == $ofQuantities[$record['of_number']] ? ' class="bg-success text-white"' : '' ?>>
                                                <?= htmlspecialchars($ofTotals[$record['of_number']]['dv']) ?>
                                            </td>
                                            <td<?= $ofTotals[$record['of_number']]['g'] == $ofQuantities[$record['of_number']] ? ' class="bg-success text-white"' : '' ?>>
                                                <?= htmlspecialchars($ofTotals[$record['of_number']]['g']) ?>
                                            </td>
                                            <td<?= $ofTotals[$record['of_number']]['m'] == $ofQuantities[$record['of_number']] ? ' class="bg-success text-white"' : '' ?>>
                                                <?= htmlspecialchars($ofTotals[$record['of_number']]['m']) ?>
                                            </td>
                                            <td<?= $ofTotals[$record['of_number']]['dos'] == $ofQuantities[$record['of_number']] ? ' class="bg-success text-white"' : '' ?>>
                                                <?= htmlspecialchars($ofTotals[$record['of_number']]['dos']) ?>
                                            </td>
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
            
            <div class="tab-pane fade" id="static" role="tabpanel" aria-labelledby="static-tab">
                <!-- Desktop version of static cards -->
                <div class="d-none d-md-block">
                    <div class="desktop-cards-container">
                        <?php if (empty($records)): ?>
                            <div class="alert alert-info">No data found in database</div>
                        <?php else: ?>
                            <?php
                            $lastOF = null;
                            foreach ($records as $index => $record): 
                                $isNewOF = $lastOF !== $record['of_number'];
                                $isLastOfCurrentOF = false;
                                if ($lastOF !== null && $lastOF !== $record['of_number']) {
                                    ?>
                                    <div class="card mb-3 of-total-card bg-light">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="card-title mb-0">OF #<?= htmlspecialchars($lastOF) ?> Totals</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-12">
                                                    <div class="total-item">
                                                        <span class="label">General OF Qty:</span>
                                                        <span class="value fw-bold"><?= htmlspecialchars($ofQuantities[$lastOF]) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-3">
                                                    <div class="total-item">
                                                        <span class="label">Devant:</span>
                                                        <span class="value<?= $ofTotals[$lastOF]['dv'] == $ofQuantities[$lastOF] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                            <?= htmlspecialchars($ofTotals[$lastOF]['dv']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="total-item">
                                                        <span class="label">Garniture:</span>
                                                        <span class="value<?= $ofTotals[$lastOF]['g'] == $ofQuantities[$lastOF] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                            <?= htmlspecialchars($ofTotals[$lastOF]['g']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="total-item">
                                                        <span class="label">Manche:</span>
                                                        <span class="value<?= $ofTotals[$lastOF]['m'] == $ofQuantities[$lastOF] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                            <?= htmlspecialchars($ofTotals[$lastOF]['m']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="total-item">
                                                        <span class="label">D.O.S:</span>
                                                        <span class="value<?= $ofTotals[$lastOF]['dos'] == $ofQuantities[$lastOF] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                            <?= htmlspecialchars($ofTotals[$lastOF]['dos']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                                $lastOF = $record['of_number'];
                                if ($index === count($records) - 1 || 
                                    (isset($records[$index + 1]) && $records[$index + 1]['of_number'] !== $record['of_number'])) {
                                    $isLastOfCurrentOF = true;
                                }
                            ?>
                                <?php if ($isNewOF): ?>
                                    <div class="card mb-4 of-section-card">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">OF #<?= htmlspecialchars($record['of_number']) ?> (<?= htmlspecialchars($record['of_type']) ?> - <?= htmlspecialchars($record['of_category']) ?>)</h5>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="row mx-0">
                                <?php endif; ?>
                                
                                <div class="col-md-6 col-lg-4 p-2">
                                    <div class="card h-100 border-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Pack #<?= htmlspecialchars($record['pack_number']) ?> (Taille: <?= htmlspecialchars($record['tailles']) ?>)</h6>
                                            <p class="card-text mb-1">
                                                <small class="text-muted">Pack Order: </small>
                                                <?= htmlspecialchars($record['pack_order_start']) ?> - <?= htmlspecialchars($record['pack_order_end']) ?>
                                            </p>
                                            <div class="row text-center mb-2 mt-3">
                                                <div class="col-6">
                                                    <div class="small text-muted">Devant</div>
                                                    <div class="fw-bold text-primary"><?= htmlspecialchars($record['dv']) ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="small text-muted">Garniture</div>
                                                    <div class="fw-bold text-primary"><?= htmlspecialchars($record['g']) ?></div>
                                                </div>
                                                <div class="col-6 mt-2">
                                                    <div class="small text-muted">Manche</div>
                                                    <div class="fw-bold text-primary"><?= htmlspecialchars($record['m']) ?></div>
                                                </div>
                                                <div class="col-6 mt-2">
                                                    <div class="small text-muted">D.O.S</div>
                                                    <div class="fw-bold text-primary"><?= htmlspecialchars($record['dos']) ?></div>
                                                </div>
                                            </div>
                                            <div class="text-muted small">Last edit: <?= date('d/m/Y H:i:s', strtotime($record['last_edit'])) ?></div>
                                            <div class="d-flex justify-content-end gap-2 mt-2">
                                                <a href="#" class="btn btn-sm btn-outline-primary edit-btn" 
                                                    data-id="<?= $record['id'] ?>"
                                                    data-of_number="<?= htmlspecialchars($record['of_number']) ?>"
                                                    data-of_quantity="<?= htmlspecialchars($record['of_quantity']) ?>"
                                                    data-of_type="<?= htmlspecialchars($record['of_type']) ?>"
                                                    data-of_category="<?= htmlspecialchars($record['of_category']) ?>"
                                                    data-tailles="<?= htmlspecialchars($record['tailles']) ?>"
                                                    data-pack_number="<?= htmlspecialchars($record['pack_number']) ?>"
                                                    data-pack_order_start="<?= htmlspecialchars($record['pack_order_start']) ?>"
                                                    data-pack_order_end="<?= htmlspecialchars($record['pack_order_end']) ?>"
                                                    data-dv="<?= htmlspecialchars($record['dv']) ?>"
                                                    data-g="<?= htmlspecialchars($record['g']) ?>"
                                                    data-m="<?= htmlspecialchars($record['m']) ?>"
                                                    data-dos="<?= htmlspecialchars($record['dos']) ?>"
                                                ><i class="bi bi-pencil-square"></i></a>
                                                <a href="#" class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $record['id'] ?>"><i class="bi bi-trash"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($isLastOfCurrentOF): ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php 
                                if ($isLastOfCurrentOF && $index === count($records) - 1): ?>
                                    <div class="card mb-3 of-total-card bg-light">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="card-title mb-0">OF #<?= htmlspecialchars($record['of_number']) ?> Totals</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-12">
                                                    <div class="total-item">
                                                        <span class="label">General OF Qty:</span>
                                                        <span class="value fw-bold"><?= htmlspecialchars($ofQuantities[$record['of_number']]) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-3">
                                                    <div class="total-item">
                                                        <span class="label">Devant:</span>
                                                        <span class="value<?= $ofTotals[$record['of_number']]['dv'] == $ofQuantities[$record['of_number']] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                            <?= htmlspecialchars($ofTotals[$record['of_number']]['dv']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="total-item">
                                                        <span class="label">Garniture:</span>
                                                        <span class="value<?= $ofTotals[$record['of_number']]['g'] == $ofQuantities[$record['of_number']] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                            <?= htmlspecialchars($ofTotals[$record['of_number']]['g']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="total-item">
                                                        <span class="label">Manche:</span>
                                                        <span class="value<?= $ofTotals[$record['of_number']]['m'] == $ofQuantities[$record['of_number']] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                            <?= htmlspecialchars($ofTotals[$record['of_number']]['m']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="total-item">
                                                        <span class="label">D.O.S:</span>
                                                        <span class="value<?= $ofTotals[$record['of_number']]['dos'] == $ofQuantities[$record['of_number']] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                            <?= htmlspecialchars($ofTotals[$record['of_number']]['dos']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Original mobile version of static cards -->
                <div class="d-md-none" id="static-mobile">
                    <div class="mobile-cards-container">
                    <?php if (empty($records)): ?>
                        <div class="alert alert-info">No data found in database</div>
                    <?php else: ?>
                        <?php
                        $lastOF = null;
                        foreach ($records as $index => $record): 
                            $isNewOF = $lastOF !== $record['of_number'];
                            $isLastOfCurrentOF = false;
                            if ($lastOF !== null && $lastOF !== $record['of_number']) {
                                ?>
                                <div class="card mb-3 of-total-card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">OF #<?= htmlspecialchars($lastOF) ?> Totals</h6>
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <div class="total-item">
                                                    <span class="label">General OF Qty:</span>
                                                    <span class="value fw-bold"><?= htmlspecialchars($ofQuantities[$lastOF]) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6 col-sm-3">
                                                <div class="total-item">
                                                    <span class="label">Devant:</span>
                                                    <span class="value<?= $ofTotals[$lastOF]['dv'] == $ofQuantities[$lastOF] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                        <?= htmlspecialchars($ofTotals[$lastOF]['dv']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <div class="total-item">
                                                    <span class="label">Garniture:</span>
                                                    <span class="value<?= $ofTotals[$lastOF]['g'] == $ofQuantities[$lastOF] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                        <?= htmlspecialchars($ofTotals[$lastOF]['g']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <div class="total-item">
                                                    <span class="label">Manche:</span>
                                                    <span class="value<?= $ofTotals[$lastOF]['m'] == $ofQuantities[$lastOF] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                        <?= htmlspecialchars($ofTotals[$lastOF]['m']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-6 col-sm-3">
                                                <div class="total-item">
                                                    <span class="label">D.O.S:</span>
                                                    <span class="value<?= $ofTotals[$lastOF]['dos'] == $ofQuantities[$lastOF] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                        <?= htmlspecialchars($ofTotals[$lastOF]['dos']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            $lastOF = $record['of_number'];
                            if ($index === count($records) - 1 || 
                                (isset($records[$index + 1]) && $records[$index + 1]['of_number'] !== $record['of_number'])) {
                                $isLastOfCurrentOF = true;
                            }
                        ?>
                        <?php 
                        if ($isLastOfCurrentOF && $index === count($records) - 1): ?>
                            <div class="card mb-3 of-total-card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">OF #<?= htmlspecialchars($record['of_number']) ?> Totals</h6>
                                    <div class="row mb-2">
                                        <div class="col-12">
                                            <div class="total-item">
                                                <span class="label">General OF Qty:</span>
                                                <span class="value fw-bold"><?= htmlspecialchars($ofQuantities[$record['of_number']]) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6 col-sm-3">
                                            <div class="total-item">
                                                <span class="label">Devant:</span>
                                                <span class="value<?= $ofTotals[$record['of_number']]['dv'] == $ofQuantities[$record['of_number']] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                    <?= htmlspecialchars($ofTotals[$record['of_number']]['dv']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-6 col-sm-3">
                                            <div class="total-item">
                                                <span class="label">Garniture:</span>
                                                <span class="value<?= $ofTotals[$record['of_number']]['g'] == $ofQuantities[$record['of_number']] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                    <?= htmlspecialchars($ofTotals[$record['of_number']]['g']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-6 col-sm-3">
                                            <div class="total-item">
                                                <span class="label">Manche:</span>
                                                <span class="value<?= $ofTotals[$record['of_number']]['m'] == $ofQuantities[$record['of_number']] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                    <?= htmlspecialchars($ofTotals[$record['of_number']]['m']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-6 col-sm-3">
                                            <div class="total-item">
                                                <span class="label">D.O.S:</span>
                                                <span class="value<?= $ofTotals[$record['of_number']]['dos'] == $ofQuantities[$record['of_number']] ? ' bg-success text-white border border-dark rounded px-2' : '' ?>">
                                                    <?= htmlspecialchars($ofTotals[$record['of_number']]['dos']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>


        </div>
        <div class="d-md-none" id="dynamic-mobile">
            <div class="mobile-cards-container">
            <?php if (empty($records)): ?>
                <div class="alert alert-info">No data found in database</div>
            <?php else: ?>
                <?php
                $lastOF = null;
                foreach ($records as $index => $record): 
                    $isNewOF = $lastOF !== $record['of_number'];
                    $isLastOfCurrentOF = false;
                    $cardId = 'mobileCard_' . $record['id']; // Unique ID for collapse
                ?>
                    <div class="card mb-4 shadow-sm border-0" style="border-radius: 1rem;">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap" style="border-top-left-radius: 1rem; border-top-right-radius: 1rem; cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#<?= $cardId ?>" aria-expanded="false" aria-controls="<?= $cardId ?>">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="fw-bold fs-5">OF #<?= htmlspecialchars($record['of_number']) ?></span>
                                <span class="badge bg-light text-primary fs-6">Taille: <?= htmlspecialchars($record['tailles']) ?></span>
                            </div>
                            <?php if ($isNewOF): ?>
                                <span class="badge bg-light text-primary fs-6">OF Qty: <?= htmlspecialchars($record['of_quantity']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div id="<?= $cardId ?>" class="collapse">
                            <div class="card-body p-3">
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <div class="small text-muted">OF Type</div>
                                        <div class="fw-semibold"><?= htmlspecialchars($record['of_type']) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">OF Category</div>
                                        <div class="fw-semibold"><?= htmlspecialchars($record['of_category']) ?></div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <div class="small text-muted">Taille</div>
                                        <div><?= htmlspecialchars($record['tailles']) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">Pack #</div>
                                        <div><?= htmlspecialchars($record['pack_number']) ?></div>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-12">
                                        <div class="small text-muted">Pack Order</div>
                                        <div><?= htmlspecialchars($record['pack_order_start']) ?> - <?= htmlspecialchars($record['pack_order_end']) ?></div>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row text-center mb-2">
                                    <div class="col-6">
                                        <div class="small text-muted">Devant</div>
                                        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($record['dv']) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">Garniture</div>
                                        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($record['g']) ?></div>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <div class="small text-muted">Manche</div>
                                        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($record['m']) ?></div>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <div class="small text-muted">D.O.S</div>
                                        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($record['dos']) ?></div>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row mb-2">
                                    <div class="col-12">
                                        <div class="small text-muted">Last Edit</div>
                                        <div><?= date('d/m/Y H:i:s', strtotime($record['last_edit'])) ?></div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-2">
                                    <a href="#" class="btn btn-sm btn-outline-primary edit-btn" 
                                        data-id="<?= $record['id'] ?>"
                                        data-of_number="<?= htmlspecialchars($record['of_number']) ?>"
                                        data-of_quantity="<?= htmlspecialchars($record['of_quantity']) ?>"
                                        data-of_type="<?= htmlspecialchars($record['of_type']) ?>"
                                        data-of_category="<?= htmlspecialchars($record['of_category']) ?>"
                                        data-tailles="<?= htmlspecialchars($record['tailles']) ?>"
                                        data-pack_number="<?= htmlspecialchars($record['pack_number']) ?>"
                                        data-pack_order_start="<?= htmlspecialchars($record['pack_order_start']) ?>"
                                        data-pack_order_end="<?= htmlspecialchars($record['pack_order_end']) ?>"
                                        data-dv="<?= htmlspecialchars($record['dv']) ?>"
                                        data-g="<?= htmlspecialchars($record['g']) ?>"
                                        data-m="<?= htmlspecialchars($record['m']) ?>"
                                        data-dos="<?= htmlspecialchars($record['dos']) ?>"
                                    ><i class="bi bi-pencil-square"></i> Edit</a>
                                    <a href="#" class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $record['id'] ?>"><i class="bi bi-trash"></i> Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
    <script src="lance.js"></script>
    <script src="loading.js"></script>
</body>
</html>