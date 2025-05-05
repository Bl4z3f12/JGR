<?php
$current_view = 'scanner_system_download.php'; // Set the current view to highlight the correct sidebar item
require_once 'auth_functions.php';

// Redirect to login page if not logged in
requireLogin('login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .download-card {
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            transition: all 0.3s;
        }
        .download-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .download-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .download-body {
            padding: 15px;
        }
        .download-footer {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-top: 1px solid #dee2e6;
        }
        .version-badge {
            margin-left: 10px;
        }
        .feature-list {
            list-style-type: none;
            padding-left: 0;
        }
        .feature-list li {
            padding: 5px 0;
        }
        .feature-list li i {
            margin-right: 10px;
        }
        .section-heading {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .changelog-item {
            margin-bottom: 15px;
            padding-left: 15px;
            border-left: 3px solid #007bff;
        }
        .latest{
            color: #000;
            font-size: 0.8rem;
            font-weight: 500;
            background-color: #e2e3e5;
            padding: 2px 5px;
            border-radius: 5px;
            font-style: italic;
        }
        .support-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #343a40;
        }
        .pe-none {
            cursor: not-allowed;       /* Force the no-entry cursor */
        }

        @keyframes button-flash {
        0%, 100% { box-shadow: 0 0 15px transparent; }
        50% { box-shadow: 0 0 15px #FF00CC; }
        }
        .flash-effect {
            animation: button-flash 1s ease 3; /* Flash 3 times */
            position: relative;
        }
        
        #atelierModal .modal-content {
        border: none;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
        
        #atelierModal .modal-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .version-container {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .version-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .version-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .version-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        .version-card {
            display: flex;
            align-items: center;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
        }
        
        .version-card:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .version-card.active {
            background-color: #e8f3ff;
            border-color: #007bff;
        }
        
        .version-icon {
            width: 40px;
            height: 40px;
            background: #e9f5ff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #007bff;
            font-size: 1.2rem;
        }
        
        .version-details {
            flex: 1;
        }
        
        .version-details h6 {
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .modal-footer {
            border-top: 1px solid #eaeaea;
        }
        
        /* Animation for cards */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .version-card {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        .version-card:nth-child(1) { animation-delay: 0.1s; }
        .version-card:nth-child(2) { animation-delay: 0.15s; }
        .version-card:nth-child(3) { animation-delay: 0.2s; }
        .version-card:nth-child(4) { animation-delay: 0.25s; }
        .version-card:nth-child(5) { animation-delay: 0.3s; }
        .version-card:nth-child(6) { animation-delay: 0.35s; }
        .version-card:nth-child(7) { animation-delay: 0.4s; }

        /* Disabled state for mobile */
        .version-card.disabled {
        pointer-events: none;
        position: relative;
        }

        .disabled-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        border-radius: 8px;
        }

        /* Mobile warning */
        .alert-warning {
        border-left: 4px solid #ffc107;
        background-color: #fff8e1;
        }

        /* Modal size adjustments */
        #atelierModal .modal-content {
        max-width: 100%;
        }

        @media (min-width: 992px) {
        #atelierModal .modal-lg {
            max-width: 800px;
        }
        }

</style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <div class="content-header">
                <h2 class="content-title">
                    Scanner Program Downloads/ Updates
                </h2>
            </div>

            <!-- Alert for updates -->
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <strong>There is no software update!</strong>
                <br>
                Version 2.5.0 has been released with improved performance and bug fixes, and database connection issues have been resolved. Please download the latest version for the best experience.
            </div>

            <!-- Downloads Section -->
            <div class="row">
                <!-- Latest Version -->
                <div class="col-lg-4 col-md-6">
                    <div class="download-card">
                        <div class="download-header">
                            <h4>
                                <i class="fas fa-box me-2"></i> Scanner Program v2.5.0
                                <span class="badge bg-success version-badge">Latest</span>
                            </h4>
                            <div class="text-muted">Released: May 1, 2025</div>
                        </div>
                        <div class="download-body">
                            <p>Latest version with enhanced performance and new features.</p>
                            <ul class="feature-list">
                                <li><i class="fas fa-check text-success"></i> Bug fixes for database connection</li>
                                <li><i class="fas fa-check text-success"></i> Enhanced security features</li>
                                <li><i class="fas fa-check text-success"></i> User interface improvements</li>
                                <li><i class="fas fa-check text-success"></i> Performance optimizations</li>
                                <li><i class="fas fa-check text-success"></i> Enhanced error handling</li>
                                <li><i class="fas fa-check text-success"></i> Improved memory management</li>
                                <li><i class="fas fa-check text-success"></i> Handle more data in less time</li>
                                <li><i class="fas fa-check text-success"></i> Avoid errors when scanning barcodes</li>
                                <li><i class="fas fa-check text-success"></i> Connect with MySQL databases</li>
                                <li><i class="fas fa-check text-success"></i> Handle large data and large volume</li>
                                <li>[...] and more</li>
                            </ul>
                            <div class="mt-3">
                                <a href="#" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#atelierModal" 
                                    id="down" 
                                    class="btn btn-primary w-100 mb-2">
                                        <i class="fas fa-download me-2"></i> Download (64-bit)
                                </a>
                            </div>
                        </div>
                        <div class="download-footer">
                            <div class="d-flex justify-content-between">
                                <span>Size: 45.2 MB</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stable Version -->
                <div class="col-lg-4 col-md-6">
                    <div class="download-card">
                        <div class="download-header">
                            <h4>
                                <i class="fas fa-box me-2"></i> Scanner Program v2.0.1
                                <span class="badge bg-secondary version-badge">EOL</span>
                            </h4>
                            <div class="text-muted">Released: Jan. 15, 2025</div>
                        </div>
                        <div class="download-body">
                            <p>Previous stable version with proven reliability.</p>
                            <ul class="feature-list">
                                <li><i class="fas fa-check text-success"></i> Stable performance</li>
                                <li><i class="fas fa-check text-success"></i> Compatible with older systems</li>
                                <li><i class="fas fa-check text-success"></i> Lower resource usage</li>
                                <li><i class="fas fa-check text-success"></i> No Security updates included</li>
                                <li><i class="fas fa-check text-success"></i> Basic data processing</li>
                                <li><i class="fas fa-check text-success"></i> Connect with sqlite database only</li>
                                <li><i class="fas fa-check text-success"></i> Handle less data and less volume</li>
                                <li><i class="fas fa-check text-success"></i> Avoid errors when scanning barcodes</li>                                
                            </ul>
                            <div class="mt-3">
                                <button class="btn btn-secondary w-100 mb-2" disabled>
                                    <i class="fas fa-download me-2"></i> Download (64-bit)
                                </button>
                            </div>
                            <div class="eol">
                                <p style="font-style: italic; font-size: 0.9rem; color:rgb(190, 190, 190);">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    End of Life (EOL): This version is no longer supported. Please upgrade to the latest version for continued support and updates
                                </p>
                            </div>
                        </div>
                        <div class="download-footer">
                            <div class="d-flex justify-content-between">
                                <span>Size: 42.8 MB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Requirements -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4><i class="fas fa-laptop me-2"></i> System Requirements</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li>Windows 10 (64-bit)</li>
                                <li>4GB RAM</li>
                                <li>Intel Core i3 / AMD Ryzen 3 or equivalent</li>
                                <li>200MB free disk space</li>
                                <li>.NET Framework 4.8</li>
                                <li>Internet connection</li>
                                <li>Scanner Device</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Installation Guide -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4><i class="fas fa-cog me-2"></i> Installation Guide</h4>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li class="mb-2">Download the latest version of the program via the <a href="#down" class="download-href">download button above</a>.</li>
                                <li class="mb-2">No installation needed.</li>
                                <li class="mb-2">Make sure that all your programs are not using port 3306.</li>
                                <li class="mb-2">Install additional components if needed.</li>
                                <li class="mb-2">Launch the Scanner Program from your desktop or start menu.</li>
                                <li class="mb-2">Ensure you have an active internet connection.</li>
                                <li class="mb-2">Make sure that your computer is connected to the same server network(LAN).</li>
                                <li class="mb-2">Make sure Scanner Device <img width="25" height="25" src="https://img.icons8.com/ios/50/barcode-scanner-2.png" alt="barcode-scanner-2"/> is connected properly.</li>
                                <li class="mb-2">Ensure all software dependencies are installed.</li>
                                <li class="mb-2">Disable Firewall.</li>
                                <li class="mb-2">Grant higher access permissions <i class="fa-solid fa-shield-halved"></i> to the program <span style="font-style: italic; font-size: 13px; color:rgb(158, 158, 158);">(comes automatically with the program)</span></li>
                                <li class="mb-2">Restart your computer if needed.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Changelog -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4><i class="fas fa-history me-2"></i> Changelog</h4>
                        </div>
                        <div class="card-body">
                            <div class="changelog-item">
                                <h5>Version 2.5.0 <span class="latest">latest</span></h5>
                                <ul>
                                    <li>Professional splash screen during initialization</li>
                                    <li>Real-time database connection status indicator</li>
                                    <li>Color-coded scan history with timestamps and status details</li>
                                        &nbsp;&nbsp;&nbsp;# <span style="color: green;">Green: Successful scans</span> &nbsp;
                                        # <span style="color: orange;">Orange: Warnings</span> &nbsp;
                                        # <span style="color: red;">Red: Errors</span>
                                    <li>Scanner toggle button (enable/disable scanning)</li>
                                    <li>Visual scan animation feedback</li>
                                    <li>Auto-truncated history (keeps last 50 scans)</li>
                                    <li>Improved error handling with detailed messages</li>
                                    <li>Enhanced user interface with modern design elements</li>
                                    <li>Improved error handling and logging</li>
                                    <li>Large/ multi data handling capabilities</li>
                                </ul>
                            </div>
                            <div class="changelog-item">
                                <h5>Version 2.0.1 </h5>
                                <ul>
                                    <li>Dual scanning modes (Inside/Outside pointing)</li>
                                    <li>Local data caching system</li>
                                    <li>PyGame audio dependency</li>
                                    <li>Registration/license enforcement system</li>
                                    <li>Arabic language UI elements</li>
                                </ul>
                            </div>
                            <div class="changelog-item">
                                <h5>Version 1.0.0 </h5>
                                <ul>
                                    <li>Server IP hardcoded in database connection</li>
                                    <li>Lacks multi-language support from previous version</li>
                                    <li>No bulk data synchronization capability</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="atelierModal" tabindex="-1" aria-labelledby="atelierModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="atelierModalLabel">
          <i class="fas fa-box-open me-2"></i>Select Atelier Version
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body p-4">
        <p class="text-muted mb-3">Please select the version you want to download:</p>
        
        <div class="version-container">
          <div class="row g-1">
            <div class="col-md-6">
              <div class="version-card" data-version="coupe">
                <div class="version-icon">
                  <i class="fas fa-cut"></i>
                </div>
                <div class="version-details">
                  <h6>Coupe</h6>
                  <span class="badge bg-info">v2.5.0</span>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="version-card" data-version="v1">
                <div class="version-icon">
                  <i class="fas fa-tshirt"></i>
                </div>
                <div class="version-details">
                  <h6>V1</h6>
                  <span class="badge bg-info">v2.5.0</span>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="version-card" data-version="v2">
                <div class="version-icon">
                  <i class="fas fa-vest"></i>
                </div>
                <div class="version-details">
                  <h6>V2</h6>
                  <span class="badge bg-info">v2.5.0</span>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="version-card" data-version="v3">
                <div class="version-icon">
                  <i class="fas fa-vest-patches"></i>
                </div>
                <div class="version-details">
                  <h6>V3</h6>
                  <span class="badge bg-info">v2.5.0</span>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="version-card" data-version="pantalan">
                <div class="version-icon">
                  <i class="fas fa-socks"></i>
                </div>
                <div class="version-details">
                  <h6>PANTALAN</h6>
                  <span class="badge bg-info">v2.5.0</span>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="version-card" data-version="repassage">
                <div class="version-icon">
                  <i class="fas fa-iron"></i>
                </div>
                <div class="version-details">
                  <h6>REPASSAGE</h6>
                  <span class="badge bg-info">v2.5.0</span>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="version-card" data-version="p_fini">
                <div class="version-icon">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="version-details">
                  <h6>P_FINI</h6>
                  <span class="badge bg-info">v2.5.0</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer bg-light">
        <div class="d-flex align-items-center me-auto">
          <i class="fas fa-info-circle text-primary me-2"></i>
          <small class="text-muted">Click on a version to download</small>
        </div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>




    <?php include 'includes/footer.php'; ?>



    
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Function to check if device is mobile
    function isMobile() {
      return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // Mapping of Atelier versions to their specific filenames
    const versionMap = {
      'coupe': 'coupe.exe',
      'v1': 'V1.exe',
      'v2': 'V2.exe',
      'v3': 'V3.exe',
      'pantalan': 'Pantalan.exe',
      'repassage': 'Repassage.exe',
      'p_fini': 'P_Fini.exe'
    };

    // Disable downloads on mobile devices
    if (isMobile()) {
      // Add mobile warning to modal
      const modalBody = document.querySelector('#atelierModal .modal-body');
      const mobileWarning = document.createElement('div');
      mobileWarning.className = 'alert alert-warning mb-3';
      mobileWarning.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> <strong>Mobile device detected!</strong> Downloads are only available on desktop computers.';
      modalBody.insertBefore(mobileWarning, modalBody.firstChild);
      
      // Disable all version cards
      document.querySelectorAll('.version-card').forEach(card => {
        card.classList.add('disabled');
        card.style.opacity = '0.6';
        card.style.cursor = 'not-allowed';
        
        // Add disabled overlay
        const overlay = document.createElement('div');
        overlay.className = 'disabled-overlay';
        card.appendChild(overlay);
      });
      
      // Also modify the main download button on the page
      const downloadTriggers = document.querySelectorAll('[data-bs-target="#atelierModal"]');
      downloadTriggers.forEach(trigger => {
        trigger.innerHTML = '<i class="fas fa-ban me-2"></i> Not available on mobile devices';
        trigger.classList.add('pe-none', 'btn-secondary');
        trigger.classList.remove('btn-primary');
      });
    } else {
      // Desktop functionality
      document.querySelectorAll('.version-card').forEach(card => {
        card.addEventListener('click', function() {
          // Remove active class from all cards
          document.querySelectorAll('.version-card').forEach(c => c.classList.remove('active'));
          
          // Add active class to clicked card
          this.classList.add('active');
          
          const version = this.dataset.version;
          const filename = versionMap[version];
          
          if (filename) {
            // Show download starting feedback
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '5000';
            toast.innerHTML = `
              <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                  <i class="fas fa-download text-primary me-2"></i>
                  <strong class="me-auto">Download Started</strong>
                  <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                  Downloading ${filename} now...
                </div>
              </div>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
              document.body.removeChild(toast);
            }, 3000);
            
            // Create and trigger download
            const link = document.createElement('a');
            link.href = `downloads/${filename}`;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Close modal with slight delay for better UX
            setTimeout(() => {
              bootstrap.Modal.getInstance(document.getElementById('atelierModal')).hide();
            }, 500);
          } else {
            console.error('No file mapped for version:', version);
          }
        });
      });
    }
  });
</script>




</body>
</html>