<?php

// Handle form submissions
function handle_property_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_property'])) {
        if (!isset($_POST['property_nonce']) || !wp_verify_nonce($_POST['property_nonce'], 'save_property')) {
            wp_die('Security check failed');
        }

        $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;

        $property_data = [
            'name'              => sanitize_text_field($_POST['name']),
            'max_adult_number'  => isset($_POST['max_adult_number']) ? intval($_POST['max_adult_number']) : 0,
            'adult_price'       => isset($_POST['adult_price']) ? floatval($_POST['adult_price']) : 0,
            'allow_children'    => isset($_POST['allow_children']) ? 1 : 0,
            'max_child_number'  => isset($_POST['max_child_number']) ? intval($_POST['max_child_number']) : 0,
            'child_price'       => isset($_POST['child_price']) ? floatval($_POST['child_price']) : 0,
            'allow_pets'        => isset($_POST['allow_pets']) ? 1 : 0,
            'max_pet_number'    => isset($_POST['max_pet_number']) ? intval($_POST['max_pet_number']) : 0,
            'pet_price'         => isset($_POST['pet_price']) ? floatval($_POST['pet_price']) : 0,
            'min_stay'          => isset($_POST['min_stay']) ? intval($_POST['min_stay']) : 0,
            'max_stay'          => isset($_POST['max_stay']) ? intval($_POST['max_stay']) : 0,
            'partial_days'      => isset($_POST['partial_days']) ? 1 : 0,
            'check_in_time_start' => isset($_POST['check_in_time_start']) ? sanitize_text_field($_POST['check_in_time_start']) : '',
            'check_in_time_end'   => isset($_POST['check_in_time_end']) ? sanitize_text_field($_POST['check_in_time_end']) : '',
            'check_out_time_start' => isset($_POST['check_out_time_start']) ? sanitize_text_field($_POST['check_out_time_start']) : '',
            'check_out_time_end'   => isset($_POST['check_out_time_end']) ? sanitize_text_field($_POST['check_out_time_end']) : '',
            'seasonal_rules'    => isset($_POST['seasonal_rules']) ? array_map('intval', $_POST['seasonal_rules']) : [],
            'disabled_dates'    => isset($_POST['disabled_dates']) ? $_POST['disabled_dates'] : []
        ];

        save_property($property_data, $editing_index);
        wp_redirect(admin_url('admin.php?page=manage-properties'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Property saved successfully.', 'reserve-mate') . '</p></div>';
    }

    if (isset($_GET['delete'])) {
        if (!isset($_GET['delete_nonce']) || !wp_verify_nonce($_GET['delete_nonce'], 'delete_property')) {
            wp_die('Security check failed');
        }

        delete_property(intval($_GET['delete']));
        wp_redirect(admin_url('admin.php?page=manage-properties'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Property deleted successfully.', 'reserve-mate') . '</p></div>';
    }
}

// Display the properties management page
function manage_properties_page() {
    handle_property_form_submission();

    $currency_symbol = get_currency();
    $properties = get_properties();
    $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $editing_property = $editing_index ? get_property($editing_index) : null;

    ?>
    <div class="wrap">
        <h1>Manage Properties</h1>
        
        <?php if ($editing_property): ?>
            <h2>Edit Property</h2>
            <?php display_property_form($editing_property); ?>
        <?php else: ?>
            <button id="toggle-form-btn" class="button button-primary" style="margin-bottom: 20px;">
                <?php _e('Add New Property', 'reserve-mate'); ?>
            </button>
            
            <div id="property-form" style="display: none;">
                <h2>Add New Property</h2>
                <?php display_property_form(); ?>
            </div>
        <?php endif; ?>
        
        <?php display_existing_properties_table($properties); ?>
    </div>
    <?php
}

function display_property_form($property = null) {
    $currency_symbol = get_currency();
    ?>
    <form method="post">
        <?php wp_nonce_field('save_property', 'property_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="name"><?php _e('Property Name', 'reserve-mate'); ?></label><i class="star-required">*</i></th>
                <td>
                    <input type="text" name="name" value="<?php echo esc_attr($property->name ?? ''); ?>" required>
                    <p class="description"><?php _e('Enter the name of the property.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="max_adult_number"><?php _e('Adults (Max)', 'reserve-mate'); ?></label><i class="star-required">*</i></th>
                <td>
                    <input name="max_adult_number" type="number" min="1" value="<?php echo esc_attr($property->max_adult_number ?? ''); ?>" required>
                    <p class="description"><?php _e('Maximum number of adults allowed.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="adult_price"><?php _e('Adult Rate', 'reserve-mate'); ?> (<?php echo $currency_symbol; ?>)</label><i class="star-required">*</i></th>
                <td>
                    <input name="adult_price" type="number" step="0.01" value="<?php echo esc_attr($property->adult_price ?? ''); ?>" required>
                    <p class="description"><?php _e('Rate per adult per day.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="allow_children"><?php _e('Children Allowed', 'reserve-mate'); ?></label></th>
                <td>
                    <input type="checkbox" id="allow_children" name="allow_children" value="1" <?php checked($property->allow_children ?? 0, 1); ?>>
                    <p class="description"><?php _e('Check this if children are allowed.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr class="children-field hidden">
                <th><label for="max_child_number"><?php _e('Children (Max)', 'reserve-mate'); ?></label></th>
                <td>
                    <input name="max_child_number" type="number" value="<?php echo esc_attr($property->max_child_number ?? ''); ?>">
                    <p class="description"><?php _e('Maximum number of children allowed.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr class="children-field hidden">
                <th><label for="child_price"><?php _e('Child Rate', 'reserve-mate'); ?> (<?php echo $currency_symbol; ?>)</label></th>
                <td>
                    <input name="child_price" type="number" step="0.01" value="<?php echo esc_attr($property->child_price ?? ''); ?>">
                    <p class="description"><?php _e('Rate per child per day.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="allow_pets"><?php _e('Pets Allowed', 'reserve-mate'); ?></label></th>
                <td>
                    <input type="checkbox" id="allow_pets" name="allow_pets" value="1" <?php checked($property->allow_pets ?? 0, 1); ?>>
                    <p class="description"><?php _e('Check this if pets are allowed.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr class="pets-field hidden">
                <th><label for="max_pet_number"><?php _e('Pets (Max)', 'reserve-mate'); ?></label></th>
                <td>
                    <input name="max_pet_number" type="number" value="<?php echo esc_attr($property->max_pet_number ?? ''); ?>">
                    <p class="description"><?php _e('Maximum number of pets allowed.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr class="pets-field hidden">
                <th><label for="pet_price"><?php _e('Pet Rate', 'reserve-mate'); ?> (<?php echo $currency_symbol; ?>)</label></th>
                <td>
                    <input name="pet_price" type="number" step="0.01" value="<?php echo esc_attr($property->pet_price ?? ''); ?>">
                    <p class="description"><?php _e('Rate per pet per day.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="min_stay"><?php _e('Minimum Stay', 'reserve-mate'); ?></label></th>
                <td>
                    <input type="number" name="min_stay" min="0" value="<?php echo esc_attr($property->min_stay ?? ''); ?>">
                    <p class="description"><?php _e('Minimum number of nights required for a booking.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="max_stay"><?php _e('Maximum Stay', 'reserve-mate'); ?></label></th>
                <td>
                    <input type="number" name="max_stay" min="0" value="<?php echo esc_attr($property->max_stay ?? ''); ?>">
                    <p class="description"><?php _e('Maximum number of nights allowed for a booking.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="check_in_time_start"><?php _e('Check-in Time', 'reserve-mate'); ?></label></th>
                <td>
                    <input name="check_in_time_start" type="text" placeholder="14:00" value="<?php echo esc_attr($property->check_in_time_start ?? ''); ?>">
                    <input name="check_in_time_end" type="text" placeholder="17:00" value="<?php echo esc_attr($property->check_in_time_end ?? ''); ?>">
                    <p class="description"><?php _e('Check-in time from - to', 'reserve-mate'); ?></p>
               </td>
            </tr>
                
            <tr>
                <th><label for="check_out_time_start"><?php _e('Check-out Time', 'reserve-mate'); ?></label></th>
                <td>
                    <input name="check_out_time_start" type="text" placeholder="08:00" value="<?php echo esc_attr($property->check_out_time_start ?? ''); ?>">
                    <input name="check_out_time_end" type="text" placeholder="12:00" value="<?php echo esc_attr($property->check_out_time_end ?? ''); ?>">
                    <p class="description"><?php _e('Check-out time from - to', 'reserve-mate'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><strong><?php _e('Seasonal Rules', 'reserve-mate'); ?></strong></th>
                <td>
                    <button id="seasonal-rules-btn" type="button"><i>▼</i></button>
                    <p class="description"><?php _e('Define minimum and maximum stay for specific months.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <?php
            $seasonal_rules = !empty($property->seasonal_rules) ? json_decode($property->seasonal_rules, true) : [];
            for ($month = 1; $month <= 12; $month++):
                $month_name = date_i18n('F', mktime(0, 0, 0, $month, 1)); // Translate month names
                $min_value = $seasonal_rules[$month]['min'] ?? '';
                $max_value = $seasonal_rules[$month]['max'] ?? '';
            ?>
                <tr class="seasonal-rules-container hidden">
                    <th><?php echo esc_html($month_name); ?> (<?php _e('Min / Max Stay', 'reserve-mate'); ?>)</th>
                    <td>
                        <input type="number" name="seasonal_rules[<?php echo $month; ?>][min]" min="0" placeholder="<?php _e('Min', 'reserve-mate'); ?>" value="<?php echo esc_attr($min_value); ?>">
                        <input type="number" name="seasonal_rules[<?php echo $month; ?>][max]" min="0" placeholder="<?php _e('Max', 'reserve-mate'); ?>" value="<?php echo esc_attr($max_value); ?>">
                    </td>
                </tr>
            <?php endfor; ?>
            <tr>
                <th><strong><?php _e('Disable Dates', 'reserve-mate'); ?></strong></th>
                <td>
                    <button id="disabled-dates-btn" type="button"><i>▼</i></button>
                    <p class="description"><?php _e('Define dates or date patterns that should be disabled for booking.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr class="disabled-dates-container hidden">
                <td colspan="2">
                    <div id="disabled-dates-rules">
                        <?php
                        $disabled_dates = !empty($property->disabled_dates) ? json_decode($property->disabled_dates, true) : [];
                        if (!empty($disabled_dates)) {
                            foreach ($disabled_dates as $index => $rule) {
                                display_disabled_date_rule($index, $rule);
                            }
                        }
                        ?>
                    </div>
                    <button type="button" id="add-disabled-date-rule" class="button"><?php _e('Add New Rule', 'reserve-mate'); ?></button>
                </td>
            </tr>
            <tr>
                <th><label for="partial_days"><?php _e('Partial Booking Days / Half Days', 'reserve-mate'); ?></label></th>
                <td>
                    <input type="checkbox" id="partial_days" name="partial_days" value="1" <?php checked($property->partial_days ?? 0, 1); ?>>
                    <p class="description">
                        <?php _e('Enable this if you allow new guests to check in during the afternoon of the same day when previous guests check out in the morning.', 'reserve-mate'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <p>
            <input type="submit" name="save_property" class="button button-primary" value="<?php echo $property ? __('Update Property', 'reserve-mate') : __('Save Property', 'reserve-mate'); ?>">
            <?php if ($property): ?>
                <a href="<?php echo admin_url('admin.php?page=manage-properties'); ?>" class="button"><?php _e('Cancel', 'reserve-mate'); ?></a>
            <?php endif; ?>
        </p>
    </form>
    <?php
}

function display_existing_properties_table($properties) {
    $currency_symbol = get_currency();
    ?>
    <h2><?php _e('Existing Properties', 'reserve-mate'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'reserve-mate'); ?></th>
                <th><?php _e('Property', 'reserve-mate'); ?></th>
                <th><?php _e('Min Stay', 'reserve-mate'); ?></th>
                <th><?php _e('Max Stay', 'reserve-mate'); ?></th>
                <th><?php _e('Details', 'reserve-mate'); ?></th>
                <th><?php _e('Actions', 'reserve-mate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($properties): ?>
            <?php foreach ($properties as $index => $property): ?>
                <tr class="property-summary">
                    <td><?php echo esc_html($property->id); ?></td>
                    <td><?php echo esc_html($property->name); ?></td>
                    <td><?php echo esc_html($property->min_stay); ?></td>
                    <td><?php echo esc_html($property->max_stay); ?></td>
                    <td>
                        <button class="toggle-details-property" data-property-id="<?php echo esc_attr($property->id); ?>"><i>▼</i></button>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=manage-properties&edit=' . $property->id); ?>" class="button"><?php _e('Edit', 'reserve-mate'); ?></a>
                        <a href="<?php echo admin_url('admin.php?page=manage-properties&delete=' . $property->id . '&delete_nonce=' . wp_create_nonce('delete_property')); ?>" class="button button-danger" onclick="return confirm('<?php echo esc_attr(__('Are you sure you want to delete this property?', 'reserve-mate')); ?>');"><?php _e('Delete', 'reserve-mate'); ?></a>
                    </td>
                </tr>
                <tr class="table-details" id="details-<?php echo esc_attr($property->id); ?>" style="display: none;">
                    <td colspan="6">
                        <div class="table-details-flex"><strong><?php _e('Check-in Time:', 'reserve-mate'); ?></strong>
                            <span class="property-data">
                                <?php echo !empty($property->check_in_time_start) && !empty($property->check_in_time_end) ? esc_html($property->check_in_time_start). '-' .esc_html($property->check_in_time_end) : __('Not Set', 'reserve-mate'); ?>
                            </span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Check-out Time:', 'reserve-mate'); ?></strong>
                            <span class="property-data">
                                <?php echo !empty($property->check_out_time_start) && !empty($property->check_out_time_end) ? esc_html($property->check_out_time_start). '-' .esc_html($property->check_out_time_end) : __('Not Set', 'reserve-mate'); ?>
                            </span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Adults(Max):', 'reserve-mate'); ?></strong>
                        <span class="property-data"><?php echo $property->max_adult_number ? esc_html($property->max_adult_number) : __('Not Set', 'reserve-mate'); ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Adult Rate:', 'reserve-mate'); ?></strong>
                        <span class="property-data"><?php echo $property->adult_price ? esc_html(format_price($property->adult_price)).' '. $currency_symbol : ''; ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Children(Max):', 'reserve-mate'); ?></strong>
                        <span class="property-data"><?php echo $property->max_child_number ? esc_html($property->max_child_number) : __('Not Set', 'reserve-mate'); ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Child Rate:', 'reserve-mate'); ?></strong>
                        <span class="property-data"><?php echo $property->child_price ? esc_html(format_price($property->child_price)).' '. $currency_symbol : ''; ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Pets(Max):', 'reserve-mate'); ?></strong>
                        <span class="property-data"><?php echo $property->max_pet_number ? esc_html($property->max_pet_number) : __('Not Set', 'reserve-mate'); ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Pet Rate:', 'reserve-mate'); ?></strong>
                        <span class="property-data"><?php echo $property->pet_price ? esc_html(format_price($property->pet_price)).' '. $currency_symbol : ''; ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Seasonal Rules:', 'reserve-mate'); ?></strong>
                            <div class="seasonal-rules">
                                <?php if (!empty($property->seasonal_rules)): ?>
                                <ul>
                                    <?php
                                    $seasonal_rules = !empty($property->seasonal_rules) ? json_decode($property->seasonal_rules, true) : [];
                                    for ($month = 1; $month <= 12; $month++):
                                        $month_name = date('F', mktime(0, 0, 0, $month, 1));
                                        $min_value = $seasonal_rules[$month]['min'] ?? '';
                                        $max_value = $seasonal_rules[$month]['max'] ?? '';
                        
                                        if ($min_value !== '' || $max_value !== ''):
                                    ?>
                                        <li>
                                            <strong><?php echo esc_html($month_name); ?>:</strong>
                                            <?php _e('Min Stay:', 'reserve-mate'); ?> <?php echo esc_html($min_value); ?> <?php _e('nights', 'reserve-mate'); ?>, 
                                            <?php _e('Max Stay:', 'reserve-mate'); ?> <?php echo esc_html($max_value); ?> <?php _e('nights', 'reserve-mate'); ?>
                                        </li>
                                    <?php endif; endfor; ?>
                                </ul>
                                <?php else: ?>
                                    <span><?php _e('No seasonal rules set.', 'reserve-mate'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Disabled Dates:', 'reserve-mate'); ?></strong>
                            <div class="disabled-dates-rules">
                                <?php if (!empty($property->disabled_dates)): ?>
                                <ul>
                                    <?php
                                    $disabled_dates = json_decode($property->disabled_dates, true);
                                    foreach ($disabled_dates as $rule):
                                        if ($rule['type'] === 'specific'): ?>
                                            <li><?php _e('Specific date:', 'reserve-mate'); ?> <?php echo esc_html($rule['date']); ?>
                                                <?php if (!empty($rule['repeat_yearly'])) echo ' (' . __('repeats yearly', 'reserve-mate') . ')'; ?>
                                            </li>
                                        <?php elseif ($rule['type'] === 'range'): ?>
                                            <li><?php _e('Date range:', 'reserve-mate'); ?> <?php echo esc_html($rule['start_date']); ?> - <?php echo esc_html($rule['end_date']); ?>
                                                <?php if (!empty($rule['repeat_yearly'])) echo ' (' . __('repeats yearly', 'reserve-mate') . ')'; ?>
                                            </li>
                                        <?php elseif ($rule['type'] === 'weekly' && !empty($rule['days'])): ?>
                                            <li><?php _e('Weekly on:', 'reserve-mate'); ?> <?php echo esc_html(implode(', ', array_map(function($day) {
                                                return date_i18n('l', strtotime($day));
                                            }, $rule['days']))); ?></li>
                                        <?php endif;
                                    endforeach; ?>
                                </ul>
                                <?php else: ?>
                                    <span><?php _e('No disabled dates set.', 'reserve-mate'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}


function display_disabled_date_rule($index, $rule = []) {
    $rule = wp_parse_args($rule, [
        'type' => 'specific',
        'date' => '',
        'start_date' => '',
        'end_date' => '',
        'days' => [],
        'repeat_yearly' => false,
    ]);
    ?>
    <div class="disabled-date-rule" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;">
        <div style="margin-bottom: 10px;">
            <label>
                <select name="disabled_dates[<?php echo $index; ?>][type]" class="disabled-date-type">
                    <option value="specific" <?php selected($rule['type'], 'specific'); ?>><?php _e('Specific Date', 'reserve-mate'); ?></option>
                    <option value="range" <?php selected($rule['type'], 'range'); ?>><?php _e('Date Range', 'reserve-mate'); ?></option>
                    <option value="weekly" <?php selected($rule['type'], 'weekly'); ?>><?php _e('Weekly Pattern', 'reserve-mate'); ?></option>
                </select>
            </label>
            
            <button type="button" class="button remove-disabled-date-rule" style="float: right;"><?php _e('Remove', 'reserve-mate'); ?></button>
        </div>
        
        <div class="disabled-date-options">
            <!-- Specific Date -->
            <div class="disabled-date-specific" style="<?php echo $rule['type'] !== 'specific' ? 'display: none;' : ''; ?>">
                <label>
                    <?php _e('Date:', 'reserve-mate'); ?>
                    <input type="text" class="datepicker" name="disabled_dates[<?php echo $index; ?>][date]" value="<?php echo esc_attr($rule['date']); ?>">
                </label>
                <label style="margin-left: 10px;">
                    <input type="checkbox" name="disabled_dates[<?php echo $index; ?>][repeat_yearly]" value="1" <?php checked($rule['repeat_yearly'], 1); ?>>
                    <?php _e('Repeat yearly', 'reserve-mate'); ?>
                </label>
            </div>
            
            <!-- Date Range -->
            <div class="disabled-date-range" style="<?php echo $rule['type'] !== 'range' ? 'display: none;' : ''; ?>">
                <label>
                    <?php _e('From:', 'reserve-mate'); ?>
                    <input type="text" class="datepicker" name="disabled_dates[<?php echo $index; ?>][start_date]" value="<?php echo esc_attr($rule['start_date']); ?>">
                </label>
                <label style="margin-left: 10px;">
                    <?php _e('To:', 'reserve-mate'); ?>
                    <input type="text" class="datepicker" name="disabled_dates[<?php echo $index; ?>][end_date]" value="<?php echo esc_attr($rule['end_date']); ?>">
                </label>
                <label style="margin-left: 10px;">
                    <input type="checkbox" name="disabled_dates[<?php echo $index; ?>][repeat_yearly]" value="1" <?php checked($rule['repeat_yearly'], 1); ?>>
                    <?php _e('Repeat yearly', 'reserve-mate'); ?>
                </label>
            </div>
            
            <!-- Weekly Pattern -->
            <div class="disabled-date-weekly" style="<?php echo $rule['type'] !== 'weekly' ? 'display: none;' : ''; ?>">
                <?php 
                $days = [
                    'monday' => __('Monday', 'reserve-mate'),
                    'tuesday' => __('Tuesday', 'reserve-mate'),
                    'wednesday' => __('Wednesday', 'reserve-mate'),
                    'thursday' => __('Thursday', 'reserve-mate'),
                    'friday' => __('Friday', 'reserve-mate'),
                    'saturday' => __('Saturday', 'reserve-mate'),
                    'sunday' => __('Sunday', 'reserve-mate'),
                ];
                
                foreach ($days as $day => $label): ?>
                    <label style="margin-right: 10px;">
                        <input type="checkbox" name="disabled_dates[<?php echo $index; ?>][days][]" value="<?php echo $day; ?>" <?php checked(in_array($day, (array)$rule['days'])); ?>>
                        <?php echo $label; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

add_action('wp_ajax_get_disabled_date_rule', 'ajax_get_disabled_date_rule');
function ajax_get_disabled_date_rule() {
    // Verify nonce first
    check_ajax_referer('reserve_mate_nonce', 'security');
    
    $index = isset($_POST['index']) ? intval($_POST['index']) : 0;
    
    ob_start();
    display_disabled_date_rule($index);
    $html = ob_get_clean();
    
    // Send JSON response
    wp_send_json_success($html);
    wp_die();
}