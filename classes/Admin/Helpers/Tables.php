<?php
namespace ReserveMate\Admin\Helpers;

defined('ABSPATH') or die('No direct access!');

use ReserveMate\Admin\Helpers\Traits\TableCreator;

class Tables {
    use TableCreator;
    
    public function create_all_tables() {
        return [
            'staff_members' => $this->create_staff_members_table(),
            'bookings' => $this->create_bookings_table(),
            'services' => $this->create_services_table(),
            'booking_services' => $this->create_booking_services_table(),
            'staff_services' => $this->create_staff_services_table(),
            'custom_fields' => $this->create_custom_fields_table(),
            'booking_limits' => $this->create_booking_limits_table(),
            'taxes' => $this->create_taxes_table()
        ];
    }
    
    public function create_bookings_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_bookings';
        
        $sql = "CREATE TABLE %s (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            staff_id MEDIUMINT(9) NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            start_datetime DATETIME NOT NULL,
            end_datetime DATETIME NOT NULL,
            total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            paid_amount DECIMAL(10,2) NULL DEFAULT 0.00,
            payment_method VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            custom_fields JSON NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB %s;";
        
        return $this->create_table($table_name, $sql);
    }
    
    public function create_booking_services_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_booking_services';
        
        $sql = "CREATE TABLE %s (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            booking_id MEDIUMINT(9) NOT NULL,
            service_id MEDIUMINT(9) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}reservemate_bookings(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}reservemate_services(id) ON DELETE CASCADE
        ) ENGINE=InnoDB %s;";
        
        return $this->create_table($table_name, $sql);
    }
    
    public function create_booking_limits_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_booking_limits';

        $sql = "CREATE TABLE %s (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            service_id MEDIUMINT(9) NULL,  /* NULL = global rule */
            limit_type ENUM('day', 'week', 'month', 'service') NOT NULL,
            max_bookings INT NOT NULL,
            applies_to ENUM('all', 'per_user') NOT NULL DEFAULT 'per_user',
            PRIMARY KEY (id),
            FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}reservemate_services(id) ON DELETE CASCADE
        ) ENGINE=InnoDB %s;";
    
        return $this->create_table($table_name, $sql);
    }
    
     public function create_services_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_services';
    
        $sql = "CREATE TABLE %s (
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
        ) ENGINE=InnoDB %s;";
    
        return $this->create_table($table_name, $sql);
    
    }
    
    public function create_staff_members_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_staff_members';

        $sql = "CREATE TABLE %s (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) NULL,  /* Optional link to WordPress user */
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NULL,
            bio TEXT NULL,
            profile_image VARCHAR(255) NULL,
            working_hours TEXT NULL,  /* JSON storing working schedule */
            status VARCHAR(20) NOT NULL DEFAULT 'active',  /* active, inactive */
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB %s;";
    
        return $this->create_table($table_name, $sql);
    }
    
    public function create_staff_services_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_staff_services';

        $sql = "CREATE TABLE %s (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            staff_id MEDIUMINT(9) NOT NULL,
            service_id MEDIUMINT(9) NOT NULL,
            price_override DECIMAL(10,2) NULL,  /* Optional custom price */
            duration_override INT NULL,         /* Optional custom duration in minutes */
            custom_notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY staff_service (staff_id, service_id),
            FOREIGN KEY (staff_id) REFERENCES {$wpdb->prefix}reservemate_staff_members(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}reservemate_services(id) ON DELETE CASCADE
        ) ENGINE=InnoDB %s;";
    
        return $this->create_table($table_name, $sql);
    }
    
    public function create_taxes_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . "reservemate_taxes";

        $sql = "CREATE TABLE %s (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            rate decimal(10,2) NOT NULL,
            type ENUM('percentage', 'fixed', 'per_person') NOT NULL DEFAULT 'percentage',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_type (type)
        ) ENGINE=InnoDB %s;";
    
        return $this->create_table($table_name, $sql);
    }
    
    function create_custom_fields_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_booking_custom_fields';
        
        $sql = "CREATE TABLE %s (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            field_key varchar(100) NOT NULL,
            field_value longtext NOT NULL,
            PRIMARY KEY (id),
            KEY booking_id (booking_id)
        ) ENGINE=InnoDB %s;";
        
        return $this->create_table($table_name, $sql);
    }
    
}
