<div class="sidebar">
    <a href="index.php" class="sidebar-item <?php echo $current_view === 'dashboard' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-qrcode"></i></div>
        Dashboard
    </a>
    <a href="scantoday.php" class="sidebar-item <?php echo $current_view === 'today' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
        Scanned Today
    </a>
   
    <a href="barcode_settings.php" class="sidebar-item <?php echo $current_view === 'Settings' ? 'active' : ''; ?>">
        <div class="sidebar-item-icon"><i class="fa-solid fa-wrench"></i></div>
        Barcodes Settings
    </a>
</div>