<?php
namespace ReserveMate\Frontend\Controllers;
use ReserveMate\Frontend\Views\BookingViews;
use ReserveMate\Frontend\Views\PaymentViews;
use ReserveMate\Shared\Helpers\BookingHelpers;
use ReserveMate\Admin\Helpers\Service;

defined('ABSPATH') or die('No direct access!');

class BookingController {
    
    public static function display_booking_form($atts = []) {
        // Handle form submission if it's a POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['frontend_booking_nonce'])) {
            self::handle_booking_submission();
        }
        
        // Prepare data for the view
        $view_data = self::prepare_view_data();
        
        // Return the rendered booking form with hidden payment form
        return BookingViews::render_booking_form($view_data) . PaymentViews::render_payment_form();
    }
    
    private static function prepare_view_data() {
        $services = Service::get_services();
        $currency = get_currency();
        $form_fields = BookingHelpers::get_form_fields();
        $inline_calendar = BookingHelpers::is_inline_calendar();
        $success_message = BookingHelpers::get_booking_success_message();
        
        // Sort form fields by order
        usort($form_fields, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        return [
            'services' => $services,
            'currency' => $currency,
            'form_fields' => $form_fields,
            'inline_calendar' => $inline_calendar,
            'success_message' => $success_message,
            'error_message' => '',
            'plugin_url' => RM_PLUGIN_URL
        ];
    }
    
    private static function handle_booking_submission() {
        if (!wp_verify_nonce($_POST['frontend_booking_nonce'], 'final_form_submit')) {
            wp_die('Invalid nonce.');
        }
        
        try {
            $booking_data = BookingHelpers::sanitize_booking_data($_POST);
            $services = BookingHelpers::process_services($_POST['services-field'] ?? []);
            $custom_fields = BookingHelpers::process_custom_fields($_POST);
            $payment_result = BookingHelpers::handle_payment($_POST);
            
            if ($payment_result) {
                $final_paid_amount = $booking_data['paid_amount'];
                if (isset($payment_result['paid_amount'])) {
                    $final_paid_amount = $payment_result['paid_amount'];
                }
                
                $booking_id = BookingHelpers::save_booking(
                    $booking_data,
                    $payment_result['method'],
                    $services,
                    $final_paid_amount,
                    $custom_fields
                );
                
                if ($booking_id) {
                    self::redirect_success();
                } else {
                    self::handle_error('Failed to save booking.');
                }
            } else {
                self::handle_error('Payment processing failed.');
            }
            
        } catch (Exception $e) {
            self::handle_error('An error occurred: ' . $e->getMessage());
        }
    }
    
    private static function redirect_success() {
        echo '<script>window.location.href="' . home_url() . '?booking_status=success";</script>';
        exit;
    }
    
    private static function handle_error($message) {
        echo json_encode(['message' => $message]);
        exit;
    }
    
    public static function init_shortcode() {
        add_shortcode('reserve_mate_booking_form', array(self::class, 'display_booking_form'));
    }
}