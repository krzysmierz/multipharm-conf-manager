<?php

/**
 * Server-Sent Events Controller
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_SSE_Controller {

    /**
     * Active SSE connections
     */
    private static $active_connections = array();
    
    /**
     * Maximum allowed connections
     * Optimized for shared hosting (was 100)
     */
    private static $max_connections = 25;

    /**
     * Initialize SSE Controller
     */
    public static function init() {
        self::$max_connections = get_option('cm_quiz_max_sse_connections', 100);
    }

    /**
     * Stream quiz updates via SSE
     *
     * @param int $event_id Event ID
     * @param int $quiz_id Quiz ID
     */
    public static function stream_quiz_updates($event_id, $quiz_id) {
        // Verify nonce - allow multiple nonce types for SSE
        $nonce = $_GET['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'cm_admin_nonce') &&
            !wp_verify_nonce($nonce, 'cm_public_nonce') &&
            !wp_verify_nonce($nonce, 'cm_sse_nonce')) {

            error_log('SSE: Invalid nonce provided: ' . $nonce);
            wp_die('Security check failed', 'Unauthorized', array('response' => 403));
        }

        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Sprawdź czy to test load
        $is_load_test = isset($_GET['load_test']) && $_GET['load_test'] === '1';

        if (!$is_load_test && self::is_rate_limited($client_ip)) {
            wp_die('Rate limit exceeded', 'Too Many Requests', array('response' => 429));
        }

        // Check connection limit
        if (count(self::$active_connections) >= self::$max_connections) {
            wp_die('Connection limit reached', 'Service Unavailable', array('response' => 503));
        }

        // Set SSE headers
        self::set_sse_headers();

        // Add connection to active list
        $connection_id = uniqid();
        self::$active_connections[$connection_id] = array(
            'event_id' => $event_id,
            'quiz_id' => $quiz_id,
            'start_time' => time()
        );

        // Send initial state
        $initial_state = self::get_quiz_current_state($quiz_id);
        self::send_event('quiz-state-init', $initial_state);

        // Send initial results if we're in results mode
        $current_mode = $initial_state['display_mode'] ?? 'qr';
        if ($current_mode === 'results') {
            $results = self::get_live_results($quiz_id);
            self::send_event('results-update', $results);
            error_log('SSE: Sent initial results-update with ' . count($results['participants']) . ' participants');
        }

        // Keep connection alive and send updates
        $last_update = time();
        $update_interval = 30; // seconds - optimized for shared hosting (was 10)
        $last_mode = $current_mode;
        $start_time = time();
        $max_connection_time = 30; // 30 seconds - optimized for shared hosting (was 110)

        while (connection_status() == CONNECTION_NORMAL && !connection_aborted()) {
            // Check if connection has been open too long
            if ((time() - $start_time) >= $max_connection_time) {
                error_log("SSE: Connection {$connection_id} closing after {$max_connection_time}s (graceful timeout)");
                break;
            }
            // Check for updates every 5 seconds
            if (time() - $last_update >= $update_interval) {
                $current_state = self::get_quiz_current_state($quiz_id);
                
                // Send heartbeat to keep connection alive
                self::send_event('heartbeat', array(
                    'timestamp' => current_time('mysql'),
                    'connection_id' => $connection_id
                ));

                // Check for mode changes
                $current_mode = $current_state['display_mode'] ?? 'qr';
                error_log('SSE: Current mode is: ' . $current_mode);
                if ($last_mode !== null && $current_mode !== $last_mode) {
                    self::send_event('quiz-mode-change', array(
                        'quiz_id' => $quiz_id,
                        'mode' => $current_mode,
                        'old_mode' => $last_mode,
                        'timestamp' => current_time('mysql'),
                        'auto_switched' => false
                    ));
                }
                $last_mode = $current_mode;

                // Check if quiz state has changed
                if (self::has_state_changed($quiz_id, $last_update)) {
                    self::send_event('quiz-state-update', $current_state);
                }

                // Check for broadcast events
                $broadcast = get_transient("cm_sse_broadcast_{$quiz_id}");
                if ($broadcast) {
                    self::send_event($broadcast['event'], $broadcast['data']);
                    delete_transient("cm_sse_broadcast_{$quiz_id}");
                }

                // Check for new participants
                $new_participants = self::get_new_participants($quiz_id, $last_update);
                if (!empty($new_participants)) {
                    foreach ($new_participants as $participant) {
                        self::send_event('participant-joined', $participant);
                    }
                }

                // Send updated results if in results mode
                if ($current_mode === 'results') {
                    $results = self::get_live_results($quiz_id);
                    self::send_event('results-update', $results);
                    error_log('SSE: Sending results-update with ' . count($results['participants']) . ' participants for quiz ' . $quiz_id);
                }

                // Log performance metrics every update cycle
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    self::log_performance();
                }

                $last_update = time();
            }

            // Sleep to prevent excessive CPU usage
            sleep(3); // Optimized for shared hosting (was 1)
            
            // Send keep-alive every 30 seconds
            if (time() % 30 === 0) {
                self::send_event('keep-alive', array('timestamp' => time()));
            }

            // Flush output buffer
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }

        // Remove connection from active list
        unset(self::$active_connections[$connection_id]);
    }

    /**
     * Set appropriate headers for SSE
     */
    private static function set_sse_headers() {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Cache-Control, Content-Type');
        header('X-Accel-Buffering: no'); // Disable nginx buffering

        // Disable all PHP output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set infinite execution time and ignore user abort
        set_time_limit(0);
        ignore_user_abort(true);

        // Send initial padding for some browsers
        echo str_repeat(' ', 2048) . "\n";
        flush();
    }

    /**
     * Send SSE event
     *
     * @param string $event_type Event type
     * @param array $data Event data
     */
    public static function send_event($event_type, $data) {
        $json_data = json_encode($data);
        
        echo "event: {$event_type}\n";
        echo "data: {$json_data}\n";
        echo "id: " . uniqid() . "\n\n";
        
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Get current quiz state
     *
     * @param int $quiz_id Quiz ID
     * @return array Current state data
     */
    public static function get_quiz_current_state($quiz_id) {
        $quiz = new CM_Quiz($quiz_id);
        $quiz_state = CM_Quiz_State::get_state($quiz_id);
        
        if (!$quiz_state) {
            // Create default state if none exists
            $quiz_state = CM_Quiz_State::create_state($quiz_id);
        }

        return array(
            'quiz_id' => $quiz_id,
            'quiz_title' => $quiz->get_title(),
            'is_active' => $quiz->get_is_active(),
            'display_mode' => $quiz_state ? $quiz_state->get_mode() : 'qr',
            'auto_switch_enabled' => $quiz_state ? $quiz_state->is_auto_switch_enabled() : false,
            'auto_switch_delay' => $quiz_state ? $quiz_state->get_auto_switch_delay() : 30,
            'participant_count' => self::get_participant_count($quiz_id),
            'last_updated' => $quiz_state ? $quiz_state->get_last_updated() : '',
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Check if quiz state has changed since last update
     *
     * @param int $quiz_id Quiz ID
     * @param int $last_check Timestamp of last check
     * @return bool True if state has changed
     */
    private static function has_state_changed($quiz_id, $last_check) {
        $quiz_state = CM_Quiz_State::get_state($quiz_id);
        
        if (!$quiz_state) {
            return false;
        }

        $last_updated = $quiz_state->get_last_updated();
        if (empty($last_updated)) {
            return false;
        }

        $last_update_time = strtotime($last_updated);
        return $last_update_time > $last_check;
    }

    /**
     * Get new participants since last update
     *
     * @param int $quiz_id Quiz ID
     * @param int $last_check Timestamp of last check
     * @return array New participants
     */
    private static function get_new_participants($quiz_id, $last_check) {
        global $wpdb;
        
        $table_responses = CM_Database::get_table_name('user_responses');
        
        $new_participants = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT participant_name, user_identifier, MIN(submitted_at) as joined_at
            FROM {$table_responses} 
            WHERE quiz_id = %d 
            AND UNIX_TIMESTAMP(submitted_at) > %d
            AND participant_name IS NOT NULL
            GROUP BY user_identifier
            ORDER BY joined_at ASC
        ", $quiz_id, $last_check));

        $participants = array();
        foreach ($new_participants as $participant) {
            $participants[] = array(
                'quiz_id' => $quiz_id,
                'participant_name' => $participant->participant_name,
                'total_participants' => self::get_participant_count($quiz_id),
                'timestamp' => $participant->joined_at
            );
        }

        return $participants;
    }

    /**
     * Get current participant count
     *
     * @param int $quiz_id Quiz ID
     * @return int Participant count
     */
    private static function get_participant_count($quiz_id) {
        global $wpdb;
        
        $table_responses = CM_Database::get_table_name('user_responses');
        
        return (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_identifier)
            FROM {$table_responses} 
            WHERE quiz_id = %d
        ", $quiz_id));
    }

    /**
     * Get live quiz results
     *
     * @param int $quiz_id Quiz ID
     * @return array Results data
     */
    private static function get_live_results($quiz_id) {
        $cache_key = 'cm_live_results_' . $quiz_id;
        $cached = wp_cache_get($cache_key, 'cm_quiz');

        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $quiz = new CM_Quiz($quiz_id);

        $participant_count = self::get_participant_count($quiz_id);
        $questions = $quiz->get_questions();
        $total_questions = count($questions);

        $table_responses = CM_Database::get_table_name('user_responses');
        $table_answers = CM_Database::get_table_name('quiz_answers');

        // Get all responses for the quiz to calculate question-specific stats
        $all_responses = $wpdb->get_results($wpdb->prepare("
            SELECT question_id, selected_answer_ids
            FROM {$table_responses}
            WHERE quiz_id = %d AND selected_answer_ids != ''
        ", $quiz_id));

        // Process responses to count selections for each answer
        $answer_counts = array();
        foreach ($all_responses as $response) {
            $selected_ids = explode(',', $response->selected_answer_ids);
            foreach ($selected_ids as $answer_id) {
                $trimmed_answer_id = trim($answer_id);
                if (!empty($trimmed_answer_id)) {
                    if (!isset($answer_counts[$trimmed_answer_id])) {
                        $answer_counts[$trimmed_answer_id] = 0;
                    }
                    $answer_counts[$trimmed_answer_id]++;
                }
            }
        }

        $question_stats = array();
        foreach ($questions as $question) {
            if ($question->question_type === 'text') {
                continue; // Skip text questions for stats
            }

            // Get answers from database like in get_quiz_results_public
            $answers = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_answers} WHERE question_id = %d ORDER BY sort_order",
                $question->id
            ));

            $answer_stats = array();
            $total_responses_for_question = 0;

            foreach ($answers as $answer) {
                $total_responses_for_question += $answer_counts[$answer->id] ?? 0;
            }

            foreach ($answers as $answer) {
                $answer_id = $answer->id;
                $response_count = $answer_counts[$answer_id] ?? 0;
                $percentage = ($total_responses_for_question > 0) ? round(($response_count / $total_responses_for_question) * 100) : 0;

                $answer_stats[] = array(
                    'answer_id' => $answer_id,
                    'answer_text' => $answer->answer_text,
                    'is_correct' => (bool) $answer->is_correct,
                    'count' => (int) $response_count,
                    'percentage' => $percentage,
                );
            }

            $question_stats[] = array(
                'question_id' => $question->id,
                'question' => $question->question,
                'answers' => $answer_stats,
            );
        }

        $total_responses = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_responses} WHERE quiz_id = %d", $quiz_id));

        // Get participant ranking data - using similar logic as get_quiz_results_public
        $table_questions = CM_Database::get_table_name('quiz_questions');
        $participants_query = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT user_identifier, participant_name, MIN(submitted_at) as first_submission
             FROM {$table_responses}
             WHERE quiz_id = %d
             GROUP BY user_identifier, participant_name
             ORDER BY first_submission",
            $quiz_id
        ));

        // OPTIMIZATION: Fetch all correct answers ONCE (fixes N+1 query problem)
        $all_correct_answers = $wpdb->get_results("
            SELECT question_id, id
            FROM {$table_answers}
            WHERE is_correct = 1
        ");

        $correct_by_question = array();
        foreach ($all_correct_answers as $ans) {
            if (!isset($correct_by_question[$ans->question_id])) {
                $correct_by_question[$ans->question_id] = array();
            }
            $correct_by_question[$ans->question_id][] = $ans->id;
        }

        $participants_data = array();
        $max_score = 0;

        foreach ($participants_query as $participant) {
            $participant_data = array(
                'user_identifier' => $participant->user_identifier,
                'participant_name' => $participant->participant_name ?: 'Nieznany uczestnik',
                'submission_time' => $participant->first_submission,
                'correct_answers' => 0,
                'total_possible_answers' => 0,
                'score_percentage' => 0,
                'is_top_scorer' => false
            );

            // Get this participant's answers for scoring questions only
            $participant_responses = $wpdb->get_results($wpdb->prepare("
                SELECT r.question_id, r.selected_answer_ids, q.question_type
                FROM {$table_responses} r
                JOIN {$table_questions} q ON r.question_id = q.id
                WHERE r.quiz_id = %d AND r.user_identifier = %s
                AND q.question_type != 'text'
            ", $quiz_id, $participant->user_identifier));

            foreach ($participant_responses as $response) {
                if (!empty($response->selected_answer_ids)) {
                    $selected_ids = explode(',', $response->selected_answer_ids);
                    $selected_ids = array_map('trim', $selected_ids);

                    // Use pre-fetched correct answers (no more N+1 query!)
                    $correct_answer_ids = $correct_by_question[$response->question_id] ?? array();

                    // Check if participant selected any correct answer
                    $has_correct = false;
                    foreach ($selected_ids as $selected_id) {
                        if (in_array($selected_id, $correct_answer_ids)) {
                            $has_correct = true;
                            break;
                        }
                    }

                    if ($has_correct) {
                        $participant_data['correct_answers']++;
                    }
                    $participant_data['total_possible_answers']++;
                }
            }

            // Calculate percentage
            if ($participant_data['total_possible_answers'] > 0) {
                $participant_data['score_percentage'] = round(
                    ($participant_data['correct_answers'] / $participant_data['total_possible_answers']) * 100
                );
                $max_score = max($max_score, $participant_data['score_percentage']);
            }

            $participants_data[] = $participant_data;
        }

        // Mark participants with the highest score
        foreach ($participants_data as &$participant) {
            $participant['is_top_scorer'] = ($participant['score_percentage'] == $max_score && $max_score > 0);
        }

        // Sort participants by score (highest first) and then by submission time (earliest first)
        usort($participants_data, function($a, $b) {
            if ($b['correct_answers'] !== $a['correct_answers']) {
                return $b['correct_answers'] - $a['correct_answers'];
            }
            return strtotime($a['submission_time']) - strtotime($b['submission_time']);
        });

        $results = array(
            'quiz_id' => (int) $quiz_id,
            'total_participants' => $participant_count,
            'total_questions' => $total_questions,
            'total_responses' => $total_responses,
            'avg_score' => self::calculate_average_score($quiz_id),
            'participants' => $participants_data,
            'questions' => $question_stats,
            'timestamp' => current_time('mysql')
        );

        wp_cache_set($cache_key, $results, 'cm_quiz', 30); // Optimized for shared hosting (was 5)

        return $results;
    }

    /**
     * Calculate average score for quiz
     *
     * @param int $quiz_id Quiz ID
     * @return float Average score percentage
     */
    private static function calculate_average_score($quiz_id) {
        global $wpdb;
        
        $table_responses = CM_Database::get_table_name('user_responses');
        $table_answers = CM_Database::get_table_name('quiz_answers');
        
        // Get all user responses with correct answer information
        $user_scores = $wpdb->get_results($wpdb->prepare("
            SELECT 
                r.user_identifier,
                COUNT(CASE WHEN a.is_correct = 1 AND FIND_IN_SET(a.id, r.selected_answer_ids) THEN 1 END) as correct_answers,
                COUNT(*) as total_answers
            FROM {$table_responses} r
            LEFT JOIN {$table_answers} a ON FIND_IN_SET(a.id, r.selected_answer_ids)
            WHERE r.quiz_id = %d 
            AND r.selected_answer_ids != ''
            GROUP BY r.user_identifier
        ", $quiz_id));

        if (empty($user_scores)) {
            return 0;
        }

        $total_percentage = 0;
        $user_count = 0;

        foreach ($user_scores as $score) {
            if ($score->total_answers > 0) {
                $percentage = ($score->correct_answers / $score->total_answers) * 100;
                $total_percentage += $percentage;
                $user_count++;
            }
        }

        return $user_count > 0 ? round($total_percentage / $user_count, 1) : 0;
    }

    /**
     * Broadcast event to all connected clients for a specific quiz
     *
     * @param int $quiz_id Quiz ID
     * @param string $event_type Event type
     * @param array $data Event data
     */
    public static function broadcast_to_quiz($quiz_id, $event_type, $data) {
        // This would typically be handled by a message queue or Redis
        // For now, we'll store it in a transient that gets picked up by active connections
        
        $broadcast_data = array(
            'event' => $event_type,
            'data' => $data,
            'timestamp' => time()
        );
        
        set_transient("cm_sse_broadcast_{$quiz_id}", $broadcast_data, 60); // 1 minute
    }

    /**
     * Trigger mode change event
     *
     * @param int $quiz_id Quiz ID
     * @param string $new_mode New display mode
     * @param bool $auto_switched Whether this was an automatic switch
     */
    public static function trigger_mode_change($quiz_id, $new_mode, $auto_switched = false) {
        $data = array(
            'quiz_id' => $quiz_id,
            'mode' => $new_mode,
            'timestamp' => current_time('mysql'),
            'auto_switched' => $auto_switched
        );
        
        self::broadcast_to_quiz($quiz_id, 'quiz-mode-change', $data);
    }

    /**
     * Trigger quiz status change event
     *
     * @param int $quiz_id Quiz ID
     * @param bool $is_active New active status
     */
    public static function trigger_status_change($quiz_id, $is_active) {
        $data = array(
            'quiz_id' => $quiz_id,
            'is_active' => $is_active,
            'timestamp' => current_time('mysql')
        );
        
        self::broadcast_to_quiz($quiz_id, 'quiz-status-change', $data);
    }

    /**
     * Stream event updates via SSE
     */
    public static function stream_event_updates($event_id) {
        // Verify nonce for security
        $nonce = $_GET['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'cm_sse_nonce')) {
            error_log("CM_SSE: Invalid nonce for event stream. Provided: " . $nonce);
            wp_die('Security check failed', 'Unauthorized', array('response' => 403));
        }

        error_log("CM_SSE: Starting event stream for event_id: $event_id");

        self::set_sse_headers();

        $last_active_presentation_id = null;
        $last_lineup_hash = '';

        while (connection_status() == CONNECTION_NORMAL && !connection_aborted()) {
            // Load event data
            $event = new CM_Event($event_id);
            $total_days = $event->get_total_days();
            $active_day = $event->get_current_active_day();

            // 1. Check active presentation change
            $active_presentation = CM_Lineup::get_active_presentation($event_id);
            $current_presentation_id = $active_presentation ? $active_presentation->id : null;

            if ($last_active_presentation_id !== $current_presentation_id) {
                error_log("CM_SSE: Presentation changed from $last_active_presentation_id to $current_presentation_id");
                $presentation_data = $active_presentation ? (array) $active_presentation : null;
                if ($presentation_data) {
                    $presentation_data['total_days'] = $total_days;
                }
                self::send_event('presentation-change', $presentation_data);
                $last_active_presentation_id = $current_presentation_id;
            }

            // 2. Check lineup changes - only for active day
            $lineup = CM_Lineup::get_by_event_and_day($event_id, $active_day);
            $filtered_lineup = self::filter_lineup_by_time($lineup);
            $current_lineup_hash = md5(json_encode($filtered_lineup));

            if ($last_lineup_hash !== $current_lineup_hash) {
                error_log("CM_SSE: Lineup changed for event $event_id (active day: $active_day)");
                $lineup_data = array(
                    'items' => $filtered_lineup,
                    'total_days' => $total_days,
                    'active_day' => $active_day
                );
                self::send_event('lineup-change', $lineup_data);
                $last_lineup_hash = $current_lineup_hash;
            }

            // 3. Check for broadcasts
            $broadcast = get_transient("cm_sse_broadcast_event_{$event_id}");
            if ($broadcast) {
                error_log("CM_SSE: Broadcasting event {$broadcast['event']} for event $event_id");
                self::send_event($broadcast['event'], $broadcast['data']);
                delete_transient("cm_sse_broadcast_event_{$event_id}");
            }

            // 4. Heartbeat
            self::send_event('heartbeat', ['timestamp' => time()]);

            sleep(5);

            if (ob_get_level()) ob_flush();
            flush();
        }
    }

    /**
     * Broadcast to event listeners
     */
    public static function broadcast_to_event($event_id, $event_type, $data) {
        error_log("CM_SSE: Broadcasting $event_type to event $event_id");

        $broadcast_data = array(
            'event' => $event_type,
            'data' => $data,
            'timestamp' => time()
        );
        set_transient("cm_sse_broadcast_event_{$event_id}", $broadcast_data, 60);
    }

    /**
     * Filter lineup by current time
     */
    private static function filter_lineup_by_time($lineup) {
        if (empty($lineup)) {
            return array();
        }

        $current_time = current_time('H:i:s');

        // Find active presentation to determine "event time"
        $active_presentation = null;
        foreach ($lineup as $item) {
            if ($item->is_active) {
                $active_presentation = $item;
                break;
            }
        }

        return array_filter($lineup, function($item) use ($current_time, $active_presentation) {
            // Calculate end_time if not exists
            if (!isset($item->end_time) && isset($item->start_time) && isset($item->duration_minutes)) {
                $start_timestamp = strtotime($item->start_time);
                $end_timestamp = $start_timestamp + ($item->duration_minutes * 60);
                $item->end_time = date('H:i:s', $end_timestamp);
            }

            // Show if item is active
            if ($item->is_active) {
                return true;
            }

            // If there's an active presentation, use its time as reference
            if ($active_presentation && isset($active_presentation->start_time)) {
                $reference_time = $active_presentation->start_time;
                // Show presentations that start at or after the active presentation time
                return isset($item->start_time) && $item->start_time >= $reference_time;
            }

            // No active presentation - use real current time
            // Hide presentations that have already ended
            if (isset($item->end_time) && $item->end_time < $current_time) {
                return false;
            }

            // Show upcoming presentations (not started yet or still running)
            return true;
        });
    }

    /**
     * Get active connection count
     *
     * @return int Number of active connections
     */
    public static function get_active_connection_count() {
        return count(self::$active_connections);
    }

    /**
     * Log performance metrics
     */
    private static function log_performance() {
        global $wpdb;
        $memory = round(memory_get_usage() / 1024 / 1024, 2);
        error_log("CM SSE Performance - Queries: {$wpdb->num_queries}, Memory: {$memory}MB");
    }

    /**
     * Clean up old connections
     */
    public static function cleanup_connections() {
        $now = time();
        $timeout = 300; // 5 minutes

        foreach (self::$active_connections as $id => $connection) {
            if ($now - $connection['start_time'] > $timeout) {
                unset(self::$active_connections[$id]);
            }
        }
    }

    /**
     * Check if IP is rate limited
     *
     * @param string $ip Client IP
     * @return bool True if rate limited
     */
    private static function is_rate_limited($ip) {
        $key = 'cm_sse_rate_' . md5($ip);
        $requests = wp_cache_get($key, 'cm_sse');

        $max_requests = apply_filters('cm_sse_rate_limit', 50);

        if ($requests === false) {
            wp_cache_set($key, 1, 'cm_sse', 60);
            return false;
        }

        if ($requests >= $max_requests) {
            return true;
        }

        wp_cache_set($key, $requests + 1, 'cm_sse', 60);
        return false;
    }
}

// Initialize SSE Controller
CM_SSE_Controller::init();