<?php

/**
 * QR Code generation class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_QR_Generator {

    /**
     * Generate QR code for event
     */
    public static function generate_event_qr($event_id) {
        $event_url = home_url("?cm_event=$event_id");
        return self::generate_qr_code($event_url, 'event', $event_id);
    }

    /**
     * Generate QR code for quiz
     */
    public static function generate_quiz_qr($quiz_id) {
        $quiz_url = home_url("/quiz/?cm_quiz=$quiz_id");
        return self::generate_qr_code($quiz_url, 'quiz', $quiz_id);
    }

    /**
     * Generate QR code for presentation
     */
    public static function generate_presentation_qr($lineup_id) {
        $presentation_url = home_url("?cm_presentation=$lineup_id");
        return self::generate_qr_code($presentation_url, 'presentation', $lineup_id);
    }

    /**
     * Generate QR code image using chillerlan/php-qrcode library
     * 
     * Uses local QR code generation for better reliability and privacy.
     * No external API calls required.
     */
    private static function generate_qr_code($data, $type, $target_id) {
        
        $upload_dir = wp_upload_dir();
        $qr_dir = $upload_dir['basedir'] . '/conference-manager/qr-codes/';
        
        if (!file_exists($qr_dir)) {
            wp_mkdir_p($qr_dir);
        }
        
        $filename = $type . '_' . $target_id . '_' . time() . '.png';
        $file_path = $qr_dir . $filename;
        $file_url = $upload_dir['baseurl'] . '/conference-manager/qr-codes/' . $filename;
        
        // Generate QR code using chillerlan/php-qrcode library
        $qr_size = get_option('cm_qr_size', 200);
        $qr_size = max(100, min(1000, $qr_size)); // Limit size to valid range
        
        // Generate QR code using local PHP library
        $image_data = bizconf_generate_qr($data, $qr_size);
        
        if ($image_data === false) {
            return new WP_Error('qr_generation_failed', 'Nie udało się wygenerować kodu QR - biblioteka QR niedostępna');
        }
        
        // Validate image data
        if (empty($image_data)) {
            return new WP_Error('qr_generation_failed', 'Otrzymano puste dane obrazu QR');
        }
        
        if (strlen($image_data) < 1000) { // PNG should be much larger than 1000 bytes
            return new WP_Error('qr_generation_failed', 'Otrzymano zbyt małe dane obrazu QR (' . strlen($image_data) . ' bajtów)');
        }
        
        // Check if it starts with PNG header
        if (substr($image_data, 0, 4) !== "\x89PNG") {
            return new WP_Error('qr_generation_failed', 'Otrzymano dane w nieprawidłowym formacie (nie PNG)');
        }
        
        // Save image to file
        $bytes_written = file_put_contents($file_path, $image_data);
        if ($bytes_written === false) {
            return new WP_Error('qr_file_save_failed', 'Nie udało się zapisać pliku QR');
        }
        
        // Final validation - check saved file
        if (!file_exists($file_path) || filesize($file_path) < 1000) {
            return new WP_Error('qr_file_validation_failed', 'Zapisany plik QR jest nieprawidłowy lub zbyt mały');
        }
        
        // Save QR code info to database
        $qr_data = array(
            'event_id' => self::get_event_id_from_target($type, $target_id),
            'code_type' => $type,
            'target_id' => $target_id,
            'qr_data' => $data,
            'file_path' => $file_path
        );
        
        $qr_id = CM_Database::insert('qr_codes', $qr_data);
        
        if (!is_wp_error($qr_id)) {
            return array(
                'id' => $qr_id,
                'url' => $file_url,
                'path' => $file_path,
                'data' => $data,
                'size' => $bytes_written
            );
        } else {
            // Clean up file if database insert failed
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            return new WP_Error('qr_db_insert_failed', 'Nie udało się zapisać informacji o kodzie QR w bazie danych');
        }
    }

    /**
     * Get event ID from target based on type
     */
    private static function get_event_id_from_target($type, $target_id) {
        switch ($type) {
            case 'event':
                return $target_id;
            case 'quiz':
                $quiz = CM_Database::get_row('quizzes', array('id' => $target_id));
                return $quiz ? $quiz->event_id : null;
            case 'presentation':
                $lineup = CM_Database::get_row('lineup', array('id' => $target_id));
                return $lineup ? $lineup->event_id : null;
            default:
                return null;
        }
    }

    /**
     * Get QR codes for event
     */
    public static function get_qr_codes_by_event($event_id) {
        return CM_Database::get_results('qr_codes', array('event_id' => $event_id), 'created_at DESC');
    }

    /**
     * Delete QR code
     */
    public static function delete_qr_code($qr_id) {
        $qr_code = CM_Database::get_row('qr_codes', array('id' => $qr_id));
        
        if ($qr_code) {
            // Delete physical file
            if (file_exists($qr_code->file_path)) {
                unlink($qr_code->file_path);
            }
            
            // Delete from database
            return CM_Database::delete('qr_codes', array('id' => $qr_id));
        }
        
        return false;
    }

    /**
     * Get QR code URL from file path
     */
    public static function get_qr_url($file_path) {
        if (empty($file_path) || !file_exists($file_path)) {
            return '';
        }
        
        $upload_dir = wp_upload_dir();
        $relative_path = str_replace($upload_dir['basedir'], '', $file_path);
        return $upload_dir['baseurl'] . $relative_path;
    }

    /**
     * Regenerate QR code
     */
    public static function regenerate_qr_code($qr_id) {
        $qr_code = CM_Database::get_row('qr_codes', array('id' => $qr_id));
        
        if ($qr_code) {
            // Delete old file
            if (file_exists($qr_code->file_path)) {
                unlink($qr_code->file_path);
            }
            
            // Delete old record
            CM_Database::delete('qr_codes', array('id' => $qr_id));
            
            // Generate new QR code
            return self::generate_qr_code($qr_code->qr_data, $qr_code->code_type, $qr_code->target_id);
        }
        
        return false;
    }
}