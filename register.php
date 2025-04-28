<?php
// register.php - Backend for user registration
require_once 'auth_functions.php';

// Redirect to login page if not logged in
<<<<<<< HEAD
 requireLogin('login.php');
=======
// requireLogin('login.php');
>>>>>>> 2cd3e7705666e0ea92f5796b66cbfa6c3c200ef4
// First check if this is an AJAX request that needs JSON response
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
    <title>Register Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="icon" href="assets\favicon.png" type="image/png">
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
                        <h3 class="card-title text-center mb-4">New User</h3>

                        <!-- Message Container -->
                        <div id="messageContainer" class="mb-3">
                            <?php if (!empty($response['message']) && !$response['success']): ?>
                            <div class="alert alert-translucent alert-danger">
                                <?php echo htmlspecialchars($response['message']); ?>
                            </div>
                            <?php endif; ?>
                        </div>    
                                            
                        <form method="post" id="registerForm">
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

                            <!-- Confirm Password Input -->
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" onclick="togglePassword('confirmPassword')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">Sign Up</button>
                            </div>

                            <!-- Login Link -->
                            <div class="text-center">
                                <small>Already have an account? <a href="login.php">Login here</a></small>
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
        const form = document.getElementById('registerForm');
        const messageContainer = document.getElementById('messageContainer');
        
        form.addEventListener('submit', function(e) {
            if (window.fetch) {
                e.preventDefault();
                
                // Clear previous messages
                messageContainer.innerHTML = '';
                
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
                    alertDiv.textContent = data.message;
                    
                    // Add to message container
                    messageContainer.appendChild(alertDiv);
                    
                    if (data.success) {
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
} // End of HTML rendering condition
?>