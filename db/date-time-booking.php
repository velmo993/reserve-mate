<?php
defined('ABSPATH') or die('No direct access!');

function create_hourly_bookings_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_hourly_bookings';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
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

function create_booking_services_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_booking_services';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            booking_id MEDIUMINT(9) NOT NULL,
            service_id MEDIUMINT(9) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}reservemate_hourly_bookings(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}reservemate_services(id) ON DELETE CASCADE
        ) ENGINE=InnoDB $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);
    }
}


// Frontend calendar (flatpickr) works with this method
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

function save_datetime_booking_to_db($name, $email, $phone, $adults, $start_datetime, $end_datetime, $total_cost, $payment_method, $services, $paid_amount, $admin = false) {
    global $wpdb;
    $currency = get_currency();
    // Insert the booking
    $result = $wpdb->insert(
        $wpdb->prefix . 'reservemate_hourly_bookings',
        [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'guests' => $adults,
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'total_cost' => $total_cost,
            'paid_amount' => $paid_amount,
            'payment_method' => $payment_method,
        ],
        [
            '%s', // name
            '%s', // email
            '%s', // phone
            '%d', // guests
            '%s', // start_datetime
            '%s', // end_datetime
            '%f', // total_cost
            '%f', // paid_amount
            '%s', // payment_method
        ]
    );

    if ($result === false) {
        error_log('Database insert failed. : ' . $wpdb->last_error);
    } else {
        $booking_id = $wpdb->insert_id;

        if (is_array($services)) {
            foreach ($services as $index => $service) {
                $service_name = get_service_name($service['id']);
                $wpdb->insert(
                    $wpdb->prefix . 'reservemate_booking_services',
                    [
                        'booking_id' => $booking_id,
                        'service_id' => $service['id'],
                        'quantity' => $service['quantity'],
                        'price' => $service['price'],
                    ],
                    [
                        '%d', // booking_id
                        '%d', // service_id
                        '%d', // quantity
                        '%f', // price
                    ]
                );
                
                $services[$index]['name'] = $service_name;
            }
        } else {
            error_log('Services is not an array: ' . print_r($services, true));
        }


        if (!$admin) {
            $services_list = '';
            foreach ($services as $service) {
                $services_list .= '<li><strong>Service:</strong> ' . esc_html($service['name']) . ' x ' . esc_html($service['quantity']) . ', Price: ' . esc_html($service['price'] . ' ' . $currency) . '</li>';
            }
            
            // Send emails to client and admin
            send_datetime_success_email_to_client($services_list, $name, $email, $phone, $adults, $start_datetime, $end_datetime, $total_cost, $payment_method, $paid_amount);
            send_datetime_success_email_to_admin($services_list, $name, $email, $phone, $adults, $start_datetime, $end_datetime, $total_cost, $payment_method, $paid_amount);
        }
    }
}

function update_datetime_booking($name, $email, $phone, $adults, $start_datetime, $end_datetime, $total_cost, $payment_method, $services, $paid_amount, $booking_id) {
    global $wpdb;
    
    // Update the main booking record
    $wpdb->update(
        $wpdb->prefix . 'reservemate_hourly_bookings',
        [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'guests' => $adults,
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'total_cost' => $total_cost,
            'paid_amount' => $paid_amount,
            'payment_method' => $payment_method,
        ],
        ['id' => $booking_id],
        [
            '%s', // name
            '%s', // email
            '%s', // phone
            '%d', // guests
            '%s', // start_datetime
            '%s', // end_datetime
            '%f', // total_cost
            '%f', // paid_amount
            '%s', // payment_method
        ],
        ['%d'] // booking id
    );
    
    // Delete existing services for this booking
    $wpdb->delete(
        $wpdb->prefix . 'reservemate_booking_services',
        ['booking_id' => $booking_id],
        ['%d']
    );
    
    // Add new services
    if (is_array($services)) {
        foreach ($services as $service) {
            $wpdb->insert(
                $wpdb->prefix . 'reservemate_booking_services',
                [
                    'booking_id' => $booking_id,
                    'service_id' => $service['id'],
                    'quantity' => $service['quantity'],
                    'price' => $service['price'],
                ],
                [
                    '%d', // booking_id
                    '%d', // service_id
                    '%d', // quantity
                    '%f', // price
                ]
            );
        }
    }
    
    return true;
}

function send_datetime_success_email_to_client($services_list, $name, $email, $phone, $adults, $start_date, $end_date, $total_cost, $payment_method, $paid_amount) {
    $message_settings = get_option('message_settings');
    $sender_name = $message_settings['email_from_name'] ?? get_bloginfo('name');
    $sender_email = $message_settings['email_from_address'] ?? get_option('admin_email');
    $site_name = get_bloginfo('name');
    $currency = get_currency();

    // Check if sending email to clients is enabled
    if (!isset($message_settings['send_email_to_clients'])) {
        return;
    }
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
                        <li><strong>Guests:</strong> {adults}</li>
                        '. esc_html($services_list) . '
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
        ['{client_name}', '{check_in}', '{check_out}', '{adults}', '{total_cost}', '{paid_amount}', '{site_name}', '{currency}'],
        [$name, $start_date, $end_date, $adults, $total_cost, $paid_amount, $site_name, $currency],
        $content
    );

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

function send_datetime_success_email_to_admin($services_list, $name, $email, $phone, $adults, $start_date, $end_date, $total_cost, $payment_method, $paid_amount) {
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
                    <li><strong>Name:</strong> ' . esc_html($name) . '</li>
                    <li><strong>Email:</strong> ' . esc_html($email) . '</li>
                    <li><strong>Phone:</strong> ' . esc_html($phone) . '</li>
                    <li><strong>Guests:</strong> ' . esc_html($adults) . '</li>
                    <li><strong>Start Date:</strong> ' . esc_html($start_date) . '</li>
                    <li><strong>End Date:</strong> ' . esc_html($end_date) . '</li>
                    '. esc_html($services_list) . '
                    <li><strong>Total Cost:</strong> ' . esc_html($total_cost . ' ' . $currency) . '</li>
                    <li><strong>Paid Amount:</strong> ' . esc_html($paid_amount . ' ' . $currency) . '</li>
                    <li><strong>Payment Method:</strong> ' . esc_html($payment_method) . '</li>
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

function get_datetime_bookings($per_page = 10, $page_number = 1) {
    global $wpdb;

    $bookings_table = $wpdb->prefix . 'reservemate_hourly_bookings';
    $services_table = $wpdb->prefix . 'reservemate_booking_services';
    $service_names_table = $wpdb->prefix . 'reservemate_services';

    // Calculate offset
    $offset = ($page_number - 1) * $per_page;

    // Get paginated bookings
    $bookings = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $bookings_table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );
    
    // Then get services for each booking
    foreach ($bookings as $booking) {
        $services = $wpdb->get_results($wpdb->prepare("
            SELECT s.service_id, s.quantity, s.price, sv.name as service_name
            FROM $services_table s
            LEFT JOIN $service_names_table sv ON s.service_id = sv.id
            WHERE s.booking_id = %d
        ", $booking->id));
        
        $booking->services = $services;
        $booking->service_names = implode(', ', array_map(function($s) {
            return $s->service_name;
        }, $services));
    }

    return $bookings;
}

// Add a function to count total bookings
function count_datetime_bookings() {
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'reservemate_hourly_bookings';
    return $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
}

function get_datetime_booking($booking_id) {
    global $wpdb;

    $bookings_table = $wpdb->prefix . 'reservemate_hourly_bookings';
    $services_table = $wpdb->prefix . 'reservemate_booking_services';
    $service_names_table = $wpdb->prefix . 'reservemate_services';

    $query = $wpdb->prepare("
        SELECT b.*, s.service_id, s.quantity, s.price, sv.name as service_name
        FROM $bookings_table b
        LEFT JOIN $services_table s ON b.id = s.booking_id
        LEFT JOIN $service_names_table sv ON s.service_id = sv.id
        WHERE b.id = %d
    ", intval($booking_id));

    return $wpdb->get_row($query);
}

function delete_datetime_booking($booking_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_hourly_bookings';
    if($booking_id) {
        $wpdb->delete($table_name, ['id' => intval($booking_id)]);
    }
}

function get_booking_services($booking_id) {
    global $wpdb;
    
    $services = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}reservemate_booking_services WHERE booking_id = %d",
            $booking_id
        )
    );
    
    return $services;
}

add_action('wp_ajax_get_date_time_bookings_data', 'get_date_time_bookings_data');
add_action('wp_ajax_nopriv_get_date_time_bookings_data', 'get_date_time_bookings_data');