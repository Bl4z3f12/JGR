<?php
require_once 'productionset.php';

// Get filter parameters
$filter_date = isset($_POST['date']) ? $_POST['date'] : null;
$filter_stage = isset($_POST['stage']) ? $_POST['stage'] : null;
$filter_piece_name = isset($_POST['piece_name']) ? $_POST['piece_name'] : null;
$filter_of = isset($_POST['of_number']) ? $_POST['of_number'] : null;

// If reset is true, only use the date filter
if (isset($_POST['reset']) && $_POST['reset'] === 'true') {
    $filter_stage = null;
    $filter_piece_name = null;
    $filter_of = null;
}

$available_of_numbers = getAvailableOFNumbers($pdo, $filter_date);

// Get production summary data
$production_summary = getProductionSummary(
    $pdo,
    $filter_date,
    $filter_stage,
    $filter_piece_name,
    $filter_of
);

// Calculate grand totals
$grand_totals = [
    'total_count' => 0,
    'total_stage_quantity' => 0,
    'total_main_quantity' => 0,
    'manque' => 0,
    'suv_plus' => 0,
];

if (!empty($production_summary) && is_array($production_summary)) {
    foreach ($production_summary as $item) {
        $grand_totals['total_count'] += (float)($item['total_count'] ?? 0);
        $grand_totals['total_stage_quantity'] += (float)($item['total_stage_quantity'] ?? 0);
        $grand_totals['total_main_quantity'] += (float)($item['total_main_quantity'] ?? 0);
        $grand_totals['manque'] += (float)($item['manque'] ?? 0);
        $grand_totals['suv_plus'] += (float)($item['suv_plus'] ?? 0);
    }
}
?>

<div class="row mb-4 dip">
    <div class="col-md-7">
        <form id="stageFilterForm" class="form-inline d-flex flex-row align-items-center" method="get">
            <?php if (!empty($filter_date)): ?>
                <input type="hidden" name="date" value="<?= htmlspecialchars($filter_date) ?>">
            <?php endif; ?>

            <div class="form-group mr-2">
                <label for="ofFilter" class="mr-2">OF Number:</label>
                <select name="of_number" id="ofFilter" class="form-select" style="min-width: 180px;">
                    <option value="">All OF Numbers</option>
                    <?php foreach ($available_of_numbers as $of): ?>
                        <option value="<?= htmlspecialchars($of) ?>" <?= ($filter_of == $of) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($of) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mr-2">
                <label for="stageFilter" class="mr-2">Stage:</label>
                <select name="stage" id="stageFilter" class="form-select" style="min-width: 200px;">
                    <option value="0">All Stages</option>
                    <option value="Coupe">Coupe</option>
                    <option value="V1">V1</option>
                    <option value="V2">V2</option>
                    <option value="V3">V3</option>
                    <option value="Pantalon">Pantalon</option>
                    <option value="AMF">AMF</option>
                    <option value="Repassage">Repassage</option>
                    <option value="P_ fini">P_ fini</option>
                    <option value="Exported">Exported</option>
                    <?php 
                    foreach ($available_stages as $stage): 
                        if (empty($stage) || $stage == 'No Stage') continue;
                    ?>
                        <option value="<?= htmlspecialchars($stage) ?>" <?= ($filter_stage == $stage) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($stage) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mr-2">
                <label for="pieceNameFilter" class="mr-2">Piece Name:</label>
                <select name="piece_name" id="pieceNameFilter" class="form-select" style="min-width: 180px;">
                    <option value="">All Piece Names</option>
                    <option value="V" <?= ($filter_piece_name == 'V') ? 'selected' : '' ?>>Vest</option>
                    <option value="P" <?= ($filter_piece_name == 'P') ? 'selected' : '' ?>>Pantalon</option>
                    <option value="G" <?= ($filter_piece_name == 'G') ? 'selected' : '' ?>>Gilet</option>
                    <option value="M" <?= ($filter_piece_name == 'M') ? 'selected' : '' ?>>Manteau</option>
                </select>
            </div>
            
            <button type="submit" id="applyFilterBtn" class="btn btn-primary mr-2"><i class="bi bi-funnel-fill"></i> Apply Filter</button>
            
            <button type="button" class="btn btn-outline-dark" id="resetFilterBtn">
                <i class="fas fa-broom"></i> Reset
            </button>
        </form>
    </div>

    <div class="col-md-5">
        <div class="card bg-white text-dark">
            <div class="card-body p-2">
                <h5 class="card-title"><?= !empty($filter_stage) ? 'for Stage: ' . ($filter_stage == 'No Stage' ? '' : htmlspecialchars($filter_stage)) : '' ?></h5>
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Total Stage Qty:</strong> <span class="badge bg-primary text-white border border-primary rounded-pill px-3 py-2"><?= number_format($grand_totals['total_count']) ?></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="productionSummaryTable">
        <thead class="table-primary">
            <tr>
                <th>OF Number</th>
                <th>Size</th>
                <th>Category</th>
                <th>Piece Name</th>
                <th>Chef</th>
                <th>Stage</th>
                <th>Total Count</th>
                <th>Total Stage Quantity</th>
                <th>Total Main Quantity</th>
                <th>Solped Client</th>
                <th>Pedido Client</th>
                <th>Color Tissus</th>
                <th>Manque</th>
                <th>Suv Plus</th>
                <th>Latest Update</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($production_summary) && !empty($production_summary)): ?>
                <?php foreach ($production_summary as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['of_number'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['size'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['p_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['chef'] ?? '') ?></td>
                        <td><span class="badge bg-primary text-white border border-primary rounded-pill px-3 py-2"><?= $item['stage'] == 'No Stage' ? '' : htmlspecialchars($item['stage'] ?? '') ?></span></td>
                        <td><span class="badge bg-primary text-white border border-primary rounded-pill px-3 py-2"><?= htmlspecialchars($item['total_count'] ?? 0) ?></span></td>
                        <td><?= htmlspecialchars($item['total_stage_quantity'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($item['total_main_quantity'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($item['solped_client'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['pedido_client'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['color_tissus'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['manque'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($item['suv_plus'] ?? 0) ?></td>
                        <td><?php 
                            if (!empty($item['latest_update'])) {
                                echo htmlspecialchars(date('Y-m-d H:i', strtotime($item['latest_update'])));
                            } else {
                                echo '';
                            }
                        ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="15" class="text-center text-danger"><i style="font-size: 1.3em;" class="fa-solid fa-face-sad-tear"></i> Oops! No production summary available for the selected filters <br> Please try again with different filters or click reset button</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div> 