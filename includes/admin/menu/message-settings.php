<?php

function register_message_settings() {
    register_setting('message_settings_group', 'message_settings', array(
        'sanitize_callback' => 'sanitize_message_settings'
    ));

    add_settings_section(
        'booking_messages',
        __('Booking Messages', 'reserve-mate'),
        null,
        'manage-messages'
    );

    add_settings_field(
        'booking_success_message',
        __('Booking Successful Message', 'reserve-mate'),
        'display_booking_success_message_field',
        'manage-messages',
        'booking_messages'
    );
    
    add_settings_field(
        'send_email_to_clients',
        __('Send Booking Email to Guest', 'reserve-mate'),
        'display_send_email_to_clients_field',
        'manage-messages',
        'booking_messages'
    );
    
    add_settings_field(
        'email_from_name',
        __('Email From Name', 'reserve-mate'),
        'display_email_from_name_field',
        'manage-messages',
        'booking_messages'
    );

    add_settings_field(
        'email_from_address',
        __('Email From Address', 'reserve-mate'),
        'display_email_from_address_field',
        'manage-messages',
        'booking_messages'
    );
    
    add_settings_field(
        'client_email_subject',
        __('Email Subject', 'reserve-mate'),
        'display_client_email_subject_field',
        'manage-messages',
        'booking_messages'
    );
    
    add_settings_field(
        'client_email_content',
        __('Email Content', 'reserve-mate'),
        'display_client_email_content_field',
        'manage-messages',
        'booking_messages'
    );
}

function manage_messages_page() {
    ?>
    <div class="wrap">
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Messages updated successfully.', 'reserve-mate') . '</p></div>';
        } ?>
        <h1><?php _e('Manage Messages', 'reserve-mate'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('message_settings_group');
            do_settings_sections('manage-messages');
            submit_button(__('Save Messages', 'reserve-mate'));
            ?>
        </form>
    </div>
    <?php
}

function display_booking_success_message_field() {
    $message = get_option('message_settings')['booking_success_message'] ?? '';
    ?>
    <textarea name="message_settings[booking_success_message]" rows="5" cols="50"><?php echo esc_textarea($message); ?></textarea>
    <p class="description"><?php _e('Enter the message shown after a successful booking.', 'reserve-mate'); ?></p>
    <?php
}

function display_send_email_to_clients_field() {
    $message_settings = get_option('message_settings');
    $send_email_to_clients = isset($message_settings['send_email_to_clients']) ? $message_settings['send_email_to_clients'] : 0;
    
    echo '<input type="checkbox" name="message_settings[send_email_to_clients]" value="1"' . checked(1, $send_email_to_clients, false) . '> ' . __('Send an email to the guest after the booking is made.', 'reserve-mate');
}

function display_email_from_name_field() {
    $message_settings = get_option('message_settings');
    $email_from_name = $message_settings['email_from_name'] ?? get_bloginfo('name');
    
    echo '<input type="text" name="message_settings[email_from_name]" value="' . esc_attr($email_from_name) . '" class="regular-text">';
    echo '<p class="description">' . __('This name will appear as the sender in booking emails.', 'reserve-mate') . '</p>';
}

function display_email_from_address_field() {
    $message_settings = get_option('message_settings');
    $email_from_address = $message_settings['email_from_address'] ?? get_option('admin_email');
    
    echo '<input type="email" name="message_settings[email_from_address]" value="' . esc_attr($email_from_address) . '" class="regular-text">';
    echo '<p class="description">' . __('This email will be used as the sender email in booking emails.', 'reserve-mate') . '</p>';
}

function display_client_email_subject_field() {
    $message_settings = get_option('message_settings');
    $client_email_subject = $message_settings['client_email_subject'] ?? 'Booking Confirmation';
    
    echo '<input type="text" name="message_settings[client_email_subject]" value="' . esc_attr($client_email_subject) . '" class="regular-text">';
}

function display_client_email_content_field() {
    $message = get_option('message_settings')['client_email_content'] ?? '';
    ?>
    <textarea name="message_settings[client_email_content]" rows="15" cols="100"><?php echo esc_textarea($message); ?></textarea>
    <p class="description"><?php _e('Enter the message sent to the client after booking.', 'reserve-mate'); ?></p>
    <?php
}

function sanitize_message_settings($input) {
    $sanitized = array();
    
    if (isset($input['booking_success_message'])) {
        $sanitized['booking_success_message'] = wp_kses_post($input['booking_success_message']);
    }

    if (isset($input['client_email_content'])) {
        $sanitized['client_email_content'] = sanitize_email_template($input['client_email_content']);
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

    return $sanitized;
}

function sanitize_email_template($input) {
    $allowed_tags = [
        'html' => [],
        'head' => [],
        'body' => [],
        'style' => [],
        'div' => [
            'class' => [],
            'style' => [],
        ],
        'h2' => [
            'style' => [],
        ],
        'p' => [
            'style' => [],
        ],
        'ul' => [],
        'li' => [],
        'strong' => [],
        'br' => [],
    ];

    return wp_kses($input, $allowed_tags);
}

add_action('admin_init', 'register_message_settings');