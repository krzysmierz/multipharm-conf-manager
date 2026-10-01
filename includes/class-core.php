<?php

/**
 * The core plugin class.
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {

        if (defined('CONFERENCE_MANAGER_VERSION')) {
            $this->version = CONFERENCE_MANAGER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'conference-manager';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
        // Handle form submissions early
        $this->loader->add_action('admin_init', $this, 'handle_admin_forms');
        
        // Run database migration if needed
        $this->loader->add_action('admin_init', $this, 'maybe_run_migration', 5);
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-database.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-event.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-lineup.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-quiz.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-quiz-state.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sse-controller.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-qr-generator.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-raffle.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-shortcodes.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ajax.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-file-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-permissions.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-public.php';

        $this->loader = new CM_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {

        $plugin_admin = new CM_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Initialize AJAX handlers
        $ajax_handler = new CM_Ajax();
        $ajax_handler->init_hooks($this->loader);

        // Initialize shortcodes
        $shortcodes = new CM_Shortcodes();
        $shortcodes->init_hooks($this->loader);
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {

        $plugin_public = new CM_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Initialize public handlers for URL parameter handling (QR codes, etc.)
        $plugin_public->init_public_handlers();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'conference-manager',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Handle admin form submissions early to prevent headers already sent
     */
    public function handle_admin_forms() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        // Handle event form submission
        if (isset($_POST['action']) && $_POST['action'] === 'cm_save_event') {
            $this->handle_event_form_submission();
        }

        // Handle event deletion
        if (isset($_GET['action']) && $_GET['action'] === 'cm_delete_event' && isset($_GET['event_id'])) {
            $this->handle_event_deletion();
        }

        if (isset($_POST['action']) && $_POST['action'] === 'cm_create_raffle') {
            $this->handle_raffle_creation();
        }

        if (isset($_POST['action']) && $_POST['action'] === 'cm_draw_raffle') {
            $this->handle_raffle_draw();
        }

        if (isset($_POST['action']) && $_POST['action'] === 'cm_generate_raffle_test_participants') {
            $this->handle_raffle_test_participants();
        }

        if (isset($_POST['action']) && $_POST['action'] === 'cm_upload_raffle_icons') {
            $this->handle_raffle_icon_upload();
        }

        if (isset($_POST['action']) && $_POST['action'] === 'cm_delete_raffle_icon') {
            $this->handle_raffle_icon_deletion();
        }

        if (isset($_POST['action']) && $_POST['action'] === 'cm_delete_raffle_participant') {
            $this->handle_raffle_participant_deletion();
        }

        if (isset($_POST['action']) && $_POST['action'] === 'cm_regenerate_raffle_qr') {
            $this->handle_raffle_qr_regeneration();
        }
    }

    /**
     * Handle event form submission
     */
    private function handle_event_form_submission() {
        $raw_nonce = $_POST['_wpnonce'] ?? '';
        $nonce = is_scalar($raw_nonce) ? sanitize_text_field(wp_unslash($raw_nonce)) : '';
        if (!wp_verify_nonce($nonce, 'cm_event_form')) {
            wp_die('Security check failed');
        }

        $event_data = CM_Permissions::sanitize_event_data($_POST);
        $custom_css = null;
        if (array_key_exists('event_custom_css', $_POST)) {
            if (!current_user_can('edit_css')) {
                wp_die(esc_html__('Nie masz uprawnień do edycji CSS.', 'conference-manager'), '', array('response' => 403));
            }

            $custom_css = CM_Event::validate_custom_css_settings(wp_unslash($_POST['event_custom_css']));
            if (is_wp_error($custom_css)) {
                set_transient('cm_admin_message', array('type' => 'error', 'message' => $custom_css->get_error_message()), 30);
                wp_safe_redirect(admin_url('admin.php?page=conference-manager-events'));
                exit;
            }
        }
        
        if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
            $event = new CM_Event($_POST['event_id']);
            $action_type = 'updated';
        } else {
            $event = new CM_Event();
            $action_type = 'created';
        }

        // Set event properties
        foreach (['title', 'description', 'event_date', 'start_time', 'end_time', 'status'] as $field) {
            if (isset($event_data[$field])) {
                $setter = 'set_' . $field;
                $event->$setter($event_data[$field]);
            }
        }

        $result = $event->save();

        if (!is_wp_error($result) && $custom_css !== null) {
            $css_result = $event->set_custom_css_settings($custom_css);
            if (is_wp_error($css_result)) {
                $result = $css_result;
            }
        }
        
        if (!is_wp_error($result)) {
            set_transient('cm_admin_message', array(
                'type' => 'success',
                'message' => 'Event ' . $action_type . ' successfully.'
            ), 30);
        } else {
            set_transient('cm_admin_message', array(
                'type' => 'error', 
                'message' => 'Error: ' . $result->get_error_message()
            ), 30);
        }
        
        wp_redirect(admin_url('admin.php?page=conference-manager-events'));
        exit;
    }

    /**
     * Handle event deletion
     */
    private function handle_event_deletion() {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_event_' . ($_GET['event_id'] ?? ''))) {
            wp_die('Security check failed');
        }

        $event = new CM_Event(intval($_GET['event_id'] ?? 0));
        $result = $event->delete();
        
        if ($result !== false) {
            set_transient('cm_admin_message', array(
                'type' => 'success',
                'message' => 'Event deleted successfully.'
            ), 30);
        } else {
            set_transient('cm_admin_message', array(
                'type' => 'error',
                'message' => 'Failed to delete event.'
            ), 30);
        }
        
        wp_redirect(admin_url('admin.php?page=conference-manager-events'));
        exit;
    }

    /** Create a public registration QR for an event. */
    private function handle_raffle_creation() {
        $event_id = absint($_POST['event_id'] ?? 0);
        $nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) $_POST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'cm_create_raffle_' . $event_id)) {
            wp_die(esc_html__('Security check failed.', 'conference-manager'));
        }

        $result = CM_Raffle::create($event_id, $_POST['raffle_label'] ?? '');
        if (is_wp_error($result)) {
            $message = array('type' => 'error', 'message' => $result->get_error_message());
        } else {
            $message = array(
                'type' => 'success',
                'message' => isset($result['qr_error'])
                    ? __('Utworzono losowanie, ale nie udało się wygenerować PNG QR. Użyj adresu rejestracji lub spróbuj ponownie po naprawie konfiguracji QR.', 'conference-manager')
                    : __('Utworzono losowanie i kod QR rejestracji.', 'conference-manager'),
            );
        }
        set_transient('cm_admin_message', $message, 30);

        $url = admin_url('admin.php?page=conference-manager-events&action=edit&event_id=' . $event_id . '&tab=raffle');
        if (!is_wp_error($result)) {
            $url = add_query_arg('raffle_id', (int) $result['id'], $url);
        }
        wp_safe_redirect($url);
        exit;
    }

    /** Persist every administrator-triggered winner draw. */
    private function handle_raffle_draw() {
        $raffle_id = absint($_POST['raffle_id'] ?? 0);
        $nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) $_POST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'cm_draw_raffle_' . $raffle_id)) {
            wp_die(esc_html__('Security check failed.', 'conference-manager'));
        }

        $raffle = CM_Raffle::get($raffle_id);
        if (!$raffle) {
            wp_die(esc_html__('Nie znaleziono losowania.', 'conference-manager'));
        }

        $result = CM_Raffle::draw($raffle_id, get_current_user_id());
        if (is_wp_error($result)) {
            $message = array('type' => 'error', 'message' => $result->get_error_message());
        } else {
            $participant = $result['participant'];
            $message = array('type' => 'success', 'message' => sprintf(__('Wylosowano: %s %s.', 'conference-manager'), $participant->first_name, $participant->last_name));
        }
        set_transient('cm_admin_message', $message, 30);
        wp_safe_redirect(add_query_arg(
            array('page' => 'conference-manager-events', 'action' => 'edit', 'event_id' => (int) $raffle->event_id, 'tab' => 'raffle', 'raffle_id' => $raffle_id),
            admin_url('admin.php')
        ));
        exit;
    }

    /** Generate exactly thirty registration-shaped entries for a selected raffle. */
    private function handle_raffle_test_participants() {
        $raffle_id = absint($_POST['raffle_id'] ?? 0);
        $nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) $_POST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'cm_generate_raffle_test_participants_' . $raffle_id)) {
            wp_die(esc_html__('Security check failed.', 'conference-manager'));
        }

        $raffle = CM_Raffle::get($raffle_id);
        if (!$raffle) {
            wp_die(esc_html__('Nie znaleziono losowania.', 'conference-manager'));
        }

        $result = CM_Raffle::generate_test_participants($raffle_id);
        set_transient('cm_admin_message', array(
            'type' => is_wp_error($result) ? 'error' : 'success',
            'message' => is_wp_error($result)
                ? $result->get_error_message()
                : sprintf(__('Dodano %d testowych uczestników.', 'conference-manager'), $result['generated']),
        ), 30);
        wp_safe_redirect(add_query_arg(
            array('page' => 'conference-manager-events', 'action' => 'edit', 'event_id' => (int) $raffle->event_id, 'tab' => 'raffle', 'raffle_id' => $raffle_id),
            admin_url('admin.php')
        ));
        exit;
    }

    private function handle_raffle_icon_upload() {
        $raffle_id = absint($_POST['raffle_id'] ?? 0);
        $nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) $_POST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'cm_upload_raffle_icons_' . $raffle_id)) {
            wp_die(esc_html__('Security check failed.', 'conference-manager'));
        }
        $raffle = CM_Raffle::get($raffle_id);
        if (!$raffle) { wp_die(esc_html__('Nie znaleziono losowania.', 'conference-manager')); }
        $files = $this->normalize_uploaded_files($_FILES['raffle_icons'] ?? array());
        $result = CM_Raffle::upload_icons($raffle_id, $files, $_FILES['raffle_icon_zip'] ?? null);
        $message = is_wp_error($result) ? $result->get_error_message() : sprintf(
            __('Dodano ikon: %d. Odrzucono: %d.', 'conference-manager'), $result['accepted'], count($result['rejected'])
        );
        if (!is_wp_error($result) && !empty($result['rejected'])) { $message .= ' ' . implode(' ', array_slice($result['rejected'], 0, 3)); }
        $this->redirect_raffle_manager($raffle, array('type' => is_wp_error($result) || !$result['accepted'] ? 'error' : 'success', 'message' => $message));
    }

    private function handle_raffle_icon_deletion() {
        $raffle_id = absint($_POST['raffle_id'] ?? 0);
        $icon_id = absint($_POST['icon_id'] ?? 0);
        $nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) $_POST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'cm_delete_raffle_icon_' . $raffle_id . '_' . $icon_id)) {
            wp_die(esc_html__('Security check failed.', 'conference-manager'));
        }
        $raffle = CM_Raffle::get($raffle_id);
        if (!$raffle) { wp_die(esc_html__('Nie znaleziono losowania.', 'conference-manager')); }
        $result = CM_Raffle::delete_icon($raffle_id, $icon_id);
        $this->redirect_raffle_manager($raffle, array('type' => is_wp_error($result) ? 'error' : 'success', 'message' => is_wp_error($result) ? $result->get_error_message() : __('Usunięto ikonę.', 'conference-manager')));
    }

    private function handle_raffle_participant_deletion() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Brak uprawnień.', 'conference-manager'), '', array('response' => 403));
        }
        $raffle_id = absint($_POST['raffle_id'] ?? 0);
        $participant_id = absint($_POST['participant_id'] ?? 0);
        $nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) $_POST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'cm_delete_raffle_participant_' . $raffle_id . '_' . $participant_id)) {
            wp_die(esc_html__('Security check failed.', 'conference-manager'), '', array('response' => 403));
        }
        $raffle = CM_Raffle::get($raffle_id);
        if (!$raffle) { wp_die(esc_html__('Nie znaleziono losowania.', 'conference-manager')); }
        $result = CM_Raffle::delete_participant($raffle_id, $participant_id);
        $this->redirect_raffle_manager($raffle, array('type' => is_wp_error($result) ? 'error' : 'success', 'message' => is_wp_error($result) ? $result->get_error_message() : __('Usunięto uczestnika oraz powiązaną historię losowań.', 'conference-manager')));
    }

    private function normalize_uploaded_files($upload) {
        if (empty($upload['name']) || !is_array($upload['name'])) { return array(); }
        $files = array();
        foreach ($upload['name'] as $index => $name) {
            if (($upload['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { continue; }
            $files[] = array('name' => $name, 'tmp_name' => $upload['tmp_name'][$index] ?? '', 'size' => $upload['size'][$index] ?? 0, 'error' => $upload['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        }
        return $files;
    }

    private function redirect_raffle_manager($raffle, $message) {
        set_transient('cm_admin_message', $message, 30);
        wp_safe_redirect(add_query_arg(array('page' => 'conference-manager-events', 'action' => 'edit', 'event_id' => (int) $raffle->event_id, 'tab' => 'raffle', 'raffle_id' => (int) $raffle->id), admin_url('admin.php')));
        exit;
    }

    private function handle_raffle_qr_regeneration() {
        $raffle_id = absint($_POST['raffle_id'] ?? 0);
        $nonce = isset($_POST['_wpnonce']) && is_scalar($_POST['_wpnonce']) ? (string) $_POST['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'cm_regenerate_raffle_qr_' . $raffle_id)) {
            wp_die(esc_html__('Security check failed.', 'conference-manager'));
        }
        $raffle = CM_Raffle::get($raffle_id);
        if (!$raffle) {
            wp_die(esc_html__('Nie znaleziono losowania.', 'conference-manager'));
        }
        $result = CM_QR_Generator::generate_raffle_qr($raffle_id);
        set_transient('cm_admin_message', array(
            'type' => is_wp_error($result) ? 'error' : 'success',
            'message' => is_wp_error($result) ? $result->get_error_message() : __('Wygenerowano nowy plik QR.', 'conference-manager'),
        ), 30);
        wp_safe_redirect(add_query_arg(
            array('page' => 'conference-manager-events', 'action' => 'edit', 'event_id' => (int) $raffle->event_id, 'tab' => 'raffle', 'raffle_id' => $raffle_id),
            admin_url('admin.php')
        ));
        exit;
    }
    
    /**
     * Run database migration if needed
     */
    public function maybe_run_migration() {
        global $wpdb;

        // Check if tables exist
        $table_name = $wpdb->prefix . 'cm_events';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if (!$table_exists) {
            // Tables don't exist, run full activation
            error_log('CM Migration: Tables missing, running full activation');
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-activator.php';
            CM_Activator::activate();
            error_log('CM Migration: Full activation completed');
            return;
        }

        // Check for incremental migrations
        $migration_version = get_option('cm_db_migration_version', 0);
        // Version 7 adds raffle schedule block columns to existing lineups.
        $current_version = 7;

        if ($migration_version >= $current_version) {
            return;
        }

        // Run incremental migration
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-activator.php';
        CM_Activator::update_lineup_table_schema();
        if (!CM_Activator::update_raffle_table_schema()) {
            error_log('CM Migration: Raffle schema migration did not complete; will retry.');
            return;
        }

        error_log('CM Migration: Running migration to version ' . $current_version);
        update_option('cm_db_migration_version', $current_version);
        error_log('CM Migration: Migration completed successfully');
    }
}
