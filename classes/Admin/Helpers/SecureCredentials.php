<?php
/**
 * Secure credential storage helper for ReserveMate
 * Encrypts sensitive data before storing in WordPress options
 */

namespace ReserveMate\Admin\Helpers;

defined('ABSPATH') or die('No direct access!');

class SecureCredentials {
    
    const STRIPE = 'stripe_';
    const PAYPAL = 'paypal_';
    const GOOGLE = 'google_';
    const SMTP = 'smtp_';
    
    /**
     * Generate or get encryption key
     */
    private static function get_encryption_key($prefix) {
        $option_name = 'rm_' . $prefix . 'encryption_key';
        $key = get_option($option_name);
        
        if (!$key) {
            $key = base64_encode(random_bytes(32));
            add_option($option_name, $key, '', 'no');
        }
        
        return base64_decode($key);
    }
    
    /**
     * Encrypt sensitive data
     */
    public static function encrypt($data, $prefix) {
        if (empty($data)) return '';
        $key = self::get_encryption_key($prefix);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public static function decrypt($encrypted_data, $prefix) {
        if (empty($encrypted_data)) return '';
        try {
            $key = self::get_encryption_key($prefix);
            $data = base64_decode($encrypted_data);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        } catch (Exception $e) {
            error_log("Decryption failed for {$prefix}: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Store encrypted credential
     */
    public static function store_credential($option_name, $key, $value) {
        $options = get_option($option_name, []);
        $options[$key] = self::encrypt($value);
        update_option($option_name, $options);
    }
    
    /**
     * Get decrypted credential
     */
    public static function get_credential($option_name, $key, $default = '') {
        $options = get_option($option_name, []);
        
        if (!isset($options[$key])) {
            return $default;
        }
        
        $decrypted = self::decrypt($options[$key]);
        return $decrypted !== false ? $decrypted : $default;
    }
    
    /**
     * Check if a value is encrypted (basic check)
     */
    public static function is_encrypted($value) {
        // Simple heuristic: encrypted values are base64 and longer than plain text
        return !empty($value) && base64_encode(base64_decode($value, true)) === $value && strlen($value) > 50;
    }
    
}