<?php
defined('ABSPATH') or die('No direct access!');

function display_daterange_booking_form($property_id, $property_ids, $property_count, $property, $inline_calendar) {
    ob_start();
    ?>
    
    <form id="rm-booking-form" class="date-range-form" method="post">
        <?php if($property_count > 1) : ?>
            <input id="property_ids" name="property_ids" type="hidden" value="<?php echo esc_attr(implode(',', $property_ids)); ?>">
            <select name="choose-apartman" id="choose-apartman">
                <option value="single" selected><?php echo esc_attr($property->name); ?></option>
                <option value="multiple">Book <?php echo esc_attr($property_count); ?> apartments</option>
            </select>
        <?php endif; ?>
        <!--<div class="booking-form-content">-->
            <!--<div class="calendar-legend">-->
            <!--    <div>-->
            <!--        <div class="only_departure">30</div>-->
            <!--        <span>Departure only</span>-->
            <!--    </div>-->
            <!--    <div>-->
            <!--        <div class="reserved">20</div>-->
            <!--        <span>Booked</span>-->
            <!--    </div>-->
            <!--    <div>-->
            <!--        <div class="only_arrival">15</div>-->
            <!--        <span>Arrival only</span>-->
            <!--    </div>-->
            <!--    <div>-->
            <!--        <div class="available">10</div>-->
            <!--        <span>Available</span>-->
            <!--    </div>-->
            <!--</div>-->
        <!--</div>-->
        <div id="nights-count"></div>
        
        <div class="form-inputs booking-form-content">
            <div class="day-selector-wrap form-field">
                <?php if($inline_calendar !== "inline") : ?>
                    <label for="name">Select Date:</label>
                    <input type="text" id="date-range" class="flatpick-r-daterange" placeholder="Check Availability" required>
                    <img src="<?php echo RESERVE_MATE_PLUGIN_URL; ?>assets/images/calendar-color-icon.png" alt="Calendar Icon" class="calendar-color-icon">
                <?php else : ?>
                    <div id="date-range" class="flatpick-r-daterange"></div>
                <?php endif; ?>
                <!-- FlatPicker js calendar comes here -->
            </div>
            <div class="form-field">
                <label for="name">Full Name:</label>
                <input placeholder="Your Name" type="text" id="name" name="name" required>
            </div>
            <div class="form-field">
                <label for="email">Email Address:</label>
                <input placeholder="email@gmail.com" type="email" id="email" name="email" required>
            </div>
            <div class="form-field">
                <label for="phone">Phone:</label>
                <input placeholder="(555) 1234345" type="tel" id="phone" name="phone" required>
            </div>
            
            <?php if($property && $property->allow_children): ?>
                <div class="form-field">
                    <label for="adults">Adults:</label>
                    <select name="adults" id="adults" required>
                        <?php for ($i = 1; $i <= $property->max_adult_number; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i === 2 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="children">Children:</label>
                    <select name="children" id="children" required>
                        <?php for ($i = 0; $i <= $property->max_child_number; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i === 0 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php else : ?>
                <div class="form-field">
                    <label for="adults">Guests:</label>
                    <select name="adults" id="adults" required>
                        <?php for ($i = 1; $i <= $property->max_adult_number; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i === 2 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>   
            <?php if ($property && $property->allow_pets): ?>
                <div class="form-field">
                    <label for="pets">Pets:</label>
                    <select name="pets" id="pets" required>
                        <?php for ($i = 0; $i <= $property->max_pet_number; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i === 0 ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="form-field request-field">
                <label for="client_request">Request:</label>
                <textarea placeholder="Anything you need." name="client_request" id="client_request" maxlength="500"></textarea>
            </div>
            
            <input type="hidden" id="start-date" name="start-date" value="" required>
            <input type="hidden" id="end-date" name="end-date" value="" required>
            <input type="hidden" name="total-payment-cost" id="total-payment-cost" value="">
            <input type="hidden" id="multiple-bookings" name="multiple-bookings" value="false">
        </div>
        
        <div class="book-now-container">
            <div class="agreement-container">
                <p>By continuing, you agree to our <a href="#">Privacy Policy</a> and <a href="#">Terms</a>.</p>
            </div>
            
            <div class="">
                <input type="submit" name="proceed-to-checkout" id="proceed-to-checkout" value="Book Now">
            </div>
        </div>
        <?php if($property_id) : ?>
            <input type="hidden" class="property_id" id="property_id" name="property_id" value="<?php echo esc_attr($property_id); ?>">
        <?php endif; ?>
    </form> 
        
    <?php
    return ob_get_clean();
}