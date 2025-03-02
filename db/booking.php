<?php
defined('ABSPATH') or die('No direct access!');

function delete_unpaid_bookings() {
    global $wpdb;
    $options = get_option('booking_settings');
    
    if (isset($options['auto_delete_booking_enabled']) && $options['auto_delete_booking_enabled'] == 1) {
        $days = isset($options['delete_after_days']) ? absint($options['delete_after_days']) : 6;
        $table_name = $wpdb->prefix . 'reservemate_bookings';

        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));

        error_log('Date threshold: ' . $date_threshold);

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE paid = 0 AND created_at < %s",
                $date_threshold
            )
        );
    }
}

function booking_auto_cleanup() {
    if (!wp_next_scheduled('reserve_mate_cleanup_unpaid_bookings')) {
        wp_schedule_event(time(), 'daily', 'reserve_mate_cleanup_unpaid_bookings');
    }
}

