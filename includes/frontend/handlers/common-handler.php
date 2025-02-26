<?php
defined('ABSPATH') or die('No direct access!');

function handle_payment() {
    $payment_settings = get_option('payment_settings');
    $pay_on_arrival_enabled = isset($payment_settings['pay_on_arrival_enabled']) ? $payment_settings['pay_on_arrival_enabled'] : '0';
    $bank_transfer_enabled = isset($payment_settings['bank_transfer_enabled']) ? $payment_settings['bank_transfer_enabled'] : '0';

    if (!empty($_POST['submit-pay-on-arrival']) && $pay_on_arrival_enabled) {
        return ['success' => true, 'method' => 'pay_on_arrival'];
    } else if (!empty($_POST['submit-bank-transfer']) && $bank_transfer_enabled) {
        return ['success' => true, 'method' => 'bank_transfer'];
    } else if (!empty($_POST['paypalPaymentID'])) {
        return ['success' => true, 'method' => 'paypal'];
    } else if (!empty($_POST['clientSecret'])) {
        $clientSecret = sanitize_text_field($_POST['clientSecret']);
        try {
            $paymentIntentId = explode('_secret_', $clientSecret)[0];
            $paymentIntent = retrieve_payment_intent($paymentIntentId);
            if ($paymentIntent && $paymentIntent->status === 'succeeded') {
                return ['success' => true, 'method' => 'card_stripe'];
            }
        } catch (\Exception $e) {
            error_log('Stripe error: ' . $e->getMessage());
        }
    }

    return false;
}