<?php
defined('ABSPATH') or die('No direct access!');

// Create staff members table
function create_staff_members_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_staff_members';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
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
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) ENGINE=InnoDB $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("Error: Failed to create table $table_name");
        }
    }
}

// Create staff to services relationship table
function create_staff_services_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_staff_services';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
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
        ) ENGINE=InnoDB $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("Error: Failed to create table $table_name");
        }
    }
}

// Modify hourly_bookings table to add staff_id column
function modify_hourly_bookings_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_hourly_bookings';
    
    // Check if staff_id column already exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'staff_id'");
    
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN staff_id MEDIUMINT(9) NULL AFTER id");
        
        // Add foreign key constraint if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}reservemate_staff_members'") == "{$wpdb->prefix}reservemate_staff_members") {
            $wpdb->query("ALTER TABLE $table_name ADD CONSTRAINT fk_booking_staff 
                          FOREIGN KEY (staff_id) REFERENCES {$wpdb->prefix}reservemate_staff_members(id) 
                          ON DELETE SET NULL");
        }
    }
}

function save_staff_member($staff_data, $staff_id = null) {
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
function assign_service_to_staff($staff_id, $service_id, $price_override = null, $duration_override = null, $custom_notes = '') {
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
function remove_service_from_staff($staff_id, $service_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_staff_services';
    
    return $wpdb->delete($table_name, [
        'staff_id' => $staff_id,
        'service_id' => $service_id
    ]);
}

// Get staff member by ID
function get_staff_member($staff_id) {
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
function get_staff_members($status = 'all') {
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

// Delete staff member
function delete_staff_member($staff_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_staff_members';
    
    return $wpdb->delete($table_name, ['id' => $staff_id]);
}

// Get services by staff member
function get_staff_services($staff_id) {
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
function get_staff_for_service($service_id) {
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

function get_staff_availability_for_day($date, $service_ids) {
    global $wpdb;
    
    if (empty($service_ids)) {
        return [];
    }
    
    $qualified_staff = $wpdb->get_results($wpdb->prepare(
        "SELECT s.* 
        FROM {$wpdb->prefix}reservemate_staff_members s
        WHERE s.status = 'active'
        AND NOT EXISTS (
            SELECT 1 
            FROM (SELECT %d as service_id " . 
            str_repeat("UNION ALL SELECT %d ", count($service_ids) - 1) . 
            ") AS required_services
            WHERE NOT EXISTS (
                SELECT 1 
                FROM {$wpdb->prefix}reservemate_staff_services ss
                WHERE ss.staff_id = s.id 
                AND ss.service_id = required_services.service_id
            )
        )",
        ...$service_ids
    ));
    
    if (empty($qualified_staff)) {
        return [];
    }
    
    $settings = get_option('booking_settings');
    $min_time = explode(':', $settings['hourly_min_time']);
    $max_time = explode(':', $settings['hourly_max_time']);
    $interval = intval($settings['hourly_booking_interval']);
    $break_duration = intval($settings['hourly_break_duration'] ?? 0);
    $start_of_day = new DateTime($date . ' ' . $settings['hourly_min_time']);
    $end_of_day = new DateTime($date . ' ' . $settings['hourly_max_time']);
    
    $staff_ids = array_map(function($staff) {
        return $staff->id;
    }, $qualified_staff);
    
    $staff_id_placeholders = implode(',', array_fill(0, count($staff_ids), '%d'));
    
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT staff_id, start_datetime, end_datetime 
         FROM {$wpdb->prefix}reservemate_hourly_bookings
         WHERE staff_id IN ($staff_id_placeholders)
         AND DATE(start_datetime) = %s",
        array_merge($staff_ids, [$date])
    ));
    
    $staff_bookings = [];
    foreach ($bookings as $booking) {
        if (!isset($staff_bookings[$booking->staff_id])) {
            $staff_bookings[$booking->staff_id] = [];
        }
        $staff_bookings[$booking->staff_id][] = [
            'start' => new DateTime($booking->start_datetime),
            'end' => new DateTime($booking->end_datetime)
        ];
    }
    
    $day_of_week = date('N', strtotime($date)); // 1-7 (Monday-Sunday)
    $result = [];
    $current_time = clone $start_of_day;
    
    while ($current_time < $end_of_day) {
        $slot_start = clone $current_time;
        $slot_end = clone $current_time;
        $slot_end->modify("+{$interval} minutes");
        
        if ($slot_end > $end_of_day) {
            break;
        }
        
        $time_key = $slot_start->format('H:i') . '-' . $slot_end->format('H:i');
        $result[$time_key] = [];
        
        foreach ($qualified_staff as $staff) {
            $working_hours = json_decode($staff->working_hours, true);
            
            if (empty($working_hours[$day_of_week])) {
                continue;
            }
            
            $time_available = false;
            foreach ($working_hours[$day_of_week] as $shift) {
                if ($shift['start'] === '00:00' && $shift['end'] === '00:00') {
                    continue;
                }
                
                $shift_start = new DateTime($date . ' ' . $shift['start']);
                $shift_end = new DateTime($date . ' ' . $shift['end']);
                
                if ($slot_start >= $shift_start && $slot_end <= $shift_end) {
                    $time_available = true;
                    break;
                }
            }
            
            if (!$time_available) {
                continue;
            }
            
            $has_conflict = false;
            if (isset($staff_bookings[$staff->id])) {
                foreach ($staff_bookings[$staff->id] as $booking) {
                    if ($booking['start'] < $slot_end && $booking['end'] > $slot_start) {
                        $has_conflict = true;
                        break;
                    }
                }
            }
            
            if (!$has_conflict) {
                $result[$time_key][] = [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'bio' => $staff->bio,
                    'profile_image' => $staff->profile_image ?: '',
                    'services' => get_staff_services($staff->id)
                ];
            } else {
                error_log("Staff " . $staff->id . " is NOT available for time slot " . $time_key . " due to booking conflict");
            }
        }
        
        $current_time->modify("+{$interval} minutes");
        if ($break_duration > 0) {
            $current_time->modify("+{$break_duration} minutes");
        }
    }
    
    return $result;
}

function ajax_get_staff_availability_for_day() {
    check_ajax_referer('custom_flatpickr_nonce', 'nonce');
    
    $date = sanitize_text_field($_POST['date']);
    $service_ids = json_decode(stripslashes($_POST['service_ids']));
    
    if (empty($service_ids) || !is_array($service_ids)) {
        wp_send_json_error(['message' => 'Invalid service selection']);
        return;
    }
    
    $service_ids = array_map('intval', $service_ids);
    
    foreach ($service_ids as $service_id) {
        $service = get_service($service_id);
        if (!$service) {
            wp_send_json_error(['message' => 'Invalid service ID: ' . $service_id]);
            return;
        }
    }
    
    $availability = get_staff_availability_for_day($date, $service_ids);
    wp_send_json_success($availability);
}

add_action('wp_ajax_get_staff_availability_for_day', 'ajax_get_staff_availability_for_day');
add_action('wp_ajax_nopriv_get_staff_availability_for_day', 'ajax_get_staff_availability_for_day');