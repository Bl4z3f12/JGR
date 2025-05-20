<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <link rel="icon" href="assets/stop.ico" type="image/png">
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- AdminLTE CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css" rel="stylesheet">
    
</head>
<style>
    body {
        background-color: #f8f9fa;
    }
    .card {
        margin: auto;
    }
    .message {
        font-size: 1.2rem;
        color:rgb(0, 0, 0);
    }
    .list-group a {
        color:black;
    }
</style>
<body class="bg-light d-flex flex-column justify-content-center align-items-center vh-100">
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto text-center">
                <div class="card shadow">
                    <div class="card-body p-2">
                        <!-- Lock Icon -->
                        <div class="mb-4">
                            <i class="fas fa-lock fa-4x text-danger"></i>
                        </div>
                        
                        <!-- Message -->
                        <div class="message">
                            <h1 class="mb-4">Oops! Access to this page is restricted</h1>
                            <p class="mb-4">You are not authorized to access this page. If you want full access to all services, contact the developer.</p>
                            
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>You are authorized to access the following pages:</h5>
                                <div class="list-group mt-3">
                                    <a href="production.php" class="list-group-item list-group-item-action">
                                        <i class="fa-solid fa-chart-pie"></i> Production
                                    </a>
                                    <a href="solped_search.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-id-card"></i> Search By Solped Client
                                    </a>
                                    <a href="RTCpublic.php" class="list-group-item list-group-item-action">
                                        <i class="fa-solid fa-circle-plus"></i> RTC [PUBLIC]
                                    </a>
                                    <a href="start.php" class="list-group-item list-group-item-action">
                                    <i class="fa-solid fa-plane-departure"></i> Lancement
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-muted">
                    <small>Please contact support if you need assistance</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>
</body>
</html>