<?php
defined('ABSPATH') or die('No direct access!');

function display_datetime_booking_form($inline_calendar) {
    $services = get_services();
    ob_start();
    ?>
    
    <form id="rm-booking-form" class="date-time-form" method="post">
        <div class="flatpickr-calendar-container booking-form-content">
            
        </div>
        
        <div class="form-inputs booking-form-content">
            <div class="form-field">
                <!--<label for="name">Full Name:</label>-->
                <input placeholder="Full name" type="text" id="name" name="name" required>
            </div>
            <div class="form-field">
                <!--<label for="email">Email Address:</label>-->
                <input placeholder="Email address" type="email" id="email" name="email" required>
            </div>
            <div class="form-field">
                <!--<label for="phone">Phone:</label>-->
                <input placeholder="Phone number" type="tel" id="phone" name="phone" required>
            </div>
            <div class="form-field">
                <!--<label for="adults">Guests:</label>-->
                <select name="adults" id="adults" required>
                    <option value=""disabled selected>Number of guests</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
            </div>
            
            <div class="form-field">
                <select name="services[]" multiple id="services" required>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service->id; ?>" data-price="<?php echo $service->price; ?>"><?php echo $service->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="date-time-container form-field">
                <?php if($inline_calendar !== "inline") : ?>
                    <input type="text" id="day-selector" class="flatpick-r-datetime" placeholder="Select day">
                    <img src="<?php echo RESERVE_MATE_PLUGIN_URL; ?>assets/images/calendar-color-icon.png" alt="Calendar Icon" class="calendar-color-icon">
                <?php else : ?>
                    <div id="day-selector" class="flatpick-r-datetime"></div>
                <?php endif; ?>
            
                <!-- Hidden fields for final output -->
                <input type="hidden" id="start-date" name="start-date">
                <input type="hidden" id="end-date" name="end-date">
                <input type="hidden" id="staff-id" name="staff-id">
            </div>
            
            <!-- Time slot selection -->
            <div id="time-slot-container" style="display: none;">
                <div id="time-slots"></div>
            </div>
            
            <!-- Staff selection -->
            <div id="staff-selection-container" style="display: none;">
                <h4>Available Staff</h4>
                <div id="staff-options"></div>
            </div>
            
            <input type="hidden" id="start-date" name="start-date" value="" required>
            <input type="hidden" id="end-date" name="end-date" value="" required>
            <input type="hidden" name="total-payment-cost" id="total-payment-cost" value="">
            
            <div class="agreement-container">
                <label for="agreement">I have read and accept the <a>privacy policy</a>.</label>    
                <input id="agreement" name="agreement" type="checkbox" required>
            </div>
            <div class="form-field">
                <input type="submit" name="proceed-to-checkout" id="proceed-to-checkout" value="Book Now">
            </div>
        </div>
    </form>  
    
    <?php
    return ob_get_clean();
}

