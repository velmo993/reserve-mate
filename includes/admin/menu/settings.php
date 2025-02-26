<?php

function register_booking_settings() {
    register_setting('booking_settings_group', 'booking_settings', array(
        'sanitize_callback' => 'sanitize_booking_settings'
    ));

    add_settings_section(
        'horizontal_line_before_google',
        '',
        'display_horizontal_line',
        'booking-settings'
    );
    
    add_settings_section(
        'google_calendar_settings',
        __('Google Calendar', 'reserve-mate'),
        null,
        'booking-settings'
    );

    add_settings_field(
        'calendar_api_key',
        __('Google Calendar API Credentials (JSON)', 'reserve-mate'),
        'display_calendar_api_key_field',
        'booking-settings',
        'google_calendar_settings'
    );
    
    add_settings_field(
        'calendar_id',
        __('Google Calendar ID', 'reserve-mate'),
        'display_calendar_id_field',
        'booking-settings',
        'google_calendar_settings'
    );
    
    add_settings_field(
        'calendar_timezones',
        __('Calendar Timezone', 'reserve-mate'),
        'display_calendar_timezones',
        'booking-settings',
        'google_calendar_settings'
    );
    
    add_settings_section(
        'horizontal_line_before_bookings',
        '',
        'display_horizontal_line',
        'booking-settings'
    );
    
    add_settings_section(
        'booking_settings',
        __('Bookings', 'reserve-mate'),
        null,
        'booking-settings'
    );
    
    add_settings_field(
        'currency',
        __('Currency', 'reserve-mate'),
        'display_currency_field',
        'booking-settings',
        'booking_settings'
    );
    
    add_settings_field(
        'check-in-time',
        __('Latest Check-in Time', 'reserve-mate'),
        'display_check_in_time',
        'booking-settings',
        'booking_settings'
    );
    
    add_settings_field(
        'check-out-time',
        __('Latest Check-out Time', 'reserve-mate'),
        'display_check_out_time',
        'booking-settings',
        'booking_settings'
    );
    
    add_settings_section(
        'hourly_booking_settings',
        __('Hourly Booking Settings', 'reserve-mate'),
        null,
        'booking-settings'
    );
    
    add_settings_field(
        'enable_hourly_booking',
        __('Enable Hourly Booking', 'reserve-mate'),
        'display_enable_hourly_booking_field',
        'booking-settings',
        'hourly_booking_settings'
    );
    
    add_settings_field(
        'hourly_min_time',
        __('Earliest Booking Time', 'reserve-mate'),
        'display_hourly_min_time',
        'booking-settings',
        'hourly_booking_settings'
    );
    
    add_settings_field(
        'hourly_max_time',
        __('Latest Booking Time', 'reserve-mate'),
        'display_hourly_max_time',
        'booking-settings',
        'hourly_booking_settings'
    );
    
    add_settings_field(
        'hourly_booking_interval',
        __('Booking Interval (Minutes)', 'reserve-mate'),
        'display_hourly_booking_interval',
        'booking-settings',
        'hourly_booking_settings'
    );
    
    add_settings_field(
        'hourly_break_duration',
        __('Break Duration Between Slots (Minutes)', 'reserve-mate'),
        'display_hourly_break_duration',
        'booking-settings',
        'hourly_booking_settings'
    );
    
    /* add_settings_field(
        'auto_delete_booking_enabled',
        __('Automatically delete unpaid bookings', 'reserve-mate'),
        'display_auto_delete_booking_field',
        'booking-settings',
        'booking_settings'
    );
    
    add_settings_field(
        'delete_after_days',
        __('Number of days before deletion', 'reserve-mate'),
        'display_delete_after_days_field',
        'booking-settings',
        'booking_settings'
    ); */
}

add_action('admin_init', 'register_booking_settings');

function booking_settings_page() {
    ?>
    <div class="wrap">
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'reserve-mate') . '</p></div>';
        } ?>
        <h1><?php _e('Booking System Settings', 'reserve-mate'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('booking_settings_group');
            do_settings_sections('booking-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function display_calendar_api_key_field() {
    $options = get_option('booking_settings');
    $api_key = isset($options['calendar_api_key']) ? esc_attr($options['calendar_api_key']) : '';
    ?>
    <input type="text" name="booking_settings[calendar_api_key]" value="<?php echo $api_key; ?>" class="regular-text">
    <?php
}

function display_calendar_id_field() {
    $options = get_option('booking_settings');
    $calendar_id = isset($options['calendar_id']) ? esc_attr($options['calendar_id']) : '';
    ?>
    <input type="text" name="booking_settings[calendar_id]" value="<?php echo $calendar_id; ?>" class="regular-text">
    <?php
}

function display_calendar_timezones() {
    $options = get_option('booking_settings');
    $default_timezone = 'America/New_York';
    $timezone = isset($options['calendar_timezones']) ? esc_attr($options['calendar_timezones']) : $default_timezone;

    $timezones = timezone_identifiers_list();

    echo '<select name="booking_settings[calendar_timezones]">';
    foreach ($timezones as $tz) {
        echo '<option value="' . esc_attr($tz) . '"' . selected($timezone, $tz, false) . '>' . esc_html($tz) . '</option>';
    }
    echo '</select>';
}

function display_currency_field() {
    $options = get_option('booking_settings');
    $currency = isset($options['currency']) ? $options['currency'] : 'USD';
    ?>
    <select name="booking_settings[currency]">
        <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR (€)</option>
        <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP (£)</option>
        <option value="HUF" <?php selected($currency, 'HUF'); ?>>HUF (Ft)</option>
        <option value="JPY" <?php selected($currency, 'JPY'); ?>>JPY (¥)</option>
        <option value="USD" <?php selected($currency, 'USD'); ?>>USD ($)</option>
    </select>
    <?php
}

function display_check_in_time() {
    $options = get_option('booking_settings');
    $checkin_time = isset($options['checkin_time']) ? esc_attr($options['checkin_time']) : '14:00';
    
    echo '<select name="booking_settings[checkin_time]">';
    generate_time_options($checkin_time);
    echo '</select>';
}

function display_check_out_time() {
    $options = get_option('booking_settings');
    $checkout_time = isset($options['checkout_time']) ? esc_attr($options['checkout_time']) : '12:00';
    
    echo '<select name="booking_settings[checkout_time]">';
    generate_time_options($checkout_time);
    echo '</select>';
}

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

function display_enable_hourly_booking_field() {
    $options = get_option('booking_settings');
    $enabled = isset($options['enable_hourly_booking']) ? $options['enable_hourly_booking'] : 0;
    ?>
    <input type="checkbox" name="booking_settings[enable_hourly_booking]" value="1" <?php checked(1, $enabled); ?>>
    <label><?php _e('Enable hourly-based booking instead of date range', 'reserve-mate'); ?></label>
    <p class="description">
        <?php _e('Enable this if you want time-slot booking(e.g. 12:30-13:00) instead of booking days.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_hourly_min_time() {
    $options = get_option('booking_settings');
    $min_time = isset($options['hourly_min_time']) ? $options['hourly_min_time'] : '08:00';

    echo '<select name="booking_settings[hourly_min_time]">';
    generate_time_options($min_time);
    echo '</select>';
    ?>
    <p class="description">
        <?php _e('Start of the first time interval.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_hourly_max_time() {
    $options = get_option('booking_settings');
    $max_time = isset($options['hourly_max_time']) ? $options['hourly_max_time'] : '20:00';

    echo '<select name="booking_settings[hourly_max_time]">';
    generate_time_options($max_time);
    echo '</select>';
    ?>
    <p class="description">
        <?php _e('End of the last time interval.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_hourly_booking_interval() {
    $options = get_option('booking_settings');
    $interval = isset($options['hourly_booking_interval']) ? $options['hourly_booking_interval'] : '30';
    ?>
    <input type="number" name="booking_settings[hourly_booking_interval]" value="<?php echo esc_attr($interval); ?>" min="5" step="1">
    <p class="description">
        <?php _e('Enter the booking interval in minutes (e.g., 15, 30, 60).', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_hourly_break_duration() {
    $options = get_option('booking_settings');
    $break_duration = isset($options['hourly_break_duration']) ? $options['hourly_break_duration'] : '0';
    ?>
    <input type="number" name="booking_settings[hourly_break_duration]" value="<?php echo esc_attr($break_duration); ?>" min="0" step="1">
    <p class="description">
        <?php _e('Add a break duration (in minutes) between time slots. For example, 5 minutes.', 'reserve-mate'); ?>
    </p>
    <?php
}

function sanitize_booking_settings($input) {
    if (isset($input['calendar_api_key'])) {
        $input['calendar_api_key'] = fix_json($input['calendar_api_key']);
    }

    if (isset($input['calendar_id'])) {
        $input['calendar_id'] = sanitize_text_field($input['calendar_id']);
    }

    if (isset($input['currency'])) {
        $input['currency'] = sanitize_text_field($input['currency']);
    }

    if (isset($input['checkin_time'])) {
        $input['checkin_time'] = sanitize_text_field($input['checkin_time']);
    }

    if (isset($input['checkout_time'])) {
        $input['checkout_time'] = sanitize_text_field($input['checkout_time']);
    }

    if (isset($input['auto_delete_booking_enabled'])) {
        $input['auto_delete_booking_enabled'] = 1;
    } else {
        $input['auto_delete_booking_enabled'] = 0;
    }

    if (isset($input['delete_after_days']) && $input['auto_delete_booking_enabled'] == 1) {
        $input['delete_after_days'] = absint($input['delete_after_days']);
    }
    
    if (isset($input['enable_hourly_booking'])) {
        $input['enable_hourly_booking'] = 1;
    } else {
        $input['enable_hourly_booking'] = 0;
    }
    
    if (isset($input['hourly_min_time'])) {
        $input['hourly_min_time'] = sanitize_text_field($input['hourly_min_time']);
    }
    
    if (isset($input['hourly_max_time'])) {
        $input['hourly_max_time'] = sanitize_text_field($input['hourly_max_time']);
    }
    
    if (isset($input['hourly_booking_interval'])) {
        $input['hourly_booking_interval'] = absint($input['hourly_booking_interval']);
    }
    
    if (isset($input['hourly_break_duration'])) {
        $input['hourly_break_duration'] = absint($input['hourly_break_duration']);
    }

    return $input;
}

/* function display_auto_delete_booking_field() {
    $options = get_option('booking_settings');
    $checked = isset($options['auto_delete_booking_enabled']) && $options['auto_delete_booking_enabled'] == 1 ? 'checked' : '';
    echo '<input type="checkbox" name="booking_settings[auto_delete_booking_enabled]" value="1" ' . $checked . '> Enable';
}

function display_delete_after_days_field() {
    $options = get_option('booking_settings');
    $days = isset($options['delete_after_days']) && $options['auto_delete_booking_enabled'] == 1 ? esc_attr($options['delete_after_days']) : '';
    echo '<input type="number" style="width: 50px;" name="booking_settings[delete_after_days]" value="' . $days . '" min="1">';
} */