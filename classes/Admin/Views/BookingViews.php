<?php
namespace ReserveMate\Admin\Views;
use ReserveMate\Admin\Helpers\Booking;
use DateTime;

defined('ABSPATH') or die('No direct access!');

class BookingViews {
    public static function render($data = []) {
        ?>
        <div class="wrap rm-page">
            <h1>Manage Bookings</h1>
            
            <?php if ($data['editing_booking']): ?>
                <h2>Edit Booking</h2>
                <?php self::booking_form($data['services'], $data['editing_booking'], $data['staff_members']); ?>
            <?php else: ?>
                <button id="toggle-form-btn" class="button button-primary" style="margin-bottom: 20px;">
                    <?php _e('Add New Booking', 'reserve-mate'); ?>
                </button>
                
                <div id="booking-form" style="display: none;">
                    <h2>Add New Booking</h2>
                    <?php self::booking_form($data['services'], null,  $data['staff_members']); ?>
                </div>
            <?php endif; ?>
            
            <?php
            self::bookings_table($data['bookings'], $data['per_page'], $data['current_page'], $data['total_items'], $data['approval_enabled'], $data);
            ?>
        </div>
    <?php }
    
    public static function booking_form($services, $booking = null, $staff_members = null) {
        $enabled_payment_methods = self::get_enabled_payment_methods();
        $selected_payment_method = $booking->payment_method ?? '';
        $booking_services = $booking && $booking->id ? Booking::get_booking_services($booking->id) : [];
        ?>
        <form method="post" id="booking-form">
        <?php wp_nonce_field('save_admin_booking', 'admin_booking_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="name">Name</label></th>
                    <td><input type="text" name="name" id="name" value="<?php echo esc_attr($booking->name ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="email">Email</label></th>
                    <td><input type="email" name="email" id="email" value="<?php echo esc_attr($booking->email ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="phone">Phone</label></th>
                    <td><input type="text" name="phone" id="phone" value="<?php echo esc_attr($booking->phone ?? ''); ?>"></td>
                </tr>
                <tr>
                    <th><label for="start_datetime">Start Date & Time</label></th>
                    <td><input type="text" name="start_datetime" id="start_datetime" value="<?php echo esc_attr($booking->start_datetime ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="end_datetime">End Date & Time</label></th>
                    <td><input type="text" name="end_datetime" id="end_datetime" value="<?php echo esc_attr($booking->end_datetime ?? ''); ?>" required></td>
                </tr>
                <tr>
                    <th><label>Services</label></th>
                    <td>
                        <select id="services" name="services[]" multiple="multiple" class="services" aria-hidden="false">
                            <option></option>
                            <?php if($services) : ?>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service->id; ?>" data-price="<?php echo $service->price; ?>"><?php echo $service->name; ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
                <input type="hidden" id="services-field" name="services-field">
                <?php
                    if(count($staff_members) > 0) :
                ?>
                <tr>
                    <th><label for="staff_id">Staff Member</label></th>
                    <td>
                        <select name="staff_id" id="staff_id" required>
                            <option value="">-- Select Staff --</option>
                            <?php 
                            foreach ($staff_members as $staff): 
                                $selected = ($booking->staff_id ?? 0) == $staff['id'] ? 'selected' : '';
                            ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo esc_html($staff['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><label for="total_cost">Total Cost</label></th>
                    <td><input type="number" step="0.01" name="total_cost" id="total_cost" value="<?php echo esc_attr($booking->total_cost ?? '0'); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="paid_amount">Paid Amount</label></th>
                    <td><input type="number" step="0.01" name="paid_amount" id="paid_amount" value="<?php echo esc_attr($booking->paid_amount ?? '0'); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="payment_method">Payment Method</label></th>
                    <td>
                        <select name="payment_method" id="payment_method">
                            <?php foreach ($enabled_payment_methods as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($selected_payment_method, $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" id="save-booking-btn" name="save_admin_booking" class="button button-primary" value="<?php echo $booking ? __('Update Booking', 'reserve-mate') : __('Save Booking', 'reserve-mate'); ?>">
                <?php if ($booking): ?>
                    <a href="<?php echo admin_url('admin.php?page=reserve-mate-bookings'); ?>" class="button"><?php _e('Cancel', 'reserve-mate'); ?></a>
                <?php endif; ?>
            </p>
        </form>
        <?php
    }

    public static function bookings_table($bookings, $per_page, $current_page, $total_items, $approval_enabled, $data = []) {
        $td_columns = 8;
        if ($approval_enabled) {
            $td_columns++;
        }
        
        ?>
        <h2><?php _e('Existing Bookings', 'reserve-mate'); ?></h2>
        <form method="post" id="bookings-bulk-form">
            <?php wp_nonce_field('bulk_booking_action', 'bulk_nonce'); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'reserve-mate'); ?></label>
                    <select name="bulk_action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e('Bulk Actions', 'reserve-mate'); ?></option>
                        <option value="delete"><?php _e('Delete', 'reserve-mate'); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'reserve-mate'); ?>">
                </div>
                
                <div class="alignright actions">
                    <input type="hidden" name="reserve-mate-bookings" value="1">
                    <?php if (!empty($_GET['orderby'])): ?>
                        <input type="hidden" name="orderby" value="<?php echo esc_attr($_GET['orderby']); ?>">
                    <?php endif; ?>
                    <?php if (!empty($_GET['order'])): ?>
                        <input type="hidden" name="order" value="<?php echo esc_attr($_GET['order']); ?>">
                    <?php endif; ?>
                    <?php if (!empty($_POST['s']) || !empty($_GET['s'])): ?>
                        <a href="<?php echo remove_query_arg('s', admin_url('admin.php?page=reserve-mate-bookings')); ?>" class="button"><?php _e('Clear Search', 'reserve-mate'); ?></a>
                    <?php endif; ?>
                    <input type="search" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" placeholder="<?php esc_attr_e('Search bookings...', 'reserve-mate'); ?>" style="width: 200px;">
                    <button type="submit" name="action" value="search" class="button"><?php _e('Search', 'reserve-mate'); ?></button>
                </div>
                <br class="clear">
            </div>
            <table class="wp-list-table widefat striped data-display-table">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th class="manage-column sortable">
                            <a href="<?php echo esc_url(self::get_sort_link('id', $data['orderby'] ?? '', $data['order'] ?? '')); ?>">
                                <?php _e('ID', 'reserve-mate'); ?>
                                <?php echo self::get_sort_icon('id', $data['orderby'] ?? '', $data['order'] ?? ''); ?>
                            </a>
                        </th>
                        <th><?php _e('Customer', 'reserve-mate'); ?></th>
                        <th class="manage-column sortable">
                            <a href="<?php echo esc_url(self::get_sort_link('start_datetime', $data['orderby'] ?? '', $data['order'] ?? '')); ?>">
                                <?php _e('Date & Time', 'reserve-mate'); ?>
                                <?php echo self::get_sort_icon('start_datetime', $data['orderby'] ?? '', $data['order'] ?? ''); ?>
                            </a>
                        </th>
                        <th class="manage-column sortable">
                            <a href="<?php echo esc_url(self::get_sort_link('total_cost', $data['orderby'] ?? '', $data['order'] ?? '')); ?>">
                                <?php _e('Total Cost', 'reserve-mate'); ?>
                                <?php echo self::get_sort_icon('total_cost', $data['orderby'] ?? '', $data['order'] ?? ''); ?>
                            </a>
                        </th>
                        <th class="manage-column sortable">
                            <a href="<?php echo esc_url(self::get_sort_link('paid_amount', $data['orderby'] ?? '', $data['order'] ?? '')); ?>">
                                <?php _e('Paid', 'reserve-mate'); ?>
                                <?php echo self::get_sort_icon('paid_amount', $data['orderby'] ?? '', $data['order'] ?? ''); ?>
                            </a>
                        </th>
                        <?php if($approval_enabled) : ?>
                            <th class="manage-column sortable">
                                <a href="<?php echo esc_url(self::get_sort_link('status', $data['orderby'] ?? '', $data['order'] ?? '')); ?>">
                                    <?php _e('Status', 'reserve-mate'); ?>
                                    <?php echo self::get_sort_icon('status', $data['orderby'] ?? '', $data['order'] ?? ''); ?>
                                </a>
                            </th>
                        <?php endif; ?>
                        <th><?php _e('Details', 'reserve-mate'); ?></th>
                        <th><?php _e('Actions', 'reserve-mate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bookings) : ?>
                        <?php foreach ($bookings as $booking) : ?>
                            <tr class="booking-summary">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="booking_ids[]" value="<?php echo esc_attr($booking->id); ?>">
                                </th>
                                <td class="booking-id-col"><?php echo esc_html($booking->id); ?></td>
                                <td class="customer-col">
                                    <span><i class="dashicons dashicons-admin-users"></i> <?php echo esc_html($booking->name ?? __('N/A', 'reserve-mate')); ?></span>
                                    <?php if (!empty($booking->email)): ?>
                                        <br><span><i class="dashicons dashicons-email"></i> <?php echo esc_html($booking->email); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($booking->phone)): ?>
                                        <br><span><i class="dashicons dashicons-phone"></i> <?php echo esc_html($booking->phone); ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php 
                                    $start = new DateTime($booking->start_datetime);
                                    $end = new DateTime($booking->end_datetime);
                                ?>
                                <td class="time-col"><?php echo esc_html($start->format('Y-m-d H:i') . ' - ' . $end->format('H:i')); ?></td>
                                <td class="cost-col"><?php echo $booking->total_cost ? esc_html(format_price($booking->total_cost) . ' ' . get_currency()) : '0'; ?></td>
                                <td class="paid-col"><?php echo $booking->paid_amount ? esc_html(format_price($booking->paid_amount) . ' ' . get_currency()) : '0'; ?></td>
                                <?php if($approval_enabled) : ?>
                                    <td class="approval-col">
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('toggle_booking_status', 'status_nonce'); ?>
                                            <input type="hidden" name="booking_id" value="<?php echo esc_attr($booking->id); ?>">
                                            <button type="submit" name="toggle_status" class="button status-toggle-button">
                                                    <?php if($booking->status === "confirmed") : ?>
                                                        <span class="dashicons dashicons-saved booking-status <?php echo esc_attr($booking->status); ?>"></span>
                                                    <?php else : ?>
                                                        <span class="dashicons dashicons-update booking-status <?php echo esc_attr($booking->status); ?>"></span>
                                                    <?php endif; ?>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <button type="button" class="button toggle-details-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                                        <span class="dashicons dashicons-arrow-down-alt"></span>
                                    </button>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=reserve-mate-bookings&edit=' . $booking->id . '&admin_booking_nonce=' . wp_create_nonce('admin_booking_action')); ?>" class="button edit-button">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('delete_booking', 'delete_nonce'); ?>
                                        <input type="hidden" name="delete" value="<?php echo esc_attr($booking->id); ?>">
                                        <button type="submit" class="button trash-button" 
                                            onclick="return confirm('<?php echo esc_attr(__('Are you sure?', 'reserve-mate')); ?>');">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="table-details" id="details-<?php echo esc_attr($booking->id); ?>" style="display: none;">
                                <td colspan="<?php echo $td_columns; ?>">
                                    <div class="table-details-container">
                                        <div class="table-details-flex">
                                            <div class="detail-item">
                                                <strong><?php _e('Services:', 'reserve-mate'); ?></strong>
                                                <span class="booking-data">
                                                    <?php if (!empty($booking->services)) : ?>
                                                        <ul>
                                                            <?php foreach ($booking->services as $service) : ?>
                                                                <li>
                                                                    <?php echo esc_html($service->service_name); ?> 
                                                                    (Qty: <?php echo esc_html($service->quantity); ?>, 
                                                                    Price: <?php echo esc_html(format_price($service->price) . ' ' . get_currency()); ?>)
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else : ?>
                                                        <?php _e('No services', 'reserve-mate'); ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if (!empty($booking->staff_name)) : ?>
                                            <div class="table-details-flex">
                                                <div class="detail-item">
                                                    <strong><?php _e('Staff:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo $booking->staff_name ? esc_html($booking->staff_name) : '--' ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="table-details-flex">
                                            <div class="detail-item">
                                                <strong><?php _e('Payment Method:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($booking->payment_method); ?></span>
                                            </div>
                                        </div>
                                        <div class="table-details-flex d-mobile">
                                            <div class="detail-item">
                                                <strong><?php _e('Total Cost:', 'reserve-mate'); ?></strong>
                                                <span class="booking-data">
                                                    <?php echo $booking->total_cost ? esc_html(format_price($booking->total_cost) . ' ' . get_currency()) : '0'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="table-details-flex d-mobile">
                                            <div class="detail-item">
                                                <strong><?php _e('Paid:', 'reserve-mate'); ?></strong>
                                                <span class="booking-data">
                                                    <?php echo $booking->paid_amount ? esc_html(format_price($booking->paid_amount) . ' ' . get_currency()) : '0'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="<?php echo $td_columns; ?>"><?php _e('No bookings found.', 'reserve-mate'); ?></td></tr>
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
    
    private static function get_sort_link($column, $current_orderby, $current_order) {
        $new_order = ($current_orderby === $column && $current_order === 'asc') ? 'desc' : 'asc';
        $url = add_query_arg([
            'orderby' => $column,
            'order' => $new_order
        ]);
        return $url;
    }
    
    private static function get_sort_icon($column, $current_orderby, $current_order) {
        if ($current_orderby !== $column) {
            return '<span class="dashicons dashicons-sort" style="opacity: 0.3;"></span>';
        }
        
        $icon = $current_order === 'asc' ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2';
        return '<span class="dashicons ' . $icon . '"></span>';
    }

    private static function get_enabled_payment_methods() {
        $rm_payment_options = get_option('rm_payment_options', []);
        $methods = [];
        
        if (!empty($rm_payment_options['stripe_enabled'])) $methods['stripe'] = 'Card (Stripe)';
        if (!empty($rm_payment_options['paypal_enabled'])) $methods['paypal'] = 'PayPal';
        if (!empty($rm_payment_options['pay_on_arrival_enabled'])) $methods['pay_on_arrival'] = 'Pay on Arrival';
        if (!empty($rm_payment_options['bank_transfer_enabled'])) $methods['bank_transfer'] = 'Bank Transfer';
        
        return $methods;
    }
}