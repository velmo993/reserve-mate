(function() {    
    const bookingForm = document.getElementById('rm-booking-form');
    const today = new Date();
    const tomorrow = new Date().setDate(today.getDate() + 1);
    const timeSlotContainer = document.getElementById('time-slot-container');
    const timeSlotsElement = document.getElementById('time-slots');
    let startDateField = document.getElementById('start-date');
    let endDateField = document.getElementById('end-date');
    let selectedTimeSlotButton = null;
    let timezone = flatpickrVars.timezone;
    let locale = flatpickrVars.locale;
    
    // Initialize the system
    init();
    
    function init() {
        fetchDateTimeBookings();
    }
    
    function fetchDateTimeBookings() {
        const url = `${flatpickrVars.ajaxurl}?action=get_date_time_bookings_data`;
        fetch(url)
            .then(response => response.json())
            .then((bookingsData) => {
                let bookedDates = new Set();
                if (bookingsData.success) {
                    bookedDates = bookingsData.data.map(booking => ({
                        start: new Date(booking.start_datetime),
                        end: new Date(booking.end_datetime)
                    }));
                    let settings = bookingsData.settings;
                    initializeDaySelector(bookedDates, settings);
                }
            })
            .catch((error) => {
                // console.error('Error fetching booked datetimes:', error);
                initializeDaySelector([]);
            });
    }
    
    function initializeDaySelector(bookedDates, settings) {    
        // Get the list of fully booked days
        const fullyBookedDays = [];
        const today = new Date();
        const endDate = new Date();
        endDate.setFullYear(today.getFullYear() + 1); // Check for the next year
    
        // Check if today should be disabled
        if (isTodayFullyPast(settings)) {
            fullyBookedDays.push(new Date(today));
        }
    
        for (let day = new Date(today); day <= endDate; day.setDate(day.getDate() + 1)) {
            if (isDayFullyBooked(new Date(day), bookedDates, settings)) {
                fullyBookedDays.push(new Date(day));
            }
        }
    
        window.flatpickrInstance = flatpickr('#day-selector', {
            enableTime: false,
            dateFormat: "Y-m-d",
            minDate: "today",
            disable: [
                ...bookedDates.map(booking => ({
                    from: booking.start,
                    to: booking.end
                })),
                ...fullyBookedDays,
                isPreviousDay
            ],
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 1) {
                    const selectedDay = selectedDates[0];
                    timeSlotContainer.style.display = "block";
                    if (selectedTimeSlotButton) {
                        selectedTimeSlotButton.classList.remove('selected-slot'); // Clear previous selection
                        selectedTimeSlotButton = null;
                    }
                
                    loadTimeSlots(selectedDay, bookedDates, settings);
                    
                    timeSlotContainer.classList.remove('open');
                    void timeSlotContainer.offsetWidth; // Trigger reflow
                    timeSlotContainer.classList.add('open');
                }
            }
        });
    
        initTimeSlotsEventListener();
        document.dispatchEvent(new Event("flatpickrInstance"));
        
        // Trigger next day by default to show the time slots
        // const tomorrow = new Date();
        // tomorrow.setDate(tomorrow.getDate() + 1);
        // const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
        // window.flatpickrInstance.setDate(tomorrowFormatted, true);
        // loadTimeSlots(tomorrow, bookedDates, settings);
    }
    
    function loadTimeSlots(selectedDay, bookedDates, settings) {
        timeSlotsElement.innerHTML = "";
    
        const [minHour, minMinute] = settings.min_time.split(':').map(Number);
        const [maxHour, maxMinute] = settings.max_time.split(':').map(Number);
        const interval = settings.interval;
        const breakDuration = settings.break_duration || 0;
    
        // Parse selectedDay properly
        const selectedDate = new Date(selectedDay);
        
        // Get current time in the SELECTED TIMEZONE (not browser time)
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString('locale', { timeZone: timezone }));
        
        // Create time slots
        let currentTime = new Date(selectedDate);
        currentTime.setHours(minHour, minMinute, 0, 0);
        
        const endTime = new Date(selectedDate);
        endTime.setHours(maxHour, maxMinute, 0, 0);
    
        let allBooked = true;
    
        while (currentTime < endTime) {
            const timeSlotStart = new Date(currentTime);
            const timeSlotEnd = new Date(timeSlotStart);
            timeSlotEnd.setMinutes(timeSlotStart.getMinutes() + interval);
    
            if (timeSlotEnd > endTime) {
                break;
            }
    
            // Compare with the time in the selected timezone
            const isToday = nowInSelectedTZ.getFullYear() === selectedDate.getFullYear() && 
                           nowInSelectedTZ.getMonth() === selectedDate.getMonth() && 
                           nowInSelectedTZ.getDate() === selectedDate.getDate();
                           
            // Only disable if it's today AND the current time (in selected TZ) is after the slot start
            const isPast = isToday && nowInSelectedTZ > timeSlotStart;
    
            const isBooked = bookedDates.some(booking => {
                const bookingStart = new Date(booking.start);
                const bookingEnd = new Date(booking.end);
                return timeSlotStart >= bookingStart && timeSlotEnd <= bookingEnd;
            });
    
            if (!isBooked && !isPast) {
                allBooked = false;
            }
    
            const timeSlotButton = document.createElement('button');
            timeSlotButton.textContent = `${formatSimpleTime(timeSlotStart)} - ${formatSimpleTime(timeSlotEnd)}`;
            timeSlotButton.disabled = isBooked || isPast;
            
            if (isPast) {
                timeSlotButton.classList.add('past-time-slot');
            }
            
            // Create time values with correct timezone offset for data-attributes
            // These represent the local time in the selected timezone
            const localTimeStart = createLocalTimeString(timeSlotStart);
            const localTimeEnd = createLocalTimeString(timeSlotEnd);
            
            timeSlotButton.dataset.start = localTimeStart;
            timeSlotButton.dataset.end = localTimeEnd;
            
            timeSlotsElement.appendChild(timeSlotButton);
    
            currentTime.setMinutes(currentTime.getMinutes() + interval + breakDuration);
        }
    
        if (allBooked) {
            window.flatpickrInstance.set('disable', [...window.flatpickrInstance.config.disable, selectedDay]);
        }
    }
    
    function createLocalTimeString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        // Get hours and minutes in the correct timezone
        const timeParts = formatSimpleTime(date).split(':');
        const hours = timeParts[0];
        const minutes = timeParts[1];
        
        // Create a timezone-aware ISO string that represents the local time
        // Format: 2025-04-09T10:30:00.000Z for 10:30 AM
        return `${year}-${month}-${day}T${hours}:${minutes}:00.000Z`;
    }
    
    function isDayFullyBooked(day, bookedDates, settings) {
        const [minHour, minMinute] = settings.min_time.split(':').map(Number);
        const [maxHour, maxMinute] = settings.max_time.split(':').map(Number);
        const interval = settings.interval;
    
        const dayDate = new Date(day);
        
        let currentTime = new Date(dayDate);
        currentTime.setHours(minHour, minMinute, 0, 0);
    
        const endTime = new Date(dayDate);
        endTime.setHours(maxHour, maxMinute, 0, 0);
    
        // Get current time in SELECTED TIMEZONE
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
    
        while (currentTime < endTime) {
            const timeSlotStart = new Date(currentTime);
            const timeSlotEnd = new Date(timeSlotStart);
            timeSlotEnd.setMinutes(timeSlotStart.getMinutes() + interval);
    
            // Compare with the time in the selected timezone
            const isToday = nowInSelectedTZ.getFullYear() === dayDate.getFullYear() && 
                           nowInSelectedTZ.getMonth() === dayDate.getMonth() && 
                           nowInSelectedTZ.getDate() === dayDate.getDate();
                           
            // Only consider a slot "past" if it's today AND current time is after slot start
            const isPast = isToday && nowInSelectedTZ > timeSlotStart;
    
            const isBooked = bookedDates.some(booking => {
                const bookingStart = new Date(booking.start);
                const bookingEnd = new Date(booking.end);
                return timeSlotStart >= bookingStart && timeSlotEnd <= bookingEnd;
            });
    
            if (!isBooked && !isPast) {
                return false; // At least one time slot is available
            }
    
            currentTime.setMinutes(currentTime.getMinutes() + interval);
        }
    
        return true; // All time slots are booked or in the past
    }
    
    function isTodayFullyPast(settings) {
        // Get current time in SELECTED TIMEZONE
        const now = new Date();
        const nowInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
        
        const [maxHour, maxMinute] = settings.max_time.split(':').map(Number);
        
        // Create a date object for today with the max time
        const todayMax = new Date(nowInSelectedTZ);
        todayMax.setHours(maxHour, maxMinute, 0, 0);
        
        return nowInSelectedTZ > todayMax;
    }
    
    function isPreviousDay(date) {
        // Get current date in SELECTED TIMEZONE
        const now = new Date();
        const todayInSelectedTZ = new Date(now.toLocaleString(locale, { timeZone: timezone }));
        
        // Create a date object for the date we're checking
        const checkDate = new Date(date);
        
        // Compare dates (ignoring time)
        return checkDate.setHours(0,0,0,0) < todayInSelectedTZ.setHours(0,0,0,0);
    }
    
    // Add this new function that doesn't use timezone conversion
    function formatSimpleTime(date) {
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    }
    
    function initTimeSlotsEventListener() {
        timeSlotsElement.addEventListener('click', function (e) {
            e.preventDefault();
            if (e.target.tagName === 'BUTTON' && !e.target.disabled) {
                const startTime = e.target.dataset.start;
                const endTime = e.target.dataset.end;
                selectTimeSlot(startTime, endTime);
                
                if (selectedTimeSlotButton) {
                    selectedTimeSlotButton.classList.remove('selected-slot');
                }
                selectedTimeSlotButton = e.target;
                selectedTimeSlotButton.classList.add('selected-slot');
            }
        });
    }
    
    function selectTimeSlot(startTime, endTime) {
        // These values should now already be in the correct timezone format
        startDateField.value = convertISOToFormattedDate(startTime);
        endDateField.value = convertISOToFormattedDate(endTime);
    }
    
    function convertISOToFormattedDate(isoString) {
        // Parse the ISO string
        const dateParts = isoString.split('T')[0].split('-');
        const timeParts = isoString.split('T')[1].split(':');
        
        // Create yyyy-MM-dd HH:mm format
        return `${dateParts[0]}-${dateParts[1]}-${dateParts[2]} ${timeParts[0]}:${timeParts[1]}`;
    }
    
    function formatTime(date) {
        return new Intl.DateTimeFormat('default', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: timezone
        }).format(date);
    }
    
    function formatDateTime(date) {
        // Format date and time with respect to the user's timezone
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: timezone
        };
        
        const parts = new Intl.DateTimeFormat(locale, options).formatToParts(date);
        const values = {};
        
        for (const part of parts) {
            values[part.type] = part.value;
        }
        
        // yyyy-MM-dd HH:mm format
        return `${values.year}-${values.month}-${values.day} ${values.hour}:${values.minute}`;
    }
})();