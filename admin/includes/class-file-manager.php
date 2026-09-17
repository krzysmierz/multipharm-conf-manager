<?php

/**
 * File management class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_File_Manager {

    /**
     * Upload presentation file
     */
    public static function upload_presentation_file($file, $event_id) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return new WP_Error('no_file', 'No file was uploaded');
        }

        // Validate file type
        $allowed_types = get_option('cm_allow_file_types', array('ppt', 'pptx', 'pdf'));
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return new WP_Error('invalid_type', 'File type not allowed');
        }

        // Validate file size
        $max_size = get_option('cm_max_file_size', 10) * 1024 * 1024; // Convert MB to bytes
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', 'File is too large');
        }

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $cm_upload_dir = $upload_dir['basedir'] . '/conference-manager/presentations/';
        
        if (!file_exists($cm_upload_dir)) {
            wp_mkdir_p($cm_upload_dir);
        }

        // Generate unique filename
        $filename = self::generate_unique_filename($file['name'], $event_id);
        $file_path = $cm_upload_dir . $filename;
        $file_url = $upload_dir['baseurl'] . '/conference-manager/presentations/' . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return array(
                'filename' => $filename,
                'path' => $file_path,
                'url' => $file_url,
                'size' => $file['size']
            );
        } else {
            return new WP_Error('upload_failed', 'Failed to move uploaded file');
        }
    }

    /**
     * Upload image file
     */
    public static function upload_image_file($file, $event_id) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return new WP_Error('no_file', 'No file was uploaded');
        }

        // Validate image type
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return new WP_Error('invalid_type', 'Image type not allowed');
        }

        // Validate image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return new WP_Error('invalid_image', 'File is not a valid image');
        }

        // Validate file size
        $max_size = get_option('cm_max_file_size', 10) * 1024 * 1024;
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', 'Image is too large');
        }

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $cm_upload_dir = $upload_dir['basedir'] . '/conference-manager/images/';
        
        if (!file_exists($cm_upload_dir)) {
            wp_mkdir_p($cm_upload_dir);
        }

        // Generate unique filename
        $filename = self::generate_unique_filename($file['name'], $event_id);
        $file_path = $cm_upload_dir . $filename;
        $file_url = $upload_dir['baseurl'] . '/conference-manager/images/' . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return array(
                'filename' => $filename,
                'path' => $file_path,
                'url' => $file_url,
                'size' => $file['size'],
                'dimensions' => array(
                    'width' => $image_info[0],
                    'height' => $image_info[1]
                )
            );
        } else {
            return new WP_Error('upload_failed', 'Failed to move uploaded file');
        }
    }

    /**
     * Generate unique filename
     */
    private static function generate_unique_filename($original_name, $event_id) {
        $file_info = pathinfo($original_name);
        $clean_name = sanitize_file_name($file_info['filename']);
        $extension = strtolower($file_info['extension']);
        
        $timestamp = current_time('timestamp');
        $unique_id = wp_generate_password(8, false, false);
        
        return "event_{$event_id}_{$clean_name}_{$timestamp}_{$unique_id}.{$extension}";
    }

    /**
     * Delete file
     */
    public static function delete_file($file_path) {
        if (file_exists($file_path)) {
            // Security check - ensure file is in our upload directory
            $upload_dir = wp_upload_dir();
            $cm_upload_dir = $upload_dir['basedir'] . '/conference-manager/';
            
            if (strpos($file_path, $cm_upload_dir) !== 0) {
                return new WP_Error('security_violation', 'Cannot delete file outside plugin directory');
            }
            
            if (unlink($file_path)) {
                return true;
            } else {
                return new WP_Error('delete_failed', 'Failed to delete file');
            }
        }
        
        return new WP_Error('file_not_found', 'File not found');
    }

    /**
     * Get files for event
     */
    public static function get_event_files($event_id) {
        $upload_dir = wp_upload_dir();
        $presentations_dir = $upload_dir['basedir'] . '/conference-manager/presentations/';
        $images_dir = $upload_dir['basedir'] . '/conference-manager/images/';
        
        $files = array(
            'presentations' => array(),
            'images' => array()
        );
        
        // Get presentation files
        if (is_dir($presentations_dir)) {
            $presentation_files = glob($presentations_dir . "event_{$event_id}_*");
            foreach ($presentation_files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    $files['presentations'][] = array(
                        'filename' => $filename,
                        'path' => $file,
                        'url' => $upload_dir['baseurl'] . '/conference-manager/presentations/' . $filename,
                        'size' => filesize($file),
                        'modified' => filemtime($file)
                    );
                }
            }
        }
        
        // Get image files
        if (is_dir($images_dir)) {
            $image_files = glob($images_dir . "event_{$event_id}_*");
            foreach ($image_files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    $image_info = getimagesize($file);
                    $files['images'][] = array(
                        'filename' => $filename,
                        'path' => $file,
                        'url' => $upload_dir['baseurl'] . '/conference-manager/images/' . $filename,
                        'size' => filesize($file),
                        'modified' => filemtime($file),
                        'dimensions' => array(
                            'width' => $image_info[0],
                            'height' => $image_info[1]
                        )
                    );
                }
            }
        }
        
        return $files;
    }

    /**
     * Clean up unused files
     */
    public static function cleanup_unused_files() {
        global $wpdb;
        
        // Get all used presentation files from lineup table
        $lineup_table = CM_Database::get_table_name('lineup');
        $used_files = $wpdb->get_col("SELECT DISTINCT presentation_file FROM $lineup_table WHERE presentation_file IS NOT NULL AND presentation_file != ''");
        
        $upload_dir = wp_upload_dir();
        $presentations_dir = $upload_dir['basedir'] . '/conference-manager/presentations/';
        
        if (is_dir($presentations_dir)) {
            $all_files = glob($presentations_dir . '*');
            
            foreach ($all_files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    
                    // If file is not in used files list and is older than 24 hours
                    if (!in_array($filename, $used_files) && (time() - filemtime($file)) > 86400) {
                        unlink($file);
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Get file type icon
     */
    public static function get_file_type_icon($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $icons = array(
            'pdf' => 'dashicons-pdf',
            'ppt' => 'dashicons-slides',
            'pptx' => 'dashicons-slides',
            'jpg' => 'dashicons-format-image',
            'jpeg' => 'dashicons-format-image',
            'png' => 'dashicons-format-image',
            'gif' => 'dashicons-format-image'
        );
        
        return isset($icons[$extension]) ? $icons[$extension] : 'dashicons-media-default';
    }

    /**
     * Format file size
     */
    public static function format_file_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $unit_index = 0;
        
        while ($bytes >= 1024 && $unit_index < count($units) - 1) {
            $bytes /= 1024;
            $unit_index++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit_index];
    }
}