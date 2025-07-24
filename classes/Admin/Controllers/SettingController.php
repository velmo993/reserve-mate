<?php
namespace ReserveMate\Admin\Controllers;

use ReserveMate\Admin\Views\SettingViews;
use ReserveMate\Admin\Helpers\Notification;
use ReserveMate\Admin\Helpers\SecureCredentials;

require_once(RM_PLUGIN_PATH . 'includes/admin/sanitization.php');

defined('ABSPATH') or die('No direct access!');

class SettingController {
    private static $hooks_registered = false;
    
    public static function init() {
        if (!self::$hooks_registered) {
            add_action('admin_init', [self::class, 'register_all_options']);
            add_action('admin_init', [self::class, 'configure_smtp']);
            self::$hooks_registered = true;
        }
    }

    public static function register_all_options() {
        self::register_general_options();
        self::register_booking_options();
        self::register_google_calendar_options();
        self::register_service_options();
        self::register_form_options();
        self::register_style_options();
        self::register_payment_options();
        self::register_notification_options();
    }

    public static function load() {
        self::display_settings_page();
    }
    
    private static function register_notification_options() {
        register_setting('rm_notification_options_group', 'rm_notification_options', array(
            'sanitize_callback' => 'sanitize_rm_notification_options'
        ));
    
        add_settings_section(
            'booking_notifications',
            __('Booking Notifications', 'reserve-mate'),
            null,
            'manage-notifications'
        );
    
        // Existing fields
        add_settings_field(
            'booking_success_message',
            __('Booking Successful Message', 'reserve-mate'),
            [Notification::class, 'display_booking_success_message_field'],
            'manage-notifications',
            'booking_notifications'
        );
        
        add_settings_field(
            'send_email_to_clients',
            __('Send Booking Email to Guest', 'reserve-mate'),
            [Notification::class, 'display_send_email_to_clients_field'],
            'manage-notifications',
            'booking_notifications'
        );
        
        add_settings_field(
            'email_from_name',
            __('Email From Name', 'reserve-mate'),
            [Notification::class, 'display_email_from_name_field'],
            'manage-notifications',
            'booking_notifications'
        );
    
        add_settings_field(
            'email_from_address',
            __('Email From Address', 'reserve-mate'),
            [Notification::class, 'display_email_from_address_field'],
            'manage-notifications',
            'booking_notifications'
        );
        
        add_settings_field(
            'client_email_subject',
            __('Email Subject', 'reserve-mate'),
            [Notification::class, 'display_client_email_subject_field'],
            'manage-notifications',
            'booking_notifications'
        );
        
        add_settings_field(
            'client_email_content',
            __('Email Content', 'reserve-mate'),
            [Notification::class, 'display_client_email_content_field'],
            'manage-notifications',
            'booking_notifications'
        );
    
        add_settings_section(
            'smtp_settings',
            __('SMTP Settings', 'reserve-mate'),
            [Notification::class, 'display_smtp_settings_section'],
            'manage-notifications'
        );
    
        add_settings_field(
            'smtp_host',
            __('SMTP Host', 'reserve-mate'),
            [Notification::class, 'display_smtp_host_field'],
            'manage-notifications',
            'smtp_settings'
        );
    
        add_settings_field(
            'smtp_port',
            __('SMTP Port', 'reserve-mate'),
            [Notification::class, 'display_smtp_port_field'],
            'manage-notifications',
            'smtp_settings'
        );
    
        add_settings_field(
            'smtp_encryption',
            __('Encryption', 'reserve-mate'),
            [Notification::class, 'display_smtp_encryption_field'],
            'manage-notifications',
            'smtp_settings'
        );
    
        add_settings_field(
            'smtp_username',
            __('SMTP Username', 'reserve-mate'),
            [Notification::class, 'display_smtp_username_field'],
            'manage-notifications',
            'smtp_settings'
        );
    
        add_settings_field(
            'smtp_password',
            __('SMTP Password', 'reserve-mate'),
            [Notification::class, 'display_smtp_password_field'],
            'manage-notifications',
            'smtp_settings'
        );
        
        add_settings_section(
            'email_test_section',
            __('Test Email Settings', 'reserve-mate'),
            [Notification::class, 'display_email_test_section'],
            'manage-notifications'
        );
    
        add_settings_field(
            'test_email_address',
            __('Test Email Address', 'reserve-mate'),
            [Notification::class, 'display_test_email_address_field'],
            'manage-notifications',
            'email_test_section'
        );
    }
    
    private static function register_payment_options() {
        register_setting(
            'rm_payment_options_group',
            'rm_payment_options',
            array(
                'sanitize_callback' => 'sanitize_payment_options',
                'show_in_rest' => false,
            )
        );
        
        // STRIPE SETTINGS
        add_settings_section(
            'rm_stripe_section',
            __('Stripe Settings', 'reserve-mate'),
            null,
            'stripe-settings'
        );
        
        add_settings_field(
            'stripe_enabled',
            __('Enable Stripe', 'reserve-mate'),
            'display_stripe_enabled_field',
            'stripe-settings',
            'rm_stripe_section'
        );
    
        add_settings_field(
            'stripe_secret_key',
            __('Stripe Secret Key', 'reserve-mate'),
            'display_stripe_secret_key_field',
            'stripe-settings',
            'rm_stripe_section'
        );
    
        add_settings_field(
            'stripe_public_key',
            __('Stripe Public Key', 'reserve-mate'),
            'display_stripe_public_key_field',
            'stripe-settings',
            'rm_stripe_section'
        );
        
        // PAYPAL SETTINGS
        add_settings_section(
            'rm_paypal_section',
            __('PayPal Settings', 'reserve-mate'),
            null,
            'paypal-settings'
        );
        
        add_settings_field(
            'paypal_enabled',
            __('Enable PayPal', 'reserve-mate'),
            'display_paypal_enabled_field',
            'paypal-settings',
            'rm_paypal_section'
        );
        
        add_settings_field(
            'paypal_client_id',
            __('PayPal Client ID', 'reserve-mate'),
            'display_paypal_client_id_field',
            'paypal-settings',
            'rm_paypal_section'
        );
        
        // DEPOSIT PAYMENT SETTINGS
        add_settings_section(
            'rm_deposit_section',
            __('Deposit Payment Settings', 'reserve-mate'),
            null,
            'deposit-payment-settings'
        );
    
        add_settings_field(
            'deposit_payment_type',
            __('Deposit Payment Type', 'reserve-mate'),
            'display_deposit_payment_type_field',
            'deposit-payment-settings',
            'rm_deposit_section'
        );
    
        add_settings_field(
            'deposit_payment_percentage',
            __('Deposit Payment Percentage', 'reserve-mate'),
            'display_deposit_payment_percentage_field',
            'deposit-payment-settings',
            'rm_deposit_section'
        );
    
        add_settings_field(
            'deposit_payment_fixed_amount',
            __('Deposit Payment Fixed Amount', 'reserve-mate'),
            'display_deposit_payment_fixed_amount_field',
            'deposit-payment-settings',
            'rm_deposit_section'
        );
        
        add_settings_field(
            'deposit_payment_methods',
            __('Apply Deposit Payment to', 'reserve-mate'),
            'display_deposit_payment_methods_field',
            'deposit-payment-settings',
            'rm_deposit_section'
        );
        
        // PAY ON ARRIVAL SETTINGS
        add_settings_section(
            'rm_pay_on_arrival_section',
            __('Pay On Arrival Settings', 'reserve-mate'),
            null,
            'pay-on-arrival-settings'
        );
    
        add_settings_field(
            'pay_on_arrival_enabled',
            __('Enable Pay On Arrival', 'reserve-mate'),
            'display_pay_on_arrival_enabled_field',
            'pay-on-arrival-settings',
            'rm_pay_on_arrival_section'
        );
        
        // BANK TRANSFER SETTINGS
        add_settings_section(
            'rm_bank_transfer_section',
            __('Bank Transfer Settings', 'reserve-mate'),
            null,
            'bank-transfer-settings'
        );
    
        add_settings_field(
            'bank_transfer_enabled',
            __('Enable Bank Transfer', 'reserve-mate'),
            'display_bank_transfer_enabled_field',
            'bank-transfer-settings',
            'rm_bank_transfer_section'
        );
    
        add_settings_field(
            'bank_account_number',
            __('Bank Account Number', 'reserve-mate'),
            'display_bank_account_number_field',
            'bank-transfer-settings',
            'rm_bank_transfer_section'
        );
    
        add_settings_field(
            'bank_account_identifier',
            __('Bank Account Identifier (IBAN/Routing Number)', 'reserve-mate'),
            'display_bank_account_identifier_field',
            'bank-transfer-settings',
            'rm_bank_transfer_section'
        );
    
        add_settings_field(
            'bank_swift_bic',
            __('Bank SWIFT/BIC Code', 'reserve-mate'),
            'display_bank_swift_bic_field',
            'bank-transfer-settings',
            'rm_bank_transfer_section'
        );
    
        add_settings_field(
            'bank_name',
            __('Bank Name', 'reserve-mate'),
            'display_bank_name_field',
            'bank-transfer-settings',
            'rm_bank_transfer_section'
        );
    
        add_settings_field(
            'bank_recipient_name',
            __('Recipient Name', 'reserve-mate'),
            'display_bank_recipient_name_field',
            'bank-transfer-settings',
            'rm_bank_transfer_section'
        );
    
        add_settings_field(
            'bank_additional_info',
            __('Additional Bank Information', 'reserve-mate'),
            'display_bank_additional_info_field',
            'bank-transfer-settings',
            'rm_bank_transfer_section'
        );
    }

    private static function register_google_calendar_options() {
        register_setting(
            'rm_google_calendar_options_group',
            'rm_google_calendar_options',
            [
                'sanitize_callback' => 'sanitize_google_calendar_options',
                'show_in_rest' => false,
            ]
        );

        add_settings_section(
            'rm_google_calendar_options',
            __('Google Calendar', 'reserve-mate'),
            null,
            'google-calendar-settings'
        );
        
        add_settings_field(
            'calendar_selection',
            __('Select Calendar', 'reserve-mate'),
            'display_calendar_selection_field',
            'google-calendar-settings',
            'rm_google_calendar_options'
        );

        add_settings_field(
            'google_calendar_auth',
            __('Google Calendar Integration', 'reserve-mate'),
            'display_google_calendar_auth',
            'google-calendar-settings',
            'rm_google_calendar_options'
        );
        
    }
    
    private static function register_general_options() {
        register_setting(
            'rm_general_options_group',
            'rm_general_options',
            [
                'sanitize_callback' => 'sanitize_general_options',
                'show_in_rest' => false,
            ]
        );

        add_settings_section(
            'rm_general_options',
            __('General Settings', 'reserve-mate'),
            null,
            'general-settings'
        );
        
        add_settings_field(
            'currency',
            __('Currency', 'reserve-mate'),
            'display_currency_field',
            'general-settings',
            'rm_general_options'
        );
        
        add_settings_field(
            'calendar_timezones',
            __('Calendar Timezone', 'reserve-mate'),
            'display_calendar_timezones',
            'general-settings',
            'rm_general_options'
        );
        
        add_settings_field(
            'calendar_locale',
            __('Calendar Locale', 'reserve-mate'),
            'display_calendar_locale',
            'general-settings',
            'rm_general_options'
        );
    }

    private static function register_booking_options() {
        register_setting(
            'rm_booking_options_group',
            'rm_booking_options',
            [
                'sanitize_callback' => 'sanitize_booking_options',
                'show_in_rest' => false,
            ]
        );

        add_settings_section(
            'rm_booking_options',
            __('Booking Settings', 'reserve-mate'),
            null,
            'booking-settings'
        );
        
        add_settings_field(
            'enable_booking_approval',
            __('Enable Booking Approval', 'reserve-mate'),
            'display_enable_booking_approval',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'booking_min_time',
            __('First Available Time', 'reserve-mate'),
            'display_booking_min_time',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'booking_max_time',
            __('Last Available Time', 'reserve-mate'),
            'display_booking_max_time',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'booking_interval',
            __('Booking Interval (Minutes)', 'reserve-mate'),
            'display_booking_interval',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'buffer_time',
            __('Buffer Time (Minutes)', 'reserve-mate'),
            'display_buffer_time',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'minimum_lead_time',
            __('Minimum Lead Time', 'reserve-mate'),
            'display_minimum_lead_time',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'booking_min_date',
            __('Earliest Bookable Date', 'reserve-mate'),
            'display_booking_min_date',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'booking_max_date',
            __('Latest Bookable Date', 'reserve-mate'),
            'display_booking_max_date',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'booking_limits',
            __('Booking Limits', 'reserve-mate'),
            'display_booking_limits',
            'booking-settings',
            'rm_booking_options'
        );
        
        add_settings_field(
            'disable_dates',
            __('Disable Dates', 'reserve-mate'),
            'display_disable_dates',
            'booking-settings',
            'rm_booking_options'
        );
    }

    private static function register_service_options() {
        register_setting(
            'rm_service_options_group',
            'rm_service_options',
            [
                'sanitize_callback' => 'sanitize_service_options',
                'show_in_rest' => false,
            ]
        );

        add_settings_section(
            'rm_service_options',
            __('Service Settings', 'reserve-mate'),
            null,
            'service-settings'
        );
        
        add_settings_field(
            'max_selectable_services',
            __('Maximum Selectable Services', 'reserve-mate'),
            'display_max_selectable_services',
            'service-settings',
            'rm_service_options'
        );
    }

    private static function register_form_options() {
        register_setting(
            'rm_form_options_group',
            'rm_form_options',
            [
                'sanitize_callback' => 'sanitize_form_options',
                'show_in_rest' => false,
            ]
        );

        add_settings_section(
            'rm_form_options',
            __('Form Settings', 'reserve-mate'),
            null,
            'form-settings'
        );
        
        add_settings_field(
            'form_fields',
            __('Booking Form Fields', 'reserve-mate'),
            'display_form_fields',
            'form-settings',
            'rm_form_options'
        );
    }

    private static function register_style_options() {
        register_setting(
            'rm_style_options_group',
            'rm_style_options',
            [
                'sanitize_callback' => 'sanitize_style_options',
                'show_in_rest' => false,
            ]
        );

        add_settings_section(
            'rm_style_options',
            __('Style Settings', 'reserve-mate'),
            null,
            'style-settings'
        );
        
        add_settings_field(
            'calendar_display_type',
            __('Calendar Display Type', 'reserve-mate'),
            'display_calendar_display_type_field',
            'style-settings',
            'rm_style_options'
        );
        
        add_settings_field(
            'time_display_format',
            __('Time Slot Display Format', 'reserve-mate'),
            'display_time_format_field',
            'style-settings',
            'rm_style_options'
        );
        
        add_settings_field(
            'primary_color',
            __('Primary Color', 'reserve-mate'),
            'display_primary_color_field',
            'style-settings',
            'rm_style_options'
        );
        
        add_settings_field(
            'text_color',
            __('Text Color', 'reserve-mate'),
            'display_text_color_field',
            'style-settings',
            'rm_style_options'
        );
        
        add_settings_field(
            'font_family',
            __('Font Family', 'reserve-mate'),
            'display_font_family_field',
            'style-settings',
            'rm_style_options'
        );
        
        add_settings_field(
            'day_bg_color',
            __('Day Background Color', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'day_bg_color', 'default' => '#fff']
        );
        
        add_settings_field(
            'day_border_color',
            __('Day Border Color', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'day_border_color', 'default' => '#d2caca']
        );
        
        add_settings_field(
            'disabled_day_bg',
            __('Disabled Day Background', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'disabled_day_bg', 'default' => 'rgba(236, 13, 13, 0.28)']
        );
        
        add_settings_field(
            'prev_next_month_color',
            __('Prev/Next Month Day Text', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'prev_next_month_color', 'default' => '#9c9c9c']
        );
        
        add_settings_field(
            'prev_next_month_border',
            __('Prev/Next Month Day Border', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'prev_next_month_border', 'default' => '#e1e1e1']
        );
        
        add_settings_field(
            'arrival_bg',
            __('Arrival Day Background', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'arrival_bg', 'default' => 'linear-gradient(to left, #fff 50%, rgb(250 188 188) 50%)']
        );
        
        add_settings_field(
            'departure_bg',
            __('Departure Day Background', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'departure_bg', 'default' => 'linear-gradient(to right, #fff 50%, rgb(250 188 188) 50%)']
        );
        
        add_settings_field(
            'start_range_highlight',
            __('Start Range Highlight', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'start_range_highlight', 'default' => '#07c66594']
        );
        
        add_settings_field(
            'range_highlight',
            __('Date Range Highlight', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'range_highlight', 'default' => '#07c66594']
        );
        
        add_settings_field(
            'end_range_highlight',
            __('End Range Highlight', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'end_range_highlight', 'default' => '#07c66594']
        );
        
        add_settings_field(
            'day_hover_outline',
            __('Day Hover Outline', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'day_hover_outline', 'default' => '#000']
        );
        
        add_settings_field(
            'today_border_color',
            __('Today\'s Date Border', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'today_border_color', 'default' => '#959ea9']
        );
        
        add_settings_field(
            'nav_hover_color',
            __('Navigation Arrow Hover', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'nav_hover_color', 'default' => '#4CAF50']
        );
        
        add_settings_field(
            'week_number_color',
            __('Week Number Color', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'week_number_color', 'default' => '#333']
        );
        
        add_settings_field(
            'calendar_bg',
            __('Calendar Background', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'calendar_bg', 'default' => '#fff']
        );
        
        add_settings_field(
            'day_selected',
            __('Selected Day Background', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'day_selected', 'default' => '#07c66594']
        );
        
        add_settings_field(
            'day_selected_text',
            __('Selected Day Text', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'day_selected_text', 'default' => '#fff']
        );
        
        add_settings_field(
            'range_text_color',
            __('Range Text Color', 'reserve-mate'),
            'display_color_field',
            'style-settings',
            'rm_style_options',
            ['name' => 'range_text_color', 'default' => '#fff']
        );
    }
    
    public static function configure_smtp() {
        $rm_notification_options = get_option('rm_notification_options');
    
        if (!empty($rm_notification_options['smtp_host']) && !empty($rm_notification_options['smtp_username'])
            && !empty($rm_notification_options['smtp_port']) && !empty($rm_notification_options['smtp_password'])) {
            add_action('phpmailer_init', function ($phpmailer) use ($rm_notification_options) {
                $phpmailer->isSMTP();
                $phpmailer->Host = $rm_notification_options['smtp_host'];
                $phpmailer->SMTPAuth = true;
                $phpmailer->Port = $rm_notification_options['smtp_port'];
                $phpmailer->Username = $rm_notification_options['smtp_username'];
                $phpmailer->Password = SecureCredentials::decrypt($rm_notification_options['smtp_password'], SecureCredentials::SMTP);
                $phpmailer->SMTPSecure = $rm_notification_options['smtp_encryption'];
            });
        }
    }
    
    private static function display_settings_page() {
        SettingViews::render();
    }
}