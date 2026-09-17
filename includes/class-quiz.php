<?php

/**
 * Quiz management class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Quiz {

    private $id;
    private $event_id;
    private $title;
    private $description;
    private $is_active;
    private $start_time;
    private $end_time;
    private $created_at;
    private $actual_start_time;

    public function __construct($id = null) {
        if ($id) {
            $this->load($id);
        }
    }

    /**
     * Load quiz from database
     */
    private function load($id) {
        $quiz = CM_Database::get_row('quizzes', array('id' => $id));
        if ($quiz) {
            $this->id = $quiz->id;
            $this->event_id = $quiz->event_id;
            $this->title = $quiz->title;
            $this->description = $quiz->description;
            $this->is_active = $quiz->is_active;
            $this->start_time = $quiz->start_time;
            $this->end_time = $quiz->end_time;
            $this->created_at = $quiz->created_at;
            // Safe access to actual_start_time (may not exist in older DB schemas)
            $this->actual_start_time = property_exists($quiz, 'actual_start_time') ? $quiz->actual_start_time : null;
        }
    }

    /**
     * Save quiz to database
     */
    public function save() {
        $data = array(
            'event_id' => $this->event_id,
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->is_active ? 1 : 0,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time
        );

        // Only add actual_start_time if column exists
        if ($this->column_exists('cm_quizzes', 'actual_start_time')) {
            $data['actual_start_time'] = $this->actual_start_time;
        }

        if ($this->id) {
            $result = CM_Database::update('quizzes', $data, array('id' => $this->id));
        } else {
            $result = CM_Database::insert('quizzes', $data);
            if (!is_wp_error($result)) {
                $this->id = $result;
            }
        }

        return $result;
    }

    /**
     * Delete quiz and all related data
     */
    public function delete() {
        if ($this->id) {
            // Get all questions for this quiz
            $questions = CM_Database::get_results('quiz_questions', array('quiz_id' => $this->id));
            
            // Delete all answers for each question
            foreach ($questions as $question) {
                CM_Database::delete('quiz_answers', array('question_id' => $question->id));
            }
            
            // Delete all questions
            CM_Database::delete('quiz_questions', array('quiz_id' => $this->id));
            
            // Delete all user responses
            CM_Database::delete('user_responses', array('quiz_id' => $this->id));
            
            // Delete quiz
            return CM_Database::delete('quizzes', array('id' => $this->id));
        }
        return false;
    }

    /**
     * Get quizzes for event
     */
    public static function get_by_event($event_id) {
        return CM_Database::get_results('quizzes', array('event_id' => $event_id), 'created_at DESC');
    }

    /**
     * Get active quizzes for event
     */
    public static function get_active_by_event($event_id) {
        return CM_Database::get_results('quizzes', array('event_id' => $event_id, 'is_active' => 1));
    }

    /**
     * Get questions for this quiz
     */
    public function get_questions() {
        if ($this->id) {
            return CM_Database::get_results('quiz_questions', array('quiz_id' => $this->id), 'sort_order ASC');
        }
        return array();
    }

    /**
     * Add question to quiz
     */
    public function add_question($question_data) {
        if (!$this->id) {
            return false;
        }

        $question_data['quiz_id'] = $this->id;
        $question_id = CM_Database::insert('quiz_questions', $question_data);
        
        return $question_id;
    }

    /**
     * Get quiz results/statistics
     */
    public function get_results() {
        if (!$this->id) {
            return array();
        }

        global $wpdb;
        $responses_table = CM_Database::get_table_name('user_responses');
        $questions_table = CM_Database::get_table_name('quiz_questions');
        $answers_table = CM_Database::get_table_name('quiz_answers');

        // Get response statistics
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                q.id as question_id,
                q.question,
                COUNT(DISTINCT r.user_identifier) as total_responses,
                a.id as answer_id,
                a.answer_text,
                a.is_correct,
                COUNT(CASE WHEN FIND_IN_SET(a.id, r.selected_answer_ids) > 0 THEN 1 END) as answer_count
            FROM $questions_table q
            LEFT JOIN $responses_table r ON q.id = r.question_id
            LEFT JOIN $answers_table a ON q.id = a.question_id
            WHERE q.quiz_id = %d
            GROUP BY q.id, a.id
            ORDER BY q.sort_order, a.sort_order
        ", $this->id));

        return $results;
    }

    // Getters and Setters
    public function get_id() { return $this->id; }
    public function get_event_id() { return $this->event_id; }
    public function get_title() { return $this->title; }
    public function get_description() { return $this->description; }
    public function get_is_active() { return $this->is_active; }
    public function get_start_time() { return $this->start_time; }
    public function get_end_time() { return $this->end_time; }
    public function get_created_at() { return $this->created_at; }
    public function get_actual_start_time() { return $this->actual_start_time; }

    public function set_event_id($event_id) { $this->event_id = intval($event_id); }
    public function set_title($title) { $this->title = sanitize_text_field($title); }
    public function set_description($description) { $this->description = wp_kses_post($description); }
    public function set_is_active($active) { $this->is_active = (bool) $active; }
    public function set_start_time($time) { $this->start_time = sanitize_text_field($time); }
    public function set_end_time($time) { $this->end_time = sanitize_text_field($time); }
    public function set_actual_start_time($time) { $this->actual_start_time = sanitize_text_field($time); }

    /**
     * Generate random participant identifier
     */
    public static function generate_participant_identifier() {
        $syllables = [
            'ba', 'be', 'bi', 'bo', 'bu',
            'ca', 'ce', 'ci', 'co', 'cu',
            'da', 'de', 'di', 'do', 'du',
            'fa', 'fe', 'fi', 'fo', 'fu',
            'ga', 'ge', 'gi', 'go', 'gu',
            'ha', 'he', 'hi', 'ho', 'hu',
            'ja', 'je', 'ji', 'jo', 'ju',
            'ka', 'ke', 'ki', 'ko', 'ku',
            'la', 'le', 'li', 'lo', 'lu',
            'ma', 'me', 'mi', 'mo', 'mu',
            'na', 'ne', 'ni', 'no', 'nu',
            'pa', 'pe', 'pi', 'po', 'pu',
            'ra', 're', 'ri', 'ro', 'ru',
            'sa', 'se', 'si', 'so', 'su',
            'ta', 'te', 'ti', 'to', 'tu',
            'wa', 'we', 'wi', 'wo', 'wu',
            'za', 'ze', 'zi', 'zo', 'zu'
        ];

        $syllable = $syllables[array_rand($syllables)];
        $digits = sprintf('%02d', mt_rand(10, 99));

        return strtoupper($syllable . $digits);
    }

    /**
     * Check if participant with IP can take quiz
     */
    public function can_participant_take_quiz($ip_address) {
        global $wpdb;
        $responses_table = CM_Database::get_table_name('user_responses');

        // Check if this IP has already taken the quiz
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$responses_table}
             WHERE quiz_id = %d AND participant_ip = %s",
            $this->id, $ip_address
        ));

        return $existing == 0;
    }

    /**
     * Start quiz timing from first participant
     */
    public function start_quiz_if_needed($ip_address) {
        if (!$this->actual_start_time) {
            // This is the first participant
            $this->set_actual_start_time(current_time('mysql'));
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Register participant and get their identifier
     */
    public function register_participant($ip_address) {
        if (!$this->can_participant_take_quiz($ip_address)) {
            return new WP_Error('duplicate_participant', 'Uczestnik z tego adresu IP już brał udział w quizie.');
        }

        // Generate unique identifier
        $identifier = self::generate_participant_identifier();

        // Ensure uniqueness for this quiz
        global $wpdb;
        $responses_table = CM_Database::get_table_name('user_responses');

        $attempts = 0;
        while ($attempts < 10) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$responses_table}
                 WHERE quiz_id = %d AND user_identifier = %s",
                $this->id, $identifier
            ));

            if ($existing == 0) {
                break;
            }

            $identifier = self::generate_participant_identifier();
            $attempts++;
        }

        if ($attempts >= 10) {
            return new WP_Error('identifier_generation_failed', 'Nie można wygenerować unikalnego identyfikatora.');
        }

        // Start quiz timing if this is the first participant
        $this->start_quiz_if_needed($ip_address);

        return $identifier;
    }

    /**
     * Get quiz leaderboard with time-based filtering
     */
    public function get_leaderboard($limit = 10) {
        global $wpdb;
        $responses_table = CM_Database::get_table_name('user_responses');
        $questions_table = CM_Database::get_table_name('quiz_questions');
        $answers_table = CM_Database::get_table_name('quiz_answers');

        $participants = $wpdb->get_results($wpdb->prepare("
            SELECT
                user_identifier,
                MIN(submitted_at) as start_time,
                MAX(submitted_at) as completion_time,
                COUNT(DISTINCT question_id) as questions_answered
            FROM {$responses_table}
            WHERE quiz_id = %d
            GROUP BY user_identifier
            HAVING questions_answered = (
                SELECT COUNT(*) FROM {$questions_table} WHERE quiz_id = %d
            )
            ORDER BY completion_time ASC
        ", $this->id, $this->id));

        if (empty($participants)) {
            return [];
        }

        $user_identifiers = array_map(function($p) { return $p->user_identifier; }, $participants);
        $placeholders = implode(',', array_fill(0, count($user_identifiers), '%s'));

        $all_user_responses = $wpdb->get_results($wpdb->prepare("
            SELECT r.user_identifier, r.question_id, r.selected_answer_ids, q.question_type
            FROM {$responses_table} r
            JOIN {$questions_table} q ON r.question_id = q.id
            WHERE r.quiz_id = %d
            AND r.user_identifier IN ({$placeholders})
            AND q.question_type IN ('single', 'multiple')
        ", array_merge([$this->id], $user_identifiers)));

        $question_ids = array_unique(array_map(function($r) { return $r->question_id; }, $all_user_responses));
        if (empty($question_ids)) {
            return [];
        }

        $q_placeholders = implode(',', array_fill(0, count($question_ids), '%d'));

        $all_correct_answers = $wpdb->get_results($wpdb->prepare("
            SELECT question_id, id
            FROM {$answers_table}
            WHERE question_id IN ({$q_placeholders})
            AND is_correct = 1
        ", $question_ids));

        $responses_by_user = [];
        foreach ($all_user_responses as $r) {
            $responses_by_user[$r->user_identifier][] = $r;
        }

        $correct_by_question = [];
        foreach ($all_correct_answers as $c) {
            $correct_by_question[$c->question_id][] = $c->id;
        }

        $leaderboard = [];
        foreach ($participants as $participant) {
            $correct_answers = 0;
            $total_scored_questions = 0;

            $user_responses = $responses_by_user[$participant->user_identifier] ?? [];

            foreach ($user_responses as $response) {
                $total_scored_questions++;

                $correct_answer_ids = $correct_by_question[$response->question_id] ?? [];

                if (!empty($response->selected_answer_ids) && !empty($correct_answer_ids)) {
                    $selected_ids = array_map('intval', explode(',', $response->selected_answer_ids));
                    $correct_ids = array_map('intval', $correct_answer_ids);

                    sort($selected_ids);
                    sort($correct_ids);

                    if ($selected_ids === $correct_ids) {
                        $correct_answers++;
                    }
                }
            }

            $score = $total_scored_questions > 0 ? round(($correct_answers / $total_scored_questions) * 100) : 0;

            $leaderboard[] = [
                'identifier' => $participant->user_identifier,
                'name' => $participant->user_identifier,
                'score' => $score,
                'correct_answers' => $correct_answers,
                'total_questions' => $total_scored_questions,
                'completion_time' => $participant->completion_time,
                'duration' => strtotime($participant->completion_time) - strtotime($participant->start_time)
            ];
        }

        usort($leaderboard, function($a, $b) {
            if ($a['score'] === $b['score']) {
                return strtotime($a['completion_time']) - strtotime($b['completion_time']);
            }
            return $b['score'] - $a['score'];
        });

        return array_slice($leaderboard, 0, $limit);
    }

    /**
     * Check if a column exists in a table
     */
    private function column_exists($table_name, $column_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . $table_name;
        $column = $wpdb->get_results($wpdb->prepare("
            SHOW COLUMNS FROM {$table_name} LIKE %s
        ", $column_name));

        return !empty($column);
    }
}