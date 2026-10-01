<?php
/** Static guard for helpers unavailable to a public/SSE template request. */
$partial = file_get_contents(dirname(__DIR__) . '/public/partials/raffle-presentation-card.php');

if ($partial === false || preg_match('/\\b(?:hidden|disabled|checked|selected)\\s*\\(/', $partial)) {
    fwrite(STDERR, "Public raffle component must not use context-specific template helpers.\n");
    exit(1);
}

echo "OK (public raffle component helper guard)\n";
