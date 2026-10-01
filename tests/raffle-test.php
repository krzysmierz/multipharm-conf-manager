<?php
/** Minimal standalone regression tests for the raffle domain rules. Run with: php tests/raffle-test.php */
if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }
function __($value, $domain = null) { return $value; }
function wp_unslash($value) { return is_string($value) ? stripslashes($value) : $value; }
function sanitize_text_field($value) { return is_scalar($value) ? trim((string) $value) : ''; }
function remove_accents($value) { return strtr($value, array('Ą'=>'A','ą'=>'a','Ć'=>'C','ć'=>'c','Ę'=>'E','ę'=>'e','Ł'=>'L','ł'=>'l','Ń'=>'N','ń'=>'n','Ó'=>'O','ó'=>'o','Ś'=>'S','ś'=>'s','Ź'=>'Z','ź'=>'z','Ż'=>'Z','ż'=>'z')); }
function absint($value) { return abs((int) $value); }
class WP_Error { private $message; public function __construct($code, $message) { $this->message = $message; } public function get_error_message() { return $this->message; } }
function is_wp_error($value) { return $value instanceof WP_Error; }
class CM_Event { public function __construct($id) {} public function get_id() { return 1; } }
class CM_Database {
    public static function get_table_name($name) { return 'cm_' . $name; }
    public static function get_row($name, $where) { global $wpdb; return $wpdb->get_row('SELECT * FROM cm_' . $name); }
    public static function insert($name, $data) { global $wpdb; if ($name === 'raffle_draws') { $wpdb->draws[] = $data; } return count($wpdb->draws); }
}
class TestWpdb {
    public $insert_id = 1; public $last_error = ''; public $draws = array(); private $participants = array();
    public function prepare($query) { $args = func_get_args(); array_shift($args); foreach ($args as $arg) { $query = preg_replace('/%[ds]/', is_int($arg) ? (string) $arg : "'" . addslashes((string) $arg) . "'", $query, 1); } return $query; }
    public function get_row($query) {
        if (strpos($query, 'cm_raffles') !== false) return (object) array('id'=>1, 'token'=>str_repeat('a', 48), 'event_id'=>1);
        if (strpos($query, 'LIMIT') !== false && count($this->participants)) {
            preg_match('/raffle_id\s*=\s*(\d+)/', $query, $raffle_match);
            preg_match('/LIMIT\s+(\d+)\s*,\s*1/', $query, $offset_match);
            $raffle_id = isset($raffle_match[1]) ? (int) $raffle_match[1] : 0;
            $offset = isset($offset_match[1]) ? (int) $offset_match[1] : 0;
            $rows = array_values(array_filter($this->participants, function ($row) use ($raffle_id) { return (int) $row['raffle_id'] === $raffle_id; }));
            return isset($rows[$offset]) ? (object) $rows[$offset] : null;
        }
        return null;
    }
    public function get_var($query) { preg_match('/raffle_id\s*=\s*(\d+)/', $query, $match); $id = isset($match[1]) ? (int) $match[1] : 0; return count(array_filter($this->participants, function ($row) use ($id) { return (int) $row['raffle_id'] === $id; })); }
    public function get_results($query) { return array(); }
    public function suppress_errors($value = null) { static $suppressed = false; $previous = $suppressed; if ($value !== null) { $suppressed = (bool) $value; } return $previous; }
    public function insert($table, $data, $formats) { foreach ($this->participants as $old) if ((int) $old['raffle_id'] === (int) $data['raffle_id'] && $old['identity_hash'] === $data['identity_hash']) { $this->last_error = 'Duplicate entry'; return false; } $this->participants[] = array_merge($data, array('id' => count($this->participants) + 1)); $this->insert_id++; return 1; }
}
global $wpdb; $wpdb = new TestWpdb();
require_once dirname(__DIR__) . '/includes/class-raffle.php';
$checks = 0;
function check_true($condition, $message) { global $checks; if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); } $checks++; }
check_true(CM_Raffle::validate_name('Jan', 'Imię') === 'Jan', 'valid name');
check_true(is_wp_error(CM_Raffle::validate_name(array('Jan'), 'Imię')), 'array input rejected');
check_true(is_wp_error(CM_Raffle::validate_name('', 'Imię')), 'empty name rejected');
check_true(is_wp_error(CM_Raffle::validate_name('Jan123', 'Imię')), 'digits rejected');
check_true(is_wp_error(CM_Raffle::validate_name(str_repeat('A', 101), 'Imię')), 'overlong name rejected');
check_true(CM_Raffle::validate_name("\xC2\xA0Jan\xC2\xA0", 'Imię') === 'Jan', 'NBSP whitespace trimmed');
check_true(CM_Raffle::normalized_name("  JAN\xE2\x80\x99", "  Kowalski  ") === "jan' kowalski", 'case, unicode whitespace and apostrophe normalization');
check_true(CM_Raffle::identity_hash("J\xC3\xB3zef", 'Lis') === CM_Raffle::identity_hash("Jo\xCC\x81zef", 'Lis'), 'precomposed and combining accents match');
check_true(CM_Raffle::identity_hash('Jan Adam', 'Kowalski') !== CM_Raffle::identity_hash('Jan', 'Adam Kowalski'), 'field boundary retained');
check_true(!is_wp_error(CM_Raffle::register_participant(1, 'Jan', 'Kowalski')), 'first registration accepted');
check_true(is_wp_error(CM_Raffle::register_participant(1, ' JAN ', 'KOWALSKI ')), 'duplicate scoped to raffle rejected atomically');
check_true(!is_wp_error(CM_Raffle::register_participant(2, 'Jan', 'Kowalski')), 'same identity accepted in another raffle');
check_true(CM_Raffle::identity_hash('Jan', 'Kowalski') === CM_Raffle::identity_hash('jan', 'kowalski'), 'case-insensitive identity');
$empty = new TestWpdb(); $wpdb = $empty;
check_true(is_wp_error(CM_Raffle::draw(1, 2)), 'empty raffle rejected');
$wpdb = new TestWpdb();
CM_Raffle::register_participant(1, 'Anna', 'Nowak');
CM_Raffle::register_participant(1, 'Piotr', 'Lis');
$draw = CM_Raffle::draw(1, 2);
check_true(!is_wp_error($draw), 'draw persists for non-empty raffle');
check_true(CM_Raffle::draw(1, 2)['participant']->id > 0, 'previous winner remains eligible on redraw');
check_true(count($wpdb->draws) === 2, 'draw history stores every draw');
$wpdb = new TestWpdb();
CM_Raffle::register_participant(1, 'Anna', 'Nowak');
CM_Raffle::register_participant(2, 'Piotr', 'Lis');
$first_draw = CM_Raffle::draw(1, 2);
$second_draw = CM_Raffle::draw(1, 2);
check_true($first_draw['participant']->first_name === 'Anna' && $second_draw['participant']->first_name === 'Anna', 'redraw retains previous winner and never includes another raffle');
check_true(count($wpdb->draws) === 2, 'single participant can win repeatedly with separate history');
$wpdb = new TestWpdb();
$generated = CM_Raffle::generate_test_participants(1);
check_true(!is_wp_error($generated) && $generated['generated'] === 30, 'test participant generator adds exactly thirty registrations');
check_true((int) $wpdb->get_var('SELECT COUNT(*) WHERE raffle_id = 1') === 30, 'generated registrations belong to the selected raffle');
echo "OK ({$checks} checks)\n";
