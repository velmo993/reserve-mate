<?php
defined('ABSPATH') or die('No direct access!');

function display_form_fields() {
    $options = get_option('rm_form_options');
    $form_fields = isset($options['form_fields']) ? $options['form_fields'] : array();
    
    usort($form_fields, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    ?>
    <div class="form-fields-container">
        <div class="form-fields-list">
            <?php foreach ($form_fields as $index => $field): ?>
            <div class="form-field-item" data-index="<?php echo $index; ?>">
                <h4 class="field-header">
                    <span class="field-drag-handle dashicons dashicons-move"></span>
                    <span class="field-title"><?php echo esc_html($field['label']); ?></span>
                    <span class="field-type">(<?php echo esc_html($field['type']); ?>)</span>
                    <span class="field-actions">
                        <button type="button" class="edit-field button button-small">
                            <span class="dashicons dashicons-edit"></span> Edit
                        </button>
                        <button type="button" class="remove-field button button-small">
                            <span class="dashicons dashicons-trash"></span> Remove
                        </button>
                    </span>
                </h4>
                <div class="field-details" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Field ID</th>
                            <td>
                                <input type="text" name="rm_form_options[form_fields][<?php echo $index; ?>][id]" 
                                    value="<?php echo esc_attr($field['id']); ?>" class="regular-text" required>
                                <p class="description">Unique identifier for the field (no spaces). Use as variable: {<?php echo esc_attr($field['id']); ?>}</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Label</th>
                            <td>
                                <input type="text" name="rm_form_options[form_fields][<?php echo $index; ?>][label]" 
                                    value="<?php echo esc_attr($field['label']); ?>" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Field Type</th>
                            <td>
                                <select name="rm_form_options[form_fields][<?php echo $index; ?>][type]">
                                    <option value="text" <?php selected($field['type'], 'text'); ?>>Text</option>
                                    <option value="email" <?php selected($field['type'], 'email'); ?>>Email</option>
                                    <option value="tel" <?php selected($field['type'], 'tel'); ?>>Telephone</option>
                                    <option value="number" <?php selected($field['type'], 'number'); ?>>Number</option>
                                    <option value="date" <?php selected($field['type'], 'date'); ?>>Date</option>
                                    <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Text Area</option>
                                    <option value="select" <?php selected($field['type'], 'select'); ?>>Dropdown</option>
                                    <option value="checkbox" <?php selected($field['type'], 'checkbox'); ?>>Checkbox</option>
                                    <option value="radio" <?php selected($field['type'], 'radio'); ?>>Radio Buttons</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Placeholder</th>
                            <td>
                                <input type="text" name="rm_form_options[form_fields][<?php echo $index; ?>][placeholder]" 
                                    value="<?php echo esc_attr(isset($field['placeholder']) ? $field['placeholder'] : ''); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr class="field-options-row" style="<?php echo ($field['type'] === 'select' || $field['type'] === 'radio' || $field['type'] === 'checkbox') ? '' : 'display: none;'; ?>">
                            <th scope="row">Options</th>
                            <td>
                                <textarea name="rm_form_options[form_fields][<?php echo $index; ?>][options]" 
                                    class="large-text" rows="4"><?php echo isset($field['options']) ? esc_textarea($field['options']) : ''; ?></textarea>
                                <p class="description">Enter options, one per line. For key-value pairs use format: key:value</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Required</th>
                            <td>
                                <input type="checkbox" name="rm_form_options[form_fields][<?php echo $index; ?>][required]" 
                                    <?php checked(isset($field['required']) && $field['required']); ?> value="1">
                                <span class="description">Make this field required</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Order</th>
                            <td>
                                <input type="number" name="rm_form_options[form_fields][<?php echo $index; ?>][order]" 
                                    value="<?php echo esc_attr(isset($field['order']) ? $field['order'] : 1); ?>" min="1" class="small-text">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="form-fields-actions">
            <button type="button" class="add-new-field button button-secondary">
                <span class="dashicons dashicons-plus"></span> Add New Field
            </button>
        </div>
        
        <script type="text/html" id="field-template">
            <div class="form-field-item" data-index="{{index}}">
                <h4 class="field-header">
                    <span class="field-drag-handle dashicons dashicons-move"></span>
                    <span class="field-title">New Field</span>
                    <span class="field-type">(text)</span>
                    <span class="field-actions">
                        <button type="button" class="edit-field button button-small">
                            <span class="dashicons dashicons-edit"></span> Edit
                        </button>
                        <button type="button" class="remove-field button button-small">
                            <span class="dashicons dashicons-trash"></span> Remove
                        </button>
                    </span>
                </h4>
                <div class="field-details">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Field ID</th>
                            <td>
                                <input type="text" name="rm_form_options[form_fields][{{index}}][id]" 
                                    value="field_{{index}}" class="regular-text field-id-input" required>
                                <p class="description">Unique identifier for the field (no spaces). Use as variable: {field_{{index}}}</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Label</th>
                            <td>
                                <input type="text" name="rm_form_options[form_fields][{{index}}][label]" 
                                    value="New Field" class="regular-text field-label-input" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Field Type</th>
                            <td>
                                <select name="rm_form_options[form_fields][{{index}}][type]" class="field-type-select">
                                    <option value="text">Text</option>
                                    <option value="email">Email</option>
                                    <option value="tel">Telephone</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="textarea">Text Area</option>
                                    <option value="select">Dropdown</option>
                                    <option value="checkbox">Checkbox</option>
                                    <option value="radio">Radio Buttons</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Placeholder</th>
                            <td>
                                <input type="text" name="rm_form_options[form_fields][{{index}}][placeholder]" 
                                    value="" class="regular-text">
                            </td>
                        </tr>
                        <tr class="field-options-row" style="display: none;">
                            <th scope="row">Options</th>
                            <td>
                                <textarea name="rm_form_options[form_fields][{{index}}][options]" 
                                    class="large-text" rows="4"></textarea>
                                <p class="description">Enter options, one per line. For key-value pairs use format: key:value</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Required</th>
                            <td>
                                <input type="checkbox" name="rm_form_options[form_fields][{{index}}][required]" value="1">
                                <span class="description">Make this field required</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Order</th>
                            <td>
                                <input type="number" name="rm_form_options[form_fields][{{index}}][order]" 
                                    value="{{order}}" min="1" class="small-text">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </script>
        <input type="hidden" id="field-count" value="<?php echo count($form_fields); ?>">
    </div>

    <style>
        .form-field-item {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin-bottom: 10px;
        }
        .field-header {
            padding: 8px 12px;
            margin: 0;
            border-bottom: 1px solid #eee;
            cursor: move;
            display: flex;
            align-items: center;
        }
        .field-title {
            flex-grow: 1;
            font-weight: 600;
        }
        .field-type {
            color: #666;
            margin-right: 10px;
        }
        .field-drag-handle {
            margin-right: 10px;
            cursor: move;
        }
        .field-details {
            padding: 12px;
            background: #fbfbfb;
        }
        .form-fields-actions {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .remove-field {
            color: #a00;
        }
        .remove-field:hover {
            color: #dc3232;
            border-color: #dc3232;
        }
    </style>
    <?php
}