
<div class="col-lg-2 col-md-3 d-none d-md-block bg-dark text-white min-vh-100 p-0">
                <div class="d-flex flex-column h-100">
                    <div class="p-3 text-center">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-industry me-2"></i>
                            Barcode System
                        </h5>
                    </div>
                    <div class="nav flex-column mt-1">       
                        <a href="index.php" class="sidebar-item  text-white <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>" tabindex="0">
                        <div class="sidebar-item-icon"><i class="fa-solid fa-qrcode"></i></div>
                          Dashboard
                        </a>
                        <a href="scantoday.php" class="sidebar-item text-white <?php echo $current_view === 'scantoday.php' ? 'active' : ''; ?>">
                          <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
                          Scanned Today
                        </a>
                        <a href="diagramme.php" class="sidebar-item text-white <?php echo $current_view === 'diagramme.php' ? 'active' : ''; ?>">
                          <div class="sidebar-item-icon"><i class="fa-solid fa-chart-line"></i></div>
                          Production
                        </a>
                        <a href="history.php" class="sidebar-item text-white <?php echo $current_view === 'history.php' ? 'active' : ''; ?>">
                          <div class="sidebar-item-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                          History Records
                        </a>
                        <a href="barcode_settings.php" class="sidebar-item text-white <?php echo $current_view === 'barcode_settings.php' ? 'active' : ''; ?>">
                          <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
                          Barcodes Settings
                        </a>
                    </div>
                    <div class="mt-auto">
                        <a href="logout.php" class="nav-link text-white-50 py-3">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarMenuLabel">
                <i class="fas fa-industry me-2"></i> Production System
            </h5>
            <button type="button" class="btn-close text-reset bg-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="nav flex-column">
                  
                        <a href="index.php" class="sidebar-item  text-white <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>" tabindex="0">
                        <div class="sidebar-item-icon"><i class="fa-solid fa-qrcode"></i></div>
                          Dashboard
                        </a>
                        <a href="scantoday.php" class="sidebar-item text-white <?php echo $current_view === 'scantoday.php' ? 'active' : ''; ?>">
                          <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
                          Scanned Today
                        </a>
                        <a href="diagramme.php" class="sidebar-item text-white <?php echo $current_view === 'diagramme.php' ? 'active' : ''; ?>">
                          <div class="sidebar-item-icon"><i class="fa-solid fa-chart-line"></i></div>
                          Production
                        </a>
                        <a href="history.php" class="sidebar-item text-white <?php echo $current_view === 'history.php' ? 'active' : ''; ?>">
                          <div class="sidebar-item-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                          History Records
                        </a>
                        <a href="barcode_settings.php" class="sidebar-item text-white <?php echo $current_view === 'barcode_settings.php' ? 'active' : ''; ?>">
                          <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
                          Barcodes Settings
                        </a>
                <a href="logout.php" class="nav-link text-white py-3">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
   