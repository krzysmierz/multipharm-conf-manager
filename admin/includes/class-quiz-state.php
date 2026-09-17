<?php

/**
 * Quiz State Management Class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Quiz_State {

    /**
     * Quiz ID
     */
    private $quiz_id;

    /**
     * State data
     */
    private $state_data;

    /**
     * Constructor
     *
     * @param int $quiz_id Quiz ID
     */
    public function __construct($quiz_id) {
        $this->quiz_id = $quiz_id;
        $this->load_state();
    }

    /**
     * Load state from database
     */
    private function load_state() {
        // Check if table exists first
        global $wpdb;
        $table_name = CM_Database::get_table_name('quiz_states');
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        
        if ($table_exists != $table_name) {
            // Table doesn't exist, run migration
            error_log('CM_Quiz_State: quiz_states table missing, running migration');
            require_once plugin_dir_path(dirname(__FILE__)) . 'class-activator.php';
            CM_Activator::update_lineup_table_schema();
        }
        
        $this->state_data = CM_Database::get_row('quiz_states', array('quiz_id' => $this->quiz_id));
        
        // Create default state if none exists
        if (!$this->state_data) {
            $this->create_default_state();
        }
    }

    /**
     * Create default state for quiz
     */
    private function create_default_state() {
        $default_data = array(
            'quiz_id' => $this->quiz_id,
            'display_mode' => get_option('cm_quiz_default_mode', 'qr'),
            'auto_switch_enabled' => false,
            'auto_switch_delay' => get_option('cm_quiz_auto_switch_delay', 30)
        );

        $state_id = CM_Database::insert('quiz_states', $default_data);
        
        if (!is_wp_error($state_id)) {
            $this->load_state();
        }
    }

    /**
     * Get current display mode
     *
     * @return string Display mode ('qr' or 'results')
     */
    public function get_mode() {
        return $this->state_data ? $this->state_data->display_mode : 'qr';
    }

    /**
     * Set display mode
     *
     * @param string $mode Display mode ('qr' or 'results')
     * @return bool Success
     */
    public function set_mode($mode) {
        if (!in_array($mode, array('qr', 'results'))) {
            return false;
        }

        $old_mode = $this->get_mode();
        
        $result = CM_Database::update('quiz_states', 
            array(
                'display_mode' => $mode,
                'last_updated' => current_time('mysql')
            ),
            array('quiz_id' => $this->quiz_id)
        );

        if (!is_wp_error($result)) {
            $this->load_state();
            
            // Trigger SSE broadcast for mode change
            set_transient("cm_sse_broadcast_{$this->quiz_id}", array(
                'event' => 'quiz-mode-change',
                'data' => array(
                    'quiz_id' => $this->quiz_id,
                    'mode' => $mode,
                    'old_mode' => $old_mode,
                    'timestamp' => current_time('mysql'),
                    'auto_switched' => false
                )
            ), 10); // 10 seconds
            
            // Log the change
            error_log("Quiz {$this->quiz_id} mode changed from {$old_mode} to {$mode}");
            
            return true;
        }

        return false;
    }

    /**
     * Toggle display mode
     *
     * @return string New display mode
     */
    public function toggle_mode() {
        $current_mode = $this->get_mode();
        $new_mode = $current_mode === 'qr' ? 'results' : 'qr';
        
        $this->set_mode($new_mode);
        return $new_mode;
    }

    /**
     * Check if auto-switch is enabled
     *
     * @return bool Auto-switch status
     */
    public function is_auto_switch_enabled() {
        return $this->state_data ? (bool) $this->state_data->auto_switch_enabled : false;
    }

    /**
     * Enable/disable auto-switch
     *
     * @param bool $enabled Auto-switch enabled
     * @param int $delay Auto-switch delay in seconds (optional)
     * @return bool Success
     */
    public function set_auto_switch($enabled, $delay = null) {
        $update_data = array(
            'auto_switch_enabled' => (int) $enabled
        );

        if ($delay !== null) {
            $update_data['auto_switch_delay'] = (int) $delay;
        }

        $result = CM_Database::update('quiz_states', 
            $update_data,
            array('quiz_id' => $this->quiz_id)
        );

        if (!is_wp_error($result)) {
            $this->load_state();
            
            // Schedule or unschedule auto-switch
            if ($enabled) {
                $this->schedule_auto_switch();
            } else {
                $this->unschedule_auto_switch();
            }
            
            return true;
        }

        return false;
    }

    /**
     * Get auto-switch delay
     *
     * @return int Delay in seconds
     */
    public function get_auto_switch_delay() {
        return $this->state_data ? (int) $this->state_data->auto_switch_delay : 30;
    }

    /**
     * Check if auto-switch should trigger
     *
     * @return bool Should auto-switch
     */
    public function should_auto_switch() {
        if (!$this->is_auto_switch_enabled()) {
            return false;
        }

        $quiz = new CM_Quiz($this->quiz_id);
        
        // Only auto-switch if quiz is active
        if (!$quiz->get_is_active()) {
            return false;
        }

        // Check if delay has passed since quiz became active
        $quiz_start_time = strtotime($quiz->get_start_time());
        $delay = $this->get_auto_switch_delay();
        
        if ($quiz_start_time && (time() - $quiz_start_time) >= $delay) {
            return true;
        }

        return false;
    }

    /**
     * Schedule auto-switch using WordPress cron
     */
    private function schedule_auto_switch() {
        $hook = "cm_auto_switch_quiz_{$this->quiz_id}";
        
        // Clear existing scheduled event
        wp_clear_scheduled_hook($hook);
        
        // Schedule new event
        $delay = $this->get_auto_switch_delay();
        wp_schedule_single_event(time() + $delay, $hook, array($this->quiz_id));
    }

    /**
     * Unschedule auto-switch
     */
    private function unschedule_auto_switch() {
        $hook = "cm_auto_switch_quiz_{$this->quiz_id}";
        wp_clear_scheduled_hook($hook);
    }

    /**
     * Execute auto-switch
     */
    public function execute_auto_switch() {
        if ($this->should_auto_switch()) {
            $current_mode = $this->get_mode();
            
            // Auto-switch to results mode if currently showing QR
            if ($current_mode === 'qr') {
                $this->set_mode('results');
                CM_SSE_Controller::trigger_mode_change($this->quiz_id, 'results', true);
                
                error_log("Auto-switched quiz {$this->quiz_id} to results mode");
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get last updated timestamp
     *
     * @return string Last updated timestamp
     */
    public function get_last_updated() {
        return $this->state_data ? $this->state_data->last_updated : '';
    }

    /**
     * Get all state data
     *
     * @return object State data object
     */
    public function get_state_data() {
        return $this->state_data;
    }

    /**
     * Static method to get state for a quiz
     *
     * @param int $quiz_id Quiz ID
     * @return CM_Quiz_State|null Quiz state object
     */
    public static function get_state($quiz_id) {
        $state_data = CM_Database::get_row('quiz_states', array('quiz_id' => $quiz_id));
        
        if ($state_data) {
            $state = new self($quiz_id);
            return $state;
        }
        
        return null;
    }

    /**
     * Static method to create state for a quiz
     *
     * @param int $quiz_id Quiz ID
     * @param array $options Optional state options
     * @return CM_Quiz_State|null Quiz state object
     */
    public static function create_state($quiz_id, $options = array()) {
        $default_options = array(
            'display_mode' => get_option('cm_quiz_default_mode', 'qr'),
            'auto_switch_enabled' => false,
            'auto_switch_delay' => get_option('cm_quiz_auto_switch_delay', 30)
        );

        $state_data = array_merge($default_options, $options);
        $state_data['quiz_id'] = $quiz_id;

        $state_id = CM_Database::insert('quiz_states', $state_data);
        
        if (!is_wp_error($state_id)) {
            return new self($quiz_id);
        }
        
        return null;
    }

    /**
     * Delete state for a quiz
     *
     * @param int $quiz_id Quiz ID
     * @return bool Success
     */
    public static function delete_state($quiz_id) {
        // Unschedule any pending auto-switch
        $hook = "cm_auto_switch_quiz_{$quiz_id}";
        wp_clear_scheduled_hook($hook);
        
        $result = CM_Database::delete('quiz_states', array('quiz_id' => $quiz_id));
        return !is_wp_error($result);
    }

    /**
     * Get states for all quizzes in an event
     *
     * @param int $event_id Event ID
     * @return array Array of quiz states
     */
    public static function get_event_states($event_id) {
        global $wpdb;
        
        $table_states = CM_Database::get_table_name('quiz_states');
        $table_quizzes = CM_Database::get_table_name('quizzes');
        
        $states = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, q.title as quiz_title
            FROM {$table_states} s
            JOIN {$table_quizzes} q ON s.quiz_id = q.id
            WHERE q.event_id = %d
            ORDER BY q.id ASC
        ", $event_id));

        return $states;
    }

    /**
     * Update multiple quiz states for an event
     *
     * @param int $event_id Event ID
     * @param array $states Array of state data
     * @return bool Success
     */
    public static function update_event_states($event_id, $states) {
        $success = true;
        
        foreach ($states as $quiz_id => $state_data) {
            $quiz_state = self::get_state($quiz_id);
            if (!$quiz_state) {
                $quiz_state = self::create_state($quiz_id);
            }
            
            if ($quiz_state) {
                if (isset($state_data['display_mode'])) {
                    $quiz_state->set_mode($state_data['display_mode']);
                }
                
                if (isset($state_data['auto_switch_enabled']) || isset($state_data['auto_switch_delay'])) {
                    $auto_enabled = isset($state_data['auto_switch_enabled']) ? 
                        $state_data['auto_switch_enabled'] : $quiz_state->is_auto_switch_enabled();
                    $auto_delay = isset($state_data['auto_switch_delay']) ? 
                        $state_data['auto_switch_delay'] : $quiz_state->get_auto_switch_delay();
                    
                    $quiz_state->set_auto_switch($auto_enabled, $auto_delay);
                }
            } else {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Handle WordPress cron hook for auto-switch
     *
     * @param int $quiz_id Quiz ID
     */
    public static function handle_auto_switch_cron($quiz_id) {
        $quiz_state = self::get_state($quiz_id);
        if ($quiz_state) {
            $quiz_state->execute_auto_switch();
        }
    }

    /**
     * Clean up old or orphaned states
     */
    public static function cleanup_states() {
        global $wpdb;
        
        $table_states = CM_Database::get_table_name('quiz_states');
        $table_quizzes = CM_Database::get_table_name('quizzes');
        
        // Remove states for non-existent quizzes
        $wpdb->query("
            DELETE s FROM {$table_states} s
            LEFT JOIN {$table_quizzes} q ON s.quiz_id = q.id
            WHERE q.id IS NULL
        ");
        
        // Clear scheduled events for deleted quizzes
        $orphaned_states = $wpdb->get_col("
            SELECT s.quiz_id FROM {$table_states} s
            LEFT JOIN {$table_quizzes} q ON s.quiz_id = q.id
            WHERE q.id IS NULL
        ");
        
        foreach ($orphaned_states as $quiz_id) {
            wp_clear_scheduled_hook("cm_auto_switch_quiz_{$quiz_id}");
        }
    }
}

// Register WordPress hooks for auto-switch functionality
add_action('init', function() {
    // Register auto-switch cron hooks for all quizzes with auto-switch enabled
    global $wpdb;
    $table_states = CM_Database::get_table_name('quiz_states');
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_states}'") == $table_states) {
        $auto_switch_quizzes = $wpdb->get_col("
            SELECT quiz_id FROM {$table_states} 
            WHERE auto_switch_enabled = 1
        ");
        
        foreach ($auto_switch_quizzes as $quiz_id) {
            add_action("cm_auto_switch_quiz_{$quiz_id}", array('CM_Quiz_State', 'handle_auto_switch_cron'));
        }
    }
});