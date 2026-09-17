<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package    ConferenceManager
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete all plugin tables
$tables = array(
    $wpdb->prefix . 'cm_events',
    $wpdb->prefix . 'cm_lineup',
    $wpdb->prefix . 'cm_quizzes',
    $wpdb->prefix . 'cm_quiz_questions',
    $wpdb->prefix . 'cm_quiz_answers',
    $wpdb->prefix . 'cm_user_responses',
    $wpdb->prefix . 'cm_qr_codes'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Delete all plugin options
$options = array(
    'cm_qr_size',
    'cm_allow_file_types',
    'cm_max_file_size',
    'cm_quiz_time_limit',
    'cm_auto_advance_presentations'
);

foreach ($options as $option) {
    delete_option($option);
}

// Delete upload directory and files
$upload_dir = wp_upload_dir();
$cm_upload_dir = $upload_dir['basedir'] . '/conference-manager';

if (file_exists($cm_upload_dir)) {
    // Recursively delete directory and all files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cm_upload_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }

    rmdir($cm_upload_dir);
}