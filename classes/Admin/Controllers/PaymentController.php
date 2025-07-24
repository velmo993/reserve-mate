<?php
namespace ReserveMate\Admin\Controllers;

use ReserveMate\Admin\Views\PaymentViews;

defined('ABSPATH') or die('No direct access!');

class PaymentController {
    private static $initialized = false;

    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
    }
    
    public static function load() {
        self::display_payments_page();
    }
    
    private static function display_payments_page() {
        PaymentViews::render();
    }
}