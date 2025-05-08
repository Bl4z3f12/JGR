<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tagesschrift&display=swap');

.flex-column{
  background: #17202a;
  flex-direction: column !important;
}
.offcanvas-header{
  background: #17202a;

}
/* Updated Sidebar Styles */
.col-lg-2.col-md-3.bg-dark {
    background: linear-gradient(to bottom, #4A90E2, #005BBB) !important;
    border-top-right-radius: 50px;
    border-bottom-right-radius: 50px;
    padding: 30px 10px !important;
}

/* Updated sidebar item styling */
.sidebar-item {
    width: 100%;
    display: flex;
    align-items: center;
    padding: 10px 20px;
    margin-bottom: 8px;
    color: rgba(120, 122, 0, 0.85);
    text-decoration: none;
    border-radius: 30px 0px 0px 30px;
    transition: background 0.3s, color 0.3s;
}

.sidebar-item-icon {
    margin-right: 12px;
    font-size: 1.1em;
    color: inherit;
}

.sidebar-item.active {
    background: #080080;
    background:  linear-gradient(90deg,rgba(10, 0, 194, 1) 0%, rgba(0, 0, 255, 1) 52%, rgba(0, 159, 191, 1) 100%);
    color: #005BBB;
    height: 15%;
    border-radius: 30px 0px 0px 30px;}

.sidebar-item:not(.active):hover {
    background: rgba(255, 255, 255, 0.2);
    color:rgb(104, 16, 16);
}

/* Mobile offcanvas sidebar styling */
.offcanvas.bg-dark {
    background: linear-gradient(to bottom, #4A90E2, #005BBB) !important;
}
</style>

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
            <a href="scantoday.php" class="sidebar-item text-white <?php echo $current_view === 'scantoday.php' ? 'active' : ''; ?>">
                <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
                Scanned Today
            </a>
            <a href="production.php" class="sidebar-item text-white <?php echo $current_view === 'production.php' ? 'active' : ''; ?>">
                <div class="sidebar-item-icon"><i class="bi bi-graph-up-arrow"></i></div>
                Production
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
                <a href="scantoday.php" class="sidebar-item text-white <?php echo $current_view === 'scantoday.php' ? 'active' : ''; ?>">
                    <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    Scanned Today
                </a>
                <a href="production.php" class="sidebar-item text-white <?php echo $current_view === 'production.php' ? 'active' : ''; ?>">
                    <div class="sidebar-item-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    Production
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