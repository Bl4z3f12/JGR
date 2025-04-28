<?php
// login.php - Backend for user login

// First check if this is an AJAX request that needs JSON response
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Include the database configuration
require_once 'config.php';

// Start session
session_start();

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
    $remember = isset($_POST['rememberMe']) ? true : false;
    
    // Validate form data
    if (empty($username) || empty($password)) {
        $response['message'] = "Username and password are required.";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Update last login time
                $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;
                
                // Set remember me cookie if checked
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    
                    // Store token in database (you would need a separate table for this)
                    // This is a simplified approach
                    setcookie("remember_me", $token, time() + (30 * 24 * 60 * 60), "/"); // 30 days
                }
                
                $response['success'] = true;
                $response['message'] = "Login successful! Redirecting...";
                
                // For non-AJAX requests, redirect directly
                if (!$isAjaxRequest) {
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $response['message'] = "Invalid username or password.";
            }
        } else {
            $response['message'] = "Invalid username or password.";
        }
        
        $stmt->close();
    }
    
    // Return JSON response for AJAX requests
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit; // Stop execution here for AJAX requests
    }
    // For regular form submissions, we'll show errors in the form below
}

// Close database connection
$conn->close();

// ONLY render the HTML form if this is a direct access to the page (not an AJAX request)
if (!$isAjaxRequest) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="icon" href="assets\favicon.png" type="image/png">
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<style>

    :root {
        --tg-blue: #0088cc;
        --tg-light-blue: #40a7e3;
        --tg-bg: 
        linear-gradient(135deg, #214864 0%, #1c3566 15%, #0a1c5c 30%, #101d54 48%, #19215e 65%, #0e125a 85%, #000076 100%)
    }

    body {
        background: var(--tg-bg);
        min-height: 100vh;
        overflow: hidden;
    }

    .floating-circles div {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        animation: float 5s infinite linear;
    }

    @keyframes float {
        0% { transform: translateY(0) rotate(0deg); }
        100% { transform: translateY(-100vh) rotate(360deg); }
    }
    .card-body {              
        background: rgba(255, 255, 255, 0.27);
        border-radius: 16px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 255, 255, 0.45);
    }
    .text-center{
        text-align: center;
        color: white;   
    }
    .form-label {
        color: white;
    }
    .form-check-label{
        color: white;
    }
    .text-center a{
        color: white;

    }
</style>

<body class="bg-light">

    <div class="floating-circles">
        <?php for($i=0; $i<15; $i++): ?>
        <div style="
            width: <?= rand(50, 150) ?>px;
            height: <?= rand(50, 150) ?>px;
            left: <?= rand(0, 100) ?>%;
            top: <?= rand(100, 150) ?>%;
<<<<<<< HEAD
            animation-delay: <?= rand(0, 10) ?>s;
        "></div>
=======
            animation-delay: <?= rand(0, 10) ?>"></div>
>>>>>>> 2cd3e7705666e0ea92f5796b66cbfa6c3c200ef4
        <?php endfor; ?>
    </div>

    <div class="container">
        <div class="row min-vh-100 justify-content-center align-items-center">
            <div class="col-md-6 col-lg-4">
                <div class="card_shadow">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Login</h3>
                        

                        <!-- Message Container -->
                        <div id="messageContainer" class="mb-3">
                            <?php if (!empty($response['message']) && !$response['success']): ?>
                            <div class="alert alert-translucent alert-danger">
                                <?php echo htmlspecialchars($response['message']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                            <div class="alert alert-translucent alert-success">
                                Registration successful! You can now log in.
                            </div>
                            <?php endif; ?>
                        </div>

                        <form method="post" id="loginForm">
                            <!-- Username Input -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                            </div>

                            <!-- Password Input -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" onclick="togglePassword('password')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me Checkbox -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                                <label class="form-check-label" for="rememberMe">Remember me</label>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">Sign In</button>
                            </div>
                            
                            <!-- Register Link -->
                            <div class="text-center">
                                <small>Don't have an account? <a href="register.php">Register here</a></small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
        const passwordField = document.getElementById(fieldId);
        const eyeIcon = passwordField.parentElement.querySelector('i');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
        } else {
            passwordField.type = 'password';
            eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
        }
    }
    </script>
    

    <script>
    // Modified JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        const messageContainer = document.getElementById('messageContainer');
        
        form.addEventListener('submit', function(e) {
            if (window.fetch) {
                e.preventDefault();
                
                // Clear previous messages
                messageContainer.innerHTML = '';
                
                const formData = new FormData(form);
                
                fetch('login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
<<<<<<< HEAD
                    // Create new alert element
                    const alertDiv = document.createElement('div');
                    alertDiv.className = `alert alert-translucent alert-${data.success ? 'success' : 'danger'}`;
                    alertDiv.textContent = data.message;
                    
                    // Add to message container
                    messageContainer.appendChild(alertDiv);
                    
=======
          
>>>>>>> 2cd3e7705666e0ea92f5796b66cbfa6c3c200ef4
                    if (data.success) {
                        setTimeout(() => {
                            window.location.href = 'scantoday.php';
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-translucent alert-danger';
                    errorDiv.textContent = 'An error occurred. Please try again later.';
                    messageContainer.appendChild(errorDiv);
                });
            }
        });
    });
    </script>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
} // End of HTML rendering condition
?>