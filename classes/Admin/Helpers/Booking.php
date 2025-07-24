<?php
namespace ReserveMate\Admin\Helpers;
use ReserveMate\Admin\Helpers\GoogleCalendar;
use ReserveMate\Admin\Helpers\Service;
use ReserveMate\Admin\Helpers\Email;
use ReserveMate\Admin\Helpers\Staff;
use DateTime;
use DateTimeZone;
use WP_Error;

defined('ABSPATH') or die('No direct access!');

class Booking {
    
    public static function save_booking_to_db($name, $email, $phone, $start_datetime, $end_datetime, $total_cost, $payment_method, $services, $paid_amount, $staff_id = null, $custom_fields = [], $admin = false) {
        global $wpdb;
        $currency = get_currency();
        $rm_booking_options = get_option('rm_booking_options');
        $requires_approval = isset($rm_booking_options['enable_booking_approval']) && $rm_booking_options['enable_booking_approval'] === 'yes';
        $initial_status = $requires_approval ? 'pending' : 'confirmed';
        
        if ($admin) {
            $initial_status = 'confirmed';
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'reservemate_bookings',
            [
                'staff_id' => $staff_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'total_cost' => $total_cost,
                'paid_amount' => $paid_amount,
                'payment_method' => $payment_method,
                'status' => $initial_status,
            ],
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s'
            ]
        );
    
        if ($result === false) {
            // error_log('Database insert failed: ' . $wpdb->last_error);
            return false;
        }
    
        $booking_id = $wpdb->insert_id;
    
        // Insert services
        if (is_array($services)) {
            foreach ($services as $index => $service) {
                $service_name = Service::get_service_name($service['id']);
                $wpdb->insert(
                    $wpdb->prefix . 'reservemate_booking_services',
                    [
                        'booking_id' => $booking_id,
                        'service_id' => $service['id'],
                        'quantity' => $service['quantity'],
                        'price' => $service['price'],
                    ],
                    ['%d', '%d', '%d', '%f']
                );
                
                $services[$index]['name'] = $service_name;
            }
        } else {
            // error_log('Services is not an array: ' . print_r($services, true));
        }
        
        if (!empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $field_key => $field_value) {
                if (is_array($field_value)) {
                    $field_value = maybe_serialize($field_value);
                }
                
                $wpdb->insert(
                    $wpdb->prefix . 'reservemate_booking_custom_fields',
                    [
                        'booking_id' => $booking_id,
                        'field_key' => $field_key,
                        'field_value' => $field_value
                    ],
                    ['%d', '%s', '%s']
                );
            }
        }
    
        $staff = '';
        if ($staff_id) {
            $staff_member = Staff::get_staff_member($staff_id);
            if ($staff_member) {
                $staff = $staff_member['name'];
            }
        }
        
        $services_list = '';
        foreach ($services as $service) {
            $services_list .= '<li>' . esc_html($service['name']) . ' x ' . esc_html($service['quantity']) . ' - ' . esc_html($service['price'] . ' ' . $currency) . '</li>';
        }
        
        // Send emails if not admin booking
        if (!$admin) {
            Email::send_booking_email_to_client(
                $services_list, 
                $name, 
                $email, 
                $phone, 
                $start_datetime, 
                $end_datetime, 
                $total_cost, 
                $payment_method, 
                $paid_amount,
                $custom_fields,
                $staff
            );
            
            Email::send_booking_email_to_admin(
                $services_list, 
                $name, 
                $email, 
                $phone, 
                $start_datetime, 
                $end_datetime, 
                $total_cost, 
                $payment_method, 
                $paid_amount,
                $custom_fields,
                $staff
            );
        }
        
        if($result) {
            $custom_fields_list = '';
            if (!empty($custom_fields)) {
                $custom_fields_list .= '<li><strong>Additional Information:</strong></li>';
                foreach ($custom_fields as $field_key => $field_value) {
                    $pretty_name = ucwords(str_replace(['_', '-'], ' ', str_replace('custom_', '', $field_key)));
                    
                    if (is_array($field_value)) {
                        $field_value = implode(', ', $field_value);
                    }
                    
                    $custom_fields_list .= '<li><strong>' . esc_html($pretty_name) . ':</strong> ' . esc_html($field_value) . '</li>';
                }
            }
            
            $google_calendar_result = self::save_booking_to_google_calendar
            (
                $services_list, 
                $name, 
                $email, 
                $phone, 
                $start_datetime, 
                $end_datetime, 
                $total_cost, 
                $payment_method, 
                $paid_amount,
                $custom_fields_list,
                $staff
            );
            
            // if($google_calendar_result) {
            //     error_log("g calendar result: " . print_r($google_calendar_result, true));
            // }
        }
        
        return $booking_id;
    }
    
    private static function save_booking_to_google_calendar($services_list, $name, $email, $phone, $start_datetime, $end_datetime, $total_cost, $payment_method, $paid_amount, $custom_fields_list, $staff) {
        // Initialize debug log
        // $debug_log = array();
        
        $options = get_option('rm_google_calendar_options');
        
        $service_names = is_array($services_list) 
            ? implode(', ', array_map('strip_tags', $services_list))
            : strip_tags($services_list);
        
        $event_details = array(
            'summary' => 'Service Booking: ' . $service_names,
            'description' => 'Services: ' . $service_names . "\n" .
                     'Client: ' . $name . "\n" .
                     'Email: ' . $email . "\n" .
                     'Phone: ' . $phone . "\n" .
                     'Total Cost: ' . $total_cost . "\n" .
                     'Payment Method: ' . $payment_method . "\n" .
                     'Amount Paid: ' . $paid_amount . "\n"  .
                     'Staff Member: ' . $staff,
            'start' => $start_datetime,
            'end' => $end_datetime
        );
        
        $result = self::create_google_calendar_event($event_details);
        
        // if (is_wp_error($result)) {
        //     $debug_log[] = "Error from create_google_calendar_event: " . $result->get_error_message();
        // } else {
        //     $debug_log[] = "Google Calendar sync result: " . print_r($result, true);
        // }
        
        return $result;
    }
    
    private static function create_google_calendar_event($event_data) {
        $google_calendar_auth = new GoogleCalendar();
        
        $access_token = $google_calendar_auth->get_valid_access_token();
        if (!$access_token) {
            return new WP_Error('no_token', 'No valid access token available');
        }
        
        $options = get_option('rm_google_calendar_options');
        $calendar_id = isset($options['calendar_id']) ? $options['calendar_id'] : '';
        
        if (!$calendar_id) {
            return new WP_Error('no_calendar_id', 'No calendar ID found');
        }
        
        $timezone = isset($options['calendar_timezones']) ? $options['calendar_timezones'] : 'UTC';
        $start_datetime = self::validate_and_format_datetime($event_data['start'], $timezone);
        $end_datetime = self::validate_and_format_datetime($event_data['end'], $timezone);
        
        if (!$start_datetime || !$end_datetime) {
            return new WP_Error('invalid_datetime', 'Invalid datetime format');
        }
        
        // Prepare event data
        $event = array(
            'summary' => $event_data['summary'],
            'description' => $event_data['description'],
            'start' => array(
                'dateTime' => $start_datetime,
                'timeZone' => $timezone
            ),
            'end' => array(
                'dateTime' => $end_datetime,
                'timeZone' => $timezone
            )
        );
        
        // Add attendees if email is valid
        if (!empty($event_data['attendees']) && is_array($event_data['attendees'])) {
            $valid_attendees = array();
            foreach ($event_data['attendees'] as $attendee) {
                if (isset($attendee['email']) && is_email($attendee['email'])) {
                    $valid_attendees[] = array('email' => $attendee['email']);
                }
            }
            if (!empty($valid_attendees)) {
                $event['attendees'] = $valid_attendees;
            }
        }
        
        $json_event = json_encode($event);
    
        // Check for JSON encoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'JSON encoding error: ' . json_last_error_msg());
        }
        
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendar_id) . '/events';
    
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => $json_event,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        
        if ($response_code !== 200) {
            $error_message = "HTTP $response_code";
            $data = json_decode($body, true);
            if (isset($data['error']['message'])) {
                $error_message .= ": " . $data['error']['message'];
            }
            return new WP_Error('api_error', $error_message);
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', 'JSON decode error: ' . json_last_error_msg());
        }
        
        return isset($data['id']) ? $data['id'] : false;
    }
    
    public static function validate_and_format_datetime($datetime_string, $timezone = null) {
        // Remove any extra whitespace
        $datetime_string = trim($datetime_string);
        
        if (!$timezone) {
            $timezone = 'UTC';
        }
        
        $formats = array(
            'Y-m-d H:i:s',           // 2024-01-15 14:30:00
            'Y-m-d H:i',             // 2024-01-15 14:30
            'Y-m-d\TH:i:s',          // 2024-01-15T14:30:00
            'Y-m-d\TH:i',            // 2024-01-15T14:30
        );
        
        foreach ($formats as $format) {
            // Create DateTime with the target timezone from the start
            $date = DateTime::createFromFormat($format, $datetime_string, new DateTimeZone($timezone));
            if ($date !== false) {
                // Convert to RFC3339 format WITH timezone offset
                $formatted = $date->format('c'); // 'c' format includes timezone offset
                return $formatted;
            }
        }
        
        $timestamp = strtotime($datetime_string);
        if ($timestamp !== false) {
            // Create DateTime from timestamp in UTC, then convert to target timezone
            $date = new DateTime('@' . $timestamp);
            $date->setTimezone(new DateTimeZone($timezone));
            $date = new DateTime($datetime_string, new DateTimeZone($timezone));
            $formatted = $date->format('c');
            return $formatted;
        }
        
        return false;
    }
    
    public static function update_booking($name, $email, $phone, $start_datetime, $end_datetime, $total_cost, $payment_method, $services, $paid_amount, $booking_id, $staff_id = null) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'reservemate_bookings',
            [
                'staff_id' => $staff_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'total_cost' => $total_cost,
                'paid_amount' => $paid_amount,
                'payment_method' => $payment_method,
            ],
            ['id' => $booking_id],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s'],
            ['%d']
        );
        
        // Delete and re-insert services
        $wpdb->delete(
            $wpdb->prefix . 'reservemate_booking_services',
            ['booking_id' => $booking_id],
            ['%d']
        );
        
        if (is_array($services)) {
            foreach ($services as $service) {
                $wpdb->insert(
                    $wpdb->prefix . 'reservemate_booking_services',
                    [
                        'booking_id' => $booking_id,
                        'service_id' => $service['id'],
                        'quantity' => $service['quantity'],
                        'price' => $service['price'],
                    ],
                    ['%d', '%d', '%d', '%f']
                );
            }
        }
        
        return true;
    }
    
    public static function get_bookings($per_page = 10, $page_number = 1, $staff_id = null, $orderby = 'created_at', $order = 'desc', $search = '') {
        global $wpdb;
    
        $bookings_table = $wpdb->prefix . 'reservemate_bookings';
        $services_table = $wpdb->prefix . 'reservemate_booking_services';
        $service_names_table = $wpdb->prefix . 'reservemate_services';
        $staff_table = $wpdb->prefix . 'reservemate_staff_members';
        $offset = ($page_number - 1) * $per_page;
        
        $where_conditions = [];
        $where_params = [];
        
        if ($staff_id) {
            $where_conditions[] = "b.staff_id = %d";
            $where_params[] = $staff_id;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(b.name LIKE %s OR b.email LIKE %s OR b.phone LIKE %s OR b.id LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_params = array_merge($where_params, [$search_term, $search_term, $search_term, $search_term]);
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Sanitize order parameters
        $orderby = sanitize_sql_orderby($orderby . ' ' . $order);
        if (!$orderby) {
            $orderby = 'b.created_at DESC';
        } else {
            $orderby = 'b.' . $orderby;
        }
        
        $query = $wpdb->prepare(
            "SELECT b.*, s.name as staff_name 
             FROM $bookings_table b
             LEFT JOIN $staff_table s ON b.staff_id = s.id
             $where_clause
             ORDER BY $orderby
             LIMIT %d OFFSET %d",
            array_merge($where_params, [$per_page, $offset])
        );
        
        $bookings = $wpdb->get_results($query);
        
        foreach ($bookings as $booking) {
            $services = $wpdb->get_results($wpdb->prepare("
                SELECT s.service_id, s.quantity, s.price, sv.name as service_name
                FROM $services_table s
                LEFT JOIN $service_names_table sv ON s.service_id = sv.id
                WHERE s.booking_id = %d
            ", $booking->id));
            
            $booking->services = $services;
            $booking->service_names = implode(', ', array_map(function($s) {
                return $s->service_name;
            }, $services));
        }
    
        return $bookings;
    }
    
    public static function count_bookings($staff_id = null, $search = '') {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'reservemate_bookings';
        
        $where_conditions = [];
        $where_params = [];
        
        if ($staff_id) {
            $where_conditions[] = "staff_id = %d";
            $where_params[] = $staff_id;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(name LIKE %s OR email LIKE %s OR phone LIKE %s OR id LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_params = array_merge($where_params, [$search_term, $search_term, $search_term, $search_term]);
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        if (!empty($where_params)) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table $where_clause",
                $where_params
            ));
        } else {
            return $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
        }
    }
        
    public static function get_booking($booking_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'reservemate_bookings';
        $services_table = $wpdb->prefix . 'reservemate_booking_services';
        $service_names_table = $wpdb->prefix . 'reservemate_services';
        $staff_table = $wpdb->prefix . 'reservemate_staff_members';
    
        $query = $wpdb->prepare("
            SELECT b.*, s.name as staff_name
            FROM $bookings_table b
            LEFT JOIN $staff_table s ON b.staff_id = s.id
            WHERE b.id = %d
        ", intval($booking_id));
        
        $booking = $wpdb->get_row($query);
        
        if ($booking) {
            $services = $wpdb->get_results($wpdb->prepare("
                SELECT bs.service_id, bs.quantity, bs.price, s.name as service_name
                FROM $services_table bs
                LEFT JOIN $service_names_table s ON bs.service_id = s.id
                WHERE bs.booking_id = %d
            ", $booking_id));
            
            $booking->services = $services;
        }
    
        return $booking;
    }
    
    public static function delete_booking($booking_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_bookings';
        if($booking_id) {
            $wpdb->delete($table_name, ['id' => intval($booking_id)]);
        }
    }
    
    public static function bulk_delete_bookings($booking_ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reservemate_bookings';
        
        if (!empty($booking_ids) && is_array($booking_ids)) {
            foreach ($booking_ids as $booking_id) {
                $wpdb->delete($table_name, ['id' => intval($booking_id)]);
            }
            return count($booking_ids);
        }
        
        return 0;
    }
    
    public static function get_booking_services($booking_id) {
        global $wpdb;
        
        $services = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}reservemate_booking_services WHERE booking_id = %d",
                $booking_id
            )
        );
        
        return $services;
    }
    
    public static function is_approval_enabled() {
        $rm_booking_options = get_option('rm_booking_options');
        return isset($rm_booking_options['enable_booking_approval']) && 
               $rm_booking_options['enable_booking_approval'] === 'yes';
    }
    
    public static function approve_booking($booking_id) {
        global $wpdb;
        
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}reservemate_bookings WHERE id = %d",
            $booking_id
        ));
        
        $new_status = ($current_status === 'confirmed') ? 'pending' : 'confirmed';
        
        $result = $wpdb->update(
            $wpdb->prefix . 'reservemate_bookings',
            ['status' => $new_status],
            ['id' => $booking_id],
            ['%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    public static function validate_booking_limits($email, $date) {
        $options = get_option('rm_booking_options');
        $limits = $options['limits'] ?? [];
        $service_limits = $options['service_limits'] ?? [];
        
        foreach (['day', 'week', 'month'] as $period) {
            if (!empty($limits[$period]['enabled'])) {
                $count = self::get_bookings_count_by_email($email, $period, $date);
                if ($count >= $limits[$period]['max']) {
                    return [
                        'valid' => false,
                        'message' => sprintf(
                            /* translators: 1: number of bookings, 2: time period (day/week/month), 3: maximum allowed bookings */
                            __('You already have %1$d bookings this %2$s (limit: %3$d).', 'reserve-mate'),
                            $count, $period, $limits[$period]['max']
                        ),
                        'type' => 'limit_reached'
                    ];
                }
            }
        }  
        return ['valid' => true];
    }
    
    public static function get_bookings_count_by_email($email, $period, $date) {
        global $wpdb;
        
        $date_range = self::calculate_date_range($date, $period);
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$wpdb->prefix}reservemate_bookings b
                 JOIN {$wpdb->prefix}reservemate_booking_services s ON b.id = s.booking_id
                 WHERE b.email = %s
                 AND b.start_datetime BETWEEN %s AND %s",
                sanitize_email($email),
                $date_range['start'],
                $date_range['end']
            )
        );
    }
    
    public static function calculate_date_range($date, $period) {
        $start = new DateTime($date);
        $end = new DateTime($date);
        
        switch ($period) {
            case 'day':
                $end->setTime(23, 59, 59);
                break;
            case 'week':
                $start->modify('Monday this week');
                $end->modify('Sunday this week')->setTime(23, 59, 59);
                break;
            case 'month':
                $start->modify('first day of this month');
                $end->modify('last day of this month')->setTime(23, 59, 59);
                break;
        }
        
        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s')
        ];
    }
}