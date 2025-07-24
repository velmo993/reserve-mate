<?php
namespace ReserveMate\Admin\Views;

defined('ABSPATH') or die('No direct access!');

require_once(RM_PLUGIN_PATH . 'includes/admin/sanitization.php');
require_once(RM_PLUGIN_PATH . 'includes/admin/tabs/general-tab.php');
require_once(RM_PLUGIN_PATH . 'includes/admin/tabs/bookings-tab.php');
require_once(RM_PLUGIN_PATH . 'includes/admin/tabs/services-tab.php');
require_once(RM_PLUGIN_PATH . 'includes/admin/tabs/calendar-tab.php');
require_once(RM_PLUGIN_PATH . 'includes/admin/tabs/forms-tab.php');
require_once(RM_PLUGIN_PATH . 'includes/admin/tabs/styles-tab.php');

class SettingViews {
    public static function render() {
        ?>
        <div class="wrap rm-page">
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'reserve-mate') . '</p></div>';
            } ?>
            <h1><?php _e('Booking System Settings', 'reserve-mate'); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#general-tab" class="nav-tab nav-tab-active" data-tab="general-tab"><?php _e('General', 'reserve-mate'); ?></a>
                <a href="#bookings-tab" class="nav-tab" data-tab="bookings-tab"><?php _e('Booking Settings', 'reserve-mate'); ?></a>
                <a href="#calendar-tab" class="nav-tab" data-tab="calendar-tab"><?php _e('Google Calendar', 'reserve-mate'); ?></a>
                <a href="#services-tab" class="nav-tab" data-tab="services-tab"><?php _e('Service Settings', 'reserve-mate'); ?></a>
                <a href="#forms-tab" class="nav-tab" data-tab="forms-tab"><?php _e('Forms', 'reserve-mate'); ?></a>
                <a href="#styles-tab" class="nav-tab" data-tab="styles-tab"><?php _e('Datepicker Styles', 'reserve-mate'); ?></a>
            </div>
            
            <!-- General Settings Form -->
            <div id="general-tab" class="tab-content active">
                <form method="post" action="options.php" id="general-settings-form">
                    <?php settings_fields('rm_general_options_group'); ?>
                    
                        <h2><?php _e('General Settings', 'reserve-mate'); ?></h2>
                        <table class="form-table">
                            <?php 
                            // Currency field
                            echo '<tr><th>';
                            _e('Currency', 'reserve-mate');
                            echo '</th><td>';
                            display_currency_field();
                            echo '</td></tr>';
                            
                            echo '<tr><th>';
                            _e('Timezone', 'reserve-mate');
                            echo '</th><td>';
                            display_calendar_timezones();
                            echo '</td></tr>';
                            
                            echo '<tr><th>';
                            _e('Date and Time format', 'reserve-mate');
                            echo '</th><td>';
                            display_date_format();
                            echo '</td></tr>';
                            
                            echo '<tr><th>';
                            _e('Locale', 'reserve-mate');
                            echo '</th><td>';
                            display_calendar_locale();
                            echo '</td></tr>';
                            ?>
                        </table>
                        <?php submit_button(__('Save Settings', 'reserve-mate'), 'primary', 'save_general_tab'); ?> 
                </form>
            </div>

            <!-- Booking Settings Form -->
            <div id="bookings-tab" class="tab-content">
                <form method="post" action="options.php" id="booking-settings-form">
                    <?php settings_fields('rm_booking_options_group'); ?>
                    
                        <h2><?php _e('Booking Settings', 'reserve-mate'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Enable Booking Approval', 'reserve-mate'); ?></th>
                                <td><?php display_enable_booking_approval(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('First Available Time', 'reserve-mate'); ?></th>
                                <td><?php display_booking_min_time(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Last Available Time', 'reserve-mate'); ?></th>
                                <td><?php display_booking_max_time(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Booking Interval (Minutes)', 'reserve-mate'); ?></th>
                                <td><?php display_booking_interval(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('First Available Day', 'reserve-mate'); ?></th>
                                <td><?php display_booking_min_date(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Last Available Day', 'reserve-mate'); ?></th>
                                <td><?php display_booking_max_date(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Buffer Time (Minutes)', 'reserve-mate'); ?></th>
                                <td><?php display_buffer_time(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Minimum Lead Time (Minutes)', 'reserve-mate'); ?></th>
                                <td><?php display_minimum_lead_time(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Time Slot Format', 'reserve-mate'); ?></th>
                                <td><?php self::display_time_format_field(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Booking Limits', 'reserve-mate'); ?></th>
                                <td><?php display_booking_limits(); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Disable Dates', 'reserve-mate'); ?></th>
                                <td><?php display_disable_dates(); ?></td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Settings', 'reserve-mate'), 'primary', 'save_bookings_tab'); ?> 
                </form>
            </div>

            <!-- Google Calendar Tab -->
            <div id="calendar-tab" class="tab-content">
                <h2><?php _e('Google Calendar Integration', 'reserve-mate'); ?></h2>
                
                <div class="calendar-integration-wrapper">
                    <!-- Main Connection Area -->
                    <?php display_google_calendar_auth(); ?>
                    
                    <!-- Calendar Selection (only shown when connected) -->
                    <?php if (reserve_mate_gcal()->is_authorized()): ?>
                        <div class="calendar-selection-section">
                            <h3><?php _e('Calendar Settings', 'reserve-mate'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e('Select Calendar', 'reserve-mate'); ?></th>
                                    <td><?php display_calendar_selection_field(); ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Help Section -->
                    <div class="calendar-help-section">
                        <h3><?php _e('How it works', 'reserve-mate'); ?></h3>
                        <div class="help-grid">
                            <div class="help-item">
                                <span class="dashicons dashicons-admin-links"></span>
                                <h4><?php _e('1. Connect', 'reserve-mate'); ?></h4>
                                <p><?php _e('Click the connect button to link your Google Calendar account securely.', 'reserve-mate'); ?></p>
                            </div>
                            <div class="help-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <h4><?php _e('2. Choose Calendar', 'reserve-mate'); ?></h4>
                                <p><?php _e('Select which calendar you want to use for your bookings.', 'reserve-mate'); ?></p>
                            </div>
                            <div class="help-item">
                                <span class="dashicons dashicons-update"></span>
                                <h4><?php _e('3. Auto-Sync', 'reserve-mate'); ?></h4>
                                <p><?php _e('All new bookings will automatically appear in your Google Calendar.', 'reserve-mate'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Settings Form -->
            <div id="services-tab" class="tab-content">
                <form method="post" action="options.php" id="service-settings-form">
                    <?php settings_fields('rm_service_options_group'); ?>
                        <h2><?php _e('Service Settings', 'reserve-mate'); ?></h2>
                        <table class="form-table">
                            <?php
                            echo '<tr><th>';
                            _e('Max Service Number', 'reserve-mate');
                            echo '</th><td>';
                            display_max_selectable_services();
                            echo '</td></tr>';
                            ?>
                        </table>
                        <?php submit_button(__('Save Settings', 'reserve-mate'), 'primary', 'save_services_tab'); ?> 
                    
                </form>
            </div>
            
            <!-- Form Settings Form -->
            <div id="forms-tab" class="tab-content">
                <form method="post" action="options.php" id="form-settings-form">
                    <?php settings_fields('rm_form_options_group'); ?>
                        <h2><?php _e('Form Settings', 'reserve-mate'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Customize Form Fields', 'reserve-mate'); ?></th>
                                <td><?php display_form_fields(); ?></td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Settings', 'reserve-mate'), 'primary', 'save_forms_tab'); ?> 
                </form>
            </div>

            <!-- Style Settings Form -->
            <div id="styles-tab" class="tab-content">
                <form method="post" action="options.php" id="style-settings-form">
                    <?php settings_fields('rm_style_options_group'); ?>
                        <table class="form-table">
                            <!-- Layout Settings -->
                            <tr>
                                <th colspan="2">
                                    <h3 style="margin:0"><?php _e('Datepicker Layout', 'reserve-mate'); ?></h3>
                                </th>
                            </tr>
                            <tr>
                                <th><?php _e('Display Type', 'reserve-mate'); ?></th>
                                <td><?php display_calendar_display_type_field(); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Theme Preset', 'reserve-mate'); ?></th>
                                <td><?php display_calendar_theme_field(); ?></td>
                            </tr>
                            
                            <!-- Color Scheme -->
                            <tr>
                                <th colspan="2">
                                    <h3 style="margin:1em 0 0 0"><?php _e('Datepicker Colors', 'reserve-mate'); ?></h3>
                                </th>
                            </tr>
                            <tr>
                                <th><?php _e('Primary Color', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('primary_color', '#4CAF50', 'Buttons, highlights'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Text Color', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('text_color', '#333', 'Main text color'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Datepicker Background', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('calendar_bg', '#fff', 'Main container background'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Day Background', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('day_bg_color', '#fff'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Day Border', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('day_border_color', '#d2caca'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Hover Outline', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('hover_outline', '#000'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Disabled Day Background', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('disabled_day_bg', '#ec0d0d47'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Disabled Day Color', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('disabled_day_color', '#676666'); ?></td>
                            </tr>
                            
                            <!-- Special States -->
                            <tr>
                                <th colspan="2">
                                    <h3 style="margin:1em 0 0 0"><?php _e('Datepicker Special States', 'reserve-mate'); ?></h3>
                                </th>
                            </tr>
                            <tr>
                                <th><?php _e('Disabled Day', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('disabled_bg', 'rgba(236,13,13,0.28)'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Today\'s Border', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('today_border', '#959ea9'); ?></td>
                            </tr>
                            
                            <!-- Date Range Styling -->
                            <tr>
                                <th colspan="2">
                                    <h3 style="margin:1em 0 0 0"><?php _e('Datepicker Date Ranges', 'reserve-mate'); ?></h3>
                                </th>
                            </tr>
                            <tr>
                                <th><?php _e('Range Highlight Start', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('start_range_highlight', '#07c66594'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Range Highlight', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('range_highlight', '#07c66594'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Range Highlight End', 'reserve-mate'); ?></th>
                                <td><?php display_color_field_wrapper('end_range_highlight', '#07c66594'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Arrival Day', 'reserve-mate'); ?></th>
                                <td><?php display_gradient_field_wrapper('arrival_bg', 'linear-gradient(to left, #fff 50%, rgb(250 188 188) 50%)'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Departure Day', 'reserve-mate'); ?></th>
                                <td><?php display_gradient_field_wrapper('departure_bg', 'linear-gradient(to right, #fff 50%, rgb(250 188 188) 50%)'); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Datepicker Font Family', 'reserve-mate'); ?></th>
                                <td><?php display_font_family_field(); ?></td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Settings', 'reserve-mate'), 'primary', 'save_styles_tab'); ?> 
                </form>
            </div>
        </div>
        <?php
    }
    
    public static function display_time_format_field() {
        $options = get_option('rm_booking_options');
        $time_format = isset($options['time_display_format']) ? $options['time_display_format'] : 'range';
        ?>
        <select name="rm_booking_options[time_display_format]">
            <option value="range" <?php selected($time_format, 'range'); ?>><?php _e('Show range (8:00 - 9:00)', 'reserve-mate'); ?></option>
            <option value="single" <?php selected($time_format, 'single'); ?>><?php _e('Show start time only (8:00)', 'reserve-mate'); ?></option>
        </select>
        <p class="description">
            <?php _e('Choose how time slots should be displayed in the booking interface.', 'reserve-mate'); ?>
        </p>
        <?php
    }
    
}



