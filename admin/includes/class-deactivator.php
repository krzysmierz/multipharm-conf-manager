<?php

/**
 * Fired during plugin deactivation
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Deactivator {

    /**
     * Short Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('cm_cleanup_expired_responses');
        wp_clear_scheduled_hook('cm_auto_advance_presentations');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}