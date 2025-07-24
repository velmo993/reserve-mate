<?php

defined('ABSPATH') or die('No direct access!');

use ReserveMate\Admin\Helpers\GoogleCalendar;

function display_calendar_selection_field() {
    $google_calendar = reserve_mate_gcal();
    $options = get_option('rm_google_calendar_options', array());
    $selected_calendar_id = isset($options['calendar_id']) ? $options['calendar_id'] : '';
    
    if (!$google_calendar || !$google_calendar->is_authorized()) {
        ?>
        <p class="description"><?php _e('Please connect to Google Calendar first to select a calendar.', 'reserve-mate'); ?></p>
        <?php
        return;
    }
    
    $calendars = get_google_calendars();
    
    if (is_wp_error($calendars)) {
        $error_code = $calendars->get_error_code();
        
        if ($error_code === 'token_expired' || $error_code === 'invalid_token') {
            ?>
            <div class="notice notice-warning inline">
                <p><?php _e('Your Google Calendar connection has expired. Please reconnect to continue using calendar integration.', 'reserve-mate'); ?></p>
                <p>
                    <button type="button" id="reconnect-google-calendar" class="button button-primary">
                        <?php _e('Reconnect Google Calendar', 'reserve-mate'); ?>
                    </button>
                </p>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $('#reconnect-google-calendar').on('click', function(e) {
                    e.preventDefault();
                    $('#connect-google-calendar').trigger('click');
                });
            });
            </script>
            <?php
        } else {
            ?>
            <p class="description" style="color: #dc3232;">
                <?php /* translators: %s is the error message from the calendar loading process */ ?>
                <?php printf(__('Error loading calendars: %s', 'reserve-mate'), $calendars->get_error_message()); ?>
            </p>
            <?php
        }
        return;
    }
    
    if (empty($calendars)) {
        ?>
        <p class="description"><?php _e('No calendars found.', 'reserve-mate'); ?></p>
        <?php
        return;
    }
    
    if (empty($selected_calendar_id)) {
        foreach ($calendars as $calendar) {
            if ($calendar['primary']) {
                $selected_calendar_id = $calendar['id'];
                break;
            }
        }

        if (empty($selected_calendar_id) && !empty($calendars)) {
            $selected_calendar_id = $calendars[0]['id'];
        }
    }
    
    ?>
    <div id="calendar-selection-wrapper">
        <select name="rm_google_calendar_options[calendar_id]" class="regular-text" id="calendar-selection">
            <?php foreach ($calendars as $calendar): ?>
                <option value="<?php echo esc_attr($calendar['id']); ?>" 
                        <?php selected($selected_calendar_id, $calendar['id']); ?>>
                    <?php echo esc_html($calendar['summary']); ?>
                    <?php if ($calendar['primary']): ?>
                        <?php _e(' (Primary)', 'reserve-mate'); ?>
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Select which Google Calendar to use for booking events.', 'reserve-mate'); ?></p>
        
        <div id="calendar-update-status" style="display: none; margin-top: 10px;">
            <span class="spinner" style="visibility: visible; float: none; margin: 0 5px 0 0;"></span>
            <span><?php _e('Updating calendar selection...', 'reserve-mate'); ?></span>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var originalCalendarId = $('#calendar-selection').val();
        
        $('#calendar-selection').on('change', function() {
            var newCalendarId = $(this).val();
            var $status = $('#calendar-update-status');
            
            if (newCalendarId === originalCalendarId) {
                return;
            }
            
            $status.show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_calendar_selection',
                    calendar_id: newCalendarId,
                    nonce: '<?php echo wp_create_nonce('update_calendar_selection'); ?>'
                },
                success: function(response) {
                    $status.hide();
                    if (response.success) {
                        $('<div class="notice notice-success is-dismissible"><p><?php _e('Calendar changed!', 'reserve-mate'); ?></p></div>')
                            .insertAfter('#calendar-selection-wrapper')
                            .delay(3000)
                            .fadeOut();
                        
                        originalCalendarId = newCalendarId;
                    } else {
                        alert('<?php _e('Error updating calendar selection', 'reserve-mate'); ?>');
                        $('#calendar-selection').val(originalCalendarId);
                        console.log(response);
                    }
                },
                error: function(error) {
                    $status.hide();
                    alert('<?php _e('Error updating calendar selection', 'reserve-mate'); ?>');
                    $('#calendar-selection').val(originalCalendarId);
                    console.log(error);
                }
            });
        });
    });
    </script>
    <?php
}

function get_google_calendars() {
    $google_calendar = reserve_mate_gcal();
    
    if (!$google_calendar) {
        return new WP_Error('no_auth', 'Google Calendar auth object not available');
    }
    
    // First check if we're authorized
    if (!$google_calendar->is_authorized()) {
        return new WP_Error('not_authorized', 'Not authorized to access Google Calendar');
    }
    
    // Test token validity with a simple API call first
    if (!$google_calendar->test_token_validity()) {
        return new WP_Error('invalid_token', 'Token validation failed. Please reconnect your Google Calendar.');
    }
    
    $access_token = $google_calendar->get_valid_access_token();
    if (!$access_token) {
        return new WP_Error('no_token', 'No valid access token available');
    }
    
    $url = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';
    
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return new WP_Error('network_error', 'Network error: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($response_code === 401) {
        // Token is invalid, clear tokens and ask for re-authentication
        $google_calendar->clear_all_tokens();
        return new WP_Error('token_expired', 'Authentication expired. Please reconnect your Google Calendar.');
    }
    
    if ($response_code !== 200) {
        $error_message = "HTTP $response_code";
        $data = json_decode($body, true);
        if (isset($data['error']['message'])) {
            $error_message .= ": " . $data['error']['message'];
        }
        return new WP_Error('api_error', $error_message);
    }
    
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_decode_error', 'JSON decode error: ' . json_last_error_msg());
    }
    
    if (!isset($data['items'])) {
        return array();
    }
    
    $calendars = array();
    $primary_calendar = null;
    
    foreach ($data['items'] as $item) {
        if (isset($item['accessRole']) && in_array($item['accessRole'], ['owner', 'writer'])) {
            $calendar = array(
                'id' => $item['id'],
                'summary' => $item['summary'],
                'primary' => isset($item['primary']) && $item['primary']
            );
            
            if ($calendar['primary']) {
                $primary_calendar = $calendar;
            } else {
                $calendars[] = $calendar;
            }
        }
    }
    
    if ($primary_calendar) {
        array_unshift($calendars, $primary_calendar);
    }
    
    return $calendars;
}

function display_google_calendar_auth() {
    $google_calendar = reserve_mate_gcal();
    $is_authorized = $google_calendar->is_authorized();
    
    $options = get_option('rm_google_calendar_options');
    $calendar_id = isset($options['calendar_id']) ? $options['calendar_id'] : '';
    
    ?>
    <div class="google-calendar-simple-auth">
        <?php if ($is_authorized): ?>
            <div class="connection-status connected">
                <span class="dashicons dashicons-yes-alt"></span>
                <h3><?php _e('Google Calendar Connected', 'reserve-mate'); ?></h3>
                <p><?php _e('Your bookings will automatically sync with your Google Calendar.', 'reserve-mate'); ?></p>
                
                <?php if ($calendar_id): ?>
                    <?php
                    $calendars = get_google_calendars();
                    $calendar_name = '';
                    if (!is_wp_error($calendars)) {
                        foreach ($calendars as $cal) {
                            if ($cal['id'] === $calendar_id) {
                                $calendar_name = $cal['summary'];
                                break;
                            }
                        }
                    }
                    ?>
                    <p><strong><?php _e('Active Calendar:', 'reserve-mate'); ?></strong> <?php echo esc_html($calendar_name); ?></p>
                <?php endif; ?>
                
                <button type="button" id="revoke-google-auth" class="button button-secondary">
                    <?php _e('Disconnect', 'reserve-mate'); ?>
                </button>
            </div>
            
        <?php else: ?>
            <div class="connection-status disconnected">
                <span class="dashicons dashicons-calendar-alt"></span>
                <h3><?php _e('Connect Google Calendar', 'reserve-mate'); ?></h3>
                <p><?php _e('Automatically sync your bookings with Google Calendar. No setup required - just click connect!', 'reserve-mate'); ?></p>
                
                <div class="connection-benefits">
                    <ul>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Automatic booking sync', 'reserve-mate'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Prevent double-bookings', 'reserve-mate'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('Access from any device', 'reserve-mate'); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e('No API keys required', 'reserve-mate'); ?></li>
                    </ul>
                </div>
                
                <button type="button" id="connect-google-calendar" class="button button-primary button-large connect-button">
                    <span class="dashicons dashicons-google"></span>
                    <?php _e('Connect Google Calendar', 'reserve-mate'); ?>
                </button>
                
                <p class="connection-note">
                    <?php _e('You\'ll be redirected to Google to authorize access. We only request calendar permissions.', 'reserve-mate'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
    .google-calendar-simple-auth {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        max-width: 600px;
        margin: 20px 0;
    }
    
    .connection-status h3 {
        margin: 10px 0 15px 0;
        font-size: 18px;
    }
    
    .connection-status.connected {
        border-left: 4px solid #46b450;
    }
    
    .connection-status.connected .dashicons-yes-alt {
        color: #46b450;
        font-size: 24px;
    }
    
    .connection-status.disconnected .dashicons-calendar-alt {
        color: #0073aa;
        font-size: 24px;
    }
    
    .connection-benefits {
        background: #f8f9fa;
        border-radius: 4px;
        padding: 15px;
        margin: 20px 0;
        text-align: left;
        display: inline-block;
    }
    
    .connection-benefits ul {
        margin: 0;
        list-style: none;
    }
    
    .connection-benefits li {
        margin: 8px 0;
        display: flex;
        align-items: center;
    }
    
    .connection-benefits .dashicons {
        color: #46b450;
        margin-right: 8px;
        font-size: 16px;
    }
    
    .connect-button {
        font-size: 16px !important;
        padding: 12px 24px !important;
        height: auto !important;
        margin: 20px 0 !important;
    }
    
    .connect-button .dashicons {
        margin-right: 8px;
        font-size: 18px;
    }
    
    .connection-note {
        font-size: 12px;
        color: #666;
        margin-top: 10px;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Connect Google Calendar
        $('#connect-google-calendar').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true).html('<span class="spinner" style="visibility: visible; float: none; margin-right: 5px;"></span><?php _e('Connecting...', 'reserve-mate'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'connect_google_calendar',
                    _ajax_nonce: '<?php echo wp_create_nonce('reserve_mate_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert('<?php _e('Error initiating connection. Please try again.', 'reserve-mate'); ?>');
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-google"></span><?php _e('Connect Google Calendar', 'reserve-mate'); ?>');
                    }
                },
                error: function() {
                    alert('<?php _e('Connection error. Please try again.', 'reserve-mate'); ?>');
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-google"></span><?php _e('Connect Google Calendar', 'reserve-mate'); ?>');
                }
            });
        });
        
        // Disconnect Google Calendar
        $('#revoke-google-auth').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('<?php _e('Are you sure you want to disconnect Google Calendar?', 'reserve-mate'); ?>')) {
                var $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Disconnecting...', 'reserve-mate'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'revoke_google_auth',
                        nonce: '<?php echo wp_create_nonce('revoke_google_auth'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Error disconnecting. Please try again.', 'reserve-mate'); ?>');
                            $button.prop('disabled', false).text('<?php _e('Disconnect', 'reserve-mate'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Connection error. Please try again.', 'reserve-mate'); ?>');
                        $button.prop('disabled', false).text('<?php _e('Disconnect', 'reserve-mate'); ?>');
                    }
                });
            }
        });
    });
    </script>
    <?php
}
?>
