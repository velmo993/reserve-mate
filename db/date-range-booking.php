<?php
defined('ABSPATH') or die('No direct access!');

function create_bookings_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_bookings';
    $properties_table = $wpdb->prefix . 'reservemate_properties';
    $charset_collate = $wpdb->get_charset_collate();

    // Make sure the properties table exists first
    create_properties_table(); 

    // Ensure properties table exists before creating bookings
    if ($wpdb->get_var("SHOW TABLES LIKE '$properties_table'") != $properties_table) {
        return;
    }
    
     if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        return;
    } else {
        // Now create the bookings table with the foreign key
        $sql = "CREATE TABLE $table_name (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            property_id MEDIUMINT(9) NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            adults INT NOT NULL,
            children INT NULL,
            pets INT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            paid_amount DECIMAL(10,2) NULL DEFAULT 0.00,
            payment_method VARCHAR(50) NOT NULL,
            client_request TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (property_id) REFERENCES $properties_table(id) ON DELETE CASCADE
        ) ENGINE=InnoDB $charset_collate;"; 
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $wpdb->query($sql);
    }

}

function get_booking($booking_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_bookings';
    $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($booking_id)));
    if($booking) {
        return $booking;
    }
}

function get_bookings() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_bookings';
    $bookings = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);
    if($bookings) {
        return $bookings;
    }
}

function get_bookings_for_property($property_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_bookings';
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE property_id = %d", $property_id
    ));
    return $bookings;
}

function update_booking($data, $index) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_bookings';
    $wpdb->update($table_name, $data, ['id' => $index]);
}

function delete_booking($booking_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_bookings';
    if($booking_id) {
        $wpdb->delete($table_name, ['id' => intval($booking_id)]);
    }
}

function get_available_days_between_bookings($bookings, $min_stay, $seasonal_rules = [], $disabled_dates = []) {
    $gaps = [];

    if (count($bookings) > 1) {
        for ($i = 0; $i < count($bookings) - 1; $i++) {
            $current_end = strtotime($bookings[$i]['end_date']);
            $next_start = strtotime($bookings[$i + 1]['start_date']);

            // Get the start and end dates of the gap
            $gap_start_date = date('Y-m-d', strtotime('+1 day', $current_end));
            $gap_end_date = date('Y-m-d', strtotime('-1 day', $next_start));

            // Check if the gap is valid (should be at least 1 day)
            if ($current_end >= $next_start) {
                continue;
            }
            
            $is_gap_disabled = false;
            $current_gap_date = strtotime($gap_start_date);
            while ($current_gap_date <= strtotime($gap_end_date)) {
                $date_str = date('Y-m-d', $current_gap_date);
                if (is_date_disabled($date_str, $disabled_dates)) {
                    $is_gap_disabled = true;
                    break;
                }
                $current_gap_date = strtotime('+1 day', $current_gap_date);
            }

            if ($is_gap_disabled) {
                continue;
            }

            // Determine the highest min_stay within the gap period
            $highest_min_stay = $min_stay; // Default to global property's min_stay
            $current_date = strtotime($gap_start_date);

            while ($current_date <= strtotime($gap_end_date)) {
                $gap_month = date('n', $current_date); // Get month (1-12)
                if (!empty($seasonal_rules[$gap_month]['min'])) {
                    $seasonal_min_stay = intval($seasonal_rules[$gap_month]['min']);
                    if ($seasonal_min_stay > 0 && $seasonal_min_stay > $highest_min_stay) {
                        $highest_min_stay = $seasonal_min_stay;
                    }
                }
                $current_date = strtotime('+1 day', $current_date); // Move to next day
            }

            // Calculate the gap in days
            $gap_days = ($next_start - $current_end) / (60 * 60 * 24);

            // Ensure the gap meets the highest min_stay requirement
            if ($gap_days > 0 && $gap_days < $highest_min_stay) {
                $gaps[] = [
                    'start_date' => $gap_start_date,
                    'end_date' => $gap_end_date
                ];
            } else {
                //error_log("Gap of $gap_days days does not meet min stay requirement of $highest_min_stay nights.");
            }
        }
    }

    return $gaps;
}

function is_date_disabled($date, $disabled_dates) {
    if (empty($disabled_dates)) return false;

    $timestamp = strtotime($date);
    $day_of_week = strtolower(date('l', $timestamp));
    $month_day = date('m-d', $timestamp);

    foreach ($disabled_dates as $rule) {
        switch ($rule['type']) {
            case 'specific':
                if ($date === $rule['date']) return true;
                if (!empty($rule['repeat_yearly']) && $month_day === date('m-d', strtotime($rule['date']))) return true;
                break;
            case 'range':
                $start = strtotime($rule['start_date']);
                $end = strtotime($rule['end_date']);
                if ($timestamp >= $start && $timestamp <= $end) return true;
                if (!empty($rule['repeat_yearly'])) {
                    $current_year = date('Y');
                    if ($date >= date('Y-m-d', strtotime($current_year . '-' . date('m-d', $start))) && 
                        $date <= date('Y-m-d', strtotime($current_year . '-' . date('m-d', $end)))) return true;
                }
                break;
            case 'weekly':
                if (in_array($day_of_week, $rule['days'])) return true;
                break;
        }
    }
    return false;
}

function get_bookings_with_settings($property_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_bookings';

    $property = get_property($property_id);
    if (!$property) {
        return new WP_Error('property_not_found', 'Property not found');
    }

    $min_stay = $property->min_stay;

    $bookings = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT start_date, end_date FROM $table_name WHERE property_id = %d ORDER BY start_date ASC",
            $property_id
        ),
        ARRAY_A
    );
    $seasonal_rules = !empty($property->seasonal_rules) ? json_decode($property->seasonal_rules, true) : [];
    $disabled_dates = !empty($property->disabled_dates) ? json_decode($property->disabled_dates, true) : [];
    // Find gaps between bookings
    $gaps = get_available_days_between_bookings($bookings, $min_stay, $seasonal_rules, $disabled_dates);

    $bookingsData = [
        'bookings' => $bookings,
        'gaps' => $gaps,
        'property' => $property
    ];

    return $bookingsData;
}

function get_booked_dates_data() {
    if (!isset($_GET['property_ids'])) {
        wp_send_json_error(['error' => 'Invalid or missing property IDs'], 400);
        wp_die();
    }
    
    $currency = get_currency();
    $taxes = get_taxes();

    global $wpdb;
    $table_name = $wpdb->prefix . 'reservemate_bookings';
    
    $property_ids = explode(',', sanitize_text_field($_GET['property_ids']));
    $bookingsData = [];

    $combined_max_children = 0;
    $combined_max_pets = 0;
    $combined_max_adults = 0;
    
    foreach ($property_ids as $property_id) {
        if (!is_numeric($property_id)) {
            continue;
        }

        $property_id = intval($property_id);
        $bookings = get_bookings_with_settings($property_id);

        if (!is_wp_error($bookings)) {
            $property = $bookings['property'];
            $combined_max_children += $property->max_child_number ?? 0;
            $combined_max_pets += $property->max_pet_number ?? 0;
            $combined_max_adults += $property->max_adult_number ?? 0;
            
            $bookingsData[] = [
                'property_id' => $property_id,
                'bookings'    => $bookings['bookings'],
                'gaps'        => $bookings['gaps'],
                'taxes'       => $taxes,
                'property'    => $property,
                'currency'    => $currency,
            ];
        }
    }

    if (empty($bookingsData)) {
        wp_send_json_error(['error' => 'No valid properties found'], 404);
        wp_die();
    }
    
    wp_send_json([
        'success' => true,
        'data' => $bookingsData,
        'combined_max_adults' => $combined_max_adults,
        'combined_max_children' => $combined_max_children,
        'combined_max_pets'   => $combined_max_pets,
    ]);
}

function save_booking_to_db($property_ids, $name, $email, $phone, $adults, $children, $pets, $start_date, $end_date, $total_cost, $payment_method, $client_request, $paid_amount = 0, $admin = false) {
    global $wpdb;

    // If $property_ids is a single value, convert it into an array to handle both cases
    if (!is_array($property_ids)) {
        $property_ids = array($property_ids);
    }
    // Loop through all property IDs and insert bookings for each one
    foreach ($property_ids as $property_id) {
        $result = $wpdb->insert(
            $wpdb->prefix . 'reservemate_bookings',
            array(
                'property_id' => $property_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'adults' => $adults,
                'children' => $children,
                'pets' => $pets,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'total_cost' => $total_cost,
                'paid_amount' => $paid_amount,
                'payment_method' => $payment_method,
                'client_request' => $client_request,
            ),
            array(
                '%d',  // property_id
                '%s',  // name
                '%s',  // email
                '%s',  // phone
                '%d',  // adults
                '%d',  // children
                '%d',  // pets
                '%s',  // start_date
                '%s',  // end_date
                '%f',  // total_cost
                '%f',  // paid_amount
                '%s',  // payment_method
                '%s',  // client_request
            )
        );

        if ($result === false) {
            error_log('Database insert failed for property ID ' . $property_id . ': ' . $wpdb->last_error);
        } else if(!$admin) {
            send_success_email_to_client($property_ids, $name, $email, $phone, $adults, $children, $pets, $start_date, $end_date, $total_cost, $paid_amount);
            send_success_email_to_admin($property_ids, $name, $email, $phone, $adults, $children, $pets, $start_date, $end_date, $total_cost, $paid_amount, $payment_method, $client_request);
        }
    }
}

function send_success_email_to_client($property_ids, $name, $email, $phone, $adults, $children, $pets, $start_date, $end_date, $total_cost, $paid_amount) {
    // if(count($property_ids) > 1) {
    //     $properties = get_properties($property_ids);
    //     $property_name = $properties[0].name;
    // } else {
    //     $propery_name = get_property($property_ids[0]).name;
    // }
    $message_settings = get_option('message_settings');
    $sender_name = $message_settings['email_from_name'] ?? get_bloginfo('name');
    $sender_email = $message_settings['email_from_address'] ?? get_option('admin_email');
    $property_name = get_bloginfo('name');
    // $booking_number = 'bn12345';
    $currency = get_currency();
    
    
    // Check if sending email to clients is enabled
    if (isset($message_settings['send_email_to_clients'])) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format: $email");
            return;
        }
            
        $subject = isset($message_settings['client_email_subject']) && !empty($message_settings['client_email_subject']) ? $message_settings['client_email_subject'] : 'Booking Confirmation - Automated Email';
        $content = isset($message_settings['client_email_content']) && !empty($message_settings['client_email_content'])
        ? $message_settings['client_email_content'] 
        : '<!DOCTYPE html>
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
                    <p>Best regards,<br>The owner of {property_name}</p>
                </div>
                <div class="email-footer">
                    © 2025 {property_name}. All rights reserved.
                </div>
            </div>
        </body>
        </html>';
    
    
        $children_placeholder = (!empty($children) && $children != '0') ? "<li><strong>Children:</strong> $children</li>" : "";
        $pets_placeholder = (!empty($pets) && $pets != '0') ? "<li><strong>Pets:</strong> $pets</li>" : "";
        
        // Replace placeholders with actual values
        $content = str_replace(
            ['{client_name}', '{check_in}', '{check_out}', '{adults}', '{children_placeholder}', '{pets_placeholder}', '{total_cost}', '{paid_amount}', '{property_name}', '{currency}'],
            [$name, $start_date, $end_date, $adults, $children_placeholder, $pets_placeholder, $total_cost, $paid_amount, $property_name, $currency],
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

function send_success_email_to_admin($property_ids, $name, $email, $phone, $adults, $children, $pets, $start_date, $end_date, $total_cost, $paid_amount, $payment_method, $client_request) {
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


add_action('wp_ajax_get_booked_dates_data', 'get_booked_dates_data');
add_action('wp_ajax_nopriv_get_booked_dates_data', 'get_booked_dates_data');