<?php
/** Regression tests for controlled storage and safe rendering of event CSS. */
if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }
function __($value, $domain = null) { return $value; }
function absint($value) { return abs((int) $value); }
function wp_unslash($value) { return is_string($value) ? stripslashes($value) : $value; }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function get_option($name, $default = false) { global $cm_test_options; return $cm_test_options[$name] ?? $default; }
function update_option($name, $value, $autoload = null) { global $cm_test_options; $cm_test_options[$name] = $value; return true; }
function delete_option($name) { global $cm_test_options; unset($cm_test_options[$name]); return true; }
class WP_Error { private $message; public function __construct($code, $message) { $this->message = $message; } public function get_error_message() { return $this->message; } }
function is_wp_error($value) { return $value instanceof WP_Error; }
class CM_Database { public static function get_row($table, $where) { return null; } }
require_once dirname(__DIR__) . '/includes/class-event.php';

$checks = 0;
function cm_css_check($condition, $message) { global $checks; if (!$condition) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); } $checks++; }
$valid = CM_Event::validate_custom_css_settings(array('schedule' => '.cm-event-theme-7 { color: red; }', 'raffle' => '.cm-event-theme-7 .cm-raffle-presentation__card { background: #fff; }'));
cm_css_check(!is_wp_error($valid), 'valid CSS is accepted');
$escaped_css = ".cm-event-theme-7::before { content: '\\2713'; }";
cm_css_check(CM_Event::validate_custom_css_settings(array('schedule' => $escaped_css, 'raffle' => ''))['schedule'] === $escaped_css, 'CSS validator preserves backslash escapes');
cm_css_check(is_wp_error(CM_Event::validate_custom_css_settings(array('schedule' => '</style><script>alert(1)</script>', 'raffle' => ''))), 'style-element breakout is rejected');
cm_css_check(is_wp_error(CM_Event::validate_custom_css_settings(array('schedule' => '<img src=x>', 'raffle' => ''))), 'HTML tags are rejected');
$one = new CM_Event(); $two = new CM_Event();
$property = new ReflectionProperty('CM_Event', 'id'); $property->setAccessible(true); $property->setValue($one, 7); $property->setValue($two, 8);
cm_css_check(!is_wp_error($one->set_custom_css_settings($valid)), 'CSS persists for one event');
cm_css_check($one->get_custom_css_settings()['schedule'] === $valid['schedule'], 'stored schedule CSS is returned');
cm_css_check(!is_wp_error($one->set_custom_css_settings(array('schedule' => $escaped_css, 'raffle' => ''))) && $one->get_custom_css_settings()['schedule'] === $escaped_css, 'stored CSS preserves backslash escapes');
cm_css_check($two->get_custom_css_settings()['schedule'] === '', 'CSS never leaks into another event');
cm_css_check(strpos($one->get_custom_css_style_tag('schedule'), '<style id="cm-event-schedule-css-7">') === 0, 'CSS emits one event-specific style tag');
echo "OK ({$checks} checks)\n";
