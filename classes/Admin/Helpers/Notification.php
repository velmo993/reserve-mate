<?php
namespace ReserveMate\Admin\Helpers;
use ReserveMate\Admin\Helpers\SecureCredentials;
use ReserveMate\Shared\Helpers\BookingHelpers;

defined('ABSPATH') or die('No direct access!');

class Notification {

    public static function display_booking_success_message_field() {
        $message = get_option('rm_notification_options')['booking_success_message'] ?? '';
        
        $default_message = __(
            '<p>Thank you for choosing us!</p>
            <p>We have just sent an email with the details.</strong></p>
            <p>If you do not get any email from us, please contact us!</p>',
            'reserve-mate'
        );

        wp_editor(!empty($message) ? $message : $default_message, 'booking_success_message', [
            'textarea_name' => 'rm_notification_options[booking_success_message]',
            'textarea_rows' => 5,
            'editor_class' => 'reservemate-message-editor',
            'media_buttons' => false,
            'tinymce' => [
                'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,undo,redo',
                'toolbar2' => '',
            ],
        ]); ?>
        <p class="description"><?php _e('Enter the message shown after a successful booking.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_send_email_to_clients_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $send_email_to_clients = isset($rm_notification_options['send_email_to_clients']) ? $rm_notification_options['send_email_to_clients'] : 0;
        
        echo '<input type="checkbox" name="rm_notification_options[send_email_to_clients]" value="1"' . checked(1, $send_email_to_clients, false) . '> ' . __('Send an email to the client after the booking is made.', 'reserve-mate');
    }
    
    public static function display_email_from_name_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $email_from_name = $rm_notification_options['email_from_name'] ?? get_bloginfo('name');
        
        echo '<input type="text" name="rm_notification_options[email_from_name]" value="' . esc_attr($email_from_name) . '" class="regular-text">';
        echo '<p class="description">' . __('This name will appear as the sender in booking emails.', 'reserve-mate') . '</p>';
    }
    
    public static function display_email_from_address_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $email_from_address = $rm_notification_options['email_from_address'] ?? get_option('admin_email');
        
        echo '<input type="email" name="rm_notification_options[email_from_address]" value="' . esc_attr($email_from_address) . '" class="regular-text">';
        echo '<p class="description">' . __('This email will be used as the sender email in booking emails.', 'reserve-mate') . '</p>';
        echo '<p class="description" style="color: #d63638;">' . __('Note: For best deliverability, this email should match the SMTP username.', 'reserve-mate') . '</p>';
    }
    
    public static function display_client_email_subject_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $client_email_subject = $rm_notification_options['client_email_subject'] ?? 'Booking Confirmation';
        
        echo '<input type="text" name="rm_notification_options[client_email_subject]" value="' . esc_attr($client_email_subject) . '" class="regular-text">';
    }
    
    public static function display_client_email_content_field() {
        $message = get_option('rm_notification_options')['client_email_content'] ?? '';
        $default_content = '<!DOCTYPE html>
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
                            <li><strong>Name:</strong> {name}</li>
                            <li><strong>Email:</strong> {email}</li>
                            <li><strong>Phone:</strong> {phone}</li>
                            <li><strong>Appointment:</strong> {booking_time} - {booking_time_end}</li>
                            <li><strong>Staff:</strong> {staff}</li>
                            <li><strong>Service:</strong> {service}</li>
                            <li><strong>Total Cost:</strong> {price}</li>
                            <li><strong>Paid Amount:</strong> {paid_amount}</li>
                            <li><strong>Payment Method:</strong> {payment_method}</li>
                        </ul>
                        <p>Please review the booking and if you have any questions, please contact us at phone or reply to this email.</p>
                        <p>Best regards,<br>{site_name}</p>
                    </div>
                    <div class="email-footer">
                        © 2025 {site_name}. All rights reserved.
                    </div>
                </div>
            </body>
        </html>';

        $variables = [
            '{booking_id}' => __('Booking ID', 'reserve-mate'),
            '{booking_date}' => __('Booking date', 'reserve-mate'),
            '{booking_time}' => __('Booking time', 'reserve-mate'),
            '{booking_time_end}' => __('Booking time end', 'reserve-mate'),
            '{email}' => __('Client email', 'reserve-mate'),
            '{name}' => __('Client name', 'reserve-mate'),
            '{paid_amount}' => __('Paid amount', 'reserve-mate'),
            '{payment_method}' => __('Payment method', 'reserve-mate'),
            '{phone}' => __('Client phone', 'reserve-mate'),
            '{price}' => __('Booking price', 'reserve-mate'),
            '{service}' => __('Service name', 'reserve-mate'),
            '{site_name}' => __('Website name', 'reserve-mate'),
            '{site_url}' => __('Website URL', 'reserve-mate'),
            '{staff}' => __('Selected staff member', 'reserve-mate'),
        ];
        
        $form_fields = BookingHelpers::get_form_fields();
        foreach ($form_fields as $field) {
            if (!in_array($field['id'], ['name', 'email', 'phone'])) {
                $variables['{' . $field['id'] . '}'] = $field['label'];
            }
        }
        
        ?>
        <div style="margin-bottom: 15px;">
            <h3><?php _e('Available Variables:', 'reserve-mate'); ?></h3>
            <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; display: inline-block;">
                <?php foreach ($variables as $var => $desc): ?>
                    <code title="<?php echo esc_attr($desc); ?>" style="cursor: help; margin-right: 5px;"><?php echo esc_html($var); ?></code>
                <?php endforeach; ?>
            </div>
        </div>
        <?php wp_editor(!empty($message) ? $message : $default_content, 'client_email_content', [
            'textarea_name' => 'rm_notification_options[client_email_content]',
            'textarea_rows' => 15,
            'editor_class' => 'reservemate-email-editor',
            'tinymce' => [
                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,forecolor,backcolor,undo,redo',
                'toolbar2' => '',
            ],
        ]); ?>
        <p class="description"><?php _e('Enter the message sent to the client after booking. You can use HTML and the variables above.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_smtp_settings_section() {
        echo '<p>' . __('Configure SMTP settings for sending emails.', 'reserve-mate') . '</p>';
    }
    
    public static function display_smtp_host_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $smtp_host = $rm_notification_options['smtp_host'] ?? '';
        echo '<input type="text" name="rm_notification_options[smtp_host]" value="' . esc_attr($smtp_host) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your SMTP host (e.g., smtp.gmail.com).', 'reserve-mate') . '</p>';
    }
    
    public static function display_smtp_port_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $smtp_port = $rm_notification_options['smtp_port'] ?? '587';
        echo '<input type="text" name="rm_notification_options[smtp_port]" value="' . esc_attr($smtp_port) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your SMTP port (e.g., 587 for TLS).', 'reserve-mate') . '</p>';
    }
    
    public static function display_smtp_encryption_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $smtp_encryption = $rm_notification_options['smtp_encryption'] ?? 'tls';
        echo '<select name="rm_notification_options[smtp_encryption]" class="regular-text">';
        echo '<option value="tls"' . selected('tls', $smtp_encryption, false) . '>TLS</option>';
        echo '<option value="ssl"' . selected('ssl', $smtp_encryption, false) . '>SSL</option>';
        echo '</select>';
        echo '<p class="description">' . __('Select the encryption method TLS/SSL (TLS recommended).', 'reserve-mate') . '</p>';
    }
    
    public static function display_smtp_username_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $smtp_username = $rm_notification_options['smtp_username'] ?? '';
        echo '<input type="text" name="rm_notification_options[smtp_username]" value="' . esc_attr($smtp_username) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your SMTP username (usually your email address).', 'reserve-mate') . '</p>';
    }
    
    public static function display_smtp_password_field() {
        $rm_notification_options = get_option('rm_notification_options');
        
        if (!empty($rm_notification_options['smtp_password'])) {
            $smtp_password = SecureCredentials::decrypt($rm_notification_options['smtp_password'], SecureCredentials::SMTP);
        } else {
            $smtp_password = '';
        }
        
        echo '<input type="password" name="rm_notification_options[smtp_password]" value="' . esc_attr($smtp_password) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter your SMTP password.', 'reserve-mate') . '</p>';
    }
    
    public static function display_email_test_section() {
        echo '<p>' . __('Test your email configuration by sending test emails.', 'reserve-mate') . '</p>';
    }
    
    public static function display_test_email_address_field() {
        $rm_notification_options = get_option('rm_notification_options');
        $test_email = $rm_notification_options['test_email'] ?? '';
        
        echo '<input type="email" id="test_email" name="test_email" value="' . esc_attr($test_email) . '" class="regular-text">';
        echo '<p class="description">' . __('Enter the email address where test emails will be sent.', 'reserve-mate') . '</p>';
        
        // Add test buttons
        echo '<div style="margin-top: 15px;">';
        echo '<button type="button" id="test_client_email" class="button button-secondary">' . __('Send Client test email', 'reserve-mate') . '</button>';
        echo ' <button type="button" id="test_admin_email" class="button button-secondary">' . __('Send Admin test email', 'reserve-mate') . '</button>';
        echo ' <span id="email_test_result" style="margin-left: 10px; display: inline-block;"></span>';
        echo '</div>';
    }

}