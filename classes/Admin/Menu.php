<?php
namespace ReserveMate\Admin;

use ReserveMate\Admin\Controllers\PageController;
use ReserveMate\Admin\Controllers\SettingController;
use ReserveMate\Admin\Controllers\DashboardController;

defined('ABSPATH') or die('No direct access!');

class Menu {
    public function __construct() {
        add_action('admin_menu', [$this, 'register']);
    }

    public function register() {
        $this->add_main_menu();
        $this->add_submenus();
    }
    
     private function add_main_menu() {
        add_menu_page(
            'Reserve Mate Dashboard',
            'Reserve Mate',
            'manage_options',
            'reserve-mate',
            [DashboardController::class, 'display_dashboard'],
            'dashicons-calendar-alt',
            26
        );
    }

    private function add_submenus() {
        $submenus = [
            [
                'parent' => 'reserve-mate',
                'page_title' => __('Home', 'reserve-mate'),
                'menu_title' => __('Home', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate',
                'callback' => [DashboardController::class, 'display_dashboard']
            ],
            [
                'parent' => 'reserve-mate',
                'page_title' => __('Settings', 'reserve-mate'),
                'menu_title' => __('Settings', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate-settings',
                'callback' => [PageController::class, 'load_settings']
            ],
            [
                'parent' => 'reserve-mate',
                'page_title' => __('Bookings', 'reserve-mate'),
                'menu_title' => __('Bookings', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate-bookings',
                'callback' => [PageController::class, 'load_bookings']
            ],
            [
                'parent' => 'reserve-mate',
                'page_title' => __('Services', 'reserve-mate'),
                'menu_title' => __('Services', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate-services',
                'callback' => [PageController::class, 'load_services']
            ],
            [
                'parent' => 'reserve-mate',
                'page_title' => __('Staff Members', 'reserve-mate'),
                'menu_title' => __('Staff Members', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate-staff',
                'callback' => [PageController::class, 'load_staff']
            ],
            [
                'parent' => 'reserve-mate',
                'page_title' => __('Payments', 'reserve-mate'),
                'menu_title' => __('Payments', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate-payments',
                'callback' => [PageController::class, 'load_payments']
            ],
            [
                'parent' => 'reserve-mate',
                'page_title' => __('Taxes', 'reserve-mate'),
                'menu_title' => __('Taxes', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate-tax',
                'callback' => [PageController::class, 'load_tax']
            ],
            [
                'parent' => 'reserve-mate',
                'page_title' => __('Notifications', 'reserve-mate'),
                'menu_title' => __('Notifications', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate-notifications',
                'callback' => [PageController::class, 'load_notifications']
            ],
            [
                'parent' => 'reserve-mate',
                'page_title' => __('iCal Settings', 'reserve-mate'),
                'menu_title' => __('iCal Settings', 'reserve-mate'),
                'capability' => 'manage_options',
                'slug' => 'reserve-mate-ical',
                'callback' => [PageController::class, 'load_ical']
            ],
        ];

        foreach ($submenus as $submenu) {
            add_submenu_page(
                $submenu['parent'],
                $submenu['page_title'],
                $submenu['menu_title'],
                $submenu['capability'],
                $submenu['slug'],
                $submenu['callback']
            );
        }
    }
}