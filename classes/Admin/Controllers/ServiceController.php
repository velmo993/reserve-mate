<?php
namespace ReserveMate\Admin\Controllers;
use ReserveMate\Admin\Views\ServiceViews;
use ReserveMate\Admin\Helpers\Service;

defined('ABSPATH') or die('No direct access!');

class ServiceController {
    public static function handle_requests() {
        // Handle form submissions first
        self::handle_form_submissions();
        
        // Then display the appropriate view
        self::display_services_page();
    }

    private static function handle_form_submissions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (isset($_POST['save_admin_service'])) {
            self::handle_service_save();
        }
        
        if (isset($_POST['delete']) && isset($_POST['delete_nonce']) && !isset($_POST['bulk_action'])) {
            self::handle_service_delete();
        }
        
        if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
            self::handle_bulk_delete();
        }
        
        if (isset($_POST['toggle_status'])) {
            self::handle_status_toggle();
        }
    }

    private static function handle_service_save() {
        // Validate nonce
        if (!isset($_POST['admin_service_nonce']) || 
            !wp_verify_nonce($_POST['admin_service_nonce'], 'save_admin_service')) {
            wp_die('Security check failed');
        }

        // Process and sanitize data
        $data = [
            'name'              => sanitize_text_field($_POST['name']),
            'description'       => sanitize_textarea_field($_POST['description']),
            'duration'          => isset($_POST['duration']) ? intval($_POST['duration']) : 0,
            'price'             => isset($_POST['price']) ? floatval($_POST['price']) : 0,
            'max_capacity'      => isset($_POST['max_capacity']) ? intval($_POST['max_capacity']) : 0,
            'allow_multiple'    => isset($_POST['allow_multiple']) ? 1 : 0,
            'time_slots'        => isset($_POST['time_slots']) ? sanitize_text_field($_POST['time_slots']) : '',
            'additional_notes'  => isset($_POST['additional_notes']) ? sanitize_textarea_field($_POST['additional_notes']) : '',
        ];

        // Save or update service
        if (isset($_POST['edit'])) {
            Service::save_service($data, intval($_POST['edit']));
        } else {
            Service::save_service($data, null);
        }
        
        wp_redirect(admin_url('admin.php?reserve-mate-services'));
        echo '<div class="notice notice-success is-dismissible"><p>' 
                    . __('Service created.', 'reserve-mate') . '</p></div>';
    }

    private static function handle_service_delete() {
        if (!isset($_POST['delete_nonce']) || 
            !wp_verify_nonce($_POST['delete_nonce'], 'delete_service')) {
            wp_die('Security check failed');
        }

        $service_id = isset($_POST['delete']) ? intval($_POST['delete']) : 0;

        Service::delete_service(intval($service_id));
                
        wp_redirect(admin_url('admin.php?reserve-mate-services'));
        echo '<div class="notice notice-success is-dismissible"><p>' 
                    . __('Service removed.', 'reserve-mate') . '</p></div>';
        
    }
    
    private static function handle_bulk_delete() {
        if (!isset($_POST['bulk_nonce']) || 
            !wp_verify_nonce($_POST['bulk_nonce'], 'bulk_service_action')) {
            wp_die('Security check failed');
        }

        if (isset($_POST['service_ids']) && is_array($_POST['service_ids'])) {
            foreach ($_POST['service_ids'] as $service_id) {
                Service::delete_service(intval($service_id));
            }
        }

        wp_redirect(admin_url('admin.php?reserve-mate-services'));
        echo '<div class="notice notice-success is-dismissible"><p>' 
                    . __('Services removed.', 'reserve-mate') . '</p></div>';
    }

    private static function display_services_page() {
        $per_page = 10;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $services = Service::get_services();
        
        $view_data = [
            'services' => $services,
            'editing_service' => isset($_GET['edit']) ? Service::get_service(intval($_GET['edit'])) : null,
            'currency_symbol' => get_currency(),
            'per_page' => $per_page,
            'current_page' => $current_page,
            'total_items' => $services ? count($services) : null,
        ];

        ServiceViews::render($view_data);
    }


}