<?php
namespace ReserveMate\Shared\Helpers;

defined('ABSPATH') or die('No direct access!');

use Stripe\Stripe;
use Stripe\PaymentIntent;
use ReserveMate\Admin\Helpers\Payment;

class PaymentHelpers {
    /**
     * Initialize Stripe with API key
     */
    public static function init_stripe() {
        $secret_key = Payment::get_stripe_secret_key();
        if (isset($secret_key)) {
            Stripe::setApiKey($secret_key);
        }
    }

    /**
     * Create Stripe Payment Intent
     */
    public static function create_stripe_payment_intent($amount, $currency) {
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            return new \WP_Error('invalid_amount', 'Invalid payment amount provided.');
        }

        if (empty($currency) || !is_string($currency)) {
            return new \WP_Error('invalid_currency', 'Invalid currency provided.');
        }

        self::init_stripe();

        try {
            $zero_decimal_currencies = [
                'jpy', 'clp', 'krw', 'vnd', 'xaf', 'xof', 'bif', 'djf', 'gnf', 
                'kmf', 'mga', 'pyg', 'rwf', 'ugx', 'vuv', 'xpf'
            ];

            $currency = strtolower($currency);
            $processed_amount = in_array($currency, $zero_decimal_currencies) 
                ? intval($amount) 
                : intval($amount * 100);

            $paymentIntent = PaymentIntent::create([
                'amount' => $processed_amount,
                'currency' => $currency,
                'payment_method_types' => ['card'],
                'setup_future_usage' => 'off_session',
            ]);

            return [
                'success' => true, 
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];
        } catch (\Exception $e) {
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Retrieve Stripe Payment Intent
     */
    public static function retrieve_payment_intent($payment_intent_id) {
        if (empty($payment_intent_id) || !is_string($payment_intent_id)) {
            return new \WP_Error('invalid_payment_intent_id', 'Invalid payment intent ID provided.');
        }

        self::init_stripe();

        try {
            $paymentIntent = PaymentIntent::retrieve($payment_intent_id);
            return $paymentIntent;
        } catch (\Exception $e) {
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Check if currency is supported by PayPal
     */
    public static function is_paypal_currency_supported($currency) {
        $supported_currencies = [
            'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS',
            'JPY', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK',
            'CHF', 'TWD', 'THB', 'USD'
        ];

        return in_array(strtoupper($currency), $supported_currencies, true);
    }

    /**
     * Update currency setting
     */
    public static function update_currency($new_currency) {
        if (empty($new_currency) || !is_string($new_currency)) {
            return new \WP_Error('invalid_currency', 'Invalid currency provided.');
        }

        $new_currency = strtoupper(sanitize_text_field($new_currency));

        if (!self::is_paypal_currency_supported($new_currency)) {
            return new \WP_Error('unsupported_currency', 'Currency not supported by PayPal.');
        }

        $options = get_option('rm_booking_options', []);
        $options['currency'] = $new_currency;
        $updated = update_option('rm_booking_options', $options);

        if (!$updated) {
            return new \WP_Error('update_failed', 'Failed to update currency setting.');
        }

        return [
            'success' => true,
            'currency' => $new_currency,
            'symbol' => self::get_currency_symbol($new_currency)
        ];
    }

    /**
     * Get currency symbol
     */
    public static function get_currency_symbol($currency) {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'SEK' => 'kr',
            'NZD' => 'NZ$'
        ];

        return isset($symbols[strtoupper($currency)]) ? $symbols[strtoupper($currency)] : strtoupper($currency);
    }

    /**
     * Get zero decimal currencies list
     */
    public static function get_zero_decimal_currencies() {
        return [
            'jpy', 'clp', 'krw', 'vnd', 'xaf', 'xof', 'bif', 'djf', 'gnf', 
            'kmf', 'mga', 'pyg', 'rwf', 'ugx', 'vuv', 'xpf'
        ];
    }

    /**
     * Get payment options
     */
    public static function get_payment_options() {
        return get_option('rm_payment_options', []);
    }

    /**
     * Check if payment method is enabled
     */
    public static function is_payment_method_enabled($method) {
        $payment_options = self::get_payment_options();
        $enabled_key = $method . '_enabled';
        return isset($payment_options[$enabled_key]) && $payment_options[$enabled_key] === '1';
    }
}