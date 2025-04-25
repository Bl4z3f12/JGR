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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row min-vh-100 justify-content-center align-items-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Sign In</h3>
                        
                        <?php if (!empty($response['message']) && !$response['success']): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($response['message']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                        <div class="alert alert-success">
                            Registration successful! You can now log in.
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" id="loginForm">
                            <!-- Username Input -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                            </div>

                            <!-- Password Input -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
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
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        
        form.addEventListener('submit', function(e) {
            // Only prevent default if we're handling with AJAX
            if (window.fetch) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(form);
                
                // Add a custom header to identify this as an AJAX request
                const requestOptions = {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                };
                
                // Send AJAX request
                fetch('login.php', requestOptions)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(data.message);
                        
                        // Redirect to dashboard page
                        setTimeout(function() {
                            window.location.href = 'scantoday.php';
                        }, 1000);
                    } else {
                        // Show error message
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again later.');
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