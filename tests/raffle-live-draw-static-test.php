<?php
/** Source-level guards for the public live-draw synchronization contract. */
$root = dirname(__DIR__);
$raffle = file_get_contents($root . '/includes/class-raffle.php');
$ajax = file_get_contents($root . '/includes/class-ajax.php');
$script = file_get_contents($root . '/public/js/raffle-presentation.js');
$shortcodes = file_get_contents($root . '/includes/class-shortcodes.php');

if (strpos($raffle, 'LIVE_DRAW_MAX_CANDIDATES') === false || strpos($raffle, "'drawId'") === false || !preg_match('/private static function get_public_participant\\(.*?return array\\((.*?)\\);\\n    }/s', $raffle, $public_participant) || strpos($public_participant[1], "'id'") !== false) {
    fwrite(STDERR, "Live draw state contract is missing.\n");
    exit(1);
}
if (strpos($ajax, 'wp_ajax_nopriv_cm_get_raffle_live_draw') === false || strpos($ajax, "'GET'") === false || strpos($ajax, 'function get_raffle_live_draw') === false) {
    fwrite(STDERR, "Public live draw endpoint must remain read-only.\n");
    exit(1);
}
if (strpos($script, 'Date.now() - Number(draw.startedAt)') === false || strpos($script, 'lastDrawId') === false || strpos($script, 'cm_get_raffle_live_draw') === false) {
    fwrite(STDERR, "Guest draw timing/replay guard is missing.\n");
    exit(1);
}
if (!preg_match('/function display_event_lineup\(.*?CM_Public::enqueue_raffle_presentation_assets\(\)/s', $shortcodes)) {
    fwrite(STDERR, "Lineup pages must load the live-draw controller before an SSE draw card is inserted.\n");
    exit(1);
}

echo "OK (raffle live draw synchronization guards)\n";
