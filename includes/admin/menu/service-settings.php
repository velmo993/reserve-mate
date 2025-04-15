<?php

// Handle form submissions for services
function handle_service_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
        if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'save_service')) {
            wp_die('Security check failed');
        }

        $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;

        $service_data = [
            'name'              => sanitize_text_field($_POST['name']),
            'description'       => sanitize_textarea_field($_POST['description']),
            'duration'          => isset($_POST['duration']) ? intval($_POST['duration']) : 0,
            'price'             => isset($_POST['price']) ? floatval($_POST['price']) : 0,
            'max_capacity'      => isset($_POST['max_capacity']) ? intval($_POST['max_capacity']) : 0,
            'allow_multiple'    => isset($_POST['allow_multiple']) ? 1 : 0,
            'time_slots'        => isset($_POST['time_slots']) ? sanitize_text_field($_POST['time_slots']) : '',
            'additional_notes'  => isset($_POST['additional_notes']) ? sanitize_textarea_field($_POST['additional_notes']) : '',
        ];

        save_service($service_data, $editing_index);
        wp_redirect(admin_url('admin.php?page=manage-services'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Service saved successfully.', 'reserve-mate') . '</p></div>';
    }

    if (isset($_GET['delete'])) {
        if (!isset($_GET['delete_nonce']) || !wp_verify_nonce($_GET['delete_nonce'], 'delete_service')) {
            wp_die('Security check failed');
        }

        delete_service(intval($_GET['delete']));
        wp_redirect(admin_url('admin.php?page=manage-services'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Service deleted successfully.', 'reserve-mate') . '</p></div>';
    }
}

// Display the services management page
function manage_services_page() {
    handle_service_form_submission();

    $currency_symbol = get_currency();
    $services = get_services();
    $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $editing_service = $editing_index ? get_service($editing_index) : null;

    ?>
    <div class="wrap">
        <h1>Manage Services</h1>
        
        <?php if ($editing_service): ?>
            <h2>Edit Service</h2>
            <?php display_service_form($editing_service); ?>
        <?php else: ?>
            <button id="toggle-form-btn" class="button button-primary" style="margin-bottom: 20px;">
                <?php _e('Add New Service', 'reserve-mate'); ?>
            </button>
            
            <div id="service-form" style="display: none;">
                <h2>Add New Service</h2>
                <?php display_service_form(); ?>
            </div>
        <?php endif; ?>
        
        <?php display_existing_services_table($services); ?>
    </div>
    <?php
}

function display_service_form($service = null) {
    $currency_symbol = get_currency();
    ?>
    <form method="post">
        <?php wp_nonce_field('save_service', 'service_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="name"><?php _e('Service Name', 'reserve-mate'); ?></label><i class="star-required">*</i></th>
                <td>
                    <input type="text" name="name" value="<?php echo esc_attr($service->name ?? ''); ?>" required>
                    <p class="description"><?php _e('Enter the name of the service', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="description"><?php _e('Description', 'reserve-mate'); ?></label></th>
                <td>
                    <textarea name="description" rows="5"><?php echo esc_textarea($service->description ?? ''); ?></textarea>
                    <p class="description"><?php _e('Provide a brief description of the service.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="duration"><?php _e('Duration (Minutes)', 'reserve-mate'); ?></label><i class="star-required">*</i></th>
                <td>
                    <input type="number" name="duration" min="0" value="<?php echo esc_attr($service->duration ?? ''); ?>" required>
                    <p class="description"><?php _e('Enter the duration of the service in minutes.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="price"><?php _e('Price', 'reserve-mate'); ?> (<?php echo $currency_symbol; ?>)</label><i class="star-required">*</i></th>
                <td>
                    <input type="number" name="price" step="0.01" value="<?php echo esc_attr($service->price ?? ''); ?>" required>
                    <p class="description"><?php _e('Enter the price of the service.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="max_capacity"><?php _e('Max Capacity', 'reserve-mate'); ?></label></th>
                <td>
                    <input type="number" name="max_capacity" min="0" value="<?php echo esc_attr($service->max_capacity ?? ''); ?>">
                    <p class="description"><?php _e('Maximum number of participants or customers allowed for this service.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="allow_multiple"><?php _e('Allow Multiple Bookings', 'reserve-mate'); ?></label></th>
                <td>
                    <input type="checkbox" name="allow_multiple" value="1" <?php checked($service->allow_multiple ?? 0, 1); ?>>
                    <p class="description"><?php _e('Allow customers to book multiple slots for this service.', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="time_slots"><?php _e('Time Slots', 'reserve-mate'); ?></label></th>
                <td>
                    <input type="text" name="time_slots" value="<?php echo esc_attr($service->time_slots ?? ''); ?>">
                    <p class="description"><?php _e('Enter available time slots (e.g., 09:00-10:00, 10:30-11:30).', 'reserve-mate'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="additional_notes"><?php _e('Additional Notes', 'reserve-mate'); ?></label></th>
                <td>
                    <textarea name="additional_notes" rows="5"><?php echo esc_textarea($service->additional_notes ?? ''); ?></textarea>
                    <p class="description"><?php _e('Add any additional notes or instructions for this service.', 'reserve-mate'); ?></p>
                </td>
            </tr>
        </table>
        <p>
            <input type="submit" name="save_service" class="button button-primary" value="<?php echo $service ? __('Update Service', 'reserve-mate') : __('Save Service', 'reserve-mate'); ?>">
            <?php if ($service): ?>
                <a href="<?php echo admin_url('admin.php?page=manage-services'); ?>" class="button"><?php _e('Cancel', 'reserve-mate'); ?></a>
            <?php endif; ?>
        </p>
    </form>
    <?php
}

function display_existing_services_table($services) {
    $currency_symbol = get_currency();
    ?>
    <h2><?php _e('Existing Services', 'reserve-mate'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'reserve-mate'); ?></th>
                <th><?php _e('Service', 'reserve-mate'); ?></th>
                <th><?php _e('Duration', 'reserve-mate'); ?></th>
                <th><?php _e('Price', 'reserve-mate'); ?></th>
                <th><?php _e('Details', 'reserve-mate'); ?></th>
                <th><?php _e('Actions', 'reserve-mate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($services): ?>
            <?php foreach ($services as $index => $service): ?>
                <tr class="service-summary">
                    <td><?php echo esc_html($service->id); ?></td>
                    <td><?php echo esc_html($service->name); ?></td>
                    <td><?php echo esc_html($service->duration) . ' ' . __('minutes', 'reserve-mate'); ?></td>
                    <td><?php echo esc_html(format_price($service->price)) . ' ' . $currency_symbol; ?></td>
                    <td>
                        <button class="toggle-details-service" data-service-id="<?php echo esc_attr($service->id); ?>"><i>▼</i></button>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=manage-services&edit=' . $service->id); ?>" class="button">✏️</a>
                        <a href="<?php echo admin_url('admin.php?page=manage-services&delete=' . $service->id . '&delete_nonce=' . wp_create_nonce('delete_service')); ?>" class="button button-danger" onclick="return confirm('<?php echo esc_attr(__('Are you sure you want to delete this service?', 'reserve-mate')); ?>');">❌</a>
                    </td>
                </tr>
                <tr class="table-details" id="details-<?php echo esc_attr($service->id); ?>" style="display: none;">
                    <td colspan="6">
                        <div class="table-details-flex"><strong><?php _e('Description:', 'reserve-mate'); ?></strong>
                            <span class="service-data"><?php echo esc_html($service->description); ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Max Capacity:', 'reserve-mate'); ?></strong>
                            <span class="service-data"><?php echo esc_html($service->max_capacity); ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Allow Multiple Bookings:', 'reserve-mate'); ?></strong>
                            <span class="service-data"><?php echo $service->allow_multiple ? __('Yes', 'reserve-mate') : __('No', 'reserve-mate'); ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Time Slots:', 'reserve-mate'); ?></strong>
                            <span class="service-data"><?php echo esc_html($service->time_slots); ?></span>
                        </div>
                        <div class="table-details-flex"><strong><?php _e('Additional Notes:', 'reserve-mate'); ?></strong>
                            <span class="service-data"><?php echo esc_html($service->additional_notes); ?></span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}