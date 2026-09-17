<?php

/**
 * Simple autoloader for chillerlan/php-qrcode
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent multiple includes
if (class_exists('chillerlan\\QRCode\\QRCode')) {
    return;
}

spl_autoload_register(function ($className) {
    // Only handle chillerlan namespace
    if (strpos($className, 'chillerlan\\') !== 0) {
        return;
    }
    
    // Convert namespace to file path
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $relativePath = str_replace('chillerlan' . DIRECTORY_SEPARATOR, '', $relativePath);
    
    $file = __DIR__ . '/php-qrcode-main/src/' . $relativePath . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load required dependencies that might not be autoloaded
$requiredFiles = [
    __DIR__ . '/php-qrcode-main/src/QRCodeException.php',
    __DIR__ . '/php-qrcode-main/src/QROptions.php',
    __DIR__ . '/php-qrcode-main/src/QROptionsTrait.php',
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}