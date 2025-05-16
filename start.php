<?php
$current_view = 'start.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming soon</title>

        <?php include 'includes/head.php'; ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<style>
    body {
        background-color: black;
    margin: 0;
    font-family: 'Montserrat', sans-serif;
    font-size: 1rem;
    color: white;
}
    content {
        background: url('https://source.unsplash.com/utwYoEu9SU8/4887×2759') center;
        background-size: cover;
        height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    h1 {
        letter-spacing: .2rem;
        text-transform: uppercase;
        background: black;
        padding: 15px 20px;
        margin: 0;
    }
</style>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
            
        <content>
            <p>Be ready, we are launching soon.</p>
            <h1>Coming Soon</h1>
        </content>
    </div>
</body>
</html>