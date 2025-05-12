<div class="col-lg-2 col-md-3 d-none d-md-block text-white min-vh-100 p-0">
    <div class="d-flex flex-column h-100">
        <div class="p-3 text-center">
            <h5 class="offcanvas-title" style="font-family: 'Tagesschrift', sans-serif;">
                <img width="48" height="48" src="https://img.icons8.com/?size=100&id=68tF9RdBPrR6&format=png&color=000000" alt="factory-1"/>
                Barcode System
            </h5>
        </div>
        <div class="nav flex-column mt-1">       
            <a href="index.php" class="sidebar-item text-white <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>" tabindex="0">
                <div class="sidebar-item-icon"><i class="fa-solid fa-qrcode"></i></div>
                Dashboard
            </a>
            <a href="scantoday.php" class="sidebar-item text-white <?php echo $current_view === 'scantoday.php' ? 'active' : ''; ?>" onclick="document.getElementById('loadingOverlay').style.display = 'flex'">
                <div class="sidebar-item-icon"><img width="25" height="25" src="https://img.icons8.com/fluency-systems-filled/48/FFFFFF/barcode-scanner-2.png" alt="barcode-scanner-2"/></div>
                Scanned Today
            </a>
            <a href="production.php" id="productionNavLink" class="sidebar-item text-white <?php echo $current_view === 'production.php' ? 'active' : ''; ?>">
                <div class="sidebar-item-icon"><i class="bi bi-graph-up-arrow"></i></div>
                Production <span style="font-size: 12px; font-style: italic;">&nbsp;[May Display Slowly]</span>
            </a>
            <a href="solped_search.php" class="sidebar-item text-white <?php echo $current_view == 'solped_search.php' ? 'active' : ''; ?>">
                <div class="sidebar-item-icon"><i class="fas fa-id-card"></i></div>
                Search by Solped Client
            </a>
            <a href="barcode_settings.php" class="sidebar-item text-white <?php echo $current_view === 'barcode_settings.php' ? 'active' : ''; ?>">
                <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
                Barcodes Settings
            </a>
            <a href="history.php" class="sidebar-item text-white <?php echo $current_view === 'history.php' ? 'active' : ''; ?>">
                <div class="sidebar-item-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                History (logs)
            </a>
            <a href="scanner_system_download.php" class="sidebar-item text-white <?php echo $current_view === 'scanner_system_download.php' ? 'active' : ''; ?>">
                <div class="sidebar-item-icon"><i class="fas fa-download me-2"></i></div>
                Scanner System Download
            </a>
            <a href="onlinewebsite.php" class="sidebar-item text-white <?php echo $current_view === 'onlinewebsite.php' ? 'active' : ''; ?>">
                <div class="sidebar-item-icon"><i class="fa-solid fa-earth-africa"></i></div>
                Online Website
            </a>
        </div>
        
        <!-- Footer note positioned at the bottom -->
        <div class="mt-auto p-3 text-center small text-white">
            <i class="fa-solid fa-circle-info"></i>
            This program is under development and improvement, the final version will be released soon
            <br><br>
            Made with <i title="love", class="fa-solid fa-heart"></i>
        </div>
    </div>
</div>

<!-- Mobile sidebar (off-canvas) -->
<div class="offcanvas offcanvas-start text-white" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarMenuLabel" style="font-family: 'Tagesschrift', sans-serif;">
            <img width="48" height="48" src="https://img.icons8.com/?size=100&id=68tF9RdBPrR6&format=png&color=000000" alt="factory-1"/>
            Barcodes System
        </h5>
        <button type="button" class="btn-close text-reset bg-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="nav flex-column h-100 d-flex flex-column">
            <div>
                <a href="index.php" class="sidebar-item text-white <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>" tabindex="0">
                    <div class="sidebar-item-icon"><i class="fa-solid fa-qrcode"></i></div>
                    Dashboard
                </a>

                <a href="scantoday.php" class="sidebar-item text-white <?php echo $current_view === 'scantoday.php' ? 'active' : ''; ?>" onclick="document.getElementById('loadingOverlay').style.display = 'flex'">
                    <div class="sidebar-item-icon"><img width="25" height="25" src="https://img.icons8.com/fluency-systems-filled/48/FFFFFF/barcode-scanner-2.png" alt="barcode-scanner-2"/></div>
                    Scanned Today
                </a>
                <a href="production.php" id="productionNavLink" class="sidebar-item text-white <?php echo $current_view === 'production.php' ? 'active' : ''; ?>">
                    <div class="sidebar-item-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    Production
                </a>
                <a href="solped_search.php" class="sidebar-item text-white <?php echo $current_view == 'solped_search.php' ? 'active' : ''; ?>">
                    <div class="sidebar-item-icon"><i class="fas fa-id-card"></i></div>
                    Search by Solped Client
                </a>
                <a href="barcode_settings.php" class="sidebar-item text-white <?php echo $current_view === 'barcode_settings.php' ? 'active' : ''; ?>">
                    <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
                    Barcodes Settings
                </a>
                <a href="history.php" class="sidebar-item text-white <?php echo $current_view === 'history.php' ? 'active' : ''; ?>">
                    <div class="sidebar-item-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    History (logs)
                </a>
                <a href="scanner_system_download.php" class="sidebar-item text-white <?php echo $current_view === 'scanner_system_download.php' ? 'active' : ''; ?>">
                    <div class="sidebar-item-icon"><i class="fas fa-download me-2"></i></div>
                    Scanner System Download
                </a>
                <a href="onlinewebsite.php" class="sidebar-item text-white <?php echo $current_view === 'onlinewebsite.php' ? 'active' : ''; ?>">
                    <div class="sidebar-item-icon"><i class="fa-solid fa-earth-africa"></i></div>
                    Online Website
                </a>
                
                <a href="logout.php" class="sidebar-item text-white">
                    <div class="sidebar-item-icon"><i class="fas fa-sign-out-alt"></i></div>
                    Logout
                </a>
            </div>
            
            <div class="mt-auto p-3 text-center small text-white">
                <i class="fa-solid fa-circle-info"></i>
                This program is under development and improvement, the final version will be released soon
                <br><br>
                Made with <i title="love", class="fa-solid fa-heart"></i>
            </div>
        </div>
    </div>
</div>