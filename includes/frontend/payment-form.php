<?php
defined('ABSPATH') or die('No direct access!');

function display_payment_form() {
    $payment_settings = get_option('payment_settings');
    $stripe_enabled = isset($payment_settings['stripe_enabled']) ? $payment_settings['stripe_enabled'] : '0';
    $paypal_enabled = isset($payment_settings['paypal_enabled']) ? $payment_settings['paypal_enabled'] : '0';
    ob_start();
    ?>
    
    <div id="payment-form-wrap" class="payment-form-wrap hidden">
        <h3>Your Booking Summary</h3>
        <form id="payment-form" class="payment-form" method="post">
            <?php wp_nonce_field('final_form_submit', 'frontend_booking_nonce'); ?>
            <input type="hidden" name="clientSecret" value="">
            <input type="hidden" id="services-field" name="services-field[]" multiple value="">
            <input type="hidden" id="multiple-properties" name="multiple-properties" value="false">
            <input type="hidden" id="multiple-ids" name="multiple-ids" value="">
            <input type="hidden" id="single-id" name="single-id" value="">
            <input type="hidden" id="name-field" name="name-field" value="">
            <input type="hidden" id="email-field" name="email-field" value="">
            <input type="hidden" id="phone-field" name="phone-field" value="">
            <input type="hidden" id="client-request-field" name="client-request-field" value="">
            <input type="hidden" id="adults-field" name="adults-field" value="">
            <input type="hidden" id="children-field" name="children-field" value="">
            <input type="hidden" id="pets-field" name="pets-field" value="">
            <input type="hidden" id="start-date-field" name="start-date-field" value="">
            <input type="hidden" id="end-date-field" name="end-date-field" value="">
            <input type="hidden" id="staff-id-field" name="staff-id-field" value="">
            <input type="hidden" id="total-cost-field" name="total-cost-field" value="">
            <input type="hidden" id="actual-payment-field" name="actual-payment-field" value="">
            <div class="card-for-test hidden">
                <p>‼ For test payment use: Card: 4242 4242 4242 4242 MM/YY:04/26 CVC:123 ‼</p>
            </div>
            <div class="payment-form-content">
            </div> <!-- Payment form from frontend.js -->
        </form>
    </div>
    <?php
    return ob_get_clean();
}