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
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content">
            <div class="content-header">
                <h2 class="content-title">
                    <i class="fas fa-download me-2"></i> Scanner Program Downloads/ Updates
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
                                <a href="#" id="down" class="btn btn-primary w-100 mb-2">
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
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle download button clicks
            const downloadButtons = document.querySelectorAll('.btn-download');
            downloadButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Download starting... Please wait.');
                    // In a real application, this would redirect to the download file
                    // window.location.href = this.href;
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile detection and button modification
            function isMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            }

            if (isMobile()) {
                const downloadButtons = document.querySelectorAll('.download-card .btn');
                downloadButtons.forEach(button => {
                    button.innerHTML = 'You cannot download this program, it is only for computers';
                    if (button.tagName === 'A') {
                        button.removeAttribute('href');
                    }
                    button.classList.add('pe-none', 'btn-secondary');
                    button.classList.remove('btn-primary');
                });
            }

            // Existing download button click handling (non-functional in original code)
            const downloadButtons = document.querySelectorAll('.btn-download');
            downloadButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Download starting... Please wait.');
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle "download button above" link click
            document.querySelectorAll('.download-href').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetBtn = document.getElementById('down');
                    
                    if (targetBtn) {
                        // Smooth scroll to the button
                        targetBtn.scrollIntoView({ behavior: 'smooth', block: "center" });
                        
                        // Add flash effect
                        targetBtn.classList.add('flash-effect');
                        
                        // Remove effect after animation completes
                        setTimeout(() => {
                            targetBtn.classList.remove('flash-effect');
                        }, 3000); // Matches 3 iterations of 1s animation
                    }
                });
            });
        });
    </script>
</body>
</html>