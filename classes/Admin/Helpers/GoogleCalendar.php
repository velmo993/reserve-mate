<?php
namespace ReserveMate\Admin\Helpers;
use ReserveMate\Admin\Helpers\SecureCredentials;

defined('ABSPATH') or die('No direct access!');

class GoogleCalendar {
    // Auth server endpoints
    private const AUTH_SERVER_URL = 'https://auth.reservemateplugin.com/google-auth';
    private const TOKEN_FETCH_URL = 'https://auth.reservemateplugin.com/get-tokens';
    private const TOKEN_REFRESH_URL = 'https://auth.reservemateplugin.com/refresh-token';
    private const TOKEN_REVOKE_URL = 'https://auth.reservemateplugin.com/revoke-token';
    private static $hooks_registered = false;
    
    public function __construct() {
        if (!self::$hooks_registered) {
            add_action('admin_init', [$this, 'handle_oauth_callback'], 1);
            add_action('wp_ajax_revoke_google_auth', [$this, 'revoke_authorization']);
            add_action('wp_ajax_connect_google_calendar', [$this, 'initiate_google_auth']);
            add_action('admin_notices', [$this, 'show_calendar_fallback_warning']);
            add_action('admin_init', [$this, 'handle_calendar_selection_change']);
            add_action('wp_ajax_update_calendar_selection', [$this, 'handle_ajax_calendar_update']);
            add_action('admin_notices', [$this, 'show_calendar_update_message']);
            add_action('admin_notices', [$this, 'show_auth_message']);
            self::$hooks_registered = true;
        }
    }
    
    public function initiate_google_auth() {
        check_ajax_referer('reserve_mate_nonce');
        
        $site_key = $this->get_site_key();
        $return_url = admin_url('admin.php?page=reserve-mate-settings');
        
        $auth_url = self::AUTH_SERVER_URL . '?' . http_build_query([
            'return_url' => $return_url,
            'site_key' => $site_key
        ]);
        
        wp_send_json_success(['redirect_url' => $auth_url]);
    }
    
    public function handle_oauth_callback() {
        if (!isset($_GET['temp_token']) || !is_admin()) {
            return;
        }
        
        $temp_token = sanitize_text_field($_GET['temp_token']);
        $site_key = $this->get_site_key();
        $tokens = $this->fetch_tokens_from_auth_server($temp_token, $site_key);
        
        if ($tokens) {
            $this->store_tokens($tokens);
            $this->set_default_calendar($tokens['access_token']);
            $this->set_auth_message('success', __('Google Calendar connected successfully!', 'reserve-mate'));
            
            wp_redirect(admin_url('admin.php?page=reserve-mate-settings'));
            exit;
        } else {
            $this->set_auth_message('error', __('Failed to connect Google Calendar. Please try again.', 'reserve-mate'));
            wp_redirect(admin_url('admin.php?page=reserve-mate-settings'));
            exit;
        }
    }
    
    private function fetch_tokens_from_auth_server($temp_token, $site_key) {
        $response = wp_remote_post(self::TOKEN_FETCH_URL, [
            'body' => [
                'temp_token' => $temp_token,
                'site_key' => $site_key
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            // error_log('ReserveMate: Failed to fetch tokens from auth server: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // error_log('ReserveMate: Invalid JSON response from auth server');
            return false;
        }
        
        return $data['success'] ? $data['tokens'] : false;
    }
    
    private function store_tokens($tokens) {
        $options = get_option('rm_google_calendar_options', array());
        
        $options['google_access_token'] = SecureCredentials::encrypt($tokens['access_token'], SecureCredentials::GOOGLE);
        $options['google_refresh_token'] = isset($tokens['refresh_token']) ? SecureCredentials::encrypt($tokens['refresh_token'], SecureCredentials::GOOGLE) : '';
        $options['google_token_expires'] = isset($tokens['created_at']) ? 
            ($tokens['created_at'] + $tokens['expires_in']) : 
            (time() + $tokens['expires_in']);
        
        update_option('rm_google_calendar_options', $options);
    }
    
    private function set_default_calendar($access_token) {
        $options = get_option('rm_google_calendar_options', array());
        
        if (empty($options['calendar_id'])) {
            $calendar_id = $this->get_primary_calendar_id($access_token);
            if ($calendar_id) {
                $options['calendar_id'] = $calendar_id;
                update_option('rm_google_calendar_options', $options);
            }
        }
    }
    
    private function get_primary_calendar_id($access_token) {
        $response = wp_remote_get('https://www.googleapis.com/calendar/v3/users/me/calendarList/primary', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token
            ),
            'timeout' => 10
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return isset($data['id']) ? $data['id'] : null;
        }
        
        return null;
    }
    
    public function clear_all_tokens() {
        delete_option('rm_google_calendar_options');
        
        $fresh_options = array();
        $result = update_option('rm_google_calendar_options', $fresh_options);
        
        wp_cache_delete('rm_google_calendar_options', 'options');
        
        return $result;
    }
    
    public function is_authorized() {
        $options = get_option('rm_google_calendar_options');
        
        if (empty($options['google_access_token'])) {
            return false;
        }
        
        // Try to get a valid token - this will attempt refresh if needed
        $token = $this->get_valid_access_token();
        return !empty($token);
    }
    
    public function test_token_validity() {
        $access_token = $this->get_valid_access_token();
        
        if (!$access_token) {
            return false;
        }
        
        $response = wp_remote_get('https://www.googleapis.com/calendar/v3/users/me/calendarList?maxResults=1', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
    
    public function is_token_valid() {
        $options = get_option('rm_google_calendar_options');
        
        if (empty($options['google_access_token'])) {
            return false;
        }
        
        $token = $this->get_access_token();
        if (empty($token)) {
            return false;
        }
        
        if (isset($options['google_token_expires']) && time() >= $options['google_token_expires']) {
            return $this->refresh_access_token() !== false;
        }
        
        return true;
    }
    
    public function get_valid_access_token() {
        $options = get_option('rm_google_calendar_options');
    
        if (empty($options['google_access_token'])) {
            return false;
        }
        
        // Check if token is expired or about to expire (5 minutes buffer)
        if (isset($options['google_token_expires']) && time() >= ($options['google_token_expires'] - 300)) {
            $refreshed_token = $this->refresh_access_token();
            if ($refreshed_token) {
                return $refreshed_token;
            } else {
                // If refresh fails, clear tokens to force re-authentication
                $this->clear_all_tokens();
                return false;
            }
        }
        
        $token = $this->get_access_token();
        return $token;
    }
    
    private function get_access_token() {
        $options = get_option('rm_google_calendar_options', []);
        
        if (empty($options['google_access_token'])) {
            return '';
        }
        
        return SecureCredentials::decrypt($options['google_access_token'], SecureCredentials::GOOGLE);
    }
    
    public static function get_refresh_token() {
        $options = get_option('rm_google_calendar_options', []);
        
        if (empty($options['google_refresh_token'])) {
            return '';
        }
        
        return SecureCredentials::decrypt($options['google_refresh_token'], SecureCredentials::GOOGLE);
    }
    
    private function refresh_access_token() {
        $refresh_token = self::get_refresh_token();
        if (empty($refresh_token)) {
            // No refresh token available, need to re-authenticate
            return false;
        }
        
        $site_key = $this->get_site_key();
        
        $response = wp_remote_post(self::TOKEN_REFRESH_URL, [
            'body' => [
                'refresh_token' => $refresh_token,
                'site_key' => $site_key
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            error_log('ReserveMate: Failed to refresh token: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            error_log('ReserveMate: Token refresh failed with HTTP ' . $response_code . ': ' . $body);
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('ReserveMate: Invalid JSON response from token refresh');
            return false;
        }
        
        if (isset($data['success']) && $data['success']) {
            $this->store_tokens($data['tokens']);
            return $data['tokens']['access_token'];
        } else {
            // Refresh failed, clear tokens to force re-authentication
            error_log('ReserveMate: Token refresh unsuccessful: ' . print_r($data, true));
            $this->clear_all_tokens();
            return false;
        }
    }
    
    private function get_site_key() {
        $site_key = get_option('reservemate_site_key');
        if (!$site_key) {
            $site_key = wp_generate_password(32, false);
            update_option('reservemate_site_key', $site_key);
        }
        return $site_key;
    }
    
    public function revoke_authorization() {
        check_ajax_referer('revoke_google_auth', 'nonce');
        
        $refresh_token = self::get_refresh_token();
        $site_key = $this->get_site_key();
        
        $request_body = ['site_key' => $site_key];
        
        if (!empty($refresh_token)) {
            $request_body['refresh_token'] = $refresh_token;
        }
        
        $response = wp_remote_post(self::TOKEN_REVOKE_URL, [
            'body' => $request_body,
            'timeout' => 10
        ]);
        
        // Log the response for debugging
        if (is_wp_error($response)) {
            // error_log('ReserveMate: Token revocation failed: ' . $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            // error_log('ReserveMate: Token revocation response: ' . $response_code . ' - ' . $response_body);
        }
        
        $this->clear_all_tokens();
        
        wp_send_json_success();
    }
    
    public function handle_calendar_selection_change() {
        if (isset($_POST['calendar_change_only']) && $_POST['calendar_change_only'] == '1') {
            if (isset($_POST['rm_google_calendar_options']['calendar_id'])) {
                $new_calendar_id = sanitize_text_field($_POST['rm_google_calendar_options']['calendar_id']);
                $options = get_option('rm_google_calendar_options', array());
                $options['calendar_id'] = $new_calendar_id;
                update_option('rm_google_calendar_options', $options);
                
                wp_redirect(add_query_arg(array(
                    'page' => 'reserve-mate-settings',
                    'calendar_updated' => '1'
                ), admin_url('admin.php')));
                exit;
            }
        }
    }
    
    public function handle_ajax_calendar_update() {
        check_ajax_referer('update_calendar_selection', 'nonce');
        
        if (!isset($_POST['calendar_id'])) {
            wp_send_json_error('No calendar ID provided');
            return;
        }
        
        $new_calendar_id = sanitize_text_field($_POST['calendar_id']);
        $options = get_option('rm_google_calendar_options', array());
        
        if (empty($options['google_access_token'])) {
            wp_send_json_error('Not authenticated');
            return;
        }
        
        $options['calendar_id'] = $new_calendar_id;
        $result = update_option('rm_google_calendar_options', $options);
        
        if ($result) {
            wp_send_json_success('Calendar updated successfully');
        } else {
            wp_send_json_error('Failed to update calendar');
        }
    }
    
    public function show_calendar_fallback_warning() {
        if (get_transient('calendar_fallback_warning')) {
            delete_transient('calendar_fallback_warning');
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php _e('Your previously selected calendar is no longer accessible. We have switched to your primary calendar. You can change this in the settings if needed.', 'reserve-mate'); ?></p>
            </div>
            <?php
        }
    }
    
    public function show_calendar_update_message() {
        if (isset($_GET['calendar_updated']) && $_GET['calendar_updated'] == '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Calendar selection updated successfully!', 'reserve-mate'); ?></p>
            </div>
            <?php
        }
    }
    
    private function set_auth_message($type, $message) {
        set_transient('google_calendar_auth_message', array('type' => $type, 'message' => $message), 30);
    }
    
    public function show_auth_message() {
        $message = get_transient('google_calendar_auth_message');
        if ($message) {
            delete_transient('google_calendar_auth_message');
            $class = $message['type'] === 'success' ? 'notice-success' : 'notice-error';
            ?>
            <div class="notice <?php echo $class; ?> is-dismissible">
                <p><?php echo esc_html($message['message']); ?></p>
            </div>
            <?php
        }
    }
    
}

?>