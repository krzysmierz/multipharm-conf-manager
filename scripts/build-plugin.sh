#!/usr/bin/env bash
# Build an installable WordPress plugin archive without development-only files.

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_FILE="$PROJECT_DIR/conference-manager.php"
SLUG="conference-manager"

for command in rsync zip sed mktemp grep wc tr; do
    command -v "$command" >/dev/null 2>&1 || {
        printf 'Required command not found: %s\n' "$command" >&2
        exit 1
    }
done

if [[ ! -f "$PLUGIN_FILE" ]]; then
    printf 'Plugin bootstrap file not found: %s\n' "$PLUGIN_FILE" >&2
    exit 1
fi

VERSION="$(sed -nE 's/^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$PLUGIN_FILE" | head -n 1)"
if [[ -z "$VERSION" ]]; then
    printf 'Could not determine the plugin version from %s\n' "$PLUGIN_FILE" >&2
    exit 1
fi

BUILD_DIR="$PROJECT_DIR/build"
ARCHIVE="$BUILD_DIR/${SLUG}-${VERSION}.zip"
STAGING_DIR="$(mktemp -d "${TMPDIR:-/tmp}/${SLUG}.XXXXXX")"
STAGING_PLUGIN_DIR="$STAGING_DIR/$SLUG"

cleanup() {
    rm -rf "$STAGING_DIR"
}
trap cleanup EXIT HUP INT TERM

fail_build() {
    printf 'Build validation failed: %s\n' "$1" >&2
    exit 1
}

validate_staging() {
    # This is the runtime manifest: the bootstrap, every directly loaded PHP
    # dependency, and assets/templates referenced by the admin and public code.
    # Keep it explicit so a future exclude cannot silently make a ZIP install
    # successfully but fail once WordPress loads the plugin.
    local required_file
    local required_runtime_files=(
        'conference-manager.php'
        'uninstall.php'
        'includes/class-activator.php'
        'includes/class-ajax.php'
        'includes/class-core.php'
        'includes/class-database.php'
        'includes/class-deactivator.php'
        'includes/class-event.php'
        'includes/class-file-manager.php'
        'includes/class-lineup.php'
        'includes/class-loader.php'
        'includes/class-permissions.php'
        'includes/class-qr-generator.php'
        'includes/class-quiz-state.php'
        'includes/class-quiz.php'
        'includes/class-raffle.php'
        'includes/class-shortcodes.php'
        'includes/class-sse-controller.php'
        'admin/class-admin.php'
        'admin/css/admin.css'
        'admin/css/tailwind.css'
        'admin/js/admin.js'
        'admin/js/file-uploader.js'
        'admin/js/lineup-manager.js'
        'admin/js/quiz-manager.js'
        'admin/js/tabs-navigation.js'
        'admin/partials/dashboard.php'
        'admin/partials/events-list.php'
        'admin/partials/global-settings.php'
        'admin/partials/lineup-item-template.php'
        'admin/partials/event-edit/basic-info.php'
        'admin/partials/event-edit/day-lineup-items.php'
        'admin/partials/event-edit/empty-day-state.php'
        'admin/partials/event-edit/files-manager.php'
        'admin/partials/event-edit/lineup-manager.php'
        'admin/partials/event-edit/main.php'
        'admin/partials/event-edit/preview.php'
        'admin/partials/event-edit/qr-codes.php'
        'admin/partials/event-edit/quiz-manager.php'
        'admin/partials/event-edit/raffle-manager.php'
        'public/class-public.php'
        'public/css/public.css'
        'public/css/tailwind.css'
        'public/js/event-live-updates.js'
        'public/js/live-timer.js'
        'public/js/public.js'
        'public/js/raffle-avatar-slider.js'
        'public/js/raffle-registration.js'
        'public/js/raffle-presentation.js'
        'public/js/quiz-live-updates.js'
        'public/js/quiz.js'
        'public/partials/day-lineup-popup.php'
        'public/partials/quiz-display.php'
        'public/partials/raffle-presentation.php'
        'public/partials/raffle-presentation-card.php'
        'public/partials/raffle-registration.php'
        'lib/php-qrcode-main/src/QRCode.php'
        'lib/php-qrcode-main/src/QROptions.php'
        'lib/php-qrcode-main/src/Common/Version.php'
        'lib/php-qrcode-main/src/Output/QRGdImagePNG.php'
        'lib/php-settings-container-main/src/SettingsContainerAbstract.php'
        'lib/php-settings-container-main/src/SettingsContainerInterface.php'
    )

    for required_file in "${required_runtime_files[@]}"; do
        [[ -f "$STAGING_PLUGIN_DIR/$required_file" ]] || fail_build "missing runtime file: $required_file"
    done

    # WordPress discovers every PHP file containing this header. The archive
    # must expose exactly the intended root bootstrap, never a legacy copy.
    local plugin_headers expected_header
    expected_header="$STAGING_PLUGIN_DIR/conference-manager.php"
    plugin_headers="$(grep -rlE '^[[:space:]]*\*?[[:space:]]*Plugin Name:' "$STAGING_PLUGIN_DIR" --include='*.php' || true)"
    [[ "$plugin_headers" == "$expected_header" ]] || fail_build "expected one plugin header at conference-manager.php; found: ${plugin_headers:-none}"

    local forbidden_path
    local forbidden_legacy_paths=(
        'admin/conference-manager.php'
        'admin/admin'
        'admin/assets'
        'admin/includes'
        'admin/lib'
        'admin/public'
        'admin/uninstall.php'
    )

    for forbidden_path in "${forbidden_legacy_paths[@]}"; do
        [[ ! -e "$STAGING_PLUGIN_DIR/$forbidden_path" ]] || fail_build "legacy duplicate included: $forbidden_path"
    done
}

mkdir -p "$BUILD_DIR" "$STAGING_PLUGIN_DIR"
rm -f "$ARCHIVE"

# Keep runtime code and assets, while removing repository metadata, local state,
# development dependencies, tests, documentation, editor settings, and secrets.
# Legacy copies of the complete plugin live under admin/. They are not loaded by
# the root bootstrap and must never be distributed or discovered by WordPress as
# a second plugin.
rsync -a --prune-empty-dirs \
    --exclude='.git/' \
    --exclude='.gitignore' \
    --exclude='build/' \
    --exclude='node_modules/' \
    --exclude='/vendor/' \
    --exclude='coverage/' \
    --exclude='test-results/' \
    --exclude='tests/' \
    --exclude='test/' \
    --exclude='__tests__/' \
    --exclude='.vscode/' \
    --exclude='.idea/' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='*.log' \
    --exclude='*.tmp' \
    --exclude='*.swp' \
    --exclude='*.swo' \
    --exclude='*.code-workspace' \
    --exclude='.DS_Store' \
    --exclude='._*' \
    --exclude='Thumbs.db' \
    --exclude='php_errorlog' \
    --exclude='error_log' \
    --exclude='.phpunit.result.cache' \
    --exclude='*.test.php' \
    --exclude='*.spec.php' \
    --exclude='*.test.js' \
    --exclude='*.spec.js' \
    --exclude='/README*' \
    --exclude='/CHANGELOG*' \
    --exclude='/CONTRIBUTING*' \
    --exclude='/CODE_OF_CONDUCT*' \
    --exclude='/docs/' \
    --exclude='/scripts/' \
    --exclude='/assets/' \
    --exclude='/css/' \
    --exclude='/avatar-icons/' \
    --exclude='/aptekarze-icons/' \
    --exclude='/aptekarze-icons.zip' \
    --exclude='/*.ai' \
    --exclude='/*.zip' \
    --exclude='/admin/conference-manager.php' \
    --exclude='/admin/admin/' \
    --exclude='/admin/assets/' \
    --exclude='/admin/includes/' \
    --exclude='/admin/lib/' \
    --exclude='/admin/public/' \
    --exclude='/admin/uninstall.php' \
    --exclude='/package.json' \
    --exclude='/package-lock.json' \
    --exclude='/yarn.lock' \
    --exclude='/pnpm-lock.yaml' \
    --exclude='/composer.json' \
    --exclude='/composer.lock' \
    --exclude='/lib/autoloader.php' \
    --exclude='/lib/php-qrcode-main/composer.json' \
    --exclude='/lib/php-settings-container-main/composer.json' \
    --exclude='/lib/php-settings-container-main/rules-magic-access.neon' \
    --exclude='/lib/php-qrcode-main/src/Output/qrcode.schema.json' \
    --exclude='/lib/php-qrcode-main/src/Output/qrcode.schema.xsd' \
    "$PROJECT_DIR/" "$STAGING_PLUGIN_DIR/"

validate_staging

(
    cd "$STAGING_DIR"
    zip -qr "$ARCHIVE" "$SLUG"
)

printf 'Built %s\n' "$ARCHIVE"
