<?php
namespace ReserveMate\Admin\Helpers;
use ReserveMate\Admin\Helpers\Email;

defined('ABSPATH') or die('No direct access!');

class TestEmail {

    public static function test_client_email_callback() {
        check_ajax_referer('email_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $rm_notification_options = get_option('rm_notification_options');
        $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
        if (empty($test_email) || !is_email($test_email)) {
            wp_send_json_error('Invalid email address');
        }
        
        $site_name = get_bloginfo('name');
        $currency = get_currency();
        
        $name = 'John Doe';
        $email = $test_email;
        $phone = '+1 (555) 123-4567';
        $start_datetime = date('Y-m-d H:i', strtotime('+7 days 14:00'));
        $end_datetime = date('Y-m-d H:i', strtotime('+7 days 16:00'));
        $end_time = '16:00';
        $total_cost = '150.00';
        $paid_amount = '50.00';
        $payment_method = 'Credit Card';
        
        $services_list = [
            '<li><strong>Service:</strong> Hair Cut & Style</li>',
            '<li><strong>Duration:</strong> 2 hours</li>'
        ];
        
        $custom_fields = [
            'special_requests' => 'Please use organic products',
            'preferred_stylist' => 'Sarah Johnson',
            'parking_needed' => 'Yes'
        ];
        
        $content = isset($rm_notification_options['client_email_content']) && !empty($rm_notification_options['client_email_content'])
            ? $rm_notification_options['client_email_content']
            : Email::get_default_client_email_template($name, $email, $phone, $start_datetime, $end_time, $services_list, '', $total_cost, $paid_amount, $payment_method, $site_name);
        
        $custom_fields_content = '';
        $template_vars = [
            '{booking_id}' => 'TEST-' . rand(1000, 9999),
            '{booking_date}' => date('Y-m-d', strtotime($start_datetime)),
            '{booking_time}' => date('H:i', strtotime($start_datetime)),
            '{booking_time_end}' => $end_time,
            '{email}' => esc_html($email),
            '{name}' => esc_html($name),
            '{paid_amount}' => esc_html($paid_amount . ' ' . $currency),
            '{payment_method}' => esc_html($payment_method),
            '{phone}' => esc_html($phone),
            '{price}' => esc_html($total_cost . ' ' . $currency),
            '{service}' => implode('', array_map('wp_kses_post', $services_list)),
            '{site_name}' => esc_html($site_name),
            '{site_url}' => esc_url(home_url()),
            '{staff}' => esc_html($staff),
        ];
        
        if (!empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $field_id => $field_value) {
                if (!empty($field_value) && !in_array($field_id, ['name', 'email', 'phone'])) {
                    $pretty_name = ucwords(str_replace(['_', '-'], ' ', $field_id));
                    $template_vars['{' . $field_id . '}'] = esc_html($field_value);
                    $custom_fields_content .= '<div class="detail-item"><strong>' . esc_html($pretty_name) . ':</strong> ' . esc_html($field_value) . '</div>';
                }
            }
        }
        
        $template_vars['{custom_fields}'] = $custom_fields_content;
        
        $content = str_replace(array_keys($template_vars), array_values($template_vars), $content);
        $content = preg_replace('/\{[a-zA-Z0-9_]+?\?.*?\}/s', '', $content);
        $content = str_replace('<div class="email-header">', '<div class="email-header">[TEST] ', $content);
        
        $from_name = $rm_notification_options['email_from_name'] ?? get_bloginfo('name');
        $from_email = $rm_notification_options['email_from_address'] ?? get_option('admin_email');
        $subject = isset($rm_notification_options['client_email_subject']) && !empty($rm_notification_options['client_email_subject']) 
                   ? '[TEST] ' . $rm_notification_options['client_email_subject'] 
                   : '[TEST] Booking Confirmation';
        
        
        $content = Email::ensure_complete_html_structure($content);
        $headers = "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$from_name} <{$from_email}>\r\n";
        
        try {
            $result = wp_mail($test_email, $subject, $content, $headers);
            if (!$result) {
                global $phpmailer;
                if (isset($phpmailer->ErrorInfo)) {
                    $error = $phpmailer->ErrorInfo;
                } else {
                    $error = 'Unknown error';
                }
                wp_send_json_error('Failed to send client test email: ' . $error);
            } else {
                wp_send_json_success('Client test email sent successfully to ' . $test_email);
            }
        } catch (Exception $e) {
            wp_send_json_error('Email sending error: ' . $e->getMessage());
        }
    }
    
    public static function test_admin_email_callback() {
        check_ajax_referer('email_test_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $rm_notification_options = get_option('rm_notification_options');
        $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
        
        if (empty($test_email) || !is_email($test_email)) {
            wp_send_json_error('Invalid email address');
        }
        
        $site_name = get_bloginfo('name');
        $currency = get_currency();
        
        $name = 'Jane Smith';
        $email = 'jane.smith@example.com';
        $phone = '+1 (555) 987-6543';
        $start_datetime = date('Y-m-d H:i', strtotime('+3 days 10:00'));
        $end_datetime = date('Y-m-d H:i', strtotime('+3 days 12:30'));
        $end_time = '12:30';
        $total_cost = '200.00';
        $paid_amount = '100.00';
        $payment_method = 'Bank Transfer';
        $staff = 'John Doe';
        
        $services_list = [
            '<li><strong>Service:</strong> Deep Tissue Massage</li>',
            '<li><strong>Duration:</strong> 2.5 hours</li>'
        ];
        
        $custom_fields = [
            'medical_conditions' => 'Lower back pain',
            'preferred_pressure' => 'Medium',
            'allergies' => 'None'
        ];
        
        $content = Email::get_default_admin_email_template($name, $email, $phone, $start_datetime, $end_time, $services_list, '', $total_cost, $paid_amount, $payment_method, $site_name, $currency, $staff);
        
        $custom_fields_content = '';
        $template_vars = [
            '{name}' => esc_html($name),
            '{email}' => esc_html($email),
            '{phone}' => esc_html($phone),
            '{booking_id}' => 'TEST-' . rand(1000, 9999),
            '{booking_date}' => date('Y-m-d', strtotime($start_datetime)),
            '{booking_time}' => $end_time,
            '{start_datetime}' => esc_html($start_datetime),
            '{end_datetime}' => esc_html($end_datetime),
            '{service}' => implode('', array_map('wp_kses_post', $services_list)),
            '{price}' => esc_html($total_cost . ' ' . $currency),
            '{paid_amount}' => esc_html($paid_amount . ' ' . $currency),
            '{payment_method}' => esc_html($payment_method),
            '{site_name}' => esc_html($site_name),
            '{site_url}' => esc_url(home_url()),
            '{staff}' => esc_html($staff),
        ];
        
        if (!empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $field_id => $field_value) {
                if (!empty($field_value) && !in_array($field_id, ['name', 'email', 'phone'])) {
                    $pretty_name = ucwords(str_replace(['_', '-'], ' ', $field_id));
                    $template_vars['{' . $field_id . '}'] = esc_html($field_value);
                    $custom_fields_content .= '<div class="detail-item"><strong>' . esc_html($pretty_name) . ':</strong> ' . esc_html($field_value) . '</div>';
                }
            }
        }
        
        $template_vars['{custom_fields}'] = $custom_fields_content;
        
        $content = str_replace(array_keys($template_vars), array_values($template_vars), $content);
        $content = preg_replace('/\{[a-zA-Z0-9_]+?\?.*?\}/s', '', $content);
        $content = str_replace('<div class="email-header">', '<div class="email-header">[TEST] ', $content);
        
        $from_name = $rm_notification_options['email_from_name'] ?? get_bloginfo('name');
        $from_email = $rm_notification_options['email_from_address'] ?? get_option('admin_email');
        $subject = '[TEST] New Booking Received - ' . $site_name;
        
        $headers = "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$from_name} <{$from_email}>\r\n";
        
        try {
            $result = wp_mail($test_email, $subject, $content, $headers);
            if (!$result) {
                global $phpmailer;
                if (isset($phpmailer->ErrorInfo)) {
                    $error = $phpmailer->ErrorInfo;
                } else {
                    $error = 'Unknown error';
                }
                wp_send_json_error('Failed to send admin test email: ' . $error);
            } else {
                wp_send_json_success('Admin test email sent successfully to ' . $test_email);
            }
        } catch (Exception $e) {
            wp_send_json_error('Email sending error: ' . $e->getMessage());
        }
    }
    
}