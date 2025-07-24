<?php
namespace ReserveMate\Admin\Controllers;
use ReserveMate\Admin\Views\StaffViews;
use ReserveMate\Admin\Helpers\Staff;
use ReserveMate\Admin\Helpers\Service;

defined('ABSPATH') or die('No direct access!');

class StaffController {
    public static function handle_requests() {
        // Handle form submissions first
        self::handle_form_submissions();
        
        // Then display the appropriate view
        self::display_staff_page();
    }

    private static function handle_form_submissions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (isset($_POST['save_staff'])) {
            self::handle_staff_save();
        }
        
        if (isset($_POST['delete']) && isset($_POST['delete_nonce']) && !isset($_POST['bulk_action'])) {
            self::handle_staff_delete();
        }
        
        if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete') {
            self::handle_bulk_delete();
        }   
    }

    private static function handle_staff_save() {
        if (!isset($_POST['staff_nonce']) || 
            !wp_verify_nonce($_POST['staff_nonce'], 'save_staff_nonce')) {
            wp_die('Security check failed');
        }

        $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
        
        $staff_data = [
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'bio' => sanitize_textarea_field($_POST['bio']),
            'status' => sanitize_text_field($_POST['status']),
            'profile_image' => $_POST['profile_image'],
        ];
        
        $working_hours = [];
        if (isset($_POST['working_hours']) && is_array($_POST['working_hours'])) {
            foreach ($_POST['working_hours'] as $day => $periods) {
                $day_enabled = false;
                
                foreach ($periods as $period) {
                    if (!empty($period['start']) && !empty($period['end'])) {
                        $day_enabled = true;
                        $working_hours[$day][] = [
                            'start' => sanitize_text_field($period['start']),
                            'end' => sanitize_text_field($period['end'])
                        ];
                    }
                }
                
                if (!$day_enabled) {
                    unset($working_hours[$day]);
                }
            }
        }
        
        $staff_data['working_hours'] = $working_hours;
        $result = Staff::save_staff_member($staff_data, $staff_id);
        
        if ($result) {
            $staff_id = $staff_id ?: $result;
            
            global $wpdb;
            $wpdb->delete(
                $wpdb->prefix . 'reservemate_staff_services',
                ['staff_id' => $staff_id]
            );
            
            if (isset($_POST['services']) && is_array($_POST['services'])) {
                foreach ($_POST['services'] as $service_id) {
                    Staff::assign_service_to_staff($staff_id, intval($service_id));
                }
            }
            add_settings_error('reservemate_staff', 'save_success', 'Staff member saved successfully.', 'success');
        } else {
            add_settings_error('reservemate_staff', 'save_error', 'Error saving staff member.', 'error');
        }
    }
        
    private static function handle_staff_delete() {
        $staff_id = isset($_GET['delete']) ? intval($_GET['delete']) : (isset($_POST['delete']) ? intval($_POST['delete']) : 0);
        $nonce = isset($_GET['delete_nonce']) ? $_GET['delete_nonce'] : (isset($_POST['delete_nonce']) ? $_POST['delete_nonce'] : '');
    
        if (!$staff_id || !wp_verify_nonce($nonce, 'delete_staff')) {
            wp_die('Security check failed');
        }
    
        Staff::delete_staff_member($staff_id);
        
        echo '<div class="notice notice-success is-dismissible"><p>' 
                    . __('Staff member deleted.', 'reserve-mate') . '</p></div>';
    }

    private static function display_staff_page() {
        $editing_staff_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
        $editing_staff = $editing_staff_id ? Staff::get_staff_member($editing_staff_id) : null;
        $staff_members = Staff::get_staff_members();
        $all_services = Service::get_services();
        $working_hours = [];
        
        if (!empty($editing_staff['working_hours'])) {
            $db_hours = $editing_staff['working_hours'];
            
            // Define reverse mapping (DB 1-7 to form 0-6)
            $day_mapping = [
                7 => 0, // Sunday
                1 => 1, // Monday
                2 => 2, // Tuesday
                3 => 3, // Wednesday
                4 => 4, // Thursday
                5 => 5, // Friday
                6 => 6  // Saturday
            ];
            
            foreach ($db_hours as $db_day => $periods) {
                if (isset($day_mapping[$db_day])) {
                    $form_day = $day_mapping[$db_day];
                    $working_hours[$form_day] = $periods;
                }
            }
        }

        $view_data = [
            'editing_staff' => $editing_staff,
            'staff_id' => $editing_staff_id, // Use the editing_staff_id here
            'all_services' => $all_services,
            'staff_members' => $staff_members
        ];
    
        StaffViews::render($view_data);
    }

}