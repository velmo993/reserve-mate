<?php
namespace ReserveMate\Admin\Controllers;

use ReserveMate\Admin\Views\DashboardViews;
use ReserveMate\Admin\Helpers\Booking;
use ReserveMate\Admin\Helpers\Staff;

defined('ABSPATH') or die('No direct access!');

class DashboardController {
    
    public static function display_dashboard() {
        DashboardViews::render_dashboard();
    }
    
    /**
     * Get dashboard statistics
     */
    public static function get_dashboard_stats() {
        global $wpdb;
        
        $stats = [];
        
        // Get total bookings
        $bookings_table = $wpdb->prefix . 'reservemate_bookings';
        if ($wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") == $bookings_table) {
            $stats['total_bookings'] = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
            $stats['pending_bookings'] = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table WHERE status = 'pending'");
            $stats['confirmed_bookings'] = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table WHERE status = 'confirmed'");
            
            // Get today's bookings - using created_at instead of datetime
            $today = date('Y-m-d');
            $stats['todays_bookings'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE DATE(created_at) = %s", 
                $today
            ));
            
            // Get this month's bookings - using created_at instead of datetime
            $this_month = date('Y-m');
            $stats['month_bookings'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s", 
                $this_month
            ));
        } else {
            // Default values if table doesn't exist
            $stats = [
                'total_bookings' => 0,
                'pending_bookings' => 0,
                'confirmed_bookings' => 0,
                'todays_bookings' => 0,
                'month_bookings' => 0
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get recent bookings for dashboard
     */
    public static function get_recent_bookings_data($limit = 5) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'reservemate_bookings';
        $staff_table = $wpdb->prefix . 'reservemate_staff_members';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") != $bookings_table) {
            return [];
        }
        
        $recent_bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, s.name as staff_name 
             FROM $bookings_table b
             LEFT JOIN $staff_table s ON b.staff_id = s.id
             ORDER BY b.created_at DESC LIMIT %d",
            $limit
        ));
        
        // Add services to each booking if needed
        if ($recent_bookings) {
            $services_table = $wpdb->prefix . 'reservemate_booking_services';
            $service_names_table = $wpdb->prefix . 'reservemate_services';
            
            foreach ($recent_bookings as $booking) {
                $services = $wpdb->get_results($wpdb->prepare(
                    "SELECT bs.service_id, bs.quantity, bs.price, s.name as service_name
                     FROM $services_table bs
                     LEFT JOIN $service_names_table s ON bs.service_id = s.id
                     WHERE bs.booking_id = %d",
                    $booking->id
                ));
                
                $booking->services = $services;
                $booking->service_names = implode(', ', array_map(function($s) {
                    return $s->service_name;
                }, $services));
            }
        }
        
        return [
            'recent_bookings' => $recent_bookings,
            'approval_enabled' => Booking::is_approval_enabled(),
            'staff_members' => Staff::get_staff_members('active')
        ];
    }
}