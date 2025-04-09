<?php
defined('ABSPATH') or die('No direct access!');

require_once plugin_dir_path(__FILE__) . '../integrations/google-calendar.php';
require_once plugin_dir_path(__FILE__) . '../payments/payments.php';
require_once plugin_dir_path(__FILE__) . 'payment-form.php';
require_once plugin_dir_path(__FILE__) . 'handlers/common-handler.php';

function isDateTimeEnabled() {
    $booking_settings = get_option('booking_settings');
    $datetime_select_enabled = $booking_settings['enable_hourly_booking'];
    $datetime_select_enabled === intval(1) ? true : false;
    return $datetime_select_enabled;
}

$enabled = isDateTimeEnabled();

if(!$enabled) {
    require_once plugin_dir_path(__FILE__) . 'daterange-booking-form.php';
    require_once plugin_dir_path(__FILE__) . 'handlers/daterange-handler.php';
} else {
    require_once plugin_dir_path(__FILE__) . 'datetime-booking-form.php';
    require_once plugin_dir_path(__FILE__) . 'handlers/datetime-handler.php';
}

function display_booking_form($atts) {
    $error_message = '';
    $message_settings = get_option('message_settings');
    $booking_success_message = !empty($message_settings['booking_success_message']) 
        ? '<h2>Booking Successful!</h2><p>'.$message_settings['booking_success_message'].'</p>' 
        : '<h2>Booking Successful!</h2><p>Thank you for choosing Us!</p>';
        
    $atts = shortcode_atts(
        array(
            'property_id' => 0, // Default to 0 if not provided
        ),
        $atts
    );

    $property_id = intval($atts['property_id']); // Ensure it's an integer
    if (!$property_id) {
        $property_id = get_property_id_from_slug(); // Get from URL if not provided
    }
    $property = get_property($property_id);
    $property_ids = get_property_ids();
    $property_count = count($property_ids);
    $datetime_select_enabled = isDateTimeEnabled();
    ob_start();
    ?>
    
    <?php if ($error_message): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div id="booking-success-modal" class="success-modal">
        <div class="success-modal-content">
            <button class="close-success-modal">X</button>
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle">
                    <path d="M9 12l2 2 4-4"></path>
                    <circle cx="12" cy="12" r="10"></circle>
                </svg>
            </div>
            <?php echo wp_kses_post($booking_success_message); ?>
        </div>
    </div>
    <div class="booking-form-wrapper">
        <?php if(!$datetime_select_enabled) : ?>
            <?php echo display_daterange_booking_form($property_id, $property_ids, $property_count, $property); ?>
        <?php else : ?>
            <?php echo display_datetime_booking_form(); ?>
        <?php endif; ?>
        
        <?php echo display_payment_form(); ?>
    </div>
    
    <?php
    return ob_get_clean();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['frontend_booking_nonce'])) {
    if (!wp_verify_nonce($_POST['frontend_booking_nonce'], 'final_form_submit')) {
        wp_die('Invalid nonce.');
    }
    
    $datetime_select_enabled = isDateTimeEnabled();
    
    if ($datetime_select_enabled) {
        handle_datetime_booking_form();
    } else {
        handle_daterange_booking_form();
    }
}

// function save_booking_to_calendar($room_id, $adults, $children, $start_date, $end_date, $name, $email) {
//     $room = get_room_details($room_id);
//     $options = get_option('booking_settings');
//     $checkin_time = isset($options['checkin_time']) ? esc_attr($options['checkin_time']) : '14:00';
//     $checkout_time = isset($options['checkout_time']) ? esc_attr($options['checkout_time']) : '12:00';
    
//     $event_details = array(
//         'summary' => 'Booked Room: ' . $room['name'],
//         'description' => 'Room: ' . $room['name'] . "\n" .
//                  'Booked by: ' . $name . "\n" .
//                  'Email: ' . $email . "\n" .
//                  'Adults: ' . $adults . "\n" .
//                  'Children: ' . $children,
//         'start' => $start_date . 'T' . $checkin_time . ':00',
//         'end' => $end_date . 'T' . $checkout_time . ':00',
//         'attendees' => array(
//             array('email' => $email),
//         ),
//     );
    
//     $result = sync_with_google_calendar($event_details);
// }

add_shortcode('reserve_mate_booking_form', 'display_booking_form');
