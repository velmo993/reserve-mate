<?php
class ReserveMate_Divi_Module extends ET_Builder_Module {
    
    public $slug = 'reserve_mate_booking';
    public $vb_support = 'on';
    
    protected $module_credits = [
        'module_uri' => '',
        'author' => 'Reserve Mate',
        'author_uri' => '',
    ];
    
    public function init() {
        $this->name = esc_html__('Reserve Mate Booking Form', 'reserve-mate');
    }
    
    public function get_fields() {
        return [];
    }
    
    public function render($attrs, $content, $render_slug) {
        return do_shortcode('[reserve_mate_booking_form]');
    }
}

new ReserveMate_Divi_Module;