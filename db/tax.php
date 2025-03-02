<?php
defined('ABSPATH') or die('No direct access!');

function create_taxes_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "reservemate_taxes";

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            rate decimal(10,2) NOT NULL,
            type ENUM('percentage', 'fixed', 'per_person_per_night') NOT NULL DEFAULT 'percentage',
            PRIMARY KEY  (id)
        ) ENGINE=InnoDB $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);

        // Check if table exists after creation
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("Error: Failed to create table $table_name");
        }
    } else {
        // error_log("$table_name table already exists.");
        return;
    }
}

function add_tax($name, $rate, $type) {
    global $wpdb;
    $table_name = $wpdb->prefix . "reservemate_taxes";

    $wpdb->insert(
        $table_name,
        [
            'name' => sanitize_text_field($name),
            'rate' => floatval($rate),
            'type' => in_array($type, ['percentage', 'fixed', 'per_person_per_night']) ? $type : 'percentage',
        ],
        ['%s', '%f', '%s']
    );
}

function delete_tax($tax_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . "reservemate_taxes";

    $wpdb->delete($table_name, ['id' => intval($tax_id)], ['%d']);
}

function get_taxes() {
    global $wpdb;
    $table_name = $wpdb->prefix . "reservemate_taxes";
    $taxes = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);
    if($taxes) {
        return $taxes;
    }
}