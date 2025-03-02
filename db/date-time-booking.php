<?php
defined('ABSPATH') or die('No direct access!');

function create_hourly_bookings_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_hourly_bookings';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            service_name VARCHAR(255) NOT NULL,
            guests INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            start_datetime DATETIME NOT NULL,  /* Stores date + time */
            end_datetime DATETIME NOT NULL,    /* Stores date + time */
            total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            paid_amount DECIMAL(10,2) NULL DEFAULT 0.00,
            payment_method VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);
    }
}

function get_date_time_bookings_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_hourly_bookings';
    $booking_settings = get_option('booking_settings');
    $min_time = isset($booking_settings['hourly_min_time']) ? $booking_settings['hourly_min_time'] : '0';
    $max_time = isset($booking_settings['hourly_max_time']) ? $booking_settings['hourly_max_time'] : '0';
    $interval = isset($booking_settings['hourly_booking_interval']) ? $booking_settings['hourly_booking_interval'] : '0';
    $break_duration = isset($booking_settings['hourly_break_duration']) ? $booking_settings['hourly_break_duration'] : '0';
    
    // Fetch all booked datetimes
    $bookings = $wpdb->get_results("
        SELECT start_datetime, end_datetime 
        FROM $table_name
    ");

    if ($wpdb->last_error) {
        wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
    } else {
        wp_send_json([
            'success' => true,
            'data' => $bookings,
            'settings' => [
                'min_time' => $min_time, // Minimum booking time (e.g., "08:00")
                'max_time' => $max_time, // Maximum booking time (e.g., "20:00")
                'interval' => $interval, // Booking interval in minutes (e.g., 30)
                'break_duration' => $break_duration // Break between time slots in minutes
            ],
            'max_guests' => 10,
        ]);
    }
}

function save_datetime_booking_to_db($service_name, $name, $email, $phone, $adults, $start_date, $end_date, $total_cost, $payment_method, $paid_amount = 0,) {
    global $wpdb;

    $result = $wpdb->insert(
        $wpdb->prefix . 'reservemate_hourly_bookings',
        array(
            'service_name' => $service_name,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'guests' => $adults,
            'start_datetime' => $start_date,
            'end_datetime' => $end_date,
            'total_cost' => $total_cost,
            'paid_amount' => $paid_amount,
            'payment_method' => $payment_method,
        ),
        array(
            '%s',  // service_name
            '%s',  // name
            '%s',  // email
            '%s',  // phone
            '%d',  // guests
            '%s',  // start_date
            '%s',  // end_date
            '%f',  // total_cost
            '%f',  // paid_amount
            '%s',  // payment_method
        )
    );

    if ($result === false) {
        error_log('Database insert failed. : ' . $wpdb->last_error);
    } else {
        send_datetime_success_email_to_client($service_name, $name, $email, $phone, $adults, $start_date, $end_date, $total_cost, $payment_method, $paid_amount);
        send_datetime_success_email_to_admin($service_name, $name, $email, $phone, $adults, $start_date, $end_date, $total_cost, $payment_method, $paid_amount);
        error_log("send_datetime_success_email_to_client function from save_datetime_booking_to_db function");
    }
}

function send_datetime_success_email_to_client($service_name, $name, $email, $phone, $adults, $start_date, $end_date, $total_cost, $payment_method, $paid_amount) {
    $message_settings = get_option('message_settings');
    $sender_name = $message_settings['email_from_name'] ?? get_bloginfo('name');
    $sender_email = $message_settings['email_from_address'] ?? get_option('admin_email');
    $site_name = get_bloginfo('name');
    $currency = get_currency();

    // Check if sending email to clients is enabled
    if (isset($message_settings['send_email_to_clients'])) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format: $email");
            return;
        }

        $subject = isset($message_settings['client_email_subject']) && !empty($message_settings['client_email_subject']) 
            ? $message_settings['client_email_subject'] 
            : 'Booking Confirmation - Automated Email';

        $content = isset($message_settings['client_email_content']) && !empty($message_settings['client_email_content'])
            ? $message_settings['client_email_content']
            : '<!DOCTYPE html>
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
                        Booking Confirmation
                    </div>
                    <div class="email-body">
                        <h2>Dear {client_name},</h2>
                        <p>Thank you for booking your stay with us! Below are the details of your reservation:</p>
                        <ul>
                            <li><strong>Check-in Date:</strong> {check_in}</li>
                            <li><strong>Check-out Date:</strong> {check_out}</li>
                            <li><strong>Adults:</strong> {adults}</li>
                            {children_placeholder}
                            {pets_placeholder}
                            <li><strong>Total Price:</strong> {total_cost} {currency}</li>
                            <li><strong>Paid Amount:</strong> {paid_amount} {currency}</li>
                        </ul>
                        <p>If you have any questions, please feel free to contact us!</p>
                        <p>Best regards,<br>The owner of {site_name}</p>
                    </div>
                    <div class="email-footer">
                        © 2025 {site_name}. All rights reserved.
                    </div>
                </div>
            </body>
            </html>';

        // Replace placeholders with actual values
        $content = str_replace(
            ['{client_name}', '{check_in}', '{check_out}', '{adults}', '{children_placeholder}', '{pets_placeholder}', '{total_cost}', '{paid_amount}', '{site_name}', '{currency}'],
            [$name, $start_date, $end_date, $adults, '', '', $total_cost, $paid_amount, $site_name, $currency],
            $content
        );

        $headers = "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $sender_name <$sender_email>\r\n";

        try {
            $email_sent = wp_mail($email, $subject, $content, $headers);
            if (!$email_sent) {
                error_log("Email failed to send to: $email");
                $response = 'Email failed to send to client';
            } else {
                error_log("Email successfully sent to: $email");
                $response = 'Email successfully sent to client';
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            $response = 'Email sending error: ' . $e->getMessage();
        }
    } else {
        error_log("Sending email to clients is not enabled in message settings.");
    }

    return $response;
}

function send_datetime_success_email_to_admin($service_name, $name, $email, $phone, $adults, $start_date, $end_date, $total_cost, $payment_method, $paid_amount) {
    $message_settings = get_option('message_settings');
    $sender_name = $message_settings['email_from_name'] ?? get_bloginfo('name');
    $sender_email = $message_settings['email_from_address'] ?? get_option('admin_email');
    $admin_email = get_option('admin_email'); // Get the admin email address
    $site_name = get_bloginfo('name');
    $currency = get_currency();

    // Subject for the admin email
    $subject = 'New Booking Received - ' . $site_name;

    // Content for the admin email
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
                New Booking Received
            </div>
            <div class="email-body">
                <h2>Hello Admin,</h2>
                <p>A new booking has been made on ' . $site_name . '. Below are the details:</p>
                <ul>
                    <li><strong>Service Name:</strong> ' . $service_name . '</li>
                    <li><strong>Client Name:</strong> ' . $name . '</li>
                    <li><strong>Client Email:</strong> ' . $email . '</li>
                    <li><strong>Client Phone:</strong> ' . $phone . '</li>
                    <li><strong>Adults:</strong> ' . $adults . '</li>
                    <li><strong>Start Date:</strong> ' . $start_date . '</li>
                    <li><strong>End Date:</strong> ' . $end_date . '</li>
                    <li><strong>Total Cost:</strong> ' . $total_cost . ' ' . $currency . '</li>
                    <li><strong>Paid Amount:</strong> ' . $paid_amount . ' ' . $currency . '</li>
                    <li><strong>Payment Method:</strong> ' . $payment_method . '</li>
                </ul>
                <p>Please review the booking and take necessary actions.</p>
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

    // Send the email to the admin
    try {
        $email_sent = wp_mail($admin_email, $subject, $content, $headers);
        if (!$email_sent) {
            error_log("Email failed to send to admin: $admin_email");
            $response = 'Email failed to send to admin';
        } else {
            error_log("Email successfully sent to admin: $admin_email");
            $response = 'Email successfully sent to admin';
        }
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        $response = 'Email sending error: ' . $e->getMessage();
    }

    return $response;
}

add_action('wp_ajax_get_date_time_bookings_data', 'get_date_time_bookings_data');
add_action('wp_ajax_nopriv_get_date_time_bookings_data', 'get_date_time_bookings_data');