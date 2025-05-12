<?php
function is_mobile() {
    return preg_match(
      '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|pal|phone|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|ucweb|vodafone|wap|windows ce|xda|xiino/i',
      $_SERVER['HTTP_USER_AGENT']
    );
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tagesschrift&display=swap');
</style>
<div class="container-fluid py-3 border-bottom text-white headertopmain">
  <div class="d-flex justify-content-between align-items-center">
    <!-- Left: Toggler Button and Title -->
    <div class="d-flex align-items-center">
      <!-- Toggler Button for Small Screens -->
      <button class="navbar-toggler d-block d-md-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <i class="fas fa-bars"></i>
      </button>
      <h2 class="fs-4 fw-normal m-0" style="font-family: 'Tagesschrift', sans-serif;">Barcode System (JGR_FORMENS) <span style="font-style: italic; font-size: 20px;">local workshop</span></h2>
    </div>

    <!-- Right: Username and Logout -->
    <div class="d-flex align-items-center">
        <i class="fas fa-user-circle me-2"></i>
      <span class="me-3">
        <?php if (isset($_SESSION['username'])): ?>
          <?php echo htmlspecialchars($_SESSION['username']); ?>
        <?php else: ?>
          Guest
        <?php endif; ?>
      </span>
      
        <?php if ( ! is_mobile() ): ?>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout 
            <i class="fas fa-sign-out-alt"></i>
            </a>
        <?php endif; ?>
    </div>
  </div>
</div>
