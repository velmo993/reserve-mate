<?php

defined('ABSPATH') or die('No direct access!');

use ICal\ICal;

// Handle iCal file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ical_file']) && check_admin_referer('import_ical', 'import_ical_nonce')) {
    $uploaded_file = $_FILES['ical_file']['tmp_name'];
    if (is_uploaded_file($uploaded_file)) {
        $ical = new ICal($uploaded_file);

        // Process each event
        foreach ($ical->events() as $event) {
            // Save event to bookings table
            $wpdb->insert('wp_reservemate_bookings', [
                'start_date' => $event->dtstart,
                'end_date' => $event->dtend,
                'name' => $event->summary,
                'description' => $event->description
            ]);
        }
    }
}

function display_ical_settings_page() {
    $export_url = site_url('?download_ical=1');
    ?>
    <div class="wrap">
        <h1><?php _e('iCal Settings', 'reserve-mate'); ?></h1>
        <p><strong><?php _e('Export iCal URL:', 'reserve-mate'); ?></strong></p>
        <input type="text" readonly value="<?php echo esc_url($export_url); ?>" style="width: 100%;" />
        
        <h2><?php _e('Import iCal', 'reserve-mate'); ?></h2>
        <form method="post" enctype="multipart/form-data" action="">
            <?php wp_nonce_field('import_ical', 'import_ical_nonce'); ?>
            <input type="file" name="ical_file" />
            <button type="submit" class="button-primary"><?php _e('Import', 'reserve-mate'); ?></button>
        </form>
    </div>
    <?php
}