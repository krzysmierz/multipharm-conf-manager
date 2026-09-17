<?php

/**
 * Lineup management class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Lineup {

    private $id;
    private $event_id;
    private $day_number;
    private $title;
    private $description;
    private $presenter;
    private $start_time;
    private $duration_minutes;
    private $presentation_file;
    private $quiz_id;
    private $event_type;
    private $sort_order;
    private $is_active;
    private $created_at;

    public function __construct($id = null) {
        if ($id) {
            $this->load($id);
        }
    }

    /**
     * Load lineup item from database
     */
    private function load($id) {
        $item = CM_Database::get_row('lineup', array('id' => $id));
        if ($item) {
            $this->id = $item->id;
            $this->event_id = $item->event_id;
            $this->day_number = $item->day_number ?? 1;
            $this->title = $item->title;
            $this->description = $item->description;
            $this->presenter = $item->presenter;
            $this->start_time = $item->start_time;
            $this->duration_minutes = $item->duration_minutes;
            $this->presentation_file = $item->presentation_file;
            $this->quiz_id = $item->quiz_id ?? null;
            $this->event_type = $item->event_type ?? 'talk';
            $this->sort_order = $item->sort_order;
            $this->is_active = $item->is_active;
            $this->created_at = $item->created_at;
        }
    }

    /**
     * Save lineup item to database
     */
    public function save() {
        $data = array(
            'event_id' => $this->event_id,
            'day_number' => $this->day_number ?? 1,
            'title' => $this->title,
            'description' => $this->description,
            'presenter' => $this->presenter,
            'start_time' => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'presentation_file' => $this->presentation_file,
            'quiz_id' => $this->quiz_id,
            'event_type' => $this->event_type,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active ? 1 : 0
        );

        if ($this->id) {
            $result = CM_Database::update('lineup', $data, array('id' => $this->id));
        } else {
            $result = CM_Database::insert('lineup', $data);
            if (!is_wp_error($result)) {
                $this->id = $result;
            }
        }

        return $result;
    }

    /**
     * Delete lineup item
     */
    public function delete() {
        if ($this->id) {
            return CM_Database::delete('lineup', array('id' => $this->id));
        }
        return false;
    }

    /**
     * Get lineup items for event and specific day
     */
    public static function get_by_event_and_day($event_id, $day_number = 1) {
        error_log("CM_Lineup: Getting lineup for event {$event_id}, day {$day_number}");

        // Ensure presentations without day_number are assigned to day 1
        self::fix_missing_day_numbers($event_id);

        return CM_Database::get_results('lineup', array(
            'event_id' => $event_id,
            'day_number' => $day_number
        ), 'sort_order ASC, start_time ASC');
    }

    /**
     * Fix presentations that have missing or null day_number values
     */
    private static function fix_missing_day_numbers($event_id) {
        global $wpdb;

        $table_name = CM_Database::get_table_name('lineup');

        // Find presentations without day_number or with day_number = NULL
        $missing_day_presentations = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE event_id = %d AND (day_number IS NULL OR day_number = 0)",
            $event_id
        ));

        if (!empty($missing_day_presentations)) {
            error_log("CM_Lineup: Found " . count($missing_day_presentations) . " presentations without proper day_number for event {$event_id}");

            // Assign them to day 1 by default
            foreach ($missing_day_presentations as $presentation) {
                $wpdb->update(
                    $table_name,
                    array('day_number' => 1),
                    array('id' => $presentation->id),
                    array('%d'),
                    array('%d')
                );
                error_log("CM_Lineup: Fixed presentation ID {$presentation->id} - assigned to day 1");
            }
        }
    }

    /**
     * Get lineup items for event (sorted by manual sort order)
     */
    public static function get_by_event($event_id) {
        return CM_Database::get_results('lineup', array('event_id' => $event_id), 'sort_order ASC');
    }

    /**
     * Get lineup items for event sorted chronologically by start time (grouped by days)
     */
    public static function get_by_event_chronological($event_id) {
        global $wpdb;
        $table = CM_Database::get_table_name('lineup');

        $sql = $wpdb->prepare("
            SELECT * FROM $table
            WHERE event_id = %d
            ORDER BY day_number ASC, start_time ASC, sort_order ASC
        ", $event_id);

        return $wpdb->get_results($sql);
    }

    /**
     * Get currently active presentation for event
     */
    public static function get_active_presentation($event_id) {
        return CM_Database::get_row('lineup', array('event_id' => $event_id, 'is_active' => 1));
    }

    /**
     * Get active presentations for event and optional day
     */
    public static function get_active_presentations($event_id, $day_number = null) {
        global $wpdb;
        $table = CM_Database::get_table_name('lineup');

        $where_day = $day_number ? $wpdb->prepare(" AND day_number = %d", $day_number) : "";

        $sql = $wpdb->prepare("
            SELECT * FROM $table
            WHERE event_id = %d AND is_active = 1 $where_day
        ", $event_id);

        $results = $wpdb->get_results($sql);
        error_log("CM_Lineup: Found " . count($results) . " active presentations for event {$event_id}" . ($day_number ? ", day {$day_number}" : ""));
        return $results;
    }

    /**
     * Set presentation as active (and deactivate others)
     */
    public static function set_active_presentation($event_id, $lineup_id) {
        global $wpdb;
        $table_name = CM_Database::get_table_name('lineup');
        
        // Deactivate all presentations for this event
        $wpdb->update(
            $table_name,
            array('is_active' => 0),
            array('event_id' => $event_id)
        );
        
        // Activate selected presentation
        return $wpdb->update(
            $table_name,
            array('is_active' => 1),
            array('id' => $lineup_id, 'event_id' => $event_id)
        );
    }

    /**
     * Start presentation and set event active day
     */
    public static function start_presentation($lineup_id, $event_id) {
        // Find the presentation's day
        $presentation = CM_Database::get_row('lineup', array('id' => $lineup_id));
        if (!$presentation) {
            error_log("CM_Lineup: Presentation {$lineup_id} not found");
            return false;
        }

        error_log("CM_Lineup: Starting presentation {$lineup_id} on day {$presentation->day_number}");

        // Stop all active presentations
        CM_Database::update('lineup', array('is_active' => 0), array('event_id' => $event_id));

        // Start selected presentation
        CM_Database::update('lineup', array('is_active' => 1), array('id' => $lineup_id));

        // Set active day in event
        $event = new CM_Event($event_id);
        $event->set_current_active_day($presentation->day_number);

        return true;
    }

    /**
     * Update sort order for lineup items
     */
    public static function update_sort_order($lineup_items) {
        foreach ($lineup_items as $index => $item_id) {
            CM_Database::update('lineup', 
                array('sort_order' => $index + 1), 
                array('id' => $item_id)
            );
        }
        return true;
    }

    /**
     * Get next sort order for a new lineup item based on start time
     */
    public static function get_next_sort_order($event_id, $start_time = null) {
        global $wpdb;
        $table_name = CM_Database::get_table_name('lineup');

        if ($start_time) {
            // Get all existing items ordered by start_time
            $existing_items = $wpdb->get_results($wpdb->prepare(
                "SELECT id, start_time, sort_order FROM {$table_name} WHERE event_id = %d ORDER BY start_time ASC",
                $event_id
            ));

            // Find position where this item should be inserted
            $target_position = 1;
            foreach ($existing_items as $item) {
                if ($item->start_time <= $start_time) {
                    $target_position++;
                } else {
                    break;
                }
            }

            // Reorder existing items to make space
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table_name} SET sort_order = sort_order + 1 WHERE event_id = %d AND sort_order >= %d",
                $event_id,
                $target_position
            ));

            return $target_position;
        }

        // Fallback to max order + 1 if no start_time provided
        $max_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(sort_order) FROM {$table_name} WHERE event_id = %d",
            $event_id
        ));

        return ($max_order !== null) ? intval($max_order) + 1 : 1;
    }

    // Getters and Setters
    public function get_id() { return $this->id; }
    public function get_event_id() { return $this->event_id; }
    public function get_day_number() { return $this->day_number ?? 1; }
    public function get_title() { return $this->title; }
    public function get_description() { return $this->description; }
    public function get_presenter() { return $this->presenter; }
    public function get_start_time() { return $this->start_time; }
    public function get_duration_minutes() { return $this->duration_minutes; }
    public function get_presentation_file() { return $this->presentation_file; }
    public function get_quiz_id() { return $this->quiz_id; }
    public function get_event_type() { return $this->event_type; }
    public function get_sort_order() { return $this->sort_order; }
    public function get_is_active() { return $this->is_active; }
    public function get_created_at() { return $this->created_at; }

    public function set_event_id($event_id) { $this->event_id = intval($event_id); }
    public function set_day_number($day) { $this->day_number = max(1, intval($day)); }
    public function set_title($title) { $this->title = sanitize_text_field($title); }
    public function set_description($description) { $this->description = wp_kses_post($description); }
    public function set_presenter($presenter) { $this->presenter = sanitize_text_field($presenter); }
    public function set_start_time($time) { $this->start_time = self::normalize_time($time); }
    public function set_duration_minutes($duration) { $this->duration_minutes = intval($duration); }
    public function set_presentation_file($file) { $this->presentation_file = sanitize_text_field($file); }
    public function set_quiz_id($quiz_id) { $this->quiz_id = $quiz_id ? intval($quiz_id) : null; }
    public function set_event_type($type) {
        if (!in_array($type, array('talk', 'quick'))) {
            throw new InvalidArgumentException('Invalid event type');
        }
        $this->event_type = $type;
    }
    public function set_sort_order($order) { $this->sort_order = intval($order); }
    public function set_is_active($active) { $this->is_active = (bool) $active; }

    /**
     * RELIABLE event type detection
     */
    public function is_quick_event() {
        return $this->event_type === 'quick';
    }

    /**
     * Create a new quick event instance
     */
    public static function create_quick_event($event_id, $title, $start_time, $duration_minutes, $day_number = 1) {
        error_log("CM_Lineup: Creating quick event - {$title} at {$start_time}, day {$day_number}");

        $lineup = new CM_Lineup();
        $lineup->set_event_id($event_id);
        $lineup->set_event_type('quick');
        $lineup->set_title($title);
        $lineup->set_start_time($start_time);
        $lineup->set_duration_minutes((int) $duration_minutes);
        $lineup->set_day_number((int) $day_number);
        $lineup->set_presenter(null);
        $lineup->set_description(null);
        $lineup->set_presentation_file(null);
        $lineup->set_quiz_id(null);
        $lineup->set_sort_order(self::get_next_sort_order($event_id, $start_time, $day_number));
        return $lineup;
    }

    /**
     * Get predefined quick event templates
     */
    public static function get_quick_event_templates() {
        $templates = array(
            'break' => array(
                'title' => __('Przerwa', 'conference-manager'),
                'duration' => 15,
                'icon' => 'coffee'
            ),
            'coffee_break' => array(
                'title' => __('Przerwa kawowa', 'conference-manager'),
                'duration' => 15,
                'icon' => 'coffee'
            ),
            'lunch' => array(
                'title' => __('Lunch', 'conference-manager'),
                'duration' => 60,
                'icon' => 'utensils'
            ),
            'networking' => array(
                'title' => __('Networking', 'conference-manager'),
                'duration' => 30,
                'icon' => 'users'
            ),
            'registration' => array(
                'title' => __('Rejestracja', 'conference-manager'),
                'duration' => 30,
                'icon' => 'clipboard'
            )
        );

        return apply_filters('cm_quick_event_templates', $templates);
    }

    /**
     * Normalize time input to H:i:s format
     */
    public static function normalize_time($time_input) {
        $time = sanitize_text_field($time_input);

        if (!preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])(:([0-5][0-9]))?$/', $time)) {
            throw new InvalidArgumentException(__('Nieprawidłowy format czasu. Użyj HH:MM', 'conference-manager'));
        }

        if (substr_count($time, ':') === 1) {
            $time .= ':00';
        }

        return $time;
    }

    /**
     * ENHANCED time conflict detection - checks overlapping intervals
     *
     * @param int $event_id Event ID
     * @param string $start_time Start time to check
     * @param int $duration_minutes Duration in minutes
     * @param int $exclude_id Optional ID to exclude from check (for updates)
     * @param int $day_number Day number to check conflicts within (default: 1)
     * @return array Array with has_conflict boolean and conflicting_item data
     */
    public static function has_time_conflict($event_id, $start_time, $duration_minutes, $exclude_id = null, $day_number = 1) {
        global $wpdb;
        $table_name = CM_Database::get_table_name('lineup');

        error_log("CM_Lineup: Checking time conflict for event {$event_id}, day {$day_number}, time {$start_time}, duration {$duration_minutes}min");

        $start_timestamp = strtotime($start_time);
        $end_timestamp = $start_timestamp + ($duration_minutes * 60);

        $where_clause = "event_id = %d AND day_number = %d";
        $params = array($event_id, $day_number);

        if ($exclude_id) {
            $where_clause .= " AND id != %d";
            $params[] = $exclude_id;
        }

        $existing_items = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, start_time, duration_minutes FROM {$table_name} WHERE {$where_clause}",
            $params
        ));

        foreach ($existing_items as $item) {
            $item_start = strtotime($item->start_time);
            $item_end = $item_start + ((int)$item->duration_minutes * 60);

            $has_overlap = ($start_timestamp < $item_end) && ($end_timestamp > $item_start);

            if ($has_overlap) {
                error_log("CM_Lineup: Time conflict detected with item ID {$item->id} ({$item->title})");
                return array(
                    'has_conflict' => true,
                    'conflicting_item' => $item
                );
            }
        }

        error_log("CM_Lineup: No time conflicts detected");
        return array('has_conflict' => false);
    }

    /**
     * Calculate timeline from sort order - ensures DnD consistency
     */
    public static function calculate_timeline_from_sort_order($event_id) {
        global $wpdb;
        $table_name = CM_Database::get_table_name('lineup');

        error_log("CM_Lineup: Recalculating timeline for event {$event_id}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE event_id = %d ORDER BY sort_order ASC",
            $event_id
        ));

        $event_start = get_post_meta($event_id, 'event_start_time', true) ?: '09:00:00';
        $current_time = strtotime($event_start);

        foreach ($items as $item) {
            $calculated_start = date('H:i:s', $current_time);

            if ($item->start_time !== $calculated_start) {
                $wpdb->update(
                    $table_name,
                    array('start_time' => $calculated_start),
                    array('id' => $item->id)
                );
                error_log("CM_Lineup: Updated item {$item->id} start time to {$calculated_start}");
            }

            $current_time += ($item->duration_minutes * 60);
        }
    }

    /**
     * Update start times of subsequent events when inserting a new event
     *
     * @param int $event_id Event ID
     * @param string $insert_time Time where new event is being inserted
     * @param int $duration_minutes Duration of the new event in minutes
     */
    public static function update_subsequent_event_times($event_id, $insert_time, $duration_minutes) {
        global $wpdb;
        $table_name = CM_Database::get_table_name('lineup');

        error_log("CM_Lineup: Updating subsequent events after insertion at {$insert_time}, duration {$duration_minutes}min");

        // Get all events that start at or after the insertion time (including same time)
        $subsequent_events = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, start_time, duration_minutes FROM {$table_name}
             WHERE event_id = %d AND start_time >= %s
             ORDER BY start_time ASC",
            $event_id,
            $insert_time
        ));

        if (empty($subsequent_events)) {
            error_log("CM_Lineup: No subsequent events to update");
            return;
        }

        $time_shift_seconds = $duration_minutes * 60;

        foreach ($subsequent_events as $event) {
            $current_start = strtotime($event->start_time);
            $new_start_time = date('H:i:s', $current_start + $time_shift_seconds);

            $wpdb->update(
                $table_name,
                array('start_time' => $new_start_time),
                array('id' => $event->id)
            );

            error_log("CM_Lineup: Pushed event '{$event->title}' (ID: {$event->id}) from {$event->start_time} to {$new_start_time}");
        }

        error_log("CM_Lineup: Successfully pushed " . count($subsequent_events) . " events forward by {$duration_minutes} minutes");
    }

    /**
     * Clear cached presentation start times for an event
     * This function should be called after deleting or modifying lineup items
     * to ensure time conflict validation uses fresh data
     *
     * @param int $event_id Event ID to clear cache for
     * @return bool True on success
     */
    public static function clear_time_cache($event_id) {
        error_log("CM_Lineup: Clearing time cache for event {$event_id}");

        // Clear WordPress transients that might cache lineup data
        delete_transient('cm_lineup_times_' . $event_id);
        delete_transient('cm_lineup_cache_' . $event_id);

        // Clear any user meta that might store cached times
        $users = get_users(array('capability' => 'manage_options'));
        foreach ($users as $user) {
            delete_user_meta($user->ID, 'cm_cached_times_' . $event_id);
        }

        // Trigger JavaScript cache clearing if in admin
        if (is_admin()) {
            add_action('admin_footer', function() use ($event_id) {
                echo "
                <script>
                // Clear localStorage cache for this event
                if (typeof localStorage !== 'undefined') {
                    localStorage.removeItem('cm_lineup_times_$event_id');
                    localStorage.removeItem('cm_used_times_$event_id');
                }

                // Clear sessionStorage cache
                if (typeof sessionStorage !== 'undefined') {
                    sessionStorage.removeItem('cm_lineup_times_$event_id');
                    sessionStorage.removeItem('cm_used_times_$event_id');
                }

                // Trigger custom event for any listeners
                if (typeof window.CustomEvent === 'function') {
                    window.dispatchEvent(new CustomEvent('cm_time_cache_cleared', {
                        detail: { event_id: $event_id }
                    }));
                }

                console.log('CM_Lineup: Time cache cleared for event $event_id');
                </script>
                ";
            });
        }

        return true;
    }

    /**
     * Refresh presentation time conflicts for an event
     * Forces a fresh check of all time conflicts without cache
     *
     * @param int $event_id Event ID to refresh
     * @return array Updated lineup with conflict status
     */
    public static function refresh_time_conflicts($event_id) {
        error_log("CM_Lineup: Refreshing time conflicts for event {$event_id}");

        // Clear cache first
        self::clear_time_cache($event_id);

        // Get fresh lineup data
        $lineup_items = self::get_by_event($event_id);

        // Check each item for conflicts
        foreach ($lineup_items as &$item) {
            $conflict_check = self::has_time_conflict(
                $event_id,
                $item->start_time,
                $item->duration_minutes,
                $item->id,
                $item->day_number
            );

            $item->has_conflict = $conflict_check['has_conflict'];
            if ($conflict_check['has_conflict']) {
                $item->conflicting_with = $conflict_check['conflicting_item'];
            }
        }

        return $lineup_items;
    }
}