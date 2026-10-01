#!/usr/bin/env node
/* Upload contract for vector avatar icons. Runtime upload tests require WordPress
 * and PHP's XML extension, so this keeps the security and rendering guarantees
 * visible to the standalone Node test suite. */
const assert = require("assert");
const fs = require("fs");

const raffle = fs.readFileSync("includes/class-raffle.php", "utf8");
const admin = fs.readFileSync("admin/partials/event-edit/raffle-manager.php", "utf8");
const registration = fs.readFileSync("public/partials/raffle-registration.php", "utf8");

assert.match(raffle, /\$original_extension === 'svg'/, "SVG uses a dedicated validation path");
assert.match(raffle, /private static function is_safe_svg_icon/, "SVG validation is centralized");
assert.match(raffle, /DOMDocument[\s\S]*?LIBXML_NONET/, "SVG XML parsing cannot load network resources");
assert.match(raffle, /in_array\(\$element_name, array\('script', 'foreignobject'[\s\S]*?'animate'/, "active SVG elements are rejected after XML parsing");
assert.match(raffle, /javascript\\s\*:[\s\S]*?@import/, "executable or remote SVG attributes are rejected");
assert.match(raffle, /\$mime_type = 'image\/svg\+xml'/, "validated files retain the SVG MIME type");
assert.match(admin, /image\/svg\+xml,\.svg/, "the admin file chooser exposes SVG");
assert.match(registration, /<img src="<\?php echo esc_url\(CM_Raffle::get_icon_url\(\$icon\)\); \?>"/, "the avatar selector renders each accepted icon through an image URL");

console.log("OK (SVG avatar upload and rendering guards)");
