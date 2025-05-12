<?php
$current_view = 'production.php';
$host = "localhost";
$dbname = "jgr";
$username = "root";
$password = "";

require_once 'auth_functions.php';
requireLogin('login.php');
$filter_date = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $display_stages = [
        'Coupe' => [
            'icon' => '<i class="fas fa-cut"></i>',
            'color' => 'primary',
            'emoji' => '<img src="assets/tiptop.png" alt="Coupe" style="width: 34px;">'
        ],
        'V1' => [
            'icon' => '<i class="fas fa-tshirt"></i>',
            'color' => 'primary',
            'emoji' => '<img src="data:image/png;base64,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" alt="external-suit-autumn-clothes-accesories-wanicon-solid-wanicon" style="width:50px;">'
        ],
        'V2' => [
            'icon' => '<i class="fas fa-tshirt"></i>',
            'color' => 'primary',
            'emoji' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAADQUlEQVR4nO2YzUsVURjGp/JaUujSPqgIKkTCTbTRFmbrKAqKIuhz26IvlLBaFoSotCmKIooSs4L+gAj6WFlYmwrapEarMsyPzOoXL7w3j8PMmZnrnOli94HLvTPnOe9znnve8+l5JcxikBBesYKSkSIDs6hHJhP4mPSKFUAlsAVoB54A74BhYAR4DzwDLgJbgSqvWACsAU4ALUBtAfXXAWeA48BqN60MF68ADgIvAtLlA3AV2AWsBXJGvZwa3wlcAfoD6vdq7IosjKxPOKa/6ScJNrhq/Hz9FxcAc4CPuMMnYK5qXpbvtExI4x+ryGZ9d8uhkduqsUmfH6ViBrhhiLTquyMOjRxVjVbj3bWZmtjrE+nS940OjTSpRpfv/Z5CTSwKGAu9WrbCoZFVqiEzmIkBYGEhRo4FiPRrWZlDIznVkIYHpl0SEzIzvQ0INGxwxh2Y+G7El92AH2+kbUmM1IcIjRqcIQdGhoz4oyGc+iRGZK8UhBGD88OBkXEjftgi2p4krWSrEYQBY21xPUYGw9ogC+ZM0krQp5zluMNi1Xht4USnF9BhCfAgg3WkXjUeWjj29NI9TtC0l8cF5R3AHfapRpuFY08voCGmiK3XZoo21dgfwWuwGemMqFyjvOe4w1PVqI3gdRSaVp+VUw6M4Q5jqiFaXxKnF7AxQqA7g4GeR6Nq3SVpesXI+8PKO4d7nFetQ4nSS7sxbAES/ASWKvcl7vFKtZaodhgGp6VXjNnq78EG+E0G8Kb0rsdOr4jZaiJ/RlBuJvCm9FbKrjhWekWkVacvDTOBF39ZGIzbuGpfUOkhG6LK43AmfJrVNnIsIwGzm1y0NYfQz2r5yZCx9EvLypUbhGbzYi9RG5MYsRyFT/vKd/hOkdILu30cMWXilEUvXSPAJflH9bfc+ZrXRGV6jVOpz03AVz1N5he5KuWU+a59WvRZeutmFkYE940DUINhIr8S9xnrTp1cVuvvZbJGKKfbMJOPkZOjgkU3dSOCe4aZeXJD6KsqV0l1Rr3agJNnjy/GnRi6qRvJN6RGTYVtNLcB2y0bwB41OS1G1kacwSsZCUGpR/5FapVQbCClLk4TXiFtTCtgmvD+ayPeLMAflDtJ9D3Lhl0AAAAASUVORK5CYII=" alt="sweater" style="width:50px;">'
        ],
        'V3' => [
            'icon' => '<i class="fas fa-vest"></i>',
            'color' => 'primary',
            'emoji' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAACj0lEQVR4nO2aTUgVURTHxyzoAwpaKLjIFoEERlA9n7poUe3atWnhQnCVCymJWkSrEIXCkHahIEKg9rGJdtGHklIgGhGECAZBEPRJi8Ikf3HlCA+ZN+/cO3OHxzR/mM2bc879/+6de++bjyDIlWEBh4Be4A7wGvgI/JbjE7AAjAPngeagWgRsBY4DA8Ai9voKTACdQH3a5huALuAe8D3E3CzQDbQB+4CdcjTKb+bci5C8NWAO6APagVqfEKPSYJSmFXWmFKP10ieIxoDR4YgaBWWNNz5BLilNDEfUMJNdowGfIEeUJn4Be0PyzTxZVdY45RNki6w0Gl0Myb+pzF0xC4Q3EDHzWGnmfenKA+wGfihzF7xCiKFb6HXaYX4ZjacBcgW9HkjONuCDRd5QGiA9FoZ6JacDO/WlAaK9RH4CeyRn3hLkRhogg0ozgxJ/AntNpgHyTGHE7BWNEv/IAWTZN0Qd8EdhZELim4C/uOmYT5B+pYmixA/jrvu+IA7KX49KmpH4ermhctVa6T6UFMQu4K3SwBnJuUZ8fQP2JwVRa1YRZcPLEr8D+EwymttYxuNCjFk0ekHyzpGsZmPBAA8tG1y/DIB3JK9XcUCs5JpnW99arg2VOW1ucYtZAGkBWrMAEltBDmKp/2VECvlkr7IRia0gB8noiBQi4oqOk71iTR8gLRFxrY47e8WaPkAqyjXPtr61XBvyg0EOErj2WJnT+c6+IfvJkcE5Ml1FIE+dQcTUUeC25uGcB5AV4K55nwjUxALZ9Nz3MrAU0fD6C1DgeUyAJWmrLhHzZYBqgJPy0M70WKmulsQcAM4C14EnZb6S2Nz7k1I7md53GCXzHcoXYATYHtEBYXCL3ns/V5C+/gGd3R9pWusLAQAAAABJRU5ErkJggg==" alt="vest" style="width:50px;">'
        ],
        'Pantalon' => [
            'icon' => '<i class="fas fa-socks"></i>',
            'color' => 'primary',
            'emoji' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAACbklEQVR4nO2bTUgVURiGP0kjC9tU0EZDxaIfiJKoRa3UWrRxEYKFUBBkOxfSrnBbUVAQ2DKp9uJCRFEJEm3lRtoEQrWKIiH6gaQnBs8lk/szM8yc8x3OPHC4MPPNcN9nZu589wwjUlBQkAVAW8ajRXyC7FkVn6AQkDnenQHdGY+zrjMVxAW4HewlADQAH0IW0J9DeK8EvN70pceA9pSNz0HghVcCgM4tR+2qWd6SpuuLtvdNwLMKAlbTnO5eCQD2AT9DFnCnTJAwBLBx6/sYsoD+CkGCEbAQrADgZJUgQQgYC13ATOgC7ocu4EoMAfMJBcz7JOBIlSB3TU09MASs1Qi+ZurqzXaPfRCwDfheJdQE0Gpq95sfzT8V6ppNXQcwuWW9TgERwGKNI/sDGAF2mPpTwBuzbhk4Z5bvNHW/yuxDtYBR4vEOuLjpsrhgPutMN1munf5PAHAemI45RmwJuEEyotO9zWx7CJhKeWeoRZ8tAadJTnRZzAG/Y9YnFfAJ2G5LQGOCIGlJKuCelfAlgBVlAg6LTYDnigTMWQ0fAQwrEnBZbAN0KRHwudRv2BawR4mAh9bDlwDeKxBwVFwBjDsW8MpZ+AjTx7sUMCAuAXodCvga/ZlyLeCAQwGPRAPAF0cCjosGgFkHAhZEC8ADBwKuiRaAAcsConnEXaIF4JhlAU/Es0nSrAWcEG3wb8IzbwFLohHgqSUB10UjwE0LAr4BTaIR4IwFAaOiFTYecKznLKBTNAO8zVHAsmgHeJmjgEHRDnArJwGXgN2iHaAnJwF62l7Lk6R6nw5XIuP3BrwUMBG6gL3BvjhZUCBe8he7ONQF9C8RSQAAAABJRU5ErkJggg==" alt="trousers" style="width:50px;">'
        ],
        'AMF' => [
            'icon' => '<i class="fas fa-cut"></i>',
            'color' => 'primary',
            'emoji' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADwAAAA8CAYAAAA6/NlyAAAACXBIWXMAAAsTAAALEwEAmpwYAAADTUlEQVR4nO2by29MURzHp6qhXkUpC6rxDxBr/gIRwa4esdGKRJpiQ1fSSmMlTSzRhMZKxEYkLAgt8UratLHTWqGMx4IFYj7ym/4mjkl77zn3MZ07zjc5yeSe7z3nfu7jPL5pc7mYAuqAU8AU6UnaPil95eZbwFEqp85qAH5SQeCRagCeqiDwVFoQDcDmgLJ8voGBFSHX2OACfC+k408laAfgd8AZYAuwVMtWoEfrrIGlb+BziPeuC/Azi87bHICvCmBAf8uAIQfgNgvvUxfg6wkCXzba3Qc8AL5puQ/sNeoHEwS+5gK8KyHg18Ai9V0M8A2oZ3FIey7AO12A64EXCQB3qOewxQUespjXbYGfAwusgY1GP8QEblHPK8I1od71MYGngU1OsCUBG4HhiMBftH4l9mrSc75GBJZr3ZCLI2AhcD4C8LTWtzgAr9VzpiMA98u1xoI1BVxxBP4NLNHNxXsL2Lfqlfm54Ah8KZe0mPm2fjgAi3arp9cC+Kx69wR4ZgOWa1qXOLAIeOQI/FA9jcDjAN+ITEnqHXYELvaRivh3NWS78DimPnm9B4DvRp38viB16jke0tZswENpAg9GAP4JHDHakKe9TUujcbwT+BUBeDBN4B7tJC/rX0vgkm4D280FgfwGdgB3ImweZAMjOp0mcB3QGnN7KDfrpZbSRUfZHgp0a2qwNR0A/HfApJ94fNRS04nHKNBeWi/ruU16bKyWEo+CrrTM0XmNlLLRui9gSZmpxKPX2GNLmP7GBAC6pU4957KeeIzq0xPYWwG+m+qRMp7lxKNdPfJkw9St3gNZTTwKxobefI3n0qR6V0XYHlZF4pHX+mbs1azn5LOYeOQjAK+OATzviUdB8qwQT1KvdNUkHvvVI1NPmLrUezDLiceYMS3J1DOXbhi+8awnHn3qq9cnPWnUye+u0hSi32HmE4+CrqCKqyljICuOyMbN6I+4tKzaxGNcFxXFgcwYoOTYRC0nHqUbNtfUEySfeFRc+MTDSj7xwE4+8QiQTzyCBiKfeMwin3iUySceFvKJR5l84qHyiUciwiceOZ94zCKfeOT+fiI+8QiRTzwqLmb+iq5SGq4G4M4KAndUA3AdcKIsYE9a0rb0Eftf8f4AednPcEPzzFUAAAAASUVORK5CYII=" alt="sweater" style="width:50px;">'
        ],
        'Repassage' => [
            'icon' => '<i class="fas fa-iron"></i>',
            'color' => 'primary',
            'emoji' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAAC6klEQVR4nO3ZS+hVVRTH8Z1/H1GhhiYZEmQRBH8aJBIWEVQgOauc6CAnRTkwNBIhAgcK/XXQoP8gamDSY9iooEEDxTKiSB2EvaUktVFvhTL7yMYtXu0+t+fcuvfs7+yee85a+7f2Y611TgiFQqFQKBQKhcLYg/vwGT4ITQLz8LoLfBiaAu7EkST8D2zB7NAEsBqnkvhPcEtoCtiEf5L4acwKTQEbkvgzeCo0DTyNv/BYaAJYhEfwACbStRtDU8A6fJr2+37MD00DV6RAxFS3PYwquBb34FFsxW7sxUF8i+P4CX/jZEpvz+Cq9PwdeDuMCrgNT+BVfN6SugblK9yabF4f/udLdTmexxeq5Xss/C9EzU+5dxvu7XJPTE9fq5fpYYt/ED+2DOD9uI9b/l+Kl9PhNAx+xZXDEv94y579BU9iRktufhF/Gj73D0P81fgtOdyDG9L1CWzG75mDj+XrYbyB57AGd8UDDgswJ/mZi4862NgxjADcnmZ/J2ama5P4OEP0oWRnJa4ZYAzrO9g7UKv4SMy7eLjl98YBl/vh1JvfHDKJ7W0H23FiFuXazQnEm32KPp3uvbtC/7E4asfaqnx0BIvT8u1FnJFXcFOoGLzUwefuqn1dRFy6XaJ/6aw/FGoi2u7g91gsuuosX0/oj221DOLiF54xyO2YrMPhEnw3QEq7rvJB/HtMsQVux6aqHS0YsH7/sk+7s2PuTp1eu6U81e1Nbuoa2/FuleJn4j2D8UOftqPAXkx1eX5Fh2dOni+cqgjACwYn9uxL+rAdZ7kXx3pMzs+1lcVYdRl9+lQf9vuih423cv13JR5il3R7OatgdaiZ2spi7HL5xGzwWtqrcytTfWGME13OkTPZZTGWJQOjzpoc8TO6tJujxq6cAKwzPhzN2VPfGB/25nxWHnXeiTVA1qFr9Pf+swOLLjQd3TuykSI3AFPGhNwAHNfwAIwNJQA5OPdRcxzYlxWAQqFQKBQKhUJoFmcBkGtIYsv5TdsAAAAASUVORK5CYII=" alt="iron" style="width:50px;" >'
        ],
        'P_ fini' => [
            'icon' => '<i class="fas fa-box"></i>',
            'color' => 'primary',
            'emoji' => '<img src="data:image/png;base64,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" alt="package" style="width:50px;">'
        ],
        'Exported' => [
            'icon' => '<i class="fas fa-truck"></i>',
            'color' => 'primary',
            'emoji' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAADzklEQVR4nO2bV2gUQRjHx94VEXvQYEPxRYkvvlkSRCSgsYMNxBJBRXxQLBgE0Ygvglhe1AcRFSOiDyKWBBRBUNAHG1gwYsUWuyT6k4nfxnGZvdtN7nLJzv3gg7uZuW92/js75ds5pbJkyZIKgHbAPGCpz3RaOxV3gFkEM0vFHWBkAgFGqLgDtABeWRr/WucpFwCOWwQ4plwBWGERoFi5AjAM+G00Xn8eplwCyAfeiuUrFwGeaFOuQlYAdA/4DmwC2irXAHYAX2QgPKlcAGgDTARay/eOQLmIkKfiDlAojZ1hpBVL2nwVd4BR0tiLQAfpAZckbYxyAeCkNPiTmDtjgEaP+MBm2QBpdjoRC/ADHBIBcpWL4LIAQA/gsghQqFwByAMOAF992+F7wGqgk4obQFcJfN4iOVUi0EgVo7v9mfpxFZjprRqbBUB7uegLpI7nQAnQSzVVgOGyuXlH+vgJnGhyARSgFTCokS2zCydgQAYaHWQDMhXMaCo0fliNJiyAXlkCHxrgrwZYHkWASqAnUGZxdkrydBk/M4AiS3pUf34BxgU07LvMTncseVckT9t5YEIUAcolTU9Vfkokz4v6mOSK+YnqL6wAteWARZa8goY8AuUxEGCb6wJcc12AaqCLywJoJrkuwI64C/BG8m1Tr+Z63AVANm06KHsYeAg8MuytCwJUAJ1DNzSkABUhLrgiggBR/UURALnbBcabKu+oXlHomAP/C1AlP75pqeyG5OkyfnT8oNSSHtVfVAES9cwfwIaoAmSaIAHe+55x01YHCOAxJw4ClIS4kbZHE9lsNc9je/wT4KzlSK5p64zzCTb6qOYI4caAMOTGVYAa4Jl082qXBPgIrNGv5Yyy3XT0x3hTXT8B+Lu6miLP1nigZZKA6gJgYTpUDhDgKTBY8gdK3YuBIZLWF7ibVAAgx5hK1kvaaOCxZb6udQDsAvrL563SBT1+ecEIoB/wIMHUFdZeWLp87ekTYIuv2+vP2+UQ91DgWzIBcg2nvfUeOiDep7mue4IXw5feEcQyKXOE1FMmvvVdD2KllNkftgecke9LklReF1wE7icod0/KTEtBD/DbXPF9O0H9L6VMgfG7nDDP2+4kAqwy3h7p7h7Er3QvPCQqnIju9XG6MYnT2UZZ2x8lPCpT3WDLteq7GkRVooFbJRnRbRsdZLlct/WUASiIjSrNAGsT1F/aEMdFxnE3D323x1pOiZ62VF7WGGcAZEA+aqn/XINftvJ3btVT3EE9PZqLDUvZqcA+YG8mzgcBk4E9MuJPb7abnixZsmRRjcQfk5uigB3FlTsAAAAASUVORK5CYII=" alt="external-cargo-logistics-delivery-icongeek26-glyph-icongeek26" style="width:50px;">'
        ]
    ];
    $targets = [
        'Coupe' => 100,
        'V1' => 100,
        'V2' => 100,
        'V3' => 100,
        'Pantalon' => 100,
        'AMF' => 100,
        'Repassage' => 1000,
        'P_ fini' => 1000,
        'Exported' => 100
    ];
    $daily_stage_stats = [];
    foreach ($display_stages as $stage => $props) {
        $daily_stage_stats[$stage] = [
            'current' => 0, // This will hold the count for the specific day
            'in' => 0,
            'out' => 0,
            'from_stages' => [],
            'to_stages' => []
        ];
    }
$daily_items_query = "SELECT 
    b.stage,
    COUNT(DISTINCT b.full_barcode_name) as count
FROM barcodes b
LEFT JOIN jgr_barcodes_history h ON h.full_barcode_name = b.full_barcode_name
WHERE h.full_barcode_name IS NOT NULL
    AND DATE(h.last_update) = :date
    AND h.action_type IN ('INSERT', 'UPDATE')
    AND h.last_update = (
        SELECT MAX(h2.last_update)
        FROM jgr_barcodes_history h2
        WHERE h2.full_barcode_name = h.full_barcode_name
        AND DATE(h2.last_update) <= :date
    )
GROUP BY b.stage";

$daily_params = [':date' => $filter_date];
$daily_items_stmt = $pdo->prepare($daily_items_query);
$daily_items_stmt->execute($daily_params);
$daily_items = $daily_items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($daily_items as $row) {
        $stage = $row['stage'];
        if (isset($daily_stage_stats[$stage])) {
            $daily_stage_stats[$stage]['current'] = (int)$row['count'];
        }
    }
    $daily_transitions_query = "
        SELECT 
            h1.stage AS from_stage,
            h2.stage AS to_stage,
            COUNT(DISTINCT h1.full_barcode_name) as transition_count
        FROM 
            jgr_barcodes_history h1
        JOIN 
            jgr_barcodes_history h2 ON h1.full_barcode_name = h2.full_barcode_name
                                   AND h2.action_time = (
                                       SELECT MIN(h3.action_time)
                                       FROM jgr_barcodes_history h3
                                       WHERE h3.full_barcode_name = h1.full_barcode_name
                                       AND h3.action_time > h1.action_time
                                       AND DATE(h3.last_update) = :date
                                   )
        JOIN
            barcodes b ON h1.full_barcode_name = b.full_barcode_name
        WHERE 
            h1.stage != h2.stage
            AND h1.action_type IN ('INSERT', 'UPDATE')
            AND h2.action_type = 'UPDATE'
            AND DATE(h2.last_update) = :date
        GROUP BY 
            h1.stage, h2.stage
        ORDER BY 
            from_stage, to_stage
    ";
    
    $daily_transitions_stmt = $pdo->prepare($daily_transitions_query);
    $daily_transitions_stmt->execute($daily_params);
    $daily_transitions = $daily_transitions_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($daily_transitions as $transition) {
        $from_stage = $transition['from_stage'];
        $to_stage = $transition['to_stage'];
        $count = (int)$transition['transition_count'];
        
        if (isset($daily_stage_stats[$from_stage])) {
            $daily_stage_stats[$from_stage]['out'] += $count;
            
            if (!isset($daily_stage_stats[$from_stage]['to_stages'][$to_stage])) {
                $daily_stage_stats[$from_stage]['to_stages'][$to_stage] = $count;
            } else {
                $daily_stage_stats[$from_stage]['to_stages'][$to_stage] += $count;
            }
        }
        if (isset($daily_stage_stats[$to_stage])) {
            $daily_stage_stats[$to_stage]['in'] += $count;
            
            if (!isset($daily_stage_stats[$to_stage]['from_stages'][$from_stage])) {
                $daily_stage_stats[$to_stage]['from_stages'][$from_stage] = $count;
            } else {
                $daily_stage_stats[$to_stage]['from_stages'][$from_stage] += $count;
            }
        }
    }
    
    $first_stage_query = "
    SELECT 
        h.stage,
        COUNT(DISTINCT h.full_barcode_name) as count
    FROM 
        jgr_barcodes_history h
    JOIN
        barcodes b ON h.full_barcode_name = b.full_barcode_name
    WHERE 
        h.action_type = 'INSERT'
        AND DATE(h.last_update) = :date
        AND NOT EXISTS (
            SELECT 1
            FROM jgr_barcodes_history h_prev
            WHERE h_prev.full_barcode_name = h.full_barcode_name
            AND h_prev.action_time < h.action_time
        )
    GROUP BY 
        h.stage
    ORDER BY 
        stage
    ";
    
    $first_stage_stmt = $pdo->prepare($first_stage_query);
    $first_stage_stmt->execute($daily_params);
    $first_stages = $first_stage_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($first_stages as $first) {
        $stage = $first['stage'];
        $count = (int)$first['count'];
        
        if (isset($daily_stage_stats[$stage])) {
            $daily_stage_stats[$stage]['in'] += $count;
            
            if (!isset($daily_stage_stats[$stage]['from_stages']['Initial'])) {
                $daily_stage_stats[$stage]['from_stages']['Initial'] = $count;
            } else {
                $daily_stage_stats[$stage]['from_stages']['Initial'] += $count;
            }
        }
    }
    
    $total_count = 0;
    $chart_data = [
        'labels' => [],
        'current' => [],
        'in' => [],
        'out' => []
    ];
    
    foreach ($daily_stage_stats as $stage => $stats) {
        $total_count += $stats['current'];
        $chart_data['labels'][] = $stage;
        $chart_data['current'][] = $stats['current'];
        $chart_data['in'][] = $stats['in'];
        $chart_data['out'][] = $stats['out'];
    }
    
    function getEmoji($count) {
        if ($count > 900) return '🔥';
        if ($count >= 700) return '👍';
        return '⚠️';
    }
    function getBadgeColor($count) {
        if ($count > 900) return 'bg-success';
        if ($count >= 700) return 'bg-warning';
        return 'bg-danger';
    }
    function getFaceEmoji($count) {
        if ($count > 900) {
            return '<i class="fas fa-smile text-success fa-2x"></i>'; // Happy green face for > 900
        } elseif ($count >= 700 && $count <= 900) {
            return '<i class="fas fa-meh text-warning fa-2x"></i>'; // Neutral face for 700-900
        } else {
            return '<i class="fas fa-angry text-danger fa-2x"></i>'; // Angry face for < 700
        }
    }

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>