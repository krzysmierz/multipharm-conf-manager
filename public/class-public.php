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
        // Registration QR URLs are standalone public forms, independent from a
        // page/shortcode being present in the current theme.
        if (isset($_GET['cm_raffle']) && is_scalar($_GET['cm_raffle']) && $_GET['cm_raffle'] !== '') {
            // The response contains a nonce and personal submission status;
            // never let a page cache serve it to another visitor.
            nocache_headers();
            $this->display_raffle_registration(wp_unslash($_GET['cm_raffle']));
            return;
        }

        if (isset($_GET['cm_raffle_presentation']) && is_scalar($_GET['cm_raffle_presentation'])) {
            nocache_headers();
            $this->display_raffle_presentation(absint($_GET['cm_raffle_presentation']));
            return;
        }

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

    /** Render and process the public registration form for a raffle token. */
    private function display_raffle_registration($token) {
        $raffle = CM_Raffle::get_by_token($token);
        if (!$raffle) {
            wp_die(esc_html__('Nie znaleziono formularza rejestracji.', 'conference-manager'), esc_html__('Formularz niedostępny', 'conference-manager'), array('response' => 404));
        }

        $registration_error = '';
        $action = isset($_POST['cm_raffle_action']) && is_scalar($_POST['cm_raffle_action'])
            ? (string) wp_unslash($_POST['cm_raffle_action']) : '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
            $raw_nonce = $_POST['_wpnonce'] ?? '';
            $nonce = is_scalar($raw_nonce) ? sanitize_text_field(wp_unslash($raw_nonce)) : '';
            if (!wp_verify_nonce($nonce, 'cm_raffle_registration_' . $raffle->token)) {
                status_header(403);
                $registration_error = __('Sesja formularza wygasła. Odśwież stronę i spróbuj ponownie.', 'conference-manager');
            } else {
                $result = CM_Raffle::register_participant(
                    $raffle->id,
                    $_POST['first_name'] ?? '',
                    $_POST['last_name'] ?? '',
                    $_POST['icon_id'] ?? 0
                );
                if (is_wp_error($result)) {
                    $registration_error = $result->get_error_message();
                } else {
                    wp_safe_redirect(add_query_arg(array(
                        'cm_raffle' => rawurlencode($raffle->token),
                        'cm_raffle_status' => 'registered',
                    ), home_url('/')));
                    exit;
                }
            }
        }

        $icons = CM_Raffle::get_icons($raffle->id);
        wp_enqueue_script(
            'cm-raffle-avatar-slider',
            plugin_dir_url(__FILE__) . 'js/raffle-avatar-slider.js',
            array(),
            defined('CONFERENCE_MANAGER_VERSION') ? CONFERENCE_MANAGER_VERSION : null,
            true
        );
        wp_enqueue_script(
            'cm-raffle-registration',
            plugin_dir_url(__FILE__) . 'js/raffle-registration.js',
            array(),
            defined('CONFERENCE_MANAGER_VERSION') ? CONFERENCE_MANAGER_VERSION : null,
            true
        );
        $template = CONFERENCE_MANAGER_PLUGIN_PATH . 'public/partials/raffle-registration.php';
        if (!file_exists($template)) {
            wp_die(esc_html__('Brakuje szablonu formularza rejestracji.', 'conference-manager'));
        }
        include $template;
        exit;
    }

    /** Render the audience screen that an administrator uses to run a draw. */
    private function display_raffle_presentation($raffle_id) {
        $raffle = CM_Raffle::get($raffle_id);
        if (!$raffle) {
            wp_die(esc_html__('Nie znaleziono losowania.', 'conference-manager'), esc_html__('Losowanie niedostępne', 'conference-manager'), array('response' => 404));
        }

        $event = new CM_Event($raffle->event_id);
        self::enqueue_raffle_presentation_assets();

        $template = CONFERENCE_MANAGER_PLUGIN_PATH . 'public/partials/raffle-presentation.php';
        if (!file_exists($template)) {
            wp_die(esc_html__('Brakuje szablonu ekranu losowania.', 'conference-manager'));
        }
        include $template;
        exit;
    }

    /** Load the draw component script for standalone and live-updated views. */
    public static function enqueue_raffle_presentation_assets() {
        wp_enqueue_script(
            'cm-raffle-presentation',
            plugin_dir_url(__FILE__) . 'js/raffle-presentation.js',
            array(),
            defined('CONFERENCE_MANAGER_VERSION') ? CONFERENCE_MANAGER_VERSION : null,
            true
        );
    }

    /**
     * Render the same self-contained draw component in a standalone screen or
     * an active schedule block. The component's data attributes keep each
     * dynamically inserted raffle bound to its own nonce and raffle ID.
     */
    public static function render_raffle_presentation_card($raffle, $event = null) {
        if (!$raffle) {
            return '';
        }

        $event = $event ?: new CM_Event($raffle->event_id);
        $participants = CM_Raffle::get_participants($raffle->id);
        $draws = CM_Raffle::get_draws($raffle->id);
        $latest_draw = !empty($draws) ? $draws[0] : null;
        $can_draw = current_user_can('manage_options');
        $template = CONFERENCE_MANAGER_PLUGIN_PATH . 'public/partials/raffle-presentation-card.php';

        if (!file_exists($template)) {
            return '<p>' . esc_html__('Brakuje komponentu ekranu losowania.', 'conference-manager') . '</p>';
        }

        ob_start();
        include $template;
        return ob_get_clean();
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
