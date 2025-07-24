<?php
namespace ReserveMate\Admin\Views;

defined('ABSPATH') or die('No direct access!');

class ICalViews {
    public static function render($data = []) {
        ?>
        <div class="wrap">
            <h1><?php _e('iCal Settings', 'reserve-mate'); ?></h1>
            <p><strong><?php _e('Export iCal URL:', 'reserve-mate'); ?></strong></p>
            <input type="text" readonly value="<?php echo esc_url($data['export_url']); ?>" style="width: 100%;" />
            
            <h2><?php _e('Import iCal', 'reserve-mate'); ?></h2>
            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field('import_ical', 'import_ical_nonce'); ?>
                <input type="file" name="ical_file" />
                <button type="submit" class="button-primary"><?php _e('Import', 'reserve-mate'); ?></button>
            </form>
        </div>
    <?php }
    
}