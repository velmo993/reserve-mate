<?php
namespace ReserveMate\Admin\Controllers;

use ReserveMate\Admin\Views\NotificationViews;

defined('ABSPATH') or die('No direct access!');

class NotificationController {
    public static function load() {
        self::display_notifications_page();
    }
    
    private static function display_notifications_page() {
        NotificationViews::render();
    }
}