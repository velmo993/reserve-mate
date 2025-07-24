<?php
defined('ABSPATH') or die('No direct access!');

function get_currency() {
    $options = get_option('rm_general_options');
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
    $options = get_option('rm_general_options');
    $currency = isset($options['currency']) ? $options['currency'] : 'USD';

    return strtolower(sanitize_text_field($currency));
}

function get_currency_code_uppercase() {
    $options = get_option('rm_general_options');
    $currency = isset($options['currency']) ? $options['currency'] : 'USD';
    return strtoupper(sanitize_text_field($currency));
}

function is_paypal_currency_supported($currency) {
    // PayPal supported currencies (as of 2024)
    $supported_currencies = [
        'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 
        'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 
        'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD'
    ];
    
    return in_array(strtoupper($currency), $supported_currencies);
}

function get_currency_symbol($currency) {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'AUD' => 'A$',
        'CAD' => 'C$',
        'CHF' => 'Fr',
        'CNY' => '¥',
        'SEK' => 'kr',
        'NZD' => 'NZ$',
        'MXN' => '$',
        'SGD' => 'S$',
        'HKD' => 'HK$',
        'NOK' => 'kr',
        'TRY' => '₺',
        'RUB' => '₽',
        'INR' => '₹',
        'BRL' => 'R$',
        'ZAR' => 'R',
        'HUF' => 'Ft',
        'CZK' => 'Kč',
        'PLN' => 'zł',
        'ILS' => '₪',
        'DKK' => 'kr',
        'THB' => '฿',
        'MYR' => 'RM',
        'PHP' => '₱',
        'TWD' => 'NT$'
    ];
    
    return $symbols[strtoupper($currency)] ?? $currency;
}

function display_horizontal_line() {
    echo '<hr style="margin: 20px 0;">';
}

function format_price($price) {
    return fmod($price, 1) == 0 ? number_format($price, 0) : number_format($price, 2);
}

function get_effective_max_date() {
    $options = get_option('rm_booking_options');
    
    // Priority 1: Dynamic rolling window (if set)
    if (!empty($options['booking_max_days_ahead'])) {
        return date('Y-m-d', strtotime("+{$options['booking_max_days_ahead']} days"));
    }
    
    // Priority 2: Fixed date (if set)
    if (!empty($options['booking_max_date'])) {
        return $options['booking_max_date'];
    }
    
    // No restrictions
    return null;
}

function get_disabled_dates() {
    $options = get_option('rm_booking_options', []);
    $disabled_dates = $options['disabled_dates'] ?? [];
    
    $disabled_specific_dates = [];
    $disabled_weekly_days = [];
    $disabled_time_periods = [];
    
    foreach ($disabled_dates as $rule) {
        if ($rule['type'] === 'specific' && !empty($rule['date'])) {
            $disabled_specific_dates[] = sanitize_text_field($rule['date']);
        }
        elseif ($rule['type'] === 'range' && !empty($rule['start_date']) && !empty($rule['end_date'])) {
            $start = new DateTime(sanitize_text_field($rule['start_date']));
            $end = new DateTime(sanitize_text_field($rule['end_date']));
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
            
            foreach ($period as $date) {
                $disabled_specific_dates[] = $date->format('Y-m-d');
            }
        }
        elseif ($rule['type'] === 'weekly' && !empty($rule['days'])) {
            foreach ($rule['days'] as $day) {
                $day_num = date('w', strtotime(sanitize_text_field($day)));
                $disabled_weekly_days[] = intval($day_num);
            }
        }
        elseif ($rule['type'] === 'time' && !empty($rule['start_time']) && !empty($rule['end_time'])) {
            if (!empty($rule['date'])) {
                $disabled_time_periods[] = [
                    'date' => sanitize_text_field($rule['date']),
                    'start_time' => sanitize_text_field($rule['start_time']),
                    'end_time' => sanitize_text_field($rule['end_time'])
                ];
            } elseif (!empty($rule['days'])) {
                foreach ($rule['days'] as $day) {
                    $day_num = date('w', strtotime(sanitize_text_field($day)));
                    $disabled_time_periods[] = [
                        'day' => intval($day_num),
                        'start_time' => sanitize_text_field($rule['start_time']),
                        'end_time' => sanitize_text_field($rule['end_time'])
                    ];
                }
            }
        }
    }
    
    return [
        'dates' => $disabled_specific_dates,
        'days' => array_unique($disabled_weekly_days),
        'time_periods' => $disabled_time_periods
    ];
}