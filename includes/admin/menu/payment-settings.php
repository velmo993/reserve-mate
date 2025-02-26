<?php

function register_payment_settings() {
    register_setting('payment_settings_group', 'payment_settings', array(
        'sanitize_callback' => 'sanitize_payment_settings'
    ));

    add_settings_section(
        'horizontal_line_before_stripe',
        '',
        'display_horizontal_line',
        'payment-settings'
    );
    
    add_settings_section(
        'stripe_settings',
        __('Stripe Settings', 'reserve-mate'),
        null,
        'payment-settings'
    );
    
    add_settings_field(
        'stripe_enabled',
        __('Enable Stripe', 'reserve-mate'),
        'display_stripe_enabled_field',
        'payment-settings',
        'stripe_settings'
    );

    add_settings_field(
        'stripe_secret_key',
        __('Stripe Secret Key', 'reserve-mate'),
        'display_stripe_secret_key_field',
        'payment-settings',
        'stripe_settings'
    );

    add_settings_field(
        'stripe_public_key',
        __('Stripe Public Key', 'reserve-mate'),
        'display_stripe_public_key_field',
        'payment-settings',
        'stripe_settings'
    );
    
    add_settings_section(
        'horizontal_line_before_paypal',
        '',
        'display_horizontal_line',
        'payment-settings'
    );
    
    add_settings_section(
        'paypal_settings',
        __('PayPal Settings', 'reserve-mate'),
        null,
        'payment-settings'
    );
    
    add_settings_field(
        'paypal_enabled',
        __('Enable PayPal', 'reserve-mate'),
        'display_paypal_enabled_field',
        'payment-settings',
        'paypal_settings'
    );
    
    add_settings_field(
        'paypal_client_id',
        __('PayPal Client ID', 'reserve-mate'),
        'display_paypal_client_id_field',
        'payment-settings',
        'paypal_settings'
    );
    
    add_settings_section(
        'horizontal_line_before_advance_payment',
        '',
        'display_horizontal_line',
        'payment-settings'
    );
    
    add_settings_section(
        'advance_payment_settings',
        __('Advance Payment Settings', 'reserve-mate'),
        null,
        'payment-settings'
    );

    add_settings_field(
        'advance_payment_type',
        __('Advance Payment Type', 'reserve-mate'),
        'display_advance_payment_type_field',
        'payment-settings',
        'advance_payment_settings'
    );

    add_settings_field(
        'advance_payment_percentage',
        __('Advance Payment Percentage', 'reserve-mate'),
        'display_advance_payment_percentage_field',
        'payment-settings',
        'advance_payment_settings'
    );

    add_settings_field(
        'advance_payment_fixed_amount',
        __('Advance Payment Fixed Amount', 'reserve-mate'),
        'display_advance_payment_fixed_amount_field',
        'payment-settings',
        'advance_payment_settings'
    );
    
    add_settings_section(
        'horizontal_line_before_pay_on_arrival',
        '',
        'display_horizontal_line',
        'payment-settings'
    );

    add_settings_field(
        'pay_on_arrival_enabled',
        __('Pay on Arrival', 'reserve-mate'),
        'display_pay_on_arrival_enabled_field',
        'payment-settings',
        'advance_payment_settings'
    );
    
    add_settings_section(
        'horizontal_line_before_bank_transfer',
        '',
        'display_horizontal_line',
        'payment-settings'
    );
    
    add_settings_section(
        'bank_transfer_settings',
        __('Bank Transfer Settings', 'reserve-mate'),
        null,
        'payment-settings'
    );

    add_settings_field(
        'bank_transfer_enabled',
        __('Enable Bank Transfer', 'reserve-mate'),
        'display_bank_transfer_enabled_field',
        'payment-settings',
        'bank_transfer_settings'
    );

    add_settings_field(
        'bank_account_number',
        __('Bank Account Number', 'reserve-mate'),
        'display_bank_account_number_field',
        'payment-settings',
        'bank_transfer_settings'
    );

    add_settings_field(
        'bank_account_identifier',
        __('Bank Account Identifier (IBAN/Routing Number)', 'reserve-mate'),
        'display_bank_account_identifier_field',
        'payment-settings',
        'bank_transfer_settings'
    );

    add_settings_field(
        'bank_swift_bic',
        __('Bank SWIFT/BIC Code', 'reserve-mate'),
        'display_bank_swift_bic_field',
        'payment-settings',
        'bank_transfer_settings'
    );

    add_settings_field(
        'bank_name',
        __('Bank Name', 'reserve-mate'),
        'display_bank_name_field',
        'payment-settings',
        'bank_transfer_settings'
    );

    add_settings_field(
        'bank_recipient_name',
        __('Recipient Name', 'reserve-mate'),
        'display_bank_recipient_name_field',
        'payment-settings',
        'bank_transfer_settings'
    );

    add_settings_field(
        'bank_additional_info',
        __('Additional Bank Information', 'reserve-mate'),
        'display_bank_additional_info_field',
        'payment-settings',
        'bank_transfer_settings'
    );

    // add_settings_section(
    //     'apple_pay_settings',
    //     __('Apple Pay Settings', 'reserve-mate'),
    //     null,
    //     'payment-settings'
    // );
    
    // add_settings_field(
    //     'apple_pay_enabled',
    //     __('Enable Apple Pay Payment', 'reserve-mate'),
    //     'display_apple_pay_enabled_field',
    //     'payment-settings',
    //     'apple_pay_settings'
    // );

    // add_settings_field(
    //     'apple_pay_merchant_id',
    //     __('Apple Pay Merchant ID', 'reserve-mate'),
    //     'display_apple_pay_merchant_id_field',
    //     'payment-settings',
    //     'apple_pay_settings'
    // );

    // add_settings_field(
    //     'apple_pay_cert_path',
    //     __('Apple Pay Certificate Path', 'reserve-mate'),
    //     'display_apple_pay_cert_path_field',
    //     'payment-settings',
    //     'apple_pay_settings'
    // );

    // add_settings_field(
    //     'apple_pay_key_path',
    //     __('Apple Pay Private Key Path', 'reserve-mate'),
    //     'display_apple_pay_key_path_field',
    //     'payment-settings',
    //     'apple_pay_settings'
    // );

    // add_settings_field(
    //     'apple_pay_display_name',
    //     __('Apple Pay Display Name', 'reserve-mate'),
    //     'display_apple_pay_display_name_field',
    //     'payment-settings',
    //     'apple_pay_settings'
    // );
}

function payment_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Payment Settings', 'reserve-mate'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('payment_settings_group');
            do_settings_sections('payment-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function display_stripe_enabled_field() {
    $value = get_option('payment_settings')['stripe_enabled'] ?? '0';
    echo '<input type="checkbox" name="payment_settings[stripe_enabled]" value="1" ' . checked(1, $value, false) . '> Enable';
}

function display_stripe_secret_key_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="text" name="payment_settings[stripe_secret_key]" value="<?php echo isset($options['stripe_secret_key']) ? esc_attr($options['stripe_secret_key']) : ''; ?>" class="regular-text">
    <?php
}

function display_stripe_public_key_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="text" name="payment_settings[stripe_public_key]" value="<?php echo isset($options['stripe_public_key']) ? esc_attr($options['stripe_public_key']) : ''; ?>" class="regular-text">
    <?php
}

function display_paypal_enabled_field() {
    $value = get_option('payment_settings')['paypal_enabled'] ?? '0';
    echo '<input type="checkbox" name="payment_settings[paypal_enabled]" value="1" ' . checked(1, $value, false) . '> Enable';
}

function display_paypal_client_id_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="text" name="payment_settings[paypal_client_id]" value="<?php echo isset($options['paypal_client_id']) ? esc_attr($options['paypal_client_id']) : ''; ?>" class="regular-text">
    <?php
}

// function display_apple_pay_enabled_field() {
//     $value = get_option('payment_settings')['apple_pay_enabled'] ?? '0';
//     echo '<input type="checkbox" name="payment_settings[apple_pay_enabled]" value="1" ' . checked(1, $value, false) . '> Enable Apple Pay';
// }

// function display_apple_pay_merchant_id_field() {
//     $value = get_option('payment_settings')['apple_pay_merchant_id'] ?? '';
//     echo '<input type="text" name="payment_settings[apple_pay_merchant_id]" value="' . esc_attr($value) . '" />';
// }

// function display_apple_pay_cert_path_field() {
//     $value = get_option('payment_settings')['apple_pay_cert_path'] ?? '';
//     echo '<input type="text" name="payment_settings[apple_pay_cert_path]" value="' . esc_attr($value) . '" />';
// }

// function display_apple_pay_key_path_field() {
//     $value = get_option('payment_settings')['apple_pay_key_path'] ?? '';
//     echo '<input type="text" name="payment_settings[apple_pay_key_path]" value="' . esc_attr($value) . '" />';
// }

// function display_apple_pay_display_name_field() {
//     $value = get_option('payment_settings')['apple_pay_display_name'] ?? '';
//     echo '<input type="text" name="payment_settings[apple_pay_display_name]" value="' . esc_attr($value) . '" />';
// }

function display_advance_payment_type_field() {
    $options = get_option('payment_settings');
    $selected = isset($options['advance_payment_type']) ? $options['advance_payment_type'] : 'none';
    ?>
    <select name="payment_settings[advance_payment_type]">
        <option value="none" <?php selected($selected, 'none'); ?>><?php _e('None', 'reserve-mate'); ?></option>
        <option value="percentage" <?php selected($selected, 'percentage'); ?>><?php _e('Percentage', 'reserve-mate'); ?></option>
        <option value="fixed" <?php selected($selected, 'fixed'); ?>><?php _e('Fixed Amount', 'reserve-mate'); ?></option>
    </select>
    <?php
}

function display_advance_payment_percentage_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="number" name="payment_settings[advance_payment_percentage]" value="<?php echo isset($options['advance_payment_percentage']) ? esc_attr($options['advance_payment_percentage']) : ''; ?>" class="small-text"> %
    <?php
}

function display_advance_payment_fixed_amount_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="number" name="payment_settings[advance_payment_fixed_amount]" value="<?php echo isset($options['advance_payment_fixed_amount']) ? esc_attr($options['advance_payment_fixed_amount']) : ''; ?>" class="small-text">
    <?php
}

function display_pay_on_arrival_enabled_field() {
    $value = get_option('payment_settings')['pay_on_arrival_enabled'] ?? '0';
    echo '<input type="checkbox" name="payment_settings[pay_on_arrival_enabled]" value="1" ' . checked(1, $value, false) . '> Enable';
}

function display_bank_transfer_enabled_field() {
    $value = get_option('payment_settings')['bank_transfer_enabled'] ?? '0';
    echo '<input type="checkbox" name="payment_settings[bank_transfer_enabled]" value="1" ' . checked(1, $value, false) . '> Enable';
}

function display_bank_account_number_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="text" name="payment_settings[bank_account_number]" value="<?php echo isset($options['bank_account_number']) ? esc_attr($options['bank_account_number']) : ''; ?>" class="regular-text">
    <p class="description"><?php _e('Provide the recipient\'s bank account number. This is typically used for domestic transfers.', 'reserve-mate'); ?></p>
    <?php
}

function display_bank_account_identifier_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="text" name="payment_settings[bank_account_identifier]" value="<?php echo isset($options['bank_account_identifier']) ? esc_attr($options['bank_account_identifier']) : ''; ?>" class="regular-text">
    <p class="description"><?php _e('Provide your IBAN for international transfers or Routing Number for domestic transfers.', 'reserve-mate'); ?></p>
    <?php
}

function display_bank_swift_bic_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="text" name="payment_settings[bank_swift_bic]" value="<?php echo isset($options['bank_swift_bic']) ? esc_attr($options['bank_swift_bic']) : ''; ?>" class="regular-text">
    <p class="description"><?php _e('Required for international transfers. This is the unique identifier for the recipient\'s bank.', 'reserve-mate'); ?></p>
    <?php
}

function display_bank_name_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="text" name="payment_settings[bank_name]" value="<?php echo isset($options['bank_name']) ? esc_attr($options['bank_name']) : ''; ?>" class="regular-text">
    <p class="description"><?php _e('The name of the bank where the recipient holds their account.', 'reserve-mate'); ?></p>
    <?php
}

function display_bank_recipient_name_field() {
    $options = get_option('payment_settings');
    ?>
    <input type="text" name="payment_settings[bank_recipient_name]" value="<?php echo isset($options['bank_recipient_name']) ? esc_attr($options['bank_recipient_name']) : ''; ?>" class="regular-text">
    <p class="description"><?php _e('The name of the person or company receiving the funds. Must match the name on the bank account.', 'reserve-mate'); ?></p>
    <?php
}

function display_bank_additional_info_field() {
    $options = get_option('payment_settings');
    ?>
    <textarea name="payment_settings[bank_additional_info]" class="regular-text"><?php echo isset($options['bank_additional_info']) ? esc_textarea($options['bank_additional_info']) : ''; ?></textarea>
    <p class="description"><?php _e('Optional: Add any reference or note to help the recipient identify the payment (e.g., invoice number, order ID).', 'reserve-mate'); ?></p>
    <?php
}

function sanitize_payment_settings($input) {
    $sanitized_input = [];
    
    // Stripe
    $sanitized_input['stripe_enabled'] = isset($input['stripe_enabled']) ? 1 : 0;
    $sanitized_input['stripe_secret_key'] = sanitize_text_field($input['stripe_secret_key']);
    $sanitized_input['stripe_public_key'] = sanitize_text_field($input['stripe_public_key']);

    // Paypal
    $sanitized_input['paypal_enabled'] = isset($input['paypal_enabled']) ? 1 : 0;
    $sanitized_input['paypal_client_id'] = sanitize_text_field($input['paypal_client_id']);

    // Apple pay
    // $sanitized_input['apple_pay_enabled'] = isset($input['apple_pay_enabled']) ? 1 : 0;
    // $sanitized_input['apple_pay_merchant_id'] = sanitize_text_field($input['apple_pay_merchant_id']);
    // $sanitized_input['apple_pay_cert_path'] = sanitize_text_field($input['apple_pay_cert_path']);
    // $sanitized_input['apple_pay_key_path'] = sanitize_text_field($input['apple_pay_key_path']);
    
    // Advance payment
    $sanitized_input['advance_payment_type'] = sanitize_text_field($input['advance_payment_type']);
    $sanitized_input['advance_payment_percentage'] = isset($input['advance_payment_percentage']) ? floatval($input['advance_payment_percentage']) : 0;
    $sanitized_input['advance_payment_fixed_amount'] = isset($input['advance_payment_fixed_amount']) ? floatval($input['advance_payment_fixed_amount']) : 0;
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

add_action('admin_init', 'register_payment_settings');