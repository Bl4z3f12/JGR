<?php
// logout.php - Backend for user logout

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Process logout request
function logout() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Delete remember_me cookie if it exists
    if (isset($_COOKIE['remember_me'])) {
        setcookie("remember_me", "", time() - 3600, "/");
        
        // Here you would also remove the token from your database
        // Example:
        // require_once 'config.php';
        // $token = $_COOKIE['remember_me'];
        // $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
        // $stmt->bind_param("s", $token);
        // $stmt->execute();
        // $stmt->close();
        // $conn->close();
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: login.php");
    exit;
}

// Check if this is a direct logout request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax'])) {
    logout();
}

// Handle AJAX logout requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['ajax'])) {
    $isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($isAjaxRequest) {
        logout();
        
        // Send JSON response if it's an AJAX request
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        exit;
    } else {
        logout();
    }
}
?>