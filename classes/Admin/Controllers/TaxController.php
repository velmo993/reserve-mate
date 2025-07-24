<?php
namespace ReserveMate\Admin\Controllers;
use ReserveMate\Admin\Views\TaxViews;
use ReserveMate\Admin\Helpers\Tax;

defined('ABSPATH') or die('No direct access!');

class TaxController {
    public static function handle_requests() {
        // Handle form submissions first
        self::handle_form_submissions();
        
        // Then display the appropriate view
        self::display_tax_page();
    }

    private static function handle_form_submissions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        if (isset($_POST['add_tax'])) {
            self::handle_tax_save();
        }

        if (isset($_POST['delete_tax']) && isset($_POST['delete_nonce'])) {
            self::handle_tax_delete($_POST['delete_tax']);
        }
        
    }


    private static function handle_tax_save() {
        // Validate nonce
        if (!isset($_POST['tax_nonce']) || 
            !wp_verify_nonce($_POST['tax_nonce'], 'save_tax_nonce')) {
            wp_die('Security check failed');
        }

        $data = [
            'tax_name' => sanitize_text_field($_POST['tax_name']),
            'tax_rate' => floatval($_POST['tax_rate']),
            'tax_type' => sanitize_text_field($_POST['tax_type'])
        ];

        Tax::add_tax($data['tax_name'], $data['tax_rate'], $data['tax_type']);

        // Edit missing

        wp_redirect(admin_url('admin.php?reserve-mate-taxes'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Tax created.', 'reserve-mate') . '</p></div>';
    }

    private static function handle_tax_delete() {
        $tax_id = isset($_GET['delete_tax']) ? intval($_GET['delete_tax']) : (isset($_POST['delete_tax']) ? intval($_POST['delete_tax']) : 0);
        $nonce = isset($_GET['delete_nonce']) ? $_GET['delete_nonce'] : (isset($_POST['delete_nonce']) ? $_POST['delete_nonce'] : '');
    
        if (!$tax_id || !wp_verify_nonce($nonce, 'delete_tax')) {
            wp_die('Security check failed');
        }
    
        Tax::delete_tax($tax_id);
        
        wp_redirect(admin_url('admin.php?reserve-mate-taxes'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Tax deleted.', 'reserve-mate') . '</p></div>';
    }

    private static function display_tax_page() {
        $taxes = Tax::get_taxes();
        $view_data = [
            'taxes' => $taxes
        ];

        TaxViews::render($view_data);
    }

}