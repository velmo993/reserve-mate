<?php
defined('ABSPATH') or die('No direct access!');

require_once plugin_dir_path(__FILE__) . 'menu/settings.php';
require_once plugin_dir_path(__FILE__) . 'menu/payment-settings.php';
require_once plugin_dir_path(__FILE__) . 'menu/message-settings.php';
require_once plugin_dir_path(__FILE__) . 'menu/booking-settings.php';
require_once plugin_dir_path(__FILE__) . 'menu/ical-settings.php';
require_once plugin_dir_path(__FILE__) . 'menu/property-settings.php';
require_once plugin_dir_path(__FILE__) . 'menu/tax-settings.php';

function add_admin_menu() {
    add_menu_page(
        'Booking System Settings',
        'Reserve Mate',
        'manage_options',
        'reserve-mate-settings',
        'booking_settings_page',
        'dashicons-calendar-alt'
    );

    // First submenu (hidden duplicate of main menu)
    add_submenu_page(
        'reserve-mate-settings', 
        __('Settings', 'reserve-mate'),
        __('Settings', 'reserve-mate'),
        'manage_options', 
        'reserve-mate-settings',
        'booking_settings_page'
    );
    
    add_submenu_page(
        'reserve-mate-settings', 
        __('Bookings', 'reserve-mate'),
        __('Bookings', 'reserve-mate'),
        'manage_options', 
        'manage-bookings',
        'display_manage_bookings_page'
    );
    
    add_submenu_page(
        'reserve-mate-settings', 
        __('Properties', 'reserve-mate'),
        __('Properties', 'reserve-mate'), 
        'manage_options', 
        'manage-properties', 
        'manage_properties_page'
    );
    
    add_submenu_page(
        'reserve-mate-settings', 
        __('Payments', 'reserve-mate'),
        __('Payments', 'reserve-mate'),
        'manage_options', 
        'payment-settings', 
        'payment_settings_page'
    );
    
    add_submenu_page(
        'reserve-mate-settings',
        __('Taxes', 'reserve-mate'),
        __('Taxes', 'reserve-mate'),
        'manage_options',
        'manage-tax',
        'manage_tax_page'
    );
    
    add_submenu_page(
        'reserve-mate-settings',
        __('Messages', 'reserve-mate'),
        __('Messages', 'reserve-mate'), 
        'manage_options', 
        'manage-messages',
        'manage_messages_page'
    );

    add_submenu_page(
        'reserve-mate-settings',
        __('iCal Settings', 'reserve-mate'),
        __('iCal Settings', 'reserve-mate'),
        'manage_options',
        'ical-settings',
        'display_ical_settings_page'
    );
}
add_action('admin_menu', 'add_admin_menu');
