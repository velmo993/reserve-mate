<?php
namespace ReserveMate\Admin\Helpers\Traits;

trait TableCreator {
    protected function create_table($table_name, $sql_definition) {
        global $wpdb;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            $charset_collate = $wpdb->get_charset_collate();
            $sql = sprintf($sql_definition, $table_name, $charset_collate);
            
            $result = dbDelta($sql);
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                error_log("Failed to create table: $table_name");
                return false;
            }
        }
        return true;
    }
}