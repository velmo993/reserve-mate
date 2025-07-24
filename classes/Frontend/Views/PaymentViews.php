<?php
namespace ReserveMate\Frontend\Views;
use ReserveMate\Shared\Helpers\PaymentHelpers;

defined('ABSPATH') or die('No direct access!');

class PaymentViews {
    public static function render_payment_form($data = []) {
        $payment_options = PaymentHelpers::get_payment_options();
        $stripe_enabled = PaymentHelpers::is_payment_method_enabled('stripe');
        $paypal_enabled = PaymentHelpers::is_payment_method_enabled('paypal');
        $pay_on_arrival_enabled = PaymentHelpers::is_payment_method_enabled('pay_on_arrival');
        $bank_transfer_enabled = PaymentHelpers::is_payment_method_enabled('bank_transfer');
        
        ob_start();
        ?>
        <div id="payment-form-wrap" class="payment-form-wrap hidden">
            <form id="payment-form" class="payment-form" method="post">
                <?php wp_nonce_field('final_form_submit', 'frontend_booking_nonce'); ?>
                
                <?php self::render_hidden_fields(); ?>
                
                <?php if (self::is_test_mode()) : ?>
                    <?php self::render_test_card_info(); ?>
                <?php endif; ?>
                
                <div class="payment-form-content">
                    <?php if ($stripe_enabled) : ?>
                        <?php self::render_stripe_section(); ?>
                    <?php endif; ?>
                    
                    <?php if ($paypal_enabled) : ?>
                        <?php self::render_paypal_section(); ?>
                    <?php endif; ?>
                    
                    <?php if ($pay_on_arrival_enabled || $bank_transfer_enabled) : ?>
                        <?php self::render_alternative_payment_methods($pay_on_arrival_enabled, $bank_transfer_enabled); ?>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render hidden form fields
     */
    private static function render_hidden_fields() {
        ?>
        <input type="hidden" name="clientSecret" value="">
        <input type="hidden" id="services-field" name="services-field[]" multiple value="">
        <input type="hidden" id="name-field" name="name-field" value="">
        <input type="hidden" id="email-field" name="email-field" value="">
        <input type="hidden" id="phone-field" name="phone-field" value="">
        <input type="hidden" id="start-date-field" name="start-date-field" value="">
        <input type="hidden" id="end-date-field" name="end-date-field" value="">
        <input type="hidden" id="staff-id-field" name="staff-id-field" value="">
        <input type="hidden" id="total-cost-field" name="total-cost-field" value="">
        <input type="hidden" id="actual-payment-field" name="actual-payment-field" value="">
        <input type="hidden" id="submit-pay-on-arrival-field" name="submit-pay-on-arrival" value="">
        <input type="hidden" id="submit-bank-transfer-field" name="submit-bank-transfer" value="">
        <?php
    }

    /**
     * Render test card information
     */
    private static function render_test_card_info() {
        ?>
        <div class="card-for-test">
            <p class="test-card-notice">
                <strong><?php _e('Test Mode:', 'reserve-mate'); ?></strong>
                <?php _e('Use card: 4242 4242 4242 4242, MM/YY: 04/26, CVC: 123', 'reserve-mate'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render Stripe payment section
     */
    private static function render_stripe_section() {
        ?>
        <div class="payment-method-section stripe-section">
            <h3><?php _e('Pay with Card', 'reserve-mate'); ?></h3>
            <div id="stripe-card-element" class="stripe-element">
                <!-- Stripe Elements will create form elements here -->
            </div>
            <div id="stripe-card-errors" class="payment-errors" role="alert"></div>
            <button type="submit" id="stripe-submit-button" class="payment-submit-btn">
                <?php _e('Pay Now', 'reserve-mate'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render PayPal payment section
     */
    private static function render_paypal_section() {
        ?>
        <div class="payment-method-section paypal-section">
            <h3><?php _e('Pay with PayPal', 'reserve-mate'); ?></h3>
            <div id="paypal-button-container" class="paypal-buttons">
                <!-- PayPal buttons will be rendered here -->
            </div>
        </div>
        <?php
    }

    /**
     * Render alternative payment methods
     */
    private static function render_alternative_payment_methods($pay_on_arrival_enabled, $bank_transfer_enabled) {
        ?>
        <div class="payment-method-section alternative-payments">
            <h3><?php _e('Other Payment Options', 'reserve-mate'); ?></h3>
            
            <?php if ($pay_on_arrival_enabled) : ?>
                <div class="payment-option">
                    <button type="button" id="pay-on-arrival-btn" class="payment-option-btn">
                        <span class="payment-icon">💳</span>
                        <span class="payment-text">
                            <strong><?php _e('Pay On Arrival', 'reserve-mate'); ?></strong>
                            <small><?php _e('Complete your booking and pay at the venue', 'reserve-mate'); ?></small>
                        </span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($bank_transfer_enabled) : ?>
                <div class="payment-option">
                    <button type="button" id="bank-transfer-btn" class="payment-option-btn">
                        <span class="payment-icon">🏦</span>
                        <span class="payment-text">
                            <strong><?php _e('Bank Transfer', 'reserve-mate'); ?></strong>
                            <small><?php _e('Transfer payment directly to our bank account', 'reserve-mate'); ?></small>
                        </span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render payment success message
     */
    public static function render_success_message($booking_data = []) {
        ob_start();
        ?>
        <div class="payment-success-wrap">
            <div class="payment-success-content">
                <div class="success-icon">✅</div>
                <h2><?php _e('Booking Confirmed!', 'reserve-mate'); ?></h2>
                <p><?php _e('Thank you for your booking. We have received your payment and your reservation is confirmed.', 'reserve-mate'); ?></p>
                
                <?php if (!empty($booking_data)) : ?>
                    <div class="booking-details">
                        <h3><?php _e('Booking Details', 'reserve-mate'); ?></h3>
                        <ul>
                            <?php if (isset($booking_data['booking_id'])) : ?>
                                <li><strong><?php _e('Booking ID:', 'reserve-mate'); ?></strong> #<?php echo esc_html($booking_data['booking_id']); ?></li>
                            <?php endif; ?>
                            <?php if (isset($booking_data['customer_name'])) : ?>
                                <li><strong><?php _e('Name:', 'reserve-mate'); ?></strong> <?php echo esc_html($booking_data['customer_name']); ?></li>
                            <?php endif; ?>
                            <?php if (isset($booking_data['start_date'])) : ?>
                                <li><strong><?php _e('Date:', 'reserve-mate'); ?></strong> <?php echo esc_html($booking_data['start_date']); ?></li>
                            <?php endif; ?>
                            <?php if (isset($booking_data['total_cost'])) : ?>
                                <li><strong><?php _e('Total:', 'reserve-mate'); ?></strong> <?php echo esc_html($booking_data['total_cost']); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="success-actions">
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <?php _e('Print Confirmation', 'reserve-mate'); ?>
                    </button>
                    <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-secondary">
                        <?php _e('Back to Home', 'reserve-mate'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render payment error message
     */
    public static function render_error_message($error_message = '') {
        ob_start();
        ?>
        <div class="payment-error-wrap">
            <div class="payment-error-content">
                <div class="error-icon">❌</div>
                <h2><?php _e('Payment Failed', 'reserve-mate'); ?></h2>
                <p><?php _e('There was an issue processing your payment. Please try again.', 'reserve-mate'); ?></p>
                
                <?php if (!empty($error_message)) : ?>
                    <div class="error-details">
                        <p><strong><?php _e('Error:', 'reserve-mate'); ?></strong> <?php echo esc_html($error_message); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="error-actions">
                    <button type="button" class="btn btn-primary" onclick="history.back()">
                        <?php _e('Try Again', 'reserve-mate'); ?>
                    </button>
                    <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-secondary">
                        <?php _e('Back to Home', 'reserve-mate'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if we're in test mode
     */
    private static function is_test_mode() {
        // $payment_options = PaymentHelpers::get_payment_options();
        // return isset($payment_options['test_mode']) && $payment_options['test_mode'] === '1';
        return true;
    }

    /**
     * Render bank transfer instructions
     */
    public static function render_bank_transfer_instructions() {
        $payment_options = PaymentHelpers::get_payment_options();
        $bank_details = isset($payment_options['bank_details']) ? $payment_options['bank_details'] : '';
        
        ob_start();
        ?>
        <div class="bank-transfer-instructions">
            <h3><?php _e('Bank Transfer Instructions', 'reserve-mate'); ?></h3>
            <div class="bank-details">
                <?php if (!empty($bank_details)) : ?>
                    <?php echo wp_kses_post(wpautop($bank_details)); ?>
                <?php else : ?>
                    <p><?php _e('Bank transfer details will be provided after booking confirmation.', 'reserve-mate'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}