<?php

/**
 * Registration raffle data and business rules.
 *
 * A raffle belongs to an event and has its own unguessable public token.  The
 * database unique key is deliberately the final duplicate check: checking in
 * PHP first would still allow two simultaneous submissions through.
 */
class CM_Raffle {

    const MAX_NAME_LENGTH = 100;
    const LIVE_DRAW_MAX_CANDIDATES = 100;
    const LIVE_DRAW_TTL_SECONDS = 30;

    public static function create($event_id, $label = '') {
        $event_id = absint($event_id);
        $event = new CM_Event($event_id);
        if (!$event->get_id()) {
            return new WP_Error('invalid_event', __('Nie znaleziono wydarzenia.', 'conference-manager'));
        }

        $label = is_scalar($label) ? sanitize_text_field((string) $label) : '';
        if ($label === '') {
            $label = __('Rejestracja uczestników', 'conference-manager');
        }
        $label = self::limit_string($label, 255);

        try {
            $token = bin2hex(random_bytes(24));
        } catch (Exception $e) {
            return new WP_Error('token_generation_failed', __('Nie udało się utworzyć bezpiecznego adresu rejestracji.', 'conference-manager'));
        }

        $id = CM_Database::insert('raffles', array(
            'event_id' => $event_id,
            'label'    => $label,
            'token'    => $token,
        ));

        if (is_wp_error($id)) {
            return $id;
        }

        $qr = CM_QR_Generator::generate_raffle_qr($id);
        if (is_wp_error($qr)) {
            // The raffle is still usable through its URL; the administrator can
            // regenerate its QR code after fixing the server QR dependency.
            return array('id' => $id, 'token' => $token, 'qr_error' => $qr);
        }

        return array('id' => $id, 'token' => $token, 'qr' => $qr);
    }

    public static function get($raffle_id) {
        return CM_Database::get_row('raffles', array('id' => absint($raffle_id)));
    }

    public static function get_by_token($token) {
        global $wpdb;
        $token = is_string($token) ? $token : '';
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            return null;
        }

        $table = CM_Database::get_table_name('raffles');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE token = %s LIMIT 1", $token));
    }

    public static function get_by_event($event_id) {
        global $wpdb;
        $table = CM_Database::get_table_name('raffles');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE event_id = %d ORDER BY created_at DESC, id DESC",
            absint($event_id)
        ));
    }

    public static function get_registration_url($raffle) {
        $token = is_object($raffle) ? $raffle->token : (is_array($raffle) ? ($raffle['token'] ?? '') : '');
        return add_query_arg('cm_raffle', rawurlencode($token), home_url('/'));
    }

    /** Return the screen URL used by the event schedule to present a draw. */
    public static function get_presentation_url($raffle) {
        $id = is_object($raffle) ? $raffle->id : (is_array($raffle) ? ($raffle['id'] ?? 0) : $raffle);
        return add_query_arg('cm_raffle_presentation', absint($id), home_url('/'));
    }

    public static function get_qr($raffle_id) {
        global $wpdb;
        $table = CM_Database::get_table_name('qr_codes');
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE code_type = 'raffle' AND target_id = %d ORDER BY created_at DESC, id DESC LIMIT 1",
            absint($raffle_id)
        ));
    }

    public static function validate_name($value, $field_label) {
        if (!is_scalar($value)) {
            return new WP_Error('invalid_name', sprintf(__('Pole „%s” zawiera niedozwolone dane.', 'conference-manager'), $field_label));
        }
        $value = sanitize_text_field(wp_unslash($value));
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        $length = self::string_length($value);

        if ($value === '' || $length < 2 || $length > self::MAX_NAME_LENGTH) {
            return new WP_Error('invalid_name', sprintf(__('Pole „%s” musi mieć od 2 do %d znaków.', 'conference-manager'), $field_label, self::MAX_NAME_LENGTH));
        }

        if (!preg_match('/^[\p{L}\p{M}][\p{L}\p{M}\s\'\x{2018}\x{2019}\-]*$/u', $value)) {
            return new WP_Error('invalid_name', sprintf(__('Pole „%s” zawiera niedozwolone znaki.', 'conference-manager'), $field_label));
        }

        return $value;
    }

    public static function normalized_name($first_name, $last_name) {
        // Inputs have already been validated, but keep this helper safe when
        // called independently (e.g. from an import or a unit test).
        $first_name = self::canonical_component($first_name);
        $last_name = self::canonical_component($last_name);
        return trim($first_name . ' ' . $last_name);
    }

    public static function identity_hash($first_name, $last_name) {
        // Keep the field boundary in the identity. A simple concatenation
        // would make "Jan Adam|Kowalski" collide with "Jan|Adam Kowalski".
        return hash('sha256', self::canonical_component($first_name) . "\x1f" . self::canonical_component($last_name));
    }

    private static function canonical_component($value) {
        $value = is_scalar($value) ? (string) $value : '';
        $value = remove_accents($value);
        // Canonicalize decomposed Unicode letters even when intl/mbstring
        // normalization is unavailable.
        $value = preg_replace('/\p{M}+/u', '', $value);
        $value = str_replace(array("\xE2\x80\x99", "\xE2\x80\x98"), "'", $value);
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return trim((string) $value);
    }

    public static function register_participant($raffle_id, $first_name, $last_name, $icon_id = 0) {
        global $wpdb;

        $raffle = self::get($raffle_id);
        if (!$raffle) {
            return new WP_Error('raffle_not_found', __('To losowanie nie jest już dostępne.', 'conference-manager'));
        }

        $first_name = self::validate_name($first_name, __('Imię', 'conference-manager'));
        if (is_wp_error($first_name)) {
            return $first_name;
        }
        $last_name = self::validate_name($last_name, __('Nazwisko', 'conference-manager'));
        if (is_wp_error($last_name)) {
            return $last_name;
        }
        $icon_id = self::validate_icon_choice($raffle_id, $icon_id);
        if (is_wp_error($icon_id)) {
            return $icon_id;
        }

        $normalized_name = self::normalized_name($first_name, $last_name);
        $identity_hash = self::identity_hash($first_name, $last_name);
        $table = CM_Database::get_table_name('raffle_participants');
        $previous_suppression = $wpdb->suppress_errors(true);
        try {
            $result = $wpdb->insert(
                $table,
                array(
                    'raffle_id'       => absint($raffle_id),
                    'first_name'      => $first_name,
                    'last_name'       => $last_name,
                    'normalized_name' => $normalized_name,
                    'identity_hash'   => $identity_hash,
                    'icon_id'         => $icon_id ?: null,
                ),
                array('%d', '%s', '%s', '%s', '%s', '%d')
            );
        } finally {
            $wpdb->suppress_errors($previous_suppression);
        }

        if ($result === false) {
            // MySQL error 1062 is the unique key; test the text too for hosts
            // which do not expose the numeric driver error via wpdb.
            if (strpos((string) $wpdb->last_error, 'Duplicate') !== false || strpos((string) $wpdb->last_error, '1062') !== false) {
                return new WP_Error('already_registered', __('Ta osoba jest już zarejestrowana do tego losowania.', 'conference-manager'));
            }
            return new WP_Error('registration_failed', __('Nie udało się zapisać zgłoszenia. Spróbuj ponownie.', 'conference-manager'));
        }

        return (int) $wpdb->insert_id;
    }

    public static function get_participants($raffle_id) {
        global $wpdb;
        $table = CM_Database::get_table_name('raffle_participants');
        $icons = CM_Database::get_table_name('raffle_icons');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, i.file_name AS icon_file_name FROM {$table} p LEFT JOIN {$icons} i ON i.id = p.icon_id AND i.raffle_id = p.raffle_id WHERE p.raffle_id = %d ORDER BY p.created_at ASC, p.id ASC",
            absint($raffle_id)
        ));
    }

    public static function get_icons($raffle_id) {
        global $wpdb;
        $table = CM_Database::get_table_name('raffle_icons');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE raffle_id = %d ORDER BY created_at ASC, id ASC", absint($raffle_id)
        ));
    }

    public static function get_icon($raffle_id, $icon_id) {
        global $wpdb;
        $table = CM_Database::get_table_name('raffle_icons');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE raffle_id = %d AND id = %d", absint($raffle_id), absint($icon_id)));
    }

    public static function get_icon_url($icon) {
        $raffle_id = is_object($icon) ? ($icon->raffle_id ?? 0) : ($icon['raffle_id'] ?? 0);
        $file_name = is_object($icon) ? ($icon->file_name ?? '') : ($icon['file_name'] ?? '');
        $file_name = basename((string) $file_name);
        if (!$raffle_id || $file_name === '') {
            return '';
        }
        $uploads = wp_upload_dir();
        return $uploads['baseurl'] . '/conference-manager/raffle-icons/' . absint($raffle_id) . '/' . rawurlencode($file_name);
    }

    public static function get_participant_icon_url($participant) {
        if (!is_object($participant) || empty($participant->icon_id)) {
            return '';
        }
        if (empty($participant->icon_file_name)) {
            $icon = self::get_icon($participant->raffle_id, $participant->icon_id);
            return $icon ? self::get_icon_url($icon) : '';
        }
        return self::get_icon_url(array('raffle_id' => $participant->raffle_id, 'file_name' => $participant->icon_file_name));
    }

    /**
     * Publish the display-only state that lets every audience screen animate
     * one persisted draw. Database IDs deliberately never leave this state.
     */
    public static function publish_live_draw($raffle_id, $participants, $winner) {
        $raffle_id = absint($raffle_id);
        $participants = is_array($participants) ? $participants : array();
        if (!$raffle_id || empty($participants) || !is_object($winner)) {
            return new WP_Error('invalid_live_draw', __('Nie udało się przygotować animacji losowania.', 'conference-manager'));
        }

        $display_candidates = array();
        $winner_included = false;
        foreach (array_slice($participants, 0, self::LIVE_DRAW_MAX_CANDIDATES) as $participant) {
            if (!is_object($participant)) {
                continue;
            }
            $display_candidates[] = self::get_public_participant($participant);
            if ((int) $participant->id === (int) $winner->id) {
                $winner_included = true;
            }
        }

        $public_winner = self::get_public_participant($winner);
        if (!$winner_included) {
            if (count($display_candidates) >= self::LIVE_DRAW_MAX_CANDIDATES) {
                array_pop($display_candidates);
            }
            $display_candidates[] = $public_winner;
        }

        $duration = count($display_candidates) === 1 ? 1300 : 4200;
        try {
            $draw_id = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            // The winner is already persisted; retain a unique WordPress ID
            // fallback so an unlikely entropy failure cannot hide its result.
            $draw_id = wp_generate_uuid4();
        }

        $state = array(
            'drawId'       => $draw_id,
            'raffleId'     => $raffle_id,
            'startedAt'    => (int) round(microtime(true) * 1000),
            'duration'     => $duration,
            'participants' => array_values($display_candidates),
            'winner'       => $public_winner,
        );
        set_transient('_cm_raffle_live_draw_' . $raffle_id, $state, self::LIVE_DRAW_TTL_SECONDS);

        return $state;
    }

    /** Return only the active display state for a raffle, never draw records. */
    public static function get_live_draw($raffle_id) {
        $raffle_id = absint($raffle_id);
        $state = get_transient('_cm_raffle_live_draw_' . $raffle_id);
        if (!is_array($state) || (int) ($state['raffleId'] ?? 0) !== $raffle_id) {
            return null;
        }
        return $state;
    }

    private static function get_public_participant($participant) {
        return array(
            'name'    => trim((string) $participant->first_name . ' ' . (string) $participant->last_name),
            'iconUrl' => esc_url_raw(self::get_participant_icon_url($participant)),
        );
    }

    /** Icon selection becomes mandatory only after an administrator adds a pool. */
    public static function validate_icon_choice($raffle_id, $icon_id) {
        $icon_id = absint($icon_id);
        $icons = self::get_icons($raffle_id);
        if (empty($icons)) {
            return 0;
        }
        if (!$icon_id) {
            return new WP_Error('icon_required', __('Wybierz ikonę uczestnika.', 'conference-manager'));
        }
        foreach ($icons as $icon) {
            if ((int) $icon->id === $icon_id) {
                return $icon_id;
            }
        }
        return new WP_Error('invalid_icon', __('Wybrana ikona nie jest dostępna dla tego losowania.', 'conference-manager'));
    }

    public static function upload_icons($raffle_id, $files, $zip_file = null) {
        $raffle_id = absint($raffle_id);
        if (!self::get($raffle_id)) {
            return new WP_Error('raffle_not_found', __('Nie znaleziono losowania.', 'conference-manager'));
        }
        $result = array('accepted' => 0, 'rejected' => array());
        foreach ((array) $files as $file) {
            if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
                $result['rejected'][] = __('Nie udało się przesłać jednego z plików ikon.', 'conference-manager');
                continue;
            }
            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                continue;
            }
            $saved = self::store_icon($raffle_id, $file['tmp_name'], $file['name'] ?? 'icon', (int) ($file['size'] ?? 0));
            if (is_wp_error($saved)) {
                $result['rejected'][] = $saved->get_error_message();
            } else {
                $result['accepted']++;
            }
        }
        if ($zip_file && !empty($zip_file['error']) && (int) $zip_file['error'] !== UPLOAD_ERR_NO_FILE && (int) $zip_file['error'] !== UPLOAD_ERR_OK) {
            $result['rejected'][] = __('Nie udało się przesłać archiwum ZIP.', 'conference-manager');
        } elseif ($zip_file && !empty($zip_file['tmp_name'])) {
            if (!is_uploaded_file($zip_file['tmp_name'])) {
                $result['rejected'][] = __('Nieprawidłowy plik ZIP.', 'conference-manager');
            } else {
                $zip_result = self::store_zip_icons($raffle_id, $zip_file);
                $result['accepted'] += $zip_result['accepted'];
                $result['rejected'] = array_merge($result['rejected'], $zip_result['rejected']);
            }
        }
        return $result;
    }

    private static function store_zip_icons($raffle_id, $zip_file) {
        $result = array('accepted' => 0, 'rejected' => array());
        if (!class_exists('ZipArchive') || (int) ($zip_file['size'] ?? 0) > 10 * 1024 * 1024) {
            $result['rejected'][] = __('Archiwum ZIP jest niedostępne lub przekracza limit 10 MB.', 'conference-manager');
            return $result;
        }
        $zip = new ZipArchive();
        if ($zip->open($zip_file['tmp_name']) !== true || $zip->numFiles > 50) {
            $result['rejected'][] = __('Nie można odczytać ZIP-a lub zawiera on więcej niż 50 plików.', 'conference-manager');
            return $result;
        }
        $total_size = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entry = (string) ($stat['name'] ?? '');
            $total_size += (int) ($stat['size'] ?? 0);
            if ($total_size > 20 * 1024 * 1024 || $entry === '' || strpos($entry, "\0") !== false || preg_match('#(^|[\\\\/])\.\.([\\\\/]|$)#', $entry) || preg_match('#^[\\\\/]#', $entry)) {
                $result['rejected'][] = __('ZIP zawiera niedozwoloną lub zbyt dużą pozycję.', 'conference-manager');
                continue;
            }
            if (substr($entry, -1) === '/') {
                continue;
            }
            $attributes = 0;
            $system = 0;
            $zip->getExternalAttributesIndex($i, $system, $attributes);
            if ($system === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000) {
                $result['rejected'][] = __('ZIP nie może zawierać dowiązań symbolicznych.', 'conference-manager');
                continue;
            }
            $stream = $zip->getStream($entry);
            $temporary = $stream ? wp_tempnam('cm-raffle-icon') : false;
            if (!$stream || !$temporary) {
                $result['rejected'][] = __('Nie udało się odczytać ikony z ZIP-a.', 'conference-manager');
                continue;
            }
            $output = fopen($temporary, 'wb');
            stream_copy_to_stream($stream, $output, 2 * 1024 * 1024 + 1);
            fclose($output); fclose($stream);
            $saved = self::store_icon($raffle_id, $temporary, basename($entry), (int) filesize($temporary));
            @unlink($temporary);
            if (is_wp_error($saved)) { $result['rejected'][] = $saved->get_error_message(); } else { $result['accepted']++; }
        }
        $zip->close();
        return $result;
    }

    private static function store_icon($raffle_id, $temporary, $original_name, $size) {
        if ($size < 1 || $size > 2 * 1024 * 1024) {
            return new WP_Error('icon_too_large', __('Ikona musi mieć maksymalnie 2 MB.', 'conference-manager'));
        }
        if (count(self::get_icons($raffle_id)) >= 50) {
            return new WP_Error('icon_limit', __('Można dodać maksymalnie 50 ikon do jednego losowania.', 'conference-manager'));
        }
        $original_extension = strtolower((string) pathinfo($original_name, PATHINFO_EXTENSION));
        if ($original_extension === 'svg') {
            if (!self::is_safe_svg_icon($temporary)) {
                return new WP_Error('invalid_icon', __('SVG ikony musi być prawidłowym, statycznym plikiem bez skryptów, odwołań zewnętrznych i aktywnej zawartości.', 'conference-manager'));
            }
            $mime_type = 'image/svg+xml';
            $extension = 'svg';
        } else {
            $image = @getimagesize($temporary);
            $allowed = array(IMAGETYPE_PNG => 'image/png', IMAGETYPE_JPEG => 'image/jpeg');
            if (defined('IMAGETYPE_WEBP')) { $allowed[IMAGETYPE_WEBP] = 'image/webp'; }
            if (!$image || !isset($allowed[$image[2]]) || $image[0] > 4096 || $image[1] > 4096) {
                return new WP_Error('invalid_icon', __('Dozwolone są tylko obrazy PNG, JPEG, WebP lub bezpieczne SVG o wymiarach do 4096 px.', 'conference-manager'));
            }
            $mime_type = $allowed[$image[2]];
            $extension = array_search($mime_type, array('png' => 'image/png', 'jpg' => 'image/jpeg', 'webp' => 'image/webp'), true);
        }
        $uploads = wp_upload_dir();
        $directory = $uploads['basedir'] . '/conference-manager/raffle-icons/' . absint($raffle_id) . '/';
        if (!wp_mkdir_p($directory)) {
            return new WP_Error('icon_directory', __('Nie udało się utworzyć katalogu ikon.', 'conference-manager'));
        }
        $filename = sanitize_file_name(pathinfo($original_name, PATHINFO_FILENAME));
        $filename = ($filename ?: 'icon') . '-' . wp_generate_password(12, false, false) . '.' . $extension;
        $filename = wp_unique_filename($directory, $filename);
        if (!@copy($temporary, $directory . $filename)) {
            return new WP_Error('icon_upload_failed', __('Nie udało się zapisać ikony.', 'conference-manager'));
        }
        $icon_id = CM_Database::insert('raffle_icons', array('raffle_id' => $raffle_id, 'file_name' => $filename, 'mime_type' => $mime_type));
        if (is_wp_error($icon_id)) { @unlink($directory . $filename); return $icon_id; }
        return $icon_id;
    }

    /**
     * SVG is rendered in an <img>, but is still inspected before storage so an
     * icon pool cannot become a place for executable or externally loaded XML.
     */
    private static function is_safe_svg_icon($temporary) {
        if (!class_exists('DOMDocument')) {
            return false;
        }

        $svg = @file_get_contents($temporary, false, null, 0, 2 * 1024 * 1024 + 1);
        if (!is_string($svg) || $svg === '' || strlen($svg) > 2 * 1024 * 1024 || strpos($svg, "\0") !== false) {
            return false;
        }
        $svg = preg_replace('/^\xEF\xBB\xBF/', '', $svg);
        if (!preg_match('/^\s*(?:<\?xml[^>]*\?>\s*)?(?:<!--[\s\S]*?-->\s*)*<svg[\s>]/i', $svg)
            || preg_match('/<!\s*(?:doctype|entity)\b/i', $svg)) {
            return false;
        }

        $previous_errors = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->resolveExternals = false;
        $document->substituteEntities = false;
        $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous_errors);
        if (!$loaded || !$document->documentElement || strtolower($document->documentElement->localName) !== 'svg'
            || $document->documentElement->namespaceURI !== 'http://www.w3.org/2000/svg') {
            return false;
        }

        $nodes = array($document);
        while (!empty($nodes)) {
            $node = array_pop($nodes);
            foreach ($node->childNodes as $child) {
                // An XML declaration is not represented in childNodes; every
                // processing instruction here is content such as xml-stylesheet.
                if ($child->nodeType === XML_PI_NODE || $child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                    return false;
                }
                $nodes[] = $child;
            }
        }

        $elements = $document->getElementsByTagName('*');
        if ($elements->length > 10000) {
            return false;
        }
        foreach ($elements as $element) {
            $element_name = strtolower($element->localName);
            if ($element->namespaceURI !== 'http://www.w3.org/2000/svg'
                || in_array($element_name, array('script', 'foreignobject', 'iframe', 'object', 'embed', 'image', 'audio', 'video', 'animate', 'animatecolor', 'animatemotion', 'animatetransform', 'set', 'discard', 'a'), true)) {
                return false;
            }
            foreach ($element->attributes as $attribute) {
                $name = strtolower($attribute->localName);
                $value = trim($attribute->nodeValue);
                if (strpos($name, 'on') === 0
                    || ($name === 'href' && substr($value, 0, 1) !== '#')
                    || preg_match('/(?:javascript\s*:|@import|expression\s*\(|-moz-binding|behavior\s*:)/i', $value)
                    || !self::has_only_internal_svg_urls($value)) {
                    return false;
                }
            }
            if ($element_name === 'style'
                && (preg_match('/(?:javascript\s*:|@import|expression\s*\(|-moz-binding|behavior\s*:)/i', $element->textContent)
                    || !self::has_only_internal_svg_urls($element->textContent))) {
                return false;
            }
        }

        return true;
    }

    /** Allow SVG paint-server references, but never URLs that leave this file. */
    private static function has_only_internal_svg_urls($value) {
        if (!preg_match_all('/url\s*\(\s*([^)]*?)\s*\)/i', (string) $value, $matches)) {
            return true;
        }
        foreach ($matches[1] as $target) {
            $target = trim($target);
            if (strlen($target) >= 2 && (($target[0] === '"' && substr($target, -1) === '"') || ($target[0] === "'" && substr($target, -1) === "'"))) {
                $target = trim(substr($target, 1, -1));
            }
            if (substr($target, 0, 1) !== '#') {
                return false;
            }
        }
        return true;
    }

    public static function delete_icon($raffle_id, $icon_id) {
        global $wpdb;
        $raffle_id = absint($raffle_id); $icon_id = absint($icon_id);
        $icons = CM_Database::get_table_name('raffle_icons');
        $participants = CM_Database::get_table_name('raffle_participants');
        $icon = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$icons} WHERE id = %d AND raffle_id = %d", $icon_id, $raffle_id));
        if (!$icon) { return new WP_Error('icon_not_found', __('Nie znaleziono ikony.', 'conference-manager')); }
        if ((int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$participants} WHERE raffle_id = %d AND icon_id = %d", $raffle_id, $icon_id)) > 0) {
            return new WP_Error('icon_in_use', __('Nie można usunąć ikony wybranej już przez uczestnika.', 'conference-manager'));
        }
        if (false === $wpdb->delete($icons, array('id' => $icon_id, 'raffle_id' => $raffle_id), array('%d', '%d'))) { return new WP_Error('icon_delete_failed', __('Nie udało się usunąć ikony.', 'conference-manager')); }
        $uploads = wp_upload_dir();
        $path = $uploads['basedir'] . '/conference-manager/raffle-icons/' . $raffle_id . '/' . basename($icon->file_name);
        if (is_file($path)) { @unlink($path); }
        return true;
    }

    /** Remove a participant and every draw that points to it as one operation. */
    public static function delete_participant($raffle_id, $participant_id) {
        global $wpdb;
        $raffle_id = absint($raffle_id);
        $participant_id = absint($participant_id);
        if (!self::get($raffle_id)) {
            return new WP_Error('raffle_not_found', __('Nie znaleziono losowania.', 'conference-manager'));
        }
        $participants = CM_Database::get_table_name('raffle_participants');
        $draws = CM_Database::get_table_name('raffle_draws');
        $participant = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$participants} WHERE id = %d AND raffle_id = %d", $participant_id, $raffle_id));
        if (!$participant) {
            return new WP_Error('participant_not_found', __('Nie znaleziono uczestnika tego losowania.', 'conference-manager'));
        }

        // InnoDB installations can roll both statements back, avoiding a
        // history entry that points to a participant that no longer exists.
        $transaction = ($wpdb->query('START TRANSACTION') !== false);
        $deleted_draws = $wpdb->delete($draws, array('raffle_id' => $raffle_id, 'participant_id' => $participant_id), array('%d', '%d'));
        $deleted_participant = $deleted_draws !== false && $wpdb->delete($participants, array('id' => $participant_id, 'raffle_id' => $raffle_id), array('%d', '%d'));
        if ($deleted_participant === false) {
            if ($transaction) { $wpdb->query('ROLLBACK'); }
            return new WP_Error('participant_delete_failed', __('Nie udało się usunąć uczestnika i jego historii losowań.', 'conference-manager'));
        }
        if ($transaction) { $wpdb->query('COMMIT'); }
        return true;
    }

    /**
     * Add a small, varied data set for demonstrating the draw on a test event.
     *
     * Registration is deliberately used for every row so generated entries have
     * the same validation and duplicate protection as public registrations.
     */
    public static function generate_test_participants($raffle_id) {
        $raffle_id = absint($raffle_id);
        if (!self::get($raffle_id)) {
            return new WP_Error('raffle_not_found', __('Nie znaleziono losowania.', 'conference-manager'));
        }

        $first_names = array('Adam', 'Adrian', 'Alicja', 'Amelia', 'Anna', 'Barbara', 'Daniel', 'Ewa', 'Filip', 'Hanna', 'Igor', 'Jan', 'Julia', 'Kamil', 'Karolina', 'Katarzyna', 'Lena', 'Maja', 'Marek', 'Maria', 'Michał', 'Monika', 'Natalia', 'Oliwia', 'Paweł', 'Piotr', 'Robert', 'Tomasz', 'Wiktoria', 'Zofia');
        $last_names = array('Bąk', 'Baran', 'Czarnecki', 'Dąbrowska', 'Gajda', 'Grabowski', 'Jabłońska', 'Jankowski', 'Kaczmarek', 'Kowalczyk', 'Kowalska', 'Król', 'Lis', 'Majewski', 'Mazur', 'Michalak', 'Nowak', 'Nowicka', 'Pawlak', 'Piotrowska', 'Sikora', 'Szymański', 'Wojciechowska', 'Wójcik', 'Wróbel', 'Zając', 'Zalewska', 'Zieliński', 'Żak', 'Żurawska');
        $candidates = array();
        foreach ($first_names as $first_name) {
            foreach ($last_names as $last_name) {
                $candidates[] = array($first_name, $last_name);
            }
        }

        // A secure shuffle avoids turning the demonstration list into a fixed
        // sequence, while the large combination set leaves room for duplicates
        // already registered on this raffle.
        for ($i = count($candidates) - 1; $i > 0; $i--) {
            try {
                $swap = random_int(0, $i);
            } catch (Exception $e) {
                return new WP_Error('test_participants_failed', __('Nie udało się przygotować danych testowych. Spróbuj ponownie.', 'conference-manager'));
            }
            $temporary = $candidates[$i];
            $candidates[$i] = $candidates[$swap];
            $candidates[$swap] = $temporary;
        }

        $generated = 0;
        $icons = self::get_icons($raffle_id);
        foreach ($candidates as $candidate) {
            $icon_id = !empty($icons) ? $icons[$generated % count($icons)]->id : 0;
            $result = self::register_participant($raffle_id, $candidate[0], $candidate[1], $icon_id);
            if (!is_wp_error($result)) {
                $generated++;
                if ($generated === 30) {
                    return array('generated' => $generated);
                }
                continue;
            }

            if (method_exists($result, 'get_error_code') && $result->get_error_code() === 'already_registered') {
                continue;
            }
            return $result;
        }

        return new WP_Error('test_participants_failed', sprintf(__('Nie udało się dodać dokładnie 30 uczestników testowych (dodano: %d).', 'conference-manager'), $generated));
    }

    public static function draw($raffle_id, $user_id) {
        global $wpdb;
        $raffle_id = absint($raffle_id);
        if (!self::get($raffle_id)) {
            return new WP_Error('raffle_not_found', __('Nie znaleziono losowania.', 'conference-manager'));
        }

        $participants = CM_Database::get_table_name('raffle_participants');
        // Pick an offset with a cryptographically secure PRNG. This samples
        // the complete population on every draw without SQL RAND() overhead;
        // previous winners intentionally remain eligible.
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$participants} WHERE raffle_id = %d", $raffle_id
        ));
        if ($count < 1) {
            return new WP_Error('no_participants', __('Brak zarejestrowanych uczestników do wylosowania.', 'conference-manager'));
        }
        try {
            $offset = random_int(0, $count - 1);
        } catch (Exception $e) {
            return new WP_Error('draw_failed', __('Nie udało się bezpiecznie wylosować zwycięzcy. Spróbuj ponownie.', 'conference-manager'));
        }
        $participant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$participants} WHERE raffle_id = %d ORDER BY id ASC LIMIT %d, 1",
            $raffle_id, $offset
        ));
        if (!$participant) {
            return new WP_Error('no_participants', __('Brak zarejestrowanych uczestników do wylosowania.', 'conference-manager'));
        }

        $draw_id = CM_Database::insert('raffle_draws', array(
            'raffle_id'      => $raffle_id,
            'participant_id' => (int) $participant->id,
            'drawn_by'       => absint($user_id),
        ));
        if (is_wp_error($draw_id)) {
            return $draw_id;
        }

        return array('draw_id' => $draw_id, 'participant' => $participant);
    }

    public static function get_draws($raffle_id) {
        global $wpdb;
        $draws = CM_Database::get_table_name('raffle_draws');
        $participants = CM_Database::get_table_name('raffle_participants');
        $icons = CM_Database::get_table_name('raffle_icons');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, p.first_name, p.last_name, p.icon_id, i.file_name AS icon_file_name FROM {$draws} d INNER JOIN {$participants} p ON p.id = d.participant_id LEFT JOIN {$icons} i ON i.id = p.icon_id AND i.raffle_id = p.raffle_id WHERE d.raffle_id = %d ORDER BY d.drawn_at DESC, d.id DESC",
            absint($raffle_id)
        ));
    }

    private static function string_length($value) {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private static function limit_string($value, $limit) {
        return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
    }
}
