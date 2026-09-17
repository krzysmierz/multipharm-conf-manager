<?php

/**
 * Shortcodes class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Shortcodes {

    /**
     * Initialize shortcode hooks
     */
    public function init_hooks($loader) {
        $loader->add_action('init', $this, 'register_shortcodes');
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        error_log('[SSE DEBUG] Registering shortcodes...');
        add_shortcode('cm_event', array($this, 'display_event'));
        add_shortcode('cm_current_presentation', array($this, 'display_current_presentation'));
        add_shortcode('cm_quiz', array($this, 'display_quiz'));
        add_shortcode('cm_quiz_display', array($this, 'display_quiz_page'));
        add_shortcode('cm_event_lineup', array($this, 'display_event_lineup'));
        error_log('[SSE DEBUG] Shortcodes registered: cm_current_presentation, cm_event_lineup');
    }

    /**
     * Display event shortcode
     * [cm_event id="1"]
     */
    public function display_event($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        if (empty($atts['id'])) {
            return '<p>Event ID is required.</p>';
        }

        $event = new CM_Event($atts['id']);
        
        if (!$event->get_id()) {
            return '<p>Event not found.</p>';
        }

        ob_start();
        
        // Load event display template
        $template_path = CONFERENCE_MANAGER_PLUGIN_PATH . 'public/partials/event-display.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo $this->get_default_event_template($event);
        }
        
        return ob_get_clean();
    }

    /**
     * Display current presentation shortcode with SSE support
     * [cm_current_presentation event_id="1"]
     */
    public function display_current_presentation($atts) {
        $atts = shortcode_atts(array(
            'event_id' => 0,
        ), $atts);

        if (empty($atts['event_id'])) {
            return '<p>Event ID is required.</p>';
        }

        $event_id = intval($atts['event_id']);

        // Enqueue SSE script
        wp_enqueue_script(
            'cm-event-live-updates',
            CONFERENCE_MANAGER_PLUGIN_URL . 'public/js/event-live-updates.js',
            array(),
            time(),
            true
        );

        // Pass AJAX URL and nonce to JavaScript (only if not already localized)
        global $wp_scripts;
        if (!isset($wp_scripts->registered['cm-event-live-updates']->extra['data'])) {
            wp_localize_script('cm-event-live-updates', 'cm_event_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('cm_sse_nonce'),
                'debug'    => WP_DEBUG
            ));
        }

        if (WP_DEBUG) {
            error_log('CM_Shortcodes::display_current_presentation - Event ID: ' . $event_id);
        }

        $current_presentation = CM_Lineup::get_active_presentation($event_id);

        // Get initial presentation HTML
        $content = $this->render_presentation_html($current_presentation);

        $result = sprintf(
            '<div id="cm-current-presentation-%d" class="cm-live-container" data-event-id="%d">%s</div>',
            $event_id,
            $event_id,
            $content
        );

        // Debug log to check if shortcode is being called
        error_log('[SSE DEBUG] cm_current_presentation shortcode called, returning: ' . substr($result, 0, 200));

        return $result;
    }

    /**
     * Display quiz shortcode
     * [cm_quiz id="1"]
     */
    public function display_quiz($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        if (empty($atts['id'])) {
            return '<p>Quiz ID is required.</p>';
        }

        $quiz = new CM_Quiz($atts['id']);
        
        if (!$quiz->get_id()) {
            return '<p>Quiz not found.</p>';
        }

        if (!$quiz->get_is_active()) {
            return '<p>Quiz is not active.</p>';
        }

        ob_start();
        
        // Load quiz display template
        $template_path = CONFERENCE_MANAGER_PLUGIN_PATH . 'public/partials/quiz-display.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo $this->get_default_quiz_template($quiz);
        }
        
        return ob_get_clean();
    }

    /**
     * Display event lineup shortcode with SSE support
     * [cm_event_lineup event_id="1"]
     */
    public function display_event_lineup($atts) {
        $atts = shortcode_atts(array(
            'event_id' => 0,
            'day' => 'auto' // auto = active day
        ), $atts);

        if (empty($atts['event_id'])) {
            return '<p>Event ID is required.</p>';
        }

        $event_id = intval($atts['event_id']);
        $event = new CM_Event($event_id);

        if (!$event->exists()) {
            return '<p>Event not found.</p>';
        }

        $current_day = ($atts['day'] === 'auto') ? $event->get_current_active_day() : intval($atts['day']);

        // Debug current day selection
        error_log("CM_Shortcodes: Event {$event_id} - Active day from event: {$event->get_current_active_day()}, Using day: {$current_day}");

        $lineup_items = CM_Lineup::get_by_event_and_day($event_id, $current_day);

        // Enqueue SSE script
        wp_enqueue_script(
            'cm-event-live-updates',
            CONFERENCE_MANAGER_PLUGIN_URL . 'public/js/event-live-updates.js',
            array(),
            time(),
            true
        );

        // Pass AJAX URL and nonce to JavaScript including public AJAX nonce
        global $wp_scripts;
        if (!isset($wp_scripts->registered['cm-event-live-updates']->extra['data'])) {
            wp_localize_script('cm-event-live-updates', 'cm_event_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('cm_sse_nonce'),
                'debug'    => WP_DEBUG
            ));
        }

        wp_localize_script('cm-event-live-updates', 'cm_public_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cm_public_nonce')
        ));

        if (WP_DEBUG) {
            error_log("CM_Shortcodes::display_event_lineup - Event ID: {$event_id}, Current Day: {$current_day}");
        }

        // Filter lineup by time
        $filtered_lineup = $this->filter_lineup_by_time($lineup_items);

        // Get initial lineup HTML
        $content = $this->render_lineup_html($filtered_lineup);

        // Add multi-day navigation if event has multiple days
        $total_days = $event->get_total_days();
        error_log("CM_Shortcodes: Event {$event_id} has {$total_days} total days, current day: {$current_day}");

        $multi_day_nav = '';
        if ($total_days > 1) {
            error_log("CM_Shortcodes: Rendering multi-day navigation for event {$event_id}");
            $multi_day_nav = $this->render_multi_day_navigation($event, $current_day);
            error_log("CM_Shortcodes: Multi-day nav HTML length: " . strlen($multi_day_nav));
            error_log("CM_Shortcodes: Multi-day nav preview: " . substr($multi_day_nav, 0, 200) . "...");
        } else {
            error_log("CM_Shortcodes: Event {$event_id} has only 1 day, skipping navigation");
        }

        $final_html = sprintf(
            '<div id="cm-event-lineup-%d" class="cm-live-container" data-event-id="%d" data-current-day="%d">%s%s</div>',
            $event_id,
            $event_id,
            $current_day,
            $content,
            $multi_day_nav
        );

        error_log("CM_Shortcodes: Final HTML length: " . strlen($final_html) . ", contains buttons: " . (strpos($final_html, 'Zobacz program') !== false ? 'YES' : 'NO'));

        return $final_html;
    }

    /**
     * Default event template
     */
    private function get_default_event_template($event) {
        $output = '<div class="cm-event-container">';
        $output .= '<h2>' . esc_html($event->get_title()) . '</h2>';
        
        if ($event->get_description()) {
            $output .= '<div class="cm-event-description">' . wp_kses_post($event->get_description()) . '</div>';
        }
        
        $output .= '<div class="cm-event-details">';
        $output .= '<p><strong>Date:</strong> ' . esc_html($event->get_event_date()) . '</p>';
        
        if ($event->get_start_time()) {
            $output .= '<p><strong>Time:</strong> ' . esc_html($event->get_start_time());
            if ($event->get_end_time()) {
                $output .= ' - ' . esc_html($event->get_end_time());
            }
            $output .= '</p>';
        }
        
        $output .= '<p><strong>Status:</strong> ' . esc_html(ucfirst($event->get_status())) . '</p>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Default presentation template
     */
    private function get_default_presentation_template($presentation) {
        $output = '<div class="cm-current-presentation">';
        $output .= '<h3>Trwa</h3>';
        $output .= '<h4>' . esc_html($presentation->title) . '</h4>';

        if ($presentation->presenter) {
            $output .= '<p class="cm-presenter text-white"><strong>Prelegent:</strong> ' . esc_html($presentation->presenter) . '</p>';
        }

        if ($presentation->description) {
            $output .= '<div class="cm-presentation-description">' . wp_kses_post($presentation->description) . '</div>';
        }

        $output .= '<p class="cm-presentation-time"><strong>Traw od:</strong> ' . esc_html($presentation->start_time) . '</p>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Render presentation HTML for SSE updates
     */
    private function render_presentation_html($presentation) {
        if (!$presentation) {
            return '<div class="bg-gray-100 rounded-lg p-8 text-center text-gray-500">Brak aktywnej prezentacji</div>';
        }

        if (WP_DEBUG) {
            error_log('CM_Shortcodes::render_presentation_html - Presentation: ' . $presentation->title);
        }

        $start_time_formatted = date("H:i", strtotime($presentation->start_time));

        $output = '<div class="bg-gradient-to-r from-pink-900 to-rose-600 text-white p-8 rounded-lg shadow-lg relative">';
        $output .= '<div class="text-sm font-bold uppercase tracking-wider">Trwa</div>';
        $output .= '<h2 class="text-4xl font-bold mt-2 mb-4">' . esc_html($presentation->title) . '</h2>';
        
        if ($presentation->presenter) {
            $output .= '<p class="text-lg opacity-80"><strong>Prelegent:</strong> ' . esc_html($presentation->presenter) . '</p>';
        }

        $output .= '<div class="absolute top-4 right-4 bg-black bg-opacity-20 text-white text-sm font-semibold px-3 py-1 rounded-full">';
        $output .= 'Trwa od: ' . esc_html($start_time_formatted);
        $output .= '</div>';
        
        $output .= '</div>';

        return $output;
    }

    /**
     * Filter lineup by time (hide past presentations)
     */
    private function filter_lineup_by_time($lineup) {
        if (empty($lineup)) {
            return array();
        }

        // 1. Log events from DB
        error_log('[DEBUG] filter_lineup_by_time: Initial lineup received. Count: ' . count($lineup));
        foreach ($lineup as $index => $item) {
            // Using json_encode for a more compact and readable object representation
            error_log("[DEBUG] filter_lineup_by_time: Item #{$index}: " . json_encode($item));
        }

        $current_time = current_time('H:i:s');

        if (WP_DEBUG) {
            error_log('CM_Shortcodes::filter_lineup_by_time - Current time: ' . $current_time . ', lineup count: ' . count($lineup));
        }

        // Find active presentation to determine "event time"
        $active_presentation = null;
        foreach ($lineup as $item) {
            if ($item->is_active) {
                $active_presentation = $item;
                break;
            }
        }

        // 2. Log active presentation
        if ($active_presentation) {
            error_log('[DEBUG] filter_lineup_by_time: Active presentation found: ' . json_encode($active_presentation));
        } else {
            error_log('[DEBUG] filter_lineup_by_time: No active presentation found. Using current server time for filtering: ' . $current_time);
        }

        $filtered_lineup = array_filter($lineup, function($item) use ($current_time, $active_presentation) {
            $item_title = isset($item->title) ? $item->title : 'N/A';
            $item_id = isset($item->id) ? $item->id : 'N/A';
            $item_start_time = isset($item->start_time) ? $item->start_time : 'N/A';

            // 3. Log filtering logic for each event
            error_log("[DEBUG] filter_lineup_by_time: --- Filtering item: '{$item_title}' (ID: {$item_id}) ---");
            error_log("[DEBUG] filter_lineup_by_time: Item start_time: {$item_start_time}, is_active: " . ($item->is_active ? 'Yes' : 'No'));

            // Calculate end_time if not exists
            if (!isset($item->end_time) && isset($item->start_time) && isset($item->duration_minutes)) {
                $start_timestamp = strtotime($item->start_time);
                $end_timestamp = $start_timestamp + ($item->duration_minutes * 60);
                $item->end_time = date('H:i:s', $end_timestamp);
                error_log("[DEBUG] filter_lineup_by_time: Calculated end_time for '{$item_title}': {$item->end_time}");
            }

            // Show if item is active
            if ($item->is_active) {
                error_log("[DEBUG] filter_lineup_by_time: -> Decision: SHOW (item is active).");
                return true;
            }

            // If there's an active presentation, use its time as reference
            if ($active_presentation && isset($active_presentation->start_time)) {
                $reference_time = $active_presentation->start_time;
                $should_show = isset($item->start_time) && $item->start_time >= $reference_time;
                error_log("[DEBUG] filter_lineup_by_time: Active presentation mode. Reference time: {$reference_time}.");
                error_log("[DEBUG] filter_lineup_by_time: Comparing item start_time '{$item->start_time}' >= '{$reference_time}'.");
                error_log("[DEBUG] filter_lineup_by_time: -> Decision: " . ($should_show ? 'SHOW' : 'HIDE'));
                return $should_show;
            }

            // No active presentation - use real current time
            error_log("[DEBUG] filter_lineup_by_time: No active presentation mode. Using current time: {$current_time}.");

            // Hide presentations that have already ended
            if (isset($item->end_time) && $item->end_time < $current_time) {
                error_log("[DEBUG] filter_lineup_by_time: Item has ended (end_time: {$item->end_time} < current_time: {$current_time}).");
                error_log("[DEBUG] filter_lineup_by_time: -> Decision: HIDE.");
                return false;
            }

            // Show upcoming presentations (not started yet or still running)
            error_log("[DEBUG] filter_lineup_by_time: Item has not ended (or end_time is not set).");
            error_log("[DEBUG] filter_lineup_by_time: -> Decision: SHOW.");
            return true;
        });

        // 4. Log what array_filter returns
        error_log('[DEBUG] filter_lineup_by_time: --- Filtering complete ---');
        error_log('[DEBUG] filter_lineup_by_time: Final filtered lineup count: ' . count($filtered_lineup));
        if (count($filtered_lineup) > 0) {
            foreach ($filtered_lineup as $index => $item) {
                error_log("[DEBUG] filter_lineup_by_time: Final Item #{$index}: {$item->title} (start_time: {$item->start_time})");
            }
        } else {
            error_log("[DEBUG] filter_lineup_by_time: No items left after filtering.");
        }

        // Sort filtered lineup chronologically by start_time
        if (!empty($filtered_lineup)) {
            usort($filtered_lineup, function($a, $b) {
                return strcmp($a->start_time, $b->start_time);
            });
            error_log('[DEBUG] filter_lineup_by_time: Sorted lineup chronologically');
        }

        return $filtered_lineup;
    }

    /**
     * Render lineup HTML for SSE updates
     */
    private function render_lineup_html($lineup) {
        if (empty($lineup)) {
            return '<div class="text-center py-12"><svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><p class="text-gray-500 text-lg">Brak zaplanowanych prezentacji</p></div>';
        }

        if (WP_DEBUG) {
            error_log('CM_Shortcodes::render_lineup_html - Lineup count: ' . count($lineup));
        }

        $current_time = current_time('H:i:s');
        $output = '<div class="cm-lineup-list space-y-3 max-w-4xl mx-auto ">';

        foreach ($lineup as $item) {
            error_log("[DEBUG] render_lineup_html: Rendering item: {$item->title} (ID: {$item->id}, start_time: {$item->start_time})");

            // Calculate end_time if not exists
            if (!isset($item->end_time) && isset($item->start_time) && isset($item->duration_minutes)) {
                $start_timestamp = strtotime($item->start_time);
                $end_timestamp = $start_timestamp + ($item->duration_minutes * 60);
                $item->end_time = date('H:i:s', $end_timestamp);
                error_log("[DEBUG] render_lineup_html: Calculated end_time for {$item->title}: {$item->end_time}");
            }

            $is_past = isset($item->end_time) && $item->end_time < $current_time && !$item->is_active;
            $is_current = $item->is_active;

            $classes = array('cm-lineup-item', 'p-4', 'rounded-lg', 'border', 'flex', 'items-start', 'relative', 'transition-all');
            if ($is_past) {
                $classes[] = 'past';
                $classes[] = 'opacity-50';
                $classes[] = 'bg-gray-50';
                $classes[] = 'border-gray-200';
            }
            if ($is_current) {
                $classes[] = 'current';
                $classes[] = 'bg-blue-50';
                $classes[] = 'border-blue-300';
                $classes[] = 'shadow-md';
            } else if (!$is_past) {
                $classes[] = 'bg-white';
                $classes[] = 'border-gray-200';
                $classes[] = 'hover:border-blue-300';
                $classes[] = 'hover:shadow-sm';
            }

            $output .= '<div class="' . implode(' ', $classes) . '">';
            if ($is_current) {
                $output .= '<span class="cm-live-badge bg-blue-200 text-white text-xs font-bold uppercase px-2 py-1 rounded absolute top-2 right-2 animate-pulse">LIVE</span>';
            }
            $output .= '<div class="cm-lineup-time text-gray-700 font-bold p-3 rounded-md text-center min-w-[70px]">';
            $output .= '<div class="text-lg leading-tight">' . esc_html(date("H:i", strtotime($item->start_time))) . '</div>';
            if (isset($item->duration_minutes) && $item->duration_minutes) {
                $output .= '<div class="text-xs text-gray-500 mt-1">' . esc_html($item->duration_minutes) . ' min</div>';
            }
            $output .= '</div>';
            $output .= '<div class="cm-lineup-content flex-grow">';
            $output .= '<h4 class="font-bold text-lg text-gray-900 mb-1">' . esc_html($item->title) . '</h4>';
            if ($item->presenter) {
                $output .= '<p class="cm-presenter text-gray-600 text-sm mb-2"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>' . esc_html($item->presenter) . '</p>';
            }
            if ($item->description) {
                $output .= '<p class="cm-description text-gray-500 text-sm leading-relaxed">' . esc_html($item->description) . '</p>';
            }
            if (isset($item->event_type) && $item->event_type === 'quick') {
                $output .= '<span class="inline-block mt-2 text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Szybkie wydarzenie</span>';
            }
            $output .= '</div>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Default quiz template
     */
    private function get_default_quiz_template($quiz) {
        $questions = $quiz->get_questions();
        
        $output = '<div class="cm-quiz-container" data-quiz-id="' . $quiz->get_id() . '">';
        $output .= '<h3>' . esc_html($quiz->get_title()) . '</h3>';
        
        if ($quiz->get_description()) {
            $output .= '<div class="cm-quiz-description">' . wp_kses_post($quiz->get_description()) . '</div>';
        }
        
        $output .= '<form class="cm-quiz-form" data-quiz-id="' . $quiz->get_id() . '">';
        
        foreach ($questions as $question) {
            $answers = CM_Database::get_results('quiz_answers', array('question_id' => $question->id), 'sort_order ASC');
            
            $output .= '<div class="cm-question" data-question-id="' . $question->id . '">';
            $output .= '<h4>' . esc_html($question->question) . '</h4>';
            
            foreach ($answers as $answer) {
                $input_type = ($question->question_type === 'multiple') ? 'checkbox' : 'radio';
                $input_name = 'question_' . $question->id . ($question->question_type === 'multiple' ? '[]' : '');
                
                $output .= '<label>';
                $output .= '<input type="' . $input_type . '" name="' . $input_name . '" value="' . $answer->id . '">';
                $output .= esc_html($answer->answer_text);
                $output .= '</label><br>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '<button type="submit" class="cm-submit-quiz">Submit Quiz</button>';
        $output .= '</form>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Display quiz page shortcode
     * [cm_quiz_display]
     * 
     * If ?cm_quiz parameter exists: Shows quiz form for participants
     * If no parameter: Shows SSE-enabled dynamic quiz display (QR code or results)
     */
    public function display_quiz_page($atts) {
        error_log('CM_Shortcodes::display_quiz_page called - shortcode is working!');
        
        try {
            // Check if cm_quiz parameter is present (participant mode)
            if (isset($_GET['cm_quiz']) && !empty($_GET['cm_quiz'])) {
                return $this->render_quiz_for_participant($_GET['cm_quiz']);
            } 
            
            // No parameter = display mode - use SSE-enabled quiz display
            return $this->render_sse_quiz_display();
        } catch (Exception $e) {
            error_log('CM_Shortcodes::display_quiz_page error: ' . $e->getMessage());
            return '<div class="cm-error">
                        <h2>Wystąpił błąd</h2>
                        <p>Nie można załadować strony quizu. Spróbuj ponownie później.</p>
                        <p><small>Błąd: ' . esc_html($e->getMessage()) . '</small></p>
                    </div>';
        }
    }

    /**
     * Render SSE-enabled quiz display (integrates with quiz-display.php)
     */
    private function render_sse_quiz_display() {
        // Get current presentation and quiz for initial state
        $current_presentation = $this->get_current_active_presentation();
        $quiz = null;
        $questions = array();
        $initial_mode = 'qr';
        
        if ($current_presentation) {
            $quiz = $this->get_quiz_for_presentation($current_presentation->id);
            if ($quiz) {
                // Get questions for the quiz
                $questions = $quiz->get_questions();
                
                // Get initial mode from quiz state
                $quiz_state = CM_Quiz_State::get_state($quiz->get_id());
                if ($quiz_state) {
                    $initial_mode = $quiz_state->get_mode();
                }
            }
        }
        
        // If no quiz found, show waiting message
        if (!$quiz) {
            return '<div class="cm-quiz-container" style="text-align: center; padding: 60px 20px;">
                <h2 style="color: #666; margin-bottom: 20px;">Oczekiwanie na quiz</h2>
                <p style="color: #999;">Quiz zostanie wyświetlony, gdy prezentacja z quizem będzie aktywna.</p>
            </div>';
        }
        
        // Set template variables that quiz-display.php expects  
        CM_Public::set_template_vars(array(
            'quiz' => $quiz,
            'questions' => $questions
        ));
        
        ob_start();
        
        // Include the SSE-enabled quiz display template
        $template_path = CONFERENCE_MANAGER_PLUGIN_PATH . 'public/partials/quiz-display.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback if template doesn't exist
            echo $this->get_sse_fallback_display($quiz, $initial_mode);
        }
        
        return ob_get_clean();
    }

    /**
     * Fallback SSE display if template is missing
     */
    private function get_sse_fallback_display($quiz, $initial_mode) {
        $quiz_id = $quiz ? intval($quiz->get_id()) : 0;
        $event_id = 1; // Default event ID
        
        // Ensure we have valid integer values for JavaScript
        if ($quiz_id <= 0) {
            $quiz_id = 0;
        }
        if ($event_id <= 0) {
            $event_id = 1;
        }
        
        return '
        <div class="cm-quiz-container">
            <!-- Connection status indicator -->
            <div id="connection-status" class="connection-status-bar disconnected">
                <span id="connection-text">Łączenie...</span>
            </div>
            
            <!-- Main display container - content will be dynamically updated -->
            <div id="quiz-display-container" class="quiz-display-dynamic">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Ładowanie wyświetlacza quizu...</p>
                </div>
            </div>
            
            <!-- Fallback content for when JavaScript is disabled -->
            <noscript>
                <div class="no-js-fallback">
                    <h2>JavaScript wymagany</h2>
                    <p>Ta strona wymaga JavaScript do prawidłowego działania.</p>
                </div>
            </noscript>
        </div>
        
        <style>
        .connection-status-bar {
            position: fixed;
            top: 0;
            right: 20px;
            z-index: 1000;
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 0 0 8px 8px;
            transition: all 0.3s ease;
        }
        
        .connection-status-bar.connected {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .connection-status-bar.disconnected {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .quiz-display-dynamic {
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .loading-state {
            text-align: center;
            padding: 40px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .no-js-fallback {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
        }
        </style>
        
        <script type="text/javascript" src="' . CONFERENCE_MANAGER_PLUGIN_URL . 'public/js/quiz-live-updates.js"></script>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Initializing SSE quiz display...");
            
            // Initialize live updates
            const liveUpdates = new QuizLiveUpdates(' . intval($quiz_id) . ', ' . intval($event_id) . ', {
                enableSSE: true,
                enablePollingFallback: true,
                pollFallbackInterval: 5000
            });
            
            // Initialize display controller
            const displayController = new QuizDisplayController("quiz-display-container", liveUpdates);
            
            // Set initial mode
            const initialMode = "' . esc_attr($initial_mode) . '";
            if (initialMode && initialMode !== "") {
                displayController.handleModeChange({
                    oldMode: "qr",
                    newMode: initialMode,
                    autoSwitched: false
                });
            }
        });
        </script>';
    }

    /**
     * Render quiz form for participant (mobile view)
     */
    private function render_quiz_for_participant($quiz_id) {
        try {
            $quiz = new CM_Quiz($quiz_id);
            
            if (!$quiz->get_id()) {
                return '<div class="cm-error">Quiz nie został znaleziony.</div>';
            }
            
            if (!$quiz->get_is_active()) {
                return '<div class="cm-error">Ten quiz nie jest obecnie aktywny.</div>';
            }

            $questions = $quiz->get_questions();

            // Debug info
            if (WP_DEBUG) {
                error_log('CM_Shortcodes::render_quiz_for_participant - Questions count: ' . count($questions));
                error_log('CM_Shortcodes::render_quiz_for_participant - Quiz ID: ' . $quiz->get_id());
            }
        } catch (Exception $e) {
            error_log('CM_Shortcodes::render_quiz_for_participant error: ' . $e->getMessage());
            return '<div class="cm-error">
                        <h2>Błąd ładowania quizu</h2>
                        <p>' . esc_html($e->getMessage()) . '</p>
                    </div>';
        }
        
        ob_start();
        
        echo '<div class="cm-quiz-participant-container">';
        echo '<div class="cm-quiz-header-mobile">';
        echo '<h2>' . esc_html($quiz->get_title()) . '</h2>';
        
        if ($quiz->get_description()) {
            echo '<div class="cm-quiz-description">' . wp_kses_post($quiz->get_description()) . '</div>';
        }
        
        // Timer if quiz has end time
        if ($quiz->get_end_time()) {
            echo '<div class="cm-quiz-timer-mobile" data-end-time="' . esc_attr($quiz->get_end_time()) . '">';
            echo '<span class="cm-timer-label">Czas pozostały: </span>';
            echo '<span class="cm-timer-value" id="mobile-timer">Obliczanie...</span>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Quiz form
        echo '<form class="cm-quiz-form-mobile" method="post" data-quiz-id="' . esc_attr($quiz_id) . '">';
        echo '<input type="hidden" name="action" value="cm_submit_quiz_response">';
        echo '<input type="hidden" name="quiz_id" value="' . esc_attr($quiz_id) . '">';
        wp_nonce_field('cm_quiz_submission', 'quiz_nonce');
        
        if (!empty($questions)) {
            foreach ($questions as $index => $question) {
                echo '<div class="cm-question-mobile" data-question-id="' . esc_attr($question->id) . '">';
                echo '<div class="cm-question-header-mobile">';
                echo '<h3>' . esc_html($question->question) . '</h3>';
                echo '<span class="cm-question-number">' . ($index + 1) . '/' . count($questions) . '</span>';
                echo '</div>';
                
                $answers = CM_Database::get_results('quiz_answers', array('question_id' => $question->id), 'sort_order ASC');

                // Debug: Log answer count for troubleshooting
                if (WP_DEBUG) {
                    error_log('CM_Shortcodes::render_quiz_for_participant - Question ID: ' . $question->id . ', Question type: ' . $question->question_type . ', Answers count: ' . count($answers));
                }

                if (!empty($answers)) {
                    echo '<div class="cm-answers-mobile">';
                    foreach ($answers as $answer) {
                        $input_type = ($question->question_type === 'multiple') ? 'checkbox' : 'radio';
                        $input_name = ($question->question_type === 'multiple') ? 'question_' . $question->id . '[]' : 'question_' . $question->id;
                        
                        echo '<label class="cm-answer-option-mobile">';
                        echo '<input type="' . $input_type . '" name="' . $input_name . '" value="' . $answer->id . '">';
                        echo '<span>' . esc_html($answer->answer_text) . '</span>';
                        echo '</label>';
                    }
                    echo '</div>';
                } elseif ($question->question_type === 'text') {
                    echo '<div class="cm-text-answer-mobile">';
                    echo '<textarea name="question_' . $question->id . '" placeholder="Wpisz swoją odpowiedź..." rows="3"></textarea>';
                    echo '</div>';
                } else {
                    // Debug: Show when no answers are found
                    echo '<div class="cm-no-answers-debug">';
                    echo '<p style="color: #dc3545; font-size: 0.9rem;">Debug: Brak odpowiedzi dla tego pytania (ID: ' . $question->id . ', Typ: ' . $question->question_type . ')</p>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
            // Add participant ID display (automatically assigned)
            echo '<div class="cm-question-mobile cm-participant-id-field">';
            echo '<div class="cm-question-header-mobile">';
            echo '<h3>Twój identyfikator uczestnika</h3>';
            echo '<span class="cm-question-number">Automatyczny</span>';
            echo '</div>';
            echo '<div class="cm-participant-id-display">';
            echo '<div id="participant-id-placeholder" style="padding: 15px; background: #f0f8ff; border: 2px solid #4285f4; border-radius: 8px; text-align: center; font-family: monospace; font-size: 18px; font-weight: bold; color: #1976d2;">Ładowanie...</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="cm-quiz-actions-mobile">';
            echo '<button type="submit" class="cm-submit-quiz-mobile" id="submit-quiz-btn">Prześlij odpowiedzi</button>';
            echo '</div>';
        } else {
            echo '<div class="cm-no-questions-mobile">';
            echo '<h3>Brak pytań w tym quizie</h3>';
            echo '<p>Quiz nie zawiera jeszcze żadnych pytań. Skontaktuj się z organizatorem.</p>';
            if (WP_DEBUG) {
                echo '<p><small>Debug: Quiz ID: ' . $quiz->get_id() . ', Questions count: ' . count($questions) . '</small></p>';
            }
            echo '</div>';
        }
        
        echo '</form>';
        echo '</div>';
        
        // Add JavaScript BEFORE CSS to ensure it loads
        ?>
        <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Quiz JavaScript loaded");

            const quizForm = document.querySelector(".cm-quiz-form-mobile");
            const participantIdPlaceholder = document.getElementById("participant-id-placeholder");
            console.log("Quiz form found:", quizForm);

            // Automatically register participant on page load
            registerParticipant();

            function registerParticipant() {
                const quizId = <?php echo intval($quiz_id); ?>;
                const storedId = localStorage.getItem('cm_user_identifier_' + quizId);

                if (storedId) {
                    // User already registered
                    displayParticipantId(storedId);
                    return;
                }

                // Register new participant
                const formData = new FormData();
                formData.append('action', 'cm_register_participant');
                formData.append('quiz_id', quizId);
                formData.append('nonce', '<?php echo wp_create_nonce('cm_public_nonce'); ?>');

                fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const participantId = data.data.participant_id;
                        localStorage.setItem('cm_user_identifier_' + quizId, participantId);
                        displayParticipantId(participantId);
                    } else {
                        participantIdPlaceholder.innerHTML = '<span style="color: #d32f2f;">Błąd rejestracji: ' + (data.data || 'Nieznany błąd') + '</span>';
                    }
                })
                .catch(error => {
                    console.error("Registration error:", error);
                    participantIdPlaceholder.innerHTML = '<span style="color: #d32f2f;">Błąd połączenia</span>';
                });
            }

            function displayParticipantId(participantId) {
                participantIdPlaceholder.innerHTML = participantId;
                participantIdPlaceholder.style.color = '#1976d2';
            }

            if (quizForm) {
                console.log("Attaching event listener to quiz form");
                
                quizForm.addEventListener("submit", function(e) {
                    console.log("Form submit event triggered");
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const submitButton = document.getElementById("submit-quiz-btn");
                    if (submitButton) {
                        const originalText = submitButton.textContent;
                        submitButton.textContent = "Przesyłanie...";
                        submitButton.disabled = true;
                        
                        // Get user identifier
                        const quizId = <?php echo intval($quiz_id); ?>;
                        const userIdentifier = localStorage.getItem('cm_user_identifier_' + quizId);

                        if (!userIdentifier) {
                            alert("Błąd: Nie można znaleźć identyfikatora uczestnika. Odśwież stronę.");
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                            return;
                        }

                        // Collect quiz responses
                        const responses = {};
                        const formData = new FormData(quizForm);

                        for (const [key, value] of formData.entries()) {
                            if (key.startsWith('question_')) {
                                const questionId = key.replace('question_', '');
                                if (!responses[questionId]) {
                                    responses[questionId] = [];
                                }
                                responses[questionId].push(value);
                            }
                        }

                        // Convert single-item arrays to single values for single-choice questions
                        for (const questionId in responses) {
                            if (responses[questionId].length === 1) {
                                responses[questionId] = responses[questionId][0];
                            }
                        }

                        // Prepare submission data
                        const submitData = new FormData();
                        submitData.append('action', 'cm_submit_quiz');
                        submitData.append('quiz_id', quizId);
                        submitData.append('user_identifier', userIdentifier);
                        submitData.append('responses', JSON.stringify(responses));
                        submitData.append('nonce', '<?php echo wp_create_nonce('cm_public_nonce'); ?>');

                        // Submit via AJAX
                        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                            method: "POST",
                            body: submitData
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log("AJAX response:", data);
                            if (data.success) {
                                quizForm.innerHTML = "<div style='text-align: center; padding: 40px; background: #d4edda; color: #155724; border-radius: 8px; margin: 20px 0;'><h3>Dziękujemy!</h3><p>Twoje odpowiedzi zostały zapisane.</p><p><strong>Twój identyfikator: " + userIdentifier + "</strong></p><p>Zapamiętaj ten identyfikator do sprawdzenia wyników!</p></div>";
                            } else {
                                alert("Błąd: " + (data.data || "Nieznany błąd"));
                                submitButton.textContent = originalText;
                                submitButton.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error("AJAX error:", error);
                            alert("Błąd sieci: " + error.message);
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        });
                    }
                    
                    return false;
                });
                
                console.log("Event listener attached successfully");
            } else {
                console.log("Quiz form NOT found");
                console.log("All forms on page:", document.querySelectorAll("form"));
            }
        });
        </script>
        <?php
        
        // Add mobile-optimized CSS
        echo '<style>
        .cm-quiz-participant-container {
            max-width: 100%;
            padding: 15px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .cm-quiz-header-mobile {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .cm-quiz-header-mobile h2 {
            margin: 0 0 10px 0;
            font-size: 1.5rem;
        }
        
        .cm-quiz-timer-mobile {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 15px;
            font-weight: 600;
        }
        
        .cm-question-mobile {
            background: white;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: block !important;
            visibility: visible !important;
        }

        .cm-question-mobile h3 {
            color: #2c3e50 !important;
            font-size: 1.2rem !important;
            margin-bottom: 15px !important;
            font-weight: 600 !important;
        }
        
        .cm-question-header-mobile {
            margin-bottom: 15px;
        }
        
        .cm-question-header-mobile h3 {
            margin: 0 0 10px 0;
            font-size: 1.1rem;
            color: #2c3e50;
        }
        
        .cm-question-number {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .cm-answers-mobile {
            display: grid;
            gap: 10px;
        }
        
        .cm-answer-option-mobile {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .cm-answer-option-mobile:hover {
            background: #e9ecef;
            border-color: #667eea;
        }
        
        .cm-answer-option-mobile input {
            margin-right: 10px;
            transform: scale(1.1);
        }
        
        .cm-text-answer-mobile textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
        }
        
        .cm-quiz-actions-mobile {
            text-align: center;
            margin-top: 30px;
        }
        
        .cm-submit-quiz-mobile {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 25px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        
        .cm-submit-quiz-mobile:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .cm-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        .cm-no-questions-mobile {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 12px;
            margin: 20px 0;
        }

        .cm-no-questions-mobile h3 {
            color: #495057 !important;
            margin-bottom: 10px !important;
        }

        .cm-no-questions-mobile p {
            margin: 10px 0 !important;
        }
        </style>';
        
        return ob_get_clean();
    }

    /**
     * Render QR code of current presentation (display screen view)
     */
    private function render_current_presentation_qr() {
        $current_presentation = $this->get_current_active_presentation();
        
        if (!$current_presentation) {
            // Check if we should show recent quiz results instead
            $recent_quiz_results = $this->get_recent_completed_quiz_results();
            
            if ($recent_quiz_results) {
                return $this->render_quiz_results_summary($recent_quiz_results);
            }
            
            // Debug info for troubleshooting
            $active_events = CM_Event::get_all('active');
            $debug_info = '';
            
            if (empty($active_events)) {
                $debug_info = '<p><small>Debug: Brak aktywnych wydarzeń.</small></p>';
            } else {
                $debug_info = '<p><small>Debug: Znaleziono ' . count($active_events) . ' aktywnych wydarzeń, ale żadna prezentacja nie jest uruchomiona.</small></p>';
            }
            
            return '<div class="cm-no-active-presentation">
                        <h2>Brak aktywnej prezentacji</h2>
                        <p>Obecnie nie ma aktywnej prezentacji z quizem.</p>
                        ' . $debug_info . '
                    </div>';
        }
        
        // Get quiz associated with this presentation
        $quiz = $this->get_quiz_for_presentation($current_presentation->id);
        
        if (!$quiz) {
            return '<div class="cm-no-quiz-presentation">
                        <h2>' . esc_html($current_presentation->title) . '</h2>
                        <p>Ta prezentacja nie ma przypisanego quizu.</p>
                    </div>';
        }
        
        // Generate QR code for this quiz
        $quiz_url = home_url('/quiz/?cm_quiz=' . $quiz->get_id());
        $qr_result = CM_QR_Generator::generate_quiz_qr($quiz->get_id());
        
        if (is_wp_error($qr_result)) {
            return '<div class="cm-qr-error">Błąd generowania kodu QR: ' . $qr_result->get_error_message() . '</div>';
        }
        
        ob_start();
        
        echo '<div class="cm-qr-display-container">';
        echo '<div class="cm-qr-header">';
        echo '<h1>' . esc_html($current_presentation->title) . '</h1>';
        echo '<h2>' . esc_html($quiz->get_title()) . '</h2>';
        echo '</div>';
        
        echo '<div class="cm-qr-code-section">';
        echo '<div class="cm-qr-image">';
        echo '<img src="' . esc_url($qr_result['url']) . '" alt="QR Code dla quizu" />';
        echo '</div>';
        echo '<div class="cm-qr-instructions">';
        echo '<h3>Zeskanuj kod QR</h3>';
        echo '<p>aby wziąć udział w quizie</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="cm-qr-url">';
        echo '<p>Lub wejdź na: <strong>' . esc_html($quiz_url) . '</strong></p>';
        echo '</div>';
        
        echo '</div>';
        
        // Add display-optimized CSS for projector/screen
        echo '<style>
        .cm-qr-display-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .cm-qr-header h1 {
            font-size: 3rem;
            margin: 0 0 20px 0;
            font-weight: 700;
        }
        
        .cm-qr-header h2 {
            font-size: 2rem;
            margin: 0 0 60px 0;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .cm-qr-code-section {
            margin: 40px 0;
        }
        
        .cm-qr-image {
            background: white;
            padding: 30px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .cm-qr-image img {
            max-width: 300px;
            height: auto;
            display: block;
        }
        
        .cm-qr-instructions h3 {
            font-size: 2.5rem;
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        
        .cm-qr-instructions p {
            font-size: 1.5rem;
            margin: 0;
            opacity: 0.9;
        }
        
        .cm-qr-url {
            margin-top: 40px;
            font-size: 1.2rem;
            opacity: 0.8;
        }
        
        .cm-no-active-presentation,
        .cm-no-quiz-presentation {
            text-align: center;
            padding: 60px 40px;
            background: #f8f9fa;
            border-radius: 12px;
            color: #6c757d;
        }
        
        .cm-no-active-presentation h2,
        .cm-no-quiz-presentation h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .cm-qr-error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        </style>';
        
        // Add postMessage handler for dynamic switching
        $this->add_postmessage_handler();
        
        return ob_get_clean();
    }

    /**
     * Get currently active presentation from any active event
     */
    private function get_current_active_presentation() {
        try {
            // First, get all active events
            $active_events = CM_Event::get_all('active');
            
            if (empty($active_events)) {
                return null;
            }
            
            // Look for active presentation in each active event
            foreach ($active_events as $event) {
                $presentation = CM_Lineup::get_active_presentation($event->id);
                if ($presentation) {
                    return $presentation;
                }
            }
            
            return null;
        } catch (Exception $e) {
            error_log('CM_Shortcodes::get_current_active_presentation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get quiz associated with a presentation
     */
    private function get_quiz_for_presentation($presentation_id) {
        // Get presentation details
        $presentation = CM_Database::get_row('lineup', array('id' => $presentation_id));
        if (!$presentation) {
            return null;
        }
        
        // Check if presentation has direct quiz assignment
        if (!empty($presentation->quiz_id)) {
            $quiz = new CM_Quiz($presentation->quiz_id);
            
            // Return quiz if it exists and is active
            if ($quiz->get_id() && $quiz->get_is_active()) {
                return $quiz;
            }
        }
        
        return null;
    }

    /**
     * Get recent completed quiz results (within last 30 minutes)
     */
    private function get_recent_completed_quiz_results() {
        try {
            // Get recently completed quizzes from active events
            $active_events = CM_Event::get_all('active');
            
            if (empty($active_events)) {
                return null;
            }

            // Look for recently ended presentations with quizzes
            foreach ($active_events as $event) {
                $recent_presentations = CM_Database::get_results('lineup', array(
                    'event_id' => $event->id,
                    'is_active' => 0  // Recently deactivated
                ), 'updated_at DESC', 5); // Get last 5 deactivated presentations

                foreach ($recent_presentations as $presentation) {
                    if (!empty($presentation->quiz_id)) {
                        $quiz = new CM_Quiz($presentation->quiz_id);
                        if ($quiz->get_id()) {
                            // Check if quiz has responses from last 60 minutes
                            global $wpdb;
                            $responses_table = CM_Database::get_table_name('user_responses');
                            
                            $recent_responses = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(DISTINCT user_identifier) 
                                 FROM {$responses_table} 
                                 WHERE quiz_id = %d 
                                 AND submitted_at > DATE_SUB(NOW(), INTERVAL 60 MINUTE)",
                                $quiz->get_id()
                            ));

                            if ($recent_responses > 0) {
                                return array(
                                    'quiz' => $quiz,
                                    'presentation' => $presentation,
                                    'participant_count' => $recent_responses
                                );
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('CM_Shortcodes::get_recent_completed_quiz_results error: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Render quiz results summary for display screen
     */
    private function render_quiz_results_summary($quiz_data) {
        $quiz = $quiz_data['quiz'];
        $presentation = $quiz_data['presentation'];
        $participant_count = $quiz_data['participant_count'];
        
        ob_start();
        
        echo '<div class="cm-quiz-results-display">';
        echo '<div class="cm-results-header-display">';
        echo '<h1>Wyniki Quizu</h1>';
        echo '<h2>' . esc_html($quiz->get_title()) . '</h2>';
        echo '<p class="cm-presentation-info">Prezentacja: ' . esc_html($presentation->title) . '</p>';
        echo '</div>';
        
        // Load and display basic statistics
        echo '<div class="cm-results-stats-display" id="quiz-stats">';
        echo '<div class="cm-stat-display">';
        echo '<div class="cm-stat-number">' . $participant_count . '</div>';
        echo '<div class="cm-stat-label">Uczestników</div>';
        echo '</div>';
        echo '<div class="cm-loading-detailed">Ładowanie szczegółowych wyników...</div>';
        echo '</div>';
        
        echo '<div class="cm-detailed-results" id="detailed-results" style="display: none;">';
        echo '</div>';
        
        echo '</div>';
        
        // Add CSS for display screen
        echo '<style>
        .cm-quiz-results-display {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 20px;
            min-height: 80vh;
        }
        
        .cm-results-header-display h1 {
            font-size: 4rem;
            margin: 0 0 20px 0;
            font-weight: 700;
        }
        
        .cm-results-header-display h2 {
            font-size: 2.5rem;
            margin: 0 0 15px 0;
            opacity: 0.9;
        }
        
        .cm-presentation-info {
            font-size: 1.5rem;
            opacity: 0.8;
            margin-bottom: 40px;
        }
        
        .cm-results-stats-display {
            margin: 40px 0;
        }
        
        .cm-stat-display {
            background: rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 20px;
            display: inline-block;
            margin: 20px;
            min-width: 200px;
        }
        
        .cm-stat-number {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .cm-stat-label {
            font-size: 1.5rem;
            opacity: 0.9;
        }
        
        .cm-loading-detailed {
            font-size: 1.2rem;
            opacity: 0.8;
            margin-top: 20px;
        }
        </style>';
        
        // Add JavaScript to load detailed results
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                loadDetailedResults(' . $quiz->get_id() . ');
            }, 2000);
        });
        
        function loadDetailedResults(quizId) {
            fetch("' . admin_url('admin-ajax.php') . '", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "cm_get_quiz_results_public",
                    quiz_id: quizId,
                    nonce: "' . wp_create_nonce('cm_public_nonce') . '"
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayDetailedResults(data.data);
                }
            })
            .catch(error => {
                console.error("Error loading results:", error);
            });
        }
        
        function displayDetailedResults(data) {
            const statsDiv = document.getElementById("quiz-stats");
            const detailedDiv = document.getElementById("detailed-results");
            
            // Update stats
            statsDiv.innerHTML = `
                <div class="cm-stat-display">
                    <div class="cm-stat-number">${data.total_participants}</div>
                    <div class="cm-stat-label">Uczestników</div>
                </div>
                <div class="cm-stat-display">
                    <div class="cm-stat-number">${data.avg_score}%</div>
                    <div class="cm-stat-label">Średni wynik</div>
                </div>
            `;
            
            // Show detailed results if needed
            detailedDiv.style.display = "block";
        }
        </script>';
        
        return ob_get_clean();
    }

    /**
     * Render quiz results in forced mode (triggered from admin panel)
     */
    private function render_forced_quiz_results($quiz_id) {
        $quiz = new CM_Quiz($quiz_id);
        
        if (!$quiz->get_id()) {
            return '<div class="cm-error">Quiz nie został znaleziony.</div>';
        }

        // Get presentation info if available
        $presentation = null;
        $lineup_id = get_transient('cm_quiz_results_lineup_' . $quiz_id);
        if ($lineup_id) {
            $presentation = CM_Database::get_row('lineup', array('id' => $lineup_id));
        }

        ob_start();
        
        echo '<div class="cm-forced-quiz-results">';
        echo '<div class="cm-results-header-forced">';
        echo '<h1>Wyniki Quizu</h1>';
        echo '<h2>' . esc_html($quiz->get_title()) . '</h2>';
        if ($presentation) {
            echo '<p class="cm-presentation-info">Prezentacja: ' . esc_html($presentation->title) . '</p>';
        }
        echo '</div>';
        
        // Container for detailed results
        echo '<div class="cm-detailed-results-forced" id="detailed-results-forced">';
        echo '<div class="cm-loading-results">';
        echo '<div class="cm-spinner"></div>';
        echo '<p>Ładowanie szczegółowych wyników...</p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // Add CSS optimized for display screen
        echo '<style>
        .cm-forced-quiz-results {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            min-height: 90vh;
        }
        
        .cm-results-header-forced h1 {
            font-size: 4rem;
            text-align: center;
            margin: 0 0 30px 0;
            font-weight: 700;
        }
        
        .cm-results-header-forced h2 {
            font-size: 2.5rem;
            text-align: center;
            margin: 0 0 20px 0;
            opacity: 0.9;
        }
        
        .cm-presentation-info {
            font-size: 1.5rem;
            text-align: center;
            opacity: 0.8;
            margin-bottom: 40px;
        }
        
        .cm-loading-results {
            text-align: center;
            padding: 60px 20px;
        }
        
        .cm-spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .cm-detailed-results-forced {
            margin-top: 40px;
        }
        
        /* Style the loaded results content */
        .cm-stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .cm-stat-card {
            background: rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        .cm-stat-value {
            font-size: 3rem;
            font-weight: 700;
            display: block;
            margin-bottom: 10px;
        }
        
        .cm-stat-label {
            font-size: 1.2rem;
            opacity: 0.9;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .participants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .participant-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
        }
        
        .participant-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .participant-name {
            font-size: .8rem;
            font-weight: 600;
            margin: 0;
        }
        
        .participant-score .score-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .participant-answers {
            display: grid;
            gap: 10px;
        }
        
        .answer-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .answer-item.correct {
            background: rgba(46, 213, 115, 0.3);
        }
        
        .answer-item.incorrect {
            background: rgba(231, 76, 60, 0.3);
        }
        
        .answer-icon {
            margin-right: 12px;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .question-text {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .user-answer {
            opacity: 0.8;
        }
        </style>';
        
        // Add JavaScript to load detailed results
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            loadDetailedResults(' . $quiz_id . ');
        });
        
        function loadDetailedResults(quizId) {
            // Check if cm_public_ajax is available
            const ajaxUrl = (typeof cm_public_ajax !== "undefined" && cm_public_ajax.ajax_url) 
                ? cm_public_ajax.ajax_url 
                : "' . admin_url('admin-ajax.php') . '";
            const nonce = (typeof cm_public_ajax !== "undefined" && cm_public_ajax.nonce)
                ? cm_public_ajax.nonce 
                : "' . wp_create_nonce('cm_public_nonce') . '";
            
            fetch(ajaxUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "cm_get_quiz_results_detailed",
                    quiz_id: quizId,
                    nonce: nonce,
                    _nocache: Date.now()
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(text => {
                console.log("Raw response (first 500 chars):", text.substring(0, 500)); // Debug log
                console.log("Response length:", text.length);
                console.log("Response starts with:", text.charAt(0), text.charCodeAt(0));
                
                // Check if response starts with HTML
                if (text.trim().startsWith('<')) {
                    console.error("Server returned HTML instead of JSON:", text.substring(0, 200));
                    throw new Error("Serwer zwrócił HTML zamiast JSON - sprawdź błędy PHP");
                }
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error("JSON parse error:", e);
                    console.error("Full response:", text);
                    throw new Error("Serwer zwrócił nieprawidłową odpowiedź JSON");
                }
                
                if (data && data.success) {
                    displayDetailedResults(data.data);
                } else {
                    showError("Błąd ładowania wyników: " + (data.data || data.message || "Nieznany błąd"));
                }
            })
            .catch(error => {
                console.error("Error loading results:", error);
                showError("Błąd ładowania wyników: " + error.message);
            });
        }
        
        function displayDetailedResults(data) {
            const container = document.getElementById("detailed-results-forced");
            
            let html = "";
            
            // Overview stats - use data.stats which matches AJAX response
            if (data.stats) {
                const totalQuestions = data.questions ? data.questions.length : 0;
                html += `
                    <div class="cm-stats-overview">
                        <div class="cm-stat-card">
                            <span class="cm-stat-value">${data.stats.unique_participants || 0}</span>
                            <span class="cm-stat-label">Uczestników</span>
                        </div>
                        <div class="cm-stat-card">
                            <span class="cm-stat-value">${totalQuestions}</span>
                            <span class="cm-stat-label">Pytań</span>
                        </div>
                        <div class="cm-stat-card">
                            <span class="cm-stat-value">${data.stats.average_score || 0}%</span>
                            <span class="cm-stat-label">Średni wynik</span>
                        </div>
                    </div>
                `;
            }
            
            // Participants
            if (data.participants && data.participants.length > 0) {
                html += `<div class="participants-grid">`;
                
                data.participants.forEach((participant, index) => {
                    let rankIcon = "#" + (index + 1);
                    if (index === 0) rankIcon = "🥇";
                    else if (index === 1) rankIcon = "🥈";
                    else if (index === 2) rankIcon = "🥉";
                    
                    html += `
                        <div class="participant-card">
                            <div class="participant-header">
                                <div class="participant-rank">${rankIcon}</div>
                                <div class="participant-info">
                                    <h4 class="participant-name">${participant.participant_name}</h4>
                                </div>
                                <div class="participant-score">
                                    <div class="score-value">${participant.score_percentage}%</div>
                                    <div class="score-details">${participant.correct_answers}/${participant.total_possible_answers} poprawnych</div>
                                </div>
                            </div>
                            <div class="participant-answers">
                    `;
                    
                    if (participant.questions && participant.questions.length > 0) {
                        participant.questions.forEach((question, qIndex) => {
                            const isCorrect = question.is_correct;
                            const correctClass = isCorrect === true ? "correct" : "incorrect";
                            const icon = isCorrect === true ? "✓" : "✗";
                            
                            html += `
                                <div class="answer-item ${correctClass}">
                                    <div class="answer-icon">${icon}</div>
                                    <div class="answer-content">
                                        <div class="question-text">P${qIndex + 1}: ${question.question}</div>
                                        <div class="user-answer">${question.user_answer || "Brak odpowiedzi"}</div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    html += `
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            container.innerHTML = html;
        }
        
        function showError(message) {
            const container = document.getElementById("detailed-results-forced");
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ff6b6b;">
                    <h3>Błąd ładowania</h3>
                    <p>${message}</p>
                </div>
            `;
        }
        </script>';
        
        return ob_get_clean();
    }

    /**
     * Add JavaScript for polling display mode changes
     */
    private function add_postmessage_handler() {
        error_log('add_postmessage_handler() called - starting JavaScript output');
        echo '<!-- DEBUG: Quiz polling JavaScript starting -->';
        
        // Simple test JavaScript first
        echo '<script>console.log("=== SIMPLE TEST WORKS ===");</script>';
        
        error_log('add_postmessage_handler() - Simple test script added');
        
        // Now a simplified script
        echo '<script type="text/javascript">
        console.log("=== SECOND SCRIPT LOADING ===");
        console.log("Testing second script execution");
        
        // Test simple AJAX call
        setTimeout(function() {
            console.log("Making test AJAX call...");
            fetch("' . admin_url('admin-ajax.php') . '", {
                method: "POST", 
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "action=cm_get_quiz_display_mode&quiz_id=current&nonce=' . wp_create_nonce('cm_public_nonce') . '"
            })
            .then(response => response.json())
            .then(data => console.log("Test AJAX result:", data))
            .catch(error => console.error("Test AJAX error:", error));
        }, 2000);
        
        // Start polling when page loads
        document.addEventListener("DOMContentLoaded", function() {
            console.log("=== QUIZ POLLING SYSTEM LOADED ===");
            console.log("Starting quiz display mode polling...");
            // Always start polling - the backend will find the current quiz
            startPollingForModeChanges("current");
        });
        
        // Also start immediately in case DOM is already loaded
        console.log("=== QUIZ POLLING SCRIPT EXECUTING ===");
        if (document.readyState === "loading") {
            console.log("DOM is still loading, waiting...");
        } else {
            console.log("DOM already loaded, starting polling immediately...");
            startPollingForModeChanges("current");
        }
        
        function startPollingForModeChanges(quizId) {
            // Check immediately
            checkDisplayMode(quizId);
            
            // Then check every 3 seconds
            pollInterval = setInterval(function() {
                checkDisplayMode(quizId);
            }, 3000);
        }
        
        function checkDisplayMode(quizId) {
            const ajaxUrl = "' . admin_url('admin-ajax.php') . '";
            const nonce = "' . wp_create_nonce('cm_public_nonce') . '";
            
            console.log("Polling for mode changes... current mode:", currentMode);
            
            fetch(ajaxUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "cm_get_quiz_display_mode",
                    quiz_id: quizId || "current",
                    nonce: nonce
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Poll response:", data);
                
                if (data.success) {
                    if (data.data.mode !== currentMode) {
                        console.log("Mode changed from", currentMode, "to", data.data.mode);
                        currentMode = data.data.mode;
                        
                        if (currentMode === "results" && data.data.quiz_id) {
                            console.log("Switching to results view for quiz", data.data.quiz_id);
                            showQuizResults(data.data.quiz_id);
                        } else if (currentMode === "qr") {
                            console.log("Switching to QR view");
                            showQuizQR();
                        }
                    }
                } else {
                    console.warn("Poll failed:", data.data || data.message);
                }
            })
            .catch(error => {
                console.error("Error checking display mode:", error);
            });
        }
        
        function handleQuizDisplayCommand(action, quizId) {
            if (action === "show_results") {
                showQuizResults(quizId);
            } else if (action === "show_qr") {
                showQuizQR();
            }
        }
        
        function showQuizResults(quizId) {
            // Replace page content with results
            document.body.innerHTML = `
                <div class="cm-forced-quiz-results">
                    <div class="cm-results-header-forced">
                        <h1>Wyniki Quizu</h1>
                        <h2 id="quiz-title">Ładowanie...</h2>
                    </div>
                    <div class="cm-detailed-results-forced" id="detailed-results-forced">
                        <div class="cm-loading-results">
                            <div class="cm-spinner"></div>
                            <p>Ładowanie szczegółowych wyników...</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Add styles
            const style = document.createElement("style");
            style.textContent = `
                .cm-forced-quiz-results {
                    max-width: 1400px;
                    margin: 0 auto;
                    padding: 40px;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border-radius: 20px;
                    min-height: 90vh;
                }
                .cm-results-header-forced h1 {
                    font-size: 4rem;
                    text-align: center;
                    margin: 0 0 30px 0;
                    font-weight: 700;
                }
                .cm-results-header-forced h2 {
                    font-size: 2.5rem;
                    text-align: center;
                    margin: 0 0 20px 0;
                    opacity: 0.9;
                }
                .cm-loading-results {
                    text-align: center;
                    padding: 60px 20px;
                }
                .cm-spinner {
                    border: 4px solid rgba(255,255,255,0.3);
                    border-top: 4px solid white;
                    border-radius: 50%;
                    width: 50px;
                    height: 50px;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 20px;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                .participants-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                    gap: 25px;
                    margin-top: 30px;
                }
                .participant-card {
                    background: rgba(255, 255, 255, 0.15);
                    border-radius: 15px;
                    padding: 25px;
                    backdrop-filter: blur(10px);
                }
            `;
            document.head.appendChild(style);
            
            // Load quiz results
            console.log("About to load quiz results for quiz ID:", quizId);
            loadQuizResultsDynamic(quizId);
        }
        
        function showQuizQR() {
            // Reload the page to show QR code
            window.location.reload();
        }
        
        function loadQuizResultsDynamic(quizId) {
            console.log("loadQuizResultsDynamic called with quiz ID:", quizId);
            
            // Check if cm_public_ajax is available
            const ajaxUrl = (typeof cm_public_ajax !== "undefined" && cm_public_ajax.ajax_url) 
                ? cm_public_ajax.ajax_url 
                : "' . admin_url('admin-ajax.php') . '";
            const nonce = (typeof cm_public_ajax !== "undefined" && cm_public_ajax.nonce)
                ? cm_public_ajax.nonce 
                : "' . wp_create_nonce('cm_public_nonce') . '";
            
            console.log("Using AJAX URL:", ajaxUrl);
            console.log("Using nonce:", nonce);
            
            fetch(ajaxUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "cm_get_quiz_results_detailed",
                    quiz_id: quizId,
                    nonce: nonce,
                    _nocache: Date.now()
                })
            })
            .then(response => {
                // First check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Get text first to debug
                return response.text();
            })
            .then(text => {
                console.log("Dynamic - Raw response (first 500 chars):", text.substring(0, 500)); // Debug log
                console.log("Dynamic - Response length:", text.length);
                console.log("Dynamic - Response starts with:", text.charAt(0), text.charCodeAt(0));
                
                // Check if response starts with HTML
                if (text.trim().startsWith('<')) {
                    console.error("Dynamic - Server returned HTML instead of JSON:", text.substring(0, 200));
                    throw new Error("Serwer zwrócił HTML zamiast JSON - sprawdź błędy PHP");
                }
                
                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error("Dynamic - JSON parse error:", e);
                    console.error("Dynamic - Full response:", text);
                    throw new Error("Serwer zwrócił nieprawidłową odpowiedź (nie JSON)");
                }
                
                if (data && data.success) {
                    displayQuizResultsDynamic(data.data);
                } else {
                    showResultsError("Błąd ładowania wyników: " + (data.data || data.message || "Nieznany błąd"));
                }
            })
            .catch(error => {
                console.error("Error loading results:", error);
                showResultsError("Błąd ładowania wyników: " + error.message);
            });
        }
        
        function displayQuizResultsDynamic(data) {
            const container = document.getElementById("detailed-results-forced");
            
            // Update title
            if (data.quiz && data.quiz.title) {
                document.getElementById("quiz-title").textContent = data.quiz.title;
            }
            
            let html = "";
            
            // Overview stats - use data.stats which matches AJAX response
            if (data.stats) {
                const totalQuestions = data.questions ? data.questions.length : 0;
                html += `
                    <div class="cm-stats-overview" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 50px;">
                        <div class="cm-stat-card" style="background: rgba(255, 255, 255, 0.2); padding: 30px; border-radius: 15px; text-align: center;">
                            <span class="cm-stat-value" style="font-size: 3rem; font-weight: 700; display: block; margin-bottom: 10px;">${data.stats.unique_participants || 0}</span>
                            <span class="cm-stat-label" style="font-size: 1.2rem; opacity: 0.9; text-transform: uppercase; font-weight: 600; letter-spacing: 1px;">Uczestników</span>
                        </div>
                        <div class="cm-stat-card" style="background: rgba(255, 255, 255, 0.2); padding: 30px; border-radius: 15px; text-align: center;">
                            <span class="cm-stat-value" style="font-size: 3rem; font-weight: 700; display: block; margin-bottom: 10px;">${totalQuestions}</span>
                            <span class="cm-stat-label" style="font-size: 1.2rem; opacity: 0.9; text-transform: uppercase; font-weight: 600; letter-spacing: 1px;">Pytań</span>
                        </div>
                        <div class="cm-stat-card" style="background: rgba(255, 255, 255, 0.2); padding: 30px; border-radius: 15px; text-align: center;">
                            <span class="cm-stat-value" style="font-size: 3rem; font-weight: 700; display: block; margin-bottom: 10px;">${data.stats.average_score || 0}%</span>
                            <span class="cm-stat-label" style="font-size: 1.2rem; opacity: 0.9; text-transform: uppercase; font-weight: 600; letter-spacing: 1px;">Średni wynik</span>
                        </div>
                    </div>
                `;
            }
            
            // Participants
            if (data.participants && data.participants.length > 0) {
                html += `<div class="participants-grid">`;
                
                data.participants.forEach((participant, index) => {
                    let rankIcon = "#" + (index + 1);
                    if (index === 0) rankIcon = "🥇";
                    else if (index === 1) rankIcon = "🥈";
                    else if (index === 2) rankIcon = "🥉";
                    
                    html += `
                        <div class="participant-card">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.2);">
                                <div style="font-size: 1.5rem;">${rankIcon}</div>
                                <div style="flex: 1; margin: 0 15px;">
                                    <h4 style="font-size: 1.3rem; font-weight: 600; margin: 0;">${participant.participant_name}</h4>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.8rem; font-weight: 700;">${participant.score_percentage}%</div>
                                    <div style="font-size: 0.9rem; opacity: 0.8;">${participant.correct_answers}/${participant.total_possible_answers} poprawnych</div>
                                </div>
                            </div>
                            <div style="display: grid; gap: 10px;">
                    `;
                    
                    if (participant.questions && participant.questions.length > 0) {
                        participant.questions.forEach((question, qIndex) => {
                            const isCorrect = question.is_correct;
                            const correctClass = isCorrect === true ? "correct" : "incorrect";
                            const correctStyle = isCorrect === true ? "background: rgba(46, 213, 115, 0.3);" : "background: rgba(231, 76, 60, 0.3);";
                            const icon = isCorrect === true ? "✓" : "✗";
                            
                            html += `
                                <div style="display: flex; align-items: center; padding: 10px; border-radius: 8px; background: rgba(255, 255, 255, 0.1); ${correctStyle}">
                                    <div style="margin-right: 12px; font-size: 1.2rem; font-weight: bold;">${icon}</div>
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 5px;">P${qIndex + 1}: ${question.question}</div>
                                        <div style="opacity: 0.8;">${question.user_answer || "Brak odpowiedzi"}</div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    html += `
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            container.innerHTML = html;
        }
        
        function showResultsError(message) {
            const container = document.getElementById("detailed-results-forced");
            if (container) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #ff6b6b;">
                        <h3>Błąd ładowania</h3>
                        <p>${message}</p>
                    </div>
                `;
            }
        }
        </script>';
        error_log('add_postmessage_handler() completed - JavaScript output finished');
    }

    /**
     * Render current presentation QR without polling JavaScript (used when polling is added elsewhere)
     */
    private function render_current_presentation_qr_without_polling() {
        $current_presentation = $this->get_current_active_presentation();
        
        if (!$current_presentation) {
            // Check if we should show recent quiz results instead
            $recent_quiz_results = $this->get_recent_completed_quiz_results();
            
            if ($recent_quiz_results) {
                return $this->render_quiz_results_summary($recent_quiz_results);
            }
            
            // Debug info for troubleshooting
            $active_events = CM_Event::get_all('active');
            $debug_info = '';
            
            if (empty($active_events)) {
                $debug_info = '<p><small>Debug: Brak aktywnych wydarzeń.</small></p>';
            } else {
                $debug_info = '<p><small>Debug: Znaleziono ' . count($active_events) . ' aktywnych wydarzeń, ale żadna prezentacja nie jest uruchomiona.</small></p>';
            }
            
            return '<div class="cm-no-active-presentation">
                        <h2>Brak aktywnej prezentacji</h2>
                        <p>Obecnie nie ma aktywnej prezentacji z quizem.</p>
                        ' . $debug_info . '
                    </div>';
        }
        
        // Get quiz associated with this presentation
        $quiz = $this->get_quiz_for_presentation($current_presentation->id);
        
        if (!$quiz) {
            return '<div class="cm-no-quiz-presentation">
                        <h2>' . esc_html($current_presentation->title) . '</h2>
                        <p>Ta prezentacja nie ma przypisanego quizu.</p>
                    </div>';
        }
        
        // Generate QR code for this quiz
        $quiz_url = home_url('/quiz/?cm_quiz=' . $quiz->get_id());
        $qr_result = CM_QR_Generator::generate_quiz_qr($quiz->get_id());
        
        if (is_wp_error($qr_result)) {
            return '<div class="cm-qr-error">Błąd generowania kodu QR: ' . $qr_result->get_error_message() . '</div>';
        }
        
        ob_start();
        
        echo '<div class="cm-qr-display-container">';
        echo '<div class="cm-qr-header">';
        echo '<h1>' . esc_html($current_presentation->title) . '</h1>';
        echo '<h2>' . esc_html($quiz->get_title()) . '</h2>';
        echo '</div>';
        
        echo '<div class="cm-qr-content">';
        echo '<div class="cm-qr-code">';
        if (isset($qr_result['url']) && !empty($qr_result['url'])) {
            echo '<img src="' . esc_url($qr_result['url']) . '" alt="Quiz QR Code" />';
        } else {
            echo '<p>Błąd ładowania kodu QR</p>';
        }
        echo '</div>';
        echo '<div class="cm-qr-instructions">';
        echo '<p>Zeskanuj kod QR swoim telefonem, aby wziąć udział w quizie!</p>';
        echo '<div class="cm-qr-url"><a href="' . esc_url($quiz_url) . '">' . esc_html($quiz_url) . '</a></div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // Add CSS styles (same as original method)
        echo '<style>
        .cm-qr-display-container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .cm-qr-header h1 {
            font-size: 3rem;
            margin: 0 0 20px 0;
            color: #1e40af;
            font-weight: 700;
        }
        
        .cm-qr-header h2 {
            font-size: 2rem;
            margin: 0 0 40px 0;
            color: #374151;
            font-weight: 500;
        }
        
        .cm-qr-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 40px;
        }
        
        .cm-qr-code img {
            max-width: 400px;
            height: auto;
            border: 8px solid #f3f4f6;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .cm-qr-instructions {
            max-width: 600px;
        }
        
        .cm-qr-instructions p {
            font-size: 1.5rem;
            color: #4b5563;
            margin: 0 0 20px 0;
            line-height: 1.4;
        }
        
        .cm-qr-url a {
            display: inline-block;
            padding: 12px 24px;
            background: #eff6ff;
            color: #1e40af;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .cm-qr-url a:hover {
            background: #dbeafe;
            transform: translateY(-2px);
        }
        
        .cm-no-active-presentation,
        .cm-no-quiz-presentation {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            padding: 60px 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .cm-no-active-presentation h2,
        .cm-no-quiz-presentation h2 {
            font-size: 2.5rem;
            margin: 0 0 20px 0;
            color: #374151;
        }
        
        .cm-no-active-presentation p,
        .cm-no-quiz-presentation p {
            font-size: 1.2rem;
            color: #6b7280;
            margin: 0;
        }
        
        .cm-qr-error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        </style>';
        
        // No polling JavaScript added here - it's added by the parent function

        return ob_get_clean();
    }

    /**
     * Render multi-day navigation for events with multiple days
     */
    private function render_multi_day_navigation($event, $current_day) {
        $total_days = $event->get_total_days();
        $output = '<div class="mt-8 text-center">';

        // Logic for which buttons to show
        $buttons_to_show = array();

        if ($total_days == 2) {
            // For 2-day events: show only the other day
            $buttons_to_show = array_filter(array(1, 2), function($day) use ($current_day) {
                return $day !== $current_day;
            });
        } elseif ($total_days > 2) {
            // For >2-day events: show neighboring days (before and after)
            if ($current_day > 1) {
                $buttons_to_show[] = $current_day - 1; // Previous day
            }
            if ($current_day < $total_days) {
                $buttons_to_show[] = $current_day + 1; // Next day
            }
        }

        foreach ($buttons_to_show as $day) {
            $output .= sprintf(
                '<button class="cm-show-day-popup hidden md:inline-flex mx-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors" data-day="%d" data-event-id="%d">Zobacz program dnia %d</button>',
                $day,
                $event->get_id(),
                $day
            );
            // Mobile button - fixed at bottom with calendar icon
            $output .= sprintf(
                '<button class="cm-show-day-popup md:hidden fixed bottom-4 right-4 w-14 h-14 bg-blue-600 text-white rounded-lg shadow-lg hover:bg-blue-700 transition-all z-50 flex items-center justify-center" data-day="%d" data-event-id="%d" title="Zobacz program dnia %d">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </button>',
                $day,
                $event->get_id(),
                $day
            );
        }

        $output .= '</div>';

        // Note: Modal HTML is created dynamically by showDayPopup() in event-live-updates.js

        return $output;
    }
}