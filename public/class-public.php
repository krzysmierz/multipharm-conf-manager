<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/public
 */

class CM_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/public.css',
            array(),
            $this->version,
            'all'
        );
        
        // Tailwind CSS z CDN
    wp_enqueue_style(
        $this->plugin_name . '-tailwind',
        plugin_dir_url(__FILE__) . 'css/tailwind.css',
        array(),
        $this->version,
        'all'
    );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/public.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'cm_public_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cm_public_nonce')
        ));

        // Enqueue quiz functionality
        wp_enqueue_script(
            $this->plugin_name . '-quiz',
            plugin_dir_url(__FILE__) . 'js/quiz.js',
            array('jquery'),
            $this->version,
            false
        );

        // Enqueue live updates functionality (globally, only once)
        wp_enqueue_script(
            $this->plugin_name . '-live-updates',
            plugin_dir_url(__FILE__) . 'js/quiz-live-updates.js',
            array(),
            $this->version,
            false
        );

        // Enqueue live timer functionality
        wp_enqueue_script(
            $this->plugin_name . '-timer',
            plugin_dir_url(__FILE__) . 'js/live-timer.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    /**
     * Handle query vars for public display
     */
    public function init_public_handlers() {
        add_action('template_redirect', array($this, 'handle_public_requests'));
    }

    /**
     * Handle public requests (QR code redirects, etc.)
     */
    public function handle_public_requests() {
        // Handle event display
        if (isset($_GET['cm_event']) && !empty($_GET['cm_event'])) {
            $this->display_public_event($_GET['cm_event']);
        }

        // Handle quiz display
        if (isset($_GET['cm_quiz']) && !empty($_GET['cm_quiz'])) {
            $this->display_public_quiz($_GET['cm_quiz']);
        }

        // Handle presentation display
        if (isset($_GET['cm_presentation']) && !empty($_GET['cm_presentation'])) {
            $this->display_public_presentation($_GET['cm_presentation']);
        }
    }

    /**
     * Display public event page
     */
    private function display_public_event($event_id) {
        $event = new CM_Event($event_id);
        
        if (!$event->get_id()) {
            wp_die('Event not found', 'Event Not Found', array('response' => 404));
        }

        // Get event data
        $lineup_items = CM_Lineup::get_by_event_chronological($event_id);
        $active_quizzes = CM_Quiz::get_active_by_event($event_id);

        // Store data globally for shortcode access
        global $cm_current_event;
        $cm_current_event = $event;
    }

    /**
     * Display public quiz page
     */
    private function display_public_quiz($quiz_id) {
        $quiz = new CM_Quiz($quiz_id);
        
        if (!$quiz->get_id() || !$quiz->get_is_active()) {
            wp_die('Quiz not found or not active', 'Quiz Not Available', array('response' => 404));
        }

        $questions = $quiz->get_questions();
        
        // Store data globally for shortcode access
        global $cm_current_quiz;
        $cm_current_quiz = $quiz;
    }

    /**
     * Display public presentation page
     */
    private function display_public_presentation($lineup_id) {
        $presentation = CM_Database::get_row('lineup', array('id' => $lineup_id));
        
        if (!$presentation) {
            wp_die('Presentation not found', 'Presentation Not Found', array('response' => 404));
        }

        $event = new CM_Event($presentation->event_id);

        // Store data globally for shortcode access
        global $cm_current_presentation, $cm_current_event;
        $cm_current_presentation = $presentation;
        $cm_current_event = $event;
    }

    /**
     * Load public template - compatible with WordPress and Elementor
     */
    private function load_public_template($template, $vars = array()) {
        // Store template variables globally for shortcode access
        global $cm_template_vars;
        $cm_template_vars = $vars;

        // Instead of forcing template, let WordPress handle the page normally
        // The shortcode will render the content based on URL parameters
        return;
    }

    /**
     * Handle shortcode requests
     */
    public function handle_shortcode_requests() {
        // This is handled by the CM_Shortcodes class
    }

    /**
     * Get template variables (for use in templates)
     */
    public static function get_template_vars() {
        global $cm_template_vars;
        return $cm_template_vars ? $cm_template_vars : array();
    }
    
    /**
     * Set template variables (for use in shortcodes)
     */
    public static function set_template_vars($vars) {
        global $cm_template_vars;
        $cm_template_vars = $vars;
    }
}