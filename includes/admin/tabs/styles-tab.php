<?php
defined('ABSPATH') or die('No direct access!');

function display_calendar_display_type_field() {
    $options = get_option('rm_style_options');
    $calendar_type = isset($options['calendar_display_type']) ? $options['calendar_display_type'] : 'popup';
    ?>
    <select name="rm_style_options[calendar_display_type]">
        <option value="popup" <?php selected($calendar_type, 'popup'); ?>><?php _e('Pop-up Calendar View', 'reserve-mate'); ?></option>
        <option value="inline" <?php selected($calendar_type, 'inline'); ?>><?php _e('Inline Calendar View', 'reserve-mate'); ?></option>
    </select>
    <p class="description">
        <?php _e('Choose between a pop-up calendar display or an inline(always visible) calendar.', 'reserve-mate'); ?>
    </p>
    <?php
}


function display_calendar_theme_field() {
    $options = get_option('rm_style_options');
    $theme = isset($options['calendar_theme']) ? $options['calendar_theme'] : 'default';
    $themes = [
        'default' => __('Default (Light)', 'reserve-mate'),
        'dark'    => __('Dark', 'reserve-mate'),
        'material'=> __('Material Design', 'reserve-mate'),
        'custom'  => __('Custom Styling', 'reserve-mate'),
    ];
    ?>
    <select name="rm_style_options[calendar_theme]" id="calendar_theme">
        <?php foreach ($themes as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($theme, $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php _e('Choose a premade theme or use "Custom Styling" to override.', 'reserve-mate'); ?>
    </p>
    <?php
}

function display_primary_color_field() {
    $options = get_option('rm_style_options');
    $color = isset($options['primary_color']) ? $options['primary_color'] : '#3B82F6';
    ?>
    <input type="color" name="rm_style_options[primary_color]" value="<?php echo esc_attr($color); ?>">
    <p class="description"><?php _e('Affects buttons, highlights, and accents.', 'reserve-mate'); ?></p>
    <?php
}

function display_text_color_field() {
    $options = get_option('rm_style_options');
    $color = isset($options['text_color']) ? $options['text_color'] : '#333333';
    ?>
    <input type="color" name="rm_style_options[text_color]" value="<?php echo esc_attr($color); ?>">
    <p class="description"><?php _e('Main text color for the calendar.', 'reserve-mate'); ?></p>
    <?php
}

function display_font_family_field() {
    $options = get_option('rm_style_options');
    $font = isset($options['font_family']) ? $options['font_family'] : 'inherit';
    $fonts = [
        'inherit' => __('System Default', 'reserve-mate'),
        'Arial'   => 'Arial',
        'Helvetica' => 'Helvetica',
        'Roboto'  => 'Roboto',
        'Open Sans' => 'Open Sans',
        'Courier New' => 'Courier New',
    ];
    ?>
    <select name="rm_style_options[font_family]">
        <?php foreach ($fonts as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($font, $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function display_color_field($args) {
    $options = get_option('rm_style_options');
    $value = $options[$args['name']] ?? $args['default'];
    ?>
    <input type="text" 
           name="rm_style_options[<?php echo esc_attr($args['name']); ?>]" 
           value="<?php echo esc_attr($value); ?>" 
           class="color-field regular-text"
           placeholder="<?php echo esc_attr($args['default']); ?>"
           data-default-color="<?php echo esc_attr($args['default']); ?>">
    <p class="description">
        <?php
        /* translators: %s is the default color value */
        printf(__('Default: %s', 'reserve-mate'), '<code>' . esc_html($args['default']) . '</code>');
        ?>
    </p>
    <?php
}

function display_color_field_wrapper($name, $default, $description = '') {
    display_color_field([
        'name' => $name,
        'default' => $default,
        'description' => $description
    ]);
}

function display_gradient_field_wrapper($name, $default) {
    $options = get_option('rm_style_options');
    $value = $options[$name] ?? $default;
    ?>
    <input type="text" name="rm_style_options[<?php echo $name; ?>]" 
           value="<?php echo esc_attr($value); ?>" class="regular-text">
    <p class="description">Example: <?php echo esc_html($default); ?></p>
    <?php
}