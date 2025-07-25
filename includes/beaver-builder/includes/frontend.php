<?php
/**
 * Frontend template for Reserve Mate Booking Module
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="rm-booking-form-container fl-reserve-mate-booking-<?php echo $id; ?>">
    <?php if ($settings->show_title === 'yes' && !empty($settings->form_title)) : ?>
        <h3 class="rm-booking-form-title"><?php echo esc_html($settings->form_title); ?></h3>
    <?php endif; ?>
    
    <div class="rm-booking-form-wrapper">
        <?php 
        if (shortcode_exists('reserve_mate_booking_form')) {
            echo do_shortcode('[reserve_mate_booking_form]'); 
        } else {
            echo '<div class="rm-booking-error">' . __('Booking form is not available at this time.', 'reserve-mate') . '</div>';
        }
        ?>
    </div>
    
</div>

<style>
.fl-reserve-mate-booking-<?php echo $id; ?> {
    <?php if (!empty($settings->container_padding)) : ?>
        padding: <?php echo is_array($settings->container_padding) ? implode('px ', $settings->container_padding) . 'px' : $settings->container_padding . 'px'; ?>;
    <?php endif; ?>
    
    <?php if (!empty($settings->container_margin)) : ?>
        margin: <?php echo is_array($settings->container_margin) ? implode('px ', $settings->container_margin) . 'px' : $settings->container_margin . 'px'; ?>;
    <?php endif; ?>
    
    <?php if (!empty($settings->background_color)) : ?>
        background-color: #<?php echo $settings->background_color; ?>;
    <?php endif; ?>
    
    <?php if (!empty($settings->border_radius)) : ?>
        border-radius: <?php echo $settings->border_radius; ?>px;
    <?php endif; ?>
}

.fl-reserve-mate-booking-<?php echo $id; ?> .rm-booking-form-title {
    <?php if (!empty($settings->title_color)) : ?>
        color: #<?php echo $settings->title_color; ?>;
    <?php endif; ?>
    
    margin-bottom: 20px;
    margin-top: 0;
}

.fl-reserve-mate-booking-<?php echo $id; ?> .rm-booking-form-wrapper {
    position: relative;
    min-height: 400px;
}

.fl-reserve-mate-booking-<?php echo $id; ?> .rm-booking-form {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Trigger any Reserve Mate initialization that might be needed
    if (typeof window.ReserveMate !== 'undefined' && window.ReserveMate.init) {
        window.ReserveMate.init();
    }
    
    // Ensure form is visible
    $('.fl-reserve-mate-booking-<?php echo $id; ?> .rm-booking-form').show();
});
</script>
