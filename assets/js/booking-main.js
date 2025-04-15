document.addEventListener('DOMContentLoaded', function () {
    // Common variables and utilities that both systems might need
    const flatpickrVars = window.flatpickrVars || {};
    
    // Determine which booking system to use
    const isHourlyBooking = flatpickrVars.bookingSettings?.hourly_booking_enabled === '1';
    
    // Dynamically import the appropriate module based on the booking type
    if (isHourlyBooking) {
        import('./booking-datetime.js').then(module => {
            // console.log('Datetime/timeslot booking module loaded');
        }).catch(error => {
            console.error('Failed to load datetime booking module');
        });
    } else {
        import('./booking-daterange.js').then(module => {
            // console.log('Date range booking module loaded');
        }).catch(error => {
            console.error('Failed to load daterange booking module');
        });
    }
});