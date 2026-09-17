<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/admin
 */

class CM_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Add admin notices hook
        add_action('admin_notices', array($this, 'display_admin_notices'));
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
    // Twój CSS pluginu
    wp_enqueue_style(
        $this->plugin_name,
        plugin_dir_url(__FILE__) . 'css/admin.css',
        array(),
        $this->version,
        'all'
    );

    // Tailwind CSS z CDN - ważne: version = null, żeby nie doklejało ?ver=...
    wp_enqueue_style(
        $this->plugin_name . '-tailwind',
        plugin_dir_url(__FILE__) . 'css/tailwind.css',
        array(),
        $this->version,
        'all'
    );
}

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'cm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cm_admin_nonce'),
            'ajax_nonce' => wp_create_nonce('cm_ajax_nonce')
        ));

        // Enqueue additional scripts based on current page
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'conference-manager') !== false) {
            
            wp_enqueue_script('jquery-ui-sortable');
            
            wp_enqueue_script(
                $this->plugin_name . '-lineup',
                plugin_dir_url(__FILE__) . 'js/lineup-manager.js',
                array('jquery', 'jquery-ui-sortable'),
                $this->version,
                false
            );
            
            wp_enqueue_script(
                $this->plugin_name . '-quiz',
                plugin_dir_url(__FILE__) . 'js/quiz-manager.js',
                array('jquery'),
                $this->version,
                false
            );
            
            wp_enqueue_script(
                $this->plugin_name . '-uploader',
                plugin_dir_url(__FILE__) . 'js/file-uploader.js',
                array('jquery'),
                $this->version,
                false
            );
            
            wp_enqueue_script(
                $this->plugin_name . '-tabs',
                plugin_dir_url(__FILE__) . 'js/tabs-navigation.js',
                array('jquery'),
                $this->version,
                false
            );

            // Add SSE support for admin area
            wp_enqueue_script(
                'cm-event-live-updates',
                plugin_dir_url(__FILE__) . '../public/js/event-live-updates.js',
                array('jquery'),
                '1.0.2',
                true
            );

            // Pass AJAX URL and nonce to JavaScript for SSE
            wp_localize_script('cm-event-live-updates', 'cm_event_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('cm_sse_nonce'),
                'debug'    => WP_DEBUG
            ));
        }
    }

    /**
     * Add plugin admin menu
     */
    public function add_plugin_admin_menu() {
        
        // Main menu page
        add_menu_page(
            'Conference Manager',
            'Conference Manager',
            'manage_options',
            'conference-manager',
            array($this, 'display_dashboard'),
            'dashicons-calendar-alt',
            30
        );

        // Dashboard (same as main menu)
        add_submenu_page(
            'conference-manager',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'conference-manager',
            array($this, 'display_dashboard')
        );

        // Events list
        add_submenu_page(
            'conference-manager',
            'Wydarzenia',
            'Wydarzenia',
            'manage_options',
            'conference-manager-events',
            array($this, 'display_events_list')
        );

        // Quiz Results
        add_submenu_page(
            null, // Parent slug null makes it a hidden page (not in menu)
            'Wyniki Quizu',
            'Wyniki Quizu',
            'manage_options',
            'cm-quiz-results',
            array($this, 'display_quiz_results')
        );

        // Settings
        add_submenu_page(
            'conference-manager',
            'Ustawienia',
            'Ustawienia',
            'manage_options',
            'conference-manager-settings',
            array($this, 'display_settings')
        );
    }

    /**
     * Display dashboard page
     */
    public function display_dashboard() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $events_count = CM_Event::get_count_by_status();
        $recent_events = CM_Event::get_all('', '5');
        
        include_once 'partials/dashboard.php';
    }

    /**
     * Display events list page
     */
    public function display_events_list() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Form submissions are now handled in class-core.php via admin_init hook

        // Check if we're editing an event
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['event_id'])) {
            $this->display_event_edit(intval($_GET['event_id'] ?? 0));
            return;
        }

        $events = CM_Event::get_all();
        include_once 'partials/events-list.php';
    }

    /**
     * Display event edit page
     */
    public function display_event_edit($event_id) {
        $event = new CM_Event($event_id);
        
        if (!$event->get_id()) {
            wp_die('Event not found');
        }

        $current_tab = $_GET['tab'] ?? 'basic';
        
        include_once 'partials/event-edit/main.php';
    }

    /**
     * Display settings page
     */
    public function display_settings() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['_wpnonce'], 'cm_settings')) {
            $this->handle_settings_form_submission();
        }

        include_once 'partials/global-settings.php';
    }

    /**
     * Display quiz results page
     */
    public function display_quiz_results() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get quiz ID from URL parameters
        $quiz_id = intval($_GET['quiz_id'] ?? 0);
        
        if (empty($quiz_id)) {
            wp_die(__('Quiz ID is required.'));
        }

        // Load quiz data
        $quiz = new CM_Quiz($quiz_id);
        if (!$quiz->get_id()) {
            wp_die(__('Quiz not found.'));
        }

        include_once 'partials/quiz-results.php';
    }

    /**
     * Handle settings form submission
     */
    private function handle_settings_form_submission() {
        $settings = array(
            'cm_qr_size' => intval($_POST['qr_size']),
            'cm_max_file_size' => intval($_POST['max_file_size']),
            'cm_quiz_time_limit' => intval($_POST['quiz_time_limit']),
            'cm_auto_advance_presentations' => isset($_POST['auto_advance_presentations']),
            'cm_allow_file_types' => isset($_POST['allow_file_types']) ? $_POST['allow_file_types'] : array()
        );

        foreach ($settings as $option_name => $option_value) {
            update_option($option_name, $option_value);
        }

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
        });
    }

    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        // Check for transient messages first
        $message_data = get_transient('cm_admin_message');
        if ($message_data) {
            $class = 'notice-' . ($message_data['type'] === 'success' ? 'success' : 'error');
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html($message_data['message']) . '</p></div>';
            delete_transient('cm_admin_message');
            return;
        }
        
        // Fallback to URL parameters for backwards compatibility
        if (isset($_GET['message'])) {
            $message = $_GET['message'] ?? '';
            $class = 'notice-success';
            
            switch ($message) {
                case 'created':
                    $text = 'Event created successfully.';
                    break;
                case 'updated':
                    $text = 'Event updated successfully.';
                    break;
                case 'deleted':
                    $text = 'Event deleted successfully.';
                    break;
                default:
                    return;
            }
            
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html($text) . '</p></div>';
        }
    }
}