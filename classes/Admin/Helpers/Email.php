<?php
namespace ReserveMate\Admin\Helpers;
use ReserveMate\Admin\Helpers\Payment;
use DateTime;

defined('ABSPATH') or die('No direct access!');

class Email {
    public static function ensure_complete_html_structure($content) {
        // Check if content already has proper HTML structure
        if (preg_match('/<!DOCTYPE html>/i', $content) && 
            preg_match('/<html[^>]*>/i', $content) && 
            preg_match('/<\/html>/i', $content)) {
            return $content;
        }
        
        // If missing structure, wrap the content properly
        if (!preg_match('/<!DOCTYPE html>/i', $content)) {
            $content = '<!DOCTYPE html>' . $content;
        }
        
        if (!preg_match('/<html[^>]*>/i', $content)) {
            $content = '<html>' . $content . '</html>';
        }
        
        return $content;
    }
    
    public static function send_booking_email_to_client($services_list, $name, $email, $phone, $start_datetime, $end_datetime, $total_cost, $payment_method, $paid_amount, $custom_fields = [], $staff = '') {
        $rm_notification_options = get_option('rm_notification_options');
        $sender_name = $rm_notification_options['email_from_name'] ?? get_bloginfo('name');
        $sender_email = $rm_notification_options['email_from_address'] ?? get_option('admin_email');
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $currency = get_currency();
        $date = new DateTime($end_datetime);
        $end_time = $date->format('H:i');
        $booking_date = $date->format('Y-m-d');
        $date = new DateTime($start_datetime);
        $start_time = $date->format('H:i');
    
        if (!isset($rm_notification_options['send_email_to_clients']) || intval($rm_notification_options['send_email_to_clients']) !== intval(1)) {
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format: $email");
            return;
        }
    
        $subject = isset($rm_notification_options['client_email_subject']) && !empty($rm_notification_options['client_email_subject']) 
            ? $rm_notification_options['client_email_subject'] 
            : 'Booking Confirmation - Automated Email';
    
        // Process custom fields - only include non-empty values
        $custom_fields_content = '';
        if (!empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $field_id => $field_value) {
                if (!empty($field_value) && !in_array($field_id, ['name', 'email', 'phone'])) {
                    $pretty_name = ucwords(str_replace(['_', '-'], ' ', $field_id));
                    $custom_fields_content .= '<p><strong>' . esc_html($pretty_name) . ':</strong> ' . esc_html($field_value) . '</p>';
                }
            }
        }
    
        $content = isset($rm_notification_options['client_email_content']) && !empty($rm_notification_options['client_email_content'])
            ? $rm_notification_options['client_email_content']
            : self::get_default_client_email_template($name, $email, $phone, $start_datetime, $end_time, $services_list, $custom_fields_content, $total_cost, $paid_amount, $payment_method, $site_name, $staff);
    
        // Replace template variables
        $template_vars = [
            '{booking_id}' => !empty($booking_id) ? esc_html($booking_id) : 'N/A',
            '{booking_date}' => esc_html($booking_date),
            '{booking_time}' => esc_html($start_time),
            '{booking_time_end}' => esc_html($end_time),
            '{email}' => esc_html($email),
            '{name}' => esc_html($name),
            '{paid_amount}' => esc_html($paid_amount . ' ' . $currency),
            '{payment_method}' => esc_html($payment_method),
            '{phone}' => esc_html($phone),
            '{price}' => esc_html($total_cost . ' ' . $currency),
            '{service}' => is_array($services_list) ? implode('', array_map('wp_kses_post', $services_list)) : wp_kses_post($services_list),
            '{site_name}' => esc_html($site_name),
            '{site_url}' => esc_url($site_url),
            '{staff}' => esc_html($staff),
        ];
        
        // Process custom fields - dynamically handle any field names
        $custom_fields_content = '';
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
        
        if ($payment_method === 'bank_transfer') {
            $rm_payment_options = get_option('rm_payment_options');
            
            $remaining_balance = $total_cost - $paid_amount;
            $is_deposit = $remaining_balance > 0;
    
            $deposit_amount = Payment::calculate_deposit_amount($rm_payment_options, $total_cost);
            
            $bank_details = '<div style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">';
            $bank_details .= '<h3 style="margin-top: 0;">' . __('Payment Instructions', 'reserve-mate') . '</h3>';
            
            $bank_details .= '<p><strong>' . __('Total Cost:', 'reserve-mate') . '</strong> ' . esc_html($total_cost . ' ' . $currency) . '</p>';
            
            if ($is_deposit) {
                $remaining_balance = $total_cost - $deposit_amount;
                $bank_details .= '<p><strong>' . __('Deposit Amount to Pay Now:', 'reserve-mate') . '</strong> ' . esc_html($deposit_amount . ' ' . $currency) . '</p>';
                $bank_details .= '<p><strong>' . __('Remaining Balance:', 'reserve-mate') . '</strong> ' . esc_html($remaining_balance . ' ' . $currency) . '</p>';
            } else {
                $bank_details .= '<p><strong>' . __('Full Amount to Pay:', 'reserve-mate') . '</strong> ' . esc_html($total_cost . ' ' . $currency) . '</p>';
            }
            
            $bank_details .= '<h4 style="margin-bottom: 5px;">' . __('Bank Transfer Details', 'reserve-mate') . '</h4>';
            $bank_details .= '<p><strong>' . __('Recipient Name:', 'reserve-mate') . '</strong> ' . esc_html($rm_payment_options['bank_recipient_name'] ?? '') . '</p>';
            $bank_details .= '<p><strong>' . __('Bank Name:', 'reserve-mate') . '</strong> ' . esc_html($rm_payment_options['bank_name'] ?? '') . '</p>';
            $bank_details .= '<p><strong>' . __('Account Number:', 'reserve-mate') . '</strong> ' . esc_html($rm_payment_options['bank_account_number'] ?? '') . '</p>';
            
            if (!empty($rm_payment_options['bank_account_identifier'])) {
                $bank_details .= '<p><strong>' . __('IBAN/Routing Number:', 'reserve-mate') . '</strong> ' . esc_html($rm_payment_options['bank_account_identifier']) . '</p>';
            }
            
            if (!empty($rm_payment_options['bank_swift_bic'])) {
                $bank_details .= '<p><strong>' . __('SWIFT/BIC:', 'reserve-mate') . '</strong> ' . esc_html($rm_payment_options['bank_swift_bic']) . '</p>';
            }
            
            if (!empty($rm_payment_options['bank_additional_info'])) {
                $bank_details .= '<p><strong>' . __('Payment Reference:', 'reserve-mate') . '</strong> ' . esc_html($rm_payment_options['bank_additional_info']) . '</p>';
            }
            
            $bank_details .= '</div>';
            
            if (strpos($content, '<div class="email-body">') !== false) {
                $pos = strpos($content, '<div class="email-body">');
                $end_pos = strpos($content, '</div>', $pos);
                
                if ($end_pos !== false) {
                    $content = substr_replace($content, $bank_details, $end_pos, 0);
                } else {
                    $content .= $bank_details;
                }
            } elseif (strpos($content, '</body>') !== false) {
                $content = str_replace('</body>', $bank_details . '</body>', $content);
            } else {
                $content .= $bank_details;
            }
        }
        
        $content = self::ensure_complete_html_structure($content);
        
        $headers = "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $sender_name <$sender_email>\r\n";
    
        try {
            $email_sent = wp_mail($email, $subject, $content, $headers);
            if (!$email_sent) {
                $response = 'Email failed to send to client';
            } else {
                $response = 'Email successfully sent to client';
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            $response = 'Email sending error: ' . $e->getMessage();
        }
            
        return $response;
    }
    
    public static function send_booking_email_to_admin($services_list, $name, $email, $phone, $start_datetime, $end_datetime, $total_cost, $payment_method, $paid_amount, $custom_fields = [], $staff = '') {
        $rm_notification_options = get_option('rm_notification_options');
        $sender_name = $rm_notification_options['email_from_name'] ?? get_bloginfo('name');
        $sender_email = $rm_notification_options['email_from_address'] ?? get_option('admin_email');
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $currency = get_currency();
        $date = new DateTime($end_datetime);
        $end_time = $date->format('H:i');
        $booking_date = $date->format('Y-m-d');
        $subject = 'New Booking Received - ' . $site_name;
    
        // Process custom fields - only include non-empty values
        $custom_fields_content = '';
        if (!empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $field_id => $field_value) {
                if (!empty($field_value) && !in_array($field_id, ['name', 'email', 'phone'])) {
                    $pretty_name = ucwords(str_replace(['_', '-'], ' ', $field_id));
                    $custom_fields_content .= '<p><strong>' . esc_html($pretty_name) . ':</strong> ' . esc_html($field_value) . '</p>';
                }
            }
        }
    
        $content = self::get_default_admin_email_template($name, $email, $phone, $start_datetime, $end_time, $services_list, $custom_fields_content, $total_cost, $paid_amount, $payment_method, $site_name, $currency, $staff);
    
        // Replace template variables
        $template_vars = [
            '{name}' => esc_html($name),
            '{email}' => esc_html($email),
            '{phone}' => esc_html($phone),
            '{booking_id}' => !empty($booking_id) ? esc_html($booking_id) : 'N/A',
            '{booking_date}' => esc_html($booking_date),
            '{booking_time}' => esc_html($end_time),
            '{start_datetime}' => esc_html($start_datetime),
            '{end_datetime}' => esc_html($end_datetime),
            '{service}' => is_array($services_list) ? implode('', array_map('wp_kses_post', $services_list)) : wp_kses_post($services_list),
            '{price}' => esc_html($total_cost . ' ' . $currency),
            '{paid_amount}' => esc_html($paid_amount . ' ' . $currency),
            '{payment_method}' => esc_html($payment_method),
            '{site_name}' => esc_html($site_name),
            '{site_url}' => esc_url($site_url),
            '{staff}' => esc_html($staff),
        ];
        
        // Process custom fields - dynamically handle any field names
        $custom_fields_content = '';
        if (!empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $field_id => $field_value) {
                if (!empty($field_value) && !in_array($field_id, ['name', 'email', 'phone'])) {
                    $pretty_name = ucwords(str_replace(['_', '-'], ' ', $field_id));
                    $template_vars['{' . $field_id . '}'] = esc_html($field_value);
                    $custom_fields_content .= '<div class="detail-item"><strong>' . esc_html($pretty_name) . ':</strong> ' . esc_html($field_value) . '</div>';
                }
            }
        }
        
        // Add custom fields section
        $template_vars['{custom_fields}'] = $custom_fields_content;
        
        // Replace variables in template
        $content = str_replace(array_keys($template_vars), array_values($template_vars), $content);
        
        // Remove conditional sections for any undefined custom fields
        $content = preg_replace('/\{[a-zA-Z0-9_]+?\?.*?\}/s', '', $content);
    
        $headers = "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $sender_name <$sender_email>\r\n";
    
        try {
            $email_sent = wp_mail($admin_email, $subject, $content, $headers);
            if (!$email_sent) {
                $response = 'Email failed to send to admin';
            } else {
                $response = 'Email successfully sent to admin';
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            $response = 'Email sending error: ' . $e->getMessage();
        }
    
        return $response;
    }
    
    public static function get_default_client_email_template($name, $email, $phone, $start_datetime, $end_time, $services_list, $custom_fields, $total_cost, $paid_amount, $payment_method, $site_name, $staff) {
        return '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f9f9f9;
                    margin: 0;
                    padding: 0;
                    color: #333;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border: 1px solid #dddddd;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                }
                .email-header {
                    background-color: #007BFF;
                    color: #ffffff;
                    padding: 20px;
                    text-align: center;
                    font-size: 24px;
                }
                .email-body {
                    padding: 20px;
                }
                .email-body h2 {
                    font-size: 20px;
                    margin-bottom: 10px;
                }
                .email-body p {
                    font-size: 16px;
                    margin-bottom: 15px;
                }
                .email-footer {
                    background-color: #f1f1f1;
                    padding: 10px;
                    text-align: center;
                    font-size: 14px;
                    color: #777;
                }
                ul li {
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <p>Dear {name},</p>
                    <p>Thank you for booking with {site_name}!</p>
                </div>
                <div class="email-body">
                    <p>Below are the details of your booking:</p>
                    <ul>
                        <li><strong>Name:</strong> ' . esc_html($name) . '</li>
                        <li><strong>Email:</strong> ' . esc_html($email) . '</li>
                        <li><strong>Phone:</strong> ' . esc_html($phone) . '</li>
                        <li><strong>Appointment:</strong> ' . esc_html($start_datetime) .' - '. esc_html($end_time) . '</li>
                        <li><strong>Staff:</strong> ' . esc_html($staff) . '</li>
                        ' . (is_array($services_list) ? implode('', array_map('wp_kses_post', $services_list)) : wp_kses_post($services_list)) . '
                        <li><strong>Total Cost:</strong> ' . esc_html($total_cost . ' ' . $currency) . '</li>
                        <li><strong>Paid Amount:</strong> ' . esc_html($paid_amount . ' ' . $currency) . '</li>
                        <li><strong>Payment Method:</strong> ' . esc_html($payment_method) . '</li>
                    </ul>
                    <p>Please review the booking and if you have any questions, please contact us at phone or reply to this email.</p>
                    <p>Best regards,<br>' . esc_html($site_name) . '</p>
                </div>
                <div class="email-footer">
                    © 2025 ' . esc_html($site_name) . '. All rights reserved.
                </div>
            </div>
        </body>
        </html>';
    }
    
    public static function get_default_admin_email_template($name, $email, $phone, $start_datetime, $end_time, $services_list, $custom_fields, $total_cost, $paid_amount, $payment_method, $site_name, $currency, $staff) {
        return '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f9f9f9;
                    margin: 0;
                    padding: 0;
                    color: #333;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border: 1px solid #dddddd;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                }
                .email-header {
                    background-color: #007BFF;
                    color: #ffffff;
                    padding: 20px;
                    text-align: center;
                    font-size: 24px;
                }
                .email-body {
                    padding: 20px;
                }
                .email-body h2 {
                    font-size: 20px;
                    margin-bottom: 10px;
                }
                .email-body p {
                    font-size: 16px;
                    margin-bottom: 15px;
                }
                .email-footer {
                    background-color: #f1f1f1;
                    padding: 10px;
                    text-align: center;
                    font-size: 14px;
                    color: #777;
                }
                ul li {
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    New Booking Received
                </div>
                <div class="email-body">
                    <h2>Hello Admin,</h2>
                    <p>A new booking has been made on ' . $site_name . '. Below are the details:</p>
                    <ul>
                        <li><strong>Name:</strong> ' . esc_html($name) . '</li>
                        <li><strong>Email:</strong> ' . esc_html($email) . '</li>
                        <li><strong>Phone:</strong> ' . esc_html($phone) . '</li>
                        <li><strong>Appointment:</strong> ' . esc_html($start_datetime) .' - '. esc_html($end_time) . '</li>
                        <li><strong>Staff:</strong> ' . esc_html($staff) . '</li>
                        ' . (is_array($services_list) ? implode('', array_map('wp_kses_post', $services_list)) : wp_kses_post($services_list)) . '
                        <li><strong>Total Cost:</strong> ' . esc_html($total_cost . ' ' . $currency) . '</li>
                        <li><strong>Paid Amount:</strong> ' . esc_html($paid_amount . ' ' . $currency) . '</li>
                        <li><strong>Payment Method:</strong> ' . esc_html($payment_method) . '</li>
                    </ul>
                    <p>Please review the booking and take necessary actions.</p>
                    <p>Best regards,<br>' . esc_html($site_name) . '</p>
                </div>
                <div class="email-footer">
                    © 2025 ' . esc_html($site_name) . '. All rights reserved.
                </div>
            </div>
        </body>
        </html>';
    }
    
}