<?php
namespace ReserveMate\Frontend\Handlers;

defined('ABSPATH') or die('No direct access!');

use ReserveMate\Admin\Helpers\Staff;
use ReserveMate\Admin\Helpers\Tax;
use ReserveMate\Admin\Helpers\Service;
use ReserveMate\Admin\Helpers\Booking;
use DateTime;

class AjaxHandler {
    
    /**
     * Initialize ajax related hooks
     */
    public static function init() {
        add_action('wp_ajax_get_date_time_bookings_data', [self::class, 'get_date_time_bookings_data']);
        add_action('wp_ajax_nopriv_get_date_time_bookings_data', [self::class, 'get_date_time_bookings_data']);
        add_action('wp_ajax_get_staff_availability_for_day', [self::class, 'get_staff_availability_for_day']);
        add_action('wp_ajax_nopriv_get_staff_availability_for_day', [self::class, 'get_staff_availability_for_day']);
        add_action('wp_ajax_check_booking_limit', [self::class, 'check_booking_limit']);
        add_action('wp_ajax_nopriv_check_booking_limit', [self::class, 'check_booking_limit']);
    }

    public static function get_date_time_bookings_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_bookings';
        $rm_booking_options = get_option('rm_booking_options');
        $staff_members = Staff::get_staff_members('active');
        $total_staff = count($staff_members);
        $enabled = isset($rm_booking_options['enable_booking_approval']) ? $rm_booking_options['enable_booking_approval'] : 'no';
        $taxes = Tax::get_taxes();
    
        $query = "SELECT 
                    start_datetime, 
                    end_datetime,
                    COUNT(DISTINCT staff_id) as staff_count
                  FROM $table_name";
        
        if ($enabled === 'yes') {
            $query .= " WHERE status = 'confirmed'";
        }
        
        $query .= " GROUP BY start_datetime, end_datetime";
        
        $bookings = $wpdb->get_results($query);
        
        $fully_booked_slots = array_filter($bookings, function($booking) use ($total_staff) {
            return $booking->staff_count >= $total_staff;
        });
        
        $all_days = range(1, 7); // Monday (1) to Sunday (7)
        $days_with_staff = [];
        
        foreach ($staff_members as $staff) {
            if (!empty($staff['working_hours'])) {
                $working_hours = is_array($staff['working_hours']) ? $staff['working_hours'] : json_decode($staff['working_hours'], true);
                
                if ($working_hours && is_array($working_hours)) {
                    foreach ($working_hours as $day => $hours) {
                        foreach ($hours as $shift) {
                            if ($shift['start'] !== '00:00' || $shift['end'] !== '00:00') {
                                // At least one staff member works this day
                                if (!in_array($day, $days_with_staff)) {
                                    $days_with_staff[] = $day;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $no_staff_days = array_diff($all_days, $days_with_staff);
        
        $settings = [
            'min_time' => $rm_booking_options['booking_min_time'] ?? '08:00',
            'max_time' => $rm_booking_options['booking_max_time'] ?? '20:00',
            'interval' => $rm_booking_options['booking_interval'] ?? 60,
            'buffer_time' => $rm_booking_options['buffer_time'] ?? 0,
            'minimum_lead_time' => $rm_booking_options['minimum_lead_time'] ?? 60,
            'no_staff_days' => array_values($no_staff_days)
        ];
        
        wp_send_json([
            'success' => true,
            'data' => array_values($fully_booked_slots),
            'settings' => $settings,
            'staff' => array_map(function($staff) {
                return ['id' => $staff['id'], 'name' => $staff['name']];
            }, $staff_members),
            'taxes' => $taxes,
        ]);
        
        exit;
    }
    
    public static function get_staff_data_for_day($date, $service_ids) {
        global $wpdb;
        
        if (empty($service_ids)) {
            return [];
        }
        
        // Calculate total duration needed for all services
        $total_duration = 0;
        foreach ($service_ids as $service_id) {
            $service = Service::get_service($service_id);
            if (!$service) continue;
            
            $duration = $service->duration ?? 30; // Default to 30 minutes
            $total_duration += $duration;
        }
        
        // Get all staff who offer ALL the requested services
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
        
        $settings = get_option('rm_booking_options');
        $day_of_week = date('N', strtotime($date)); // 1-7 (Monday-Sunday)
        $result = [];
        
        // Get all bookings for these staff members on this date
        $staff_ids = array_map(function($staff) { return $staff->id; }, $qualified_staff);
        $staff_id_placeholders = implode(',', array_fill(0, count($staff_ids), '%d'));
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT staff_id, start_datetime, end_datetime 
             FROM {$wpdb->prefix}reservemate_bookings
             WHERE staff_id IN ($staff_id_placeholders)
             AND DATE(start_datetime) = %s
             ORDER BY start_datetime",
            array_merge($staff_ids, [$date])
        ));
        
        // Organize bookings by staff
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
        
        // Generate time slots based on settings
        $min_time = new DateTime($date . ' ' . $settings['booking_min_time']);
        $max_time = new DateTime($date . ' ' . $settings['booking_max_time']);
        $interval = $settings['booking_booking_interval'] ?? 30;
        $buffer_time = $settings['buffer_time'] ?? 0;
        
        $current_time = clone $min_time;
        
        while ($current_time <= $max_time) {
            $slot_start = clone $current_time;
            $slot_end = clone $current_time;
            $slot_end->modify("+{$total_duration} minutes");
            
            if ($slot_end > $max_time) {
                break;
            }
            
            $time_key = $slot_start->format('H:i') . '-' . $slot_end->format('H:i');
            $result[$time_key] = [];
            
            foreach ($qualified_staff as $staff) {
                $working_hours = json_decode($staff->working_hours, true);
                if (empty($working_hours[$day_of_week])) {
                    continue;
                }
                
                // Check if staff is working during this slot
                $is_working = false;
                foreach ($working_hours[$day_of_week] as $shift) {
                    $shift_start = new DateTime($date . ' ' . $shift['start']);
                    $shift_end = new DateTime($date . ' ' . $shift['end']);
                    
                    if ($slot_start >= $shift_start && $slot_end <= $shift_end) {
                        $is_working = true;
                        break;
                    }
                }
                
                if (!$is_working) {
                    continue;
                }
                
                // Check for booking conflicts
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
                        'profile_image' => $staff->profile_image ?: ''
                    ];
                }
            }
            
            // Move to next potential slot (current slot end + buffer time)
            $current_time = clone $slot_end;
            $current_time->modify("+{$buffer_time} minutes");
        }
        
        return $result;
    }
    
    public static function get_staff_availability_for_day() {
        check_ajax_referer('booking_js_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $service_ids = json_decode(stripslashes($_POST['service_ids']));
        
        if (empty($service_ids) || !is_array($service_ids)) {
            wp_send_json_error(['message' => 'Invalid service selection']);
            return;
        }
        
        $service_ids = array_map('intval', $service_ids);
        
        foreach ($service_ids as $service_id) {
            $service = Service::get_service($service_id);
            if (!$service) {
                wp_send_json_error(['message' => 'Invalid service ID: ' . $service_id]);
                return;
            }
        }
        
        $availability = self::get_staff_data_for_day($date, $service_ids);
        wp_send_json_success($availability);
    }
    
    public static function check_booking_limit() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Invalid request data.', 'reserve-mate')]);
        }
        
        if(empty($date)) {
            wp_send_json_error(['message' => __('Empty date.', 'reserve-mate')]);
        }
        
        $result = Booking::validate_booking_limits($email, $date);
        
        if ($result['valid']) {
            wp_send_json_success();
        } else {
            wp_send_json_success([
                'validation_message' => $result['message'],
                'type' => $result['type'] ?? 'validation'
            ]);
        }
    }

}