<?php
namespace ReserveMate\Admin\Controllers;

defined('ABSPATH') or die('No direct access!');

class PageController {
    public static function load_bookings() {
        \ReserveMate\Admin\Controllers\BookingController::handle_requests();
    }

    public static function load_settings() {
        \ReserveMate\Admin\Controllers\SettingController::load();
    }

    public static function load_services() {
        \ReserveMate\Admin\Controllers\ServiceController::handle_requests();
    }

    public static function load_notifications() {
        \ReserveMate\Admin\Controllers\NotificationController::load();
    }

    public static function load_payments() {
        \ReserveMate\Admin\Controllers\PaymentController::load();
    }

    public static function load_ical() {
        \ReserveMate\Admin\Controllers\IcalController::load();
    }

    public static function load_staff() {
        \ReserveMate\Admin\Controllers\StaffController::handle_requests();
    }

    public static function load_tax() {
        \ReserveMate\Admin\Controllers\TaxController::handle_requests();
    }
}