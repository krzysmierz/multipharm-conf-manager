<?php

/**
 * Fired during plugin activation
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Activator {

    /**
     * Short Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_tables();
        self::update_lineup_table_schema();
        self::create_upload_directories();
        self::set_default_options();
    }

    /**
     * Create plugin database tables
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Events table
        $table_events = $wpdb->prefix . 'cm_events';
        $sql_events = "CREATE TABLE $table_events (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            event_date datetime NOT NULL,
            start_time time,
            end_time time,
            status enum('draft','active','paused','completed') DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_date (event_date),
            KEY status (status)
        ) $charset_collate;";

        // Lineup table
        $table_lineup = $wpdb->prefix . 'cm_lineup';
        $sql_lineup = "CREATE TABLE $table_lineup (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            presenter varchar(255),
            start_time time NOT NULL,
            duration_minutes int DEFAULT 30,
            presentation_file varchar(255),
            quiz_id mediumint(9) DEFAULT NULL,
            sort_order int DEFAULT 0,
            is_active boolean DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY quiz_id (quiz_id),
            KEY sort_order (sort_order),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Quizzes table
        $table_quizzes = $wpdb->prefix . 'cm_quizzes';
        $sql_quizzes = "CREATE TABLE $table_quizzes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            is_active boolean DEFAULT 0,
            start_time datetime,
            end_time datetime,
            sse_enabled boolean DEFAULT 1,
            live_results_enabled boolean DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Quiz questions table
        $table_questions = $wpdb->prefix . 'cm_quiz_questions';
        $sql_questions = "CREATE TABLE $table_questions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9) NOT NULL,
            question text NOT NULL,
            question_type enum('single','multiple','text') DEFAULT 'single',
            sort_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY quiz_id (quiz_id),
            KEY sort_order (sort_order)
        ) $charset_collate;";

        // Quiz answers table
        $table_answers = $wpdb->prefix . 'cm_quiz_answers';
        $sql_answers = "CREATE TABLE $table_answers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question_id mediumint(9) NOT NULL,
            answer_text text NOT NULL,
            is_correct boolean DEFAULT 0,
            sort_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY question_id (question_id),
            KEY is_correct (is_correct)
        ) $charset_collate;";

        // User responses table
        $table_responses = $wpdb->prefix . 'cm_user_responses';
        $sql_responses = "CREATE TABLE $table_responses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9) NOT NULL,
            question_id mediumint(9) NOT NULL,
            user_identifier varchar(255) NOT NULL,
            selected_answer_ids text,
            text_response text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY quiz_id (quiz_id),
            KEY question_id (question_id),
            KEY user_identifier (user_identifier)
        ) $charset_collate;";

        // QR Codes table
        $table_qr = $wpdb->prefix . 'cm_qr_codes';
        $sql_qr = "CREATE TABLE $table_qr (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id mediumint(9) NOT NULL,
            code_type enum('event','quiz','presentation') NOT NULL,
            target_id mediumint(9),
            qr_data text NOT NULL,
            file_path varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY code_type (code_type),
            KEY target_id (target_id)
        ) $charset_collate;";

        // Quiz States table for SSE functionality
        $table_quiz_states = $wpdb->prefix . 'cm_quiz_states';
        $sql_quiz_states = "CREATE TABLE $table_quiz_states (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9) NOT NULL,
            display_mode enum('qr','results') DEFAULT 'qr',
            auto_switch_enabled boolean DEFAULT 0,
            auto_switch_delay int DEFAULT 30,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_quiz_id (quiz_id),
            KEY display_mode (display_mode),
            CONSTRAINT fk_quiz_states_quiz_id FOREIGN KEY (quiz_id) REFERENCES {$table_quizzes}(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_events);
        dbDelta($sql_lineup);
        dbDelta($sql_quizzes);
        dbDelta($sql_questions);
        dbDelta($sql_answers);
        dbDelta($sql_responses);
        dbDelta($sql_qr);
        dbDelta($sql_quiz_states);
        
        // Update existing lineup table to add quiz_id column if it doesn't exist
        self::update_lineup_table_schema();
    }

    /**
     * Create upload directories
     *
     * @since    1.0.0
     */
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $cm_upload_dir = $upload_dir['basedir'] . '/conference-manager';

        $directories = array(
            $cm_upload_dir,
            $cm_upload_dir . '/presentations',
            $cm_upload_dir . '/images',
            $cm_upload_dir . '/qr-codes'
        );

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                // Create index.php to prevent directory browsing
                file_put_contents($dir . '/index.php', '<?php // Silence is golden');
            }
        }
    }

    /**
     * Set default plugin options
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        $default_options = array(
            'cm_qr_size' => 200,
            'cm_allow_file_types' => array('ppt', 'pptx', 'pdf', 'jpg', 'jpeg', 'png', 'gif'),
            'cm_max_file_size' => 10, // MB
            'cm_quiz_time_limit' => 300, // seconds
            'cm_auto_advance_presentations' => false,
            'cm_quiz_sse_enabled' => true,
            'cm_quiz_default_mode' => 'qr',
            'cm_quiz_auto_switch_delay' => 30,
            'cm_quiz_max_sse_connections' => 100,
            'cm_quiz_polling_fallback' => true
        );

        foreach ($default_options as $option_name => $option_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $option_value);
            }
        }
    }
    
    /**
     * Update lineup table schema to add quiz_id column
     */
    public static function update_lineup_table_schema() {
        global $wpdb;
        
        $table_lineup = $wpdb->prefix . 'cm_lineup';
        
        // Check if quiz_id column exists
        $column_exists = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_lineup} LIKE %s
        ", 'quiz_id'));
        
        // Add quiz_id column if it doesn't exist
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE {$table_lineup} 
                ADD COLUMN quiz_id mediumint(9) DEFAULT NULL AFTER presentation_file,
                ADD KEY quiz_id (quiz_id)
            ");
        }
        
        // Add participant_name column to user_responses table
        $table_responses = $wpdb->prefix . 'cm_user_responses';

        // Check if participant_name column exists
        $name_column_exists = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_responses} LIKE %s
        ", 'participant_name'));

        // Add participant_name column if it doesn't exist
        if (empty($name_column_exists)) {
            $wpdb->query("
                ALTER TABLE {$table_responses}
                ADD COLUMN participant_name varchar(255) DEFAULT NULL AFTER user_identifier
            ");
        }

        // Add participant_ip column to user_responses table
        $ip_column_exists = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_responses} LIKE %s
        ", 'participant_ip'));

        // Add participant_ip column if it doesn't exist
        if (empty($ip_column_exists)) {
            $wpdb->query("
                ALTER TABLE {$table_responses}
                ADD COLUMN participant_ip varchar(45) DEFAULT NULL AFTER participant_name,
                ADD KEY participant_ip (participant_ip)
            ");
        }

        // Add actual_start_time column to quizzes table
        $table_quizzes = $wpdb->prefix . 'cm_quizzes';
        $actual_start_column_exists = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_quizzes} LIKE %s
        ", 'actual_start_time'));

        // Add actual_start_time column if it doesn't exist
        if (empty($actual_start_column_exists)) {
            $wpdb->query("
                ALTER TABLE {$table_quizzes}
                ADD COLUMN actual_start_time datetime DEFAULT NULL AFTER end_time
            ");
        }
        
        // Add SSE fields to quizzes table
        $table_quizzes = $wpdb->prefix . 'cm_quizzes';

        // Check if sse_enabled column exists
        $sse_column_exists = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_quizzes} LIKE %s
        ", 'sse_enabled'));

        if (empty($sse_column_exists)) {
            $wpdb->query("
                ALTER TABLE {$table_quizzes}
                ADD COLUMN sse_enabled boolean DEFAULT 1 AFTER end_time,
                ADD COLUMN live_results_enabled boolean DEFAULT 1 AFTER sse_enabled
            ");
        }

        // CRITICAL: Add event_type column to lineup table for quick events
        $event_type_column_exists = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_lineup} LIKE %s
        ", 'event_type'));

        if (empty($event_type_column_exists)) {
            error_log('CM_Activator: Adding event_type column to lineup table');

            $wpdb->query("
                ALTER TABLE {$table_lineup}
                ADD COLUMN event_type ENUM('talk','quick') NOT NULL DEFAULT 'talk' AFTER quiz_id,
                ADD INDEX idx_event_time (event_id, start_time),
                ADD INDEX idx_event_type (event_id, event_type)
            ");

            // Update existing records - detect quick events by empty presenter
            $updated_rows = $wpdb->query("
                UPDATE {$table_lineup}
                SET event_type = 'quick'
                WHERE (presenter IS NULL OR presenter = '')
                  AND (presentation_file IS NULL OR presentation_file = '')
            ");

            error_log("CM_Activator: Updated {$updated_rows} existing records to 'quick' event type");
        }

        // Multi-day support: Add day_number column to lineup table
        $day_column_exists = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_lineup} LIKE %s
        ", 'day_number'));

        if (empty($day_column_exists)) {
            error_log('CM_Activator: Adding day_number column to lineup table');
            $wpdb->query("
                ALTER TABLE {$table_lineup}
                ADD COLUMN day_number INT DEFAULT 1 AFTER event_id,
                ADD KEY day_number (day_number)
            ");
        }

        // Multi-day support: Add total_days and current_active_day to events table
        $table_events = $wpdb->prefix . 'cm_events';
        $total_days_exists = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_events} LIKE %s
        ", 'total_days'));

        if (empty($total_days_exists)) {
            error_log('CM_Activator: Adding total_days and current_active_day columns to events table');
            $wpdb->query("
                ALTER TABLE {$table_events}
                ADD COLUMN total_days INT DEFAULT 1 AFTER end_time,
                ADD COLUMN current_active_day INT DEFAULT 1 AFTER total_days
            ");
        }
        
        // Create quiz_states table if it doesn't exist
        $table_quiz_states = $wpdb->prefix . 'cm_quiz_states';
        $states_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_quiz_states}'");
        
        if ($states_table_exists != $table_quiz_states) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql_quiz_states = "CREATE TABLE $table_quiz_states (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                quiz_id mediumint(9) NOT NULL,
                display_mode enum('qr','results') DEFAULT 'qr',
                auto_switch_enabled boolean DEFAULT 0,
                auto_switch_delay int DEFAULT 30,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_quiz_id (quiz_id),
                KEY display_mode (display_mode)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_quiz_states);
        }
    }
}