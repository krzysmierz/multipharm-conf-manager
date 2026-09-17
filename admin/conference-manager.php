<?php
/**
 * Conference Manager
 *
 * @package           ConferenceManager
 * @author            Krzysztof Mierzejewski
 * @copyright         Photograficznie.pl
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Conference Manager
 * Plugin URI:        https://photograficznie.pl/conference-manager
 * Description:       Plugin do zarządzania wydarzeniami konferencyjnymi z systemem lineupów, quizami, kodami QR i intuicyjnym panelem administracyjnym upakowane wszystko w ssa.
 * Version:           0.9.7
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Krzysztof Mierzejewski
 * Author URI:        https://photograficznie.pl
 * Text Domain:       conference-manager
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('CONFERENCE_MANAGER_VERSION', '0.9.7');
define('CONFERENCE_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONFERENCE_MANAGER_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Autoloader for chillerlan libraries
 */
spl_autoload_register(function ($class) {
    // Handle chillerlan QR Code library
    $qr_prefix = 'chillerlan\\QRCode\\';
    $qr_base_dir = __DIR__ . '/lib/php-qrcode-main/src/';
    
    $qr_len = strlen($qr_prefix);
    if (strncmp($qr_prefix, $class, $qr_len) === 0) {
        $relative_class = substr($class, $qr_len);
        $file = $qr_base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    
    // Handle chillerlan Settings Container library
    $settings_prefix = 'chillerlan\\Settings\\';
    $settings_base_dir = __DIR__ . '/lib/php-settings-container-main/src/';
    
    $settings_len = strlen($settings_prefix);
    if (strncmp($settings_prefix, $class, $settings_len) === 0) {
        $relative_class = substr($class, $settings_len);
        $file = $settings_base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

/**
 * Simple QR Code generation function for Conference Manager
 */
function bizconf_generate_qr($text, $size = 200) {
    // Check if required classes are available
    if (!class_exists('chillerlan\\QRCode\\QRCode') || 
        !class_exists('chillerlan\\QRCode\\QROptions') ||
        !extension_loaded('gd')) {
        error_log('CM QR Generator: Missing requirements - QRCode: ' . 
                  (class_exists('chillerlan\\QRCode\\QRCode') ? 'YES' : 'NO') . 
                  ', Options: ' . (class_exists('chillerlan\\QRCode\\QROptions') ? 'YES' : 'NO') . 
                  ', GD: ' . (extension_loaded('gd') ? 'YES' : 'NO'));
        return bizconf_generate_qr_fallback($text, $size); // Try fallback
    }
    
    try {
        // Calculate appropriate scale for desired size
        $scale = max(3, min(15, intval($size / 25))); // Better scaling calculation
        
        $options = new \chillerlan\QRCode\QROptions();
        
        // Set options directly on object
        $options->version = 5;
        $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
        $options->eccLevel = \chillerlan\QRCode\Common\EccLevel::L;
        $options->scale = $scale;
        $options->addQuietzone = true;
        $options->quietzoneSize = 2;
        $options->returnResource = false;
        $options->outputBase64 = false;

        $qrcode = new \chillerlan\QRCode\QRCode($options);
        
        // Simple render - let the library handle everything
        $imageData = $qrcode->render($text);
        
        // Log for debugging
        if (is_string($imageData)) {
            error_log('CM QR Generator: Generated QR for: ' . substr($text, 0, 50) . '... Size: ' . strlen($imageData) . ' bytes');
        } else {
            error_log('CM QR Generator: Unexpected return type: ' . gettype($imageData));
            return false;
        }
        
        return $imageData;
    } catch (Exception $e) {
        error_log('Conference Manager QR Generation Error: ' . $e->getMessage());
        return bizconf_generate_qr_fallback($text, $size);
    } catch (Throwable $e) {
        error_log('Conference Manager QR Generation Fatal Error: ' . $e->getMessage());
        return bizconf_generate_qr_fallback($text, $size);
    }
}

/**
 * Fallback QR generation using Google Charts API
 */
function bizconf_generate_qr_fallback($text, $size = 200) {
    $size = max(100, min(500, $size));
    $api_url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($text);
    
    if (!function_exists('wp_remote_get')) {
        return false; // WordPress functions not available
    }
    
    $response = wp_remote_get($api_url, array(
        'timeout' => 15,
        'user-agent' => 'Conference Manager QR Fallback',
    ));
    
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        error_log('CM QR Fallback: Failed to fetch from Google Charts API');
        return false;
    }
    
    $image_data = wp_remote_retrieve_body($response);
    
    if (strlen($image_data) > 100) {
        error_log('CM QR Fallback: Generated QR using Google Charts API');
        return $image_data;
    }
    
    return false;
}

/**
 * The code that runs during plugin activation.
 */
function activate_conference_manager() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
    CM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_conference_manager() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-deactivator.php';
    CM_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_conference_manager');
register_deactivation_hook(__FILE__, 'deactivate_conference_manager');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-core.php';

/**
 * Begins execution of the plugin.
 */
function run_conference_manager() {
    $plugin = new CM_Core();
    $plugin->run();
}
run_conference_manager();