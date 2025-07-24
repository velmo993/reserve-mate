<?php
namespace ReserveMate\Admin\Views;

defined('ABSPATH') or die('No direct access!');

class NotificationViews {
    public static function render() {
        ?>
        <div class="wrap rm-page">
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Notifications updated successfully.', 'reserve-mate') . '</p></div>';
            } ?>
            <h1><?php _e('Manage Notifications', 'reserve-mate'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('rm_notification_options_group');
                do_settings_sections('manage-notifications');
                submit_button(__('Save notifications', 'reserve-mate'));
                ?>
            </form>
        </div>
    <?php }
    
}