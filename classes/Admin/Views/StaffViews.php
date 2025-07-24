<?php
namespace ReserveMate\Admin\Views;
use ReserveMate\Admin\Helpers\Staff;
use DateTime;

defined('ABSPATH') or die('No direct access!');

class StaffViews {
    public static function render($data = []) {
        ?>
        <div class="wrap rm-page">
            <h1>Staff Members</h1>
            
            <?php 
            settings_errors('reservemate_staff'); 
            ?>
            
            <?php if ($data['editing_staff']): ?>
                <h2>Edit Staff Member</h2>
                <?php self::staff_form($data['staff_id'], $data['editing_staff'], $data['all_services']); ?>
            <?php else: ?>
                <button id="toggle-form-btn" class="button button-primary" style="margin-bottom: 20px;">
                    Add Member
                </button>
                
                <div id="staff-form" style="display: none;">
                    <h2>Add Member</h2>
                    <?php self::staff_form(null, null, $data['all_services']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$data['editing_staff']): ?>
                <table class="wp-list-table widefat striped data-display-table">
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
                        <?php if (empty($data['staff_members'])): ?>
                            <tr>
                                <td colspan="6">No staff members found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['staff_members'] as $staff): ?>
                                <tr>
                                    <td><?php echo esc_html($staff['name']); ?></td>
                                    <td><?php echo esc_html($staff['email']); ?></td>
                                    <td><?php echo esc_html($staff['phone']); ?></td>
                                    <td>
                                        <?php 
                                        // CHANGE this part
                                        $services = Staff::get_staff_services($staff['id']);
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
                                        <a href="<?php echo admin_url('admin.php?page=reserve-mate-staff&edit=' . $staff['id']); ?>" class="button edit-button">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('delete_staff', 'delete_nonce'); ?>
                                            <input type="hidden" name="delete" value="<?php echo esc_attr($staff['id']); ?>">
                                            <button type="submit" class="button trash-button" 
                                                onclick="return confirm('<?php echo esc_attr(__('Are you sure?', 'reserve-mate')); ?>');">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php }

    public static function staff_form($staff_id = null, $staff = null, $all_services = null) {
        $services_assigned = [];
        
        if ($staff_id) {
            $services_assigned = Staff::get_staff_services($staff_id);
            $services_assigned = array_column($services_assigned, 'id');
        }
        
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
                        <textarea name="bio" id="bio" class="large-text" maxlength="600" rows="5"><?php echo esc_textarea($staff['bio'] ?? ''); ?></textarea>
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
                            <p>No services available. <a href="<?php echo admin_url('admin.php?page=reserve-mate-services'); ?>">Add services first</a>.</p>
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
                                $has_valid_hours = false;
                                
                                if (!empty($periods)) {
                                    foreach ($periods as $period) {
                                        if (!empty($period['start']) && !empty($period['end']) && 
                                            ($period['start'] !== '00:00' || $period['end'] !== '00:00')) {
                                            $has_valid_hours = true;
                                            break;
                                        }
                                    }
                                }
                
                                ?>
                                <div class="day-schedule">
                                    <h4><?php echo $day_name; ?></h4>
                                    <label>
                                        <input type="checkbox" class="day-enabled" data-day="<?php echo $day_index; ?>" 
                                            <?php checked($has_valid_hours); ?>>
                                        Working this day
                                    </label>
                                    
                                    <div class="time-periods" data-day="<?php echo $day_index; ?>" <?php echo !$has_valid_hours ? 'style="display:none;"' : ''; ?>>
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
                    <a href="<?php echo admin_url('admin.php?page=reserve-mate-staff'); ?>" class="button">Cancel</a>
                <?php endif; ?>
            </p>
        </form>
        <?php
    }
    
}