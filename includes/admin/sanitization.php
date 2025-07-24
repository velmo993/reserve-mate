<?php 
use ReserveMate\Admin\Helpers\SecureCredentials;

defined('ABSPATH') or die('No direct access!');

function sanitize_style_options($input) {
    $existing_options = get_option('rm_style_options', array());
    $sanitized = $existing_options;
    
    if (isset($input['calendar_theme'])) {
        $sanitized['calendar_theme'] = sanitize_text_field($input['calendar_theme']);
    }

    if (isset($input['font_family'])) {
        $sanitized['font_family'] = sanitize_text_field($input['font_family']);
    }
    
    $defaults = [
        'primary_color' => '#4CAF50',
        'text_color' => '#333',
        'day_bg_color' => '#fff',
        'day_border_color' => '#d2caca',
        'disabled_day_bg' => 'rgba(236,13,13,0.28)',
        'disabled_day_color' => '#676666',
        'prev_next_month_color' => '#9c9c9c',
        'prev_next_month_border' => '#e1e1e1',
        'arrival_bg' => 'linear-gradient(to left, #fff 50%, rgb(250 188 188) 50%)',
        'departure_bg' => 'linear-gradient(to right, #fff 50%, rgb(250 188 188) 50%)',
        'start_range_highlight' => '#07c66594',
        'range_highlight' => '#07c66594',
        'end_range_highlight' => '#07c66594',
        'day_hover_outline' => '#000',
        'today_border_color' => '#959ea9',
        'nav_hover_color' => '#4CAF50',
        'week_number_color' => '#333',
        'calendar_bg' => '#fff',
        'day_selected' => '#07c66594',
        'day_selected_text' => '#fff',
        'range_text_color' => '#fff',
    ];

    $color_fields = array_keys($defaults);
    
    foreach ($color_fields as $field) {
        if (isset($input[$field])) {
            if (preg_match('/^#([a-f0-9]{3}){1,2}$/i', $input[$field]) || 
                preg_match('/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/i', $input[$field]) ||
                preg_match('/^[a-z]+$/i', $input[$field]) ||
                strpos($input[$field], 'gradient') !== false) {
                $sanitized[$field] = sanitize_text_field($input[$field]);
            } else {
                $sanitized[$field] = $defaults[$field];
            }
        }
    }
    
    return $sanitized;
}


function sanitize_form_options($input) {
    $existing_options = get_option('rm_form_options', array());
    $sanitized = $existing_options;
 
    if (array_key_exists('form_fields', $input)) {
        if (is_array($input['form_fields'])) {

            $sanitized_form_fields = [];
            $order = 1;
            
            foreach ($input['form_fields'] as $index => $field) {

                if (!isset($field['id']) || !isset($field['label']) || !isset($field['type'])) {
                    continue;
                }
                
                if (empty(trim($field['id'])) || empty(trim($field['label']))) {
                    continue;
                }
                
                $sanitized_field = [
                    'id' => sanitize_key($field['id']),
                    'label' => sanitize_text_field($field['label']),
                    'type' => sanitize_text_field($field['type']),
                    'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
                    'required' => isset($field['required']) ? (bool)$field['required'] : false,
                    'order' => isset($field['order']) ? absint($field['order']) : $order,
                ];
                
                if (in_array($field['type'], ['select', 'checkbox', 'radio']) && isset($field['options'])) {
                    $sanitized_field['options'] = sanitize_textarea_field($field['options']);
                }
                
                $sanitized_form_fields[] = $sanitized_field;
                $order++;
            }
            

            usort($sanitized_form_fields, function($a, $b) {
                return $a['order'] - $b['order'];
            });
            
            $sanitized['form_fields'] = $sanitized_form_fields;
        } else {
            $sanitized['form_fields'] = [];
        }
    } else {
        error_log('form_fields key does not exist in input');
    }
    
    return $sanitized;   
}
    
function sanitize_service_options($input) {
    $existing_options = get_option('rm_service_options', array());
    $sanitized = $existing_options;
    
    if (isset($input['max_selectable_services'])) {
        $sanitized['max_selectable_services'] = max(1, absint($input['max_selectable_services']));
    }

    return $sanitized;
}

function sanitize_google_calendar_options($input) {
    $existing_options = get_option('rm_google_calendar_options', array());
    
    $protected_fields = array(
        'google_access_token',
        'google_refresh_token', 
        'google_token_expires'
    );
    
    $sanitized = $existing_options;
    
    foreach ($input as $key => $value) {
        if (!in_array($key, $protected_fields)) {
            if ($key === 'sync_enabled') {
                $sanitized[$key] = !empty($value);
            } elseif ($key === 'google_client_secret' && !empty($value)) {
                // ENCRYPT CLIENT SECRET
                $sanitized[$key] = SecureCredentials::encrypt($value, SecureCredentials::GOOGLE);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
    }
    
    return $sanitized;
}

function sanitize_general_options($input) {
    $existing_options = get_option('rm_general_options', array());
    $sanitized = $existing_options;
    
    if (isset($input['currency'])) {
        $sanitized['currency'] = sanitize_text_field($input['currency']);
    }
    
    if (isset($input['calendar_timezones'])) {
        $sanitized['calendar_timezones'] = sanitize_text_field($input['calendar_timezones']);
    }
    
    if (isset($input['date_format'])) {
        $sanitized['date_format'] = sanitize_text_field($input['date_format']);
    }
    
    if (isset($input['calendar_locale'])) {
        $sanitized['calendar_locale'] = sanitize_text_field($input['calendar_locale']);
    }

    return $sanitized;   
}

function sanitize_booking_options($input) {
    $existing_options = get_option('rm_booking_options', array());
    $sanitized = $existing_options;

    if (isset($input['checkin_time'])) {
        $sanitized['checkin_time'] = sanitize_text_field($input['checkin_time']);
    }

    if (isset($input['checkout_time'])) {
        $sanitized['checkout_time'] = sanitize_text_field($input['checkout_time']);
    }

    if (array_key_exists('auto_delete_booking_enabled', $input)) {
        $sanitized['auto_delete_booking_enabled'] = isset($input['auto_delete_booking_enabled']) ? 1 : 0;
    }

    if (isset($input['delete_after_days']) && isset($sanitized['auto_delete_booking_enabled']) && $sanitized['auto_delete_booking_enabled'] == 1) {
        $sanitized['delete_after_days'] = absint($input['delete_after_days']);
    }
    
    if (isset($input['enable_booking_approval'])) {
        $sanitized['enable_booking_approval'] = in_array($input['enable_booking_approval'], ['yes', 'no']) ? $input['enable_booking_approval'] : 'no';
    }
    
    if (isset($input['booking_min_time'])) {
        $sanitized['booking_min_time'] = sanitize_text_field($input['booking_min_time']);
    }
    
    if (isset($input['booking_max_time'])) {
        $sanitized['booking_max_time'] = sanitize_text_field($input['booking_max_time']);
    }
    
    if (isset($input['booking_interval'])) {
        $sanitized['booking_interval'] = absint($input['booking_interval']);
    }
    
    if (isset($input['buffer_time'])) {
        $sanitized['buffer_time'] = absint($input['buffer_time']);
    }
    
    if (isset($input['minimum_lead_time'])) {
        $sanitized['minimum_lead_time'] = absint($input['minimum_lead_time']);
    }
    
    if (isset($input['booking_min_date'])) {
        $sanitized['booking_min_date'] = sanitize_text_field($input['booking_min_date']);
    }
    
    if (isset($input['booking_max_date'])) {
        $sanitized['booking_max_date'] = sanitize_text_field($input['booking_max_date']);
    }
    
    if (isset($input['booking_max_days_ahead'])) {
        $sanitized['booking_max_days_ahead'] = absint($input['booking_max_days_ahead']);
    }
    
    if (isset($input['booking_mode'])) {
        $sanitized['booking_mode'] = in_array($input['booking_mode'], ['fixed', 'rolling']) ? $input['booking_mode'] : 'fixed';
    }

    if (isset($input['calendar_display_type'])) {
        $sanitized['calendar_display_type'] = sanitize_text_field($input['calendar_display_type']);
    }
    
    if (isset($input['time_display_format'])) {
        $sanitized['time_display_format'] = sanitize_text_field($input['time_display_format']);
    }
    
    if (isset($input['limits'])) {
        $valid_periods = ['day', 'week', 'month'];
        $sanitized['limits'] = isset($sanitized['limits']) ? $sanitized['limits'] : [];
        
        foreach ($valid_periods as $period) {
            if (isset($input['limits'][$period])) {
                $sanitized['limits'][$period]['max'] = absint($input['limits'][$period]['max']);
                $sanitized['limits'][$period]['enabled'] = isset($input['limits'][$period]['enabled']);
            }
        }
    }
    
    if (array_key_exists('disabled_dates', $input)) {
        if (is_array($input['disabled_dates'])) {
            $sanitized_disabled_dates = [];
            
            foreach ($input['disabled_dates'] as $index => $rule) {
                $sanitized_rule = [
                    'type' => isset($rule['type']) ? sanitize_text_field($rule['type']) : 'specific',
                    'repeat_yearly' => isset($rule['repeat_yearly']) ? (bool)$rule['repeat_yearly'] : false,
                ];
                
                switch ($sanitized_rule['type']) {
                    case 'specific':
                        $sanitized_rule['date'] = isset($rule['date']) ? sanitize_text_field($rule['date']) : '';
                        break;
                        
                    case 'range':
                        $sanitized_rule['start_date'] = isset($rule['start_date']) ? sanitize_text_field($rule['start_date']) : '';
                        $sanitized_rule['end_date'] = isset($rule['end_date']) ? sanitize_text_field($rule['end_date']) : '';
                        break;
                        
                    case 'weekly':
                        $sanitized_rule['days'] = isset($rule['days']) ? array_map('sanitize_text_field', (array)$rule['days']) : [];
                        break;
                        
                    case 'time':
                        $sanitized_rule['date'] = isset($rule['date']) ? sanitize_text_field($rule['date']) : '';
                        $sanitized_rule['start_time'] = isset($rule['start_time']) ? sanitize_text_field($rule['start_time']) : '';
                        $sanitized_rule['end_time'] = isset($rule['end_time']) ? sanitize_text_field($rule['end_time']) : '';
                        $sanitized_rule['days'] = isset($rule['days']) ? array_map('sanitize_text_field', (array)$rule['days']) : [];
                        break;
                }
                
                $sanitized_disabled_dates[$index] = $sanitized_rule;
            }
            
            $sanitized['disabled_dates'] = $sanitized_disabled_dates;
        } else {
            $sanitized['disabled_dates'] = [];
        }
    }

    return $sanitized;
}

function sanitize_rm_notification_options($input) {
    $sanitized = array();
    
    if (isset($input['booking_success_message'])) {
        $sanitized['booking_success_message'] = wp_kses_post($input['booking_success_message']);
    }

    if (isset($input['client_email_content'])) {
        $sanitized['client_email_content'] = sanitize_html_template($input['client_email_content']);
    }

    if (isset($input['client_email_subject'])) {
        $sanitized['client_email_subject'] = sanitize_text_field($input['client_email_subject']);
    }
    
    if (isset($input['email_from_name'])) {
        $sanitized['email_from_name'] = sanitize_text_field($input['email_from_name']);
    }

    if (isset($input['email_from_address']) && is_email($input['email_from_address'])) {
        $sanitized['email_from_address'] = sanitize_email($input['email_from_address']);
    }
    
    $sanitized['send_email_to_clients'] = isset($input['send_email_to_clients']) ? 1 : 0;
    
    if (isset($input['smtp_host'])) {
        $sanitized['smtp_host'] = sanitize_text_field($input['smtp_host']);
    }

    if (isset($input['smtp_port'])) {
        $sanitized['smtp_port'] = absint($input['smtp_port']);
    }

    if (isset($input['smtp_encryption'])) {
        $sanitized['smtp_encryption'] = in_array($input['smtp_encryption'], ['tls', 'ssl']) ? $input['smtp_encryption'] : 'tls';
    }

    if (isset($input['smtp_username'])) {
        $sanitized['smtp_username'] = sanitize_text_field($input['smtp_username']);
    }
    
    if (isset($input['smtp_password']) && !empty($input['smtp_password'])) {
        $sanitized['smtp_password'] = SecureCredentials::encrypt($input['smtp_password'], SecureCredentials::SMTP);
    }
    
    if (isset($input['test_email']) && is_email($input['test_email'])) {
        $sanitized['test_email'] = sanitize_email($input['test_email']);
    }

    return $sanitized;
}

function sanitize_payment_options($input) {
    $sanitized_input = [];
    
    // Stripe
    $sanitized_input['stripe_enabled'] = isset($input['stripe_enabled']) ? 1 : 0;
    $stripe_has_credentials = isset($input['stripe_secret_key']) && !empty($input['stripe_secret_key']) && 
                             isset($input['stripe_public_key']) && !empty($input['stripe_public_key']);
    $sanitized_input['stripe_enabled'] = (isset($input['stripe_enabled']) && $stripe_has_credentials) ? 1 : 0;
    if (isset($input['stripe_secret_key']) && !empty($input['stripe_secret_key'])) {
        $sanitized_input['stripe_secret_key'] = SecureCredentials::encrypt($input['stripe_secret_key'], SecureCredentials::STRIPE);
    }
    $sanitized_input['stripe_public_key'] = sanitize_text_field($input['stripe_public_key']);

    // Paypal
    $paypal_has_credentials = isset($input['paypal_client_id']) && !empty($input['paypal_client_id']);
    $sanitized_input['paypal_enabled'] = (isset($input['paypal_enabled']) && $paypal_has_credentials) ? 1 : 0;
    if (isset($input['paypal_client_id']) && !empty($input['paypal_client_id'])) {
        $sanitized_input['paypal_client_id'] = SecureCredentials::encrypt($input['paypal_client_id'], SecureCredentials::PAYPAL);
    }

    // Apple pay
    // $sanitized_input['apple_pay_enabled'] = isset($input['apple_pay_enabled']) ? 1 : 0;
    // $sanitized_input['apple_pay_merchant_id'] = sanitize_text_field($input['apple_pay_merchant_id']);
    // $sanitized_input['apple_pay_cert_path'] = sanitize_text_field($input['apple_pay_cert_path']);
    // $sanitized_input['apple_pay_key_path'] = sanitize_text_field($input['apple_pay_key_path']);
    
    // Deposit payment
    $sanitized_input['deposit_payment_type'] = sanitize_text_field($input['deposit_payment_type']);
    $sanitized_input['deposit_payment_percentage'] = isset($input['deposit_payment_percentage']) ? floatval($input['deposit_payment_percentage']) : 0;
    $sanitized_input['deposit_payment_fixed_amount'] = isset($input['deposit_payment_fixed_amount']) ? floatval($input['deposit_payment_fixed_amount']) : 0;
    if (isset($input['deposit_payment_methods']) && is_array($input['deposit_payment_methods'])) {
        foreach ($input['deposit_payment_methods'] as $method_key => $enabled) {
            $sanitized_input['deposit_payment_methods'][$method_key] = isset($enabled) ? 1 : 0;
        }
    } else {
        $sanitized_input['deposit_payment_methods'] = [];
    }
    
    $sanitized_input['pay_on_arrival_enabled'] = isset($input['pay_on_arrival_enabled']) ? 1 : 0;
    
    // Bank transfer
    $sanitized_input['bank_transfer_enabled'] = isset($input['bank_transfer_enabled']) ? 1 : 0;
    $sanitized_input['bank_account_number'] = sanitize_text_field($input['bank_account_number']);
    $sanitized_input['bank_account_identifier'] = sanitize_text_field($input['bank_account_identifier']);
    $sanitized_input['bank_swift_bic'] = sanitize_text_field($input['bank_swift_bic']);
    $sanitized_input['bank_name'] = sanitize_text_field($input['bank_name']);
    $sanitized_input['bank_recipient_name'] = sanitize_text_field($input['bank_recipient_name']);
    $sanitized_input['bank_additional_info'] = sanitize_textarea_field($input['bank_additional_info']);

    return $sanitized_input;
}


function fix_json($raw_json) {
    $decoded = json_decode($raw_json, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_PRETTY_PRINT);
    } else {
        $fixed_json = htmlspecialchars_decode($raw_json);
        $decoded = json_decode($fixed_json, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_PRETTY_PRINT);
        } else {
            error_log('Invalid JSON: ' . json_last_error_msg());
            return false;
        }
    }
}

function sanitize_html_template($content) {
    $patterns = [
        '/<script[^>]*>.*?<\/script>/is',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/<object[^>]*>.*?<\/object>/is',
        '/<embed[^>]*>.*?<\/embed>/is',
        '/<form[^>]*>.*?<\/form>/is',
        '/on\w+\s*=/i', // Remove event handlers
        '/javascript:/i',
    ];
    
    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Basic HTML entity encoding for user input only (not the entire template)
    // This will be handled when replacing variables
    
    return $content;
}