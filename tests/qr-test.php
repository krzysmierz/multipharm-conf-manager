<?php
/** Exercise the actual bootstrap QR generator without starting WordPress. */
define('WPINC', 'wp-includes');
function plugin_dir_url($file) { return 'https://example.test/plugins/conference-manager/'; }
function plugin_dir_path($file) { return dirname($file) . '/'; }
function wp_remote_get($url, $args) { throw new RuntimeException('QR must stay local'); }

$root = dirname(__DIR__);
$bootstrap = file_get_contents($root . '/conference-manager.php');
$end = strpos($bootstrap, '/**' . "\n" . ' * The code that runs during plugin activation.');
if ($end === false) { throw new RuntimeException('Bootstrap boundary missing'); }
$generator = substr($bootstrap, 5, $end - 5);
$generator = str_replace(array('__DIR__', '__FILE__'), array(var_export($root, true), var_export($root . '/conference-manager.php', true)), $generator);
eval($generator);

foreach (array(
    'https://example.test/?cm_raffle=' . str_repeat('a', 48),
    'https://example.test/' . str_repeat('conference/', 25) . '?cm_raffle=' . str_repeat('b', 48),
) as $url) {
    $png = bizconf_generate_qr($url, 300, false);
    if (!is_string($png) || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
        throw new RuntimeException('QR generator failed to return PNG');
    }
    $qr = new \chillerlan\QRCode\QRCode();
    $decoded = $qr->readFromBlob($png);
    if ($decoded->data !== $url) { throw new RuntimeException('QR decoded to a different URL'); }
}
echo "OK (2 local QR generation and decoding checks)\n";
