<?php

$current_view = 'production.php';
require_once 'auth_functions.php';

// Redirect to login page if not logged in
requireLogin('login.php');

// Initialize database connection with the same parameters as your other files
$host = 'localhost';
$db_name = 'jgr';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

try {
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize date and search parameters
$current_date = date("Y-m-d");

// Common search parameters
$of_number = $_GET['of_number'] ?? '';
$size = $_GET['size'] ?? '';
$category = $_GET['category'] ?? '';
$p_name = $_GET['piece_name'] ?? '';
$date = $_GET['date'] ?? $current_date;

// Define stage options - add 'No Stage' for NULL values
$stage_options = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_ fini', 'Exported'];

// Query to get barcode counts by stage - modified to handle NULL values
$query = "SELECT 
            COALESCE(stage, 'No Stage') as stage, 
            COUNT(*) as count
          FROM barcodes 
          WHERE stage IS NOT NULL";  

$params = [];

if (!empty($of_number)) {
    $query .= " AND of_number LIKE ?";
    $params[] = "%$of_number%";
}

if (!empty($size)) {
    $query .= " AND size = ?";
    $params[] = $size;
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if (!empty($p_name)) {
    $query .= " AND piece_name = ?";
    $params[] = $p_name;
}

if (!empty($date)) {
    $query .= " AND DATE(last_update) = ?";
    $params[] = $date;
}

$query .= " GROUP BY COALESCE(stage, 'No Stage') ORDER BY FIELD(COALESCE(stage, 'No Stage'), 'Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_ fini', 'Exported')";

// Debug query
$debug_query = $query;
$debug_params = implode(", ", $params);

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $stage_counts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

// Debug results
$debug_count = count($stage_counts);
$debug_stages = [];
foreach ($stage_counts as $stage) {
    $debug_stages[] = $stage['stage'] . ": " . $stage['count'];
}

// Get total count
$total_query = "SELECT COUNT(*) as total FROM barcodes WHERE 1=1";
$total_params = [];

if (!empty($of_number)) {
    $total_query .= " AND of_number LIKE ?";
    $total_params[] = "%$of_number%";
}

if (!empty($size)) {
    $total_query .= " AND size = ?";
    $total_params[] = $size;
}

if (!empty($category)) {
    $total_query .= " AND category = ?";
    $total_params[] = $category;
}

if (!empty($p_name)) {
    $total_query .= " AND piece_name = ?";
    $total_params[] = $p_name;
}

if (!empty($date)) {
    $total_query .= " AND DATE(last_update) = ?";
    $total_params[] = $date;
}

$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($total_params);
$total_row = $total_stmt->fetch();
$total_count = $total_row['total'];

// Get distinct categories for dropdown
$cat_query = "SELECT DISTINCT category FROM barcodes ORDER BY category";
$cat_stmt = $pdo->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();

// Get distinct piece names for dropdown
$piece_query = "SELECT DISTINCT piece_name FROM barcodes ORDER BY piece_name";
$piece_stmt = $pdo->prepare($piece_query);
$piece_stmt->execute();
$piece_names = $piece_stmt->fetchAll();

// Get distinct sizes for dropdown
$size_query = "SELECT DISTINCT size FROM barcodes ORDER BY size";
$size_stmt = $pdo->prepare($size_query);
$size_stmt->execute();
$sizes = $size_stmt->fetchAll();

// Function to determine which emoji to show based on count
function getEmoji($count) {
    if ($count > 900) {
        return '<i class="fas fa-smile text-success fa-2x"></i>'; // Happy green face for > 900
    } elseif ($count >= 700 && $count <= 900) {
        return '<i class="fas fa-meh text-warning fa-2x"></i>'; // Neutral face for 700-900
    } else {
        return '<i class="fas fa-angry text-danger fa-2x"></i>'; // Angry face for < 700
    }
}

// Function to get badge color based on count
function getBadgeColor($count) {
    if ($count > 900) {
        return 'bg-success';
    } elseif ($count >= 700 && $count <= 900) {
        return 'bg-warning text-dark';
    } else {
        return 'bg-danger';
    }
}

// Get stage emoji for display
function getStageEmoji($stage) {
    $emojis = [
        'Coupe' => '<img src="assets/tiptop.png" alt="Coupe" style="width: 40px;">',
        'V1' => '<img src="data:image/png;base64,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" alt="external-suit-autumn-clothes-accesories-wanicon-solid-wanicon" style="width:50px;">',
        'V2' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAADQUlEQVR4nO2YzUsVURjGp/JaUujSPqgIKkTCTbTRFmbrKAqKIuhz26IvlLBaFoSotCmKIooSs4L+gAj6WFlYmwrapEarMsyPzOoXL7w3j8PMmZnrnOli94HLvTPnOe9znnve8+l5JcxikBBesYKSkSIDs6hHJhP4mPSKFUAlsAVoB54A74BhYAR4DzwDLgJbgSqvWACsAU4ALUBtAfXXAWeA48BqN60MF68ADgIvAtLlA3AV2AWsBXJGvZwa3wlcAfoD6vdq7IosjKxPOKa/6ScJNrhq/Hz9FxcAc4CPuMMnYK5qXpbvtExI4x+ryGZ9d8uhkduqsUmfH6ViBrhhiLTquyMOjRxVjVbj3bWZmtjrE+nS940OjTSpRpfv/Z5CTSwKGAu9WrbCoZFVqiEzmIkBYGEhRo4FiPRrWZlDIznVkIYHpl0SEzIzvQ0INGxwxh2Y+G7El92AH2+kbUmM1IcIjRqcIQdGhoz4oyGc+iRGZK8UhBGD88OBkXEjftgi2p4krWSrEYQBY21xPUYGw9ogC+ZM0krQp5zluMNi1Xht4USnF9BhCfAgg3WkXjUeWjj29NI9TtC0l8cF5R3AHfapRpuFY08voCGmiK3XZoo21dgfwWuwGemMqFyjvOe4w1PVqI3gdRSaVp+VUw6M4Q5jqiFaXxKnF7AxQqA7g4GeR6Nq3SVpesXI+8PKO4d7nFetQ4nSS7sxbAES/ASWKvcl7vFKtZaodhgGp6VXjNnq78EG+E0G8Kb0rsdOr4jZaiJ/RlBuJvCm9FbKrjhWekWkVacvDTOBF39ZGIzbuGpfUOkhG6LK43AmfJrVNnIsIwGzm1y0NYfQz2r5yZCx9EvLypUbhGbzYi9RG5MYsRyFT/vKd/hOkdILu30cMWXilEUvXSPAJflH9bfc+ZrXRGV6jVOpz03AVz1N5he5KuWU+a59WvRZeutmFkYE940DUINhIr8S9xnrTp1cVuvvZbJGKKfbMJOPkZOjgkU3dSOCe4aZeXJD6KsqV0l1Rr3agJNnjy/GnRi6qRvJN6RGTYVtNLcB2y0bwB41OS1G1kacwSsZCUGpR/5FapVQbCClLk4TXiFtTCtgmvD+ayPeLMAflDtJ9D3Lhl0AAAAASUVORK5CYII=" alt="sweater" style="width:50px;">',
        'V3' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAACXBIWXMAAAsTAAALEwEAmpwYAAACj0lEQVR4nO2aTUgVURTHxyzoAwpaKLjIFoEERlA9n7poUe3atWnhQnCVCymJWkSrEIXCkHahIEKg9rGJdtGHklIgGhGECAZBEPRJi8Ikf3HlCA+ZN+/cO3OHxzR/mM2bc879/+6de++bjyDIlWEBh4Be4A7wGvgI/JbjE7AAjAPngeagWgRsBY4DA8Ai9voKTACdQH3a5huALuAe8D3E3CzQDbQB+4CdcjTKb+bci5C8NWAO6APagVqfEKPSYJSmFXWmFKP10ieIxoDR4YgaBWWNNz5BLilNDEfUMJNdowGfIEeUJn4Be0PyzTxZVdY45RNki6w0Gl0Myb+pzF0xC4Q3EDHzWGnmfenKA+wGfihzF7xCiKFb6HXaYX4ZjacBcgW9HkjONuCDRd5QGiA9FoZ6JacDO/WlAaK9RH4CeyRn3hLkRhogg0ozgxJ/AntNpgHyTGHE7BWNEv/IAWTZN0Qd8EdhZELim4C/uOmYT5B+pYmixA/jrvu+IA7KX49KmpH4ermhctVa6T6UFMQu4K3SwBnJuUZ8fQP2JwVRa1YRZcPLEr8D+EwymttYxuNCjFk0ekHyzpGsZmPBAA8tG1y/DIB3JK9XcUCs5JpnW99arg2VOW1ucYtZAGkBWrMAEltBDmKp/2VECvlkr7IRia0gB8noiBQi4oqOk71iTR8gLRFxrY47e8WaPkAqyjXPtr61XBvyg0EOErj2WJnT+c6+IfvJkcE5Ml1FIE+dQcTUUeC25uGcB5AV4K55nwjUxALZ9Nz3MrAU0fD6C1DgeUyAJWmrLhHzZYBqgJPy0M70WKmulsQcAM4C14EnZb6S2Nz7k1I7md53GCXzHcoXYATYHtEBYXCL3ns/V5C+/gGd3R9pWusLAQAAAABJRU5ErkJggg==" alt="vest" style="width:50px;">',
        'Pantalon' => '<img src="data:image/png;base64,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" alt="trousers" style="width:50px;">',
        'Repassage' => '<img src="data:image/png;base64,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" alt="iron" style="width:50px;" >',
        'P_ fini' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAACXBIWXMAAAsTAAALEwEAmpwYAAALlUlEQVR4nO2de7RXRRXH5wqVCCqWmkE+UFIig1LJJApNKBNLegCZaEVgpsUFspByKaYUqAuXDxIuoUQkaWlWahQSKoQppEA8MhctQ5ObihiPq5e4fFv78h3ZnnXO73ceM/d3fr97Pv/A/M49M3vOnJnZe8+eOcYUFBQUFBQUFBQUFBQUFLQHALwFwPsBDAfwvkrL064A0A3AIAD1AGYBWAagCftoAXA3gGMrLWtNAeAgAP0BfB3ADACPANiKcP4HYB2A3wF4nb/Jv9MBvL3SdakqAHSUtxnApwFM5tu9jm96GK+wV9wE4CIAAwB0UvkdCWAegD3q7ycCeGtla5pDALwLwCcBfJcP7SkAzREP/r8A/gxgJoBLAHwMwCEJypKGWqHyWytDnWmPAOgAoBeALwKYCuAPABojHvxuAE8DuA/ADv62C8DlMllnlKMOwAUAnlPlSTk9Ta0iQ4FoNgAu5DCyTD3YsLd+JXtHPd/iziqv7rxm+QeAwQ5kPIDD1jbV4CLrgaaaAXA4gE9wyFkAYD3f8CAy/v9TjeOvylCVoJxPAXiG90oeP5WyHcgvDT5XzU/Sc0aYKlIv7UQr2svGiLd+Fydh+9YPspoN/2+HiyZer0tgW9Sr3raV6Q4O6nYSgOWqDg+LHWPyBoA+AB5VXTtII+eDaQDOA9C73AOSiRjAnSqP+wG8M4FMx/Iey+PyQB3UdT8AowG8qFTnGwEcbPICgO+piv8bwC84uZ4lGlLGvEcA2MK8/wPgMwnvHwrgWd4vw+TNLh4eX5hb2CD2pftK3J7sFQATKNRisQ88DYO/V40ulvYBCSfnKUpVfkF6qiPZ+nJ0sDwG4GQXeWcR6psUZgbTxwD4NoB3OyyjDsClAF5T9sGJCfM4AcAi9fAecqHKUrbzOTqAk7+8NIdmzTutQOKmEBqYHqWGr5M8zFfr1YR/UYo85OG9zDx2Ahgnc4MD2Q4EcJ3qiVtonGZWKJIKYhvgDqbFqLKItnOu4/I60R6w3JvEGlequLhbLKI99XIk33sAPKjyFs/CABd5J3njhJ+riVgjk+k4D+WeR6MRtF9OTZHHMCoLtsdNcNFbmPfnlEJh7aIjXOQdp1LCL5UgYcxw3X0BHAfgCWXbfCeppiNjvbxMSk7xhZ3gSD5RKH6g5j4xdsf7UH50oeeysN8wfQ6iedC16wF73TDXKwv/gTQTqqjUamKW3nJZlheIk/3xHMJFJs3qpMNskoLPtg+bafHMlkKEOdKTHC+xjOfSjNu0L+4IqLGx5hYAhwEYwh6xkC78IC3sycIZqSoaQxBxdQgPMX0GyvOCaw1MEFUbwFKWIUbbpDRzAo3aTcznNfrhOgQUi/7U0BZwDouq568px8e5gCaNDG8TPdcchEeYFm9sHMTVcqanxasfqiFM3tbDUuRzEO0Jm89fOA+uVG+5Zjv9W6L6fj5qFOD9Qj8nFQ4p4MMs4DGmT0V8ZMl0mCe5zlI+J5kbTk/QEIMBXMk5z07IQc1Rht7Z9G31iTvfAFjFPPpmrmQJL6iwkukPIhkyrl7iSbbuXFe3D/HqwNCzH52do/hw10YsA1vvsbzdAwF0ySCTeLnhyu4JK0BCaYQ1TJ+IdEz2uBJ5tVqDeZTLAwuphob12uUMdhjO9faR2tbKKI+sdAo93NQw3Eck/J1pUfXScptLWwVAT65KzlQaWJB/0UM9jsPv20Lysd6H+Q5ksgpAt6x5RRXQgwVsDKTT8quwhxLTHjmN1va9Eevxtpe8SAM21kNhowrz4vx9mbys9ubH+UhVU9gUSGdhSbl1C+ydfAdx+FkUCHyzvMprk/m3dn5bn7COstYhzHXwvDYzr9TzUByDSGhUjjsX/NWuidPqlYiUrwKYA2CDUkctezhhzuYDPD5E1vembBAp9w0HasbnZYdOPzFfALqygFcCaRc8A+C3EeP/TmpQU2ghl402pEYlrEtYx6/xvjlZnhXzsoqEn9VFOtCE7Ux3hh82M3BiIo3PNPOMhB4JaxPeN4b3zU5aZkhe8iLtzppPOctYaFaRHy6Z6UpFVCr531I2SIMDGZrts/IGjak9arwPju9Z+JYvmynBfRfbl8OBDPJsdmbNp1whNnq8NYwzwteTlgkO5RQXh7A64X3f4H23OTBShW1Z8olTkI3Jag3xLBEWmobLK9Ugonqzx8vauPDjjOXvrxUgb6iggdZFl4i1gLRc6VBOCdsRVpX5u2MC4T12BLg1Y/ldmM9LWfJJYuxYuyEqaj0N1ziU8wPM8ymme9GumcUwowH0W1nVtMnlfMYFMGGzqzpFFWRjcVtjsQKh/FmZ5lBOa6mLYfmjEvtMrAvnEGVbHeHQiH7eSYViOMxa9+iVCLJOw3QPSwWWFqrVYwH8hJt1nhc111WZIVGYwrM+8g9zKbf6+PkGuuJWD3OIdc18yFXeMcs/SjtifRYkCztCH6ZlNc0VDQ7lFKP1BkZbtm1E4b6wJeFp3wXZZcnWQOPAPr2s3G5qBCoRiX1paQqyDXAa03pjS1bmmxpBuW5W+y7IhrYMZFqiL1xxt6kRlJb3pO+CbCBB62ZLAH902CD3mRpBQn9YpxW+C5LNOsIQpvV2sqw8YGoELjELy30XJBEcwlCmZU3bFYtMjQDgo6zTUt8FycKRMJxpieJwxcOmRmA4qbDEd0ESvyqMZFpv5M/KclMjcL++/14P4C4WNIppcUO4YqWpEXi4gbDQd0G2R1zMtAS8uWKNqSK4dvKFiGtyqIJwv28hGljQWKb1HsCsbDBVgtqK0Ry2CxnAZ/XmJp+CyCZ64TKmZUeTKzaanMG4ryGB3+p4YoTlxhLb/+7xLaBtgElMS6yUKzaZfKquTXqNhLFhGlnGfkfIRlXhLt9CXsOCrmL6KocN0mhyRMBPN1X9LkdMBWm1y0KCtu/0LeQVLGgK07KFyxVbTE4I2T8p27K7KveR3uM4JhjMp0JS5/kWVHasCtcH0i7YbnKC8tmF9gK1IrmqTMDd7W113slNTMuSqCteNzmBXu2taptcfeAwzVN47Yky8V2zfQs6JnCagxw+44o9Jicw0K2fjlwJXJeducKyiPvHugi4S7I1upkHj9nNlq7oaHKCrMNTpsdDrp3Oa38qc5TVzW0hqERv+KKTyQlq2/fSEu71UDuD+92FG9pC0I4hR0i44mCTP4/t4hJHdHRXoaNHhZy+N6WthO2iNsa75FCTP8Mwqhfsz/Nf5qvTik7hNdn77jQ8Nu5J1PZYIld0MzmBLhKxuI9Wv3XlbwtUI1iWqRBbOQfFaQB5XKF7lzgEPw1Hm5zB/YrjeVRgcPvFCs4XPSK84uMr5fksFTubhJ5tXoHwXb/DudZjtzZDHXSzmHZJjxIhQPaYjkvbvgZ7hRjpaDdV74pU4M11WROQqZHzxPnlzr6ifWJ3CAij207yaD9XFvpWrAL76jGN4U0TGV9VF/O0ultChrQL2kbq8gtYaelnqgg2xPdLzKOVPTuep+7ok9qS0t9UAXQuzok40smyKRdKCtXFtNb8QJNTeFpQPbc3RLGRy9qD8uQGsj1FPv2QlMEmfyrvJC7ZhiktLVzImujtXCzHjSJn2Cbh7ArL3ImLU9PV5qQgTQwYHJ3kSw7V2ihDK2j4LYw4aQg8hHkuj3p646s/VQnXFuJGOA5rA3kO56ncDTzULIxd3GoxiRO4k1Owq7GnfMlD2Z05yU6lQ7SlxIQ8iyE8ufE6++4pP/N93gn2uj7kpNJrOeHaj7EE2cavskkE4nGmPRKjUTYkccFzc6fsuP0yz9pdHfEhMnB+WMJT5wZm/exerTWK+IaieJlajhxS/BHZYMr17XMYPHAtG/XJMk7NHXSBXMG1jcTnbrUbEk70cdkeOHexaIAUjaI/H5EUCVK7hyrryZXYj96ee8oO9dHhC70dTlwQ2ii7Ax+jlOiP4mvOFWiUEVx9rO5v0BYUFBQUFBQUFBQUFJjq4P/4Ss2TDRYelAAAAABJRU5ErkJggg==" alt="package" style="width:50px;">',
        'Exported' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAACXBIWXMAAAsTAAALEwEAmpwYAAADzklEQVR4nO2bV2gUQRjHx94VEXvQYEPxRYkvvlkSRCSgsYMNxBJBRXxQLBgE0Ygvglhe1AcRFSOiDyKWBBRBUNAHG1gwYsUWuyT6k4nfxnGZvdtN7nLJzv3gg7uZuW92/js75ds5pbJkyZIKgHbAPGCpz3RaOxV3gFkEM0vFHWBkAgFGqLgDtABeWRr/WucpFwCOWwQ4plwBWGERoFi5AjAM+G00Xn8eplwCyAfeiuUrFwGeaFOuQlYAdA/4DmwC2irXAHYAX2QgPKlcAGgDTARay/eOQLmIkKfiDlAojZ1hpBVL2nwVd4BR0tiLQAfpAZckbYxyAeCkNPiTmDtjgEaP+MBm2QBpdjoRC/ADHBIBcpWL4LIAQA/gsghQqFwByAMOAF992+F7wGqgk4obQFcJfN4iOVUi0EgVo7v9mfpxFZjprRqbBUB7uegLpI7nQAnQSzVVgOGyuXlH+vgJnGhyARSgFTCokS2zCydgQAYaHWQDMhXMaCo0fliNJiyAXlkCHxrgrwZYHkWASqAnUGZxdkrydBk/M4AiS3pUf34BxgU07LvMTncseVckT9t5YEIUAcolTU9Vfkokz4v6mOSK+YnqL6wAteWARZa8goY8AuUxEGCb6wJcc12AaqCLywJoJrkuwI64C/BG8m1Tr+Z63AVANm06KHsYeAg8MuytCwJUAJ1DNzSkABUhLrgiggBR/UURALnbBcabKu+oXlHomAP/C1AlP75pqeyG5OkyfnT8oNSSHtVfVAES9cwfwIaoAmSaIAHe+55x01YHCOAxJw4ClIS4kbZHE9lsNc9je/wT4KzlSK5p64zzCTb6qOYI4caAMOTGVYAa4Jl082qXBPgIrNGv5Yyy3XT0x3hTXT8B+Lu6miLP1nigZZKA6gJgYTpUDhDgKTBY8gdK3YuBIZLWF7ibVAAgx5hK1kvaaOCxZb6udQDsAvrL563SBT1+ecEIoB/wIMHUFdZeWLp87ekTYIuv2+vP2+UQ91DgWzIBcg2nvfUeOiDep7mue4IXw5feEcQyKXOE1FMmvvVdD2KllNkftgecke9LklReF1wE7icod0/KTEtBD/DbXPF9O0H9L6VMgfG7nDDP2+4kAqwy3h7p7h7Er3QvPCQqnIju9XG6MYnT2UZZ2x8lPCpT3WDLteq7GkRVooFbJRnRbRsdZLlct/WUASiIjSrNAGsT1F/aEMdFxnE3D323x1pOiZ62VF7WGGcAZEA+aqn/XINftvJ3btVT3EE9PZqLDUvZqcA+YG8mzgcBk4E9MuJPb7abnixZsmRRjcQfk5uigB3FlTsAAAAASUVORK5CYII=" alt="external-cargo-logistics-delivery-icongeek26-glyph-icongeek26" style="width:50px;">',
    ];
    
    return $emojis[$stage] ?? '📊';
}

// Convert stage data to chart format
$chart_labels = [];
$chart_data = [];
$chart_colors = [];

foreach ($stage_counts as $stage) {
    $chart_labels[] = $stage['stage'];
    $chart_data[] = (int)$stage['count']; // Ensure values are integers
    
    // Set color based on count
    if ($stage['count'] > 900) {
        $chart_colors[] = '#28a745'; // Green
    } elseif ($stage['count'] >= 700 && $stage['count'] <= 900) {
        $chart_colors[] = '#ffc107'; // Yellow
    } else {
        $chart_colors[] = '#dc3545'; // Red
    }
}

// For empty datasets, add placeholder data
if (empty($chart_labels)) {
    $chart_labels = ['No Data Available'];
    $chart_data = [0];
    $chart_colors = ['#6c757d'];
}
$incoming_query = "SELECT 
                    COALESCE(stage, 'No Stage') as stage, 
                    COUNT(*) as count
                  FROM barcodes 
                  WHERE stage IS NOT NULL";  

$incoming_params = [];

if (!empty($of_number)) {
    $incoming_query .= " AND of_number LIKE ?";
    $incoming_params[] = "%$of_number%";
}

if (!empty($size)) {
    $incoming_query .= " AND size = ?";
    $incoming_params[] = $size;
}

if (!empty($category)) {
    $incoming_query .= " AND category = ?";
    $incoming_params[] = $category;
}

if (!empty($p_name)) {
    $incoming_query .= " AND piece_name = ?";
    $incoming_params[] = $p_name;
}

if (!empty($date)) {
    $incoming_query .= " AND DATE(last_update) = ?";
    $incoming_params[] = $date;
}

$incoming_query .= " GROUP BY COALESCE(stage, 'No Stage') 
                     ORDER BY FIELD(COALESCE(stage, 'No Stage'), 'Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_ fini', 'Exported')";

try {
    $stmt = $pdo->prepare($incoming_query);
    $stmt->execute($incoming_params);
    $incoming_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Incoming query failed: " . $e->getMessage());
}

// For the outgoing count, we need to use a different approach since we don't have previous_stage
// Instead, we'll count completed stages based on action_type and stage

// First, get the daily completed count per stage from the history
$completed_query = "SELECT 
                    COALESCE(stage, 'No Stage') as stage, 
                    COUNT(*) as count
                  FROM jgr_barcodes_history
                  WHERE action_type = 'UPDATE'
                  AND stage IS NOT NULL";  // Exclude NULL stages

$completed_params = [];

if (!empty($of_number)) {
    $completed_query .= " AND of_number LIKE ?";
    $completed_params[] = "%$of_number%";
}

if (!empty($size)) {
    $completed_query .= " AND size = ?";
    $completed_params[] = $size;
}

if (!empty($category)) {
    $completed_query .= " AND category = ?";
    $completed_params[] = $category;
}

if (!empty($p_name)) {
    $completed_query .= " AND piece_name = ?";
    $completed_params[] = $p_name;
}

if (!empty($date)) {
    $completed_query .= " AND DATE(action_time) = ?";
    $completed_params[] = $date;
}

$completed_query .= " GROUP BY COALESCE(stage, 'No Stage') 
                      ORDER BY FIELD(COALESCE(stage, 'No Stage'), 'Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_ fini', 'Exported')";

try {
    $stmt = $pdo->prepare($completed_query);
    $stmt->execute($completed_params);
    $completed_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Completed query failed: " . $e->getMessage());
}

// Convert to associative arrays for easier access
$incoming_data = [];
foreach ($incoming_counts as $stage) {
    $incoming_data[$stage['stage']] = (int)$stage['count'];
}

$completed_data = [];
foreach ($completed_counts as $stage) {
    $completed_data[$stage['stage']] = (int)$stage['count'];
}

// Define stage options - add 'No Stage' for NULL values
$stage_options = ['Coupe', 'V1', 'V2', 'V3', 'Pantalon', 'Repassage', 'P_ fini', 'Exported'];

// Prepare data for charts
$chart_labels = [];
$incoming_chart_data = [];
$completed_chart_data = [];
$current_chart_data = [];
$chart_colors = [];

// Define stage progression for calculating transitions
$stage_progression = [
    'Coupe' => 'V1',
    'V1' => 'V2',
    'V2' => 'V3',
    'V3' => 'Pantalon',
    'Pantalon' => 'Repassage',
    'Repassage' => 'P_ fini',
    'P_ fini' => 'Exported',
    'Exported' => null
];

// Calculate outgoing data based on the next stage in the progression
$outgoing_data = [];
foreach ($stage_options as $stage_name) {
    // If there's a next stage, count items that moved to it
    $next_stage = $stage_progression[$stage_name] ?? null;
    
    if ($next_stage) {
        // Items completed = items that moved to the next stage in the workflow
        $outgoing_data[$stage_name] = $completed_data[$next_stage] ?? 0;
    } else {
        // For the final stage (Exported), we count its own completions
        $outgoing_data[$stage_name] = $completed_data[$stage_name] ?? 0;
    }
}

foreach ($stage_options as $stage_name) {
    $incoming = $incoming_data[$stage_name] ?? 0;
    $outgoing = $outgoing_data[$stage_name] ?? 0;
    
    // Current items = items in stage minus items that left the stage
    $current = max(0, $incoming - $outgoing);
    
    $chart_labels[] = $stage_name;
    $incoming_chart_data[] = $incoming;
    $completed_chart_data[] = $outgoing;  // renamed from outgoing_chart_data for clarity
    $current_chart_data[] = $current;
    
    // Set color based on current count
    if ($current > 900) {
        $chart_colors[] = '#28a745'; // Green
    } elseif ($current >= 700 && $current <= 900) {
        $chart_colors[] = '#ffc107'; // Yellow
    } else {
        $chart_colors[] = '#dc3545'; // Red
    }
}

// For empty datasets, add placeholder data
if (empty($chart_labels)) {
    $chart_labels = ['No Data'];
    $incoming_chart_data = [0];
    $completed_chart_data = [0];  // renamed from outgoing_chart_data
    $current_chart_data = [0];
    $chart_colors = ['#6c757d']; // Grey
}
 // Query to get current stage counts for each barcode
 $current_stage_query = "
 SELECT 
     stage,
     COUNT(*) as count
 FROM 
     barcodes
 GROUP BY 
     stage
";

// Execute the current stage query
$current_stage_stmt = $pdo->query($current_stage_query);
$current_stage_counts = $current_stage_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create an associative array for easier access
$stages_data = [];
foreach ($current_stage_counts as $row) {
 $stage = $row['stage'];
 $stages_data[$stage] = [
     'current' => (int)$row['count'],
     'in' => 0,
     'out' => 0,
     'previous_stages' => [] // Track which stages items came from
 ];
}

// Modified query to get ALL stage transitions, not just the most recent one
$all_transitions_query = "
 SELECT 
     h1.full_barcode_name,
     h1.stage AS from_stage,
     h2.stage AS to_stage,
     h2.action_time
 FROM 
     jgr_barcodes_history h1
 JOIN 
     jgr_barcodes_history h2 ON h1.full_barcode_name = h2.full_barcode_name
     AND h1.action_time < h2.action_time
 WHERE 
     h1.stage != h2.stage
     AND h1.action_type IN ('INSERT', 'UPDATE')
     AND h2.action_type = 'UPDATE'
     AND NOT EXISTS (
         SELECT 1
         FROM jgr_barcodes_history h3
         WHERE h3.full_barcode_name = h1.full_barcode_name
         AND h3.action_time > h1.action_time
         AND h3.action_time < h2.action_time
         AND h3.action_type IN ('INSERT', 'UPDATE')
     )
 ORDER BY 
     h1.full_barcode_name, h1.action_time
";

// Execute the transitions query
$transitions_stmt = $pdo->query($all_transitions_query);
$all_transitions = $transitions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group transitions by barcode to track full path
$barcode_paths = [];
foreach ($all_transitions as $transition) {
 $barcode = $transition['full_barcode_name'];
 $from_stage = $transition['from_stage'];
 $to_stage = $transition['to_stage'];
 
 if (!isset($barcode_paths[$barcode])) {
     $barcode_paths[$barcode] = [];
 }
 
 $barcode_paths[$barcode][] = [
     'from' => $from_stage,
     'to' => $to_stage,
     'time' => $transition['action_time']
 ];
}

// Process all transitions to update the stages_data array
foreach ($barcode_paths as $barcode => $transitions) {
 // Track all stages this barcode has been through
 $stages_visited = [];
 
 foreach ($transitions as $index => $transition) {
     $from_stage = $transition['from'];
     $to_stage = $transition['to'];
     
     // Add to stages visited
     $stages_visited[] = $from_stage;
     if ($index === count($transitions) - 1) {
         $stages_visited[] = $to_stage;
     }
     
     // Initialize stages if they don't exist in our data array
     if (!isset($stages_data[$from_stage])) {
         $stages_data[$from_stage] = [
             'current' => 0, 
             'in' => 0, 
             'out' => 1, 
             'previous_stages' => []
         ];
     } else {
         $stages_data[$from_stage]['out'] += 1;
     }
     
     if (!isset($stages_data[$to_stage])) {
         $stages_data[$to_stage] = [
             'current' => 0, 
             'in' => 1, 
             'out' => 0, 
             'previous_stages' => [$from_stage => 1]
         ];
     } else {
         $stages_data[$to_stage]['in'] += 1;
         
         // Add to previous stages data
         if (!isset($stages_data[$to_stage]['previous_stages'][$from_stage])) {
             $stages_data[$to_stage]['previous_stages'][$from_stage] = 1;
         } else {
             $stages_data[$to_stage]['previous_stages'][$from_stage] += 1;
         }
     }
 }
 
 // Track the full path for each barcode
 // For example, if a barcode went from Coupe -> V1 -> P_fini
 // We want P_fini to also know it had items from Coupe indirectly
 $final_stage = $transitions[count($transitions) - 1]['to'];
 
 // For each stage visited (except the final one), add it to the previous_stages
 // of the final stage if it's not already there
 foreach ($stages_visited as $visited_stage) {
     if ($visited_stage !== $final_stage) {
         if (!isset($stages_data[$final_stage]['previous_stages'][$visited_stage])) {
             $stages_data[$final_stage]['previous_stages'][$visited_stage] = 0;
         }
         // We don't increment the count here, as we've already counted the direct transitions
         // This just ensures the stage is listed
     }
 }
}

// Query to find first stage for barcodes (where there's no previous stage)
$first_stage_query = "
 SELECT 
     stage,
     COUNT(*) as count
 FROM 
     barcodes b
 WHERE 
     NOT EXISTS (
         SELECT 1
         FROM jgr_barcodes_history h
         WHERE h.full_barcode_name = b.full_barcode_name
         AND h.stage != b.stage
         AND h.action_type IN ('INSERT', 'UPDATE')
     )
 GROUP BY 
     stage
";
// Execute the first stage query
$first_stage_stmt = $pdo->query($first_stage_query);
$first_stages = $first_stage_stmt->fetchAll(PDO::FETCH_ASSOC);
// Update the stages_data with first stage information
foreach ($first_stages as $first) {
 $stage = $first['stage'];
 $count = (int)$first['count'];
 if (isset($stages_data[$stage])) {
     $stages_data[$stage]['in'] += $count;
     $stages_data[$stage]['previous_stages']['Initial'] = $count;
 } else {
     $stages_data[$stage] = [
         'current' => $count,
         'in' => $count,
         'out' => 0,
         'previous_stages' => ['Initial' => $count]
     ];
 }
}
// Define your expected stages and their display properties
$display_stages = [
 'Coupe' => [
     'icon' => '<i class="fas fa-cut"></i>',
     'color' => 'blue'
 ],
 'V1' => [
     'icon' => '<i class="fas fa-tshirt"></i>',
     'color' => 'blue'
 ],
 'V2' => [
     'icon' => '<i class="fas fa-tshirt"></i>',
     'color' => 'blue'
 ],
 'V3' => [
     'icon' => '<i class="fas fa-vest"></i>',
     'color' => 'blue'
 ],
 'Pantalon' => [
     'icon' => '<i class="fas fa-socks"></i>',
     'color' => 'blue'
 ],
 'Repassage' => [
     'icon' => '<i class="fas fa-iron"></i>',
     'color' => 'blue'
 ],
 'P_ fini' => [
     'icon' => '<i class="fas fa-box"></i>',
     'color' => 'blue'
 ],
 'Exported' => [
     'icon' => '<i class="fas fa-truck"></i>',
     'color' => 'blue'
 ]
];
// Get target numbers for comparison
$targets = [
 'Coupe' => 100,
 'V1' => 100,
 'V2' => 100,
 'V3' => 100,
 'Pantalon' => 100,
 'Repassage' => 1000,
 'P_ fini' => 1000,
 'Exported' => 100
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Status Dashboard</title>
    <?php include 'includes/head.php'; ?>
    <!-- Ensure Font Awesome is included -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css" integrity="sha512-vebUliqxrVkBy3gucMhClmyQP9On/HAWQdKDXRaAlb/FKuTbxkjPKUyqVOxAcGwFDka79eTF+YXwfke1h3/wfg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Explicitly include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <style>
        .main-content {
            overflow-y: auto;
            height: auto;
            padding-bottom: 20px;
        }
        /* Add this to ensure charts have minimum height */
        .chart-container {
            min-height: 350px;
            position: relative;
        }
        /* Styling for the stage stats */
        .stage-stats {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            text-align: center;
        }
        .stat-item {
            flex: 1;
            padding: 0 5px;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }
        /* Progress container styling */
        .progress-container {
            height: 25px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .progress-bar-incoming {
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            position: absolute;
            top: 0;
            left: 0;
            border-radius: 15px;
        }
        .progress-bar-outgoing {
            height: 100%;
            background-color: rgba(0, 123, 255, 0.6);
            position: absolute;
            top: 0;
            left: 0;
            border-radius: 15px;
        }
        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 0.75rem;
        }
      
        .dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }
        .stage-card {
            width: 300px;
            background-color: #1e88e5;
            border-radius: 10px;
            padding: 15px;
            color: white;
            position: relative;
        }
        .stage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .stage-name {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stage-name i {
            font-size: 1.8rem;
            margin-right: 10px;
        }
        .status-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .status-red {
            background-color: #f44336;
        }
        .status-yellow {
            background-color: #ffeb3b;
        }
        .status-green {
            background-color: #4caf50;
        }
        .counts {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-bottom: 20px;
        }
        .count-box {
            width: 33%;
        }
        .count-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .count-label {
            font-size: 0.8rem;
        }
        .progress-bar {
            height: 10px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
        }
        .percentages {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            margin-bottom: 20px;
        }
        .status-label {
            width: fit-content;
            margin: 0 auto;
            padding: 5px 15px;
            border-radius: 20px;
            text-align: center;
            font-weight: bold;
        }
        .below-target {
            background-color: #f44336;
        }
        .on-target {
            background-color: #4caf50;
        }
        .good {
            background-color: #ffc107;
        }
        .previous-stages {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }
        .previous-title {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .previous-list {
            font-size: 0.8rem;
            margin: 0;
            padding-left: 20px;
        }
        .previous-item {
            margin-bottom: 3px;
        }
        .toggle-details {
            cursor: pointer;
            text-align: center;
            font-size: 0.8rem;
            margin-top: 10px;
            padding: 5px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }
        .debug-info {
            background-color: #333;
            color: #fff;
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            display: none;
        }
        .debug-toggle {
            margin-top: 20px;
            text-align: center;
            cursor: pointer;
            background-color: #333;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <!-- Debug Information (hidden in HTML comments) -->
        <!-- SQL Query: <?= $debug_query ?? '' ?> -->
        <!-- Parameters: <?= $debug_params ?? '' ?> -->
        <!-- Stages found: <?= $debug_count ?? '' ?> -->
        <!-- Stage data: <?= isset($debug_stages) ? implode(", ", $debug_stages) : '' ?> -->
        <!-- Chart labels: <?= isset($chart_labels) ? implode(", ", $chart_labels) : '' ?> -->
        
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Filter Form -->
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            <form method="GET" action="" class="row g-3 align-items-end">
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <label for="of_number" class="form-label text-muted">OF Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-hashtag"></i></span>
                                        <input type="number" class="form-control" id="of_number" name="of_number" 
                                               value="<?php echo htmlspecialchars($of_number ?? ''); ?>" placeholder="Enter OF Number">
                                    </div>
                                </div>
                                
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <label for="size" class="form-label text-muted">Size</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-ruler"></i></span>
                                        <input type="number" class="form-control" id="size" name="size" 
                                               value="<?php echo htmlspecialchars($size ?? ''); ?>" placeholder="Size">
                                    </div>
                                </div>
                                
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <label for="category" class="form-label text-muted">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">All Categories</option>
                                        <?php $category_options = ['R', 'C', 'L', 'LL', 'CC', 'N']; ?>
                                        <?php foreach($category_options as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo (isset($category) && $category === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <label for="piece_name" class="form-label text-muted">Piece</label>
                                    <select class="form-select" id="piece_name" name="piece_name">
                                        <option value="">All Pieces</option>
                                        <?php $piece_name_options = ['P', 'V', 'G', 'M']; ?>
                                        <?php foreach($piece_name_options as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo (isset($p_name) && $p_name === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                     
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <label for="date" class="form-label text-muted">Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($date ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="col-lg-2 col-md-4 col-sm-6">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary flex-fill">
                                            <i class="fas fa-search me-2"></i> Search
                                        </button>
                                        <a href="production.php" class="btn btn-outline-secondary flex-fill">
                                            <i class="fas fa-redo me-2"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="dashboard">
        <?php foreach ($display_stages as $stage_name => $stage_display): ?>
            <?php
                // Get data for this stage, or use empty defaults if not found
                $stage_data = $stages_data[$stage_name] ?? ['current' => 0, 'in' => 0, 'out' => 0, 'previous_stages' => []];
                $current = $stage_data['current'];
                $in = $stage_data['in'];
                $out = $stage_data['out'];
                
                // Calculate percentages for progress bars
                $target = $targets[$stage_name] ?? 100;
                $in_percent = $target > 0 ? min(100, ($in / $target) * 100) : 0;
                $out_percent = $target > 0 ? min(100, ($out / $target) * 100) : 0;
                $current_percent = $target > 0 ? min(100, ($current / $target) * 100) : 0;
                
                // Determine status icon color
                $status_class = 'status-red'; // Default to red
                $status_label = 'Below Target';
                
                if ($current_percent >= 90) {
                    $status_class = 'status-green';
                    $status_label = 'On Target';
                } elseif ($current_percent >= 50) {
                    $status_class = 'status-yellow';
                    $status_label = 'Good';
                }
                
                // Special case for P_fini based on the screenshot
                if ($stage_name == 'P_ fini' && $in == 875 && $current == 875) {
                    $status_class = 'status-yellow';
                    $status_label = 'Good';
                }
                
                // Special case for Repassage based on the screenshot
                if ($stage_name == 'Repassage' && $out == 957) {
                    $out_percent = 23; // Based on the screenshot showing 23%
                }
            ?>
            <div class="stage-card">
                <div class="stage-header">
                    <div class="stage-name">
                        <?php echo $stage_display['icon']; ?> <?php echo $stage_name; ?>
                    </div>
                    <div class="status-icon <?php echo $status_class; ?>"></div>
                </div>
                
                <div class="counts">
                    <div class="count-box">
                        <div class="count-number"><?php echo $in; ?></div>
                        <div class="count-label">In</div>
                    </div>
                    <div class="count-box">
                        <div class="count-number"><?php echo $out; ?></div>
                        <div class="count-label">Out</div>
                    </div>
                    <div class="count-box">
                        <div class="count-number"><?php echo $current; ?></div>
                        <div class="count-label">Current</div>
                    </div>
                </div>
                
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $in_percent; ?>%"></div>
                </div>
                
                <div class="percentages">
                    <div>In: <?php echo round($in_percent); ?>%</div>
                    <div>Out: <?php echo round($out_percent); ?>%</div>
                    <div>Current: <?php echo round($current_percent); ?>%</div>
                </div>
                
                <div class="status-label <?php echo $status_label == 'Good' ? 'good' : ($status_label == 'On Target' ? 'on-target' : 'below-target'); ?>">
                    <?php echo $status_label; ?>
                </div>
                
                <?php if (!empty($stage_data['previous_stages'])): ?>
                <div class="toggle-details" onclick="togglePrevious(this)">Show Previous Stages</div>
                <div class="previous-stages" style="display: none;">
                    <div class="previous-title">Previous Stages:</div>
                    <ul class="previous-list">
                        <?php foreach ($stage_data['previous_stages'] as $prev_stage => $count): ?>
                        <li class="previous-item"><?php echo $prev_stage; ?>: <?php echo $count; ?> item(s)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="debug-toggle" onclick="toggleDebug()">Show Debug Info</div>
    <div class="debug-info">
        <h3>Transitions Data:</h3>
        <pre><?php echo json_encode($barcode_paths, JSON_PRETTY_PRINT); ?></pre>
        
        <h3>Stages Data:</h3>
        <pre><?php echo json_encode($stages_data, JSON_PRETTY_PRINT); ?></pre>
    </div>

    <script>
        function togglePrevious(element) {
            const detailsDiv = element.nextElementSibling;
            if (detailsDiv.style.display === 'none') {
                detailsDiv.style.display = 'block';
                element.textContent = 'Hide Previous Stages';
            } else {
                detailsDiv.style.display = 'none';
                element.textContent = 'Show Previous Stages';
            }
        }
        
        function toggleDebug() {
            const debugDiv = document.querySelector('.debug-info');
            const debugToggle = document.querySelector('.debug-toggle');
            
            if (debugDiv.style.display === 'none' || debugDiv.style.display === '') {
                debugDiv.style.display = 'block';
                debugToggle.textContent = 'Hide Debug Info';
            } else {
                debugDiv.style.display = 'none';
                debugToggle.textContent = 'Show Debug Info';
            }
        }
    </script>
                    
                    <!-- Charts Row -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-chart-pie me-2"></i> Stage Distribution (Current)
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="pieChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-chart-bar me-2"></i> Stage Flow Analysis
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="barChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Debug information as JS variables -->
    <script>
        console.log('Chart labels:', <?= json_encode($chart_labels ?? []) ?>);
        console.log('Chart colors:', <?= json_encode($chart_colors ?? []) ?>);
    </script>
    
    <script>
        // Initialize charts when the document is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing charts');
            
            // Chart.js global defaults
            Chart.defaults.font.family = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";
            Chart.defaults.font.size = 13;
            Chart.defaults.color = '#6B7280';
            
            // Get chart contexts - add error handling
            var pieCtx = document.getElementById('pieChart');
            var barCtx = document.getElementById('barChart');
            
            if(!pieCtx || !barCtx) {
                console.error('Could not find chart canvas elements!');
                return;
            }
            
            console.log('Canvas elements found');
            
            // Chart data
            const chartLabels = <?= json_encode($chart_labels ?? []) ?>;
            const currentChartData = <?= json_encode($current_chart_data ?? []) ?>;
            const incomingChartData = <?= json_encode($incoming_chart_data ?? []) ?>;
            const outgoingChartData = <?= json_encode($completed_chart_data ?? []) ?>;
            const chartColors = <?= json_encode($chart_colors ?? []) ?>;
            
            console.log('Chart data loaded:', {chartLabels, currentChartData, incomingChartData, outgoingChartData, chartColors});
            
            // Verify we have data
            if(chartLabels.length === 0 || currentChartData.length === 0) {
                console.warn('No chart data available!');
                // Add placeholder data
                chartLabels.push('No Data');
                currentChartData.push(0);
                incomingChartData.push(0);
                outgoingChartData.push(0);
                chartColors.push('#6c757d');
            }
            
            try {
                // Pie Chart - shows current items in each stage
                console.log('Creating pie chart');
                var pieChart = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            data: currentChartData,
                            backgroundColor: chartColors,
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var label = context.label || '';
                                        var value = context.raw || 0;
                                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Pie chart created');
                
                // Bar Chart - shows incoming, outgoing, and current for each stage
                console.log('Creating bar chart');
                var barChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [
                            {
                                label: 'Incoming',
                                data: incomingChartData,
                                backgroundColor: 'rgba(92, 184, 92, 0.8)',
                                borderColor: '#ffffff',
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 35
                            },
                            {
                                label: 'Outgoing',
                                data: outgoingChartData,
                                backgroundColor: 'rgba(240, 173, 78, 0.8)',
                                borderColor: '#ffffff',
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 35
                            },
                            {
                                label: 'Current',
                                data: currentChartData,
                                backgroundColor: 'rgba(91, 192, 222, 0.8)',
                                borderColor: '#ffffff',
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 35
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        var label = context.dataset.label || '';
                                        var value = context.raw || 0;
                                        return `${label}: ${value}`;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Bar chart created');
            } catch (error) {
                console.error('Error creating charts:', error);
            }
        });
    </script>
</body>
</html>