<?php
namespace ReserveMate\Elementor;

class BookingWidget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'reserve_mate_booking';
    }
    
    public function get_title() {
        return __('Reserve Mate Booking Form', 'reserve-mate');
    }
    
    public function get_icon() {
        return 'eicon-form-horizontal';
    }
    
    public function get_categories() {
        return ['general'];
    }
    
    protected function render() {
        echo do_shortcode('[reserve_mate_booking_form]');
    }
}