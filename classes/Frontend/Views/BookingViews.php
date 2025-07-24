<?php
namespace ReserveMate\Frontend\Views;

defined('ABSPATH') or die('No direct access!');

class BookingViews {
    
    public static function render_booking_form($data = []) {
        ob_start();
        ?>
        
        <?php if (!empty($data['error_message'])): ?>
            <div class="error-message"><?php echo esc_html($data['error_message']); ?></div>
        <?php endif; ?>
        
        <?php self::render_success_modal($data['success_message']); ?>
        
        <div id="booking-form-loading" class="booking-form-loading">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <div class="loading-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="progress-text">
                        <span id="progress-percentage">0%</span>
                        <span id="progress-status">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="booking-form-wrapper" id="booking-form-wrapper" style="display: none;">
            <?php self::render_booking_form_content($data); ?>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    private static function render_success_modal($success_message) {
        ?>
        <div id="booking-success-modal" class="success-modal">
            <div class="success-modal-content">
                <button class="close-success-modal">X</button>
                <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="green" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle">
                        <path d="M9 12l2 2 4-4"></path>
                        <circle cx="12" cy="12" r="10"></circle>
                    </svg>
                </div>
                <?php echo wp_kses_post($success_message); ?>
            </div>
        </div>
        <?php
    }
    
    private static function render_booking_form_content($data) {
        ?>
        <form id="rm-booking-form" method="post">
            <div class="form-inputs booking-form-content">
                <?php self::render_form_fields($data['form_fields']); ?>
                <?php self::render_services_field($data['services'], $data['currency']); ?>
                <?php self::render_date_time_field($data['inline_calendar'], $data['plugin_url']); ?>
                <?php self::render_time_slot_field(); ?>
                <?php self::render_staff_selection_field(); ?>
                <?php self::render_hidden_fields(); ?>
                <?php self::render_agreement_text(); ?>
                <?php self::render_submit_button(); ?>
            </div>
        </form>
        <?php
    }
    
    private static function render_form_fields($form_fields) {
        $sorted_fields = $form_fields;
        usort($sorted_fields, function($a, $b) {
            return (isset($a['order']) ? $a['order'] : 999) - (isset($b['order']) ? $b['order'] : 999);
        });
        
        foreach ($sorted_fields as $field) {
            self::render_form_field($field);
        }
    }
    
    private static function render_services_field($services, $currency) {
        ?>
        <div class="form-field">
            <select name="services[]" multiple id="services" required>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo esc_attr($service->id); ?>"
                        data-price="<?php echo esc_attr($service->price); ?>"
                        data-duration="<?php echo esc_attr($service->duration); ?>"
                    ><?php echo esc_html($service->name . ' - ' . $currency . format_price($service->price)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }
    
    private static function render_date_time_field($inline_calendar, $plugin_url) {
        ?>
        <div class="date-time-container form-field">
            <?php if($inline_calendar !== "inline") : ?>
                <input type="text" id="day-selector" class="flatpick-r-datetime" placeholder="Select day" required>
                <img src="<?php echo esc_url($plugin_url); ?>assets/images/calendar-color-icon.png" alt="Calendar Icon" class="calendar-color-icon">
            <?php else : ?>
                <div id="day-selector" class="flatpick-r-datetime"></div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private static function render_time_slot_field() {
        ?>
        <div id="time-slot-container" style="display: none;">
            <div id="time-slots"></div>
        </div>
        <?php
    }
    
    private static function render_staff_selection_field() {
        ?>
        <div id="staff-selection-container" style="display: none;">
            <div id="staff-options"></div>
        </div>
        <?php
    }
    
    private static function render_hidden_fields() {
        ?>
        <input type="hidden" id="start-date" name="start-date" value="" required>
        <input type="hidden" id="end-date" name="end-date" value="" required>
        <input type="hidden" id="staff-id" name="staff-id">
        <input type="hidden" name="total-payment-cost" id="total-payment-cost" value="">
        <?php wp_nonce_field('final_form_submit', 'frontend_booking_nonce'); ?>
        <?php
    }
    
    private static function render_agreement_text() {
        ?>
        <div class="agreement-container">
            <p class="agreement-notice">
                By continuing, you agree to our <a href="<?php echo esc_url( home_url( '/terms-conditions' ) ); ?>"><?php _e('Terms & Conditions', 'reserve-mate'); ?></a>
                and <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php _e('Privacy Policy', 'reserve-mate'); ?></a></p>
        </div>
        <?php
    }
    
    private static function render_submit_button() {
        ?>
        <div class="form-field">
            <input type="submit" name="proceed-to-checkout" id="proceed-to-checkout" value="<?php _e('Book Now', 'reserve-mate'); ?>">
        </div>
        <?php
    }
    
    private static function render_form_field($field) {
        $id = esc_attr($field['id']);
        $label = esc_html($field['label']);
        $type = esc_attr($field['type']);
        $placeholder = isset($field['placeholder']) ? esc_attr($field['placeholder']) : '';
        $required = isset($field['required']) && $field['required'] ? 'required' : '';
        $options = isset($field['options']) ? $field['options'] : '';
        $autocomplete = isset($field['autocomplete']) ? esc_attr($field['autocomplete']) : 'on';
        
        $field_name = in_array($id, ['name', 'email', 'phone']) ? $id : 'custom_' . $id;
        
        echo '<div class="form-field">';
        
        switch ($type) {
            case 'textarea':
                echo "<textarea id=\"{$id}\" name=\"{$field_name}\" placeholder=\"{$placeholder}\" {$required}></textarea>";
                break;
                
            case 'select':
                self::render_select_field($id, $field_name, $placeholder, $options, $required);
                break;
                
            case 'checkbox':
                self::render_checkbox_field($id, $field_name, $label, $options, $required);
                break;
                
            case 'radio':
                self::render_radio_field($id, $field_name, $options, $required);
                break;
                
            default:
                echo "<input type=\"{$type}\" id=\"{$id}\" name=\"{$field_name}\" placeholder=\"{$placeholder}\" autocomplete=\"{$autocomplete}\" {$required}>";
                break;
        }
        
        echo '</div>';
    }
    
    private static function render_select_field($id, $field_name, $placeholder, $options, $required) {
        echo "<select id=\"{$id}\" name=\"{$field_name}\" {$required}>";
        echo "<option value=\"\" disabled selected>{$placeholder}</option>";
        
        if (!empty($options)) {
            $option_lines = explode("\n", $options);
            foreach ($option_lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    echo "<option value=\"" . esc_attr(trim($key)) . "\">" . esc_html(trim($value)) . "</option>";
                } else {
                    echo "<option value=\"" . esc_attr($line) . "\">" . esc_html($line) . "</option>";
                }
            }
        }
        
        echo "</select>";
    }
    
    private static function render_checkbox_field($id, $field_name, $label, $options, $required) {
        if (!empty($options)) {
            $option_lines = explode("\n", $options);
            foreach ($option_lines as $i => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $option_id = "{$id}_{$i}";
                $option_value = $line;
                $option_label = $line;
                
                if (strpos($line, ':') !== false) {
                    list($option_value, $option_label) = explode(':', $line, 2);
                    $option_value = trim($option_value);
                    $option_label = trim($option_label);
                }
                
                echo "<div class=\"checkbox-option\">";
                echo "<input type=\"checkbox\" id=\"{$option_id}\" name=\"{$field_name}[]\" value=\"" . esc_attr($option_value) . "\" {$required}>";
                echo "<label for=\"{$option_id}\">" . esc_html($option_label) . "</label>";
                echo "</div>";
            }
        } else {
            echo "<input type=\"checkbox\" id=\"{$id}\" name=\"{$field_name}\" value=\"1\" {$required}>";
            echo "<label for=\"{$id}\">" . esc_html($label) . "</label>";
        }
    }
    
    private static function render_radio_field($id, $field_name, $options, $required) {
        if (!empty($options)) {
            $option_lines = explode("\n", $options);
            foreach ($option_lines as $i => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $option_id = "{$id}_{$i}";
                $option_value = $line;
                $option_label = $line;
                
                if (strpos($line, ':') !== false) {
                    list($option_value, $option_label) = explode(':', $line, 2);
                    $option_value = trim($option_value);
                    $option_label = trim($option_label);
                }
                
                echo "<div class=\"radio-option\">";
                echo "<input type=\"radio\" id=\"{$option_id}\" name=\"{$field_name}\" value=\"" . esc_attr($option_value) . "\" {$required}>";
                echo "<label for=\"{$option_id}\">" . esc_html($option_label) . "</label>";
                echo "</div>";
            }
        }
    }
}