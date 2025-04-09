<?php
defined('ABSPATH') or die('No direct access!');

function handle_datetime_booking_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin_datetime_booking'])) {
        if (!isset($_POST['admin_datetime_booking_nonce']) || !wp_verify_nonce($_POST['admin_datetime_booking_nonce'], 'save_admin_datetime_booking')) {
            error_log("Security check failed");
            wp_die('Security check failed');
        }
        
        error_log(print_r($_POST, true));
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $guests = intval($_POST['adults']);
        $start_date = sanitize_text_field($_POST['start_datetime']);
        $end_date = sanitize_text_field($_POST['end_datetime']);
        $total_cost = isset($_POST['total_cost']) ? floatval($_POST['total_cost']) : 0;
        $paid_amount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : 0;
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
        
        $services = [];
        if (!empty($_POST['services']) && is_array($_POST['services'])) {
            foreach ($_POST['services'] as $service_id) {
                $service_id = intval($service_id);
                if ($service_id > 0) { // Validate it's a positive number
                    $service = get_service($service_id);
                    if ($service) { // Only add if service exists
                        $services[] = [
                            'id' => $service_id,
                            'quantity' => 1, // Default quantity
                            'price' => $service->price,
                        ];
                    }
                }
            }
        }
        
        error_log('Services fater: '.print_r($services, true));

        if (isset($_GET['edit'])) {
            $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;
            update_datetime_booking($name, $email, $phone, $guests, $start_date, $end_date, $total_cost, $payment_method, $services, $paid_amount, $editing_index);
        } else {
            save_datetime_booking_to_db($name, $email, $phone, $guests, $start_date, $end_date, $total_cost, $payment_method, $services, $paid_amount, $admin = true);
        }

        wp_redirect(admin_url('admin.php?page=manage-datetime-bookings'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Booking saved successfully.', 'reserve-mate') . '</p></div>';
    }

    if (isset($_GET['delete'])) {
        if (!isset($_GET['delete_nonce']) || !wp_verify_nonce($_GET['delete_nonce'], 'delete_datetime_booking')) {
            wp_die('Security check failed');
        }

        delete_datetime_booking(intval($_GET['delete']));
        wp_redirect(admin_url('admin.php?page=manage-datetime-bookings'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Booking deleted successfully.', 'reserve-mate') . '</p></div>';
    }
}

function display_datetime_bookings_page() {
    handle_datetime_booking_form_submission();

    $bookings = get_datetime_bookings();
    $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $editing_booking = $editing_index ? get_datetime_booking($editing_index) : null;

    ?>
    <div class="wrap">
        <h1><?php echo $editing_booking ? 'Edit Booking' : 'Add New Booking'; ?></h1>
        <?php display_admin_datetime_booking_form($editing_booking); ?>
        <?php display_existing_datetime_bookings_table($bookings); ?>
    </div>
    <?php
}

function display_admin_datetime_booking_form($booking = null) {
    $services = get_services();
    $payment_settings = get_option('payment_settings', []);
    $payment_methods = [
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'pay_on_arrival' => 'Pay on Arrival',
        'bank_transfer' => 'Bank Transfer'
    ];
    $enabled_payment_methods = [];
    if (!empty($payment_settings['stripe_enabled'])) {
        $enabled_payment_methods['stripe'] = 'Card (Stripe)';
    }
    if (!empty($payment_settings['paypal_enabled'])) {
        $enabled_payment_methods['paypal'] = 'PayPal';
    }
    if (!empty($payment_settings['pay_on_arrival_enabled'])) {
        $enabled_payment_methods['pay_on_arrival'] = 'Pay on Arrival';
    }
    if (!empty($payment_settings['bank_transfer_enabled'])) {
        $enabled_payment_methods['bank_transfer'] = 'Bank Transfer';
    }
    $selected_payment_method = $booking->payment_method ?? '';
    
    // Get booking services if editing
    $booking_services = [];
    if ($booking && $booking->id) {
        $booking_services = get_booking_services($booking->id);
    }
    ?>
    <form method="post" id="booking-form">
        <?php wp_nonce_field('save_admin_datetime_booking', 'admin_datetime_booking_nonce'); ?>
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
                <th><label for="adults">Guests</label></th>
                <td>
                    <select name="adults" id="adults" required>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($booking->guests ?? 1, $i); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
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
                    <select id="services" name="services[]" multiple="multiple" class="services">
                        <option></option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?php echo $service->id; ?>" data-price="<?php echo $service->price; ?>"><?php echo $service->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <input type="hidden" id="services-field" name="services-field">
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
            <input type="submit" id="save-booking-btn" name="save_admin_datetime_booking" class="button button-primary" value="<?php echo $booking ? __('Update Booking', 'reserve-mate') : __('Save Booking', 'reserve-mate'); ?>">
            <?php if ($booking): ?>
                <a href="<?php echo admin_url('admin.php?page=manage-datetime-bookings'); ?>" class="button"><?php _e('Cancel', 'reserve-mate'); ?></a>
            <?php endif; ?>
        </p>
    </form>
    <?php
}

function display_existing_datetime_bookings_table($bookings) {
    ?>
    <h2><?php _e('Existing Bookings', 'reserve-mate'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'reserve-mate'); ?></th>
                <th><?php _e('Services', 'reserve-mate'); ?></th>
                <th><?php _e('Total Cost', 'reserve-mate'); ?></th>
                <th><?php _e('Paid Amount', 'reserve-mate'); ?></th>
                <th><?php _e('Details', 'reserve-mate'); ?></th>
                <th><?php _e('Actions', 'reserve-mate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($bookings) : ?>
                <?php foreach ($bookings as $booking) : ?>
                    <tr>
                        <td><?php echo esc_html($booking->id); ?></td>
                        <td>
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
                        </td>
                        <td><?php echo $booking->total_cost ? esc_html(format_price($booking->total_cost) . ' ' . get_currency()) : '0'; ?></td>
                        <td><?php echo $booking->paid_amount ? esc_html(format_price($booking->paid_amount) . ' ' . get_currency()) : '0'; ?></td>
                        <td>
                            <button class="toggle-details-booking" data-booking-id="<?php echo esc_attr($booking->id); ?>"><i>▼</i></button>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=manage-datetime-bookings&edit=' . $booking->id . '&admin_datetime_booking_nonce=' . wp_create_nonce('admin_datetime_booking_action')); ?>" class="button">
                                <?php _e('Edit', 'reserve-mate'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=manage-datetime-bookings&delete=' . $booking->id . '&delete_nonce=' . wp_create_nonce('delete_datetime_booking')); ?>" 
                                class="button button-danger" 
                                onclick="return confirm('<?php echo esc_attr(__('Are you sure you want to delete this booking?', 'reserve-mate')); ?>');">
                                <?php _e('Delete', 'reserve-mate'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr class="table-details" id="details-<?php echo esc_attr($booking->id); ?>" style="display: none;">
                        <td colspan="6">
                            <div class="table-details-flex">
                                <strong><?php _e('Name:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($booking->name); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Email:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($booking->email); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Phone:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($booking->phone); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Guests:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($booking->guests); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Start Date & Time:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($booking->start_datetime); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('End Date & Time:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($booking->end_datetime); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Payment Method:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($booking->payment_method); ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="6"><?php _e('No bookings found.', 'reserve-mate'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}