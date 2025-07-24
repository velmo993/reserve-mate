<?php
namespace ReserveMate\Admin\Helpers;
use ReserveMate\Admin\Helpers\SecureCredentials;

defined('ABSPATH') or die('No direct access!');

class Payment {

    // public static function display_apple_pay_enabled_field() {
    //     $value = get_option('rm_payment_options')['apple_pay_enabled'] ?? '0';
    //     echo '<input type="checkbox" name="rm_payment_options[apple_pay_enabled]" value="1" ' . checked(1, $value, false) . '> Enable Apple Pay';
    // }
    
    // public static function display_apple_pay_merchant_id_field() {
    //     $value = get_option('rm_payment_options')['apple_pay_merchant_id'] ?? '';
    //     echo '<input type="text" name="rm_payment_options[apple_pay_merchant_id]" value="' . esc_attr($value) . '" />';
    // }
    
    // public static function display_apple_pay_cert_path_field() {
    //     $value = get_option('rm_payment_options')['apple_pay_cert_path'] ?? '';
    //     echo '<input type="text" name="rm_payment_options[apple_pay_cert_path]" value="' . esc_attr($value) . '" />';
    // }
    
    // public static function display_apple_pay_key_path_field() {
    //     $value = get_option('rm_payment_options')['apple_pay_key_path'] ?? '';
    //     echo '<input type="text" name="rm_payment_options[apple_pay_key_path]" value="' . esc_attr($value) . '" />';
    // }
    
    // public static function display_apple_pay_display_name_field() {
    //     $value = get_option('rm_payment_options')['apple_pay_display_name'] ?? '';
    //     echo '<input type="text" name="rm_payment_options[apple_pay_display_name]" value="' . esc_attr($value) . '" />';
    // }
    
    public static function display_stripe_enabled_field() {
        $value = get_option('rm_payment_options')['stripe_enabled'] ?? '0';
        echo '<input type="checkbox" name="rm_payment_options[stripe_enabled]" value="1" ' . checked(1, $value, false) . '> Enable';
        echo '<p class="description">' . __('Enable Stripe as a payment method.', 'reserve-mate') . '</p>';
    }
    
    public static function display_stripe_secret_key_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="password" name="rm_payment_options[stripe_secret_key]" placeholder="sk_live_...4W2Q" 
            value="<?php echo isset($options['stripe_secret_key']) ? self::get_stripe_secret_key() : ''; ?>" class="regular-text">
        <p class="description"><?php _e('Enter your Stripe Secret Key. This is required to process payments via Stripe.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_stripe_public_key_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="password" name="rm_payment_options[stripe_public_key]" placeholder="pk_live_...4W2Q"
            value="<?php echo isset($options['stripe_public_key']) ? esc_attr($options['stripe_public_key']) : ''; ?>" class="regular-text">
        <p class="description"><?php _e('Enter your Stripe Publishable Key. This is used to securely collect payment details on the frontend.', 'reserve-mate'); ?></p>
        <a href="https://dashboard.stripe.com/apikeys" target="_blank">
            <?php _e('Get Stripe Keys →', 'reserve-mate'); ?>
        </a>
        <?php
    }
    
    public static function display_paypal_enabled_field() {
        $value = get_option('rm_payment_options')['paypal_enabled'] ?? '0';
        echo '<input type="checkbox" name="rm_payment_options[paypal_enabled]" value="1" ' . checked(1, $value, false) . '> Enable';
        echo '<p class="description">' . __('Enable PayPal as a payment method.', 'reserve-mate') . '</p>';
    }
    
    public static function display_paypal_client_id_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="password" name="rm_payment_options[paypal_client_id]" placeholder="AbtJXYK..._your_client_id_...xyz"
             value="<?php echo isset($options['paypal_client_id']) ? esc_attr(self::get_paypal_client_id()) : ''; ?>" class="regular-text">
        <p class="description"><?php _e('Enter your PayPal Client ID. This is required to process payments via PayPal.', 'reserve-mate'); ?></p>
        <a href="https://developer.paypal.com/dashboard" target="_blank" style="text-decoration: none;">
            <?php _e('Get PayPal Client ID →', 'reserve-mate'); ?>
        </a>
        <?php
    }
    
    public static function display_deposit_payment_type_field() {
        $options = get_option('rm_payment_options');
        $selected = isset($options['deposit_payment_type']) ? $options['deposit_payment_type'] : 'none';
        ?>
        <select name="rm_payment_options[deposit_payment_type]">
            <option value="none" <?php selected($selected, 'none'); ?>><?php _e('None', 'reserve-mate'); ?></option>
            <option value="percentage" <?php selected($selected, 'percentage'); ?>><?php _e('Percentage', 'reserve-mate'); ?></option>
            <option value="fixed" <?php selected($selected, 'fixed'); ?>><?php _e('Fixed Amount', 'reserve-mate'); ?></option>
        </select>
        <p class="description"><?php _e('Choose the type of deposit payment required (e.g., a percentage of the total or a fixed amount).', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_deposit_payment_percentage_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="number" name="rm_payment_options[deposit_payment_percentage]" value="<?php echo isset($options['deposit_payment_percentage']) ? esc_attr($options['deposit_payment_percentage']) : ''; ?>" class="small-text"> %
        <p class="description"><?php _e('Enter the percentage of the total amount to be paid in deposit.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_deposit_payment_fixed_amount_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="number" name="rm_payment_options[deposit_payment_fixed_amount]" value="<?php echo isset($options['deposit_payment_fixed_amount']) ? esc_attr($options['deposit_payment_fixed_amount']) : ''; ?>" class="small-text">
        <p class="description"><?php _e('Enter the fixed amount to be paid in deposit.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_deposit_payment_methods_field() {
        $options = get_option('rm_payment_options');
        $payment_methods = [
            'stripe' => __('Stripe (Card Payment)', 'reserve-mate'),
            'paypal' => __('PayPal', 'reserve-mate'),
            'bank_transfer' => __('Bank Transfer', 'reserve-mate'),
            'pay_on_arrival' => __('Pay On Arrival', 'reserve-mate')
        ];
        
        echo '<fieldset>';
        echo '<legend class="screen-reader-text">' . __('Apply Deposit Payment to', 'reserve-mate') . '</legend>';
        
        foreach ($payment_methods as $method_key => $method_label) {
            $checked = isset($options['deposit_payment_methods'][$method_key]) ? $options['deposit_payment_methods'][$method_key] : 0;
            echo '<label for="deposit_payment_' . $method_key . '">';
            echo '<input type="checkbox" id="deposit_payment_' . $method_key . '" name="rm_payment_options[deposit_payment_methods][' . $method_key . ']" value="1" ' . checked(1, $checked, false) . '> ';
            echo $method_label . '</label><br>';
        }
        
        echo '</fieldset>';
        echo '<p class="description">' . __('Select which payment methods should use the deposit payment option.', 'reserve-mate') . '</p>';
    }
    
    public static function display_pay_on_arrival_enabled_field() {
        $value = get_option('rm_payment_options')['pay_on_arrival_enabled'] ?? '0';
        echo '<input type="checkbox" name="rm_payment_options[pay_on_arrival_enabled]" value="1" ' . checked(1, $value, false) . '> Enable';
        echo '<p class="description">' . __('Allow clients to pay on arrival (at the appointment).', 'reserve-mate') . '</p>';
    }
    
    public static function display_bank_transfer_enabled_field() {
        $value = get_option('rm_payment_options')['bank_transfer_enabled'] ?? '0';
        echo '<input type="checkbox" name="rm_payment_options[bank_transfer_enabled]" value="1" ' . checked(1, $value, false) . '> Enable';
        echo '<p class="description">' . __('Enable bank transfer as a payment method.', 'reserve-mate') . '</p>';
    }
    
    public static function display_bank_account_number_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="text" name="rm_payment_options[bank_account_number]" value="<?php echo isset($options['bank_account_number']) ? esc_attr($options['bank_account_number']) : ''; ?>" class="regular-text">
        <p class="description"><?php _e('Provide the recipient\'s bank account number. This is typically used for domestic transfers.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_bank_account_identifier_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="text" name="rm_payment_options[bank_account_identifier]" value="<?php echo isset($options['bank_account_identifier']) ? esc_attr($options['bank_account_identifier']) : ''; ?>" class="regular-text">
        <p class="description"><?php _e('Provide your IBAN for international transfers or Routing Number for domestic transfers.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_bank_swift_bic_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="text" name="rm_payment_options[bank_swift_bic]" value="<?php echo isset($options['bank_swift_bic']) ? esc_attr($options['bank_swift_bic']) : ''; ?>" class="regular-text">
        <p class="description"><?php _e('Required for international transfers. This is the unique identifier for the recipient\'s bank.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_bank_name_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="text" name="rm_payment_options[bank_name]" value="<?php echo isset($options['bank_name']) ? esc_attr($options['bank_name']) : ''; ?>" class="regular-text">
        <p class="description"><?php _e('The name of the bank where the recipient holds their account.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_bank_recipient_name_field() {
        $options = get_option('rm_payment_options');
        ?>
        <input type="text" name="rm_payment_options[bank_recipient_name]" value="<?php echo isset($options['bank_recipient_name']) ? esc_attr($options['bank_recipient_name']) : ''; ?>" class="regular-text">
        <p class="description"><?php _e('The name of the person or company receiving the funds. Must match the name on the bank account.', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function display_bank_additional_info_field() {
        $options = get_option('rm_payment_options');
        ?>
        <textarea name="rm_payment_options[bank_additional_info]" class="regular-text"><?php echo isset($options['bank_additional_info']) ? esc_textarea($options['bank_additional_info']) : ''; ?></textarea>
        <p class="description"><?php _e('Optional: Add any reference or note to help the recipient identify the payment (e.g., invoice number, order ID).', 'reserve-mate'); ?></p>
        <?php
    }
    
    public static function get_stripe_secret_key() {
        $options = get_option('rm_payment_options');
        if (empty($options['stripe_secret_key'])) {
            return '';
        }
    
        return SecureCredentials::decrypt($options['stripe_secret_key'], SecureCredentials::STRIPE);
    }
    
    public static function get_paypal_client_id() {
        $options = get_option('rm_payment_options');
        
        if (empty($options['paypal_client_id'])) {
            return '';
        }
        
        return SecureCredentials::decrypt($options['paypal_client_id'], SecureCredentials::PAYPAL);
    }
    
    public static function calculate_deposit_amount($payment_options, $total_cost) {
        $deposit_type = $payment_options['deposit_payment_type'] ?? '0';
        $deposit_amount = 0;
        
        if ($deposit_type === 'percentage') {
            $percentage = floatval($payment_options['deposit_payment_percentage'] ?? 0);
            $deposit_amount = ($total_cost * $percentage) / 100;
        } elseif ($deposit_type === 'fixed') {
            $deposit_amount = floatval($payment_options['deposit_payment_fixed_amount'] ?? 0);
        }
        
        return min($deposit_amount, $total_cost);
    }
    
}