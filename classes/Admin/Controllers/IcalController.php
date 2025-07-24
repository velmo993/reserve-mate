<?php
namespace ReserveMate\Admin\Controllers;
use ReserveMate\Admin\Views\ICalViews;

defined('ABSPATH') or die('No direct access!');

class IcalController {
    public static function load() {
        self::display_ical_page();
    }
    
    private static function display_ical_page() {
        $export_url = site_url('?download_ical=1');
        $data = [
            'export_url' => $export_url
        ];
        
        ICalViews::render($data);
    }
}