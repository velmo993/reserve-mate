<?php
namespace ReserveMate\Admin\Views;

defined('ABSPATH') or die('No direct access!');

class ServiceViews {
    public static function render($data = []) {
        // extract($data);
        // include RM_PLUGIN_PATH . "includes/admin/templates/{$template}.php";
        ?>
        <div class="wrap rm-page">
            <h1>Manage Services</h1>
            
            <?php if ($data['editing_service']): ?>
                <h2>Edit Service</h2>
                <?php self::service_form($data['editing_service'], $data['currency_symbol']); ?>
            <?php else: ?>
                <button id="toggle-form-btn" class="button button-primary" style="margin-bottom: 20px;">
                    <?php _e('Add New Service', 'reserve-mate'); ?>
                </button>
                
                <div id="service-form" style="display: none;">
                    <h2>Add New Service</h2>
                    <?php self::service_form(); ?>
                </div>
            <?php endif; ?>
            
            <?php
            self::services_table($data['services'], $data['currency_symbol'], $data['per_page'], $data['current_page'], $data['total_items']); ?>
        </div>
    <?php }
    
    private static function service_form($service = null, $currency_symbol = '$') {
        ?>
        <form method="post" id="service-form">
        <?php wp_nonce_field('save_admin_service', 'admin_service_nonce'); ?>
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
        </table>
        <p>
            <input type="submit" name="save_admin_service" class="button button-primary" value="<?php echo $service ? __('Update Service', 'reserve-mate') : __('Save Service', 'reserve-mate'); ?>">
            <?php if ($service): ?>
                <a href="<?php echo admin_url('admin.php?reserve-mate-services'); ?>" class="button"><?php _e('Cancel', 'reserve-mate'); ?></a>
            <?php endif; ?>
        </p>
        </form>
        <?php
    }

    private static function services_table($services, $currency_symbol, $per_page, $current_page, $total_items) {
        ?>
        <h2><?php _e('Existing Services', 'reserve-mate'); ?></h2>
        <form method="post" id="services-bulk-form">
            <?php wp_nonce_field('bulk_service_action', 'bulk_nonce'); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'reserve-mate'); ?></label>
                    <select name="bulk_action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e('Bulk Actions', 'reserve-mate'); ?></option>
                        <option value="delete"><?php _e('Delete', 'reserve-mate'); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'reserve-mate'); ?>">
                </div>
                <br class="clear">
            </div>
            <table class="wp-list-table widefat striped data-display-table">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
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
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="service_ids[]" value="<?php echo esc_attr($service->id); ?>">
                            </th>
                            <td><?php echo esc_html($service->id); ?></td>
                            <td><?php echo esc_html($service->name); ?></td>
                            <td><?php echo esc_html($service->duration) . ' ' . __('minutes', 'reserve-mate'); ?></td>
                            <td><?php echo esc_html(format_price($service->price)) . ' ' . $currency_symbol; ?></td>
                            <td>
                                <button type="button" class="button toggle-details-service" data-service-id="<?php echo esc_attr($service->id); ?>">
                                    <span class="dashicons dashicons-arrow-down-alt"></span>
                                </button>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?reserve-mate-services&edit=' . $service->id); ?>" class="button edit-button"><span class="dashicons dashicons-edit"></span></a>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('delete_service', 'delete_nonce'); ?>
                                    <input type="hidden" name="delete" value="<?php echo esc_attr($service->id); ?>">
                                    <button type="submit" class="button trash-button" 
                                        onclick="return confirm('<?php echo esc_attr(__('Are you sure?', 'reserve-mate')); ?>');">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr class="table-details" id="details-<?php echo esc_attr($service->id); ?>" style="display: none;">
                            <td colspan="7">
                                <div class="table-details-container">
                                    <div class="table-details-flex">
                                        <div class="detail-item">
                                            <strong><?php _e('Description:', 'reserve-mate'); ?></strong>
                                            <span class="service-data"><?php echo esc_html($service->description); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'total' => ceil($total_items / $per_page),
                        'current' => $current_page,
                        'show_all' => false,
                        'prev_next' => true,
                        'prev_text' => __('&laquo; Previous'),
                        'next_text' => __('Next &raquo;'),
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        </form>
        <?php
    }

}