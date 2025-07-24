<?php
namespace ReserveMate\Frontend\Controllers;

use ReserveMate\Shared\Helpers\PaymentHelpers;
use ReserveMate\Frontend\Views\PaymentViews;

defined('ABSPATH') or die('No direct access!');

class PaymentController {
    
    /**
     * Initialize payment-related hooks
     */
    public static function init() {
        add_action('wp_ajax_create_payment_intent', [self::class, 'handle_create_payment_intent']);
        add_action('wp_ajax_nopriv_create_payment_intent', [self::class, 'handle_create_payment_intent']);
        add_action('wp_ajax_change_currency', [self::class, 'handle_currency_change']);
        add_action('wp_ajax_nopriv_change_currency', [self::class, 'handle_currency_change']);
    }

    /**
     * Handle Stripe Payment Intent creation via AJAX
     */
    public static function handle_create_payment_intent() {
        // Verify nonce if needed
        if (!isset($_POST['totalPaymentCost'])) {
            wp_send_json_error(['error' => 'Missing total payment cost.']);
            return;
        }

        $total_cost = floatval($_POST['totalPaymentCost']);
        
        if ($total_cost <= 0) {
            wp_send_json_error(['error' => 'Invalid payment amount.']);
            return;
        }

        $options = get_option('rm_booking_options', []);
        $currency = isset($options['currency']) ? $options['currency'] : 'USD';

        $result = PaymentHelpers::create_stripe_payment_intent($total_cost, $currency);

        if (is_wp_error($result)) {
            wp_send_json_error(['error' => $result->get_error_message()]);
            return;
        }

        if ($result['success']) {
            wp_send_json_success([
                'clientSecret' => $result['client_secret'],
                'paymentIntentId' => $result['payment_intent_id']
            ]);
        } else {
            wp_send_json_error(['error' => 'Failed to create payment intent.']);
        }
    }

    /**
     * Handle currency change via AJAX
     */
    public static function handle_currency_change() {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'currency_change_nonce')) {
            wp_send_json_error(['message' => 'Security check failed.']);
            return;
        }

        if (!isset($_POST['currency'])) {
            wp_send_json_error(['message' => 'Currency not provided.']);
            return;
        }

        $new_currency = sanitize_text_field($_POST['currency']);
        $result = PaymentHelpers::update_currency($new_currency);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'currency' => $result['currency'],
            'symbol' => $result['symbol'],
            'reload_paypal' => true
        ]);
    }

    /**
     * Process payment form submission
     */
    public static function handle_payment_form_submission() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Verify nonce
        if (!isset($_POST['frontend_booking_nonce']) || 
            !wp_verify_nonce($_POST['frontend_booking_nonce'], 'final_form_submit')) {
            wp_die('Security check failed');
        }

        $payment_data = self::sanitize_payment_data($_POST);
        
        // Process different payment methods
        if (isset($_POST['submit-pay-on-arrival'])) {
            return self::process_pay_on_arrival($payment_data);
        }

        if (isset($_POST['submit-bank-transfer'])) {
            return self::process_bank_transfer($payment_data);
        }

        // Default Stripe processing
        return self::process_stripe_payment($payment_data);
    }

    /**
     * Sanitize payment form data
     */
    private static function sanitize_payment_data($post_data) {
        return [
            'client_secret' => sanitize_text_field($post_data['clientSecret'] ?? ''),
            'services' => isset($post_data['services-field']) ? array_map('sanitize_text_field', $post_data['services-field']) : [],
            'name' => sanitize_text_field($post_data['name-field'] ?? ''),
            'email' => sanitize_email($post_data['email-field'] ?? ''),
            'phone' => sanitize_text_field($post_data['phone-field'] ?? ''),
            'start_date' => sanitize_text_field($post_data['start-date-field'] ?? ''),
            'end_date' => sanitize_text_field($post_data['end-date-field'] ?? ''),
            'staff_id' => intval($post_data['staff-id-field'] ?? 0),
            'total_cost' => floatval($post_data['total-cost-field'] ?? 0),
            'actual_payment' => floatval($post_data['actual-payment-field'] ?? 0)
        ];
    }

    /**
     * Process Stripe payment
     */
    private static function process_stripe_payment($payment_data) {
        if (empty($payment_data['client_secret'])) {
            return new \WP_Error('missing_client_secret', 'Payment client secret is missing.');
        }

        // Extract payment intent ID from client secret
        $client_secret_parts = explode('_secret_', $payment_data['client_secret']);
        if (count($client_secret_parts) < 2) {
            return new \WP_Error('invalid_client_secret', 'Invalid payment client secret.');
        }

        $payment_intent_id = $client_secret_parts[0];
        $payment_intent = PaymentHelpers::retrieve_payment_intent($payment_intent_id);

        if (is_wp_error($payment_intent)) {
            return $payment_intent;
        }

        // Process successful payment
        return self::complete_booking($payment_data, $payment_intent_id, 'stripe');
    }

    /**
     * Process pay on arrival option
     */
    private static function process_pay_on_arrival($payment_data) {
        if (!PaymentHelpers::is_payment_method_enabled('pay_on_arrival')) {
            return new \WP_Error('payment_method_disabled', 'Pay on arrival option is not enabled.');
        }

        return self::complete_booking($payment_data, null, 'pay_on_arrival');
    }

    /**
     * Process bank transfer option
     */
    private static function process_bank_transfer($payment_data) {
        if (!PaymentHelpers::is_payment_method_enabled('bank_transfer')) {
            return new \WP_Error('payment_method_disabled', 'Bank transfer option is not enabled.');
        }

        return self::complete_booking($payment_data, null, 'bank_transfer');
    }

    /**
     * Complete the booking process
     */
    private static function complete_booking($payment_data, $transaction_id = null, $payment_method = 'stripe') {
        // This would contain your booking completion logic
        // For now, returning success response
        
        global $wpdb;
        
        // Insert booking record
        $table_name = $wpdb->prefix . "reservemate_bookings";
        
        $booking_data = [
            'customer_name' => $payment_data['name'],
            'customer_email' => $payment_data['email'],
            'customer_phone' => $payment_data['phone'],
            'start_date' => $payment_data['start_date'],
            'end_date' => $payment_data['end_date'],
            'staff_id' => $payment_data['staff_id'],
            'total_cost' => $payment_data['total_cost'],
            'payment_method' => $payment_method,
            'transaction_id' => $transaction_id,
            'status' => ($payment_method === 'stripe') ? 'confirmed' : 'pending',
            'created_at' => current_time('mysql')
        ];

        $result = $wpdb->insert($table_name, $booking_data);

        if ($result === false) {
            return new \WP_Error('booking_failed', 'Failed to complete booking.');
        }

        return [
            'success' => true,
            'booking_id' => $wpdb->insert_id,
            'message' => 'Booking completed successfully.'
        ];
    }
    
}