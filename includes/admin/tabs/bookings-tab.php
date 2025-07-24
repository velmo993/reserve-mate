<?php 
defined('ABSPATH') or die('No direct access!');

function display_enable_booking_approval() {
    $options = get_option('rm_booking_options');
    $enabled = isset($options['enable_booking_approval']) ? $options['enable_booking_approval'] : 'no';

    echo '<select name="rm_booking_options[enable_booking_approval]" id="enable_booking_approval">';
    echo '<option value="no" ' . selected($enabled, 'no', false) . '>' . __('No', 'reserve-mate') . '</option>';
    echo '<option value="yes" ' . selected($enabled, 'yes', false) . '>' . __('Yes', 'reserve-mate') . '</option>';
    echo '</select>';
    ?>
    <p class="description">
        <?php _e('When enabled, bookings will require manual approval before being confirmed.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_booking_min_time() {
    $options = get_option('rm_booking_options');
    $min_time = isset($options['booking_min_time']) ? $options['booking_min_time'] : '08:00';

    echo '<select name="rm_booking_options[booking_min_time]" id="booking_min_time">';
    generate_time_options($min_time);
    echo '</select>';
    ?>
    <p class="description">
        <?php _e('Start of the first time interval.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_booking_max_time() {
    $options = get_option('rm_booking_options');
    $max_time = isset($options['booking_max_time']) ? $options['booking_max_time'] : '17:00';

    echo '<select name="rm_booking_options[booking_max_time]" id="booking_max_time">';
    generate_time_options($max_time);
    echo '</select>';
    ?>
    <p class="description">
        <?php _e('End of the last time interval.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_booking_interval() {
    $options = get_option('rm_booking_options');
    $interval = isset($options['booking_interval']) ? $options['booking_interval'] : '30';
    ?>
    <input type="number" name="rm_booking_options[booking_interval]" value="<?php echo esc_attr($interval); ?>" min="5" step="1">
    <p class="description">
        <?php _e('Default booking interval in minutes (e.g., 15, 30, 60).<br />Service durations override this value.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_buffer_time() {
    $options = get_option('rm_booking_options');
    $buffer_time = isset($options['buffer_time']) ? $options['buffer_time'] : '10';
    ?>
    <input type="number" name="rm_booking_options[buffer_time]" value="<?php echo esc_attr($buffer_time); ?>" min="0" step="1">
    <p class="description">
        <?php _e('Add buffer time / break duration (in minutes) between time slots. For example, 5 minutes.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_minimum_lead_time() {
    $options = get_option('rm_booking_options');
    $minimum_lead_time = isset($options['minimum_lead_time']) ? $options['minimum_lead_time'] : '60'; // Default to 60 minutes
    ?>
    <input type="number" name="rm_booking_options[minimum_lead_time]" value="<?php echo esc_attr($minimum_lead_time); ?>" min="0" step="5">
    <p class="description">
        <?php _e('Minimum time required before a booking can be made (in minutes). For example, 60 minutes means customers can\'t book within 1 hour of current time.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_booking_min_date() {
    $options = get_option('rm_booking_options');
    $min_date = isset($options['booking_min_date']) ? $options['booking_min_date'] : date('Y-m-d');
    ?>
    <input type="date" name="rm_booking_options[booking_min_date]" value="<?php echo esc_attr($min_date); ?>">
    <p class="description">
        <?php _e('First available date for booking. Leave empty for default (today).', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_booking_max_date() {
    $options = get_option('rm_booking_options');
    $booking_mode = isset($options['booking_mode']) ? $options['booking_mode'] : 'rolling';
    ?>
    
    <div style="margin-bottom: 12px;">
        <label>
            <input type="radio" name="rm_booking_options[booking_mode]" value="fixed" <?php checked($booking_mode, 'fixed'); ?>>
            <?php _e('Fixed End Date', 'reserve-mate'); ?>
        </label>
        <label style="margin-left: 15px;">
            <input type="radio" name="rm_booking_options[booking_mode]" value="rolling" <?php checked($booking_mode, 'rolling'); ?>>
            <?php _e('Rolling Window (X days from today)', 'reserve-mate'); ?>
        </label>
    </div>

    <div id="fixed-date-field">
        <input type="date" name="rm_booking_options[booking_max_date]" value="<?php echo esc_attr($options['booking_max_date'] ?? ''); ?>">
        <p class="description"><?php _e('Fixed last available date.', 'reserve-mate'); ?></p>
    </div>
    
    <div id="rolling-days-field">
        <input id="rolling-days-field-input" type="number" name="rm_booking_options[booking_max_days_ahead]" value="<?php echo esc_attr($options['booking_max_days_ahead'] ?? '60'); ?>" min="1" placeholder="60">
        <p class="description"><?php _e('Rolling window days ahead.', 'reserve-mate'); ?></p>
    </div>

    <?php
}

function display_booking_limits() {
    $options = get_option('rm_booking_options');
    $limits = $options['limits'] ?? [
        'day' => ['max' => 1, 'enabled' => false],
        'week' => ['max' => 3, 'enabled' => false],
        'month' => ['max' => 10, 'enabled' => false]
    ];
    ?>
    <div class="limit-rule">
        <h4><?php _e('Maximum Bookings Per Customer', 'reserve-mate'); ?></h4>
        
        <!-- Daily Limit -->
        <label>
            <input type="checkbox" name="rm_booking_options[limits][day][enabled]" <?php checked($limits['day']['enabled'] ?? false); ?>>
            <?php _e('Per Day:', 'reserve-mate'); ?>
            <input type="number" name="rm_booking_options[limits][day][max]" value="<?php echo esc_attr($limits['day']['max']); ?>">
        </label><br>

        <!-- Weekly Limit -->
        <label>
            <input type="checkbox" name="rm_booking_options[limits][week][enabled]" <?php checked($limits['week']['enabled'] ?? false); ?>>
            <?php _e('Per Week:', 'reserve-mate'); ?>
            <input type="number" name="rm_booking_options[limits][week][max]" value="<?php echo esc_attr($limits['week']['max']); ?>">
        </label><br>

        <!-- Monthly Limit -->
        <label>
            <input type="checkbox" name="rm_booking_options[limits][month][enabled]" <?php checked($limits['month']['enabled'] ?? false); ?>>
            <?php _e('Per Month:', 'reserve-mate'); ?>
            <input type="number" name="rm_booking_options[limits][month][max]" value="<?php echo esc_attr($limits['month']['max']); ?>">
        </label>
    </div>
    <?php
}

function display_disable_dates() {
    $options = get_option('rm_booking_options', []);
    $disabled_dates = isset($options['disabled_dates']) ? $options['disabled_dates'] : [];
    ?>
    
    <div>
        <button id="disabled-dates-btn" type="button">
            <span class="dashicons dashicons-arrow-down"></span>
        </button>
        <p class="description">
            <?php _e('Set dates or time periods that are not available for booking.', 'reserve-mate'); ?>
        </p>
    </div>
    <br />
    
    <div class="disabled-dates-container hidden">
        <div id="disabled-dates-rules">
            <?php
            if (!empty($disabled_dates)) {
                foreach ($disabled_dates as $index => $rule) {
                    display_disabled_date_rule($index, $rule);
                }
            }
            ?>
        </div>
        
        <button type="button" id="add-disabled-date-rule" class="button">
            <?php _e('Add New Rule', 'reserve-mate'); ?>
        </button>
        
    </div>
    <?php
}

function display_disabled_date_rule($index, $rule = []) {
    $rule = wp_parse_args($rule, [
        'type' => 'specific',
        'date' => '',
        'start_date' => '',
        'end_date' => '',
        'start_time' => '',
        'end_time' => '',
        'days' => [],
        'repeat_yearly' => false,
    ]);
    ?>
    <div class="disabled-date-rule" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
        <div style="margin-bottom: 10px;">
            <label>
                <select name="rm_booking_options[disabled_dates][<?php echo $index; ?>][type]" class="disabled-date-type">
                    <option value="specific" <?php selected($rule['type'], 'specific'); ?>><?php _e('Specific Date', 'reserve-mate'); ?></option>
                    <option value="range" <?php selected($rule['type'], 'range'); ?>><?php _e('Date Range', 'reserve-mate'); ?></option>
                    <option value="weekly" <?php selected($rule['type'], 'weekly'); ?>><?php _e('Weekly Pattern', 'reserve-mate'); ?></option>
                    <option value="time" <?php selected($rule['type'], 'time'); ?>><?php _e('Time Interval', 'reserve-mate'); ?></option>
                </select>
            </label>
            
            <button type="button" class="button remove-disabled-date-rule" style="float: right;">
                <?php _e('Remove', 'reserve-mate'); ?>
            </button>
        </div>
        
        <div class="disabled-date-options">
            <!-- Specific Date -->
            <div class="disabled-date-specific" style="<?php echo $rule['type'] !== 'specific' ? 'display: none;' : ''; ?>">
                <label>
                    <?php _e('Date:', 'reserve-mate'); ?>
                    <input type="text" class="datepicker" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][date]" value="<?php echo esc_attr($rule['date']); ?>">
                </label>
                <label style="margin-left: 10px;">
                    <input type="checkbox" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][repeat_yearly]" value="1" <?php checked($rule['repeat_yearly'], 1); ?>>
                    <?php _e('Repeat yearly', 'reserve-mate'); ?>
                </label>
            </div>
            
            <!-- Date Range -->
            <div class="disabled-date-range" style="<?php echo $rule['type'] !== 'range' ? 'display: none;' : ''; ?>">
                <label>
                    <?php _e('From:', 'reserve-mate'); ?>
                    <input type="text" class="datepicker" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][start_date]" value="<?php echo esc_attr($rule['start_date']); ?>">
                </label>
                <label style="margin-left: 10px;">
                    <?php _e('To:', 'reserve-mate'); ?>
                    <input type="text" class="datepicker" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][end_date]" value="<?php echo esc_attr($rule['end_date']); ?>">
                </label>
                <label style="margin-left: 10px;">
                    <input type="checkbox" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][repeat_yearly]" value="1" <?php checked($rule['repeat_yearly'], 1); ?>>
                    <?php _e('Repeat yearly', 'reserve-mate'); ?>
                </label>
            </div>
            
            <!-- Weekly Pattern -->
            <div class="disabled-date-weekly" style="<?php echo $rule['type'] !== 'weekly' ? 'display: none;' : ''; ?>">
                <?php 
                $days = [
                    'monday' => __('Monday', 'reserve-mate'),
                    'tuesday' => __('Tuesday', 'reserve-mate'),
                    'wednesday' => __('Wednesday', 'reserve-mate'),
                    'thursday' => __('Thursday', 'reserve-mate'),
                    'friday' => __('Friday', 'reserve-mate'),
                    'saturday' => __('Saturday', 'reserve-mate'),
                    'sunday' => __('Sunday', 'reserve-mate'),
                ];
                
                foreach ($days as $day => $label): ?>
                    <label style="margin-right: 10px; display: inline-block; margin-bottom: 5px;">
                        <input type="checkbox" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][days][]" value="<?php echo $day; ?>" <?php checked(in_array($day, (array)$rule['days'])); ?>>
                        <?php echo $label; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <!-- Time Interval -->
            <div class="disabled-date-time" style="<?php echo $rule['type'] !== 'time' ? 'display: none;' : ''; ?>">
                <div style="margin-bottom: 10px;">
                    <label style="margin-right: 15px;">
                        <?php _e('Specific Date (Optional):', 'reserve-mate'); ?>
                        <input type="text" class="datepicker" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][date]" value="<?php echo esc_attr($rule['date']); ?>">
                    </label>
                    <p class="description">
                        <?php _e('Leave empty to apply to selected days of week below', 'reserve-mate'); ?>
                    </p>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <label style="margin-right: 15px;">
                        <?php _e('Start Time:', 'reserve-mate'); ?>
                        <input type="text" class="timepicker" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][start_time]" value="<?php echo esc_attr($rule['start_time']); ?>">
                    </label>
                    <label>
                        <?php _e('End Time:', 'reserve-mate'); ?>
                        <input type="text" class="timepicker" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][end_time]" value="<?php echo esc_attr($rule['end_time']); ?>">
                    </label>
                </div>
                
                <div class="weekly-options" style="<?php echo !empty($rule['date']) ? 'display: none;' : ''; ?>">
                    <p><?php _e('Select days to apply this time interval:', 'reserve-mate'); ?></p>
                    <?php 
                    foreach ($days as $day => $label): ?>
                        <label style="margin-right: 10px; display: inline-block; margin-bottom: 5px;">
                            <input type="checkbox" name="rm_booking_options[disabled_dates][<?php echo $index; ?>][days][]" value="<?php echo $day; ?>" <?php checked(in_array($day, (array)$rule['days'])); ?>>
                            <?php echo $label; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// function ajax_get_disabled_date_rule() {
//     // Security checks
//     if (!current_user_can('manage_options')) {
//         wp_die(__('Insufficient permissions', 'reserve-mate'));
//     }
    
//     check_ajax_referer('reserve_mate_nonce', 'security');
    
//     $index = isset($_POST['index']) ? intval($_POST['index']) : 0;
    
//     ob_start();
//     display_disabled_date_rule($index);
//     $html = ob_get_clean();
    
//     wp_send_json_success($html);
// }

function ajax_get_disabled_date_rule() {
    try {
        // Verify nonce first
        if (!check_ajax_referer('reserve_mate_nonce', 'security', false)) {
            throw new Exception('Nonce verification failed');
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            throw new Exception('Capability check failed');
        }
        
        $index = isset($_POST['index']) ? intval($_POST['index']) : 0;
        
        if (!function_exists('display_disabled_date_rule')) {
            throw new Exception('display_disabled_date_rule function missing');
        }
        
        ob_start();
        display_disabled_date_rule($index);
        $html = ob_get_clean();
        
        wp_send_json_success($html);
        
    } catch (Exception $e) {
        error_log('Reserve Mate AJAX Error: ' . $e->getMessage());
        wp_send_json_error($e->getMessage(), 500);
    }
}

add_action('wp_ajax_get_disabled_date_rule', 'ajax_get_disabled_date_rule');

function generate_time_options($selected_time) {
    $times = [];
    for ($i = 0; $i < 24; $i++) {
        $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
        $times[] = "$hour:00";
        $times[] = "$hour:30";
    }

    foreach ($times as $time) {
        echo '<option value="' . esc_attr($time) . '"' . selected($selected_time, $time, false) . '>' . esc_html($time) . '</option>';
    }
}