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

    // Existing fields
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

    add_settings_section(
        'smtp_settings',
        __('SMTP Settings', 'reserve-mate'),
        'display_smtp_settings_section',
        'manage-messages'
    );

    add_settings_field(
        'smtp_host',
        __('SMTP Host', 'reserve-mate'),
        'display_smtp_host_field',
        'manage-messages',
        'smtp_settings'
    );

    add_settings_field(
        'smtp_port',
        __('SMTP Port', 'reserve-mate'),
        'display_smtp_port_field',
        'manage-messages',
        'smtp_settings'
    );

    add_settings_field(
        'smtp_encryption',
        __('Encryption', 'reserve-mate'),
        'display_smtp_encryption_field',
        'manage-messages',
        'smtp_settings'
    );

    add_settings_field(
        'smtp_username',
        __('SMTP Username', 'reserve-mate'),
        'display_smtp_username_field',
        'manage-messages',
        'smtp_settings'
    );

    add_settings_field(
        'smtp_password',
        __('SMTP Password', 'reserve-mate'),
        'display_smtp_password_field',
        'manage-messages',
        'smtp_settings'
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
    echo '<p class="description" style="color: #d63638;">' . __('Note: For best deliverability, this email should match the SMTP username.', 'reserve-mate') . '</p>';
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

function display_smtp_settings_section() {
    echo '<p>' . __('Configure SMTP settings for sending emails.', 'reserve-mate') . '</p>';
}

function display_smtp_host_field() {
    $message_settings = get_option('message_settings');
    $smtp_host = $message_settings['smtp_host'] ?? '';
    echo '<input type="text" name="message_settings[smtp_host]" value="' . esc_attr($smtp_host) . '" class="regular-text">';
    echo '<p class="description">' . __('Enter your SMTP host (e.g., smtp.gmail.com).', 'reserve-mate') . '</p>';
}

function display_smtp_port_field() {
    $message_settings = get_option('message_settings');
    $smtp_port = $message_settings['smtp_port'] ?? '587';
    echo '<input type="text" name="message_settings[smtp_port]" value="' . esc_attr($smtp_port) . '" class="regular-text">';
    echo '<p class="description">' . __('Enter your SMTP port (e.g., 587 for TLS).', 'reserve-mate') . '</p>';
}

function display_smtp_encryption_field() {
    $message_settings = get_option('message_settings');
    $smtp_encryption = $message_settings['smtp_encryption'] ?? 'tls';
    echo '<select name="message_settings[smtp_encryption]" class="regular-text">';
    echo '<option value="tls"' . selected('tls', $smtp_encryption, false) . '>TLS</option>';
    echo '<option value="ssl"' . selected('ssl', $smtp_encryption, false) . '>SSL</option>';
    echo '</select>';
    echo '<p class="description">' . __('Select the encryption method (TLS or SSL).', 'reserve-mate') . '</p>';
}

function display_smtp_username_field() {
    $message_settings = get_option('message_settings');
    $smtp_username = $message_settings['smtp_username'] ?? '';
    echo '<input type="text" name="message_settings[smtp_username]" value="' . esc_attr($smtp_username) . '" class="regular-text">';
    echo '<p class="description">' . __('Enter your SMTP username (usually your email address).', 'reserve-mate') . '</p>';
}

function display_smtp_password_field() {
    $message_settings = get_option('message_settings');
    $smtp_password = $message_settings['smtp_password'] ?? '';
    echo '<input type="password" name="message_settings[smtp_password]" value="' . esc_attr($smtp_password) . '" class="regular-text">';
    echo '<p class="description">' . __('Enter your SMTP password.', 'reserve-mate') . '</p>';
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

    if (isset($input['smtp_password'])) {
        $sanitized['smtp_password'] = sanitize_text_field($input['smtp_password']);
    }

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

function configure_smtp() {
    $message_settings = get_option('message_settings');

    if (!empty($message_settings['smtp_host']) && !empty($message_settings['smtp_username'])) {
        add_action('phpmailer_init', function ($phpmailer) use ($message_settings) {
            $phpmailer->isSMTP();
            $phpmailer->Host = $message_settings['smtp_host'];
            $phpmailer->SMTPAuth = true;
            $phpmailer->Port = $message_settings['smtp_port'];
            $phpmailer->Username = $message_settings['smtp_username'];
            $phpmailer->Password = $message_settings['smtp_password'];
            $phpmailer->SMTPSecure = $message_settings['smtp_encryption'];
        });
    }
}

add_action('admin_init', 'configure_smtp');