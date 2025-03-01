<?php
/*
Plugin Name: Reserve Mate
Plugin URI: https://github.com/velmo993/reserve-mate
Description: A plugin for managing reservations.
Version: 1.0.3
Author: velmoweb.com
Author URI: https://example.com
License: GPL2
* Text Domain:       reserve-mate
* Domain Path:       /assets/languages
*/

defined('ABSPATH') or die('No direct access!');
define('RESERVE_MATE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TOKEN_FOR_RELEASES', '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');
define('RESERVE_MATE_PLUGIN_SLUG', 'reserve-mate');

// Include the Plugin Update Checker library
require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize the update checker
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/velmo993/reserve-mate/',
    __FILE__,
    RESERVE_MATE_PLUGIN_SLUG
);
$myUpdateChecker->debugMode = true;
$myUpdateChecker->getVcsApi()->enableReleaseAssets();
$myUpdateChecker->setBranch('main');
$myUpdateChecker->setAuthentication(TOKEN_FOR_RELEASES);

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Include required files for mail
if (!function_exists('wp_mail')) {
    require_once ABSPATH . WPINC . '/pluggable.php';
}
require_once(plugin_dir_path(__FILE__) . 'db/property.php');
require_once(plugin_dir_path(__FILE__) . 'db/service.php');
require_once(plugin_dir_path(__FILE__) . 'db/booking.php');
require_once(plugin_dir_path(__FILE__) . 'db/tax.php');
require_once(plugin_dir_path(__FILE__) . 'includes/helpers.php');

if (!is_admin()) {
    require_once(plugin_dir_path(__FILE__) . 'includes/frontend/booking-form.php');
}

if (is_admin()) {
    require_once(plugin_dir_path(__FILE__) . 'includes/admin/admin-settings.php');
    require_once(plugin_dir_path(__FILE__) . 'includes/integrations/google-calendar.php');
    require_once(plugin_dir_path(__FILE__) . 'includes/payments/payments.php');
}

register_activation_hook(__FILE__, 'create_properties_table'); // Create this first, bookings table has foreign for this table.
// Register custom post types
function create_booking_post_type() {
    register_post_type('booking',
        array(
            'labels' => array(
                'name' => __('Bookings'),
                'singular_name' => __('Booking')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'show_in_rest' => true,
        )
    );
}

function enqueue_admin_styles() {
    wp_enqueue_style(
        'booking-plugin-styles',
        plugin_dir_url(__FILE__) . 'assets/css/style.css',
        array(),
        '1.0.0'
    );
}

function enqueue_admin_scripts() {
    wp_enqueue_style('flatpickr-css', plugins_url('assets/css/flatpickr.min.css', __FILE__));
    wp_enqueue_script('flatpickr-js', plugins_url('assets/js/flatpickr.min.js', __FILE__), array(), null, true);
    wp_enqueue_script('flatpickr-hu', plugins_url('assets/l10n/hu.js', __FILE__), ['flatpickr-js'], null, true);
    wp_enqueue_script('booking-plugin-scripts', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('flatpickr-js', 'flatpickr-hu'), null, true);
}


function enqueue_flatpickr_scripts() {
    wp_enqueue_style('flatpickr-css', plugins_url('assets/css/flatpickr.min.css', __FILE__));
    wp_enqueue_script('flatpickr-js', plugins_url('assets/js/flatpickr.min.js', __FILE__), array(), null, true);
    wp_enqueue_script('flatpickr-hu', plugins_url('assets/l10n/hu.js', __FILE__), ['flatpickr-js'], null, true);
    wp_enqueue_script('custom-flatpickr', plugin_dir_url(__FILE__) . 'assets/js/flatpickr-init.js', ['flatpickr-js', 'flatpickr-hu'], null, true);
    
    $b_settings = get_option('booking_settings');
    $booking_settings = [
        'hourly_booking_enabled' => isset($b_settings['enable_hourly_booking']) ? strval($b_settings['enable_hourly_booking']) : '0'
    ];
    
    wp_localize_script('custom-flatpickr', 'flatpickrVars', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'bookingSettings' => $booking_settings,
        'nonce' => wp_create_nonce('custom_flatpickr_nonce')
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_flatpickr_scripts');

function enqueue_frontend_styles() {
    wp_enqueue_style('reserve-mate-frontend', plugin_dir_url(__FILE__) . 'assets/css/frontend.css');
}

add_action('wp_enqueue_scripts', 'enqueue_frontend_styles');

function enqueue_booking_form_styles() {
    $b_settings = get_option('booking_settings');
    if(!$b_settings['enable_hourly_booking']) {
        wp_enqueue_style('daterange-form-css', plugin_dir_url(__FILE__) . 'assets/css/daterange-form.css', ['flatpickr-css']);
    } else {
        wp_enqueue_style('datetime-form-css', plugin_dir_url(__FILE__) . 'assets/css/datetime-form.css', ['flatpickr-css']);
    }
}

add_action('wp_enqueue_scripts', 'enqueue_booking_form_styles');

function enqueue_frontend_scripts() {
    wp_enqueue_script(
        'frontend-scripts',
        plugin_dir_url(__FILE__) . 'assets/js/frontend.js',
        array('jquery'),
        '1.0.0',
        true
    );
    $p_settings = get_option('payment_settings');
    $stripe_public_key = get_option('payment_settings')['stripe_public_key'] ?? '';
    $stripe_secret_key = get_option('payment_settings')['stripe_secret_key'] ?? '';
    $stripe_enabled = '0';
    !empty($p_settings['stripe_enabled']) && !empty($stripe_public_key) && !empty($stripe_secret_key) ? $stripe_enabled = strval(1) : '0';
    
    $client_id = get_option('payment_settings')['paypal_client_id'] ?? '';
    $paypal_enabled = '0';
    !empty($p_settings['paypal_enabled']) && !empty($client_id) ? $paypal_enabled = strval(1) : '0';
    
    $payment_settings = [
        'stripe_enabled' => $stripe_enabled,
        'paypal_enabled' => $paypal_enabled,
        'bank_transfer_enabled' => !empty($p_settings['bank_transfer_enabled']) ? strval($p_settings['bank_transfer_enabled']) : '0',
        'pay_on_arrival_enabled' => !empty($p_settings['pay_on_arrival_enabled']) ? strval($p_settings['pay_on_arrival_enabled']) : '0',
        'advance_payment_type' => isset($p_settings['advance_payment_type']) ? strval($p_settings['advance_payment_type']) : '0',
        'advance_payment_percentage' => isset($p_settings['advance_payment_percentage']) ? floatval($p_settings['advance_payment_percentage']) : '0',
        'advance_payment_fixed_amount' => isset($p_settings['advance_payment_fixed_amount']) ? floatval($p_settings['advance_payment_fixed_amount']) : '0',
    ];
    
    $b_settings = get_option('booking_settings');
    $booking_settings = [
        'hourly_booking_enabled' => isset($b_settings['enable_hourly_booking']) ? strval($b_settings['enable_hourly_booking']) : '0'
    ];
    
    $plugin_url = plugin_dir_url(__FILE__);
    
    $currency = get_currency();

    wp_localize_script('frontend-scripts', 'ajaxScript', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('send_email_nonce'),
        'paymentSettings' => $payment_settings,
        'bookingSettings' => $booking_settings,
        'imagePath' => $plugin_url . 'assets/images',
        'currency' => $currency
    ));
}

// Deregister Elementor's Font Awesome Styles
add_action('wp_enqueue_scripts', 'remove_elementor_font_awesome_styles', 100);

function remove_elementor_font_awesome_styles() {
    // Dequeue Elementor's Font Awesome styles (solid, regular, brands, etc.)
    wp_dequeue_style('elementor-icons-fa-solid');
    wp_deregister_style('elementor-icons-fa-solid');
    
    wp_dequeue_style('elementor-icons-fa-regular');
    wp_deregister_style('elementor-icons-fa-regular');
    
    wp_dequeue_style('elementor-icons-fa-brands');
    wp_deregister_style('elementor-icons-fa-brands');
    
    wp_dequeue_style('elementor-icons');
    wp_deregister_style('elementor-icons');
    
    // Optionally, if Elementor loads Font Awesome under any other handles
    wp_dequeue_style('font-awesome');
    wp_deregister_style('font-awesome');
}

// Enqueue Font Awesome 6.6.0
add_action('wp_enqueue_scripts', 'enqueue_custom_font_awesome_6', 110);

function enqueue_custom_font_awesome_6() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css', array(), '6.6.0');
}

function enqueue_stripe_scripts() {
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], null, true);
    wp_enqueue_script('stripe-payment', plugin_dir_url(__FILE__) . 'assets/js/stripe-payment.js', ['stripe-js'], null, true);
    
    $stripe_public_key = get_option('payment_settings')['stripe_public_key'] ?? '';
    wp_localize_script('stripe-payment', 'stripe_vars', [
        'stripePublicKey' => $stripe_public_key,
        'pluginDir' => plugin_dir_url(__FILE__)
    ]);
}

function enqueue_paypal_scripts() {
    $paypal_client_id = get_option('payment_settings')['paypal_client_id'] ?? '';
    wp_enqueue_script('paypal-js', 'https://www.paypal.com/sdk/js?client-id=' . $paypal_client_id . '&disable-funding=card', [], null, true);
    wp_enqueue_script('paypal-payment', plugin_dir_url(__FILE__) . 'assets/js/paypal-payment.js', ['paypal-js'], null, true);

    wp_localize_script('paypal-payment', 'paypal_vars', [
        'paypalClientId' => $paypal_client_id,
        'pluginDir' => plugin_dir_url(__FILE__)
    ]);
}



function generate_ical_feed() {
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="bookings.ics"');

    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//YourPluginName//NONSGML v1.0//EN\r\n";

    global $wpdb;
    $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM wp_reservemate_bookings"));

    foreach ($bookings as $booking) {
        $start_date = date('Ymd\THis\Z', strtotime($booking->start_date));
        $end_date = date('Ymd\THis\Z', strtotime($booking->end_date));
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . uniqid() . "@yourdomain.com\r\n";
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $start_date . "\r\n";
        $ical .= "DTEND:" . $end_date . "\r\n";
        $ical .= "SUMMARY:Booking for " . $booking->name . "\r\n";
        $ical .= "END:VEVENT\r\n";
    }

    $ical .= "END:VCALENDAR";

    echo $ical;
    exit;
}

add_action('init', function () {
    if (isset($_GET['download_ical'])) {
        generate_ical_feed();
    }
});

add_action('admin-init', 'create_booking_post_type');
add_action('admin_enqueue_scripts', 'enqueue_admin_styles');
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
add_action('wp_enqueue_scripts', 'enqueue_frontend_scripts');
add_action('reserve_mate_cleanup_unpaid_bookings', 'delete_unpaid_bookings');
add_action('wp', 'booking_auto_cleanup');

$option = get_option('payment_settings');
if($option) {
    if($option['paypal_enabled'] == 1) {
        add_action('wp_enqueue_scripts', 'enqueue_paypal_scripts');
    }
    
    if($option['stripe_enabled'] == 1) {
        add_action('wp_enqueue_scripts', 'enqueue_stripe_scripts');
    }
}

register_activation_hook(__FILE__, 'create_bookings_table');
register_activation_hook(__FILE__, 'create_services_table');
register_activation_hook(__FILE__, 'create_hourly_bookings_table');
register_activation_hook(__FILE__, 'create_taxes_table');

function reserve_mate_force_load_translation() {
    $loaded = load_textdomain( 'reserve-mate', WP_LANG_DIR . '/plugins/reserve-mate-hu_HU.mo' );
    //error_log('Translation Loaded: ' . var_export($loaded, true));
}
add_action('init', 'reserve_mate_force_load_translation');

