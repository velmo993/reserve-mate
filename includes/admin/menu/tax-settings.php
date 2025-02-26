<?php

function register_tax_settings() {
    register_setting('tax_settings_group', 'tax_settings', array(
        'sanitize_callback' => 'sanitize_tax_settings'
    ));
}

add_action('admin_init', 'register_tax_settings');

function manage_tax_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Tax Settings', 'reserve-mate'); ?></h1>

        <form method="post">
            <h2><?php _e('Add New Tax', 'reserve-mate'); ?></h2>
            <input type="text" class="tax-input" name="tax_name" placeholder="<?php _e('Tax Name', 'reserve-mate'); ?>" required>
            <input type="number" class="tax-input" name="tax_rate" placeholder="<?php _e('Rate', 'reserve-mate'); ?>" step="0.01" min="0" required>
            <select name="tax_type">
                <option value="percentage"><?php _e('Percentage', 'reserve-mate'); ?></option>
                <option value="fixed"><?php _e('Fixed Amount', 'reserve-mate'); ?></option>
                <option value="per_person_per_night"><?php _e('Per Person Per Night', 'reserve-mate'); ?></option>
            </select>
            <button type="submit" name="add_tax" class="button button-primary"><?php _e('Add Tax', 'reserve-mate'); ?></button>
        </form>

        <?php
        // Handle new tax submission
        if (isset($_POST['add_tax'])) {
            add_tax($_POST['tax_name'], floatval($_POST['tax_rate']), $_POST['tax_type']);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Tax added successfully.', 'reserve-mate') . '</p></div>';
        }

        list_taxes_in_admin();
        ?>
    </div>
    <?php
}

function list_taxes_in_admin() {
    $taxes = get_taxes();

    // Handle tax deletion
    if (isset($_POST['delete_tax'])) {
        delete_tax($_POST['delete_tax']);
        echo '<div class="notice notice-warning is-dismissible"><p>' . __('Tax deleted successfully.', 'reserve-mate') . '</p></div>';
    }
    ?>
    <h2><?php _e('Existing Taxes', 'reserve-mate'); ?></h2>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Tax Name', 'reserve-mate'); ?></th>
                <th><?php _e('Rate', 'reserve-mate'); ?></th>
                <th><?php _e('Type', 'reserve-mate'); ?></th>
                <th><?php _e('Actions', 'reserve-mate'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($taxes)) : ?>
                <?php foreach ($taxes as $tax) : ?>
                    <tr>
                        <td><?php echo esc_html($tax->name); ?></td>
                        <td><?php echo esc_html($tax->rate); ?><?php if($tax->type === "percentage") { echo '%'; } ?></td>
                        <td><?php echo esc_html($tax->type); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="delete_tax" value="<?php echo esc_attr($tax->id); ?>">
                                <button type="submit" class="button button-link-delete" onclick="return confirm('<?php _e('Are you sure you want to delete this tax?', 'reserve-mate'); ?>')">
                                    ❌
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php _e('No taxes added yet.', 'reserve-mate'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}