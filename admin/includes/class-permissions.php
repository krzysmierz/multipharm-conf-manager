<?php

/**
 * Permissions and security class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Permissions {

    /**
     * Initialize permission hooks
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'add_custom_capabilities'));
        add_filter('user_has_cap', array(__CLASS__, 'check_custom_capabilities'), 10, 3);
    }

    /**
     * Add custom capabilities for conference management
     */
    public static function add_custom_capabilities() {
        $role = get_role('administrator');
        
        if ($role) {
            $capabilities = array(
                'manage_conferences',
                'edit_conferences',
                'delete_conferences',
                'manage_conference_lineup',
                'manage_conference_quizzes',
                'upload_conference_files',
                'generate_qr_codes',
                'view_quiz_results'
            );
            
            foreach ($capabilities as $cap) {
                $role->add_cap($cap);
            }
        }
        
        // Add capabilities for editor role (limited access)
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('edit_conferences');
            $editor_role->add_cap('manage_conference_lineup');
            $editor_role->add_cap('manage_conference_quizzes');
        }
    }

    /**
     * Check if user can manage conferences
     */
    public static function can_manage_conferences($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_conferences') || user_can($user_id, 'manage_options');
    }

    /**
     * Check if user can edit conferences
     */
    public static function can_edit_conferences($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'edit_conferences') || self::can_manage_conferences($user_id);
    }

    /**
     * Check if user can delete conferences
     */
    public static function can_delete_conferences($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'delete_conferences') || self::can_manage_conferences($user_id);
    }

    /**
     * Check if user can manage lineup
     */
    public static function can_manage_lineup($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_conference_lineup') || self::can_edit_conferences($user_id);
    }

    /**
     * Check if user can manage quizzes
     */
    public static function can_manage_quizzes($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_conference_quizzes') || self::can_edit_conferences($user_id);
    }

    /**
     * Check if user can upload files
     */
    public static function can_upload_files($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'upload_conference_files') || self::can_edit_conferences($user_id);
    }

    /**
     * Check if user can generate QR codes
     */
    public static function can_generate_qr_codes($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'generate_qr_codes') || self::can_edit_conferences($user_id);
    }

    /**
     * Check if user can view quiz results
     */
    public static function can_view_quiz_results($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'view_quiz_results') || self::can_manage_quizzes($user_id);
    }

    /**
     * Sanitize and validate event data
     */
    public static function sanitize_event_data($data) {
        $sanitized = array();
        
        if (isset($data['title'])) {
            $sanitized['title'] = sanitize_text_field($data['title']);
        }
        
        if (isset($data['description'])) {
            $sanitized['description'] = wp_kses_post($data['description']);
        }
        
        if (isset($data['event_date'])) {
            $sanitized['event_date'] = sanitize_text_field($data['event_date']);
            // Validate date format
            if (!self::validate_date($sanitized['event_date'])) {
                unset($sanitized['event_date']);
            }
        }
        
        if (isset($data['start_time'])) {
            $sanitized['start_time'] = sanitize_text_field($data['start_time']);
            if (!self::validate_time($sanitized['start_time'])) {
                unset($sanitized['start_time']);
            }
        }
        
        if (isset($data['end_time'])) {
            $sanitized['end_time'] = sanitize_text_field($data['end_time']);
            if (!self::validate_time($sanitized['end_time'])) {
                unset($sanitized['end_time']);
            }
        }
        
        if (isset($data['status'])) {
            $allowed_statuses = array('draft', 'active', 'paused', 'completed');
            if (in_array($data['status'], $allowed_statuses)) {
                $sanitized['status'] = $data['status'];
            }
        }
        
        return $sanitized;
    }

    /**
     * Validate date format
     */
    private static function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate time format
     */
    private static function validate_time($time) {
        // Allow empty time values
        if (empty($time)) {
            return true;
        }
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
    }

    /**
     * Generate and verify nonces
     */
    public static function create_nonce($action = 'cm_admin_action') {
        return wp_create_nonce($action);
    }

    public static function verify_nonce($nonce, $action = 'cm_admin_action') {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Rate limiting for quiz submissions
     */
    public static function check_quiz_rate_limit($user_identifier, $quiz_id) {
        global $wpdb;
        $responses_table = CM_Database::get_table_name('user_responses');
        
        // Check if user has submitted this quiz in the last 5 minutes
        $recent_submission = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $responses_table 
            WHERE user_identifier = %s 
            AND quiz_id = %d 
            AND submitted_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ", $user_identifier, $quiz_id));
        
        return $recent_submission == 0;
    }

    /**
     * Validate file upload security
     */
    public static function validate_file_upload($file) {
        // Check file size
        $max_size = get_option('cm_max_file_size', 10) * 1024 * 1024;
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', 'File size exceeds limit');
        }
        
        // Check file extension
        $allowed_types = get_option('cm_allow_file_types', array('ppt', 'pptx', 'pdf', 'jpg', 'jpeg', 'png', 'gif'));
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return new WP_Error('invalid_file_type', 'File type not allowed');
        }
        
        // Basic malware check (check for suspicious patterns)
        $suspicious_patterns = array(
            '<?php',
            '<%',
            '<script',
            'javascript:',
            'vbscript:'
        );
        
        $file_content = file_get_contents($file['tmp_name']);
        foreach ($suspicious_patterns as $pattern) {
            if (stripos($file_content, $pattern) !== false) {
                return new WP_Error('suspicious_content', 'File contains suspicious content');
            }
        }
        
        return true;
    }

    /**
     * Log security events
     */
    public static function log_security_event($event_type, $description, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'description' => $description,
            'user_id' => $user_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        );
        
        // Store in options table for now (could be moved to custom table later)
        $security_log = get_option('cm_security_log', array());
        $security_log[] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($security_log) > 1000) {
            $security_log = array_slice($security_log, -1000);
        }
        
        update_option('cm_security_log', $security_log);
    }
}