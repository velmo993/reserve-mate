<?php
namespace ReserveMate\Admin\Views;

use ReserveMate\Admin\Helpers\Payment;

defined('ABSPATH') or die('No direct access!');

class PaymentViews {
    public static function render() {
        ?>
        <div class="wrap rm-page">
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'reserve-mate') . '</p></div>';
            } ?>
            <h1><?php _e('Payment Settings', 'reserve-mate'); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#online-tab" class="nav-tab nav-tab-active" data-tab="online-tab"><?php _e('Online Payments', 'reserve-mate'); ?></a>
                <a href="#offline-tab" class="nav-tab" data-tab="offline-tab"><?php _e('Offline Payments', 'reserve-mate'); ?></a>
                <a href="#deposit-tab" class="nav-tab" data-tab="deposit-tab"><?php _e('Deposit Payment', 'reserve-mate'); ?></a>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('rm_payment_options_group'); ?>
                <?php do_settings_sections('rm_payment_options_group'); ?>
                <div id="online-tab" class="tab-content active">
                    <h2><?php _e('Online Payment Methods', 'reserve-mate'); ?></h2>
                    
                    <h3><?php _e('Stripe Settings', 'reserve-mate'); ?></h3>
                    <table class="form-table">
                        <?php 
                        // Stripe fields
                        echo '<tr><th>';
                        _e('Enable Stripe', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_stripe_enabled_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Stripe Secret Key', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_stripe_secret_key_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Stripe Public Key', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_stripe_public_key_field();
                        echo '</td></tr>';
                        ?>
                    </table>
                    
                    <h3><?php _e('PayPal Settings', 'reserve-mate'); ?></h3>
                    <table class="form-table">
                        <?php
                        // PayPal fields
                        echo '<tr><th>';
                        _e('Enable PayPal', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_paypal_enabled_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('PayPal Client ID', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_paypal_client_id_field();
                        echo '</td></tr>';
                        ?>
                    </table>
                </div>
                
                <div id="offline-tab" class="tab-content">
                    <h2><?php _e('Offline Payment Methods', 'reserve-mate'); ?></h2>
                    
                    <h3><?php _e('Pay On Arrival', 'reserve-mate'); ?></h3>
                    <table class="form-table">
                        <?php
                        // Pay on arrival
                        echo '<tr><th>';
                        _e('Pay On Arrival', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_pay_on_arrival_enabled_field();
                        echo '</td></tr>';
                        ?>
                    </table>
                    
                    <h3><?php _e('Bank Transfer', 'reserve-mate'); ?></h3>
                    <table class="form-table">
                        <?php
                        // Bank transfer fields
                        echo '<tr><th>';
                        _e('Enable Bank Transfer', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_bank_transfer_enabled_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Bank Account Number', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_bank_account_number_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Bank Account Identifier (IBAN/Routing Number)', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_bank_account_identifier_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Bank SWIFT/BIC Code', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_bank_swift_bic_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Bank Name', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_bank_name_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Recipient Name', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_bank_recipient_name_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Additional Bank Information', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_bank_additional_info_field();
                        echo '</td></tr>';
                        ?>
                    </table>
                </div>
                
                <div id="deposit-tab" class="tab-content">
                    <h2><?php _e('Deposit Payment Settings', 'reserve-mate'); ?></h2>
                    <table class="form-table">
                        <?php
                        // Deposit payment fields
                        echo '<tr><th>';
                        _e('Deposit Payment Type', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_deposit_payment_type_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Deposit Payment Percentage', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_deposit_payment_percentage_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Deposit Payment Fixed Amount', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_deposit_payment_fixed_amount_field();
                        echo '</td></tr>';
                        
                        echo '<tr><th>';
                        _e('Apply Deposit Payment to', 'reserve-mate');
                        echo '</th><td>';
                        Payment::display_deposit_payment_methods_field();
                        echo '</td></tr>';
                        ?>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
    <?php }
    
}