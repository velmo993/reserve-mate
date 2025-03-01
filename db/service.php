<?php

// Create the services table if it doesn't exist
function create_services_table() {
    global $wpdb;
    $table_name = get_services_table_name();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            duration int(11) NOT NULL,
            price decimal(10,2) NOT NULL,
            max_capacity int(11),
            allow_multiple tinyint(1) DEFAULT 0,
            time_slots varchar(255),
            additional_notes text,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

// Get the services table name
function get_services_table_name() {
    global $wpdb;
    return esc_sql($wpdb->prefix . 'reservemate_services');
}

// Save or update a service
function save_service($service_data, $service_id = null) {
    global $wpdb;
    $table_name = get_services_table_name();

    $data = [
        'name'              => $service_data['name'],
        'description'       => $service_data['description'],
        'duration'          => $service_data['duration'],
        'price'             => $service_data['price'],
        'max_capacity'      => $service_data['max_capacity'],
        'allow_multiple'    => $service_data['allow_multiple'],
        'time_slots'        => $service_data['time_slots'],
        'additional_notes'  => $service_data['additional_notes'],
    ];

    if ($service_id) {
        $wpdb->update($table_name, $data, ['id' => $service_id]);
    } else {
        $wpdb->insert($table_name, $data);
    }
}

// Delete a service
function delete_service($service_id) {
    global $wpdb;
    $table_name = get_services_table_name();
    if ($service_id) {
        $wpdb->delete($table_name, ['id' => intval($service_id)]);
    }
}

// Get all services
function get_services() {
    global $wpdb;
    $table_name = get_services_table_name();
    $services = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);

    if ($services) {
        foreach ($services as $service) {
            $service = format_service_data($service);
        }
    }

    return $services;
}

// Get a single service by ID
function get_service($service_id) {
    global $wpdb;
    $table_name = get_services_table_name();
    $service = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($service_id)));
    return format_service_data($service);
}

// Get all service IDs
function get_service_ids() {
    global $wpdb;
    $table_name = get_services_table_name();
    return $wpdb->get_col("SELECT id FROM $table_name");
}

// Format service data (if needed)
function format_service_data($service) {
    if ($service) {
        // Format time slots if necessary
        if (!empty($service->time_slots)) {
            $service->time_slots = explode(',', $service->time_slots);
        }
    }
    return $service;
}