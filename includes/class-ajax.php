<?php

/**
 * AJAX handlers class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Ajax {

    /**
     * Initialize AJAX hooks
     */
    public function init_hooks($loader) {
        // Admin AJAX actions
        $loader->add_action('wp_ajax_cm_update_lineup_order', $this, 'update_lineup_order');
        $loader->add_action('wp_ajax_cm_save_lineup_item', $this, 'save_lineup_item');
        $loader->add_action('wp_ajax_cm_get_lineup_item', $this, 'get_lineup_item');
        $loader->add_action('wp_ajax_cm_delete_lineup_item', $this, 'delete_lineup_item');
        $loader->add_action('wp_ajax_cm_check_time_conflict', $this, 'check_time_conflict');
        $loader->add_action('wp_ajax_cm_clear_time_cache', $this, 'clear_time_cache');

        // Quick events endpoints
        $loader->add_action('wp_ajax_cm_check_time_conflict_extended', $this, 'check_time_conflict_extended');
        $loader->add_action('wp_ajax_cm_save_quick_event', $this, 'save_quick_event');
        $loader->add_action('wp_ajax_cm_recalculate_timeline_after_sort', $this, 'recalculate_timeline_after_sort');
        $loader->add_action('wp_ajax_cm_start_presentation', $this, 'start_presentation');
        $loader->add_action('wp_ajax_cm_pause_event', $this, 'pause_event');
        $loader->add_action('wp_ajax_cm_save_quiz', $this, 'save_quiz');
        $loader->add_action('wp_ajax_cm_toggle_quiz_status', $this, 'toggle_quiz_status');
        $loader->add_action('wp_ajax_cm_get_quiz_questions', $this, 'get_quiz_questions');
        $loader->add_action('wp_ajax_cm_get_quiz_data', $this, 'get_quiz_data');
        $loader->add_action('wp_ajax_cm_delete_quiz', $this, 'delete_quiz');
        $loader->add_action('wp_ajax_cm_reset_quiz', $this, 'reset_quiz');
        $loader->add_action('wp_ajax_cm_get_question_data', $this, 'get_question_data');
        $loader->add_action('wp_ajax_cm_get_quiz_results_detailed', $this, 'get_quiz_results_detailed');
        $loader->add_action('wp_ajax_nopriv_cm_get_quiz_results_detailed', $this, 'get_quiz_results_detailed');
        $loader->add_action('wp_ajax_cm_export_quiz_results', $this, 'export_quiz_results');
        $loader->add_action('wp_ajax_cm_save_quiz_question', $this, 'save_quiz_question');
        $loader->add_action('wp_ajax_cm_delete_quiz_question', $this, 'delete_quiz_question');
        $loader->add_action('wp_ajax_cm_upload_presentation', $this, 'upload_presentation');
        $loader->add_action('wp_ajax_cm_delete_file', $this, 'delete_file');
        $loader->add_action('wp_ajax_cm_generate_qr', $this, 'generate_qr');
        $loader->add_action('wp_ajax_cm_delete_qr', $this, 'delete_qr');
        $loader->add_action('wp_ajax_cm_regenerate_qr', $this, 'regenerate_qr');
        $loader->add_action('wp_ajax_cm_cleanup_files', $this, 'cleanup_files');
        $loader->add_action('wp_ajax_cm_set_quiz_display_mode', $this, 'set_quiz_display_mode');
        $loader->add_action('wp_ajax_cm_get_quiz_display_mode', $this, 'get_quiz_display_mode');
        $loader->add_action('wp_ajax_nopriv_cm_get_quiz_display_mode', $this, 'get_quiz_display_mode');
        
        // SSE and live update endpoints
        $loader->add_action('wp_ajax_cm_quiz_live_updates', $this, 'quiz_live_updates');
        $loader->add_action('wp_ajax_nopriv_cm_quiz_live_updates', $this, 'quiz_live_updates');
        $loader->add_action('wp_ajax_cm_event_live_updates', $this, 'event_live_updates');
        $loader->add_action('wp_ajax_nopriv_cm_event_live_updates', $this, 'event_live_updates');
        $loader->add_action('wp_ajax_cm_get_test_nonce', $this, 'get_test_nonce');
        $loader->add_action('wp_ajax_nopriv_cm_get_test_nonce', $this, 'get_test_nonce');
        $loader->add_action('wp_ajax_cm_set_quiz_state_mode', $this, 'set_quiz_state_mode');
        $loader->add_action('wp_ajax_nopriv_cm_set_quiz_state_mode', $this, 'set_quiz_state_mode_public');
        $loader->add_action('wp_ajax_cm_configure_auto_switch', $this, 'configure_auto_switch');
        $loader->add_action('wp_ajax_cm_toggle_quiz_mode', $this, 'toggle_quiz_mode');
        $loader->add_action('wp_ajax_nopriv_cm_toggle_quiz_mode', $this, 'toggle_quiz_mode_public');
        $loader->add_action('wp_ajax_cm_get_quiz_current_mode', $this, 'get_quiz_current_mode');
        $loader->add_action('wp_ajax_nopriv_cm_get_quiz_current_mode', $this, 'get_quiz_current_mode');
        $loader->add_action('wp_ajax_nopriv_cm_participant_join_notification', $this, 'participant_join_notification');

        // Public AJAX actions (available for non-logged users)
        $loader->add_action('wp_ajax_nopriv_cm_register_participant', $this, 'register_participant');
        $loader->add_action('wp_ajax_cm_register_participant', $this, 'register_participant');
        $loader->add_action('wp_ajax_nopriv_cm_submit_quiz', $this, 'submit_quiz');
        $loader->add_action('wp_ajax_cm_submit_quiz', $this, 'submit_quiz');
        $loader->add_action('wp_ajax_nopriv_cm_get_current_time', $this, 'get_current_time');
        $loader->add_action('wp_ajax_cm_get_current_time', $this, 'get_current_time');
        $loader->add_action('wp_ajax_nopriv_cm_get_current_presentation', $this, 'get_current_presentation');
        $loader->add_action('wp_ajax_cm_get_current_presentation', $this, 'get_current_presentation');
        $loader->add_action('wp_ajax_nopriv_cm_get_quiz_results', $this, 'get_quiz_results');
        $loader->add_action('wp_ajax_cm_get_quiz_results', $this, 'get_quiz_results');
        $loader->add_action('wp_ajax_nopriv_cm_submit_quiz_response', $this, 'submit_quiz_response');
        $loader->add_action('wp_ajax_cm_submit_quiz_response', $this, 'submit_quiz_response');
        $loader->add_action('wp_ajax_nopriv_cm_get_quiz_results_public', $this, 'get_quiz_results_public');
        $loader->add_action('wp_ajax_cm_get_quiz_results_public', $this, 'get_quiz_results_public');
        
        // Migration endpoint
        $loader->add_action('wp_ajax_cm_run_migration', $this, 'run_migration');
        
        // Quiz participant count endpoint
        $loader->add_action('wp_ajax_cm_get_quiz_participant_count', $this, 'get_quiz_participant_count');
        
        // QR Code endpoints for quiz display
        $loader->add_action('wp_ajax_nopriv_cm_get_quiz_qr_code', $this, 'get_quiz_qr_code');
        $loader->add_action('wp_ajax_cm_get_quiz_qr_code', $this, 'get_quiz_qr_code');
        $loader->add_action('wp_ajax_nopriv_cm_generate_quiz_qr', $this, 'generate_quiz_qr');
        $loader->add_action('wp_ajax_cm_generate_quiz_qr', $this, 'generate_quiz_qr');

        // Leaderboard endpoint
        $loader->add_action('wp_ajax_nopriv_cm_get_quiz_leaderboard', $this, 'get_quiz_leaderboard');
        $loader->add_action('wp_ajax_cm_get_quiz_leaderboard', $this, 'get_quiz_leaderboard');

        // Database migration endpoint (admin only)
        $loader->add_action('wp_ajax_cm_run_database_migration', $this, 'run_database_migration');

        // Multi-day conference endpoints
        $loader->add_action('wp_ajax_cm_add_event_day', $this, 'add_event_day');
        $loader->add_action('wp_ajax_cm_delete_event_day', $this, 'delete_event_day');
        $loader->add_action('wp_ajax_cm_load_day_content', $this, 'load_day_content');
        $loader->add_action('wp_ajax_nopriv_cm_get_day_lineup', $this, 'get_day_lineup');
        $loader->add_action('wp_ajax_cm_get_day_lineup', $this, 'get_day_lineup');
    }

    /**
     * Update lineup order
     */
    public function update_lineup_order() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $lineup_items = isset($_POST['lineup_items']) ? $_POST['lineup_items'] : array();
        
        if (empty($lineup_items)) {
            wp_send_json_error('No lineup items provided');
        }

        $result = CM_Lineup::update_sort_order($lineup_items);

        if ($result) {
            // Get event_id from the first lineup item and recalculate timeline
            if (!empty($lineup_items[0])) {
                $first_item = CM_Database::get_row('lineup', array('id' => intval($lineup_items[0])));
                if ($first_item) {
                    $event_id = intval($first_item->event_id);

                    error_log("CM_AJAX: Recalculating timeline after sort order change for event {$event_id}");

                    // Recalculate timeline based on new sort order
                    CM_Lineup::calculate_timeline_from_sort_order($event_id);

                    // Get updated lineup for broadcasting - only active day
                    $event = new CM_Event($event_id);
                    $active_day = $event->get_current_active_day();
                    $updated_lineup = CM_Lineup::get_by_event_and_day($event_id, $active_day);

                    // Broadcast changes via SSE
                    if (class_exists('CM_SSE_Controller')) {
                        CM_SSE_Controller::broadcast_to_event($event_id, 'lineup-change', $updated_lineup);
                        error_log("CM_AJAX: Broadcasted lineup order change for event $event_id (active day: $active_day)");
                    }

                    // Return timeline data for immediate UI update
                    $timeline_data = array_map(function($item) {
                        return array(
                            'id' => $item->id,
                            'start_time' => $item->start_time,
                            'end_time' => date('H:i:s', strtotime($item->start_time) + ($item->duration_minutes * 60))
                        );
                    }, $updated_lineup);

                    wp_send_json_success(array(
                        'message' => 'Lineup order updated successfully',
                        'timeline' => $timeline_data
                    ));
                }
            }

            wp_send_json_success('Lineup order updated successfully');
        } else {
            wp_send_json_error('Failed to update lineup order');
        }
    }

    /**
     * Save lineup item
     */
    public function save_lineup_item() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $event_id = intval(isset($_POST['event_id']) ? $_POST['event_id'] : 0);
        $lineup_id = isset($_POST['lineup_id']) ? intval($_POST['lineup_id']) : 0;
        
        // Validate required fields
        if (empty($event_id)) {
            wp_send_json_error('Event ID is required');
        }
        
        if (!isset($_POST['title']) || empty($_POST['title'])) {
            wp_send_json_error('Title is required');
        }
        
        if (empty($_POST['start_time'])) {
            wp_send_json_error('Start time is required');
        }

        // Check for time conflicts with existing presentations
        $start_time = sanitize_text_field($_POST['start_time']);
        $duration_minutes = intval(isset($_POST['duration_minutes']) ? $_POST['duration_minutes'] : 30); // Default 30 minutes
        $exclude_id = $lineup_id > 0 ? $lineup_id : null;
        $day_number = intval(isset($_POST['day_number']) ? $_POST['day_number'] : 1);

        $conflict_check = CM_Lineup::has_time_conflict($event_id, $start_time, $duration_minutes, $exclude_id, $day_number);
        if ($conflict_check['has_conflict']) {
            wp_send_json_error('A presentation already exists at this start time. Please choose a different time.');
        }

        $lineup_data = array(
            'event_id' => $event_id,
            'day_number' => intval(isset($_POST['day_number']) ? $_POST['day_number'] : 1),
            'title' => sanitize_text_field(isset($_POST['title']) ? $_POST['title'] : ''),
            'description' => sanitize_textarea_field(isset($_POST['description']) ? $_POST['description'] : ''),
            'presenter' => sanitize_text_field(isset($_POST['presenter']) ? $_POST['presenter'] : ''),
            'start_time' => sanitize_text_field(isset($_POST['start_time']) ? $_POST['start_time'] : ''),
            'duration_minutes' => intval(isset($_POST['duration_minutes']) ? $_POST['duration_minutes'] : 0),
            'presentation_file' => isset($_POST['presentation_file']) ? sanitize_text_field($_POST['presentation_file']) : '',
            'quiz_id' => (!empty($_POST['quiz_id']) && $_POST['quiz_id'] !== '0') ? intval($_POST['quiz_id']) : null,
            'is_active' => 0
        );

        if ($lineup_id > 0) {
            // Update existing item
            $result = CM_Database::update('lineup', $lineup_data, array('id' => $lineup_id));
            $message = 'Presentation updated successfully';
        } else {
            // Create new item with time-based sorting
            $lineup_data['sort_order'] = CM_Lineup::get_next_sort_order($event_id, $lineup_data['start_time']);
            $result = CM_Database::insert('lineup', $lineup_data);
            $message = 'Presentation added successfully';
        }
        
        if (!is_wp_error($result) && $result !== false) {
            // Trigger SSE broadcast for lineup change - only active day
            if (class_exists('CM_SSE_Controller')) {
                $event = new CM_Event($event_id);
                $active_day = $event->get_current_active_day();
                $lineup = CM_Lineup::get_by_event_and_day($event_id, $active_day);
                CM_SSE_Controller::broadcast_to_event($event_id, 'lineup-change', $lineup);
                error_log("CM_AJAX: Broadcasted lineup save for event $event_id (active day: $active_day)");
            }

            wp_send_json_success(array('message' => $message, 'lineup_id' => $lineup_id > 0 ? $lineup_id : $result));
        } else {
            $error_message = 'Failed to save presentation';
            if (is_wp_error($result)) {
                $error_message .= ': ' . $result->get_error_message();
            }
            error_log('CM_Ajax::save_lineup_item failed: ' . print_r($lineup_data, true) . ' Error: ' . $error_message);
            wp_send_json_error($error_message);
        }
    }

    /**
     * Get lineup item data for editing
     */
    public function get_lineup_item() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $lineup_id = isset($_POST['lineup_id']) ? intval($_POST['lineup_id']) : 0;
        
        if (empty($lineup_id)) {
            wp_send_json_error('Lineup ID is required');
        }
        
        $lineup_item = CM_Database::get_row('lineup', array('id' => $lineup_id));
        
        if ($lineup_item) {
            wp_send_json_success($lineup_item);
        } else {
            wp_send_json_error('Lineup item not found');
        }
    }

    /**
     * Delete lineup item
     */
    public function delete_lineup_item() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $lineup_id = intval($_POST['lineup_id']);
        
        if (empty($lineup_id)) {
            wp_send_json_error('Lineup ID required');
        }

        // Get event_id before deletion
        $lineup_item = CM_Database::get_results('lineup', array('id' => $lineup_id));
        $event_id = $lineup_item ? $lineup_item[0]->event_id : null;

        $result = CM_Database::delete('lineup', array('id' => $lineup_id));

        if ($result !== false) {
            // Clear time cache after successful deletion
            if ($event_id) {
                CM_Lineup::clear_time_cache($event_id);
                error_log("CM_AJAX: Cleared time cache for event $event_id after lineup deletion");
            }

            // Trigger SSE broadcast for lineup change - only active day
            if ($event_id && class_exists('CM_SSE_Controller')) {
                $event = new CM_Event($event_id);
                $active_day = $event->get_current_active_day();
                $lineup = CM_Lineup::get_by_event_and_day($event_id, $active_day);
                CM_SSE_Controller::broadcast_to_event($event_id, 'lineup-change', $lineup);
                error_log("CM_AJAX: Broadcasted lineup delete for event $event_id (active day: $active_day)");
            }

            wp_send_json_success('Presentation deleted successfully');
        } else {
            wp_send_json_error('Failed to delete presentation');
        }
    }

    /**
     * Start presentation
     */
    public function start_presentation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $event_id = intval(isset($_POST['event_id']) ? $_POST['event_id'] : 0);
        $lineup_id = intval($_POST['lineup_id']);

        $result = CM_Lineup::start_presentation($lineup_id, $event_id);

        if ($result !== false) {
            // Trigger SSE broadcast
            $active_presentation = CM_Lineup::get_active_presentation($event_id);
            if (class_exists('CM_SSE_Controller')) {
                CM_SSE_Controller::broadcast_to_event($event_id, 'presentation-change', $active_presentation);
                error_log("CM_AJAX: Broadcasted presentation change for event $event_id");
            }

            wp_send_json_success('Presentation started successfully');
        } else {
            wp_send_json_error('Failed to start presentation');
        }
    }

    /**
     * Pause event
     */
    public function pause_event() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $event_id = intval(isset($_POST['event_id']) ? $_POST['event_id'] : 0);
        $event = new CM_Event($event_id);
        
        if ($event->get_id()) {
            $event->set_status('paused');
            $result = $event->save();
            
            if (!is_wp_error($result)) {
                wp_send_json_success('Event paused successfully');
            } else {
                wp_send_json_error('Failed to pause event');
            }
        } else {
            wp_send_json_error('Event not found');
        }
    }

    /**
     * Delete quiz question
     */
    public function delete_quiz_question() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $question_id = intval($_POST['question_id']);
        
        // Delete answers first
        CM_Database::delete('quiz_answers', array('question_id' => $question_id));
        
        // Delete question
        $result = CM_Database::delete('quiz_questions', array('id' => $question_id));
        
        if ($result !== false) {
            wp_send_json_success('Question deleted successfully');
        } else {
            wp_send_json_error('Failed to delete question');
        }
    }

    /**
     * Upload presentation file
     */
    public function upload_presentation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }

        $event_id = intval(isset($_POST['event_id']) ? $_POST['event_id'] : 0);
        $file = $_FILES['file'];

        $result = CM_File_Manager::upload_presentation_file($file, $event_id);
        
        if (!is_wp_error($result)) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result->get_error_message());
        }
    }

    /**
     * Delete file
     */
    public function delete_file() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $file_id = intval($_POST['file_id']);
        
        if (empty($file_id)) {
            wp_send_json_error('File ID required');
        }

        $result = CM_File_Manager::delete_file($file_id);
        
        if ($result) {
            wp_send_json_success('File deleted successfully');
        } else {
            wp_send_json_error('Failed to delete file');
        }
    }

    /**
     * Generate QR code
     */
    public function generate_qr() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $type = sanitize_text_field($_POST['type']);
        $target_id = intval($_POST['target_id']);

        switch ($type) {
            case 'event':
                $result = CM_QR_Generator::generate_event_qr($target_id);
                break;
            case 'quiz':
                $result = CM_QR_Generator::generate_quiz_qr($target_id);
                break;
            case 'presentation':
                $result = CM_QR_Generator::generate_presentation_qr($target_id);
                break;
            default:
                wp_send_json_error('Invalid QR type');
                return;
        }

        if (!is_wp_error($result)) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result->get_error_message());
        }
    }

    /**
     * Delete QR code
     */
    public function delete_qr() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $qr_id = intval($_POST['qr_id']);
        
        if (empty($qr_id)) {
            wp_send_json_error('QR ID required');
        }

        $result = CM_QR_Generator::delete_qr_code($qr_id);
        
        if ($result) {
            wp_send_json_success('QR code deleted successfully');
        } else {
            wp_send_json_error('Failed to delete QR code');
        }
    }

    /**
     * Regenerate QR code
     */
    public function regenerate_qr() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $qr_id = intval($_POST['qr_id']);
        
        if (empty($qr_id)) {
            wp_send_json_error('QR ID required');
        }

        $result = CM_QR_Generator::regenerate_qr_code($qr_id);
        
        if (!is_wp_error($result) && $result !== false) {
            wp_send_json_success($result);
        } else {
            $error_message = is_wp_error($result) ? $result->get_error_message() : 'Failed to regenerate QR code';
            wp_send_json_error($error_message);
        }
    }

    /**
     * Submit quiz (public action)
     */
    public function submit_quiz() {
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');

    // Basic nonce validation (allow both admin and public nonces)
    if (!wp_verify_nonce($nonce, 'cm_public_nonce') && !wp_verify_nonce($nonce, 'cm_admin_nonce')) {
        wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
    }

    $quiz_id = intval($_POST['quiz_id']);
    $user_identifier = sanitize_text_field($_POST['user_identifier']);
    $responses_raw = $_POST['responses'] ?? '';

    // Debug: Log received data
    if (WP_DEBUG) {
        error_log('CM_Ajax::submit_quiz - Raw responses: ' . print_r($responses_raw, true));
        error_log('CM_Ajax::submit_quiz - Type: ' . gettype($responses_raw));
    }

    // Handle JSON encoded responses
    if (is_string($responses_raw)) {
        // Try to decode as-is first
        $responses = json_decode($responses_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If that fails, try unescaping first
            $unescaped = stripslashes($responses_raw);
            $responses = json_decode($unescaped, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (WP_DEBUG) {
                    error_log('CM_Ajax::submit_quiz - JSON decode error after unescape: ' . json_last_error_msg());
                    error_log('CM_Ajax::submit_quiz - Original: ' . $responses_raw);
                    error_log('CM_Ajax::submit_quiz - Unescaped: ' . $unescaped);
                }
                wp_send_json_error('Invalid responses format: ' . json_last_error_msg());
            } else {
                if (WP_DEBUG) {
                    error_log('CM_Ajax::submit_quiz - Successfully decoded after unescaping');
                }
            }
        }
    } else {
        $responses = $responses_raw;
    }

    if (empty($quiz_id) || empty($user_identifier) || empty($responses)) {
        wp_send_json_error('Missing required data');
    }

    foreach ($responses as $question_id => $response) {
        $qid = intval($question_id);
        if (!$qid) {
            continue;
        }

        // Sprawdź typ pytania, by poprawnie przetworzyć odpowiedź
        $question = CM_Database::get_row('quiz_questions', array('id' => $qid));
        if (!$question || intval($question->quiz_id) !== $quiz_id) {
            continue;
        }

        $selected_answer_ids = '';
        $text_response = '';

        if ($question->question_type === 'text') {
            // Odpowiedź tekstowa
            if (is_array($response) && array_key_exists('text', $response)) {
                $text_response = sanitize_textarea_field($response['text']);
            } elseif (is_string($response)) {
                $text_response = sanitize_textarea_field($response);
            }
        } else {
            // Odpowiedzi typu single/multiple
            if (is_array($response)) {
                $ids = array_map(
                    'intval',
                    array_filter($response, static function ($v) {
                        return $v !== '' && $v !== null;
                    })
                );
                $selected_answer_ids = implode(',', $ids);
            } else {
                $selected_answer_ids =
                    $response !== null && $response !== ''
                        ? (string) intval($response)
                        : '';
            }
        }

        $response_data = array(
            'quiz_id' => $quiz_id,
            'question_id' => $qid,
            'user_identifier' => $user_identifier,
            'selected_answer_ids' => $selected_answer_ids, // zawsze string
            'text_response' => $text_response, // zawsze string
            'participant_ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        );

        CM_Database::insert('user_responses', $response_data);
    }

    wp_cache_delete('cm_live_results_' . $quiz_id, 'cm_quiz');

    wp_send_json_success('Quiz submitted successfully');
}

    /**
     * Register participant (public action)
     */
    public function register_participant() {
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');

        if (!$quiz_id) {
            wp_send_json_error('Quiz ID jest wymagane');
        }

        // Basic nonce validation (allow both admin and public nonces)
        if (!wp_verify_nonce($nonce, 'cm_public_nonce') && !wp_verify_nonce($nonce, 'cm_admin_nonce')) {
            wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
        }

        // Get participant IP
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        if (empty($ip_address)) {
            wp_send_json_error('Nie można ustalić adresu IP');
        }

        // Load quiz
        $quiz = new CM_Quiz($quiz_id);
        if (!$quiz->get_id()) {
            wp_send_json_error('Quiz nie istnieje');
        }

        // Check if quiz is active
        if (!$quiz->get_is_active()) {
            wp_send_json_error('Quiz nie jest aktywny');
        }

        // Register participant
        $result = $quiz->register_participant($ip_address);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => 'Uczestnik został zarejestrowany',
            'participant_id' => $result,
            'quiz_id' => $quiz_id
        ));
    }

    /**
     * Get current time (public action)
     */
    public function get_current_time() {
        wp_send_json_success(array(
            'timestamp' => current_time('timestamp'),
            'formatted' => current_time('Y-m-d H:i:s')
        ));
    }

    /**
     * Get current presentation (public action)
     */
    public function get_current_presentation() {
        $event_id = intval($_GET['event_id'] ?? 0);
        
        if (empty($event_id)) {
            wp_send_json_error('Event ID required');
        }

        $presentation = CM_Lineup::get_active_presentation($event_id);
        
        if ($presentation) {
            wp_send_json_success($presentation);
        } else {
            wp_send_json_error('No active presentation');
        }
    }

    /**
     * Get quiz results (public action)
     */
    public function get_quiz_results() {
        $quiz_id = intval($_GET['quiz_id'] ?? 0);
        
        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID required');
        }

        $quiz = new CM_Quiz($quiz_id);
        $results = $quiz->get_results();
        
        wp_send_json_success($results);
    }

    /**
     * Cleanup unused files
     */
    public function cleanup_files() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $result = CM_File_Manager::cleanup_unused_files();
        
        if ($result) {
            wp_send_json_success('Unused files cleaned up successfully.');
        } else {
            wp_send_json_error('Failed to cleanup files.');
        }
    }

/**
 * Save quiz
 */
public function save_quiz() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
        wp_die('Security check failed');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    $event_id = intval(isset($_POST['event_id']) ? $_POST['event_id'] : 0);
    $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;

    // Validate required fields
    if (empty($event_id)) {
        wp_send_json_error('Event ID is required');
    }

    if (!isset($_POST['title']) || empty($_POST['title'])) {
        wp_send_json_error('Title is required');
    }

    $quiz_data = array(
        'event_id' => $event_id,
        'title' => sanitize_text_field($_POST['title']),
        'description' => sanitize_textarea_field(
            isset($_POST['description']) ? $_POST['description'] : ''
        ),
        'start_time' => !empty($_POST['start_time'])
            ? sanitize_text_field($_POST['start_time'])
            : '',
        'end_time' => !empty($_POST['end_time'])
            ? sanitize_text_field($_POST['end_time'])
            : '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    );

    if ($quiz_id > 0) {
        // Update existing quiz
        $result = CM_Database::update('quizzes', $quiz_data, array('id' => $quiz_id));
        $message = 'Quiz updated successfully';
    } else {
        // Create new quiz
        $quiz_data['created_at'] = current_time('mysql');
        $result = CM_Database::insert('quizzes', $quiz_data);
        $quiz_id = $result;
        $message = 'Quiz added successfully';
    }

    if (!is_wp_error($result) && $result !== false) {
        wp_send_json_success(array(
            'message' => $message,
            'quiz_id' => $quiz_id,
            'reload' => true
        ));
    } else {
        $error_message = 'Failed to save quiz';
        if (is_wp_error($result)) {
            $error_message .= ': ' . $result->get_error_message();
        }
        error_log(
            'CM_Ajax::save_quiz failed: ' .
                print_r($quiz_data, true) .
                ' Error: ' .
                $error_message
        );
        wp_send_json_error($error_message);
    }
}

    /**
     * Toggle quiz status
     */
    public function toggle_quiz_status() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_POST['quiz_id']);
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID required');
        }

        $result = CM_Database::update('quizzes', array('is_active' => $is_active), array('id' => $quiz_id));

        if ($result !== false) {
            wp_send_json_success('Quiz status updated successfully');
        } else {
            wp_send_json_error('Failed to update quiz status');
        }
    }

    /**
     * Get quiz questions
     */
    public function get_quiz_questions() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_GET['quiz_id'] ?? 0);

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID required');
        }

        $quiz = new CM_Quiz($quiz_id);
        $questions = $quiz->get_questions();

        // Debug: Log the questions found
        if (WP_DEBUG) {
            error_log('CM_Ajax::get_quiz_questions - Quiz ID: ' . $quiz_id . ', Questions count: ' . (is_array($questions) ? count($questions) : 'not array'));
            if (is_array($questions) && !empty($questions)) {
                foreach ($questions as $q) {
                    error_log('CM_Ajax::get_quiz_questions - Question: ID=' . $q->id . ', Text=' . substr($q->question, 0, 50));
                }
            }
        }

        if (is_array($questions)) {
            // Generate HTML for questions list
            $html = '<div class="cm-questions-list">';
            if (empty($questions)) {
                $html .= '<p class="cm-empty-questions">Ten quiz nie ma jeszcze żadnych pytań. Dodaj pierwsze pytanie!</p>';
            } else {
                foreach ($questions as $question) {
                    $html .= '<div class="cm-question-item" data-question-id="' . $question->id . '">';
                    $html .= '<h4>' . esc_html($question->question) . '</h4>';
                    $html .= '<span class="question-type">' . esc_html($question->question_type) . '</span>';
                    $html .= '<div class="question-actions">';
                    $html .= '<button type="button" class="button button-small cm-edit-question" data-question-id="' . $question->id . '">Edytuj</button>';
                    $html .= '<button type="button" class="button button-small button-link-delete cm-delete-question" data-question-id="' . $question->id . '">Usuń</button>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }
            $html .= '</div>';
            wp_send_json_success($html);
        } else {
            wp_send_json_error('Failed to load questions');
        }
    }

    /**
     * Get quiz data for editing
     */
    public function get_quiz_data() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_GET['quiz_id'] ?? 0);

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID required');
        }

        $quiz = CM_Database::get_row('quizzes', array('id' => $quiz_id));

        if ($quiz) {
            wp_send_json_success($quiz);
        } else {
            wp_send_json_error('Quiz not found');
        }
    }

    /**
     * Delete quiz
     */
    public function delete_quiz() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_POST['quiz_id']);

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID required');
        }

        $quiz = new CM_Quiz($quiz_id);
        $result = $quiz->delete();

        if ($result !== false) {
            wp_send_json_success('Quiz deleted successfully');
        } else {
            wp_send_json_error('Failed to delete quiz');
        }
    }

    /**
     * Reset quiz - remove all participant responses
     */
    public function reset_quiz() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_POST['quiz_id']);

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID required');
        }

        // Verify quiz exists
        $quiz = new CM_Quiz($quiz_id);
        if (!$quiz->get_id()) {
            wp_send_json_error('Quiz not found');
        }

        global $wpdb;
        $responses_table = CM_Database::get_table_name('user_responses');

        // Delete all responses for this quiz
        $result = $wpdb->delete($responses_table, array('quiz_id' => $quiz_id), array('%d'));

        if ($result !== false) {
            wp_send_json_success('Quiz reset successfully. All participant responses have been removed.');
        } else {
            wp_send_json_error('Failed to reset quiz');
        }
    }

    /**
     * Get question data for editing
     */
    public function get_question_data() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $question_id = intval($_GET['question_id'] ?? 0);

        if (empty($question_id)) {
            wp_send_json_error('Question ID required');
        }

        // Get question data
        $question = CM_Database::get_row('quiz_questions', array('id' => $question_id));
        
        if (!$question) {
            wp_send_json_error('Question not found');
        }

        // Get answers for this question
        $answers = CM_Database::get_results('quiz_answers', array('question_id' => $question_id), 'sort_order ASC');

        wp_send_json_success(array(
            'question' => $question,
            'answers' => $answers
        ));
    }

    /**
     * Get detailed quiz results
     */
    public function get_quiz_results_detailed() {
        // Clear any output buffer to prevent JSON corruption
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Suppress warnings/notices
        error_reporting(E_ERROR);
        
        // Log that function was called
        error_log('CM_Ajax::get_quiz_results_detailed called. POST: ' . print_r($_POST, true) . ' GET: ' . print_r($_GET, true));
        
        // Allow both admin and public access with different nonce validation
        $is_admin_request = current_user_can('manage_options');
        
        if ($is_admin_request) {
            // Admin request - require admin nonce and permissions
            if (!wp_verify_nonce($_GET['nonce'] ?? '', 'cm_admin_nonce')) {
                wp_send_json_error('Security check failed');
                return;
            }
        } else {
            // Public request - allow but validate basic nonce
            $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_GET['nonce']) ? $_GET['nonce'] : '');
            if (empty($nonce)) {
                wp_send_json_error('Security check failed - no nonce');
                return;
            }
            // Allow multiple nonce types for public access
            if (!wp_verify_nonce($nonce, 'cm_admin_nonce') && 
                !wp_verify_nonce($nonce, 'cm_public_nonce')) {
                wp_send_json_error('Security check failed - invalid nonce');
                return;
            }
        }

        $quiz_id = intval($_POST['quiz_id'] ?? $_GET['quiz_id'] ?? 0);

        if (empty($quiz_id)) {
            error_log('CM_Ajax::get_quiz_results_detailed - Missing quiz_id. POST: ' . print_r($_POST, true) . ' GET: ' . print_r($_GET, true));
            wp_send_json_error('Quiz ID required');
        }
        
        error_log('CM_Ajax::get_quiz_results_detailed - quiz_id: ' . $quiz_id . ', is_admin: ' . ($is_admin_request ? 'yes' : 'no'));

        global $wpdb;
        $quiz_table = CM_Database::get_table_name('quizzes');
        $questions_table = CM_Database::get_table_name('quiz_questions');
        $answers_table = CM_Database::get_table_name('quiz_answers');
        $responses_table = CM_Database::get_table_name('user_responses');

        // Get quiz info
        $quiz = CM_Database::get_row('quizzes', array('id' => $quiz_id));
        if (!$quiz) {
            wp_send_json_error('Quiz not found');
        }

        // Get basic statistics
        $stats = array();
        
        // Total responses
        $stats['total_responses'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$responses_table} WHERE quiz_id = %d",
            $quiz_id
        ));

        // Unique participants
        $stats['unique_participants'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_identifier) FROM {$responses_table} WHERE quiz_id = %d",
            $quiz_id
        ));

        // Get questions count
        $total_questions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$questions_table} WHERE quiz_id = %d",
            $quiz_id
        ));

        // Calculate completion rate (participants who answered all questions)
        if ($total_questions > 0 && $stats['unique_participants'] > 0) {
            $completed_participants = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_identifier) FROM {$responses_table} 
                 WHERE quiz_id = %d 
                 GROUP BY user_identifier 
                 HAVING COUNT(*) = %d",
                $quiz_id, $total_questions
            ));
            
            $stats['completion_rate'] = $stats['unique_participants'] > 0 ? 
                round(($completed_participants / $stats['unique_participants']) * 100) : 0;
        } else {
            $stats['completion_rate'] = 0;
        }

        // Get all questions for scoring calculation
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$questions_table} WHERE quiz_id = %d ORDER BY sort_order",
            $quiz_id
        ));

        // Get participant results grouped by user
        $participants_data = array();

        // DEBUG: Enhanced debug logging for participant data issue
        error_log('CM_Ajax::get_quiz_results_detailed - DEBUG: Starting participant data retrieval for quiz_id: ' . $quiz_id);

        // FIXED: Use user_identifier as participant_name since user_identifier IS the participant ID
        // The issue was using MAX(participant_name) which could pick wrong names from the group
        $participants_query = $wpdb->get_results($wpdb->prepare(
            "SELECT user_identifier,
                    user_identifier as participant_name,
                    MIN(submitted_at) as first_submission
             FROM {$responses_table}
             WHERE quiz_id = %d
             GROUP BY user_identifier
             ORDER BY first_submission",
            $quiz_id
        ));

        // Debug: Log participants query results with enhanced info
        error_log('CM_Ajax::get_quiz_results_detailed - DEBUG: participants_query result count: ' . count($participants_query));
        error_log('CM_Ajax::get_quiz_results_detailed - DEBUG: participants_query full result: ' . print_r($participants_query, true));

        // DEBUG: Log the actual SQL query being executed
        $debug_sql = $wpdb->prepare(
            "SELECT user_identifier,
                    user_identifier as participant_name,
                    MIN(submitted_at) as first_submission
             FROM {$responses_table}
             WHERE quiz_id = %d
             GROUP BY user_identifier
             ORDER BY first_submission",
            $quiz_id
        );
        error_log('CM_Ajax::get_quiz_results_detailed - DEBUG: SQL Query: ' . $debug_sql);

        // DEBUG: Check raw data in responses table for this quiz
        $raw_responses = $wpdb->get_results($wpdb->prepare(
            "SELECT user_identifier, participant_name, submitted_at FROM {$responses_table} WHERE quiz_id = %d ORDER BY submitted_at",
            $quiz_id
        ));
        error_log('CM_Ajax::get_quiz_results_detailed - DEBUG: Raw responses data: ' . print_r($raw_responses, true));

        $max_score = 0;
        $scored_questions_count = 0;

        // Count how many questions can be scored (non-text questions)
        foreach ($questions as $question) {
            if ($question->question_type === 'single' || $question->question_type === 'multiple') {
                $scored_questions_count++;
            }
        }

        foreach ($participants_query as $participant) {
            // Debug: Log each participant being processed
            error_log('CM_Ajax::get_quiz_results_detailed - processing participant: ' . $participant->user_identifier . ', name: ' . $participant->participant_name);

            $participant_data = array(
                'user_identifier' => $participant->user_identifier,
                'participant_name' => $participant->participant_name ?: 'Nieznany uczestnik',
                'submission_time' => $participant->first_submission,
                'responses' => array(),
                'correct_answers' => 0,
                'total_possible_answers' => $scored_questions_count,
                'score_percentage' => 0
            );

            // Get all responses for this participant
            $user_responses = $wpdb->get_results($wpdb->prepare(
                "SELECT question_id, selected_answer_ids, text_response 
                 FROM {$responses_table} 
                 WHERE quiz_id = %d AND user_identifier = %s 
                 ORDER BY question_id",
                $quiz_id, $participant->user_identifier
            ));

            // Process each question for this participant
            foreach ($questions as $question) {
                $response_found = false;
                $question_data = array(
                    'question_id' => $question->id,
                    'question_text' => $question->question,
                    'question_type' => $question->question_type,
                    'user_answer' => '',
                    'is_correct' => false
                );

                // Find response for this question
                foreach ($user_responses as $response) {
                    if ($response->question_id == $question->id) {
                        $response_found = true;

                        if ($question->question_type === 'text') {
                            $question_data['user_answer'] = $response->text_response;
                            $question_data['is_correct'] = null; // Text questions aren't scored
                        } else {
                            // Get correct answers for this question
                            $correct_answers = $wpdb->get_col($wpdb->prepare(
                                "SELECT id FROM {$answers_table} 
                                 WHERE question_id = %d AND is_correct = 1",
                                $question->id
                            ));

                            // Get user's selected answers - handle both comma-separated and single values
                            $selected_answer_ids_raw = trim($response->selected_answer_ids);
                            if (empty($selected_answer_ids_raw)) {
                                $selected_answers = array();
                            } else {
                                $selected_answers = array_filter(array_map('intval', explode(',', $selected_answer_ids_raw)));
                            }
                            
                            // Get answer texts
                            if (!empty($selected_answers)) {
                                $placeholders = implode(',', array_fill(0, count($selected_answers), '%d'));
                                $answer_texts = $wpdb->get_col($wpdb->prepare(
                                    "SELECT answer_text FROM {$answers_table} 
                                     WHERE id IN ({$placeholders})",
                                    ...$selected_answers
                                ));
                                $question_data['user_answer'] = implode(', ', $answer_texts);
                            } else {
                                $question_data['user_answer'] = 'Brak odpowiedzi';
                            }

                            // Check if answer is correct
                            if (empty($selected_answers) || empty($correct_answers)) {
                                $question_data['is_correct'] = false;
                            } else {
                                // Convert to strings for comparison
                                $selected_str = array_map('strval', $selected_answers);
                                $correct_str = array_map('strval', $correct_answers);
                                
                                // For single choice questions
                                if ($question->question_type === 'single') {
                                    $question_data['is_correct'] = (count($selected_answers) === 1 && 
                                                                   in_array($selected_str[0], $correct_str));
                                } 
                                // For multiple choice questions
                                else if ($question->question_type === 'multiple') {
                                    sort($selected_str);
                                    sort($correct_str);
                                    $question_data['is_correct'] = ($selected_str === $correct_str);
                                }
                            }
                            
                            if ($question_data['is_correct']) {
                                $participant_data['correct_answers']++;
                            }
                        }
                        break;
                    }
                }

                if (!$response_found) {
                    $question_data['user_answer'] = 'Brak odpowiedzi';
                }

                $participant_data['responses'][] = $question_data;
            }

            // Calculate score percentage
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

        // Calculate average score
        $total_score = 0;
        $participants_with_score = 0;
        foreach ($participants_data as $participant) {
            if ($participant['total_possible_answers'] > 0) {
                $total_score += $participant['score_percentage'];
                $participants_with_score++;
            }
        }
        $stats['average_score'] = $participants_with_score > 0 ? round($total_score / $participants_with_score) : 0;

        // Keep original question data for compatibility
        $questions_data = array();
        foreach ($questions as $question) {
            $question_data = array(
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->question_type
            );

            if ($question->question_type === 'text') {
                $text_responses = $wpdb->get_col($wpdb->prepare(
                    "SELECT text_response FROM {$responses_table} 
                     WHERE quiz_id = %d AND question_id = %d AND text_response IS NOT NULL",
                    $quiz_id, $question->id
                ));
                
                $question_data['responses'] = $text_responses;
                $question_data['total_responses'] = count($text_responses);
            } else {
                $answers = $wpdb->get_results($wpdb->prepare(
                    "SELECT a.*, 
                            COALESCE(response_counts.count, 0) as response_count
                     FROM {$answers_table} a
                     LEFT JOIN (
                         SELECT answer_id, COUNT(*) as count
                         FROM (
                             SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(selected_answer_ids, ',', numbers.n), ',', -1)) as answer_id
                             FROM {$responses_table}
                             CROSS JOIN (
                                 SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
                             ) numbers
                             WHERE quiz_id = %d AND question_id = %d
                             AND CHAR_LENGTH(selected_answer_ids) - CHAR_LENGTH(REPLACE(selected_answer_ids, ',', '')) >= numbers.n - 1
                         ) response_answers
                         WHERE answer_id != ''
                         GROUP BY answer_id
                     ) response_counts ON a.id = response_counts.answer_id
                     WHERE a.question_id = %d
                     ORDER BY a.sort_order",
                    $quiz_id, $question->id, $question->id
                ));

                $total_responses_for_question = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$responses_table} 
                     WHERE quiz_id = %d AND question_id = %d",
                    $quiz_id, $question->id
                ));

                $question_data['answers'] = $answers;
                $question_data['total_responses'] = $total_responses_for_question;
            }

            $questions_data[] = $question_data;
        }

        // Get leaderboard with time-based filtering
        $quiz_obj = new CM_Quiz($quiz_id);
        $leaderboard = $quiz_obj->get_leaderboard(20);

        // FIXED: Replace broken participants_data with correct data from leaderboard
        // The leaderboard logic works correctly, so let's use it for participants too
        $participants_data_fixed = array();
        foreach ($leaderboard as $leader) {
            // Convert leaderboard format to participants format
            $participant_fixed = array(
                'user_identifier' => $leader['identifier'],
                'participant_name' => $leader['name'], // This should be the same as identifier
                'submission_time' => $leader['completion_time'] ?? '',
                'correct_answers' => $leader['correct_answers'] ?? 0,
                'total_possible_answers' => $leader['total_questions'] ?? $scored_questions_count,
                'score_percentage' => $leader['score'] ?? 0,
                'is_top_scorer' => false,
                'questions' => array() // Add empty questions array for compatibility
            );
            $participants_data_fixed[] = $participant_fixed;
        }

        // Debug: Log both original broken data and fixed data
        error_log('CM_Ajax::get_quiz_results_detailed - BROKEN participants_data: ' . print_r($participants_data, true));
        error_log('CM_Ajax::get_quiz_results_detailed - FIXED participants_data: ' . print_r($participants_data_fixed, true));
        error_log('CM_Ajax::get_quiz_results_detailed - WORKING leaderboard: ' . print_r($leaderboard, true));

        // Use the fixed participants data
        $participants_data = $participants_data_fixed;

        wp_send_json_success(array(
            'quiz' => $quiz,
            'stats' => $stats,
            'questions' => $questions_data,
            'participants' => $participants_data,
            'leaderboard' => $leaderboard
        ));
    }

    /**
     * Export quiz results to CSV
     */
    public function export_quiz_results() {
    if (!wp_verify_nonce($_GET['nonce'] ?? '', 'cm_admin_nonce')) {
        wp_die('Security check failed');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    $quiz_id = intval($_GET['quiz_id'] ?? 0);

    if (empty($quiz_id)) {
        wp_die('Quiz ID required');
    }

    global $wpdb;
    $quiz = CM_Database::get_row('quizzes', array('id' => $quiz_id));
    if (!$quiz) {
        wp_die('Quiz not found');
    }

    // Wyczyść WSZYSTKIE bufory (eliminuje "headers already sent")
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    // Dodatkowo wyślij nagłówki anty-cache WP
    nocache_headers();

    $filename =
        'quiz_' . $quiz_id . '_results_' . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Strumień wyjściowy
    $output = fopen('php://output', 'w');

    // BOM dla UTF-8 (Excel)
    fputs($output, "\xEF\xBB\xBF");

    // Pobranie pytań i odpowiedzi
    $questions_table = CM_Database::get_table_name('quiz_questions');
    $responses_table = CM_Database::get_table_name('user_responses');

    // Pytania
    $questions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$questions_table} WHERE quiz_id = %d ORDER BY sort_order",
            $quiz_id
        )
    );

    // Nagłówki CSV
    $headers = array('Imię i Nazwisko', 'Data odpowiedzi');
    foreach ($questions as $question) {
        $headers[] =
            'Pytanie ' .
            $question->sort_order .
            ': ' .
            mb_substr($question->question, 0, 50);
    }
    fputcsv($output, $headers);

    // Odpowiedzi pogrupowane po użytkowniku
    $responses = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_identifier, participant_name, question_id, selected_answer_ids, text_response, submitted_at
             FROM {$responses_table}
             WHERE quiz_id = %d
             ORDER BY user_identifier, question_id",
            $quiz_id
        )
    );

    $user_responses = array();
    foreach ($responses as $response) {
        $user_responses[$response->user_identifier][$response->question_id] =
            $response;
    }

    $answers_table = CM_Database::get_table_name('quiz_answers');

    foreach ($user_responses as $user_id => $user_data) {
        // Get participant name from first response
        $first_response = reset($user_data);
        $participant_name = $first_response ? $first_response->participant_name : 'Nieznany uczestnik';
        
        $row = array($participant_name, '');

        // Data – pierwsza odpowiedź
        if ($first_response) {
            $row[1] = date(
                'Y-m-d H:i:s',
                strtotime($first_response->submitted_at)
            );
        }

        // Kolumny dla kolejnych pytań
        foreach ($questions as $question) {
            $response_value = '';

            if (isset($user_data[$question->id])) {
                $response = $user_data[$question->id];

                if ($question->question_type === 'text') {
                    $response_value = (string) $response->text_response;
                } else {
                    if (!empty($response->selected_answer_ids)) {
                        $answer_ids = array_filter(
                            array_map(
                                'intval',
                                explode(',', $response->selected_answer_ids)
                            ),
                            static function ($v) {
                                return $v !== 0;
                            }
                        );

                        $answer_texts = array();
                        foreach ($answer_ids as $answer_id) {
                            $answer = $wpdb->get_var(
                                $wpdb->prepare(
                                    "SELECT answer_text FROM {$answers_table} WHERE id = %d",
                                    $answer_id
                                )
                            );
                            if ($answer) {
                                $answer_texts[] = $answer;
                            }
                        }

                        $response_value = implode('; ', $answer_texts);
                    }
                }
            }

            $row[] = $response_value;
        }

        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

    /**
     * Submit quiz response (public)
     */
    public function submit_quiz_response() {
        // Verify nonce for public submissions
        if (!isset($_POST['quiz_nonce']) || !wp_verify_nonce($_POST['quiz_nonce'], 'cm_quiz_submission')) {
            wp_send_json_error('Invalid nonce');
        }

        $quiz_id = intval(isset($_POST['quiz_id']) ? $_POST['quiz_id'] : 0);
        if (!$quiz_id) {
            wp_send_json_error('Quiz ID required');
        }

        // Verify quiz exists and is active
        $quiz = new CM_Quiz($quiz_id);
        if (!$quiz->get_id() || !$quiz->get_is_active()) {
            wp_send_json_error('Quiz not found or not active');
        }

        // Check if quiz has ended
        if (!empty($quiz->get_end_time())) {
            $current_time = current_time('mysql');
            if ($current_time > $quiz->get_end_time()) {
                wp_send_json_error('Quiz time has expired');
            }
        }

        // Get participant name
        $participant_name = sanitize_text_field(isset($_POST['participant_name']) ? $_POST['participant_name'] : '');
        if (empty($participant_name)) {
            wp_send_json_error('Imię i nazwisko jest wymagane');
        }

        // Generate unique user identifier (IP + timestamp + random)
        $user_identifier = md5($_SERVER['REMOTE_ADDR'] . time() . rand());
        
        global $wpdb;
        $success_count = 0;
        $error_count = 0;

        // Process each question response
        foreach ($_POST as $key => $value) {
            if (is_string($key) && strpos($key, 'question_') === 0) {
                $question_id = intval(str_replace('question_', '', $key));
                
                if ($question_id > 0) {
                    $question = CM_Database::get_row('quiz_questions', array('id' => $question_id));
                    if ($question && $question->quiz_id == $quiz_id) {
                        
                        $response_data = array(
                            'quiz_id' => $quiz_id,
                            'question_id' => $question_id,
                            'user_identifier' => $user_identifier,
                            'participant_name' => $participant_name,
                            'submitted_at' => current_time('mysql')
                        );

                        if ($question->question_type === 'text') {
                            // Text response
                            $response_data['text_response'] = sanitize_textarea_field($value);
                            $response_data['selected_answer_ids'] = '';
                        } else {
                            // Choice response
                            if (is_array($value)) {
                                $response_data['selected_answer_ids'] = implode(',', array_map('intval', $value));
                            } else {
                                $response_data['selected_answer_ids'] = intval($value);
                            }
                            $response_data['text_response'] = '';
                        }

                        $result = CM_Database::insert('user_responses', $response_data);
                        if (!is_wp_error($result)) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    }
                }
            }
        }

        if ($success_count > 0) {
            wp_cache_delete('cm_live_results_' . $quiz_id, 'cm_quiz');

            wp_send_json_success(array(
                'message' => 'Responses submitted successfully',
                'responses_saved' => $success_count,
                'user_id' => $user_identifier
            ));
        } else {
            wp_send_json_error('Failed to save responses');
        }
    }

    /**
     * Get quiz results for public display
     */
    public function get_quiz_results_public() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_public_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $quiz_id = intval(isset($_POST['quiz_id']) ? $_POST['quiz_id'] : 0);
        if (!$quiz_id) {
            wp_send_json_error('Quiz ID required');
        }

        // Verify quiz exists
        $quiz = new CM_Quiz($quiz_id);
        if (!$quiz->get_id()) {
            wp_send_json_error('Quiz not found');
        }

        global $wpdb;
        
        // Get quiz statistics
        $responses_table = CM_Database::get_table_name('user_responses');
        $questions_table = CM_Database::get_table_name('quiz_questions');
        $answers_table = CM_Database::get_table_name('quiz_answers');
        
        // Get total participants
        $total_participants = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_identifier) FROM {$responses_table} WHERE quiz_id = %d",
            $quiz_id
        ));

        // Get total questions
        $total_questions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$questions_table} WHERE quiz_id = %d",
            $quiz_id
        ));

        // Get questions with answer statistics
        $questions_data = array();
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$questions_table} WHERE quiz_id = %d ORDER BY sort_order",
            $quiz_id
        ));

        $total_correct_answers = 0;
        $total_possible_answers = 0;

        foreach ($questions as $question) {
            $question_data = array(
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->question_type,
                'answers' => array()
            );

            if ($question->question_type !== 'text') {
                // Get answers for choice questions
                $answers = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$answers_table} WHERE question_id = %d ORDER BY sort_order",
                    $question->id
                ));

                foreach ($answers as $answer) {
                    // Count how many times this answer was selected
                    $answer_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$responses_table} 
                         WHERE question_id = %d AND (
                             selected_answer_ids = %d OR 
                             selected_answer_ids LIKE %s OR 
                             selected_answer_ids LIKE %s OR 
                             selected_answer_ids LIKE %s
                         )",
                        $question->id,
                        $answer->id,
                        $answer->id . ',%',
                        '%,' . $answer->id . ',%',
                        '%,' . $answer->id
                    ));

                    $percentage = $total_participants > 0 ? round(($answer_count / $total_participants) * 100, 1) : 0;
                    
                    $question_data['answers'][] = array(
                        'id' => $answer->id,
                        'answer_text' => $answer->answer_text,
                        'is_correct' => (bool) $answer->is_correct,
                        'count' => intval($answer_count),
                        'percentage' => $percentage
                    );

                    // Count correct answers for average calculation
                    if ($answer->is_correct) {
                        $total_correct_answers += intval($answer_count);
                    }
                }
                
                $total_possible_answers += $total_participants;
            }

            $questions_data[] = $question_data;
        }

        // Calculate average score
        $avg_score = $total_possible_answers > 0 ? round(($total_correct_answers / $total_possible_answers) * 100, 1) : 0;

        // Get participant results for leaderboard
        $participants_data = array();

        // DEBUG: Enhanced debug logging for participant data issue in public function
        error_log('CM_Ajax::get_quiz_results_public - DEBUG: Starting participant data retrieval for quiz_id: ' . $quiz_id);

        // FIXED: Use user_identifier as participant_name since user_identifier IS the participant ID
        // The issue was using MAX(participant_name) which could pick wrong names from the group
        $participants_query = $wpdb->get_results($wpdb->prepare(
            "SELECT user_identifier,
                    user_identifier as participant_name,
                    MIN(submitted_at) as first_submission
             FROM {$responses_table}
             WHERE quiz_id = %d
             GROUP BY user_identifier
             ORDER BY first_submission",
            $quiz_id
        ));

        // Debug: Log participants query results with enhanced info
        error_log('CM_Ajax::get_quiz_results_public - DEBUG: participants_query result count: ' . count($participants_query));
        error_log('CM_Ajax::get_quiz_results_public - DEBUG: participants_query full result: ' . print_r($participants_query, true));

        // Count scored questions (non-text questions)
        $scored_questions_count = 0;
        foreach ($questions as $question) {
            if ($question->question_type === 'single' || $question->question_type === 'multiple') {
                $scored_questions_count++;
            }
        }

        foreach ($participants_query as $participant) {
            $participant_data = array(
                'user_identifier' => $participant->user_identifier,
                'participant_name' => $participant->participant_name ?: 'Nieznany uczestnik',
                'submission_time' => $participant->first_submission,
                'correct_answers' => 0,
                'total_possible_answers' => $scored_questions_count,
                'score_percentage' => 0,
                'is_top_scorer' => false
            );

            // Get all responses for this participant
            $user_responses = $wpdb->get_results($wpdb->prepare(
                "SELECT question_id, selected_answer_ids
                 FROM {$responses_table}
                 WHERE quiz_id = %d AND user_identifier = %s
                 ORDER BY question_id",
                $quiz_id, $participant->user_identifier
            ));

            // Calculate correct answers
            foreach ($questions as $question) {
                if ($question->question_type === 'text') continue;

                // Find response for this question
                foreach ($user_responses as $response) {
                    if ($response->question_id == $question->id) {
                        // Get correct answers for this question
                        $correct_answers = $wpdb->get_col($wpdb->prepare(
                            "SELECT id FROM {$answers_table}
                             WHERE question_id = %d AND is_correct = 1",
                            $question->id
                        ));

                        // Get user's selected answers
                        $selected_answer_ids_raw = trim($response->selected_answer_ids);
                        if (empty($selected_answer_ids_raw)) {
                            $selected_answers = array();
                        } else {
                            $selected_answers = array_filter(array_map('intval', explode(',', $selected_answer_ids_raw)));
                        }

                        // Check if answer is correct
                        if (!empty($selected_answers) && !empty($correct_answers)) {
                            $selected_str = array_map('strval', $selected_answers);
                            $correct_str = array_map('strval', $correct_answers);

                            if ($question->question_type === 'single') {
                                $is_correct = (count($selected_answers) === 1 &&
                                             in_array($selected_str[0], $correct_str));
                            } else if ($question->question_type === 'multiple') {
                                sort($selected_str);
                                sort($correct_str);
                                $is_correct = ($selected_str === $correct_str);
                            }

                            if ($is_correct) {
                                $participant_data['correct_answers']++;
                            }
                        }
                        break;
                    }
                }
            }

            // Calculate score percentage
            if ($participant_data['total_possible_answers'] > 0) {
                $participant_data['score_percentage'] = round(
                    ($participant_data['correct_answers'] / $participant_data['total_possible_answers']) * 100
                );
            }

            $participants_data[] = $participant_data;
        }

        // Mark participants with the highest score
        $max_score = 0;
        foreach ($participants_data as $participant) {
            $max_score = max($max_score, $participant['score_percentage']);
        }
        foreach ($participants_data as &$participant) {
            $participant['is_top_scorer'] = ($participant['score_percentage'] == $max_score && $max_score > 0);
        }

        $result = array(
            'quiz_title' => $quiz->get_title(),
            'total_participants' => intval($total_participants),
            'total_questions' => intval($total_questions),
            'avg_score' => $avg_score,
            'questions' => $questions_data,
            'participants' => $participants_data
        );

        wp_send_json_success($result);
    }

    /**
     * Save quiz question
     */
    public function save_quiz_question() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $question_id = intval($_POST['question_id'] ?? 0);

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID is required adsd');
        }

        if (empty($_POST['question'])) {
            wp_send_json_error('Question text is required');
        }

        if (empty($_POST['question_type'])) {
            wp_send_json_error('Question type is required');
        }

        $question_data = array(
            'quiz_id' => $quiz_id,
            'question' => sanitize_textarea_field($_POST['question']),
            'question_type' => sanitize_text_field($_POST['question_type']),
            'sort_order' => intval($_POST['sort_order'] ?? 0)
        );

        if ($question_id > 0) {
            // Update existing question
            $result = CM_Database::update('quiz_questions', $question_data, array('id' => $question_id));
            $message = 'Question updated successfully';
            $current_question_id = $question_id;
        } else {
            // Create new question
            $question_data['created_at'] = current_time('mysql');
            $result = CM_Database::insert('quiz_questions', $question_data);
            $current_question_id = $result;
            $message = 'Question added successfully';
        }

        if (!is_wp_error($result) && $result !== false) {
            // Handle answers for choice questions
            if (in_array($_POST['question_type'], ['single', 'multiple']) && isset($_POST['answers'])) {
                // Delete existing answers
                CM_Database::delete('quiz_answers', array('question_id' => $current_question_id));
                
                // Get correct answers from form - handle different formats
                $correct_answers = array();
                if ($_POST['question_type'] === 'single') {
                    if (isset($_POST['correct_answer'])) {
                        $correct_answers = array(intval($_POST['correct_answer']));
                    } elseif (isset($_POST['correct_answers'])) {
                        // Handle single correct answer sent as 'correct_answers'
                        if (is_array($_POST['correct_answers'])) {
                            $correct_answers = array_map('intval', $_POST['correct_answers']);
                        } else {
                            $correct_answers = array(intval($_POST['correct_answers']));
                        }
                    }
                } elseif ($_POST['question_type'] === 'multiple' && isset($_POST['correct_answers'])) {
                    if (is_array($_POST['correct_answers'])) {
                        $correct_answers = array_map('intval', $_POST['correct_answers']);
                    } else {
                        $correct_answers = array(intval($_POST['correct_answers']));
                    }
                }
                
                // Add new answers
                foreach ($_POST['answers'] as $index => $answer) {
                    // Handle both string format and array format
                    $answer_text = '';
                    if (is_string($answer)) {
                        $answer_text = $answer; // Simple string format: answers[] = "text"
                    } elseif (is_array($answer) && isset($answer['text'])) {
                        $answer_text = $answer['text']; // Array format: answers[0][text] = "text"
                    }

                    if (!empty($answer_text)) {
                        $is_correct = in_array($index, $correct_answers) ? 1 : 0;

                        $answer_data = array(
                            'question_id' => $current_question_id,
                            'answer_text' => sanitize_textarea_field($answer_text),
                            'is_correct' => $is_correct,
                            'sort_order' => $index + 1,
                            'created_at' => current_time('mysql')
                        );
                        CM_Database::insert('quiz_answers', $answer_data);
                    }
                }
            }

            wp_send_json_success(array(
                'message' => $message,
                'question_id' => $current_question_id
            ));
        } else {
            $error_message = 'Failed to save question';
            if (is_wp_error($result)) {
                $error_message .= ': ' . $result->get_error_message();
            }
            error_log('CM_Ajax::save_quiz_question failed: ' . print_r($question_data, true) . ' Error: ' . $error_message);
            wp_send_json_error($error_message);
        }
    }

    /**
     * Set quiz display mode for display page (results or QR)
     */
    public function set_quiz_display_mode() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $lineup_id = intval($_POST['lineup_id'] ?? 0);
        $mode = sanitize_text_field($_POST['mode'] ?? 'qr');

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID is required3123');
        }

        if (!in_array($mode, ['results', 'qr'])) {
            wp_send_json_error('Invalid display mode');
        }

        if ($mode === 'results') {
            // Set transient to show results mode
            set_transient("quiz_display_mode_$quiz_id", 'results', 3600); // 1 hour
            set_transient('cm_quiz_results_lineup_' . $quiz_id, $lineup_id, 3600);
            error_log("CM_Ajax::set_quiz_display_mode - Set quiz_display_mode_$quiz_id to 'results'");
            $message = 'Results mode activated';
            $url = home_url('/quiz/');
        } else {
            // Set transient to show QR mode
            set_transient("quiz_display_mode_$quiz_id", 'qr', 3600); // 1 hour
            delete_transient('cm_quiz_results_lineup_' . $quiz_id);
            error_log("CM_Ajax::set_quiz_display_mode - Set quiz_display_mode_$quiz_id to 'qr'");
            $message = 'QR mode activated';
            $url = home_url('/quiz/');
        }

        wp_send_json_success(array(
            'message' => $message,
            'mode' => $mode,
            'url' => $url
        ));
    }

    /**
     * Get quiz display mode for polling
     */
    public function get_quiz_display_mode() {
        // Log that the function was called
        error_log('CM_Ajax::get_quiz_display_mode called. POST: ' . print_r($_POST, true));
        
        // Allow both admin and public access
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_GET['nonce']) ? $_GET['nonce'] : '');
        if (empty($nonce) || (!wp_verify_nonce($nonce, 'cm_admin_nonce') && !wp_verify_nonce($nonce, 'cm_public_nonce'))) {
            error_log('CM_Ajax::get_quiz_display_mode - Security check failed. Nonce: ' . $nonce);
            wp_send_json_error('Security check failed');
            return;
        }

        $quiz_id = isset($_POST['quiz_id']) ? $_POST['quiz_id'] : (isset($_GET['quiz_id']) ? $_GET['quiz_id'] : '');
        
        // Handle "current" quiz_id by finding the active presentation
        if ($quiz_id === 'current' || empty($quiz_id)) {
            // Find current active presentation with quiz
            $presentation = $this->get_current_active_presentation_with_quiz();
            if ($presentation && $presentation['quiz_id']) {
                $quiz_id = $presentation['quiz_id'];
            } else {
                wp_send_json_success(array('mode' => 'qr', 'quiz_id' => null));
                return;
            }
        }

        // Get current mode from transient
        $mode = get_transient("quiz_display_mode_$quiz_id");
        if (empty($mode)) {
            $mode = 'qr'; // Default to QR mode
        }
        
        error_log('CM_Ajax::get_quiz_display_mode - Returning mode: ' . $mode . ' for quiz_id: ' . $quiz_id);

        wp_send_json_success(array(
            'mode' => $mode,
            'quiz_id' => intval($quiz_id)
        ));
    }

    /**
     * Helper to get current active presentation with quiz
     */
    private function get_current_active_presentation_with_quiz() {
        try {
            // Get all active events
            $active_events = CM_Event::get_all('active');
            if (empty($active_events)) {
                return null;
            }

            // Look for active presentation in each active event
            foreach ($active_events as $event) {
                $presentation = CM_Lineup::get_active_presentation($event->id);
                if ($presentation && !empty($presentation->quiz_id)) {
                    return array(
                        'presentation_id' => $presentation->id,
                        'quiz_id' => $presentation->quiz_id,
                        'event_id' => $event->id
                    );
                }
            }

            return null;
        } catch (Exception $e) {
            error_log('CM_Ajax::get_current_active_presentation_with_quiz error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * SSE endpoint for live quiz updates
     */
    /**
     * Get test nonce for load testing
     */
    public function get_test_nonce() {
        wp_send_json_success(array(
            'nonce' => wp_create_nonce('cm_sse_nonce'),
            'public_nonce' => wp_create_nonce('cm_public_nonce')
        ));
    }

    public function quiz_live_updates() {
        try {
            error_log('CM_AJAX: SSE endpoint called with: ' . json_encode($_GET));

            // Check if SSE Controller class exists
            if (!class_exists('CM_SSE_Controller')) {
                error_log('CM_AJAX: CM_SSE_Controller class not found');
                wp_die('SSE Controller not available', 'Service Unavailable', array('response' => 503));
                return;
            }

            // Get parameters
            $event_id = intval($_GET['event_id'] ?? 0);
            $quiz_id = intval($_GET['quiz_id'] ?? 0);

            // Validate parameters
            if (!$event_id || !$quiz_id) {
                error_log('CM_AJAX: Missing parameters - event_id: ' . $event_id . ', quiz_id: ' . $quiz_id);
                wp_die('Missing required parameters', 'Bad Request', array('response' => 400));
                return;
            }
            
            // Start SSE stream
            CM_SSE_Controller::stream_quiz_updates($event_id, $quiz_id);
            
        } catch (Exception $e) {
            error_log('CM_AJAX: SSE error: ' . $e->getMessage());
            wp_die('SSE Error: ' . $e->getMessage(), 'Internal Server Error', array('response' => 500));
        } catch (Error $e) {
            error_log('CM_AJAX: SSE fatal error: ' . $e->getMessage());
            wp_die('SSE Fatal Error: ' . $e->getMessage(), 'Internal Server Error', array('response' => 500));
        }
    }

    /**
     * Set quiz display mode using new state system
     */
    public function set_quiz_state_mode() {
        // Check nonce - allow both admin and public nonces
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'cm_admin_nonce') && 
            !wp_verify_nonce($nonce, 'cm_public_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $mode = sanitize_text_field($_POST['mode'] ?? 'qr');

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID is requiredhhrh');
        }

        if (!in_array($mode, ['qr', 'results'])) {
            wp_send_json_error('Invalid display mode');
        }

        $quiz_state = CM_Quiz_State::get_state($quiz_id);
        if (!$quiz_state) {
            $quiz_state = CM_Quiz_State::create_state($quiz_id);
        }

        if ($quiz_state && $quiz_state->set_mode($mode)) {
            wp_send_json_success(array(
                'message' => 'Mode updated successfully',
                'mode' => $mode,
                'quiz_id' => $quiz_id
            ));
        } else {
            wp_send_json_error('Failed to update mode');
        }
    }

    /**
     * Configure auto-switch settings
     */
    public function configure_auto_switch() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $enabled = isset($_POST['enabled']) ? (bool) $_POST['enabled'] : false;
        $delay = intval($_POST['delay'] ?? 30);

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID is required7u67');
        }

        if ($delay < 5 || $delay > 300) {
            wp_send_json_error('Delay must be between 5 and 300 seconds');
        }

        $quiz_state = CM_Quiz_State::get_state($quiz_id);
        if (!$quiz_state) {
            $quiz_state = CM_Quiz_State::create_state($quiz_id);
        }

        if ($quiz_state && $quiz_state->set_auto_switch($enabled, $delay)) {
            wp_send_json_success(array(
                'message' => 'Auto-switch configured successfully',
                'enabled' => $enabled,
                'delay' => $delay,
                'quiz_id' => $quiz_id
            ));
        } else {
            wp_send_json_error('Failed to configure auto-switch');
        }
    }

    /**
     * Toggle quiz mode (QR <-> Results)
     */
    public function toggle_quiz_mode() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $quiz_id = intval($_POST['quiz_id'] ?? 0);

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID is required');
        }

        $quiz_state = CM_Quiz_State::get_state($quiz_id);
        if (!$quiz_state) {
            $quiz_state = CM_Quiz_State::create_state($quiz_id);
        }

        if ($quiz_state) {
            $new_mode = $quiz_state->toggle_mode();
            wp_send_json_success(array(
                'message' => 'Mode toggled successfully',
                'mode' => $new_mode,
                'quiz_id' => $quiz_id
            ));
        } else {
            wp_send_json_error('Failed to toggle mode');
        }
    }

    /**
     * Get current quiz mode
     */
    public function get_quiz_current_mode() {
        // Allow both admin and public access
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_GET['nonce']) ? $_GET['nonce'] : '');
        if (empty($nonce) || (!wp_verify_nonce($nonce, 'cm_admin_nonce') && !wp_verify_nonce($nonce, 'cm_public_nonce'))) {
            wp_send_json_error('Security check failed');
            return;
        }

        $quiz_id = intval($_POST['quiz_id'] ?? $_GET['quiz_id'] ?? 0);

        if (empty($quiz_id)) {
            wp_send_json_error('Quiz ID is required');
        }

        $quiz_state = CM_Quiz_State::get_state($quiz_id);
        if (!$quiz_state) {
            $quiz_state = CM_Quiz_State::create_state($quiz_id);
        }

        if ($quiz_state) {
            $state_data = $quiz_state->get_state_data();
            wp_send_json_success(array(
                'mode' => $quiz_state->get_mode(),
                'auto_switch_enabled' => $quiz_state->is_auto_switch_enabled(),
                'auto_switch_delay' => $quiz_state->get_auto_switch_delay(),
                'last_updated' => $quiz_state->get_last_updated(),
                'quiz_id' => $quiz_id
            ));
        } else {
            wp_send_json_error('Failed to get quiz state');
        }
    }

    /**
     * Notify about participant joining (triggers SSE event)
     */
    public function participant_join_notification() {
        // Basic validation for public endpoint
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $participant_name = sanitize_text_field($_POST['participant_name'] ?? '');

        if (!$quiz_id || !$participant_name) {
            wp_send_json_error('Missing required data');
        }

        // Trigger SSE event for new participant
        $data = array(
            'quiz_id' => $quiz_id,
            'participant_name' => $participant_name,
            'timestamp' => current_time('mysql')
        );

        // This would be handled by the SSE controller
        CM_SSE_Controller::broadcast_to_quiz($quiz_id, 'participant-joined', $data);

        wp_send_json_success(array(
            'message' => 'Notification sent',
            'participant' => $participant_name
        ));
    }
    
    /**
     * Run database migration manually
     */
    public function run_migration() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cm_admin_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        try {
            error_log('CM_AJAX: Manual migration requested');
            
            require_once plugin_dir_path(dirname(__FILE__)) . 'class-activator.php';
            CM_Activator::update_lineup_table_schema();
            
            // Force update migration version
            update_option('cm_db_migration_version', 3);
            
            error_log('CM_AJAX: Manual migration completed successfully');
            
            wp_send_json_success(array(
                'message' => 'Migration completed successfully'
            ));
        } catch (Exception $e) {
            error_log('CM_AJAX: Migration error: ' . $e->getMessage());
            wp_send_json_error('Migration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get quiz participant count
     */
    public function get_quiz_participant_count() {
        if (!wp_verify_nonce($_GET['nonce'] ?? '', 'cm_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $quiz_id = intval($_GET['quiz_id'] ?? 0);
        if (!$quiz_id) {
            wp_send_json_error('Quiz ID is required');
        }

        try {
            global $wpdb;
            $table_responses = CM_Database::get_table_name('user_responses');
            
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT user_identifier)
                FROM {$table_responses} 
                WHERE quiz_id = %d
            ", $quiz_id));

            wp_send_json_success(array(
                'count' => intval($count),
                'quiz_id' => $quiz_id
            ));
        } catch (Exception $e) {
            error_log('CM_AJAX: Error getting participant count: ' . $e->getMessage());
            wp_send_json_error('Failed to get participant count');
        }
    }

    /**
     * Public version of set quiz state mode (for non-admin users from quiz display)
     */
    public function set_quiz_state_mode_public() {
        // Only allow if user is logged in as admin or request comes from admin interface
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Verify nonce  
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_public_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Call the main function
        $this->set_quiz_state_mode();
    }

    /**
     * Public version of toggle quiz mode (for non-admin users from quiz display)
     */
    public function toggle_quiz_mode_public() {
        // Only allow if user is logged in as admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_public_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Call the main function
        $this->toggle_quiz_mode();
    }
    
    /**
     * Get QR code for quiz display
     */
    public function get_quiz_qr_code() {
        // Verify nonce if provided (optional for public access)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'cm_public_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        
        if (!$quiz_id) {
            wp_send_json_error('Quiz ID is required');
        }
        
        // Get existing QR code for this quiz
        $qr_codes = CM_Database::get_results('qr_codes', array(
            'code_type' => 'quiz',
            'target_id' => $quiz_id
        ), 'created_at DESC', 1);
        
        if (!empty($qr_codes)) {
            $qr_code = $qr_codes[0];
            $qr_url = CM_QR_Generator::get_qr_url($qr_code->file_path);
            
            if (!empty($qr_url)) {
                wp_send_json_success(array(
                    'qr_url' => $qr_url,
                    'quiz_url' => $qr_code->qr_data
                ));
            }
        }
        
        // No existing QR code found
        wp_send_json_error('No QR code found for this quiz');
    }
    
    /**
     * Generate new QR code for quiz
     */
    public function generate_quiz_qr() {
        // Verify nonce if provided (optional for public access)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'cm_public_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        
        if (!$quiz_id) {
            wp_send_json_error('Quiz ID is required');
        }
        
        // Verify quiz exists
        $quiz = CM_Database::get_row('quizzes', array('id' => $quiz_id));
        if (!$quiz) {
            wp_send_json_error('Quiz not found');
        }
        
        // Generate QR code
        $result = CM_QR_Generator::generate_quiz_qr($quiz_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        if (isset($result['url'])) {
            wp_send_json_success(array(
                'qr_url' => $result['url'],
                'quiz_url' => $result['data']
            ));
        }
        
        wp_send_json_error('Failed to generate QR code');
    }

    /**
     * Get quiz leaderboard with time-based filtering
     */
    public function get_quiz_leaderboard() {
        // Allow both admin and public access
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_GET['nonce']) ? $_GET['nonce'] : '');
        if (!empty($nonce) && !wp_verify_nonce($nonce, 'cm_admin_nonce') && !wp_verify_nonce($nonce, 'cm_public_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : (isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0);

        if (!$quiz_id) {
            wp_send_json_error('Quiz ID is required');
        }

        // Load quiz
        $quiz = new CM_Quiz($quiz_id);
        if (!$quiz->get_id()) {
            wp_send_json_error('Quiz not found');
        }

        // Get leaderboard
        $leaderboard = $quiz->get_leaderboard(20);

        wp_send_json_success(array(
            'leaderboard' => $leaderboard,
            'quiz_title' => $quiz->get_title(),
            'actual_start_time' => $quiz->get_actual_start_time()
        ));
    }

    /**
     * Run database migration manually (admin only)
     */
    public function run_database_migration() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }

        try {
            // Run the database schema update
            CM_Activator::update_lineup_table_schema();

            wp_send_json_success('Database migration completed successfully');
        } catch (Exception $e) {
            wp_send_json_error('Migration failed: ' . $e->getMessage());
        }
    }

    public function event_live_updates() {
        error_log('CM_AJAX: event_live_updates called with event_id: ' . ($_GET['event_id'] ?? 'none'));

        $event_id = intval($_GET['event_id'] ?? 0);
        if (empty($event_id)) {
            wp_die('Event ID is required');
        }

        if (!class_exists('CM_SSE_Controller')) {
            wp_die('SSE Controller not available');
        }

        CM_SSE_Controller::stream_event_updates($event_id);
        exit;
    }

    /**
     * Check for time conflicts when adding/editing lineup items
     */
    public function check_time_conflict() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $event_id = intval($_POST['event_id'] ?? 0);
        $start_time = sanitize_text_field($_POST['start_time'] ?? '');
        $duration_minutes = intval($_POST['duration_minutes'] ?? 30); // Default 30 minutes
        $lineup_id = !empty($_POST['lineup_id']) ? intval($_POST['lineup_id']) : null;
        $day_number = intval($_POST['day_number'] ?? 1);

        if (empty($event_id) || empty($start_time)) {
            wp_send_json_error('Event ID and start time are required');
        }

        $conflict_check = CM_Lineup::has_time_conflict($event_id, $start_time, $duration_minutes, $lineup_id, $day_number);

        wp_send_json_success(array(
            'has_conflict' => $conflict_check['has_conflict']
        ));
    }

    /**
     * Enhanced time conflict check for quick events with duration
     */
    public function check_time_conflict_extended() {
        check_ajax_referer('cm_ajax_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Brak uprawnień', 'conference-manager'));
        }

        $event_id = intval($_POST['event_id'] ?? 0);
        $start_time = sanitize_text_field($_POST['start_time'] ?? '');
        $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
        $exclude_id = !empty($_POST['exclude_id']) ? intval($_POST['exclude_id']) : null;
        $day_number = intval($_POST['day_number'] ?? 1);

        if (empty($event_id) || empty($start_time) || empty($duration_minutes)) {
            wp_send_json_error(__('Wszystkie pola są wymagane', 'conference-manager'));
        }

        error_log("CM_Ajax: Extended conflict check - event {$event_id}, day {$day_number}, time {$start_time}, duration {$duration_minutes}");

        try {
            $normalized_time = CM_Lineup::normalize_time($start_time);
        } catch (InvalidArgumentException $e) {
            wp_send_json_error($e->getMessage());
        }

        $conflict_check = CM_Lineup::has_time_conflict($event_id, $normalized_time, $duration_minutes, $exclude_id, $day_number);

        if ($conflict_check['has_conflict']) {
            $conflicting = $conflict_check['conflicting_item'];
            wp_send_json_success(array(
                'has_conflict' => true,
                'message' => sprintf(
                    __('Konflikt z wydarzeniem "%s" o %s', 'conference-manager'),
                    $conflicting->title ?? __('Bez tytułu', 'conference-manager'),
                    $conflicting->start_time
                )
            ));
        } else {
            wp_send_json_success(array('has_conflict' => false));
        }
    }

    /**
     * Save quick event with proper security and validation
     */
    public function save_quick_event() {
        check_ajax_referer('cm_ajax_nonce', 'nonce');

        $event_id = intval($_POST['event_id'] ?? 0);
        if (!current_user_can('edit_posts') || !$this->user_can_edit_event($event_id)) {
            wp_send_json_error(__('Brak uprawnień do edycji tego wydarzenia', 'conference-manager'));
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        if (empty($title)) {
            wp_send_json_error(__('Tytuł wydarzenia jest wymagany', 'conference-manager'));
        }

        $start_time = sanitize_text_field($_POST['start_time'] ?? '');
        try {
            $normalized_time = CM_Lineup::normalize_time($start_time);
        } catch (InvalidArgumentException $e) {
            wp_send_json_error($e->getMessage());
        }

        $duration = intval($_POST['duration_minutes'] ?? 0);
        // Allow any duration that's a multiple of 5 minutes, up to 300 minutes (5 hours)
        if ($duration < 5 || $duration > 300 || $duration % 5 !== 0) {
            wp_send_json_error(__('Czas trwania musi być wielokrotnością 5 minut (5-300 min)', 'conference-manager'));
        }

        $day_number = intval($_POST['day_number'] ?? 1);
        if ($day_number < 1 || $day_number > 7) {
            wp_send_json_error(__('Nieprawidłowy numer dnia wydarzenia', 'conference-manager'));
        }

        error_log("CM_Ajax: Creating quick event '{$title}' for event {$event_id} at {$normalized_time}, duration {$duration}min, day {$day_number}");

        // For quick events, we don't check conflicts - we push subsequent events forward
        // First, update start times of events that start at or after the insertion time
        CM_Lineup::update_subsequent_event_times($event_id, $normalized_time, $duration, $day_number);

        $lineup = CM_Lineup::create_quick_event($event_id, $title, $normalized_time, $duration, $day_number);
        $result = $lineup->save();

        if (is_wp_error($result)) {
            error_log("CM_Ajax: Quick event save failed: " . $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        }

        error_log("CM_Ajax: Quick event created successfully with ID " . $lineup->get_id());

        // Get updated lineup for return - only active day
        $event = new CM_Event($event_id);
        $active_day = $event->get_current_active_day();
        $updated_lineup = CM_Lineup::get_by_event_and_day($event_id, $active_day);

        $lineup_data = array(
            'id' => $lineup->get_id(),
            'title' => $lineup->get_title(),
            'start_time' => $lineup->get_start_time(),
            'duration_minutes' => $lineup->get_duration_minutes(),
            'event_type' => 'quick',
            'sort_order' => $lineup->get_sort_order()
        );

        ob_start();
        $item = (object) $lineup_data;
        include CONFERENCE_MANAGER_PLUGIN_PATH . 'admin/partials/lineup-item-template.php';
        $html = ob_get_clean();

        // Prepare timeline data for updated times
        $timeline = array_map(function($item) {
            return array(
                'id' => $item->id,
                'start_time' => $item->start_time,
                'end_time' => date('H:i:s', strtotime($item->start_time) + ($item->duration_minutes * 60))
            );
        }, $updated_lineup);

        // Count how many events were pushed forward
        $pushed_count = 0;
        foreach ($timeline as $item) {
            if ($item['id'] != $lineup->get_id() && $item['start_time'] > $normalized_time) {
                $pushed_count++;
            }
        }

        $success_message = __('Szybkie wydarzenie zostało dodane.', 'conference-manager');
        if ($pushed_count > 0) {
            $success_message .= ' ' . sprintf(
                _n('Przesunięto %d następne wydarzenie.', 'Przesunięto %d następnych wydarzeń.', $pushed_count, 'conference-manager'),
                $pushed_count
            );
        }

        wp_send_json_success(array(
            'lineup' => $lineup_data,
            'html' => $html,
            'timeline' => $timeline,
            'message' => $success_message
        ));
    }

    /**
     * Recalculate timeline after drag and drop reordering
     */
    public function recalculate_timeline_after_sort() {
        check_ajax_referer('cm_ajax_nonce', 'nonce');

        $event_id = intval($_POST['event_id'] ?? 0);
        if (!$this->user_can_edit_event($event_id)) {
            wp_send_json_error(__('Brak uprawnień', 'conference-manager'));
        }

        error_log("CM_Ajax: Recalculating timeline for event {$event_id}");

        CM_Lineup::calculate_timeline_from_sort_order($event_id);

        $event = new CM_Event($event_id);
        $active_day = $event->get_current_active_day();
        $updated_items = CM_Lineup::get_by_event_and_day($event_id, $active_day);
        wp_send_json_success(array(
            'timeline' => array_map(function($item) {
                return array(
                    'id' => $item->id,
                    'start_time' => $item->start_time,
                    'end_time' => date('H:i:s', strtotime($item->start_time) + ($item->duration_minutes * 60))
                );
            }, $updated_items)
        ));
    }

    /**
     * Check if user can edit specific event
     */
    private function user_can_edit_event($event_id) {
        // Check if user has edit_posts capability
        if (!current_user_can('edit_posts')) {
            return false;
        }

        // Check if event exists in cm_events table
        global $wpdb;
        $table_name = $wpdb->prefix . 'cm_events';
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE id = %d",
            $event_id
        ));

        return $event !== null;
    }

    /**
     * Clear cached presentation start times for an event
     * Manual cache clearing function accessible via AJAX
     */
    public function clear_time_cache() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cm_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $event_id = intval($_POST['event_id'] ?? 0);

        if (empty($event_id)) {
            wp_send_json_error('Event ID is required');
        }

        // Clear the cache
        $result = CM_Lineup::clear_time_cache($event_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Cache cleared successfully',
                'event_id' => $event_id
            ));
        } else {
            wp_send_json_error('Failed to clear cache');
        }
    }

    /**
     * Add a new day to an event
     */
    public function add_event_day() {
        check_ajax_referer('cm_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Brak uprawnień');
        }

        $event_id = intval($_POST['event_id']);
        $event = new CM_Event($event_id);

        if (!$event->exists()) {
            wp_send_json_error('Wydarzenie nie istnieje');
        }

        $new_day = $event->add_day();
        if ($new_day) {
            error_log("CM_Ajax: Added day {$new_day} to event {$event_id}");

            // Generate HTML for all tabs when adding second day (show day 1 tab too)
            $tabs_html = '';
            if ($new_day == 2) {
                // This is the second day, so we need to show day 1 tab too
                $tabs_html .= '<button class="day-tab px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-day="1">Dzień 1</button>';
            }
            $tabs_html .= '<button class="day-tab px-4 py-2 border-b-2 border-blue-500 text-blue-600" data-day="' . $new_day . '">Dzień ' . $new_day . '</button>';

            wp_send_json_success(array(
                'day_number' => $new_day,
                'tabs_html' => $tabs_html,
                'is_second_day' => $new_day == 2,
                'message' => 'Dodano dzień ' . $new_day
            ));
        } else {
            wp_send_json_error('Nie można dodać więcej dni (max 7)');
        }
    }

    /**
     * Load content for a specific day
     */
    public function load_day_content() {
        check_ajax_referer('cm_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Brak uprawnień');
        }

        $event_id = intval($_POST['event_id']);
        $day_number = intval($_POST['day_number']);

        $lineup_items = CM_Lineup::get_by_event_and_day($event_id, $day_number);
        error_log("CM_Ajax: Loading day {$day_number} content for event {$event_id} - found " . count($lineup_items) . " items");

        ob_start();
        if (empty($lineup_items)) {
            echo '<p class="text-gray-500 text-center py-8">Brak prezentacji na ten dzień</p>';
        } else {
            foreach ($lineup_items as $item) {
                include CONFERENCE_MANAGER_PLUGIN_PATH . 'admin/partials/lineup-item-template.php';
            }
        }
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Get day lineup for public display
     */
    public function get_day_lineup() {
        try {
            $nonce = $_REQUEST['nonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'cm_public_nonce') && !wp_verify_nonce($nonce, 'cm_sse_nonce')) {
                wp_send_json_error('Security check failed');
                return;
            }

            $event_id = intval($_REQUEST['event_id']);
            $day_number = intval($_REQUEST['day_number']);

            if (empty($event_id) || empty($day_number)) {
                wp_send_json_error('Brak wymaganych parametrów');
                return;
            }

            $event = new CM_Event($event_id);
            if (!$event->exists()) {
                wp_send_json_error('Wydarzenie nie istnieje');
                return;
            }

            $lineup_items = CM_Lineup::get_by_event_and_day($event_id, $day_number);
            $day_date = $event->get_day_date($day_number);

            if (empty($day_date)) {
                error_log("CM_Ajax: Unable to get day_date for event {$event_id}, day {$day_number}");
                wp_send_json_error('Nie można pobrać daty dnia');
                return;
            }

            error_log("CM_Ajax: Public day {$day_number} lineup request for event {$event_id} - found " . count($lineup_items) . " items, date: {$day_date}");

            ob_start();
            include CONFERENCE_MANAGER_PLUGIN_PATH . 'public/partials/day-lineup-popup.php';
            $html = ob_get_clean();

            if (empty($html)) {
                error_log("CM_Ajax: Generated HTML is empty for day {$day_number} lineup");
                wp_send_json_error('Błąd generowania HTML');
                return;
            }

            wp_send_json_success(array(
                'html' => $html,
                'day_date' => $day_date,
                'day_number' => $day_number
            ));
        } catch (Exception $e) {
            error_log("CM_Ajax: Exception in get_day_lineup: " . $e->getMessage());
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }

    /**
     * Delete a day from an event
     */
    public function delete_event_day() {
        check_ajax_referer('cm_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Brak uprawnień');
        }

        $event_id = intval($_POST['event_id']);
        $day_number = intval($_POST['day_number']);

        $event = new CM_Event($event_id);
        if (!$event->exists()) {
            wp_send_json_error('Wydarzenie nie istnieje');
        }

        if ($day_number < 1 || $day_number > $event->get_total_days()) {
            wp_send_json_error('Nieprawidłowy numer dnia');
        }

        if ($event->get_total_days() <= 1) {
            wp_send_json_error('Nie można usunąć jedynego dnia wydarzenia');
        }

        $lineup_items = CM_Lineup::get_by_event_and_day($event_id, $day_number);
        if (!empty($lineup_items)) {
            wp_send_json_error('Nie można usunąć dnia zawierającego prezentacje. Najpierw usuń wszystkie prezentacje z tego dnia.');
        }

        if ($event->remove_day($day_number)) {
            wp_send_json_success(array(
                'message' => 'Dzień został usunięty',
                'new_total_days' => $event->get_total_days()
            ));
        } else {
            wp_send_json_error('Nie udało się usunąć dnia');
        }
    }
}