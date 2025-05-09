<?php
// register.php - Backend for user registration
//require_once 'auth_functions.php';

// Redirect to login page if not logged in

 //requireLogin('login.php');

// requireLogin('login.php');

//Bl4z3 was here
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Include the database configuration
require_once 'config.php';

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Process form submission (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validate form data
    if (empty($username) || empty($password) || empty($confirmPassword)) {
        $response['message'] = "All fields are required.";
    } 
    elseif (strlen($password) < 8) {
        $response['message'] = "Password must be at least 8 characters long.";
    }
    elseif ($password !== $confirmPassword) {
        $response['message'] = "Passwords do not match.";
    }
    else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $response['message'] = "Username already exists.";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $password_hash);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Registration successful! Redirecting to login page...";
            } else {
                $response['message'] = "Error: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
    
    // Return JSON response for AJAX requests
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit; // Stop execution here for AJAX requests
    } else {
        // For normal form submission
        if ($response['success']) {
            header('Location: login.php?registered=success');
            exit;
        }
    }
}

// Close database connection 
$conn->close();

// ONLY render the HTML form if this is a direct access to the page
if (!$isAjaxRequest) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="icon" href="assets\favicon.png" type="image/png">
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<style>
    :root {
        --primary: #3a86ff;
        --primary-hover: #2970e6;
        --secondary: #ff006e;
        --dark: #192841;
        --light: #ffffff;
        --success: #38b000;
        --error: #d90429;
        --bg-gradient: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
    }

    * {
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: var(--bg-gradient);
        min-height: 100vh;
        overflow-x: hidden;
    }
    
    .animated-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    .floating-circles div {
        position: absolute;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.01));
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        animation: float 15s infinite linear;
    }

    @keyframes float {
        0% { transform: translateY(0) rotate(0deg); opacity: 0; }
        10% { opacity: 0.8; }
        90% { opacity: 0.6; }
        100% { transform: translateY(-100vh) rotate(360deg); opacity: 0; }
    }

    .register-container {
        padding: 10px;
    }

    .card-body {              
        background: rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        padding: 2.5rem;
        transition: all 0.3s ease;
    }

    .card-body:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.25);
    }

    .logo {
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .logo img {
        height: 60px;
        margin-bottom: 10px;
    }

    .card-title {
        text-align: center;
        color: var(--light);
        font-weight: 600;
        font-size: 1.8rem;
        margin-bottom: 1.8rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        letter-spacing: 1px;
    }

    .form-label {
        color: var(--light);
        font-weight: 500;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 12px 15px;
        color: var(--light);
        backdrop-filter: blur(5px);
        transition: all 0.3s ease;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.25);
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.25);
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }

    .input-group {
        position: relative;
    }

    .password-toggle {
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.7);
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        padding: 5px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .password-toggle:hover {
        color: var(--light);
        transform: translateY(-50%) scale(1.1);
    }

    .password-toggle:active {
        transform: translateY(-50%) scale(0.95);
    }

    .password-toggle:focus {
        outline: none;
    }
    
    .password-toggle i{
        color: black;
    }

    .btn-primary {
        background: var(--primary);
        border: none;
        border-radius: 12px;
        padding: 12px;
        font-weight: 600;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.3);
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(58, 134, 255, 0.4);
    }

    .btn-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 10px rgba(58, 134, 255, 0.3);
    }

    .footer-link {
        text-align: center;
        font-size: 0.9rem;
        margin-top: 1.5rem;
    }

    .footer-link a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .footer-link a:hover {
        color: var(--secondary);
        text-decoration: underline;
    }

    .alert-translucent {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: var(--light);
        border-radius: 12px;
        padding: 12px 15px;
    }

    .alert-danger {
        background: rgba(217, 4, 41, 0.15);
        border-color: rgba(217, 4, 41, 0.3);
    }

    .alert-success {
        background: rgba(56, 176, 0, 0.15);
        border-color: rgba(56, 176, 0, 0.3);
    }

    /* Enhanced password toggle interaction */
    .password-toggle .bi {
        transition: all 0.3s ease;
    }
    
    .password-toggle:hover .bi {
        transform: scale(1.1);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem;
        }
    }
</style>

<body>
    <div class="animated-bg">
        <div class="floating-circles">
            <?php for($i=0; $i<15; $i++): ?>
            <div style="
                width: <?= rand(80, 200) ?>px;
                height: <?= rand(80, 200) ?>px;
                left: <?= rand(0, 100) ?>%;
                top: <?= rand(110, 150) ?>%;
                animation-duration: <?= rand(15, 30) ?>s;
                animation-delay: <?= rand(0, 10) ?>s;"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="container">
        <div class="row min-vh-100 justify-content-center align-items-center">
            <div class="col-md-6 col-lg-5 col-xl-4 register-container">
                <div class="card-body">
                    <div class="logo">
                        <!-- Same logo as login page -->
                        <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M30 0C13.4315 0 0 13.4315 0 30C0 46.5685 13.4315 60 30 60C46.5685 60 60 46.5685 60 30C60 13.4315 46.5685 0 30 0ZM30 10C35.5228 10 40 14.4772 40 20C40 25.5228 35.5228 30 30 30C24.4772 30 20 25.5228 20 20C20 14.4772 24.4772 10 30 10ZM30 52C22.5 52 15.9 48.3 12 42.6C12.1 36.3 24 32.8 30 32.8C35.9 32.8 47.9 36.3 48 42.6C44.1 48.3 37.5 52 30 52Z" fill="url(#paint0_linear)"/>
                            <defs>
                                <linearGradient id="paint0_linear" x1="0" y1="0" x2="60" y2="60" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#3A86FF"/>
                                    <stop offset="1" stop-color="#FF006E"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    
                    <h3 class="card-title">Create Account</h3>

                    <!-- Message Container -->
                    <div id="messageContainer" class="mb-4">
                        <?php if (!empty($response['message']) && !$response['success']): ?>
                        <div class="alert alert-translucent alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($response['message']); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <form method="post" id="registerForm">
                        <!-- Username Input -->
                        <div class="mb-4">
                            <label for="username" class="form-label">
                                <i class="bi bi-person me-2"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-2"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                                <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            <small class="form-text text-light opacity-75">Password must be at least 8 characters long</small>
                        </div>

                        <!-- Confirm Password Input -->
                        <div class="mb-4">
                            <label for="confirmPassword" class="form-label">
                                <i class="bi bi-shield-lock me-2"></i>Confirm Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                                <button type="button" class="password-toggle" id="toggleConfirmPassword" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus me-2"></i>Sign Up
                            </button>
                        </div>
                        
                        <!-- Login Link -->
                        <div class="footer-link">
                            <span>Already have an account?</span> 
                            <a href="login.php">Sign in</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Improved password toggle function with better visual feedback
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = passwordField.parentElement.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
                // Add a small animation effect when showing password
                eyeIcon.classList.add('text-primary');
                setTimeout(() => {
                    eyeIcon.classList.remove('text-primary');
                }, 300);
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            }
            
            // Focus back on the password field
            passwordField.focus();
        }

        // Set up event listeners when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Set up password toggle button events
            const toggleBtn = document.getElementById('togglePassword');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    togglePassword('password');
                });
            }
            
            const toggleConfirmBtn = document.getElementById('toggleConfirmPassword');
            if (toggleConfirmBtn) {
                toggleConfirmBtn.addEventListener('click', function() {
                    togglePassword('confirmPassword');
                });
            }
            
            // Allow toggling password visibility with Alt+P key combination
            document.addEventListener('keydown', function(event) {
                if (event.altKey && event.key === 'p') {
                    if (document.activeElement.id === 'password' || document.activeElement.id === 'confirmPassword') {
                        togglePassword(document.activeElement.id);
                    }
                    event.preventDefault();
                }
            });
        });
    </script>
    
    <script>
        // Enhanced JavaScript with better visual feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const messageContainer = document.getElementById('messageContainer');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function(e) {
                if (window.fetch) {
                    e.preventDefault();
                    
                    // Clear previous messages
                    messageContainer.innerHTML = '';
                    
                    // Show loading state
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating account...';
                    submitBtn.disabled = true;
                    
                    const formData = new FormData(form);
                    
                    fetch('register.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Create new alert element
                        const alertDiv = document.createElement('div');
                        alertDiv.className = `alert alert-translucent alert-${data.success ? 'success' : 'danger'}`;
                        
                        // Add icon based on success/failure
                        const icon = document.createElement('i');
                        icon.className = `bi bi-${data.success ? 'check-circle' : 'exclamation-circle'} me-2`;
                        alertDiv.appendChild(icon);
                        
                        // Add message text
                        const textNode = document.createTextNode(data.message);
                        alertDiv.appendChild(textNode);
                        
                        // Add to message container
                        messageContainer.appendChild(alertDiv);
                        
                        // Reset button state
                        if (!data.success) {
                            submitBtn.innerHTML = '<i class="bi bi-person-plus me-2"></i>Sign Up';
                            submitBtn.disabled = false;
                        }

                        if (data.success) {
                            // Keep the loading state for successful registration
                            setTimeout(() => {
                                window.location.href = 'login.php?registered=success';
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-translucent alert-danger';
                        
                        const icon = document.createElement('i');
                        icon.className = 'bi bi-exclamation-triangle me-2';
                        errorDiv.appendChild(icon);
                        
                        const textNode = document.createTextNode('An error occurred. Please try again later.');
                        errorDiv.appendChild(textNode);
                        
                        messageContainer.appendChild(errorDiv);
                        
                        // Reset button state
                        submitBtn.innerHTML = '<i class="bi bi-person-plus me-2"></i>Sign Up';
                        submitBtn.disabled = false;
                    });
                }
            });
        });
    </script>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } ?>