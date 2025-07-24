<?php
/*
Plugin Name: Reserve Mate
Plugin URI: https://github.com/velmo993/reserve-mate
Description: A plugin for managing reservations.
Version: 1.0.4
Author: velmoweb.com
Author URI: https://example.com
License: GPL2
* Text Domain:       reserve-mate
* Domain Path:       /assets/languages
*/

defined('ABSPATH') or die('No direct access!');

if (!defined('RM_PLUGIN_URL')) {
    define('RM_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('RM_PLUGIN_PATH')) {
    define('RM_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('RM_PLUGIN_SLUG')) {
    define('RM_PLUGIN_SLUG', 'reserve-mate');
}

require_once RM_PLUGIN_PATH . 'vendor/autoload.php';

$GLOBALS['reserve_mate_google_calendar'] = new ReserveMate\Admin\Helpers\GoogleCalendar();

function reserve_mate_gcal() {
    return $GLOBALS['reserve_mate_google_calendar'];
}

use ReserveMate\ReserveMate;
use ReserveMate\Admin\Menu;

register_activation_hook(__FILE__, ['ReserveMate\ReserveMate', 'activate']);
register_deactivation_hook(__FILE__, ['ReserveMate\ReserveMate', 'deactivate']);

// Initialize the plugin
ReserveMate::get_instance();

// Initialize admin menu
new Menu();

reserve_mate_gcal();

// if (class_exists('ReserveMate\Admin\Helpers\GoogleCalendar')) {
//     $google_calendar_sync = new ReserveMate\Admin\Helpers\GoogleCalendarSync();
// }
