<!-- Network Status Indicator -->
<div class="network-status <?php echo function_exists('fsockopen') && @fsockopen('www.google.com', 80) ? 'online' : 'offline'; ?>" id="network-status">
    <?php echo function_exists('fsockopen') && @fsockopen('www.google.com', 80) ? 'Online' : 'Offline'; ?>
</div>

<?php
// Function to generate a simple barcode (this is a mock function)
function generateBarcode($code) {
    // In a real app, you would use a barcode generation library
    // This is just a placeholder that creates a simple image
    $image = imagecreate(200, 80);
    $background = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 0);
    
    // Draw some lines to represent a barcode
    for ($i = 0; $i < 15; $i++) {
        $x = 20 + ($i * 10);
        $h = rand(20, 60);
        imagefilledrectangle($image, $x, 10, $x + 5, 10 + $h, $text_color);
    }
    
    // Add the text
    imagestring($image, 5, 50, 65, $code, $text_color);
    
    // Capture the image data
    ob_start();
    imagepng($image);
    $data = ob_get_clean();
    
    imagedestroy($image);
    
    return $data;
}
?>
