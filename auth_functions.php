<?php
// auth_functions.php - Helper functions for authentication

/**
 * Check if user is logged in
 * 
 * @return boolean
 */
function isLoggedIn() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Redirect user if not logged in
 * 
 * @param string $redirect URL to redirect to
 * @return void
 */
function requireLogin($redirect = 'login.html') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Get current user ID
 * 
 * @return int|null
 */
function getCurrentUserId() {
    if (isLoggedIn() && isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }
    
    return null;
}

/**
 * Get current username
 * 
 * @return string|null
 */
function getCurrentUsername() {
    if (isLoggedIn() && isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }
    
    return null;
}

/**
 * Security function to help prevent CSRF attacks
 * 
 * @return string - CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * 
 * @param string $token - CSRF token to validate
 * @return boolean
 */
function validateCSRFToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    
    return false;
}

?>