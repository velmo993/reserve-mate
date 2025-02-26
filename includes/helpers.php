<?php

function get_currency() {
    $options = get_option('booking_settings');
    $currency = isset($options['currency']) ? $options['currency'] : 'USD';

    $c_symbol = '$';
    if ($currency === 'HUF') {
        $c_symbol = 'Ft';
    } else if ($currency === 'EUR') {
        $c_symbol = '€';
    } elseif ($currency === 'GBP') {
        $c_symbol = '£';
    } elseif ($currency === 'JPY') {
        $c_symbol = '¥';
    }
    return sanitize_text_field($c_symbol);
}

function get_currency_code() {
    $options = get_option('booking_settings');
    $currency = isset($options['currency']) ? $options['currency'] : 'USD';

    return strtolower(sanitize_text_field($currency));
}

function fix_json($raw_json) {
    $decoded = json_decode($raw_json, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($decoded, JSON_PRETTY_PRINT);
    } else {
        $fixed_json = htmlspecialchars_decode($raw_json);
        $decoded = json_decode($fixed_json, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_PRETTY_PRINT);
        } else {
            // error_log('Invalid JSON: ' . json_last_error_msg());
            return false;
        }
    }
}

function display_horizontal_line() {
    echo '<hr style="margin: 20px 0;">';
}

function format_price($price) {
    return fmod($price, 1) == 0 ? number_format($price, 0) : number_format($price, 2);
}
