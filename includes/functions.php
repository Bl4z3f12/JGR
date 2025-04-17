<?php
/**
 * Helper functions for the BarcodeHub application
 */

/**
 * Gets the title for the current view
 * 
 * @param string $view The current view
 * @return string The title for the view
 */
function getViewTitle($view) {
    $titles = [
        'dashboard' => 'Dashboard',
        'today' => 'Scanned Today',
        'production' => 'Production',
        'export' => 'Export Data',
        'Settings' => 'Barcode Settings'
    ];
    
    return isset($titles[$view]) ? $titles[$view] : 'Dashboard';
}

/**
 * Formats a date for display
 * 
 * @param string $date The date to format
 * @return string The formatted date
 */
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Check if the system is online
 * 
 * @return bool True if online, false otherwise
 */
function isOnline() {
    return function_exists('fsockopen') && @fsockopen('www.google.com', 80);
}
?>