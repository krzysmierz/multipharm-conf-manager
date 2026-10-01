<?php

/**
 * Event management class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Event {

    private $id;
    private $title;
    private $description;
    private $event_date;
    private $start_time;
    private $end_time;
    private $status;
    private $total_days;
    private $current_active_day;
    private $created_at;
    private $updated_at;

    public function __construct($id = null) {
        if ($id) {
            $this->load($id);
        }
    }

    /**
     * Load event data from database
     */
    private function load($id) {
        $event = CM_Database::get_row('events', array('id' => $id));
        if ($event) {
            $this->id = $event->id;
            $this->title = $event->title;
            $this->description = $event->description;
            $this->event_date = $event->event_date;
            $this->start_time = $event->start_time;
            $this->end_time = $event->end_time;
            $this->status = $event->status;
            $this->total_days = $event->total_days ?? 1;
            $this->current_active_day = $event->current_active_day ?? 1;
            $this->created_at = $event->created_at;
            $this->updated_at = $event->updated_at;
        }
    }

    /**
     * Save event to database
     */
    public function save() {
        $data = array(
            'title' => $this->title,
            'description' => $this->description,
            'event_date' => $this->event_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'total_days' => $this->total_days ?? 1,
            'current_active_day' => $this->current_active_day ?? 1
        );

        if ($this->id) {
            $result = CM_Database::update('events', $data, array('id' => $this->id));
        } else {
            $result = CM_Database::insert('events', $data);
            if (!is_wp_error($result)) {
                $this->id = $result;
            }
        }

        return $result;
    }

    /**
     * Delete event from database
     */
    public function delete() {
        if ($this->id) {
            // Delete related lineup items
            CM_Database::delete('lineup', array('event_id' => $this->id));
            
            // Delete related quizzes and their data
            $quizzes = CM_Database::get_results('quizzes', array('event_id' => $this->id));
            foreach ($quizzes as $quiz) {
                $quiz_obj = new CM_Quiz($quiz->id);
                $quiz_obj->delete();
            }

            // Remove raffle registrations, draw history and their QR files
            // together with the event (the tables intentionally have no FK so
            // this also works on older WordPress/MySQL installations).
            $raffles = class_exists('CM_Raffle') ? CM_Raffle::get_by_event($this->id) : array();
            foreach ($raffles as $raffle) {
                CM_Database::delete('raffle_draws', array('raffle_id' => $raffle->id));
                CM_Database::delete('raffle_participants', array('raffle_id' => $raffle->id));
                CM_Database::delete('raffles', array('id' => $raffle->id));
            }
            
            // Delete QR codes
            $qr_codes = CM_Database::get_results('qr_codes', array('event_id' => $this->id));
            foreach ($qr_codes as $qr_code) {
                if (class_exists('CM_QR_Generator')) {
                    CM_QR_Generator::delete_qr_code($qr_code->id);
                }
            }
            CM_Database::delete('qr_codes', array('event_id' => $this->id));
            
            // Delete event
            $result = CM_Database::delete('events', array('id' => $this->id));

            if ($result !== false) {
                delete_option(self::get_custom_css_option_name($this->id));
            }

            return $result;
        }
        return false;
    }

    /**
     * Get all events
     */
    public static function get_all($status = '', $limit = '') {
        $where = array();
        if (!empty($status)) {
            $where['status'] = $status;
        }
        return CM_Database::get_results('events', $where, 'event_date DESC', $limit);
    }

    /**
     * Get events count by status
     */
    public static function get_count_by_status() {
        global $wpdb;
        $table_name = CM_Database::get_table_name('events');
        
        $results = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table_name 
            GROUP BY status
        ");
        
        $counts = array();
        foreach ($results as $result) {
            $counts[$result->status] = $result->count;
        }
        
        return $counts;
    }

    /**
     * Check if event exists in database
     */
    public function exists() {
        return !empty($this->id);
    }

    /**
     * Get total days for this event
     */
    public function get_total_days() {
        $total = $this->total_days ?? 1;

        // Auto-update total_days based on maximum day_number in presentations
        if ($this->id) {
            $max_day = $this->get_max_presentation_day();
            if ($max_day > $total) {
                error_log("CM_Event: Auto-updating total_days from {$total} to {$max_day} for event {$this->id}");
                $this->total_days = max(1, min(7, (int)$max_day));
                $this->save();
                $total = $this->total_days;
            }
        }

        error_log("CM_Event: get_total_days() for event {$this->id}: total_days={$total}");
        return $total;
    }

    /**
     * Get maximum day_number from presentations
     */
    private function get_max_presentation_day() {
        global $wpdb;
        $lineup_table = $wpdb->prefix . 'cm_lineup';

        $max_day = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(day_number) FROM {$lineup_table} WHERE event_id = %d",
            $this->id
        ));

        return max(1, (int)$max_day);
    }

    /**
     * Set total days (max 7)
     */
    public function set_total_days($days) {
        $this->total_days = max(1, min(7, (int)$days));
        error_log("CM_Event: Set total_days to {$this->total_days} for event {$this->id}");
    }

    /**
     * Get current active day
     */
    public function get_current_active_day() {
        return $this->current_active_day ?? 1;
    }

    /**
     * Set current active day
     */
    public function set_current_active_day($day) {
        $this->current_active_day = max(1, min($this->get_total_days(), (int)$day));
        error_log("CM_Event: Set current_active_day to {$this->current_active_day} for event {$this->id}");
        return $this->save();
    }

    /**
     * Add a new day to the event (max 7 days)
     */
    public function add_day() {
        $new_total = $this->get_total_days() + 1;
        if ($new_total <= 7) {
            $this->set_total_days($new_total);
            $result = $this->save();
            if ($result !== false) {
                error_log("CM_Event: Added day {$new_total} to event {$this->id}");
                return $new_total;
            }
        }
        return false;
    }

    /**
     * Remove a day from the event
     */
    public function remove_day($day_number) {
        if ($this->get_total_days() <= 1) {
            return false;
        }

        $new_total = $this->get_total_days() - 1;
        $this->set_total_days($new_total);
        $result = $this->save();

        if ($result !== false) {
            error_log("CM_Event: Removed day {$day_number}, new total {$new_total} for event {$this->id}");
            return true;
        }
        return false;
    }

    /**
     * Get date for specific day number
     */
    public function get_day_date($day_number) {
        $base_date = new DateTime($this->event_date);
        $base_date->modify('+' . ($day_number - 1) . ' days');
        return $base_date->format('Y-m-d');
    }

    /**
     * Auto-switch to next day if no active presentations in current day
     */
    public function auto_switch_to_next_day() {
        $current_day = $this->get_current_active_day();
        $active_presentations = CM_Lineup::get_active_presentations($this->get_id(), $current_day);

        if (empty($active_presentations) && $current_day < $this->get_total_days()) {
            error_log("CM_Event: Auto-switching from day {$current_day} to day " . ($current_day + 1) . " for event {$this->id}");
            $this->set_current_active_day($current_day + 1);
            return true;
        }
        return false;
    }

    // Getters and Setters
    public function get_id() { return $this->id; }
    public function get_title() { return $this->title; }
    public function get_description() { return $this->description; }
    public function get_event_date() { return $this->event_date; }
    public function get_start_time() { return $this->start_time; }
    public function get_end_time() { return $this->end_time; }
    public function get_status() { return $this->status; }
    public function get_created_at() { return $this->created_at; }
    public function get_updated_at() { return $this->updated_at; }

    /**
     * Retrieve optional CSS for the schedule and raffle screens.
     *
     * Event IDs belong to the plugin's custom table, so this deliberately uses
     * a namespaced option instead of WordPress post meta.
     */
    public function get_custom_css_settings() {
        if (!$this->id) {
            return array('schedule' => '', 'raffle' => '');
        }

        $settings = get_option(self::get_custom_css_option_name($this->id), array());
        $settings = is_array($settings) ? $settings : array();

        return array(
            'schedule' => self::get_valid_custom_css($settings['schedule'] ?? ''),
            'raffle'   => self::get_valid_custom_css($settings['raffle'] ?? ''),
        );
    }

    /** Store CSS only after validation by the trusted CSS editor workflow. */
    public function set_custom_css_settings($settings) {
        if (!$this->id) {
            return false;
        }

        $settings = self::validate_custom_css_settings($settings);
        if (is_wp_error($settings)) {
            return $settings;
        }

        $option_name = self::get_custom_css_option_name($this->id);
        if ($settings['schedule'] === '' && $settings['raffle'] === '') {
            return delete_option($option_name);
        }

        return update_option($option_name, $settings, false);
    }

    /**
     * Validate raw CSS without pretending to parse or sandbox it. The edit_css
     * capability is the trust boundary; this guard prevents breaking out of a
     * raw-text style element if option data is ever tampered with.
     */
    public static function validate_custom_css_settings($settings) {
        if (!is_array($settings)) {
            return new WP_Error('cm_invalid_event_css', __('Nieprawidłowe ustawienia CSS.', 'conference-manager'));
        }

        $validated = array();
        foreach (array('schedule', 'raffle') as $view) {
            $css = $settings[$view] ?? '';
            if (!is_scalar($css)) {
                return new WP_Error('cm_invalid_event_css', __('CSS musi być tekstem.', 'conference-manager'));
            }

            $css = trim((string) $css);
            if (strlen($css) > 50000) {
                return new WP_Error('cm_invalid_event_css', __('CSS nie może przekraczać 50 000 znaków.', 'conference-manager'));
            }

            // CSS is emitted as raw text. Do not permit an HTML tag or a
            // closing style tag to escape its dedicated style element.
            if (stripos($css, '</style') !== false || preg_match('/<\\s*\\/?\\s*[a-zA-Z!]/', $css)) {
                return new WP_Error('cm_invalid_event_css', __('CSS nie może zawierać znaczników HTML.', 'conference-manager'));
            }

            $validated[$view] = $css;
        }

        return $validated;
    }

    /** Render an already revalidated CSS block for a single public view. */
    public function get_custom_css_style_tag($view) {
        $settings = $this->get_custom_css_settings();
        $css = $settings[$view] ?? '';
        if ($css === '') {
            return '';
        }

        // The CSS was validated at write time and again immediately before
        // output. It must remain raw text; esc_html() corrupts valid CSS.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return '<style id="cm-event-' . esc_attr($view) . '-css-' . absint($this->id) . '">' . $css . '</style>';
    }

    private static function get_valid_custom_css($css) {
        $validated = self::validate_custom_css_settings(array('schedule' => $css, 'raffle' => ''));
        return is_wp_error($validated) ? '' : $validated['schedule'];
    }

    private static function get_custom_css_option_name($event_id) {
        return 'cm_event_custom_css_' . absint($event_id);
    }

    public function set_title($title) { $this->title = sanitize_text_field($title); }
    public function set_description($description) { $this->description = wp_kses_post($description); }
    public function set_event_date($date) { $this->event_date = sanitize_text_field($date); }
    public function set_start_time($time) { $this->start_time = sanitize_text_field($time); }
    public function set_end_time($time) { $this->end_time = sanitize_text_field($time); }
    public function set_status($status) { 
        $allowed_statuses = array('draft', 'active', 'paused', 'completed');
        if (in_array($status, $allowed_statuses)) {
            $this->status = $status;
        }
    }
}
