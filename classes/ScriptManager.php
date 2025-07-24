<?php
namespace ReserveMate;
use ReserveMate\Admin\Helpers\Staff;
use ReserveMate\Admin\Helpers\Payment;
use ReserveMate\Admin\Helpers\TestEmail;

defined('ABSPATH') or die('No direct access!');

/**
 * Script Manager Class
 */
class ScriptManager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_email_test_script']);
        add_action('wp_ajax_test_admin_email', [TestEmail::class, 'test_admin_email_callback']);
        add_action('wp_ajax_test_client_email', [TestEmail::class, 'test_client_email_callback']);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_media();
        
        // External libraries
        wp_enqueue_style('flatpickr-css', RM_PLUGIN_URL . 'assets/css/flatpickr.min.css');
        wp_enqueue_script('flatpickr-js', RM_PLUGIN_URL . 'assets/js/flatpickr.min.js', [], null, true);
        wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', [], '4.0.13');
        wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', [], '4.0.13', true);
        wp_enqueue_style('jquery-datetimepicker-css', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css');
        wp_enqueue_script('jquery-datetimepicker-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js', ['jquery'], null, true);
        
        // Plugin styles and scripts
        wp_enqueue_style('booking-plugin-styles', RM_PLUGIN_URL . 'assets/css/style.css', [], '1.0.0');
        wp_enqueue_script('booking-plugin-scripts', RM_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'flatpickr-js', 'select2-js'], null, true);
        
        // WordPress built-in
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Localize script
        wp_localize_script('booking-plugin-scripts', 'reserve_mate_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reserve_mate_nonce')
        ]);
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Basic scripts
        wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', [], '4.0.13');
        wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], '4.0.13', true);
        wp_enqueue_script('frontend-scripts', RM_PLUGIN_URL . 'assets/js/frontend.js', ['jquery', 'select2-js'], null, true);
        
        // Payment scripts
        $this->enqueue_payment_scripts();
        
        // Flatpickr
        $this->enqueue_flatpickr_scripts();
        
        // Localize ALL scripts after they're enqueued
        $this->localize_scripts();
    }
    
    /**
     * Enqueue Flatpickr scripts
     */
    private function enqueue_flatpickr_scripts() {
        wp_enqueue_style('flatpickr-css', RM_PLUGIN_URL . 'assets/css/flatpickr.min.css');
        wp_enqueue_script('flatpickr-js', RM_PLUGIN_URL . 'assets/js/flatpickr.min.js', [], null, true);
        wp_enqueue_script('booking-js', RM_PLUGIN_URL . 'assets/js/booking-main.js', ['flatpickr-js'], null, true);
        
        // Handle localization
        $this->handle_flatpickr_localization();
    }
    
    /**
     * Handle Flatpickr localization
     */
    private function handle_flatpickr_localization() {
        $options = get_option('rm_general_options', []);
        $calendar_locale = $options['calendar_locale'] ?? 'en-US';
        $base_locale = explode('-', $calendar_locale)[0];
        $locales_dir = RM_PLUGIN_PATH . 'assets/l10n/';
        $available_locales = array_map(
            function($f) { return basename($f, '.js'); },
            glob($locales_dir . '*.js')
        );
        
        if ($base_locale !== 'en' && in_array($base_locale, $available_locales)) {
            wp_enqueue_script(
                "flatpickr-locale-{$base_locale}",
                RM_PLUGIN_URL . "assets/l10n/{$base_locale}.js",
                ['flatpickr-js'],
                null,
                true
            );
        }
    }
    
    private function localize_scripts() {
        $booking_options = get_option('rm_booking_options', []);
        $style_options = get_option('rm_style_options', []);
        $general_options = get_option('rm_general_options', []);
        $service_options = get_option('rm_service_options', []);
        $p_settings = get_option('rm_payment_options', []);
        $payment_options = $this->prepare_payment_options($p_settings);
    
        $date_format = $general_options['date_format'] ?? 'Y-m-d H:i';
        $selected_timezone = $general_options['calendar_timezones'] ?? 'UTC';
        $base_locale = explode('-', $general_options['calendar_locale'] ?? 'en-US')[0];
        $disabled = get_disabled_dates();
        $staff_count = Staff::get_staff_count() || 0;
    
        $shared_data = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'date_format' => $date_format,
            'timezone' => $selected_timezone,
            'locale' => $base_locale,
            'hasLocale' => in_array($base_locale, glob(RM_PLUGIN_PATH . 'assets/l10n/*.js')) || $base_locale === 'en',
            'homeurl' => home_url(),
            'privacyurl' => get_privacy_policy_url(),
            'imagePath' => RM_PLUGIN_URL . 'assets/images',
            'currency' => get_currency(),
        ];
    
        $booking_data = [
            'nonce' => wp_create_nonce('booking_js_nonce'),
            'inline_calendar' => $style_options['calendar_display_type'] ?? 'popup',
            'disabled_dates' => $disabled['dates'],
            'disabled_days' => $disabled['days'],
            'disabled_time_periods' => $disabled['time_periods'],
            'staff_enabled' => $staff_count > 0,
            'timeslot_format' => $booking_options['time_display_format'] ?? 'range',
            'min_date' => $booking_options['booking_min_date'] ?? '',
            'max_date' => get_effective_max_date(),
            'max_services' => $service_options['max_selectable_services'] ?? '10',
        ];
    
        $frontend_data = [
            'nonce' => wp_create_nonce('send_email_nonce'),
            'paymentSettings' => $payment_options,
        ];
        
        if (wp_script_is('booking-js', 'enqueued') || wp_script_is('booking-js', 'registered')) {
            wp_localize_script('booking-js', 'rmVars', array_merge($shared_data, $booking_data));
        }
        
        if (wp_script_is('frontend-scripts', 'enqueued') || wp_script_is('frontend-scripts', 'registered')) {
            wp_localize_script('frontend-scripts', 'paymentVars', array_merge($shared_data, $frontend_data));
        }
    }
    
    /**
     * Enqueue payment scripts based on options
     */
    private function enqueue_payment_scripts() {
        $payment_options = get_option('rm_payment_options', []);
        
        if (!empty($payment_options['paypal_enabled'])) {
            $this->enqueue_paypal_scripts();
        }
        
        if (!empty($payment_options['stripe_enabled'])) {
            $this->enqueue_stripe_scripts();
        }
    }
    
    /**
     * Enqueue PayPal scripts
     */
    private function enqueue_paypal_scripts() {
        $paypal_client_id = Payment::get_paypal_client_id();
        $currency = get_currency_code_uppercase();
        
        if (empty($paypal_client_id)) {
            error_log('PayPal client ID is not configured');
            return;
        }
        
        if (!is_paypal_currency_supported($currency)) {
            error_log("Currency {$currency} is not supported by PayPal");
            $currency = 'USD';
        }
        
        $paypal_sdk_url = add_query_arg([
            'client-id' => $paypal_client_id,
            'currency' => $currency,
            'disable-funding' => 'card',
            'enable-funding' => 'venmo',
            'intent' => 'capture'
        ], 'https://www.paypal.com/sdk/js');
        
        wp_enqueue_script('paypal-js', $paypal_sdk_url, [], null, true);
        wp_enqueue_script('paypal-payment', RM_PLUGIN_URL . 'assets/js/paypal-payment.js', ['paypal-js'], null, true);
        
        wp_localize_script('paypal-payment', 'paypal_vars', [
            'currency' => $currency,
            'currencySymbol' => get_currency_symbol($currency),
            'isRTL' => is_rtl()
        ]);
    }
    
    /**
     * Enqueue Stripe scripts
     */
    private function enqueue_stripe_scripts() {
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], null, true);
        wp_enqueue_script('stripe-payment', RM_PLUGIN_URL . 'assets/js/stripe-payment.js', ['stripe-js'], null, true);
        
        $stripe_public_key = get_option('rm_payment_options')['stripe_public_key'] ?? '';
        wp_localize_script('stripe-payment', 'stripe_vars', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'stripePublicKey' => $stripe_public_key
        ]);
    }
    
    /**
     * Prepare payment options for frontend
     */
    private function prepare_payment_options($p_settings) {
        $stripe_public_key = $p_settings['stripe_public_key'] ?? '';
        $stripe_secret_key = $p_settings['stripe_secret_key'] ?? '';
        $stripe_enabled = (!empty($p_settings['stripe_enabled']) && !empty($stripe_public_key) && !empty($stripe_secret_key)) ? strval(1) : '0';
        
        $client_id = $p_settings['paypal_client_id'] ?? '';
        $paypal_enabled = (!empty($p_settings['paypal_enabled']) && !empty($client_id)) ? strval(1) : '0';
        
        $deposit_payment_methods = isset($p_settings['deposit_payment_methods']) ? $p_settings['deposit_payment_methods'] : [];
        $deposit_payment_methods_array = [
            'stripe' => isset($deposit_payment_methods['stripe']) ? strval($deposit_payment_methods['stripe']) : '0',
            'paypal' => isset($deposit_payment_methods['paypal']) ? strval($deposit_payment_methods['paypal']) : '0',
            'bank_transfer' => isset($deposit_payment_methods['bank_transfer']) ? strval($deposit_payment_methods['bank_transfer']) : '0',
            'pay_on_arrival' => isset($deposit_payment_methods['pay_on_arrival']) ? strval($deposit_payment_methods['pay_on_arrival']) : '0'
        ];
        
        return [
            'stripe_enabled' => $stripe_enabled,
            'paypal_enabled' => $paypal_enabled,
            'bank_transfer_enabled' => !empty($p_settings['bank_transfer_enabled']) ? strval($p_settings['bank_transfer_enabled']) : '0',
            'pay_on_arrival_enabled' => !empty($p_settings['pay_on_arrival_enabled']) ? strval($p_settings['pay_on_arrival_enabled']) : '0',
            'deposit_payment_type' => isset($p_settings['deposit_payment_type']) ? strval($p_settings['deposit_payment_type']) : '0',
            'deposit_payment_percentage' => isset($p_settings['deposit_payment_percentage']) ? floatval($p_settings['deposit_payment_percentage']) : '0',
            'deposit_payment_fixed_amount' => isset($p_settings['deposit_payment_fixed_amount']) ? floatval($p_settings['deposit_payment_fixed_amount']) : '0',
            'deposit_payment_methods' => $deposit_payment_methods_array
        ];
    }
    
    /**
     * Enqueue email test script
     */
    public function enqueue_email_test_script() {
        $screen = get_current_screen();
        
        if ($screen && strpos($screen->id, 'reserve-mate') !== false) {
            wp_enqueue_script('email-test-script', RM_PLUGIN_URL . 'assets/js/test-email.js', ['jquery'], '1.0', true);
            wp_localize_script('email-test-script', 'email_test_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('email_test_nonce')
            ]);
        }
    }
}