<?php

function handle_booking_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin_booking'])) {
        if (!isset($_POST['admin_booking_nonce']) || !wp_verify_nonce($_POST['admin_booking_nonce'], 'save_admin_booking')) {
            error_log("security check failed");
            wp_die('Security check failed');
        }

        $booking_data = [
            'name'           => sanitize_text_field($_POST['name']),
            'email'          => sanitize_email($_POST['email']),
            'phone'          => sanitize_text_field($_POST['phone']),
            'property_id'    => intval($_POST['property_id']),
            'adults'         => intval($_POST['adults']),
            'children'       => isset($_POST['children']) ? intval($_POST['children']) : 0,
            'pets'           => isset($_POST['pets']) ? intval($_POST['pets']) : 0,
            'start_date'     => sanitize_text_field($_POST['start']),
            'end_date'       => sanitize_text_field($_POST['end']),
            'total_cost'     => isset($_POST['total_cost']) ? floatval($_POST['total_cost']) : 0,
            'paid_amount'    => isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : 0,
            'payment_method' => isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '',
            'client_request' => sanitize_textarea_field($_POST['client_request']),
        ];
        
        if(isset($_GET['edit'])) {
            $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;
            update_booking($booking_data, $editing_index);
        } else {
            save_booking_to_db($booking_data['property_id'], $booking_data['name'], $booking_data['email'], $booking_data['phone'], $booking_data['adults'], $booking_data['children'], $booking_data['pets'], $booking_data['start_date'], $booking_data['end_date'], $booking_data['total_cost'], $booking_data['paid_amount'], $booking_data['client_request'], $admin = true);
        }
        
        wp_redirect(admin_url('admin.php?page=manage-bookings'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Booking saved successfully.', 'reserve-mate') . '</p></div>';
    }

    if (isset($_GET['delete'])) {
        if (!isset($_GET['delete_nonce']) || !wp_verify_nonce($_GET['delete_nonce'], 'delete_booking')) {
            wp_die('Security check failed');
        }

        delete_booking(intval($_GET['delete']));
        wp_redirect(admin_url('admin.php?page=manage-bookings'));
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Booking deleted successfully.', 'reserve-mate') . '</p></div>';
    }
}

function display_admin_booking_form($booking = null) {
    $properties = get_properties();
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
    ?>
    <form method="post">
        <?php wp_nonce_field('save_admin_booking', 'admin_booking_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="name">Name</label></th>
                <td><input type="text" name="name" value="<?php echo esc_attr($booking->name ?? ''); ?>" required></td>
            </tr>
            <tr>
                <th><label for="email">Email</label></th>
                <td><input type="email" name="email" value="<?php echo esc_attr($booking->email ?? ''); ?>" required></td>
            </tr>
            <tr>
                <th><label for="phone">Phone</label></th>
                <td><input type="text" name="phone" value="<?php echo esc_attr($booking->phone ?? ''); ?>"></td>
            </tr>
            <tr>
                <th><label for="property_id">Property</label></th>
                <td>
                    <select name="property_id" id="property_id" required>
                        <?php foreach ($properties as $property): ?>
                            <option value="<?php echo $property->id; ?>" 
                                data-max-adults="<?php echo $property->max_adult_number; ?>" 
                                data-max-children="<?php echo $property->max_child_number; ?>" 
                                data-max-pets="<?php echo $property->max_pet_number; ?>" 
                                data-allow-children="<?php echo $property->allow_children; ?>"
                                data-allow-pets="<?php echo $property->allow_pets; ?>"
                                <?php selected($booking->property_id ?? '', $property->id); ?>>
                                <?php echo esc_html($property->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="adults">Adults</label></th>
                <td>
                    <select name="adults" id="adults" required></select>
                </td>
            </tr>
            
            <tr class="children-row">
                <th><label for="children">Children</label></th>
                <td>
                    <select name="children" id="children"></select>
                </td>
            </tr>
            
            <tr class="pets-row">
                <th><label for="pets">Pets</label></th>
                <td>
                    <select name="pets" id="pets"></select>
                </td>
            </tr>
            <tr>
                <th><label for="start">Arrival</label></th>
                <td><input type="date" name="start" value="<?php echo esc_attr($booking->start_date ?? ''); ?>" required></td>
            </tr>
            <tr>
                <th><label for="end">Departure</label></th>
                <td><input type="date" name="end" value="<?php echo esc_attr($booking->end_date ?? ''); ?>" required></td>
            </tr>
            <tr>
                <th><label for="total_cost">Total Cost</label></th>
                <td><input type="number" step="0.01" name="total_cost" value="<?php echo esc_attr($booking->total_cost ?? '0'); ?>" required></td>
            </tr>
            <tr>
                <th><label for="paid_amount">Paid Amount</label></th>
                <td><input type="number" step="0.01" name="paid_amount" value="<?php echo esc_attr($booking->paid_amount ?? '0'); ?>" required></td>
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
            <tr>
                <th><label for="client_request">Client Request</label></th>
                <td><textarea name="client_request"><?php echo esc_textarea($booking->client_request ?? ''); ?></textarea></td>
            </tr>
        </table>
        <p>
            <input type="submit" name="save_admin_booking" class="button button-primary" value="<?php echo $booking ? __('Update Booking', 'reserve-mate') : __('Save Booking', 'reserve-mate'); ?>">
            <?php if ($booking): ?>
                <a href="<?php echo admin_url('admin.php?page=manage-bookings'); ?>" class="button"><?php _e('Cancel', 'reserve-mate'); ?></a>
            <?php endif; ?>
        </p>
    </form>
    <?php
}

function display_manage_bookings_page() {
    handle_booking_form_submission();

    $per_page = 10;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    
    $bookings = get_bookings($per_page, $current_page);
    $editing_index = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $editing_booking = $editing_index ? get_booking($editing_index) : null;

    ?>
    <div class="wrap">
        <h1>Manage Bookings</h1>
        
        <?php if ($editing_booking): ?>
            <h2>Edit Booking</h2>
            <?php display_admin_booking_form($editing_booking); ?>
        <?php else: ?>
            <button id="toggle-form-btn" class="button button-primary" style="margin-bottom: 20px;">
                <?php _e('Add New Booking', 'reserve-mate'); ?>
            </button>
            
            <div id="booking-form" style="display: none;">
                <h2>Add New Booking</h2>
                <?php display_admin_booking_form(); ?>
            </div>
        <?php endif; ?>
        
        <?php display_existing_bookings_table($bookings, $per_page); ?>
    </div>
    <?php
}

function display_existing_bookings_table($bookings, $per_page = 10) {
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $total_items = count_bookings();
    
    ?>
    <h2><?php _e('Existing Bookings', 'reserve-mate'); ?></h2>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'reserve-mate'); ?></th>
                <th><?php _e('Properties', 'reserve-mate'); ?></th>
                <th><?php _e('Total Cost', 'reserve-mate'); ?></th>
                <th><?php _e('Paid Amount', 'reserve-mate'); ?></th>
                <th><?php _e('Details', 'reserve-mate'); ?></th>
                <th><?php _e('Actions', 'reserve-mate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($bookings) : ?>
                <?php 
                // Group bookings by unique session (based on name, email, phone, arrival, departure, created_at)
                $groupedBookings = [];
                foreach ($bookings as $booking) {
                    $groupKey = md5($booking->name . $booking->email . $booking->phone . $booking->start_date . $booking->end_date . $booking->created_at);
                    if (!isset($groupedBookings[$groupKey])) {
                        $groupedBookings[$groupKey] = [
                            'ids' => [],
                            'properties' => [],
                            'total_cost' => 0,
                            'booking' => $booking,
                        ];
                    }
                    $groupedBookings[$groupKey]['ids'][] = $booking->id;
                    $groupedBookings[$groupKey]['properties'][] = get_property($booking->property_id)->name ?? __('Unknown', 'reserve-mate');
                    $groupedBookings[$groupKey]['total_cost'] += floatval($booking->total_cost);
                }
                ?>

                <?php foreach ($groupedBookings as $group) : ?>
                    <tr class="booking-summary">
                        <td><?php echo esc_html(implode(', ', $group['ids'])); ?></td>
                        <td><?php echo esc_html(implode(', ', $group['properties'])); ?></td>
                        <td><?php echo esc_html($group['total_cost'] . ' ' . get_currency()); ?></td>
                        <td><?php echo $group['booking']->paid_amount ? esc_html(format_price($group['booking']->paid_amount) . ' ' . get_currency()) : '0'; ?></td>
                        <td>
                            <button class="toggle-details-booking" data-booking-id="<?php echo esc_attr($group['ids'][0]); ?>"><i>▼</i></button>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=manage-bookings&edit=' . $group['ids'][0] . '&admin_booking_nonce=' . wp_create_nonce('admin_booking_action')); ?>" class="button">
                                ✏️
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=manage-bookings&delete=' . $group['ids'][0] . '&delete_nonce=' . wp_create_nonce('delete_booking')); ?>" 
                                class="button button-danger" 
                                onclick="return confirm('<?php echo esc_attr(__('Are you sure you want to delete this booking?', 'reserve-mate')); ?>');">
                                ❌
                            </a>
                        </td>
                    </tr>
                    <tr class="table-details" id="details-<?php echo esc_attr($group['ids'][0]); ?>" style="display: none;">
                        <td colspan="6">
                            <div class="table-details-flex">
                                <strong><?php _e('Name:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->name); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Email:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->email); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Phone:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->phone); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Adults:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->adults); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Children:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->children); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Pets:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->pets); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Arrival:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->start_date); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Departure:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->end_date); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Payment Method:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->payment_method); ?></span>
                            </div>
                            <div class="table-details-flex">
                                <strong><?php _e('Client Request:', 'reserve-mate'); ?></strong><span class="booking-data"><?php echo esc_html($group['booking']->client_request); ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="6"><?php _e('No bookings found.', 'reserve-mate'); ?></td></tr>
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
    <?php
}
