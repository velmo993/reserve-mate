<?php

function test_client_email_callback() {
    check_ajax_referer('email_test_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $notification_settings = get_option('notification_settings');
    $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
    if (empty($test_email) || !is_email($test_email)) {
        wp_send_json_error('Invalid email address');
    }
    
    // Sample booking data for the test
    $booking_data = array(
        'name' => 'Test User',
        'email' => $test_email,
        'property_name' => 'Sample Property',
        'check_in' => date('Y-m-d', strtotime('+7 days')),
        'check_out' => date('Y-m-d', strtotime('+14 days')),
        'adults' => 2,
        'children' => 1,
        'total_cost' => '1000.00',
        'currency' => get_currency()
    );
    
    // Get subject from settings or use default
    $subject = $notification_settings['client_email_subject'] ?? 'Booking Confirmation';
    
    // Check if client email content exists in settings
    $content = isset($notification_settings['client_email_content']) && !empty($notification_settings['client_email_content']) 
               ? $notification_settings['client_email_content'] 
               : create_default_client_email_template();
    
    $from_name = $notification_settings['email_from_name'] ?? get_bloginfo('name');
    $from_email = $notification_settings['email_from_address'] ?? get_option('admin_email');
    
    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    
    try {
        $result = wp_mail($test_email, $subject, $content, $headers);
        if (!$result) {
            $response = 'Email failed to send to client';
        } else {
            $response = 'Email successfully sent to client';
        }
    } catch (Exception $e) {
        $response = 'Email sending error: ' . $e->getMessage();
    }
    
    if ($result) {
        wp_send_json_success('Client test email sent successfully to ' . $test_email);
    } else {
        // Get more information about why it failed
        global $phpmailer;
        if (isset($phpmailer->ErrorInfo)) {
            $error = $phpmailer->ErrorInfo;
        } else {
            $error = 'Unknown error';
        }
        wp_send_json_error('Failed to send client test email: ' . $error);
    }
}

// Function to create a default client email template if none exists
function create_default_client_email_template() {
    
    return '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            .email-footer {
                background-color: #f1f1f1;
                padding: 10px;
                text-align: center;
                font-size: 14px;
                color: #777;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                [TEST] Booking Confirmation
            </div>
            <div class="email-body">
                <h2>Dear John Doe,</h2>
                <p>Thank you for your booking at Test. This is a <strong>TEST EMAIL</strong>.</p>
                
                <p>Your booking details would appear as follows:</p>
                <ul>
                    <li><strong>Check-in date:</strong> 2025-03-30</li>
                    <li><strong>Check-out date:</strong> 2025-04-12</li>
                    <li><strong>Number of adults:</strong> 2</li>
                    <li><strong>Number of children:</strong> 1</li>
                    <li><strong>Total price:</strong> 1540 $</li>
                </ul>
                
                <p>This is a test email sent from your Reserve-mate notification settings page.</p>
                
                <p>Best regards</p>
            </div>
            <div class="email-footer">
                © All rights reserved.
            </div>
        </div>
    </body>
    </html>';
}

add_action('wp_ajax_test_client_email', 'test_client_email_callback');

function test_admin_email_callback() {
    check_ajax_referer('email_test_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $notification_settings = get_option('notification_settings');
    $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
    
    if (empty($test_email) || !is_email($test_email)) {
        wp_send_json_error('Invalid email address');
    }
    
    // Sample booking data for admin test email
    $property_ids = array('1001', '1002');
    $name = 'Test User';
    $email = 'test@example.com';
    $phone = '+123456789';
    $adults = 2;
    $children = 1;
    $pets = 0;
    $start_date = date('Y-m-d', strtotime('+7 days'));
    $end_date = date('Y-m-d', strtotime('+14 days'));
    $total_cost = '1000.00';
    $paid_amount = '300.00';
    $payment_method = 'Credit Card';
    $client_request = 'This is a test booking request.';
    
    // Reuse the admin email function but override the recipient
    $response = send_test_admin_email($property_ids, $name, $email, $phone, $adults, $children, $pets, 
                               $start_date, $end_date, $total_cost, $paid_amount, 
                               $payment_method, $client_request, $test_email);
    
    if (strpos($response, 'successfully') !== false) {
        wp_send_json_success('Admin test email sent successfully to ' . $test_email);
    } else {
        wp_send_json_error('Failed to send admin test email: ' . $response);
    }
}
add_action('wp_ajax_test_admin_email', 'test_admin_email_callback');

// Modified version of send_success_email_to_admin for testing
function send_test_admin_email($property_ids, $name, $email, $phone, $adults, $children, $pets, 
                       $start_date, $end_date, $total_cost, $paid_amount, 
                       $payment_method, $client_request, $test_email) {
    
    $notification_settings = get_option('notification_settings');
    $sender_name = $notification_settings['email_from_name'] ?? get_bloginfo('name');
    $sender_email = $notification_settings['email_from_address'] ?? get_option('admin_email');
    $site_name = get_bloginfo('name');
    $currency = get_currency();

    // Subject for the admin email
    $subject = '[TEST] New Booking Received - ' . $site_name;

    // Content for the admin email - reusing the existing template
    $content = '<!DOCTYPE html>
    <html lang="hu">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                [TEST] New Booking Received
            </div>
            <div class="email-body">
                <h2>Hello Admin,</h2>
                <p>This is a <strong>TEST EMAIL</strong>. In a real booking, you would receive details like this:</p>
                <ul>
                    <li><strong>Property ID(s):</strong> ' . implode(', ', $property_ids) . '</li>
                    <li><strong>Client Name:</strong> ' . $name . '</li>
                    <li><strong>Client Email:</strong> ' . $email . '</li>
                    <li><strong>Client Phone:</strong> ' . $phone . '</li>
                    <li><strong>Adults:</strong> ' . $adults . '</li>
                    <li><strong>Children:</strong> ' . $children . '</li>
                    <li><strong>Pets:</strong> ' . $pets . '</li>
                    <li><strong>Start Date:</strong> ' . $start_date . '</li>
                    <li><strong>End Date:</strong> ' . $end_date . '</li>
                    <li><strong>Total Cost:</strong> ' . $total_cost . ' ' . $currency . '</li>
                    <li><strong>Paid Amount:</strong> ' . $paid_amount . ' ' . $currency . '</li>
                    <li><strong>Payment Method:</strong> ' . $payment_method . '</li>
                    <li><strong>Client Request:</strong> ' . $client_request . '</li>
                </ul>
                <p>This is a test email sent from your Reserve-mate notification settings page.</p>
                <p>Best regards,<br>' . $site_name . '</p>
            </div>
            <div class="email-footer">
                © 2025 ' . $site_name . '. All rights reserved.
            </div>
        </div>
    </body>
    </html>';

    // Headers for the email
    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $sender_name <$sender_email>\r\n";

    // Send the email to the test address
    try {
        $email_sent = wp_mail($test_email, $subject, $content, $headers);
        if (!$email_sent) {
            $response = 'Email failed to send to admin';
        } else {
            $response = 'Email successfully sent to admin';
        }
    } catch (Exception $e) {
        $response = 'Email sending error: ' . $e->getMessage();
    }

    return $response;
}