<?php
namespace ReserveMate\Admin\Helpers;

defined('ABSPATH') or die('No direct access!');

class Service {
    // Save or update a service
    public static function save_service($service_data, $service_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_services';

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
            $result = $wpdb->update($table_name, $data, ['id' => $service_id]);
        } else {
            $result = $wpdb->insert($table_name, $data);
        }
    }

    // Delete a service
    public static function delete_service($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_services';
        if ($service_id) {
            $wpdb->delete($table_name, ['id' => intval($service_id)]);
        }
    }

    // Get all services
    public static function get_services() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_services';
        $services = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);

        if (!empty($services)) {
            foreach ($services as $service) {
                $service = self::format_service_data($service);
            }
        }
    
        return $services ? $services : array();
    }

    // Get a single service by ID
    public static function get_service($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_services';
        $service = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($service_id)));
        return self::format_service_data($service);
    }

    // Get all service IDs
    public static function get_service_ids() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_services';
        return $wpdb->get_col("SELECT id FROM $table_name");
    }

    public static function get_service_name($service_id) {
        global $wpdb;
        $service_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}reservemate_services WHERE id = %d", intval($service_id)));
        return $service_name;
    }

    public static function format_service_data($service) {
        if ($service) {
            if (!empty($service->time_slots)) {
                $service->time_slots = explode(',', $service->time_slots);
            }
        }
        return $service;
    }
}