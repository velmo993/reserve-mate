<?php
namespace ReserveMate\Admin\Controllers;
use ReserveMate\Admin\Views\BookingViews;
use ReserveMate\Admin\Helpers\Booking;
use ReserveMate\Admin\Helpers\Service;
use ReserveMate\Admin\Helpers\Staff;

defined('ABSPATH') or die('No direct access!');

class BookingController {
    public static function handle_requests() {
        // Handle form submissions first
        self::handle_form_submissions();
        
        // Then display the appropriate view
        self::display_bookings_page();
    }

    private static function handle_form_submissions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        if (isset($_POST['action']) && $_POST['action'] === 'search' && empty($_POST['s'])) {
            wp_redirect(admin_url('admin.php?page=reserve-mate-bookings'));
            exit;
        }
        
        if (isset($_POST['save_admin_booking'])) {
            self::handle_booking_save();
        }
        
        if (isset($_POST['delete']) && isset($_POST['delete_nonce']) && !isset($_POST['bulk_action'])) {
            self::handle_booking_delete();
        }
        
        if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
            self::handle_bulk_delete();
        }
        
        if (isset($_POST['toggle_status'])) {
            self::handle_status_toggle();
        }
        
    }
    
    private static function get_sort_params() {
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
        
        // Validate orderby column
        $allowed_columns = ['id', 'start_datetime', 'total_cost', 'paid_amount', 'status', 'created_at', 'name', 'email'];
        if (!in_array($orderby, $allowed_columns)) {
            $orderby = 'created_at';
        }
        
        // Validate order direction
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'desc';
        }
        
        return [$orderby, $order];
    }
    
    private static function get_search_param() {
        return isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
    }

    private static function handle_booking_save() {
        // Validate nonce
        if (!isset($_POST['admin_booking_nonce']) || 
            !wp_verify_nonce($_POST['admin_booking_nonce'], 'save_admin_booking')) {
            wp_die('Security check failed');
        }

        // Process and sanitize data
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $start_datetime = sanitize_text_field($_POST['start_datetime']);
        $end_datetime = sanitize_text_field($_POST['end_datetime']);
        $total_cost = isset($_POST['total_cost']) ? floatval($_POST['total_cost']) : 0;
        $paid_amount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        $services = self::process_services($_POST['services'] ?? []);
        $staff_id =  intval($_POST['staff_id'] ?? 0);
        
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'start_datetime' => sanitize_text_field($_POST['start_datetime']),
            'end_datetime' => sanitize_text_field($_POST['end_datetime']),
            'total_cost' => floatval($_POST['total_cost'] ?? 0),
            'paid_amount' => floatval($_POST['paid_amount'] ?? 0),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? ''),
            'services' => self::process_services($_POST['services'] ?? []),
            'staff_id' => intval($_POST['staff_id'] ?? 0)
        ];

        // Save or update booking
        if (isset($_GET['edit'])) {
            $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;
            Booking::update_booking($name, $email, $phone, $start_datetime, $end_datetime, $total_cost, $payment_method, $services, $paid_amount, $editing_index, $staff_id = null);
        } else {
            Booking::save_booking_to_db($name, $email, $phone, $start_datetime, $end_datetime, $total_cost, $payment_method, $services, $paid_amount, true);
        }

        wp_redirect(admin_url('admin.php?reserve-mate-bookings'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Booking saved.', 'reserve-mate') . '</p></div>';
    }

    private static function process_services($services) {
        $processed = [];
        foreach ((array)$services as $service_id) {
            $service_id = intval($service_id);
            if ($service_id > 0) {
                $service = Service::get_service($service_id);
                if ($service) {
                    $processed[] = [
                        'id' => $service_id,
                        'quantity' => 1,
                        'price' => $service->price,
                    ];
                }
            }
        }
        return $processed;
    }

    private static function handle_booking_delete() {
        $booking_id = isset($_GET['delete']) ? intval($_GET['delete']) : (isset($_POST['delete']) ? intval($_POST['delete']) : 0);
        $nonce = isset($_GET['delete_nonce']) ? $_GET['delete_nonce'] : (isset($_POST['delete_nonce']) ? $_POST['delete_nonce'] : '');
    
        if (!$booking_id || !wp_verify_nonce($nonce, 'delete_booking')) {
            wp_die('Security check failed');
        }
    
        Booking::delete_booking($booking_id);
        
        echo '<div class="notice notice-success is-dismissible"><p>' 
                    . __('Booking deleted.', 'reserve-mate') . '</p></div>';
    }

    private static function handle_bulk_delete() {
        if (!isset($_POST['bulk_nonce']) || 
            !wp_verify_nonce($_POST['bulk_nonce'], 'bulk_booking_action')) {
            wp_die('Security check failed');
        }

        if (isset($_POST['booking_ids']) && is_array($_POST['booking_ids'])) {
            foreach ($_POST['booking_ids'] as $booking_id) {
                Booking::delete_booking(intval($booking_id));
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . __('Bookings deleted.', 'reserve-mate') . '</p></div>';
    }

    private static function handle_status_toggle() {
        if (!isset($_POST['status_nonce']) || 
            !wp_verify_nonce($_POST['status_nonce'], 'toggle_booking_status')) {
            wp_die('Security check failed');
        }

        $booking_id = intval($_POST['booking_id']);
        Booking::approve_booking($booking_id);
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Status changed.', 'reserve-mate') . '</p></div>';
    }

    private static function display_bookings_page() {
        $per_page = 10;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        list($orderby, $order) = self::get_sort_params();
        $search = self::get_search_param();
        
        $view_data = [
            'bookings' => Booking::get_bookings($per_page, $current_page, null, $orderby, $order, $search),
            'per_page' => $per_page,
            'current_page' => $current_page,
            'total_items' => Booking::count_bookings(null, $search),
            'editing_index' => isset($_GET['edit']) ? intval($_GET['edit']) : null,
            'editing_booking' => isset($_GET['edit']) ? Booking::get_booking(intval($_GET['edit'])) : null,
            'approval_enabled' => Booking::is_approval_enabled(),
            'rm_payment_options' => get_option('rm_payment_options', []),
            'services' => Service::get_services(),
            'staff_members' => Staff::get_staff_members('active'),
            'orderby' => $orderby,
            'order' => $order,
            'search' => $search
        ];
    
        BookingViews::render($view_data);
    }
    
}