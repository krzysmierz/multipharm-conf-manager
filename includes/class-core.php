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
        $this->loader->add_action('admin_init', $this, 'maybe_run_migration');
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
    }

    /**
     * Handle event form submission
     */
    private function handle_event_form_submission() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'cm_event_form')) {
            wp_die('Security check failed');
        }

        $event_data = CM_Permissions::sanitize_event_data($_POST);
        
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
        $current_version = 3;

        if ($migration_version >= $current_version) {
            return;
        }

        // Run incremental migration
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-activator.php';
        CM_Activator::update_lineup_table_schema();

        error_log('CM Migration: Running migration to version ' . $current_version);
        update_option('cm_db_migration_version', $current_version);
        error_log('CM Migration: Migration completed successfully');
    }
}