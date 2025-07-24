<?php
namespace ReserveMate;
defined('ABSPATH') or die('No direct access!');

/**
 * Asset Manager Class
 */
class AssetManager  {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    /**
     * Enqueue frontend styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'booking-form-css',
            RM_PLUGIN_URL . 'assets/css/booking-form.css',
            ['flatpickr-css'],
            '1.0.0'
        );
        
        $this->enqueue_flatpickr_styles();
    }
    
    /**
     * Enqueue Flatpickr styles with customization
     */
    private function enqueue_flatpickr_styles() {
        wp_enqueue_style(
            'flatpickr-styles',
            RM_PLUGIN_URL . 'assets/css/flatpickr-styles.css',
            [],
            '1.0'
        );
        
        $this->add_custom_calendar_css();
    }
    
    /**
     * Add custom calendar CSS variables
     */
    private function add_custom_calendar_css() {
        $options = get_option('rm_style_settings', []);
        
        // Core Styles
        $primary_color = $options['primary_color'] ?? '#3B82F6';
        $text_color = $options['text_color'] ?? '#1F2937';
        $font_family = $options['font_family'] ?? 'inherit';
        $calendar_bg = $options['calendar_bg'] ?? '#fff';
        
        // Day Cell Styles
        $day_bg = $options['day_bg_color'] ?? '#fff';
        $day_border = $options['day_border_color'] ?? '#d2caca';
        $day_selected = $options['day_selected'] ?? '#3B82F6';
        $day_selected_text = $options['day_selected_text'] ?? '#fff';
        $hover_outline = $options['day_hover_outline'] ?? '#000';
        $today_border = $options['today_border_color'] ?? '#959ea9';
        
        // Special States
        $disabled_bg = $options['disabled_day_bg'] ?? '#ec0d0d47';
        $disabled_color = $options['disabled_day_color'] ?? '#676666';
        $prev_next_color = $options['prev_next_month_color'] ?? '#9c9c9c';
        $prev_next_border = $options['prev_next_month_border'] ?? '#e1e1e1';
        
        // Date Ranges
        $start_range_highlight = $options['start_range_highlight'] ?? '#3B82F6';
        $range_highlight = $options['range_highlight'] ?? '#3B82F6';
        $end_range_highlight = $options['end_range_highlight'] ?? '#3B82F6';
        $range_text = $options['range_text_color'] ?? '#fff';
        $arrival_bg = $options['arrival_bg'] ?? 'linear-gradient(to left, #fff 50%, rgb(250 188 188) 50%)';
        $departure_bg = $options['departure_bg'] ?? 'linear-gradient(to right, #fff 50%, rgb(250 188 188) 50%)';
        
        // Navigation
        $nav_hover = $options['nav_hover_color'] ?? $primary_color;
        
        $css_variables = ":root {
            --rm-primary: {$primary_color};
            --rm-text: {$text_color};
            --rm-font: {$font_family};
            --rm-calendar-bg: {$calendar_bg};
            --rm-day-bg: {$day_bg};
            --rm-day-border: {$day_border};
            --rm-day-selected: {$day_selected};
            --rm-day-selected-text: {$day_selected_text};
            --rm-hover-outline: {$hover_outline};
            --rm-today-border: {$today_border};
            --rm-disabled-bg: {$disabled_bg};
            --rm-disabled-color: {$disabled_color};
            --rm-prev-next-color: {$prev_next_color};
            --rm-prev-next-border: {$prev_next_border};
            --rm-start-range-highlight: {$start_range_highlight};
            --rm-range-highlight: {$range_highlight};
            --rm-end-range-highlight: {$end_range_highlight};
            --rm-range-text: {$range_text};
            --rm-arrival-bg: {$arrival_bg};
            --rm-departure-bg: {$departure_bg};
            --rm-nav-hover: {$nav_hover};
        }";
        
        wp_add_inline_style('flatpickr-styles', $css_variables);
    }
}