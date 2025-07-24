<?php
namespace ReserveMate\Shared\Helpers;

defined('ABSPATH') or die('No direct access!');

class FormFieldHelpers {
    
    // Predefined field types with their properties
    private static $field_types = [
        'basic_info' => [
            'label' => 'Basic Information',
            'fields' => [
                'name' => [
                    'label' => 'Full Name',
                    'type' => 'text',
                    'required' => true,
                    'core' => true,
                    'placeholder' => 'Enter your full name',
                    'validation' => 'text',
                    'email_label' => 'Client Name'
                ],
                'first_name' => [
                    'label' => 'First Name',
                    'type' => 'text',
                    'placeholder' => 'Enter your first name',
                    'validation' => 'text',
                    'email_label' => 'First Name'
                ],
                'last_name' => [
                    'label' => 'Last Name',
                    'type' => 'text',
                    'placeholder' => 'Enter your last name',
                    'validation' => 'text',
                    'email_label' => 'Last Name'
                ],
                'email' => [
                    'label' => 'Email Address',
                    'type' => 'email',
                    'required' => true,
                    'core' => true,
                    'placeholder' => 'Enter your email address',
                    'validation' => 'email',
                    'email_label' => 'Email'
                ],
                'phone' => [
                    'label' => 'Phone Number',
                    'type' => 'tel',
                    'required' => true,
                    'core' => true,
                    'placeholder' => 'Enter your phone number',
                    'validation' => 'phone',
                    'email_label' => 'Phone'
                ],
                'date_of_birth' => [
                    'label' => 'Date of Birth',
                    'type' => 'date',
                    'placeholder' => 'Select your date of birth',
                    'validation' => 'date',
                    'email_label' => 'Date of Birth'
                ],
                'gender' => [
                    'label' => 'Gender',
                    'type' => 'select',
                    'placeholder' => 'Select gender',
                    'options' => "male:Male\nfemale:Female\nnon_binary:Non-binary\nprefer_not_to_say:Prefer not to say",
                    'validation' => 'text',
                    'email_label' => 'Gender'
                ]
            ]
        ],
        
    ];
    
    public static function get_field_types() {
        return self::$field_types;
    }
    
    public static function get_available_fields() {
        $available_fields = [];
        foreach (self::$field_types as $category => $data) {
            foreach ($data['fields'] as $field_id => $field_config) {
                $available_fields[$field_id] = array_merge($field_config, [
                    'category' => $category,
                    'category_label' => $data['label']
                ]);
            }
        }
        return $available_fields;
    }
    
    public static function is_core_field($field_id) {
        $available_fields = self::get_available_fields();
        return isset($available_fields[$field_id]['core']) && $available_fields[$field_id]['core'];
    }
    
    public static function get_field_config($field_id) {
        $available_fields = self::get_available_fields();
        return isset($available_fields[$field_id]) ? $available_fields[$field_id] : null;
    }
    
    public static function validate_field_value($field_id, $value) {
        $field_config = self::get_field_config($field_id);
        if (!$field_config) {
            return false;
        }
        
        $validation_type = $field_config['validation'] ?? 'text';
        
        switch ($validation_type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'phone':
                return preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[^\d\+]/', '', $value));
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'number':
                return is_numeric($value);
            case 'date':
                return DateTime::createFromFormat('Y-m-d', $value) !== false;
            case 'array':
                return is_array($value) || !empty($value);
            default:
                return !empty($value) || $value === '0';
        }
    }
    
    public static function sanitize_field_value($field_id, $value) {
        $field_config = self::get_field_config($field_id);
        if (!$field_config) {
            return sanitize_text_field($value);
        }
        
        $validation_type = $field_config['validation'] ?? 'text';
        
        switch ($validation_type) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'number':
                return floatval($value);
            case 'array':
                return is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            default:
                return sanitize_textarea_field($value);
        }
    }
    
    public static function get_form_field_for_email($field_id, $value) {
        $field_config = self::get_field_config($field_id);
        if (!$field_config) {
            return null;
        }
        
        $label = $field_config['email_label'] ?? $field_config['label'];
        
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        
        return [
            'label' => $label,
            'value' => $value,
            'is_core' => self::is_core_field($field_id)
        ];
    }
    
    public static function process_form_submission($post_data) {
        $processed_data = [
            'core_fields' => [],
            'custom_fields' => [],
            'validation_errors' => []
        ];
        
        $available_fields = self::get_available_fields();
        
        foreach ($post_data as $key => $value) {
            // Handle custom field prefix
            $field_id = $key;
            if (strpos($key, 'custom_') === 0) {
                $field_id = substr($key, 7); // Remove 'custom_' prefix
            }
            
            if (!isset($available_fields[$field_id])) {
                continue; // Skip unknown fields
            }
            
            $field_config = $available_fields[$field_id];
            
            // Check if field is required
            if (isset($field_config['required']) && $field_config['required']) {
                if (empty($value) && $value !== '0') {
                    $processed_data['validation_errors'][$field_id] = $field_config['label'] . ' is required.';
                    continue;
                }
            }
            
            // Validate field value
            if (!empty($value) && !self::validate_field_value($field_id, $value)) {
                $processed_data['validation_errors'][$field_id] = $field_config['label'] . ' has an invalid format.';
                continue;
            }
            
            // Sanitize field value
            $sanitized_value = self::sanitize_field_value($field_id, $value);
            
            // Separate core fields from custom fields
            if (self::is_core_field($field_id)) {
                $processed_data['core_fields'][$field_id] = $sanitized_value;
            } else {
                $processed_data['custom_fields'][$field_id] = $sanitized_value;
            }
        }
        
        return $processed_data;
    }
    
    public static function generate_email_content($custom_fields) {
        if (empty($custom_fields)) {
            return '';
        }
        
        $content = '<div style="margin-top: 20px;"><h3>Additional Information:</h3><ul>';
        
        foreach ($custom_fields as $field_id => $value) {
            $field_info = self::get_form_field_for_email($field_id, $value);
            if ($field_info) {
                $content .= '<li><strong>' . esc_html($field_info['label']) . ':</strong> ' . esc_html($field_info['value']) . '</li>';
            }
        }
        
        $content .= '</ul></div>';
        
        return $content;
    }
}