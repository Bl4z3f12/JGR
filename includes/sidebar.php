<div class="sidebar">
    <a href="index.php" class="sidebar-item <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-qrcode"></i></div>
        Dashboard
    </a>
    <a href="?view=today" class="sidebar-item <?php echo $current_view === 'today' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
        Scanned Today
    </a>
    <a href="?view=production" class="sidebar-item <?php echo $current_view === 'production' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-chart-line"></i></div>
        Production
    </a>
    <a href="?view=export" class="sidebar-item <?php echo $current_view === 'export' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-file-export"></i></div>
        Export
    </a>
    <a href="barcode_settings.php" class="sidebar-item <?php echo $current_view === 'Settings' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
        Barcodes Settings
    </a>
</div>