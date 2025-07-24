<?php
defined('ABSPATH') or die('No direct access!');

function display_calendar_timezones() {
    $options = get_option('rm_general_options');
    $default_timezone = 'America/New_York';
    $timezone = isset($options['calendar_timezones']) ? esc_attr($options['calendar_timezones']) : $default_timezone;

    $timezones = timezone_identifiers_list();

    echo '<select name="rm_general_options[calendar_timezones]">';
    foreach ($timezones as $tz) {
        echo '<option value="' . esc_attr($tz) . '"' . selected($timezone, $tz, false) . '>' . esc_html($tz) . '</option>';
    }
    echo '</select>';
}

function display_date_format() {
    $options = get_option('rm_general_options');
    $default = 'Y-m-d H:i';
    $current_format = isset($options['date_format']) ? $options['date_format'] : $default;
    
    $formats = array(
        'Y-m-d H:i'    => date_i18n('Y-m-d H:i'),
        'm-d-Y g:i A' => date_i18n('m-d-Y g:i A'),
        'd-m-Y H:i'    => date_i18n('d-m-Y H:i'),
        'F j, Y g:i A' => date_i18n('F j, Y g:i A'),
        'd.m.Y H:i'    => date_i18n('d.m.Y H:i'),
        'Y/m/d g:i A'  => date_i18n('Y/m/d g:i A'),
        'D, M j, Y H:i' => date_i18n('D, M j, Y H:i'),
    );
    
    echo '<select name="rm_general_options[date_format]">';
    foreach ($formats as $format => $example) {
        echo '<option value="' . esc_attr($format) . '"' . selected($current_format, $format, false) . '>' . esc_html($example) . '</option>';
    }
    echo '</select>';
}

function display_calendar_locale() {
    $options = get_option('rm_general_options');
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
    
    echo '<select name="rm_general_options[calendar_locale]">';
    foreach ($locales as $code => $name) {
        echo '<option value="' . esc_attr($code) . '"' . selected($locale, $code, false) . '>' . esc_html($name) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . __('Select the locale for the calendar.', 'reserve-mate') . '</p>';
}

function display_currency_field() {
    $options = get_option('rm_general_options');
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

    echo '<select name="rm_general_options[currency]">';
    foreach ($currencies as $code => $symbol) {
        echo '<option value="' . esc_attr($code) . '" ' . selected($currency, $code, false) . '>' . esc_html($symbol) . '</option>';
    }
    echo '</select>';
}