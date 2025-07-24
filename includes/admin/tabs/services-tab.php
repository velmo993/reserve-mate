<?php
defined('ABSPATH') or die('No direct access!');

function display_max_selectable_services() {
    $options = get_option('rm_service_options');
    $max_services = isset($options['max_selectable_services']) ? $options['max_selectable_services'] : '5';
    ?>
    <input type="number" name="rm_service_options[max_selectable_services]" 
           value="<?php echo esc_attr($max_services); ?>" 
           min="1" max="10" step="1">
    <p class="description">
        <?php _e('Maximum number of services that can be selected simultaneously.', 'reserve-mate'); ?>
    </p>
    <?php
}