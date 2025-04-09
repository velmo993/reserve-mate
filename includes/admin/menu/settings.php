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
    
    add_settings_field(
        'calendar_locale',
        __('Calendar Locale', 'reserve-mate'),
        'display_calendar_locale',
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
    
    add_settings_section(
        'horizontal_line_before_form_display',
        '',
        'display_horizontal_line',
        'booking-settings'
    );
    
    add_settings_section(
        'form_display_settings',
        __('Form Display Options', 'reserve-mate'),
        null,
        'booking-settings'
    );
    
    add_settings_field(
        'calendar_display_type',
        __('Calendar Display Type', 'reserve-mate'),
        'display_calendar_display_type_field',
        'booking-settings',
        'form_display_settings'
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
        
        <div class="nav-tab-wrapper">
            <a href="#general-tab" class="nav-tab nav-tab-active" data-tab="general-tab"><?php _e('General', 'reserve-mate'); ?></a>
            <a href="#hourly-tab" class="nav-tab" data-tab="hourly-tab"><?php _e('Hourly Booking', 'reserve-mate'); ?></a>
            <a href="#calendar-tab" class="nav-tab" data-tab="calendar-tab"><?php _e('Google Calendar', 'reserve-mate'); ?></a>
            <a href="#display-tab" class="nav-tab" data-tab="display-tab"><?php _e('Display', 'reserve-mate'); ?></a>
        </div>
        
        <form method="post" action="options.php">
            <?php settings_fields('booking_settings_group'); ?>
            
            <div id="general-tab" class="tab-content active">
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
                    _e('Calendar Timezone', 'reserve-mate');
                    echo '</th><td>';
                    display_calendar_timezones();
                    echo '</td></tr>';
                    
                    echo '<tr><th>';
                    _e('Date and Time Format', 'reserve-mate');
                    echo '</th><td>';
                    display_calendar_locale();
                    echo '</td></tr>';
                    ?>
                </table>
            </div>
            
            <div id="hourly-tab" class="tab-content">
                <h2><?php _e('Hourly Booking Settings', 'reserve-mate'); ?></h2>
                <table class="form-table">
                    <?php
                    // Hourly booking fields
                    echo '<tr><th>';
                    _e('Enable Hourly Booking', 'reserve-mate');
                    echo '</th><td>';
                    display_enable_hourly_booking_field();
                    echo '</td></tr>';
                    
                    echo '<tr><th>';
                    _e('Earliest Booking Time', 'reserve-mate');
                    echo '</th><td>';
                    display_hourly_min_time();
                    echo '</td></tr>';
                    
                    echo '<tr><th>';
                    _e('Latest Booking Time', 'reserve-mate');
                    echo '</th><td>';
                    display_hourly_max_time();
                    echo '</td></tr>';
                    
                    echo '<tr><th>';
                    _e('Booking Interval (Minutes)', 'reserve-mate');
                    echo '</th><td>';
                    display_hourly_booking_interval();
                    echo '</td></tr>';
                    
                    echo '<tr><th>';
                    _e('Break Duration Between Slots (Minutes)', 'reserve-mate');
                    echo '</th><td>';
                    display_hourly_break_duration();
                    echo '</td></tr>';
                    ?>
                </table>
            </div>
            
            <div id="calendar-tab" class="tab-content">
                <h2><?php _e('Google Calendar Settings', 'reserve-mate'); ?></h2>
                <table class="form-table">
                    <?php
                    // Google Calendar fields
                    echo '<tr><th>';
                    _e('Google Calendar API Credentials (JSON)', 'reserve-mate');
                    echo '</th><td>';
                    display_calendar_api_key_field();
                    echo '</td></tr>';
                    
                    echo '<tr><th>';
                    _e('Google Calendar ID', 'reserve-mate');
                    echo '</th><td>';
                    display_calendar_id_field();
                    echo '</td></tr>';
                    ?>
                </table>
            </div>
            
            <div id="display-tab" class="tab-content">
                <h2><?php _e('Display Settings', 'reserve-mate'); ?></h2>
                <table class="form-table">
                    <?php
                    // Display settings
                    echo '<tr><th>';
                    _e('Calendar Display Type', 'reserve-mate');
                    echo '</th><td>';
                    display_calendar_display_type_field();
                    echo '</td></tr>';
                    ?>
                </table>
            </div>
            
            <?php submit_button(); ?>
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

function display_calendar_locale() {
    $options = get_option('booking_settings');
    $default_locale = 'en-US';
    $locale = isset($options['calendar_locale']) ? esc_attr($options['calendar_locale']) : $default_locale;
    
    $locales = [
        'af-ZA' => 'Afrikaans (South Africa)',
        'am-ET' => 'Amharic (Ethiopia)',
        'ar-SA' => 'Arabic (Saudi Arabia)',
        'ar-AE' => 'Arabic (United Arab Emirates)',
        'ar-EG' => 'Arabic (Egypt)',
        'az-AZ' => 'Azerbaijani (Azerbaijan)',
        'be-BY' => 'Belarusian (Belarus)',
        'bg-BG' => 'Bulgarian (Bulgaria)',
        'bn-BD' => 'Bengali (Bangladesh)',
        'bs-BA' => 'Bosnian (Bosnia & Herzegovina)',
        'ca-ES' => 'Catalan (Spain)',
        'cs-CZ' => 'Czech (Czech Republic)',
        'cy-GB' => 'Welsh (United Kingdom)',
        'da-DK' => 'Danish (Denmark)',
        'de-DE' => 'German (Germany)',
        'de-AT' => 'German (Austria)',
        'de-CH' => 'German (Switzerland)',
        'el-GR' => 'Greek (Greece)',
        'en-AU' => 'English (Australia)',
        'en-CA' => 'English (Canada)',
        'en-GB' => 'English (United Kingdom)',
        'en-IN' => 'English (India)',
        'en-NZ' => 'English (New Zealand)',
        'en-US' => 'English (United States)',
        'es-ES' => 'Spanish (Spain)',
        'es-MX' => 'Spanish (Mexico)',
        'es-AR' => 'Spanish (Argentina)',
        'es-CO' => 'Spanish (Colombia)',
        'es-CL' => 'Spanish (Chile)',
        'et-EE' => 'Estonian (Estonia)',
        'eu-ES' => 'Basque (Spain)',
        'fa-IR' => 'Persian (Iran)',
        'fi-FI' => 'Finnish (Finland)',
        'fil-PH' => 'Filipino (Philippines)',
        'fr-FR' => 'French (France)',
        'fr-CA' => 'French (Canada)',
        'fr-BE' => 'French (Belgium)',
        'fr-CH' => 'French (Switzerland)',
        'ga-IE' => 'Irish (Ireland)',
        'gl-ES' => 'Galician (Spain)',
        'gu-IN' => 'Gujarati (India)',
        'he-IL' => 'Hebrew (Israel)',
        'hi-IN' => 'Hindi (India)',
        'hr-HR' => 'Croatian (Croatia)',
        'hu-HU' => 'Hungarian (Hungary)',
        'hy-AM' => 'Armenian (Armenia)',
        'id-ID' => 'Indonesian (Indonesia)',
        'is-IS' => 'Icelandic (Iceland)',
        'it-IT' => 'Italian (Italy)',
        'it-CH' => 'Italian (Switzerland)',
        'ja-JP' => 'Japanese (Japan)',
        'ka-GE' => 'Georgian (Georgia)',
        'kk-KZ' => 'Kazakh (Kazakhstan)',
        'km-KH' => 'Khmer (Cambodia)',
        'kn-IN' => 'Kannada (India)',
        'ko-KR' => 'Korean (South Korea)',
        'ky-KG' => 'Kyrgyz (Kyrgyzstan)',
        'lo-LA' => 'Lao (Laos)',
        'lt-LT' => 'Lithuanian (Lithuania)',
        'lv-LV' => 'Latvian (Latvia)',
        'mk-MK' => 'Macedonian (North Macedonia)',
        'ml-IN' => 'Malayalam (India)',
        'mn-MN' => 'Mongolian (Mongolia)',
        'mr-IN' => 'Marathi (India)',
        'ms-MY' => 'Malay (Malaysia)',
        'mt-MT' => 'Maltese (Malta)',
        'nb-NO' => 'Norwegian Bokmål (Norway)',
        'ne-NP' => 'Nepali (Nepal)',
        'nl-NL' => 'Dutch (Netherlands)',
        'nl-BE' => 'Dutch (Belgium)',
        'nn-NO' => 'Norwegian Nynorsk (Norway)',
        'pa-IN' => 'Punjabi (India)',
        'pl-PL' => 'Polish (Poland)',
        'ps-AF' => 'Pashto (Afghanistan)',
        'pt-BR' => 'Portuguese (Brazil)',
        'pt-PT' => 'Portuguese (Portugal)',
        'ro-RO' => 'Romanian (Romania)',
        'ru-RU' => 'Russian (Russia)',
        'si-LK' => 'Sinhala (Sri Lanka)',
        'sk-SK' => 'Slovak (Slovakia)',
        'sl-SI' => 'Slovenian (Slovenia)',
        'sq-AL' => 'Albanian (Albania)',
        'sr-RS' => 'Serbian (Serbia)',
        'sv-SE' => 'Swedish (Sweden)',
        'sw-KE' => 'Swahili (Kenya)',
        'ta-IN' => 'Tamil (India)',
        'te-IN' => 'Telugu (India)',
        'th-TH' => 'Thai (Thailand)',
        'tr-TR' => 'Turkish (Turkey)',
        'uk-UA' => 'Ukrainian (Ukraine)',
        'ur-PK' => 'Urdu (Pakistan)',
        'uz-UZ' => 'Uzbek (Uzbekistan)',
        'vi-VN' => 'Vietnamese (Vietnam)',
        'zh-CN' => 'Chinese (Simplified)',
        'zh-HK' => 'Chinese (Hong Kong)',
        'zh-TW' => 'Chinese (Traditional)'
    ];
    
    echo '<select name="booking_settings[calendar_locale]">';
    foreach ($locales as $code => $name) {
        echo '<option value="' . esc_attr($code) . '"' . selected($locale, $code, false) . '>' . esc_html($name) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . __('Select the locale for date formatting throughout the booking system.', 'reserve-mate') . '</p>';
}

function display_currency_field() {
    $options = get_option('booking_settings');
    $currency = isset($options['currency']) ? $options['currency'] : 'USD';

    $currencies = [
        'AED' => 'AED (د.إ)',
        'AUD' => 'AUD ($)',
        'BRL' => 'BRL (R$)',
        'CAD' => 'CAD ($)',
        'CHF' => 'CHF (CHF)',
        'CNY' => 'CNY (¥)',
        'CZK' => 'CZK (Kč)',
        'DKK' => 'DKK (kr)',
        'EUR' => 'EUR (€)',
        'GBP' => 'GBP (£)',
        'HKD' => 'HKD ($)',
        'HUF' => 'HUF (Ft)',
        'IDR' => 'IDR (Rp)',
        'ILS' => 'ILS (₪)',
        'INR' => 'INR (₹)',
        'JPY' => 'JPY (¥)',
        'KRW' => 'KRW (₩)',
        'MXN' => 'MXN ($)',
        'MYR' => 'MYR (RM)',
        'NOK' => 'NOK (kr)',
        'NZD' => 'NZD ($)',
        'PHP' => 'PHP (₱)',
        'PLN' => 'PLN (zł)',
        'RON' => 'RON (lei)',
        'RUB' => 'RUB (₽)',
        'SAR' => 'SAR (﷼)',
        'SEK' => 'SEK (kr)',
        'SGD' => 'SGD ($)',
        'THB' => 'THB (฿)',
        'TRY' => 'TRY (₺)',
        'TWD' => 'TWD (NT$)',
        'USD' => 'USD ($)',
        'ZAR' => 'ZAR (R)'
    ];

    echo '<select name="booking_settings[currency]">';
    foreach ($currencies as $code => $symbol) {
        echo '<option value="' . esc_attr($code) . '" ' . selected($currency, $code, false) . '>' . esc_html($symbol) . '</option>';
    }
    echo '</select>';
}

function display_enable_hourly_booking_field() {
    $options = get_option('booking_settings');
    $enabled = isset($options['enable_hourly_booking']) ? $options['enable_hourly_booking'] : $options['enable_hourly_booking'] = 0;
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

function display_calendar_display_type_field() {
    $options = get_option('booking_settings');
    $calendar_type = isset($options['calendar_display_type']) ? $options['calendar_display_type'] : 'full';
    ?>
    <select name="booking_settings[calendar_display_type]">
        <option value="full" <?php selected($calendar_type, 'full'); ?>><?php _e('Full Calendar View', 'reserve-mate'); ?></option>
        <option value="inline" <?php selected($calendar_type, 'inline'); ?>><?php _e('Inline Calendar View', 'reserve-mate'); ?></option>
    </select>
    <p class="description">
        <?php _e('Choose between a full calendar display or an inline calendar.', 'reserve-mate'); ?>
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

    if (isset($input['calendar_display_type'])) {
        $input['calendar_display_type'] = sanitize_text_field($input['calendar_display_type']);
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

