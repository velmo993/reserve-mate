<?php
defined('ABSPATH') or die('No direct access!');

function handle_staff_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_staff'])) {
        if (!isset($_POST['staff_nonce']) || !wp_verify_nonce($_POST['staff_nonce'], 'save_staff_nonce')) {
            wp_die('Security check failed');
        }
        
        $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
        
        $staff_data = [
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'bio' => sanitize_textarea_field($_POST['bio']),
            'status' => sanitize_text_field($_POST['status']),
            'profile_image' => $_POST['profile_image'],
        ];
        
        $working_hours = [];
        if (isset($_POST['working_hours']) && is_array($_POST['working_hours'])) {
            foreach ($_POST['working_hours'] as $day => $periods) {
                $day_enabled = false;
                
                foreach ($periods as $period) {
                    if (!empty($period['start']) && !empty($period['end'])) {
                        $day_enabled = true;
                        $working_hours[$day][] = [
                            'start' => sanitize_text_field($period['start']),
                            'end' => sanitize_text_field($period['end'])
                        ];
                    }
                }
                
                if (!$day_enabled) {
                    unset($working_hours[$day]);
                }
            }
        }
        
        $staff_data['working_hours'] = $working_hours;
        $result = save_staff_member($staff_data, $staff_id);
        
        if ($result) {
            $staff_id = $staff_id ?: $result;
            
            global $wpdb;
            $wpdb->delete(
                $wpdb->prefix . 'reservemate_staff_services',
                ['staff_id' => $staff_id]
            );
            
            if (isset($_POST['services']) && is_array($_POST['services'])) {
                foreach ($_POST['services'] as $service_id) {
                    assign_service_to_staff($staff_id, intval($service_id));
                }
            }
            
            add_settings_error('reservemate_staff', 'save_success', 'Staff member saved successfully.', 'success');
        } else {
            add_settings_error('reservemate_staff', 'save_error', 'Error saving staff member.', 'error');
        }
    }
    
    
    
    if (isset($_GET['delete'])) {
        if (!isset($_GET['delete_nonce']) || !wp_verify_nonce($_GET['delete_nonce'], 'delete_staff')) {
            wp_die('Security check failed');
        }
        
        $staff_id = intval($_GET['delete']);
        delete_staff_member($staff_id);
        wp_redirect(admin_url('admin.php?page=manage-staff'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Staff member deleted successfully.', 'reserve-mate') . '</p></div>';
    }
    
}

function render_staff_management_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    handle_staff_form_submission();
    
    $editing_staff_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $editing_staff = $editing_staff_id ? get_staff_member($editing_staff_id) : null;
    
    $staff_members = get_staff_members();
    
    ?>
    <div class="wrap">
        <h1>Staff Members</h1>
        
        <?php 
        settings_errors('reservemate_staff'); 
        ?>
        
        <?php if ($editing_staff): ?>
            <h2>Edit Staff Member</h2>
            <?php render_staff_form($editing_staff); ?>
        <?php else: ?>
            <button id="toggle-form-btn" class="button button-primary" style="margin-bottom: 20px;">
                Add Member
            </button>
            
            <div id="staff-form" style="display: none;">
                <h2>Add Member</h2>
                <?php render_staff_form(); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$editing_staff): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Services</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staff_members)): ?>
                        <tr>
                            <td colspan="6">No staff members found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($staff_members as $staff): ?>
                            <tr>
                                <td><?php echo esc_html($staff['name']); ?></td>
                                <td><?php echo esc_html($staff['email']); ?></td>
                                <td><?php echo esc_html($staff['phone']); ?></td>
                                <td>
                                    <?php 
                                    $services = get_staff_services($staff['id']);
                                    if (!empty($services)) {
                                        $service_names = array_column($services, 'name');
                                        echo esc_html(implode(', ', $service_names));
                                    } else {
                                        echo 'No services assigned';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-<?php echo esc_attr($staff['status']); ?>">
                                        <?php echo esc_html(ucfirst($staff['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=manage-staff&edit=' . $staff['id']); ?>" class="button button-small">✏️</a>
                                    <a href="<?php echo admin_url('admin.php?page=manage-staff&delete=' . $staff['id'] . '&delete_nonce=' . wp_create_nonce('delete_staff')); ?>" class="button button-small" 
                                    onclick="return confirm('<?php echo esc_attr(__('Are you sure you want to delete this staff member?', 'reserve-mate')); ?>');">❌</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <style>
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; }
    </style>
    
    <?php
}

function render_staff_form($staff = null) {
    $staff_id = isset($staff['id']) ? intval($staff['id']) : 0;
    
    $services_assigned = [];
    $working_hours = [];
    if (!empty($staff['working_hours'])) {
        $db_hours = $staff['working_hours'];
        
        // Define reverse mapping (DB 1-7 to form 0-6)
        $day_mapping = [
            7 => 0, // Sunday
            1 => 1, // Monday
            2 => 2, // Tuesday
            3 => 3, // Wednesday
            4 => 4, // Thursday
            5 => 5, // Friday
            6 => 6  // Saturday
        ];
        
        foreach ($db_hours as $db_day => $periods) {
            if (isset($day_mapping[$db_day])) {
                $form_day = $day_mapping[$db_day];
                $working_hours[$form_day] = $periods;
            }
        }
    }
    
    // Define days for the form (0=Sunday to 6=Saturday)
    $days = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday', 
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday'
    ];
    
    if ($staff_id) {
        $services_assigned = get_staff_services($staff_id);
        $services_assigned = array_column($services_assigned, 'id');
    }
    
    $all_services = get_services();
    ?>
    <form method="post">
        <input type="hidden" name="staff_id" value="<?php echo intval($staff_id); ?>">
        <?php wp_nonce_field('save_staff_nonce', 'staff_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="name">Name</label></th>
                <td>
                    <input type="text" name="name" id="name" class="regular-text" value="<?php echo esc_attr($staff['name'] ?? ''); ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="email">Email</label></th>
                <td>
                    <input type="email" name="email" id="email" class="regular-text" value="<?php echo esc_attr($staff['email'] ?? ''); ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="phone">Phone</label></th>
                <td>
                    <input type="text" name="phone" id="phone" class="regular-text" value="<?php echo esc_attr($staff['phone'] ?? ''); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="bio">Bio</label></th>
                <td>
                    <textarea name="bio" id="bio" class="large-text" rows="5"><?php echo esc_textarea($staff['bio'] ?? ''); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="status">Status</label></th>
                <td>
                    <select name="status" id="status">
                        <option value="active" <?php selected(($staff['status'] ?? ''), 'active'); ?>>Active</option>
                        <option value="inactive" <?php selected(($staff['status'] ?? ''), 'inactive'); ?>>Inactive</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label>Services</label></th>
                <td>
                    <?php if (empty($all_services)): ?>
                        <p>No services available. <a href="<?php echo admin_url('admin.php?page=manage-services'); ?>">Add services first</a>.</p>
                    <?php else: ?>
                        <div class="services-list">
                            <?php foreach ($all_services as $service): ?>
                                <label>
                                    <input type="checkbox" name="services[]" value="<?php echo intval($service->id); ?> "
                                        <?php checked(in_array($service->id, $services_assigned)); ?>>
                                    <?php echo esc_html($service->name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="profile_image">Profile Image</label></th>
                <td>
                    <?php 
                    $image_url = $staff['profile_image'] ?? '';
                    $image_id = $image_url ? attachment_url_to_postid($image_url) : 0;
                    ?>
                    
                    <div class="staff-image-selector">
                        <div class="image-preview" style="margin-bottom: 10px;">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px;">
                            <?php endif; ?>
                        </div>
                        
                        <input type="hidden" name="profile_image" id="profile_image" value="<?php echo esc_attr($image_id); ?>">
                        
                        <button type="button" class="button select-image-btn">
                            <?php _e('Select Image', 'reserve-mate'); ?>
                        </button>
                        
                        <?php if ($image_url): ?>
                            <button type="button" class="button remove-image-btn">
                                <?php _e('Remove Image', 'reserve-mate'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label>Working Hours</label></th>
                <td>
                    <div class="working-hours-container">
                        <?php
                        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        $working_hours = $staff['working_hours'] ?? [];
                        
                        foreach ($days as $day_index => $day_name) {
                            $periods = isset($working_hours[$day_index]) ? $working_hours[$day_index] : [];
                            ?>
                            <div class="day-schedule">
                                <h4><?php echo $day_name; ?></h4>
                                <label>
                                    <input type="checkbox" class="day-enabled" data-day="<?php echo $day_index; ?>" 
                                        <?php checked(!empty($periods)); ?>>
                                    Working this day
                                </label>
                                
                                <div class="time-periods" data-day="<?php echo $day_index; ?>" <?php echo empty($periods) ? 'style="display:none;"' : ''; ?>>
                                    <div class="time-period-template">
                                        <select name="working_hours[<?php echo $day_index; ?>][0][start]" class="time-select">
                                            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                                <?php for ($min = 0; $min < 60; $min += 30): ?>
                                                    <?php 
                                                    $time = sprintf('%02d:%02d', $hour, $min);
                                                    $selected = isset($periods[0]['start']) && $periods[0]['start'] == $time;
                                                    ?>
                                                    <option value="<?php echo $time; ?>" <?php selected($selected); ?>><?php echo $time; ?></option>
                                                <?php endfor; ?>
                                            <?php endfor; ?>
                                        </select>
                                        to
                                        <select name="working_hours[<?php echo $day_index; ?>][0][end]" class="time-select">
                                            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                                <?php for ($min = 0; $min < 60; $min += 30): ?>
                                                    <?php 
                                                    $time = sprintf('%02d:%02d', $hour, $min);
                                                    $selected = isset($periods[0]['end']) && $periods[0]['end'] == $time;
                                                    ?>
                                                    <option value="<?php echo $time; ?>" <?php selected($selected); ?>><?php echo $time; ?></option>
                                                <?php endfor; ?>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="button" class="add-period button-secondary">Add Period</button>
                                    </div>
                                    
                                    <?php 
                                    if (!empty($periods) && count($periods) > 1) {
                                        for ($i = 1; $i < count($periods); $i++) {
                                            ?>
                                            <div class="additional-period">
                                                <select name="working_hours[<?php echo $day_index; ?>][<?php echo $i; ?>][start]" class="time-select">
                                                    <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                                        <?php for ($min = 0; $min < 60; $min += 30): ?>
                                                            <?php 
                                                            $time = sprintf('%02d:%02d', $hour, $min);
                                                            $selected = $periods[$i]['start'] == $time;
                                                            ?>
                                                            <option value="<?php echo $time; ?>" <?php selected($selected); ?>><?php echo $time; ?></option>
                                                        <?php endfor; ?>
                                                    <?php endfor; ?>
                                                </select>
                                                to
                                                <select name="working_hours[<?php echo $day_index; ?>][<?php echo $i; ?>][end]" class="time-select">
                                                    <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                                        <?php for ($min = 0; $min < 60; $min += 30): ?>
                                                            <?php 
                                                            $time = sprintf('%02d:%02d', $hour, $min);
                                                            $selected = $periods[$i]['end'] == $time;
                                                            ?>
                                                            <option value="<?php echo $time; ?>" <?php selected($selected); ?>><?php echo $time; ?></option>
                                                        <?php endfor; ?>
                                                    <?php endfor; ?>
                                                </select>
                                                <button type="button" class="remove-period button-secondary">Remove</button>
                                            </div>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="save_staff" class="button button-primary" value="Save Member">
            <?php if ($staff_id): ?>
                <a href="<?php echo admin_url('admin.php?page=manage-staff'); ?>" class="button">Cancel</a>
            <?php endif; ?>
        </p>
    </form>
    <?php
}