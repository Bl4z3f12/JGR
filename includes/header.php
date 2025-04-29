


<div class="container-fluid bg-dark py-3 border-bottom text-white position-relative">
    <div class="row align-items-center">
        <!-- Toggler Button for Small Screens -->
        <div class="col-auto d-block d-md-none">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="col">
            <h2 class="fs-4 fw-normal m-0"> Barcode System (JGR_FORMENS)</h2>
        </div>

        <div class="col-auto">
    <div class="dropdown text-white">
        <?php if (isset($_SESSION['user'])): ?>
            <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
        <?php else: ?>
            Guest
        <?php endif; ?>
    </div>
</div>

    </div>
</div>

