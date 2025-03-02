<?php
defined('ABSPATH') or die('No direct access!');

// Create the properties table if it doesn't exist
function create_properties_table() {
    global $wpdb;
    $table_name = get_properties_table_name();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            max_adult_number int(11) NOT NULL,
            adult_price decimal(10,2) NULL,
            allow_children tinyint(1) NOT NULL DEFAULT 0,
            max_child_number int(11) NOT NULL,
            child_price decimal(10,2) NULL,
            allow_pets tinyint(1) NOT NULL DEFAULT 0,
            max_pet_number int(11) NOT NULL,
            pet_price decimal(10,2) NULL,
            min_stay int(11) NULL,
            max_stay int(11) NULL,
            partial_days tinyint(1) NOT NULL DEFAULT 0,
            check_in_time_start TIME NULL,
            check_in_time_end TIME NULL,
            check_out_time_start TIME NULL,
            check_out_time_end TIME NULL,
            seasonal_rules TEXT NULL,
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

function get_property_id_from_slug() {
    global $wpdb;
    $slug = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

    return $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}reservemate_properties WHERE name = %s LIMIT 1",
        $slug
    ));
}

function get_properties_table_name() {
    global $wpdb;
    return esc_sql($wpdb->prefix . 'reservemate_properties');
}

function save_property($property_data, $property_id = null) {
    global $wpdb;
    $table_name = get_properties_table_name();

    $seasonal_rules = isset($_POST['seasonal_rules']) ? json_encode($_POST['seasonal_rules']) : null;

    $data = [
        'name'              => $property_data['name'],
        'max_adult_number'  => $property_data['max_adult_number'],
        'adult_price'       => $property_data['adult_price'],
        'allow_children'    => $property_data['allow_children'],
        'max_child_number'  => $property_data['max_child_number'],
        'child_price'       => $property_data['child_price'],
        'allow_pets'        => $property_data['allow_pets'],
        'max_pet_number'    => $property_data['max_pet_number'],
        'pet_price'         => $property_data['pet_price'],
        'min_stay'          => $property_data['min_stay'],
        'max_stay'          => $property_data['max_stay'],
        'partial_days'      => $property_data['partial_days'],
        'check_in_time_start'     => $property_data['check_in_time_start'],
        'check_in_time_end'     => $property_data['check_in_time_end'],
        'check_out_time_start'    => $property_data['check_out_time_start'],
        'check_out_time_end'    => $property_data['check_out_time_end'],
        'seasonal_rules'    => $seasonal_rules
    ];
    if ($property_id) {
        $wpdb->update($table_name, $data, ['id' => $property_id]);
    } else {
        $wpdb->insert($table_name, $data);
    }
}

function delete_property($property_id) {
    global $wpdb;
    $table_name = get_properties_table_name();
    if($property_id) {
        $wpdb->delete($table_name, ['id' => intval($property_id)]);
    }
}

function get_properties() {
    global $wpdb;
    $table_name = get_properties_table_name();
    $properties = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);

    if ($properties) {
        foreach ($properties as $property) {
            $property = format_property_times($property);
        }
    }

    return $properties;
}

function get_property($property_id) {
    global $wpdb;
    $table_name = get_properties_table_name();
    $property = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($property_id)));
    return format_property_times($property);
}

function get_property_ids() {
    global $wpdb;
    $table_name = get_properties_table_name();

    return $wpdb->get_col("SELECT id FROM $table_name");
}

function format_property_times($property) {
    if ($property) {
        // Format time fields to exclude seconds
        $property->check_in_time_start = !empty($property->check_in_time_start) ? date('H:i', strtotime($property->check_in_time_start)) : '';
        $property->check_in_time_end = !empty($property->check_in_time_end) ? date('H:i', strtotime($property->check_in_time_end)) : '';
        $property->check_out_time_start = !empty($property->check_out_time_start) ? date('H:i', strtotime($property->check_out_time_start)) : '';
        $property->check_out_time_end = !empty($property->check_out_time_end) ? date('H:i', strtotime($property->check_out_time_end)) : '';
    }
    return $property;
}
