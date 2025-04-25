<?php
// register.php - Backend for user registration
require_once 'auth_functions.php';

// Redirect to login page if not logged in
requireLogin('login.php');
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
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $agreeToTerms = isset($_POST['terms']) ? $_POST['terms'] : '';
    
    // Validate form data
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $response['message'] = "All fields are required.";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format.";
    }
    elseif (strlen($password) < 8) {
        $response['message'] = "Password must be at least 8 characters long.";
    }
    elseif ($password !== $confirmPassword) {
        $response['message'] = "Passwords do not match.";
    }
    elseif (empty($agreeToTerms)) {
        $response['message'] = "You must agree to the Terms and Conditions.";
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
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $response['message'] = "Email already registered.";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $password_hash);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Registration successful! Redirecting to login page...";
                } else {
                    $response['message'] = "Error: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
    }
    
    // Return JSON response for AJAX requests
    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit; // Stop execution here for AJAX requests
    } else {
        // For normal form submission, you might want to redirect or show a message
        if ($response['success']) {
            header('Location: login.html?registered=success');
            exit;
        }
        // If there was an error, we'll show it in the form below
    }
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
    <title>Register Page</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row min-vh-100 justify-content-center align-items-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Sign Up</h3>
                        
                        <?php if (!empty($response['message']) && !$response['success']): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($response['message']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" id="registerForm">
                            <!-- Username Input -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                            </div>

                            <!-- Email Input -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            </div>

                            <!-- Password Input -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                            </div>

                            <!-- Confirm Password Input -->
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                            </div>

                            <!-- Terms Checkbox -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">I agree to the Terms and Conditions</label>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">Sign Up</button>
                            </div>

                            <!-- Login Link -->
                            <div class="text-center">
                                <small>Already have an account? <a href="login.html">Login here</a></small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('registerForm');
        
        form.addEventListener('submit', function(e) {
            // Only prevent default if we're handling with AJAX
            // For normal form submission, we allow the form to submit normally
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
                fetch('register.php', requestOptions)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(data.message);
                        
                        // Redirect to login page after 2 seconds
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 2000);
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
