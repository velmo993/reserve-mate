<?php
namespace ReserveMate\Admin\Helpers;

defined('ABSPATH') or die('No direct access!');

class Staff {
    public static function save_staff_member($staff_data, $staff_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_staff_members';
        
        // Normalize working hours to use 1-7 (Monday-Sunday)
        if (isset($staff_data['working_hours']) && is_array($staff_data['working_hours'])) {
            $normalized_hours = [];
            
            // Define day mapping (your form uses 0=Sunday to 6=Saturday)
            $day_mapping = [
                0 => 7, // Sunday
                1 => 1, // Monday
                2 => 2, // Tuesday
                3 => 3, // Wednesday
                4 => 4, // Thursday
                5 => 5, // Friday
                6 => 6  // Saturday
            ];
            
            foreach ($staff_data['working_hours'] as $form_day => $periods) {
                if (isset($day_mapping[$form_day])) {
                    $normalized_day = $day_mapping[$form_day];
                    $normalized_hours[$normalized_day] = [];
                    
                    foreach ($periods as $period) {
                        if (!empty($period['start']) && !empty($period['end'])) {
                            $normalized_hours[$normalized_day][] = [
                                'start' => sanitize_text_field($period['start']),
                                'end' => sanitize_text_field($period['end'])
                            ];
                        }
                    }
                }
            }
            
            // Ensure all days are present in the normalized structure
            for ($day = 1; $day <= 7; $day++) {
                if (!isset($normalized_hours[$day])) {
                    $normalized_hours[$day] = [];
                }
            }
            
            // Sort by day number
            ksort($normalized_hours);
            $staff_data['working_hours'] = json_encode($normalized_hours);
        }
        
        $profile_image_url = '';
        if (!empty($staff_data['profile_image'])) {
            $attachment_id = absint($staff_data['profile_image']);
            $profile_image_url = wp_get_attachment_url($attachment_id);
        }
        
        // Rest of your save logic remains the same
        $data = [
            'name' => sanitize_text_field($staff_data['name']),
            'email' => sanitize_email($staff_data['email']),
            'phone' => sanitize_text_field($staff_data['phone'] ?? ''),
            'bio' => sanitize_textarea_field($staff_data['bio'] ?? ''),
            'profile_image' => $profile_image_url,
            'working_hours' => $staff_data['working_hours'] ?? '[]',
            'status' => in_array($staff_data['status'] ?? '', ['active', 'inactive']) ? $staff_data['status'] : 'active',
        ];
        
        // Link to WP user if provided
        if (!empty($staff_data['user_id'])) {
            $data['user_id'] = $staff_data['user_id'];
        }
        
        if ($staff_id) {
            // Update existing staff member
            $result = $wpdb->update($table_name, $data, ['id' => $staff_id]);
            return $result !== false ? $staff_id : false;
        } else {
            // Insert new staff member
            $result = $wpdb->insert($table_name, $data);
            return $result ? $wpdb->insert_id : false;
        }
    }

    // Assign service to staff member
    public static function assign_service_to_staff($staff_id, $service_id, $price_override = null, $duration_override = null, $custom_notes = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_staff_services';
        
        // Check if relationship already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE staff_id = %d AND service_id = %d",
            $staff_id, $service_id
        ));
        
        $data = [
            'staff_id' => $staff_id,
            'service_id' => $service_id,
            'price_override' => $price_override,
            'duration_override' => $duration_override,
            'custom_notes' => $custom_notes
        ];
        
        if ($exists) {
            // Update existing relationship
            return $wpdb->update($table_name, $data, ['id' => $exists]);
        } else {
            // Insert new relationship
            return $wpdb->insert($table_name, $data);
        }
    }

    // Remove service from staff member
    public static function remove_service_from_staff($staff_id, $service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_staff_services';
        
        return $wpdb->delete($table_name, [
            'staff_id' => $staff_id,
            'service_id' => $service_id
        ]);
    }

    // Get staff member by ID
    public static function get_staff_member($staff_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_staff_members';
        
        $staff = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $staff_id
        ), ARRAY_A);
        
        if ($staff && !empty($staff['working_hours'])) {
            $staff['working_hours'] = json_decode($staff['working_hours'], true);
        }
        
        return $staff;
    }

    // Get all staff members
    public static function get_staff_members($status = 'all') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_staff_members';
        
        $query = "SELECT * FROM $table_name";
        if ($status !== 'all') {
            $query .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        $query .= " ORDER BY name ASC";
        
        $staff = $wpdb->get_results($query, ARRAY_A);
        
        foreach ($staff as &$member) {
            if (!empty($member['working_hours'])) {
                $member['working_hours'] = json_decode($member['working_hours'], true);
            }
        }
        
        return $staff;
    }

    public static function get_staff_count() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}reservemate_staff_members");
        return (int) $count;
    }

    // Delete staff member
    public static function delete_staff_member($staff_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_staff_members';
        
        return $wpdb->delete($table_name, ['id' => $staff_id]);
    }

    // Get services by staff member
    public static function get_staff_services($staff_id) {
        global $wpdb;
        $staff_services_table = $wpdb->prefix . 'reservemate_staff_services';
        $services_table = $wpdb->prefix . 'reservemate_services';
        
        $services = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, ss.price_override, ss.duration_override, ss.custom_notes
            FROM $services_table s
            JOIN $staff_services_table ss ON s.id = ss.service_id
            WHERE ss.staff_id = %d
            ORDER BY s.name ASC",
            $staff_id
        ), ARRAY_A);
        
        foreach ($services as &$service) {
            if (!empty($service['time_slots'])) {
                $service['time_slots'] = explode(',', $service['time_slots']);
            }
        }
        
        return $services;
    }

    // Get available staff for a specific service
    public static function get_staff_for_service($service_id) {
        global $wpdb;
        $staff_services_table = $wpdb->prefix . 'reservemate_staff_services';
        $staff_table = $wpdb->prefix . 'reservemate_staff_members';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, ss.price_override, ss.duration_override
            FROM $staff_table s
            JOIN $staff_services_table ss ON s.id = ss.staff_id
            WHERE ss.service_id = %d AND s.status = 'active'
            ORDER BY s.name ASC",
            $service_id
        ), ARRAY_A);
    }
}