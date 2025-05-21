<?php
function is_mobile() {
    return preg_match(
      '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|pal|phone|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|ucweb|vodafone|wap|windows ce|xda|xiino/i',
      $_SERVER['HTTP_USER_AGENT']
    );
}
?>

<!-- Add Bootstrap Icons CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tagesschrift&display=swap');
</style>
<div class="container-fluid py-3 border-bottom text-white headertopmain">
  <div class="d-flex justify-content-between align-items-center">
    <!-- Left: Toggler Button and Title -->
    <div class="d-flex align-items-center">
      <!-- Toggler Button for Small Screens -->
      <button class="navbar-toggler barnew d-block d-md-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <i class="fa-solid fa-bars-staggered"></i>
      </button>
      <h2 class="fs-4 fw-normal m-0 bigtitle" style="font-family: 'Tagesschrift', sans-serif;">Barcode System (JGR_FORMENS) <span style="font-style: italic; font-size: 20px;">local workshop</span></h2>
    </div>

    <!-- Right: Notifications, Username and Logout -->
    <div class="d-flex align-items-center">
      <i class="fas fa-user-circle me-2"></i>
      <span class="me-3">
        <?php if (isset($_SESSION['username'])): ?>
          <?php echo htmlspecialchars($_SESSION['username']); ?>
        <?php else: ?>
          Guest
        <?php endif; ?>
      </span>
      
      <?php if (!is_mobile()): ?>
        <div class="d-flex align-items-center">
          <a href="notifications.php" class="text-white me-3 position-relative">
            <i class="bi bi-bell-fill"></i>
            <span id="notificationBadge" class="badge position-absolute top-0 start-100 translate-middle bg-success">0</span>
          </a>
        </div>
      <?php endif; ?>
      
      <?php if (!is_mobile()): ?>
          <!-- Added Tracker Button -->
          <a href="tracker.php" class="btn btn-outline-light btn-sm me-2">
            Tracker <i class="bi bi-geo-alt-fill"></i>
          </a>
          <a href="logout.php" class="btn btn-outline-light btn-sm">
            Logout <i class="fas fa-sign-out-alt"></i>
          </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function updateNotificationCount() {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            if(badge) { // Check if badge exists
                badge.textContent = data.count;
                
                // Change color based on count
                if (data.count == 0) {
                    badge.classList.remove('bg-danger');
                    badge.classList.add('bg-success'); // Green for 0
                } else {
                    badge.classList.remove('bg-success');
                    badge.classList.add('bg-danger');   // Red for 1+
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

// Update on load and every 60 seconds
document.addEventListener('DOMContentLoaded', function() {
    updateNotificationCount();
    setInterval(updateNotificationCount, 60000);
});
</script>