<?php

/**
 * Database operations class
 *
 * @package    ConferenceManager
 * @subpackage ConferenceManager/includes
 */

class CM_Database {

    /**
     * Get table name with WordPress prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'cm_' . $table;
    }

    /**
     * Insert data into a table
     */
    public static function insert($table, $data) {
        global $wpdb;
        
        $table_name = self::get_table_name($table);
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            return new WP_Error('db_insert_error', $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Update data in a table
     */
    public static function update($table, $data, $where) {
        global $wpdb;
        
        $table_name = self::get_table_name($table);
        
        $result = $wpdb->update($table_name, $data, $where);
        
        if ($result === false) {
            return new WP_Error('db_update_error', $wpdb->last_error);
        }
        
        return $result;
    }

    /**
     * Delete data from a table
     */
    public static function delete($table, $where) {
        global $wpdb;
        
        $table_name = self::get_table_name($table);
        
        $result = $wpdb->delete($table_name, $where);
        
        if ($result === false) {
            return new WP_Error('db_delete_error', $wpdb->last_error);
        }
        
        return $result;
    }

    /**
     * Get data from a table
     */
    public static function get_results($table, $where = array(), $orderby = '', $limit = '') {
        global $wpdb;

        $table_name = self::get_table_name($table);

        $sql = "SELECT * FROM $table_name";

        if (!empty($where)) {
            $conditions = array();
            $allowed_columns = array('id', 'event_id', 'quiz_id', 'question_id', 'user_identifier', 'is_active', 'day_number');

            foreach ($where as $column => $value) {
                if (!in_array($column, $allowed_columns)) {
                    continue;
                }
                $conditions[] = $wpdb->prepare("$column = %s", $value);
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }
        }

        if (!empty($orderby)) {
            $allowed_orderby = array('id', 'sort_order', 'created_at', 'submitted_at', 'start_time');
            $orderby_parts = explode(' ', $orderby);
            $column = $orderby_parts[0];

            if (!in_array($column, $allowed_orderby)) {
                $orderby = 'id ASC';
            }
        }

        if (!empty($orderby)) {
            $sql .= " ORDER BY $orderby";
        }

        if (!empty($limit)) {
            $limit = intval($limit);
            if ($limit > 0) {
                $sql .= " LIMIT $limit";
            }
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Get single row from a table
     */
    public static function get_row($table, $where = array()) {
        global $wpdb;
        
        $table_name = self::get_table_name($table);
        
        $sql = "SELECT * FROM $table_name";
        
        if (!empty($where)) {
            $conditions = array();
            foreach ($where as $column => $value) {
                $conditions[] = $wpdb->prepare("$column = %s", $value);
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " LIMIT 1";
        
        return $wpdb->get_row($sql);
    }
}