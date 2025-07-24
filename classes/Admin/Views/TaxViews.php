<?php
namespace ReserveMate\Admin\Views;
use ReserveMate\Admin\Helpers\Tax;
use DateTime;

defined('ABSPATH') or die('No direct access!');

class TaxViews {
    public static function render($data = []) {
        ?>
        <div class="wrap rm-page">
            <h1><?php _e('Tax Settings', 'reserve-mate'); ?></h1>

            <div id="tax-form">
                <?php self::tax_form(); ?>
            </div>

            <?php
                self::tax_table($data['taxes']);
            ?>
        </div>
        <?php
    }

    public static function tax_form() {
        ?>
        <form method="post">
            <?php wp_nonce_field('save_tax_nonce', 'tax_nonce'); ?>
            <h2><?php _e('Add New Tax', 'reserve-mate'); ?></h2>
            <input type="text" class="tax-input" name="tax_name" placeholder="<?php _e('Tax Name', 'reserve-mate'); ?>" required>
            <input type="number" class="tax-input" name="tax_rate" placeholder="<?php _e('Rate', 'reserve-mate'); ?>" step="0.01" min="0" required>
            <select name="tax_type">
                <option value="percentage"><?php _e('Percentage', 'reserve-mate'); ?></option>
                <option value="fixed"><?php _e('Fixed Amount', 'reserve-mate'); ?></option>
            </select>
            <input type="submit" name="add_tax" class="button button-primary" value="<?php _e('Add Tax', 'reserve-mate'); ?>">
        </form>
        <?php
    }

    public static function tax_table($taxes) {
        ?>
        <h2><?php _e('Existing Taxes', 'reserve-mate'); ?></h2>
        <table class="wp-list-table widefat striped data-display-table">
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
                        <tr class="tax-tr">
                            <td><?php echo esc_html($tax->name); ?></td>
                            <td><?php echo esc_html($tax->rate); ?><?php if($tax->type === "percentage") { echo '%'; } ?></td>
                            <td><?php echo esc_html($tax->type); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('delete_tax', 'delete_nonce'); ?>
                                    <input type="hidden" name="delete_tax" value="<?php echo esc_attr($tax->id); ?>">
                                    <button type="submit" class="button trash-button" onclick="return confirm('<?php _e('Are you sure you want to delete this?', 'reserve-mate'); ?>')">
                                        <span class="dashicons dashicons-trash"></span>
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

}