<?php
namespace ReserveMate\Shared\Helpers;

use ReserveMate\Admin\Helpers\Service;
use ReserveMate\Admin\Helpers\Booking;
use ReserveMate\Shared\Helpers\Payment;

defined('ABSPATH') or die('No direct access!');

class BookingHelpers {
    
    public static function get_form_fields() {
        $options = get_option('rm_form_options');
        return isset($options['form_fields']) ? $options['form_fields'] : self::get_default_form_fields();
    }
    
    private static function get_default_form_fields() {
        return array(
            array(
                'id' => 'name',
                'label' => 'Full Name',
                'type' => 'text',
                'placeholder' => 'Full name',
                'required' => true,
                'order' => 1,
                'autocomplete' => 'name'
            ),
            array(
                'id' => 'email',
                'label' => 'Email Address',
                'type' => 'email',
                'placeholder' => 'Email address',
                'required' => true,
                'order' => 2,
                'autocomplete' => 'email'
            ),
            array(
                'id' => 'phone',
                'label' => 'Phone Number',
                'type' => 'tel',
                'placeholder' => 'Phone number',
                'required' => false,
                'order' => 3,
                'autocomplete' => 'phone'
            )
        );
    }
    
    public static function is_inline_calendar() {
        $rm_style_settings = get_option('rm_style_settings');
        $inline_calendar = isset($rm_style_settings['calendar_display_type']) ? $rm_style_settings['calendar_display_type'] : 'full';
        return $inline_calendar;
    }
    
    public static function get_booking_success_message() {
        $rm_notification_options = get_option('rm_notification_options');
        return !empty($rm_notification_options['booking_success_message']) 
            ? $rm_notification_options['booking_success_message'] 
            : '<h2>Booking Successful!</h2><p>Thank you for choosing Us!</p>';
    }
    
    public static function process_services($services_data) {
        $services = [];
        if (!empty($services_data) && is_array($services_data)) {
            $service_ids = json_decode(stripslashes($services_data[0]), true);
            if (is_array($service_ids)) {
                foreach ($service_ids as $service_id) {
                    $service_id = intval($service_id);
                    if ($service_id > 0) {
                        $service = Service::get_service($service_id);
                        if ($service) {
                            $services[] = [
                                'id' => $service_id,
                                'quantity' => 1,
                                'price' => $service->price
                            ];
                        }
                    }
                }
            }
        }
        return $services;
    }
    
    public static function process_custom_fields($post_data) {
        $custom_fields = [];
        $form_fields = self::get_form_fields();
        
        foreach ($form_fields as $field) {
            $field_id = $field['id'];
            
            $field_names_to_check = [
                $field_id,
                'custom_' . $field_id,
                $field_id . '-field',
                'custom_' . $field_id . '-field'
            ];
            
            $found_value = null;
            foreach ($field_names_to_check as $name) {
                if (isset($post_data[$name])) {
                    $found_value = $post_data[$name];
                    break;
                }
            }
            
            if ($found_value !== null) {
                if (is_array($found_value)) {
                    $sanitized_values = [];
                    foreach ($found_value as $val) {
                        $sanitized_values[] = sanitize_text_field($val);
                    }
                    $custom_fields[$field_id] = implode(', ', $sanitized_values);
                } else {
                    $custom_fields[$field_id] = sanitize_text_field($found_value);
                }
            } else {
                $custom_fields[$field_id] = '';
            }
        }
        
        return $custom_fields;
    }
    
    public static function sanitize_booking_data($post_data) {
        return [
            'name' => sanitize_text_field($post_data['name-field']),
            'email' => sanitize_email($post_data['email-field']),
            'phone' => sanitize_text_field($post_data['phone-field']),
            'start_date' => sanitize_text_field($post_data['start-date-field']),
            'end_date' => sanitize_text_field($post_data['end-date-field']),
            'total_cost' => isset($post_data['total-cost-field']) ? floatval($post_data['total-cost-field']) : 0,
            'paid_amount' => isset($post_data['actual-payment-field']) ? floatval($post_data['actual-payment-field']) : 0,
            'staff_id' => isset($post_data['staff-id-field']) ? intval($post_data['staff-id-field']) : null,
        ];
    }
    
    public static function handle_payment($post_data) {
        $rm_payment_options = get_option('rm_payment_options');
        $pay_on_arrival_enabled = isset($rm_payment_options['pay_on_arrival_enabled']) ? $rm_payment_options['pay_on_arrival_enabled'] : '0';
        $bank_transfer_enabled = isset($rm_payment_options['bank_transfer_enabled']) ? $rm_payment_options['bank_transfer_enabled'] : '0';
        
        if (isset($post_data['submit-pay-on-arrival']) && $post_data['submit-pay-on-arrival'] == 1 && $pay_on_arrival_enabled == '1') {
            return ['success' => true, 'method' => 'pay_on_arrival', 'paid_amount' => 0];
        } 
        
        if (isset($post_data['submit-bank-transfer']) && $post_data['submit-bank-transfer'] == 1 && $bank_transfer_enabled == '1') {
            return ['success' => true, 'method' => 'bank_transfer', 'paid_amount' => 0];
        }
        
        if (!empty($post_data['paypalPaymentID'])) {
            return ['success' => true, 'method' => 'paypal'];
        }
        
        if (!empty($post_data['clientSecret'])) {
            $clientSecret = sanitize_text_field($post_data['clientSecret']);
            try {
                $paymentIntentId = explode('_secret_', $clientSecret)[0];
                $paymentIntent = PaymentHelpers::retrieve_payment_intent($paymentIntentId);
                if ($paymentIntent && $paymentIntent->status === 'succeeded') {
                    return ['success' => true, 'method' => 'card_stripe'];
                }
            } catch (\Exception $e) {
                error_log('Stripe error: ' . $e->getMessage());
            }
        }

        return false;
    }
    
    public static function save_booking($booking_data, $payment_method, $services, $paid_amount, $custom_fields) {
        return Booking::save_booking_to_db(
            $booking_data['name'], 
            $booking_data['email'], 
            $booking_data['phone'], 
            $booking_data['start_date'], 
            $booking_data['end_date'], 
            $booking_data['total_cost'], 
            $payment_method, 
            $services, 
            $paid_amount, 
            $booking_data['staff_id'],
            $custom_fields
        );
    }
}