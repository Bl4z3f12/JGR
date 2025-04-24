<div class="sidebar" class="col-12 col-sm-4 col-md-3 col-lg-2">
    <a href="index.php" class="sidebar-item <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>" tabindex="0">
        <div class="sidebar-item-icon"><i class="fa-solid fa-qrcode"></i></div>
        Dashboard
    </a>

    <a href="scantoday.php" class="sidebar-item <?php echo $current_view === 'scantoday.php' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
        Scanned Today
    </a>
    <a href="barcode_settings.php" class="sidebar-item <?php echo $current_view === 'barcode_settings.php' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
        Barcodes Settings
    </a>
    <a href="history.php" class="sidebar-item <?php echo $current_view === 'history.php' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        History Records
    </a>
    <a href="scantoday.php" class="sidebar-item <?php echo $current_view === 'today' ? 'active' : ''; ?>" tabindex="0">
        <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
        Scanned Today
    </a>
    <a href="history.php" class="sidebar-item <?php echo $current_view === 'history' ? 'active' : ''; ?>" tabindex="0">
        <div class="sidebar-item-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        History Records
    </a>
    <a href="barcode_settings.php" class="sidebar-item <?php echo $current_view === 'settings' ? 'active' : ''; ?>" tabindex="0">
        <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
        Barcodes Settings
    </a>

</div>